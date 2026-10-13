/**
 * VDownload Pro - Extension Popup Logic (Standalone Safe Engine)
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */

document.addEventListener('DOMContentLoaded', async () => {
  const DEFAULT_SERVER_URL = 'http://localhost/vdownload';

  // Navigation Tabs
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabPanes = document.querySelectorAll('.tab-pane');

  // Downloader Tab Elements
  const detectedPlatformBadge = document.getElementById('detectedPlatformBadge');
  const detectedUrlText = document.getElementById('detectedUrlText');
  const btnDownloadActiveTab = document.getElementById('btnDownloadActiveTab');
  const btnDownloadActiveLabel = document.getElementById('btnDownloadActiveLabel');
  const inputCustomUrl = document.getElementById('inputCustomUrl');
  const btnPasteClipboard = document.getElementById('btnPasteClipboard');
  const btnClearUrl = document.getElementById('btnClearUrl');
  const btnExtractCustomUrl = document.getElementById('btnExtractCustomUrl');

  // Loading & Alert Elements
  const loadingState = document.getElementById('loadingState');
  const loaderText = document.getElementById('loaderText');
  const errorAlert = document.getElementById('errorAlert');
  const errorMessage = document.getElementById('errorMessage');
  const btnErrorRetry = document.getElementById('btnErrorRetry');

  // Result Card Elements
  const resultCard = document.getElementById('resultCard');
  const resultPlatformTag = document.getElementById('resultPlatformTag');
  const btnCloseResult = document.getElementById('btnCloseResult');
  const resultThumbImg = document.getElementById('resultThumbImg');
  const resultAuthor = document.getElementById('resultAuthor');
  const resultCaption = document.getElementById('resultCaption');
  const resultQualityBadge = document.getElementById('resultQualityBadge');
  const resultDurationBadge = document.getElementById('resultDurationBadge');
  const resultDownloadsList = document.getElementById('resultDownloadsList');
  const resultToast = document.getElementById('resultToast');
  const resultToastText = document.getElementById('resultToastText');
  const btnOpenInWebResult = document.getElementById('btnOpenInWebResult');

  // Tools Tab Elements
  const btnReloadExtension = document.getElementById('btnReloadExtension');
  const btnReloadExtensionAction = document.getElementById('btnReloadExtensionAction');
  const btnOpenTutorial = document.getElementById('btnOpenTutorial');
  const serverBaseUrlInput = document.getElementById('serverBaseUrlInput');
  const btnSaveServerUrl = document.getElementById('btnSaveServerUrl');

  let activeTabUrl = '';
  let lastFailedUrl = '';

  // 1. Tab Switching Logic
  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab');
      tabButtons.forEach((b) => b.classList.remove('active'));
      tabPanes.forEach((p) => p.classList.add('d-none'));

      btn.classList.add('active');
      const targetPane = document.getElementById(targetId);
      if (targetPane) targetPane.classList.remove('d-none');
    });
  });

  // 2. Load Server URL from storage (Optional config)
  let serverBaseUrl = DEFAULT_SERVER_URL;
  try {
    const stored = await chrome.storage.sync.get(['serverBaseUrl']);
    if (stored && stored.serverBaseUrl) {
      serverBaseUrl = stored.serverBaseUrl.replace(/\/+$/, '');
      if (serverBaseUrlInput) serverBaseUrlInput.value = serverBaseUrl;
    }
  } catch (e) {
    // Keep default
  }

  // 3. Inspect Active Tab on popup launch
  try {
    const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tabs && tabs[0] && tabs[0].url) {
      activeTabUrl = tabs[0].url;
      detectedUrlText.textContent = activeTabUrl;

      const platform = (typeof VDownloadExtractor !== 'undefined') 
        ? VDownloadExtractor.detectPlatform(activeTabUrl) 
        : detectPlatformFallback(activeTabUrl);

      if (platform) {
        detectedPlatformBadge.textContent = platform === 'ytmusic' ? 'YT MUSIC' : platform.toUpperCase();
        detectedPlatformBadge.className = `platform-badge ${platform}`;
        btnDownloadActiveTab.disabled = false;
        btnDownloadActiveLabel.textContent = `Unduh Media di Tab Ini (${platform === 'ytmusic' ? 'YT MUSIC' : platform.toUpperCase()})`;
      } else {
        detectedPlatformBadge.textContent = 'BUKAN MEDIA';
        detectedPlatformBadge.className = 'platform-badge';
        btnDownloadActiveTab.disabled = true;
        btnDownloadActiveLabel.textContent = 'Tab ini Bukan Media yang Didukung';
      }
    }
  } catch (err) {
    detectedUrlText.textContent = 'Gagal mengakses tab aktif.';
  }

  // 4. Handle "Unduh Video Tab Ini" (Direct Standalone Extraction)
  btnDownloadActiveTab?.addEventListener('click', () => {
    if (!activeTabUrl) return;
    performExtraction(activeTabUrl);
  });

  // 5. Handle Clipboard Paste & Custom Input
  btnPasteClipboard?.addEventListener('click', async () => {
    try {
      const text = await navigator.clipboard.readText();
      if (text && inputCustomUrl) {
        inputCustomUrl.value = text.trim();
        btnClearUrl?.classList.remove('d-none');
        inputCustomUrl.focus();
      }
    } catch (err) {
      inputCustomUrl.focus();
    }
  });

  inputCustomUrl?.addEventListener('input', () => {
    if (inputCustomUrl.value.trim().length > 0) {
      btnClearUrl?.classList.remove('d-none');
    } else {
      btnClearUrl?.classList.add('d-none');
    }
  });

  btnClearUrl?.addEventListener('click', () => {
    if (inputCustomUrl) {
      inputCustomUrl.value = '';
      btnClearUrl.classList.add('d-none');
      inputCustomUrl.focus();
    }
  });

  // 6. Handle "Ekstrak & Unduh Langsung"
  btnExtractCustomUrl?.addEventListener('click', () => {
    const url = inputCustomUrl?.value.trim();
    if (!url) {
      showError('Silakan tempel atau ketikkan tautan video YouTube, TikTok, Shopee, atau IG terlebih dahulu.');
      return;
    }
    performExtraction(url);
  });

  // Retry on error
  btnErrorRetry?.addEventListener('click', () => {
    if (lastFailedUrl) performExtraction(lastFailedUrl);
  });

  // Close Result Card
  btnCloseResult?.addEventListener('click', () => {
    resultCard?.classList.add('d-none');
  });

  // 7. Core Standalone Extraction Function
  async function performExtraction(url) {
    lastFailedUrl = url;
    hideAllStatus();
    showLoading('Mengekstrak berkas media kualitas True HD...');

    try {
      if (typeof VDownloadExtractor === 'undefined') {
        throw new Error('Modul ekstraksi mandiri tidak termuat dengan benar.');
      }

      let media = null;
      try {
        media = await VDownloadExtractor.extractMedia(url);
      } catch (extErr) {
        console.warn('VDownloadExtractor error:', extErr);
      }

      // DOM fallback if current active tab is the page
      if (!media || !media.downloads || media.downloads.length === 0) {
        try {
          const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
          if (tabs && tabs[0] && tabs[0].id) {
            const domRes = await new Promise((resolve) => {
              chrome.tabs.sendMessage(tabs[0].id, { action: 'get_dom_media' }, (res) => {
                if (chrome.runtime.lastError || !res || !res.success) {
                  resolve(null);
                } else {
                  resolve(res.data);
                }
              });
            });
            if (domRes && domRes.downloads && domRes.downloads.length > 0) {
              media = domRes;
            }
          }
        } catch (tabErr) {
          console.warn('DOM fallback error:', tabErr);
        }
      }

      if (!media || !media.downloads || media.downloads.length === 0) {
        throw new Error('Tidak dapat mengekstrak berkas media dari tautan ini. Pastikan server VDownload aktif di http://localhost/vdownload atau postingan tidak diprivat.');
      }

      displayResult(media, url);
    } catch (err) {
      hideLoading();
      showError(err.message || 'Gagal mengekstrak berkas media. Pastikan tautan valid dan postingan bersifat publik.');
    }
  }

  // 8. Render Extracted Results inside Popup
  function displayResult(media, sourceUrl) {
    hideLoading();
    hideError();

    resultPlatformTag.textContent = (media.platformName || media.platform || 'VIDEO').toUpperCase();
    resultAuthor.textContent = `@${media.author || 'creator'}`;
    resultCaption.textContent = media.title || 'Video Tanpa Judul';
    resultQualityBadge.textContent = media.downloads[0]?.quality || '⭐ True HD';
    resultDurationBadge.textContent = media.duration || '00:00';

    if (media.thumbnail) {
      resultThumbImg.src = media.thumbnail;
      resultThumbImg.onerror = () => { resultThumbImg.src = 'icons/icon128.png'; };
    } else {
      resultThumbImg.src = 'icons/icon128.png';
    }

    // Build dynamic download buttons
    resultDownloadsList.innerHTML = '';
    media.downloads.forEach((item, index) => {
      const btn = document.createElement('button');
      let typeClass = 'hd';
      let icon = '📥';
      if (item.type === 'zip') {
        typeClass = 'zip';
        icon = '📦';
      } else if (item.type === 'audio') {
        typeClass = 'audio';
        icon = '🎵';
      } else if (item.type === 'image') {
        typeClass = 'image';
        icon = '🖼️';
      } else if (item.quality && (item.quality.includes('360p') || item.quality.includes('SD') || item.quality.includes('Hemat'))) {
        typeClass = 'sd';
        icon = '⚡';
      } else if (item.quality && item.quality.includes('720p')) {
        typeClass = 'sd';
        icon = '🎬';
      } else if (item.quality && (item.quality.includes('1080p') || item.quality.includes('Full HD') || item.quality.includes('Master'))) {
        typeClass = 'hd';
        icon = '⭐';
      } else if (index > 0) {
        typeClass = 'sd';
        icon = '🎬';
      }

      btn.className = `btn-dl-option ${typeClass}`;
      btn.innerHTML = `
        <span class="btn-dl-label">
          <span>${icon}</span>
          <span>${item.label}</span>
        </span>
        <span class="btn-dl-tag">${item.quality || 'Download'}</span>
      `;

      btn.addEventListener('click', async () => {
        let downloadTargetUrl = item.url;
        const isLarge = (item.size_bytes && item.size_bytes >= 30 * 1024 * 1024) || (item.quality && item.quality.includes('MB') && parseFloat(item.quality.match(/(\d+(?:\.\d+)?)\s*MB/)?.[1] || 0) >= 30);

        if (serverBaseUrl && isLarge && item.type === 'video') {
          const fileSize = item.size_formatted || (item.quality.match(/(\d+(?:\.\d+)?\s*MB)/)?.[1]) || '30+ MB';
          const doCompress = confirm(`Ukuran video ini cukup besar (${fileSize}).\n\nIngin dikompresi Full HD Cerdas (GPU NVENC) agar hemat kuota tanpa pecah?\n\n- Klik [OK] untuk Kompresi Full HD (Jernih & Cepat)\n- Klik [Batal] untuk Langsung Unduh Berkas Asli (${fileSize})`);
          
          if (doCompress) {
            showToast(`⏳ Mengompresi Full HD (${fileSize}) dengan akselerasi GPU, mohon tunggu sebentar...`);
            try {
              const resp = await fetch(`${serverBaseUrl}/download/compress`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  url: item.url,
                  name: item.filename || 'video.mp4',
                  type: item.type || 'video',
                  audio_url: item.audio_url || '',
                  size_bytes: item.size_bytes || 0
                })
              });
              const json = await resp.json();
              if (json && json.success && json.download_url) {
                showToast(`🎉 Selesai dikompres (${json.compressed_size})! Mengunduh berkas HD...`);
                triggerDirectDownload(json.download_url, json.filename || item.filename, btn);
                return;
              } else {
                showToast(`⚠️ Server menyarankan berkas asli: ${json?.error || 'Mengunduh langsung...'}`);
              }
            } catch (err) {
              console.warn('Compression fallback to raw:', err);
              showToast('⚠️ Gagal terhubung ke kompresor server, mengunduh berkas asli...');
            }
          }
        }

        if (serverBaseUrl && !item.url.includes('/download/file') && !item.url.includes('/download/zip')) {
          let proxyUrl = `${serverBaseUrl}/download/file?url=${encodeURIComponent(item.url)}&name=${encodeURIComponent(item.filename || 'media')}&type=${encodeURIComponent(item.type || 'video')}`;
          if (item.audio_url) {
            proxyUrl += `&audio_url=${encodeURIComponent(item.audio_url)}`;
          }
          if (item.source_url) {
            proxyUrl += `&source_url=${encodeURIComponent(item.source_url)}`;
          }
          downloadTargetUrl = proxyUrl;
        }
        triggerDirectDownload(downloadTargetUrl, item.filename, btn);
      });

      resultDownloadsList.appendChild(btn);
    });

    // Fallback web link
    if (btnOpenInWebResult) {
      btnOpenInWebResult.onclick = (e) => {
        e.preventDefault();
        const webUrl = `${serverBaseUrl}/?url=${encodeURIComponent(sourceUrl)}`;
        chrome.tabs.create({ url: webUrl });
      };
    }

    resultCard.classList.remove('d-none');
    resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // 9. Direct Download Handler using chrome.downloads API
  function triggerDirectDownload(url, filename, buttonEl) {
    showToast(`⏳ Mengunduh: ${filename}...`);

    const originalText = buttonEl ? buttonEl.innerHTML : '';
    if (buttonEl) {
      buttonEl.style.opacity = '0.7';
      buttonEl.style.pointerEvents = 'none';
    }

    try {
      if (typeof chrome !== 'undefined' && chrome.downloads && typeof chrome.downloads.download === 'function') {
        chrome.downloads.download({
          url: url,
          filename: filename || 'vdownload_video.mp4',
          saveAs: false
        }, (downloadId) => {
          if (chrome.runtime.lastError) {
            chrome.tabs.create({ url: url });
          } else {
            showToast(`✅ Berhasil diunduh! Cek folder Downloads komputer Anda.`);
          }
          restoreButton(buttonEl, originalText);
        });
      } else {
        chrome.tabs.create({ url: url });
        restoreButton(buttonEl, originalText);
      }
    } catch (err) {
      chrome.tabs.create({ url: url });
      restoreButton(buttonEl, originalText);
    }
  }

  function restoreButton(btn, originalHtml) {
    setTimeout(() => {
      if (btn) {
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
        if (originalHtml) btn.innerHTML = originalHtml;
      }
    }, 1500);
  }

  // 10. Toast Notification
  function showToast(message) {
    if (!resultToast || !resultToastText) return;
    resultToastText.textContent = message;
    resultToast.classList.remove('d-none');
    setTimeout(() => {
      resultToast.classList.add('d-none');
    }, 4500);
  }

  // 11. Loading & Error Helpers
  function showLoading(msg) {
    if (loaderText) loaderText.textContent = msg;
    loadingState?.classList.remove('d-none');
  }

  function hideLoading() {
    loadingState?.classList.add('d-none');
  }

  function showError(msg) {
    if (errorMessage) errorMessage.textContent = msg;
    errorAlert?.classList.remove('d-none');
  }

  function hideError() {
    errorAlert?.classList.add('d-none');
  }

  function hideAllStatus() {
    hideLoading();
    hideError();
  }

  // 12. Setup Reload Handlers
  const setupReloadHandler = (buttonElement) => {
    if (!buttonElement) return;
    buttonElement.addEventListener('click', (e) => {
      e.preventDefault();
      buttonElement.classList.add('reloading-active');
      const label = buttonElement.querySelector('.reload-text, .reload-btn-label');
      if (label) label.textContent = 'Mereload...';

      setTimeout(() => {
        try {
          if (typeof chrome !== 'undefined' && chrome.runtime && typeof chrome.runtime.reload === 'function') {
            chrome.runtime.reload();
          }
        } catch (reloadErr) {
          // quiet
        }
      }, 250);
    });
  };

  setupReloadHandler(btnReloadExtension);
  setupReloadHandler(btnReloadExtensionAction);

  // 13. Tutorial Link
  btnOpenTutorial?.addEventListener('click', (e) => {
    e.preventDefault();
    const tutorialUrl = `${serverBaseUrl}/extension`;
    chrome.tabs.create({ url: tutorialUrl });
  });

  // 14. Server Base URL Config
  btnSaveServerUrl?.addEventListener('click', async () => {
    const newUrl = serverBaseUrlInput?.value.trim().replace(/\/+$/, '');
    if (!newUrl) return;

    try {
      await chrome.storage.sync.set({ serverBaseUrl: newUrl });
      serverBaseUrl = newUrl;
      btnSaveServerUrl.textContent = 'Tersimpan!';
      setTimeout(() => { btnSaveServerUrl.textContent = 'Simpan'; }, 2000);
    } catch (err) {
      alert('Gagal menyimpan URL: ' + err.message);
    }
  });

  function detectPlatformFallback(url) {
    if (!url) return null;
    const u = url.toLowerCase();
    if (u.includes('music.youtube.com')) return 'ytmusic';
    if (u.includes('youtube.com') || u.includes('youtu.be')) return 'youtube';
    if (u.includes('tiktok.com') || u.includes('douyin.com') || u.includes('vt.tiktok')) return 'tiktok';
    if (u.includes('instagram.com') || u.includes('instagr.am')) return 'instagram';
    if (u.includes('shopee.co.id') || u.includes('shopee.com') || u.includes('shp.ee') || u.includes('sv.shopee')) return 'shopee';
    return null;
  }
});
