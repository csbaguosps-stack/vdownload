/**
 * VDownload Pro - Web Content Script (Standalone In-Page Downloader)
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */

(function () {
  let cachedExtraction = null;
  let isExtracting = false;

  function isSupportedVideoPage(url) {
    const u = url.toLowerCase();
    return (
      (u.includes('music.youtube.com') && (u.includes('/watch') || u.includes('/playlist') || u.includes('/channel') || u.includes('/album'))) ||
      (u.includes('youtube.com') && (u.includes('/watch') || u.includes('/shorts/'))) ||
      (u.includes('youtu.be/')) ||
      (u.includes('tiktok.com') && (u.includes('/video/') || u.includes('/v/') || u.includes('/photo/'))) ||
      (u.includes('instagram.com') && (u.includes('/reel/') || u.includes('/p/'))) ||
      (u.includes('shopee.co.id') && (u.includes('share-video') || u.includes('/video/'))) ||
      (u.includes('shp.ee'))
    );
  }

  function extractMediaFromDOM() {
    const downloads = [];
    const images = [];

    // Check for Instagram images
    const igImgs = document.querySelectorAll('article img, main img, div[role="dialog"] img');
    igImgs.forEach((img) => {
      let src = img.src || '';
      if (img.srcset) {
        const parts = img.srcset.split(',');
        const lastPart = parts[parts.length - 1].trim().split(' ')[0];
        if (lastPart) src = lastPart;
      }
      if (src && (src.includes('cdninstagram.com') || src.includes('fbcdn.net'))) {
        if (!images.includes(src)) {
          images.push(src);
          downloads.push({
            type: 'image',
            label: `Unduh Foto #${images.length} (Kualitas HD)`,
            quality: '⭐ HD Foto',
            filename: `instagram_photo_${images.length}.jpg`,
            url: src
          });
        }
      }
    });

    // Check for Instagram videos
    const igVideos = document.querySelectorAll('article video, main video, div[role="dialog"] video');
    igVideos.forEach((vid, idx) => {
      const src = vid.src || '';
      if (src && !src.startsWith('blob:')) {
        downloads.push({
          type: 'video',
          label: `Unduh Video #${downloads.length + 1} (Kualitas HD)`,
          quality: '⭐ True HD',
          filename: `instagram_video_${idx + 1}.mp4`,
          url: src
        });
      }
    });

    if (downloads.length > 0) {
      if (images.length > 1) {
        const zipQuery = new URLSearchParams();
        images.forEach(u => zipQuery.append('urls[]', u));
        zipQuery.append('name', 'instagram_photos.zip');
        downloads.unshift({
          type: 'zip',
          label: `Unduh Semua Foto Sekaligus (${images.length} Foto Full HD)`,
          quality: '⭐ Paket ZIP',
          icon: 'badge-hd',
          filename: 'instagram_photos.zip',
          url: `http://localhost/vdownload/download/zip?${zipQuery.toString()}`
        });
      }

      return {
        platformName: 'Instagram',
        title: document.title || 'Instagram Media',
        author: 'Instagram User',
        thumbnail: images[0] || '',
        downloads: downloads
      };
    }
    return null;
  }

  // Handle messages from popup or background
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'get_dom_media') {
      const media = extractMediaFromDOM();
      sendResponse({ success: !!media, data: media });
      return true;
    }
  });

  function injectDownloadPill() {
    if (document.getElementById('vdownload-floating-btn')) return;
    if (!isSupportedVideoPage(window.location.href)) return;

    const pill = document.createElement('div');
    pill.id = 'vdownload-floating-btn';
    pill.className = 'vdownload-pill-container';

    pill.innerHTML = `
      <div class="vdownload-pill-wrapper">
        <button class="vdownload-btn-main" title="Unduh Video / Foto ini Langsung">
          <span class="vdownload-icon">📥</span>
          <span class="vdownload-text">Unduh Media HD</span>
        </button>
        <button class="vdownload-btn-close" title="Tutup">&times;</button>
      </div>
      <div class="vdownload-panel" id="vdownload-floating-panel" style="display: none;"></div>
    `;

    document.body.appendChild(pill);

    const mainBtn = pill.querySelector('.vdownload-btn-main');
    const closeBtn = pill.querySelector('.vdownload-btn-close');
    const panel = pill.querySelector('#vdownload-floating-panel');

    mainBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (panel.style.display === 'none' || !panel.style.display) {
        openStandalonePanel(panel);
      } else {
        panel.style.display = 'none';
      }
    });

    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      pill.style.display = 'none';
    });
  }

  function openStandalonePanel(panel) {
    panel.style.display = 'flex';
    const currentUrl = window.location.href;

    if (cachedExtraction && cachedExtraction.originalUrl === currentUrl) {
      renderPanelData(panel, cachedExtraction.data);
      return;
    }

    // Render loading state
    panel.innerHTML = `
      <div class="vdownload-panel-header">
        <div class="vdownload-panel-title">
          <span>⚡</span> VDownload Pro Standalone
        </div>
        <button class="vdownload-panel-close">&times;</button>
      </div>
      <div class="vdownload-panel-loading">
        <div class="vdownload-spinner"></div>
        <div>Mengekstrak berkas media kualitas True HD...</div>
      </div>
      <div class="vdownload-panel-footer">
        <span>by cs.baguosps@gmail.com</span>
      </div>
    `;

    panel.querySelector('.vdownload-panel-close').onclick = () => {
      panel.style.display = 'none';
    };

    isExtracting = true;
    chrome.runtime.sendMessage({ action: 'extract_media', url: currentUrl }, (res) => {
      isExtracting = false;
      if (!res || !res.success || !res.data || !res.data.downloads || res.data.downloads.length === 0) {
        const domMedia = extractMediaFromDOM();
        if (domMedia) {
          cachedExtraction = { originalUrl: currentUrl, data: domMedia };
          renderPanelData(panel, domMedia);
          return;
        }

        panel.innerHTML = `
          <div class="vdownload-panel-header">
            <div class="vdownload-panel-title">⚠️ Gagal Ekstrak</div>
            <button class="vdownload-panel-close">&times;</button>
          </div>
          <div style="font-size:12px;color:#dc2626;padding:10px 0;">
            ${res?.error || 'Gagal mengekstrak berkas media ini secara otomatis.'}
          </div>
          <div class="vdownload-btn-group">
            <button class="vdownload-action-btn vdownload-btn-secondary" id="vdownload-btn-retry">Coba Lagi</button>
            <button class="vdownload-action-btn vdownload-btn-hd" id="vdownload-btn-open-web">Buka di Web VDownload</button>
          </div>
        `;
        panel.querySelector('.vdownload-panel-close').onclick = () => { panel.style.display = 'none'; };
        const retryBtn = panel.querySelector('#vdownload-btn-retry');
        if (retryBtn) retryBtn.onclick = () => openStandalonePanel(panel);
        const webBtn = panel.querySelector('#vdownload-btn-open-web');
        if (webBtn) {
          webBtn.onclick = () => {
            chrome.storage.sync.get(['serverBaseUrl'], (stored) => {
              const base = stored?.serverBaseUrl || 'http://localhost/vdownload';
              window.open(`${base}/?url=${encodeURIComponent(currentUrl)}`, '_blank');
            });
          };
        }
        return;
      }

      cachedExtraction = { originalUrl: currentUrl, data: res.data };
      renderPanelData(panel, res.data);
    });
  }

  function renderPanelData(panel, data) {
    const downloads = data.downloads || [];
    let buttonsHtml = '';

    downloads.forEach((item, index) => {
      const isPrimary = index === 0;
      const btnClass = isPrimary ? 'vdownload-btn-hd' : 'vdownload-btn-secondary';
      buttonsHtml += `
        <button class="vdownload-action-btn ${btnClass}" 
          data-url="${encodeURIComponent(item.url)}" 
          data-filename="${encodeURIComponent(item.filename)}"
          data-size="${item.size_bytes || 0}"
          data-type="${item.type || 'video'}"
          data-quality="${encodeURIComponent(item.quality || '')}"
          data-label="${encodeURIComponent(item.label || '')}"
          data-audio-url="${encodeURIComponent(item.audio_url || '')}"
          data-source-url="${encodeURIComponent(item.source_url || '')}">
          📥 ${item.label}
        </button>
      `;
    });

    panel.innerHTML = `
      <div class="vdownload-panel-header">
        <div class="vdownload-panel-title">
          <span>⚡</span> ${data.platformName || 'Video'} Siap Diunduh
        </div>
        <button class="vdownload-panel-close">&times;</button>
      </div>

      <div class="vdownload-media-preview">
        ${data.thumbnail ? `<img src="${data.thumbnail}" class="vdownload-media-thumb" alt="Thumb" />` : ''}
        <div class="vdownload-media-meta">
          <div class="vdownload-media-author">@${data.author || 'Creator'}</div>
          <div class="vdownload-media-caption">${data.title || 'Video Tanpa Watermark'}</div>
        </div>
      </div>

      <div class="vdownload-btn-group">
        ${buttonsHtml}
      </div>

      <div id="vdownload-panel-toast" class="vdownload-panel-toast" style="display: none;"></div>

      <div class="vdownload-panel-footer">
        <span>Standalone Mode</span>
        <a href="#" class="vdownload-web-link" id="vdownload-link-web">Buka Web</a>
      </div>
    `;

    panel.querySelector('.vdownload-panel-close').onclick = () => {
      panel.style.display = 'none';
    };

    // Wire up download buttons
    const toast = panel.querySelector('#vdownload-panel-toast');
    panel.querySelectorAll('.vdownload-action-btn').forEach((btn) => {
      btn.addEventListener('click', async () => {
        let url = decodeURIComponent(btn.getAttribute('data-url'));
        let filename = decodeURIComponent(btn.getAttribute('data-filename'));
        const sizeBytes = parseInt(btn.getAttribute('data-size') || '0', 10);
        const itemType = btn.getAttribute('data-type') || 'video';
        const quality = decodeURIComponent(btn.getAttribute('data-quality') || '');
        const label = decodeURIComponent(btn.getAttribute('data-label') || '');

        const isLarge = (sizeBytes >= 30 * 1024 * 1024) || 
          (quality.includes('MB') && parseFloat(quality.match(/(\d+(?:\.\d+)?)\s*MB/)?.[1] || 0) >= 30) ||
          (label.includes('MB') && parseFloat(label.match(/(\d+(?:\.\d+)?)\s*MB/)?.[1] || 0) >= 30);

        if (itemType === 'video' && isLarge) {
          const sizeStr = (quality.match(/(\d+(?:\.\d+)?\s*MB)/)?.[1]) || (label.match(/(\d+(?:\.\d+)?\s*MB)/)?.[1]) || '30+ MB';
          const doCompress = confirm(`Ukuran video ini cukup besar (${sizeStr}).\n\nIngin dikompresi Full HD Cerdas (GPU NVENC) agar hemat kuota tanpa pecah?\n\n- [OK] : Kompresi Full HD Jernih\n- [Batal] : Unduh Berkas Asli Langsung (${sizeStr})`);

          if (doCompress) {
            toast.style.display = 'block';
            toast.textContent = `⏳ Mengompresi Full HD (${sizeStr}) via server...`;

            try {
              const stored = await chrome.storage.sync.get(['serverBaseUrl']);
              const base = stored?.serverBaseUrl || 'http://localhost/vdownload';
              const resp = await fetch(`${base}/download/compress`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  url: url,
                  name: filename || 'video.mp4',
                  type: 'video',
                  size_bytes: sizeBytes
                })
              });
              const json = await resp.json();
              if (json && json.success && json.download_url) {
                toast.textContent = `🎉 Selesai dikompres (${json.compressed_size})! Mengunduh...`;
                url = json.download_url;
                if (json.filename) filename = json.filename;
              }
            } catch (err) {
              console.warn('Compression error:', err);
              toast.textContent = `⚠️ Beralih ke unduhan berkas asli...`;
            }
          }
        }

        let targetDownloadUrl = url;
        if (!url.includes('/download/file') && !url.includes('/download/zip') && (itemType === 'video' || itemType === 'audio')) {
          try {
            const stored = await chrome.storage.sync.get(['serverBaseUrl']);
            const base = stored?.serverBaseUrl || 'http://localhost/vdownload';
            let proxyUrl = `${base}/download/file?url=${encodeURIComponent(url)}&name=${encodeURIComponent(filename || 'media')}&type=${encodeURIComponent(itemType || 'video')}`;
            const audioParam = btn.getAttribute('data-audio-url');
            if (audioParam) proxyUrl += `&audio_url=${audioParam}`;
            const sourceParam = btn.getAttribute('data-source-url');
            if (sourceParam) proxyUrl += `&source_url=${sourceParam}`;
            targetDownloadUrl = proxyUrl;
          } catch (e) {
            // Keep direct url
          }
        }

        toast.style.display = 'block';
        toast.textContent = `⏳ Mengunduh: ${filename}...`;

        chrome.runtime.sendMessage({
          action: 'download_file',
          url: targetDownloadUrl,
          filename: filename
        }, (res) => {
          if (res && res.success) {
            toast.textContent = `✅ Unduhan dimulai! Cek folder Downloads Anda.`;
          } else {
            // Fallback: open clean download link
            window.open(url, '_blank');
            toast.textContent = `✅ File dialihkan ke pengunduh browser.`;
          }
          setTimeout(() => { toast.style.display = 'none'; }, 4000);
        });
      });
    });

    const webLink = panel.querySelector('#vdownload-link-web');
    if (webLink) {
      webLink.onclick = (e) => {
        e.preventDefault();
        chrome.storage.sync.get(['serverBaseUrl'], (stored) => {
          const base = stored?.serverBaseUrl || 'http://localhost/vdownload';
          window.open(`${base}/?url=${encodeURIComponent(window.location.href)}`, '_blank');
        });
      };
    }
  }

  // Inject when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectDownloadPill);
  } else {
    injectDownloadPill();
  }

  // Observe YouTube / YouTube Music SPA navigation
  window.addEventListener('yt-navigate-finish', () => {
    cachedExtraction = null;
    setTimeout(injectDownloadPill, 800);
  });

  // Observe general URL changes (SPA navigation on TikTok/Instagram)
  let lastUrl = location.href;
  new MutationObserver(() => {
    const url = location.href;
    if (url !== lastUrl) {
      lastUrl = url;
      cachedExtraction = null;
      setTimeout(injectDownloadPill, 1000);
    }
  }).observe(document, { subtree: true, childList: true });
})();
