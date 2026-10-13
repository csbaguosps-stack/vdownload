/**
 * VDownload Pro - Client App JavaScript
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 * Handles platform detection, AJAX video fetch, player binding, clipboard API
 */

document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = window.APP_CONFIG ? window.APP_CONFIG.baseUrl : '';

    // DOM Elements
    const form = document.getElementById('downloadForm');
    const input = document.getElementById('videoUrlInput');
    const btnSubmit = document.getElementById('btnSubmitDownload');
    const btnSubmitText = btnSubmit?.querySelector('.btn-text');
    const btnSubmitIcon = btnSubmit?.querySelector('.btn-icon');
    const submitSpinner = document.getElementById('submitSpinner');
    const btnClear = document.getElementById('btnClearInput');
    const btnPaste = document.getElementById('btnPasteClipboard');
    const platformIcon = document.getElementById('inputPlatformIcon');
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const loadingSkeleton = document.getElementById('loadingSkeleton');
    const resultContainer = document.getElementById('resultContainer');

    // Result Card Elements
    const previewVideo = document.getElementById('previewVideoPlayer');
    const previewImageFallback = document.getElementById('previewImageFallback');
    const galleryControls = document.getElementById('galleryControls');
    const btnGalleryPrev = document.getElementById('btnGalleryPrev');
    const btnGalleryNext = document.getElementById('btnGalleryNext');
    const galleryCounter = document.getElementById('galleryCounter');
    const audioPreviewBlock = document.getElementById('audioPreviewBlock');
    const previewAudio = document.getElementById('previewAudioPlayer');
    const resultPlatformBadge = document.getElementById('resultPlatformBadge');
    const resultAccent = document.getElementById('resultAccent');
    const authorAvatar = document.getElementById('authorAvatar');
    const authorName = document.getElementById('authorName');
    const authorMeta = document.getElementById('authorMeta');
    const videoDuration = document.getElementById('videoDuration');
    const durationBadgeContainer = document.getElementById('durationBadgeContainer');
    const videoResolutionBadge = document.getElementById('videoResolutionBadge');
    const videoResolutionText = document.getElementById('videoResolutionText');
    const noWatermarkBadge = document.getElementById('noWatermarkBadge');
    const videoTitle = document.getElementById('videoTitle');
    const statLikes = document.getElementById('statLikes');
    const statComments = document.getElementById('statComments');
    const statShares = document.getElementById('statShares');
    const sharesStatWrapper = document.getElementById('sharesStatWrapper');
    const downloadButtonsList = document.getElementById('downloadButtonsList');
    const btnCopyDirectLink = document.getElementById('btnCopyDirectLink');
    const btnResetView = document.getElementById('btnResetView');

    // Platform Filter Tabs
    const platformTabs = document.querySelectorAll('.btn-platform-tab');
    const sampleLinks = document.querySelectorAll('.sample-link');

    let currentMediaData = null;
    let currentGallery = [];
    let currentGalleryIndex = 0;

    function showGalleryImage(index) {
        if (!currentGallery || currentGallery.length === 0) return;
        if (index < 0) index = currentGallery.length - 1;
        if (index >= currentGallery.length) index = 0;
        currentGalleryIndex = index;
        if (previewImageFallback) {
            previewImageFallback.src = currentGallery[currentGalleryIndex];
        }
        if (galleryCounter) {
            galleryCounter.textContent = `${currentGalleryIndex + 1} / ${currentGallery.length}`;
        }
    }

    btnGalleryPrev?.addEventListener('click', (e) => {
        e.stopPropagation();
        showGalleryImage(currentGalleryIndex - 1);
    });

    btnGalleryNext?.addEventListener('click', (e) => {
        e.stopPropagation();
        showGalleryImage(currentGalleryIndex + 1);
    });

    /**
     * Detect platform from URL string
     */
    function detectPlatform(url) {
        const u = url.toLowerCase();
        if (u.includes('music.youtube.com')) {
            return 'ytmusic';
        }
        if (u.includes('youtube.com') || u.includes('youtu.be')) {
            return 'youtube';
        }
        if (u.includes('tiktok.com') || u.includes('douyin.com') || u.includes('vt.tiktok')) {
            return 'tiktok';
        }
        if (u.includes('instagram.com') || u.includes('instagr.am')) {
            return 'instagram';
        }
        if (u.includes('shopee.co.id') || u.includes('shopee.com') || u.includes('shp.ee') || u.includes('sv.shopee')) {
            return 'shopee';
        }
        return null;
    }

    /**
     * Update Input Icon & styling based on input text
     */
    function updateInputVisuals() {
        const val = input.value.trim();

        // Toggle clear button
        if (val.length > 0) {
            btnClear?.classList.remove('d-none');
        } else {
            btnClear?.classList.add('d-none');
        }

        // Platform icon detection
        const platform = detectPlatform(val);
        if (platform === 'ytmusic') {
            platformIcon.innerHTML = '<i class="bi bi-music-note-beamed fs-4 text-danger"></i>';
        } else if (platform === 'youtube') {
            platformIcon.innerHTML = '<i class="bi bi-youtube fs-4 text-danger"></i>';
        } else if (platform === 'tiktok') {
            platformIcon.innerHTML = '<i class="bi bi-tiktok fs-4 text-info"></i>';
        } else if (platform === 'instagram') {
            platformIcon.innerHTML = '<i class="bi bi-instagram fs-4 text-danger"></i>';
        } else if (platform === 'shopee') {
            platformIcon.innerHTML = '<i class="bi bi-bag-check-fill fs-4 text-warning"></i>';
        } else {
            platformIcon.innerHTML = '<i class="bi bi-link-45deg fs-4 text-secondary"></i>';
        }
    }

    input?.addEventListener('input', updateInputVisuals);

    // Clear Button
    btnClear?.addEventListener('click', () => {
        input.value = '';
        updateInputVisuals();
        input.focus();
    });

    // Paste from Clipboard Button
    btnPaste?.addEventListener('click', async () => {
        try {
            if (navigator.clipboard && navigator.clipboard.readText) {
                const text = await navigator.clipboard.readText();
                if (text) {
                    input.value = text.trim();
                    updateInputVisuals();
                    showToast('Tautan berhasil ditempel dari papan klip!', 'success');
                    processDownload(text.trim());
                }
            } else {
                showToast('Fitur clipboard tidak diizinkan oleh browser.', 'warning');
            }
        } catch (err) {
            input.focus();
            showToast('Silakan tempel secara manual (Ctrl+V)', 'info');
        }
    });

    // Sample Links Auto-Run
    sampleLinks.forEach(btn => {
        btn.addEventListener('click', () => {
            const sampleUrl = btn.getAttribute('data-url');
            if (sampleUrl && input) {
                input.value = sampleUrl;
                updateInputVisuals();
                processDownload(sampleUrl);
            }
        });
    });

    // Platform Tab Filter clicks
    platformTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            platformTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const filter = tab.getAttribute('data-platform');
            if (filter === 'all') {
                input.placeholder = 'Tempel tautan video YouTube, YT Music, TikTok, Instagram, atau Shopee di sini...';
            } else if (filter === 'ytmusic') {
                input.placeholder = 'Tempel tautan YouTube Music (contoh: https://music.youtube.com/watch?v=...)...';
            } else if (filter === 'youtube') {
                input.placeholder = 'Tempel tautan YouTube video / musik (contoh: https://www.youtube.com/watch?v=...)...';
            } else if (filter === 'tiktok') {
                input.placeholder = 'Tempel tautan TikTok (contoh: https://vt.tiktok.com/...)...';
            } else if (filter === 'instagram') {
                input.placeholder = 'Tempel tautan Instagram Reels (contoh: https://www.instagram.com/reel/...)...';
            } else if (filter === 'shopee') {
                input.placeholder = 'Tempel tautan Shopee Video (contoh: https://sv.shopee.co.id/share-video/...)...';
            }
            input.focus();
        });
    });

    // Form Submission
    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        const url = input.value.trim();
        if (!url) {
            showError('Silakan masukkan tautan video terlebih dahulu.');
            return;
        }
        processDownload(url);
    });

    /**
     * Main Function: Send AJAX request to /download/fetch
     */
    async function processDownload(url) {
        hideError();
        setLoadingState(true);

        try {
            const response = await fetch(`${baseUrl}/download/fetch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ url: url })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Gagal memproses video.');
            }

            renderResult(data.data);
            showToast('Video berhasil dianalisis! Siap diunduh.', 'success');
        } catch (err) {
            showError(err.message);
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Render Result Card with Metadata & Action Buttons
     */
    function renderResult(media) {
        currentMediaData = media;

        // Platform Styling
        const platform = media.platform || 'video';
        resultPlatformBadge.className = `badge badge-platform badge-${platform}`;
        resultPlatformBadge.textContent = media.platform_name || platform.toUpperCase();

        if (platform === 'ytmusic') {
            resultAccent.style.background = 'linear-gradient(135deg, #e11d48 0%, #be123c 50%, #881337 100%)';
        } else if (platform === 'youtube') {
            resultAccent.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #991b1b 100%)';
        } else if (platform === 'shopee') {
            resultAccent.style.background = 'var(--gradient-shopee)';
        } else if (platform === 'tiktok') {
            resultAccent.style.background = 'var(--gradient-tiktok)';
        } else if (platform === 'instagram') {
            resultAccent.style.background = 'var(--gradient-instagram)';
        } else {
            resultAccent.style.background = 'var(--gradient-primary)';
        }

        // Author Info
        authorName.textContent = media.author || 'Creator';
        authorMeta.textContent = media.album ? `${media.album} • ${media.platform_name}` : `${media.platform_name} Official`;
        if (media.author_avatar) {
            authorAvatar.src = media.author_avatar;
            authorAvatar.classList.remove('d-none');
        } else {
            authorAvatar.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23334155"/><text x="50" y="55" font-size="35" text-anchor="middle" fill="%23fff">👤</text></svg>';
        }

        // Title / Caption
        videoTitle.textContent = media.title || 'Video Media';

        // Duration & Resolution
        if (media.duration) {
            videoDuration.textContent = media.duration;
            videoDuration.classList.remove('d-none');
        } else {
            videoDuration.classList.add('d-none');
        }

        if (media.resolution) {
            if (videoResolutionText) videoResolutionText.textContent = media.resolution;
            videoResolutionBadge?.classList.remove('d-none');
        } else {
            videoResolutionBadge?.classList.add('d-none');
        }

        if (media.duration || media.resolution) {
            durationBadgeContainer.classList.remove('d-none');
        } else {
            durationBadgeContainer.classList.add('d-none');
        }

        // Stats
        statLikes.textContent = formatNumber(media.stats?.likes || 0);
        statComments.textContent = formatNumber(media.stats?.comments || 0);
        if (media.stats?.shares) {
            statShares.textContent = formatNumber(media.stats.shares);
            sharesStatWrapper.classList.remove('d-none');
        } else {
            sharesStatWrapper.classList.add('d-none');
        }

        // Video & Photo Gallery Preview
        currentGallery = Array.isArray(media.gallery) ? media.gallery : [];
        currentGalleryIndex = 0;

        if (media.preview_video) {
            previewVideo.classList.remove('d-none');
            previewImageFallback.classList.add('d-none');
            galleryControls?.classList.add('d-none');
            previewVideo.src = media.preview_video;
            if (media.thumbnail) {
                previewVideo.poster = media.thumbnail;
            }
            previewVideo.load();
        } else if (currentGallery.length > 0) {
            previewVideo.classList.add('d-none');
            if (previewVideo) previewVideo.src = '';
            previewImageFallback.classList.remove('d-none');
            previewImageFallback.src = currentGallery[0];
            if (currentGallery.length > 1) {
                galleryControls?.classList.remove('d-none');
                if (galleryCounter) {
                    galleryCounter.textContent = `1 / ${currentGallery.length}`;
                }
            } else {
                galleryControls?.classList.add('d-none');
            }
        } else if (media.thumbnail) {
            previewVideo.classList.add('d-none');
            if (previewVideo) previewVideo.src = '';
            galleryControls?.classList.add('d-none');
            previewImageFallback.classList.remove('d-none');
            previewImageFallback.src = media.thumbnail;
        }

        // Watermark badge label
        if (media.media_type === 'photo' || media.media_type === 'carousel' || currentGallery.length > 0) {
            if (noWatermarkBadge) {
                noWatermarkBadge.innerHTML = '<i class="bi bi-images me-1"></i>Foto HD Asli';
            }
        } else {
            if (noWatermarkBadge) {
                noWatermarkBadge.innerHTML = '<i class="bi bi-shield-check me-1"></i>No Watermark HD';
            }
        }

        // Audio Preview (if available)
        if (media.preview_audio) {
            audioPreviewBlock.classList.remove('d-none');
            previewAudio.src = media.preview_audio;
            previewAudio.load();
        } else {
            audioPreviewBlock.classList.add('d-none');
            if (previewAudio) previewAudio.src = '';
        }

        // Render Download Buttons
        downloadButtonsList.innerHTML = '';
        if (Array.isArray(media.downloads) && media.downloads.length > 0) {
            media.downloads.forEach(dl => {
                const btn = document.createElement('a');
                let streamUrl = '';
                if (dl.type === 'zip') {
                    streamUrl = dl.url;
                } else {
                    streamUrl = `${baseUrl}/download/file?url=${encodeURIComponent(dl.url)}&name=${encodeURIComponent(dl.filename || 'media')}&type=${encodeURIComponent(dl.type || 'video')}`;
                    if (dl.audio_url) {
                        streamUrl += `&audio_url=${encodeURIComponent(dl.audio_url)}`;
                    }
                    if (dl.source_url) {
                        streamUrl += `&source_url=${encodeURIComponent(dl.source_url)}`;
                    }
                }
                
                btn.href = streamUrl;
                btn.className = `btn btn-download-option ${dl.btn_class || 'btn-gradient-primary'} text-decoration-none`;
                
                btn.innerHTML = `
                    <div class="d-flex align-items-center gap-2 text-start flex-grow-1 min-w-0 me-2">
                        <i class="bi ${dl.icon || 'bi-download'} fs-5 flex-shrink-0"></i>
                        <span class="btn-download-title text-break">${dl.label}</span>
                    </div>
                    <span class="badge download-pill-badge flex-shrink-0">
                        ${dl.quality || 'HD'}
                    </span>
                `;

                // Check if file is large (> 30 MB)
                const isLarge = isLargeMedia(dl);

                btn.addEventListener('click', (e) => {
                    if (isLarge && dl.type === 'video') {
                        e.preventDefault();
                        openCompressionDialog(dl, streamUrl);
                    } else if (dl.type === 'zip') {
                        showToast('📦 Mengemas dan mengunduh paket ZIP semua foto...', 'info');
                    } else if (dl.type === 'image') {
                        showToast('🖼️ Mengunduh foto kualitas HD...', 'info');
                    } else {
                        showToast('⚡ Mengunduh berkas...', 'info');
                    }
                });

                downloadButtonsList.appendChild(btn);
            });
        }

        // Show container and smooth scroll
        resultContainer.classList.remove('d-none');
        resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Reset View Button
    btnResetView?.addEventListener('click', () => {
        resultContainer.classList.add('d-none');
        if (previewVideo) {
            previewVideo.pause();
            previewVideo.src = '';
        }
        if (previewAudio) {
            previewAudio.pause();
            previewAudio.src = '';
        }
        input.value = '';
        updateInputVisuals();
        input.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Copy Direct Link Button
    btnCopyDirectLink?.addEventListener('click', () => {
        if (!currentMediaData || !currentMediaData.downloads || currentMediaData.downloads.length === 0) {
            return;
        }
        const first = currentMediaData.downloads[0];
        const link = `${baseUrl}/download/file?url=${encodeURIComponent(first.url)}&name=${encodeURIComponent(first.filename || 'video.mp4')}&type=${encodeURIComponent(first.type || 'video')}`;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(() => {
                showToast('Tautan unduhan disalin ke papan klip!', 'success');
            }).catch(() => {
                fallbackCopy(link);
            });
        } else {
            fallbackCopy(link);
        }
    });

    function fallbackCopy(text) {
        const dummy = document.createElement('input');
        document.body.appendChild(dummy);
        dummy.value = text;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);
        showToast('Tautan unduhan disalin ke papan klip!', 'success');
    }

    /**
     * UI Helper States
     */
    function setLoadingState(isLoading) {
        if (isLoading) {
            btnSubmit.disabled = true;
            btnSubmitText.classList.add('d-none');
            btnSubmitIcon?.classList.add('d-none');
            submitSpinner.classList.remove('d-none');
            loadingSkeleton.classList.remove('d-none');
            resultContainer.classList.add('d-none');
        } else {
            btnSubmit.disabled = false;
            btnSubmitText.classList.remove('d-none');
            btnSubmitIcon?.classList.remove('d-none');
            submitSpinner.classList.add('d-none');
            loadingSkeleton.classList.add('d-none');
        }
    }

    function showError(msg) {
        errorMessage.innerHTML = msg;
        errorAlert.classList.remove('d-none');
        resultContainer.classList.add('d-none');
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideError() {
        errorAlert.classList.add('d-none');
    }

    function showToast(msg, type = 'success') {
        const toastEl = document.getElementById('appToast');
        const toastMsg = document.getElementById('toastMessage');
        if (!toastEl || !toastMsg) return;

        let icon = 'bi-check-circle-fill text-success';
        if (type === 'warning') icon = 'bi-exclamation-triangle-fill text-warning';
        if (type === 'danger') icon = 'bi-x-circle-fill text-danger';
        if (type === 'info') icon = 'bi-info-circle-fill text-info';

        toastMsg.innerHTML = `<i class="bi ${icon} fs-5"></i> <span>${msg}</span>`;
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    }

    function formatNumber(num) {
        if (!num || isNaN(num)) return '0';
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toLocaleString();
    }

    function isLargeMedia(dl) {
        if (dl.size_bytes && dl.size_bytes >= 30 * 1024 * 1024) {
            return true;
        }
        const str = `${dl.quality || ''} ${dl.label || ''}`;
        const match = str.match(/(\d+(?:\.\d+)?)\s*MB/i);
        if (match && parseFloat(match[1]) >= 30) {
            return true;
        }
        return false;
    }

    function extractFileSizeString(dl) {
        if (dl.size_formatted) return dl.size_formatted;
        if (dl.size_bytes && dl.size_bytes > 0) {
            return (dl.size_bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
        const str = `${dl.quality || ''} ${dl.label || ''}`;
        const match = str.match(/(\d+(?:\.\d+)?)\s*MB/i);
        if (match) return match[1] + ' MB';
        return '> 30 MB';
    }

    let compressionModalInstance = null;
    let progressTimer = null;
    let elapsedTimer = null;
    let compressionAbortController = null;

    function openCompressionDialog(dl, rawStreamUrl) {
        const modalEl = document.getElementById('compressionModal');
        if (!modalEl) {
            window.location.href = rawStreamUrl;
            return;
        }

        if (!compressionModalInstance && typeof bootstrap !== 'undefined') {
            compressionModalInstance = new bootstrap.Modal(modalEl);
        }

        const promptSection = document.getElementById('compressionPromptSection');
        const progressSection = document.getElementById('compressionProgressSection');
        const doneSection = document.getElementById('compressionDoneSection');
        const promptFileSize = document.getElementById('promptFileSize');
        const btnConfirm = document.getElementById('btnConfirmCompress');
        const btnSkip = document.getElementById('btnSkipCompress');
        const btnCancelProgress = document.getElementById('btnCancelCompressProgress');
        const statusText = document.getElementById('compressionStatusText');
        const progressBar = document.getElementById('compressionProgressBar');
        const timerBadge = document.getElementById('compressionElapsedTimer');

        // Cleanup any active timers / requests
        if (progressTimer) clearInterval(progressTimer);
        if (elapsedTimer) clearInterval(elapsedTimer);
        if (compressionAbortController) {
            compressionAbortController.abort();
            compressionAbortController = null;
        }

        // Reset sections
        promptSection?.classList.remove('d-none');
        progressSection?.classList.add('d-none');
        doneSection?.classList.add('d-none');

        const fileSizeStr = extractFileSizeString(dl);
        if (promptFileSize) promptFileSize.textContent = fileSizeStr;

        // Skip compression: download immediately without lag
        if (btnSkip) {
            btnSkip.onclick = () => {
                compressionModalInstance?.hide();
                showToast('📥 Mengunduh berkas asli langsung...', 'info');
                window.location.href = `${rawStreamUrl}&raw=1&compress=0`;
            };
        }

        // Cancel during compression: abort and download raw stream
        if (btnCancelProgress) {
            btnCancelProgress.onclick = () => {
                if (compressionAbortController) {
                    compressionAbortController.abort();
                    compressionAbortController = null;
                }
                if (progressTimer) clearInterval(progressTimer);
                if (elapsedTimer) clearInterval(elapsedTimer);
                compressionModalInstance?.hide();
                showToast('📥 Mengunduh berkas asli langsung...', 'info');
                window.location.href = `${rawStreamUrl}&raw=1&compress=0`;
            };
        }

        // Confirm compression: start AJAX compression and show live status
        if (btnConfirm) {
            btnConfirm.onclick = () => {
                promptSection?.classList.add('d-none');
                progressSection?.classList.remove('d-none');

                let elapsedSec = 0;
                if (timerBadge) timerBadge.innerHTML = '<i class="bi bi-stopwatch me-1"></i>0s';
                elapsedTimer = setInterval(() => {
                    elapsedSec++;
                    if (timerBadge) timerBadge.innerHTML = `<i class="bi bi-stopwatch me-1"></i>${elapsedSec}s`;
                }, 1000);

                if (progressBar) progressBar.style.width = '20%';
                if (statusText) statusText.textContent = '🚀 Memulai akselerasi perangkat keras & buffer cepat...';

                let progressVal = 20;
                const statusMessages = [
                    '⚡ Mengunduh buffer video lokal...',
                    '🚀 Akselerasi GPU NVIDIA NVENC aktif...',
                    '🎬 Menata kompresi Full HD jernih (anti-pecah)...',
                    '🎵 Mengoptimalkan audio stereo AAC HQ...',
                    '✨ Mengatur faststart agar video siap putar...'
                ];
                let msgIdx = 0;

                progressTimer = setInterval(() => {
                    if (progressVal < 70) {
                        progressVal += Math.floor(Math.random() * 8) + 4;
                    } else if (progressVal < 92) {
                        progressVal += Math.floor(Math.random() * 3) + 1;
                    } else if (progressVal < 98) {
                        progressVal += 0.5;
                    }
                    if (progressBar) progressBar.style.width = `${Math.min(progressVal, 98)}%`;
                    msgIdx = (msgIdx + 1) % statusMessages.length;
                    if (statusText) statusText.textContent = statusMessages[msgIdx];
                }, 900);

                compressionAbortController = new AbortController();

                fetch(`${baseUrl}/download/compress`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        url: dl.url,
                        name: dl.filename || 'video.mp4',
                        type: dl.type || 'video',
                        audio_url: dl.audio_url || '',
                        size_bytes: dl.size_bytes || 0
                    }),
                    signal: compressionAbortController.signal
                })
                .then(r => r.json())
                .then(res => {
                    clearInterval(progressTimer);
                    clearInterval(elapsedTimer);
                    if (res && res.success) {
                        if (progressBar) progressBar.style.width = '100%';
                        if (statusText) statusText.textContent = '✅ Kompresi selesai!';

                        setTimeout(() => {
                            progressSection?.classList.add('d-none');
                            doneSection?.classList.remove('d-none');

                            const doneOrig = document.getElementById('doneOriginalSize');
                            const doneComp = document.getElementById('doneCompressedSize');
                            const doneBadge = document.getElementById('doneSavingsBadge');
                            const btnFinal = document.getElementById('btnDownloadCompressedFinal');

                            if (doneOrig) doneOrig.textContent = res.original_size || fileSizeStr;
                            if (doneComp) doneComp.textContent = res.compressed_size || 'Terkonpres';
                            if (doneBadge) doneBadge.textContent = `Hemat ${res.savings_percent || '60%'}`;
                            if (btnFinal) btnFinal.href = res.download_url;

                            showToast('🎉 Kompresi selesai! Mengunduh berkas...', 'success');
                            window.location.href = res.download_url;
                        }, 400);
                    } else {
                        showToast(res.error || 'Gagal mengompresi berkas, mengunduh berkas asli...', 'warning');
                        compressionModalInstance?.hide();
                        window.location.href = `${rawStreamUrl}&raw=1&compress=0`;
                    }
                })
                .catch(err => {
                    if (err.name === 'AbortError') {
                        return; // User cancelled deliberately
                    }
                    clearInterval(progressTimer);
                    clearInterval(elapsedTimer);
                    showToast('Gagal memproses kompresi, mengunduh berkas asli langsung...', 'warning');
                    compressionModalInstance?.hide();
                    window.location.href = `${rawStreamUrl}&raw=1&compress=0`;
                });
            };
        }

        compressionModalInstance?.show();
    }
});
