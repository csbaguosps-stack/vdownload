/**
 * VDownload Pro - Extension Background Service Worker (Standalone Engine)
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */

// Import standalone extractor logic into service worker
try {
  importScripts('extractor.js');
} catch (e) {
  console.warn('VDownload background importScripts warning:', e);
}

const DEFAULT_SERVER_URL = 'http://localhost/vdownload';

// Setup Context Menus on Install
chrome.runtime.onInstalled.addListener(() => {
  try {
    if (chrome.storage && chrome.storage.sync) {
      chrome.storage.sync.get(['serverBaseUrl'], (res) => {
        if (!res || !res.serverBaseUrl) {
          chrome.storage.sync.set({ serverBaseUrl: DEFAULT_SERVER_URL });
        }
      });
    }

    if (chrome.contextMenus && typeof chrome.contextMenus.create === 'function') {
      chrome.contextMenus.create({
        id: 'vdownload_quick',
        title: '📥 Unduh Video ini (VDownload Standalone HD)',
        contexts: ['link', 'page', 'video']
      }, () => {
        if (chrome.runtime.lastError) {
          // Handled quietly
        }
      });
    }
  } catch (err) {
    console.warn('VDownload background install warning:', err);
  }
});

// Handle Messages from Popup or Content Scripts
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'extract_media') {
    if (typeof VDownloadExtractor === 'undefined') {
      sendResponse({ success: false, error: 'Modul ekstraksi tidak tersedia.' });
      return true;
    }

    VDownloadExtractor.extractMedia(request.url)
      .then((data) => {
        sendResponse({ success: true, data: data });
      })
      .catch((err) => {
        sendResponse({ success: false, error: err.message || 'Gagal mengekstrak video.' });
      });
    return true; // Keep message channel open for async response
  }

  if (request.action === 'download_file') {
    const downloadOpts = {
      url: request.url,
      filename: request.filename || 'vdownload_video.mp4',
      saveAs: false
    };

    chrome.downloads.download(downloadOpts, (downloadId) => {
      if (chrome.runtime.lastError) {
        sendResponse({ success: false, error: chrome.runtime.lastError.message });
      } else {
        sendResponse({ success: true, downloadId: downloadId });
      }
    });
    return true;
  }

});

// Handle Context Menu Click (Standalone 1-Click HD Download)
if (chrome.contextMenus && chrome.contextMenus.onClicked) {
  chrome.contextMenus.onClicked.addListener(async (info, tab) => {
    try {
      if (info.menuItemId === 'vdownload_quick') {
        const targetUrl = info.linkUrl || info.srcUrl || info.pageUrl || '';
        if (!targetUrl) return;

        if (typeof VDownloadExtractor !== 'undefined') {
          try {
            const data = await VDownloadExtractor.extractMedia(targetUrl);
            if (data && data.downloads && data.downloads.length > 0) {
              const bestDownload = data.downloads[0];
              chrome.downloads.download({
                url: bestDownload.url,
                filename: bestDownload.filename || 'video_hd.mp4',
                saveAs: false
              });
              return;
            }
          } catch (e) {
            console.warn('Direct extraction failed in context menu, falling back to web:', e);
          }
        }

        // Fallback to web interface
        chrome.storage.sync.get(['serverBaseUrl'], (res) => {
          const base = (res && res.serverBaseUrl) ? res.serverBaseUrl.replace(/\/+$/, '') : DEFAULT_SERVER_URL;
          const fullUrl = `${base}/?url=${encodeURIComponent(targetUrl)}`;
          chrome.tabs.create({ url: fullUrl });
        });
      }
    } catch (err) {
      console.warn('VDownload background click warning:', err);
    }
  });
}
