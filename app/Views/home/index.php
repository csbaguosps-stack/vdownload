<?php
/**
 * VDownload Pro - Home Index View
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
?>
<main class="py-5">
    <div class="container">
        <!-- Hero Section -->
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-9 col-xl-8">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-2 mb-3">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill hero-badge animate-fade-in shadow-sm">
                        <span class="badge-dot"></span>
                        <span class="small fw-semibold text-uppercase tracking-wider">Downloader Video &amp; Foto Multi-Platform v<?= \App\Config\Config::APP_VERSION ?></span>
                    </div>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success-subtle text-success border border-success-subtle shadow-xs animate-fade-in">
                        <i class="bi bi-file-earmark-zip-fill"></i>
                        <span class="small fw-bold">Auto-Kompres Aktif: Hemat ~60% Kuota, Gambar Full HD Tetap Jernih</span>
                    </div>
                </div>
                
                <h1 class="display-4 fw-extrabold hero-title mb-3 animate-slide-up">
                    Unduh Video <span class="text-danger"><i class="bi bi-youtube"></i> YouTube</span>, 
                    <span class="brand-gradient-tiktok">TikTok</span>, 
                    <span class="brand-gradient-ig">Instagram</span> & 
                    <span class="brand-gradient-shopee">Shopee</span>
                </h1>
                
                <p class="lead text-secondary hero-subtitle mb-4">
                    Salin dan tempel tautan video favorit Anda. Dapatkan video resolusi Full HD jernih tanpa tanda air (watermark) serta file audio musik MP3 secara instan & gratis.
                </p>

                <!-- Platform Filters (Horizontally scrollable on mobile) -->
                <div class="platform-tabs-container mb-4">
                    <div class="platform-tabs-scroll d-inline-flex gap-2 p-1 rounded-pill bg-white shadow-xs border border-light-subtle">
                        <button class="btn btn-sm btn-platform-tab active" data-platform="all">
                            <i class="bi bi-grid-fill me-1"></i> Semua Platform
                        </button>
                        <button class="btn btn-sm btn-platform-tab" data-platform="youtube">
                            <i class="bi bi-youtube me-1 text-danger"></i> YouTube Video & Musik
                        </button>
                        <button class="btn btn-sm btn-platform-tab" data-platform="tiktok">
                            <i class="bi bi-tiktok me-1 text-info"></i> TikTok (Video & Foto)
                        </button>
                        <button class="btn btn-sm btn-platform-tab" data-platform="instagram">
                            <i class="bi bi-instagram me-1 text-danger"></i> Instagram (Reels & Foto)
                        </button>
                        <button class="btn btn-sm btn-platform-tab" data-platform="shopee">
                            <i class="bi bi-bag-check-fill me-1 text-warning"></i> Shopee Video
                        </button>
                    </div>
                </div>

                <!-- Input Card Form (Responsive Modern Layout) -->
                <div class="card glass-card border-0 search-card">
                    <form id="downloadForm" onsubmit="return false;" class="m-0">
                        <div class="search-input-group">
                            <!-- Dynamic Platform Icon -->
                            <div class="search-icon-slot" id="inputPlatformIcon">
                                <i class="bi bi-link-45deg fs-4 text-primary"></i>
                            </div>

                            <!-- Text Input -->
                            <input 
                                type="url" 
                                class="form-control search-input shadow-none" 
                                id="videoUrlInput" 
                                placeholder="Tempel tautan video atau foto Instagram, TikTok, Shopee, YouTube di sini..." 
                                autocomplete="off"
                                required
                            >

                            <!-- Clear Button -->
                            <button class="btn btn-clear-action d-none" type="button" id="btnClearInput" title="Hapus Teks">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>

                            <!-- Paste Clipboard Button (Clean Modern Blue Pill, NEVER solid dark grey) -->
                            <button class="btn btn-paste-action" type="button" id="btnPasteClipboard" title="Tempel dari Clipboard">
                                <i class="bi bi-clipboard"></i>
                                <span class="paste-label">Tempel</span>
                            </button>

                            <!-- Download Submit Button -->
                            <button class="btn btn-submit-action" type="submit" id="btnSubmitDownload">
                                <span class="btn-text">Unduh</span>
                                <i class="bi bi-arrow-right btn-icon"></i>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="submitSpinner"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Example Links -->
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mt-3 text-secondary small example-links-bar">
                    <span class="opacity-75"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Coba Contoh:</span>
                    <button type="button" class="btn btn-sample-chip text-danger sample-link" 
                        data-url="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                        <i class="bi bi-youtube me-1"></i>YouTube Musik Video
                    </button>
                    <button type="button" class="btn btn-sample-chip text-shopee-accent sample-link" 
                        data-url="https://sv.shopee.co.id/share-video/NQJLGKAjCgBWcSEFAAAAAA==?af_cost_value=h5_video_page&c=share_web">
                        <i class="bi bi-bag-check-fill me-1"></i>Shopee Video Kacamata
                    </button>
                    <button type="button" class="btn btn-sample-chip text-info sample-link" 
                        data-url="https://www.tiktok.com/@scout2015/video/6718335390845095173">
                        <i class="bi bi-tiktok me-1"></i>TikTok Video
                    </button>
                    <button type="button" class="btn btn-sample-chip text-danger sample-link" 
                        data-url="https://www.instagram.com/reel/C8h0YdKP2fC/">
                        <i class="bi bi-instagram me-1"></i>Instagram Reel
                    </button>
                </div>

                <!-- Error Message Alert -->
                <div class="alert alert-danger d-none mt-4 text-start shadow-sm border-0 glass-alert animate-shake" id="errorAlert" role="alert">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0 mt-1"></i>
                        <div class="flex-grow-1" id="errorMessage">
                            Terjadi kesalahan saat memproses video.
                        </div>
                        <button type="button" class="btn-close" onclick="document.getElementById('errorAlert').classList.add('d-none')"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skeleton / Loading Indicator -->
        <div class="row justify-content-center d-none" id="loadingSkeleton">
            <div class="col-lg-9 col-xl-8">
                <div class="card glass-card border-0 p-4 shadow-xl">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="skeleton-circle"></div>
                        <div class="w-50">
                            <div class="skeleton-line mb-2 w-75"></div>
                            <div class="skeleton-line w-50"></div>
                        </div>
                    </div>
                    <div class="skeleton-box mb-4"></div>
                    <div class="skeleton-line mb-2"></div>
                    <div class="skeleton-line w-75"></div>
                    <div class="text-center text-secondary mt-3">
                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                        <span class="small fw-semibold">Sedang mengekstrak stream video True HD dari server...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Result Container -->
        <div class="row justify-content-center d-none" id="resultContainer">
            <div class="col-lg-9 col-xl-8">
                <div class="card glass-card border-0 shadow-2xl overflow-hidden result-card animate-zoom-in">
                    <!-- Platform Accent Ribbon -->
                    <div class="result-header-accent" id="resultAccent"></div>

                    <div class="card-body p-3 p-sm-4 p-lg-5 result-card-body">
                        <div class="row g-4">
                            <!-- Left: Video / Media Player Preview -->
                            <div class="col-md-5 text-center">
                                <div class="video-preview-wrapper position-relative rounded-4 overflow-hidden shadow-lg bg-black">
                                    <video 
                                        id="previewVideoPlayer" 
                                        class="w-100 rounded-4 preview-media" 
                                        controls 
                                        playsinline 
                                        preload="metadata"
                                    ></video>
                                    <img id="previewImageFallback" class="w-100 rounded-4 preview-media d-none" alt="Thumbnail">
                                    
                                    <!-- Gallery Navigation (If Multiple Photos Available) -->
                                    <div id="galleryControls" class="d-none position-absolute bottom-0 start-0 end-0 p-2 d-flex align-items-center justify-content-between" style="background: linear-gradient(to top, rgba(0,0,0,0.75), transparent); z-index: 10;">
                                        <button type="button" class="btn btn-sm btn-dark bg-opacity-75 rounded-circle p-1 px-2 border-0 shadow-sm" id="btnGalleryPrev" title="Foto Sebelumnya">
                                            <i class="bi bi-chevron-left text-white"></i>
                                        </button>
                                        <span class="badge bg-black bg-opacity-75 text-white fw-medium small px-2 py-1" id="galleryCounter">1 / 1</span>
                                        <button type="button" class="btn btn-sm btn-dark bg-opacity-75 rounded-circle p-1 px-2 border-0 shadow-sm" id="btnGalleryNext" title="Foto Berikutnya">
                                            <i class="bi bi-chevron-right text-white"></i>
                                        </button>
                                    </div>

                                    <div class="platform-watermark-badge position-absolute top-0 start-0 m-2 d-flex gap-1" style="z-index: 10;">
                                        <span class="badge" id="resultPlatformBadge">Shopee Video</span>
                                        <span class="badge bg-success bg-opacity-90 text-white shadow-sm" id="noWatermarkBadge">
                                            <i class="bi bi-shield-check me-1"></i>No Watermark HD
                                        </span>
                                    </div>
                                </div>

                                <!-- Audio Player (If TikTok Audio Available) -->
                                <div class="mt-3 p-2 rounded-3 bg-light border border-light-subtle d-none" id="audioPreviewBlock">
                                    <div class="small text-secondary mb-1 d-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-disc text-info"></i> Audio Musik / Suara Asli
                                    </div>
                                    <audio id="previewAudioPlayer" class="w-100" controls></audio>
                                </div>
                            </div>

                            <!-- Right: Video Metadata & Download Buttons -->
                            <div class="col-md-7 d-flex flex-column justify-content-between">
                                <div>
                                    <!-- Author Info -->
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="author-avatar-wrapper shadow-sm">
                                            <img id="authorAvatar" src="" class="rounded-circle avatar-img" alt="Avatar" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23cbd5e1%22/><text x=%2250%22 y=%2255%22 font-size=%2235%22 text-anchor=%22middle%22 fill=%22%23475569%22>👤</text></svg>'">
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark" id="authorName">Creator</h6>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small" id="authorMeta">Verified Media</span>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center gap-2" id="durationBadgeContainer">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-none" id="videoResolutionBadge">
                                                <i class="bi bi-badge-hd-fill me-1"></i><span id="videoResolutionText">True HD</span>
                                            </span>
                                            <span class="badge bg-light border border-secondary-subtle text-secondary px-2 py-1" id="videoDuration">00:00</span>
                                        </div>
                                    </div>

                                    <!-- Video Title / Caption -->
                                    <div class="mb-3">
                                        <p class="card-text text-dark fs-6 fw-medium line-clamp-3" id="videoTitle">
                                            Judul video atau caption
                                        </p>
                                    </div>

                                    <!-- Stats Counters -->
                                    <div class="d-flex flex-wrap gap-3 p-2 rounded-3 bg-light border border-light-subtle mb-4 small text-secondary" id="statsRow">
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="bi bi-heart-fill text-danger"></i> <span id="statLikes" class="fw-semibold text-dark">0</span> Suka
                                        </span>
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="bi bi-chat-dots-fill text-info"></i> <span id="statComments" class="fw-semibold text-dark">0</span> Komentar
                                        </span>
                                        <span class="d-flex align-items-center gap-1 d-none" id="sharesStatWrapper">
                                            <i class="bi bi-share-fill text-warning"></i> <span id="statShares" class="fw-semibold text-dark">0</span> Bagikan
                                        </span>
                                    </div>
                                </div>

                                <!-- Download Options Buttons List -->
                                <div>
                                    <h6 class="fw-bold text-uppercase tracking-wider small text-secondary mb-2">
                                        <i class="bi bi-cloud-arrow-down-fill text-primary me-1"></i> Pilihan Unduhan Kualitas HD
                                    </h6>
                                    
                                    <div class="d-grid gap-2" id="downloadButtonsList">
                                        <!-- Dynamically populated via JS -->
                                    </div>

                                    <div class="mt-3 d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                                        <button class="btn btn-sm btn-link text-secondary text-decoration-none p-0 hover-blue" type="button" id="btnCopyDirectLink">
                                            <i class="bi bi-link-45deg"></i> Salin Tautan Langsung
                                        </button>
                                        <button class="btn btn-sm btn-link text-secondary text-decoration-none p-0 hover-blue" type="button" id="btnResetView">
                                            <i class="bi bi-arrow-counterclockwise"></i> Unduh Video Lain
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Showcase Section -->
        <div class="row g-4 mt-5 pt-3">
            <div class="col-12 text-center mb-2">
                <h3 class="fw-bold text-dark">Keunggulan VDownload Pro Modern</h3>
                <p class="text-secondary small">Dirancang khusus untuk unduhan super cepat berkualitas jernih tanpa pecah dengan PHP 8.2</p>
            </div>

            <!-- Feature 1: TikTok -->
            <div class="col-md-4">
                <div class="card glass-card h-100 border-0 p-4 feature-card">
                    <div class="feature-icon mb-3 text-info bg-info-subtle">
                        <i class="bi bi-tiktok fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">TikTok Full HD Tanpa Watermark</h5>
                    <p class="text-secondary small mb-3">
                        Unduh video TikTok dalam kualitas Full HD 1080p jernih tanpa tanda air. Dilengkapi fitur ekstraksi audio musik latar belakang (MP3).
                    </p>
                    <ul class="list-unstyled small text-secondary mb-0">
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Mendukung vt.tiktok & tiktok.com</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Prioritas True HD 1080p/720p</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Unduh Audio MP3 terpisah</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2: Instagram -->
            <div class="col-md-4">
                <div class="card glass-card h-100 border-0 p-4 feature-card">
                    <div class="feature-icon mb-3 text-danger bg-danger-subtle">
                        <i class="bi bi-instagram fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Instagram Reels & Postingan</h5>
                    <p class="text-secondary small mb-3">
                        Simpan video Reels, Video Postingan, dan IGTV dengan cepat. Dilengkapi opsi cookie sessionid untuk akses video terlindungi.
                    </p>
                    <ul class="list-unstyled small text-secondary mb-0">
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Instagram Reels & Postingan HD</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Thumbnail resolusi tinggi</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Kompatibel browser desktop & ponsel</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3: Shopee Video -->
            <div class="col-md-4">
                <div class="card glass-card h-100 border-0 p-4 feature-card">
                    <div class="feature-icon mb-3 text-warning bg-warning-subtle">
                        <i class="bi bi-bag-check-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Shopee Video Master HD (No WM)</h5>
                    <p class="text-secondary small mb-3">
                        Ekstraksi asli stream Master Raw Shopee Video dari tautan bagikan. Kualitas 720x1280 bitrate penuh tanpa watermark dan tidak pecah.
                    </p>
                    <ul class="list-unstyled small text-secondary mb-0">
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Format link sv.shopee & shp.ee</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Ekstraksi Master Raw Stream HD</li>
                        <li><i class="bi bi-check2-circle text-primary me-1"></i> Ekstraksi cover produk HD asli</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Downloads Section (If Available) -->
        <?php if (!empty($recentDownloads)): ?>
        <div class="row mt-5 pt-4">
            <div class="col-12 d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h4 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Unduhan Terakhir
                    </h4>
                    <p class="text-secondary small mb-0">Video yang baru saja Anda analisa dan unduh</p>
                </div>
                <a href="<?= $baseUrl ?>/history" class="btn btn-sm btn-outline-primary fw-medium">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <?php foreach ($recentDownloads as $item): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card glass-card border-0 h-100 p-3 history-preview-card">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="history-thumb-wrapper rounded-3 overflow-hidden bg-black flex-shrink-0 shadow-sm">
                            <?php if (!empty($item['thumbnail'])): ?>
                                <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="Thumbnail" class="history-thumb-img" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23e2e8f0%22/><text x=%2250%22 y=%2255%22 font-size=%2225%22 text-anchor=%22middle%22 fill=%22%23475569%22>🎬</text></svg>'">
                            <?php else: ?>
                                <div class="history-thumb-placeholder d-flex align-items-center justify-content-center h-100 text-secondary">
                                    <i class="bi bi-play-btn fs-4"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <span class="badge badge-platform badge-<?= htmlspecialchars($item['platform'] ?? 'video') ?> mb-1">
                                <?= htmlspecialchars($item['platform_name'] ?? 'Video') ?>
                            </span>
                            <?php if (!empty($item['resolution'])): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 small ms-1 mb-1">
                                    <?= htmlspecialchars($item['resolution']) ?>
                                </span>
                            <?php endif; ?>
                            <h6 class="text-dark text-truncate mb-1 small fw-bold" title="<?= htmlspecialchars($item['title'] ?? '') ?>">
                                <?= htmlspecialchars($item['title'] ?? 'Video Download') ?>
                            </h6>
                            <div class="text-secondary small d-flex align-items-center justify-content-between">
                                <span><?= htmlspecialchars($item['author'] ?? 'Creator') ?></span>
                                <span class="opacity-75"><?= htmlspecialchars($item['formatted_time'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($item['downloads'])): ?>
                    <div class="mt-2 pt-2 border-top border-light-subtle d-flex gap-2">
                        <?php 
                        $firstDl = $item['downloads'][0];
                        $streamUrl = $baseUrl . '/download/file?url=' . urlencode($firstDl['url']) . '&name=' . urlencode($firstDl['filename'] ?? 'video.mp4') . '&type=' . ($firstDl['type'] ?? 'video');
                        ?>
                        <a href="<?= $streamUrl ?>" class="btn btn-sm btn-primary w-100 fw-semibold">
                            <i class="bi bi-download me-1"></i> Unduh Ulang
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Compression Confirmation & Status Modal -->
    <div class="modal fade" id="compressionModal" tabindex="-1" aria-labelledby="compressionModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom border-light-subtle bg-white px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary-subtle p-2 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-file-earmark-zip-fill fs-5"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="compressionModalLabel">Opsi Kompresi Video Cerdas</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseCompressionModal"></button>
                </div>
                
                <div class="modal-body p-4 bg-white">
                    <!-- 1. Prompt / Choice State -->
                    <div id="compressionPromptSection">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border border-light-subtle mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-exclamation-circle-fill text-warning fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary small">Ukuran berkas asli terdeteksi cukup besar:</div>
                                <div class="fw-bold text-dark fs-5" id="promptFileSize">-- MB</div>
                            </div>
                        </div>

                        <p class="text-secondary small mb-4">
                            Mengunduh berkas besar tanpa kompresi dapat memakan kuota dan waktu unduh lebih lama. 
                            <strong>Apakah Anda ingin mengompres video ini terlebih dahulu?</strong>
                        </p>

                        <div class="p-3 rounded-3 bg-success-subtle text-success border border-success-subtle mb-4 small">
                            <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                                <i class="bi bi-stars"></i> Keuntungan Kompresi Otomatis:
                            </div>
                            <ul class="mb-0 ps-3">
                                <li>Ukuran berkas terpangkas <strong>~50% - 70% lebih ringan</strong></li>
                                <li>Kualitas Full HD <strong>tetap jernih & tidak pecah</strong> (H.264 CRF 23)</li>
                                <li>Langsung siap diputar di semua HP & laptop</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" id="btnConfirmCompress">
                                <i class="bi bi-magic fs-5"></i>
                                <span>Ya, Kompres Dulu (Hemat Kuota & Jernih)</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary fw-semibold py-2" id="btnSkipCompress">
                                <i class="bi bi-cloud-arrow-down me-1"></i> Tidak, Unduh Berkas Asli Langsung
                            </button>
                        </div>
                    </div>

                    <!-- 2. Compressing In-Progress State -->
                    <div id="compressionProgressSection" class="d-none text-center py-3">
                        <div class="mb-3 position-relative d-inline-block">
                            <div class="spinner-grow text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <i class="bi bi-cpu-fill text-primary position-absolute top-50 start-50 translate-middle fs-4"></i>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <h5 class="fw-bold text-dark mb-0">Sedang Mengompres Video...</h5>
                            <span id="compressionElapsedTimer" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small"><i class="bi bi-stopwatch me-1"></i>0s</span>
                        </div>
                        <p class="text-secondary small mb-3" id="compressionStatusText">
                            Menghubungkan ke stream dan menyiapkan encoding FFmpeg...
                        </p>

                        <div class="progress mb-3 shadow-2xs" style="height: 12px; border-radius: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="compressionProgressBar" role="progressbar" style="width: 25%;"></div>
                        </div>

                        <div class="alert alert-light border border-light-subtle rounded-3 text-start small text-muted mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1 text-dark fw-semibold">
                                <i class="bi bi-lightning-charge-fill text-warning"></i> Akselerasi GPU & Multi-Core Aktif
                            </div>
                            Menggunakan kompresi cerdas perangkat keras. Jika ingin mengunduh instan tanpa menunggu proses kompresi, klik tombol di bawah ini.
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill w-100" id="btnCancelCompressProgress">
                            <i class="bi bi-x-circle me-1"></i> Batalkan & Unduh Berkas Asli Langsung
                        </button>
                    </div>

                    <!-- 3. Done / Completed State -->
                    <div id="compressionDoneSection" class="d-none text-center py-2">
                        <div class="text-success mb-2">
                            <i class="bi bi-check-circle-fill" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Kompresi Selesai!</h5>
                        <p class="text-secondary small mb-3">Video berhasil dioptimalkan dan siap diunduh.</p>

                        <div class="row g-2 justify-content-center mb-4">
                            <div class="col-6">
                                <div class="p-2 rounded-3 bg-light border border-light-subtle">
                                    <span class="text-secondary small d-block">Ukuran Asli:</span>
                                    <del class="text-muted fw-bold" id="doneOriginalSize">-- MB</del>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle">
                                    <span class="text-success small d-block">Ukuran Baru:</span>
                                    <strong class="text-success fw-extrabold fs-6" id="doneCompressedSize">-- MB</strong>
                                    <span class="badge bg-success text-white ms-1 small" id="doneSavingsBadge">Hemat 65%</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-success btn-lg fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnDownloadCompressedFinal">
                                <i class="bi bi-download"></i>
                                <span>Unduh Berkas Sekarang</span>
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
