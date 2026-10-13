<?php
/**
 * VDownload Pro - Browser Extension Download & Installation Guide View
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
$fileSizeKb = $fileSizeKb ?? 15.0;
?>
<main class="py-5">
    <div class="container">
        <!-- Header Hero -->
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill hero-badge mb-3 shadow-xs">
                <span class="badge-dot"></span>
                <span class="small fw-semibold">Official Companion Extension &bull; Manifest V3 Ready</span>
            </div>
            <h1 class="display-6 fw-extrabold text-dark mb-2">Download & Panduan Ekstensi Browser</h1>
            <p class="text-secondary max-w-700 mx-auto">
                Pasang ekstensi resmi <strong><?= Config::APP_NAME ?></strong> di browser Anda untuk mengunduh video TikTok, Instagram Reels, dan Shopee Video secara instan dengan fitur <em>Auto-Sync Cookie</em> dan <em>Tombol Reload Cepat</em>.
            </p>
        </div>

        <!-- Section 1: Prominent Download Extension Card (Production-Ready) -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="card glass-card border-0 p-4 p-md-5 shadow-card position-relative overflow-hidden text-center text-md-start">
                    <div class="row align-items-center g-4">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2 flex-wrap">
                                <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-2 py-1">
                                    Paket Ekstensi Resmi
                                </span>
                                <span class="badge bg-success text-white fw-bold px-2 py-1">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> 100% Standalone (Direct)
                                </span>
                                <span class="badge bg-light text-secondary border fw-bold px-2 py-1">
                                    v1.5.0
                                </span>
                            </div>
                            <h3 class="fw-extrabold text-dark mb-2">Unduh Ekstensi VDownload Pro (Standalone)</h3>
                            <p class="text-secondary small mb-3">
                                Ekstensi ini dapat <strong>berdiri sendiri (100% Standalone)</strong> langsung di browser tanpa bergantung pada server lokal / XAMPP. Unduh video TikTok, Shopee, dan Instagram langsung ke folder Downloads komputer Anda, atau gunakan sebagai pendamping sinkronisasi website VDownload Pro.
                            </p>
                            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 small text-muted">
                                <span class="badge bg-light text-secondary border"><i class="bi bi-file-earmark-zip me-1 text-primary"></i> Format: ZIP</span>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-hdd me-1 text-info"></i> Ukuran: ~<?= htmlspecialchars((string)$fileSizeKb) ?> KB</span>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-cpu me-1 text-primary"></i> Standalone Direct</span>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-shield-check me-1 text-success"></i> Bebas Iklan & Aman</span>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-arrow-repeat me-1 text-primary"></i> Fitur Reload</span>
                            </div>
                        </div>

                        <div class="col-md-5 text-center text-md-end">
                            <a href="<?= $baseUrl ?>/extension/download" class="btn btn-gradient-primary btn-lg px-4 py-3 fw-bold rounded-pill shadow-soft d-inline-flex align-items-center gap-2 mb-2 w-100 justify-content-center btn-download-zip" id="btnDownloadZip" download="vdownload-extension.zip">
                                <i class="bi bi-file-earmark-zip-fill fs-4" id="downloadZipIcon"></i>
                                <span id="downloadZipText">Download Ekstensi (.ZIP)</span>
                                <span class="spinner-border spinner-border-sm d-none" id="downloadZipSpinner" role="status"></span>
                            </a>
                            <div class="small text-muted">
                                <i class="bi bi-info-circle me-1"></i> Tautan alternatif langsung: 
                                <a href="<?= $baseUrl ?>/downloads/vdownload-extension.zip" class="text-primary text-decoration-none fw-semibold" download="vdownload-extension.zip">vdownload-extension.zip</a>
                            </div>
                        </div>
                    </div>

                    <!-- Chrome Insecure Download Notice for HTTP LAN IPs -->
                    <div class="mt-4 p-3 rounded-4 bg-light border border-primary-subtle small text-start">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-shield-check text-primary fs-5 flex-shrink-0 mt-0"></i>
                            <div>
                                <div class="fw-bold text-dark mb-1">
                                    Catatan Keamanan Browser (Pesan <em>"Insecure download blocked"</em>):
                                </div>
                                <div class="text-secondary">
                                    Jika browser Chrome menampilkan peringatan <em>"Insecure download blocked"</em>, cukup klik tombol <strong>Keep (Tetap Simpan)</strong> &rarr; <strong>Keep anyway</strong>. Peringatan ini adalah proteksi bawaan Chrome khusus untuk alamat IP jaringan lokal non-SSL (<code>http://192.168.x.x</code>). Saat aplikasi ini di-deploy di domain internet resmi dengan protokol <strong>HTTPS (SSL)</strong>, pesan ini tidak akan muncul sama sekali.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Step-by-Step Installation by Browser -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="card glass-card border-0 p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 border-bottom pb-3">
                        <div>
                            <h3 class="fw-extrabold text-dark mb-1">
                                <i class="bi bi-puzzle text-primary me-2"></i>Cara Pasang Ekstensi (5 Langkah Mudah)
                            </h3>
                            <p class="text-secondary small mb-0">Pilih browser yang Anda gunakan untuk melihat petunjuk instalasi:</p>
                        </div>
                        <ul class="nav nav-pills" id="browserTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold px-3 py-2 rounded-pill" id="tab-chrome" data-bs-toggle="pill" data-bs-target="#content-chrome" type="button" role="tab">
                                    <i class="bi bi-browser-chrome me-1"></i> Google Chrome
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold px-3 py-2 rounded-pill" id="tab-edge" data-bs-toggle="pill" data-bs-target="#content-edge" type="button" role="tab">
                                    <i class="bi bi-browser-edge me-1"></i> Microsoft Edge
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold px-3 py-2 rounded-pill" id="tab-brave" data-bs-toggle="pill" data-bs-target="#content-brave" type="button" role="tab">
                                    <i class="bi bi-shield-shaded me-1"></i> Brave / Opera
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="browserTabsContent">
                        <!-- Tab 1: Chrome -->
                        <div class="tab-pane fade show active" id="content-chrome" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">1</span>
                                            <h6 class="fw-bold text-dark mb-0">Download & Ekstrak ZIP</h6>
                                        </div>
                                        <p class="text-secondary small mb-3">
                                            Klik tombol <strong>Download Ekstensi (.ZIP)</strong> di atas. Setelah selesai, klik kanan file ZIP &rarr; pilih <strong>Extract All...</strong> (Ekstrak Semua) ke folder di komputer Anda.
                                        </p>
                                        <div class="p-2 rounded-3 bg-white border small fw-medium mt-auto text-primary">
                                            <i class="bi bi-folder-check me-1"></i> Folder hasil ekstrak siap dimuat
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">2</span>
                                            <h6 class="fw-bold text-dark mb-0">Buka Menu Ekstensi</h6>
                                        </div>
                                        <p class="text-secondary small mb-3">
                                            Buka Google Chrome, lalu ketik alamat berikut di bilah alamat (URL bar) browser:
                                        </p>
                                        <div class="p-2 rounded-3 bg-white border small font-monospace text-primary mb-auto d-flex align-items-center justify-content-between">
                                            <span>chrome://extensions</span>
                                            <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="navigator.clipboard.writeText('chrome://extensions'); alert('chrome://extensions berhasil disalin!');">Salin</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">3</span>
                                            <h6 class="fw-bold text-dark mb-0">Aktifkan Developer Mode</h6>
                                        </div>
                                        <p class="text-secondary small mb-3">
                                            Di pojok kanan atas halaman ekstensi Chrome, aktifkan switch toggle <strong>"Developer mode"</strong> (Mode Pengembang).
                                        </p>
                                        <div class="p-2 rounded-3 bg-primary-subtle text-primary border border-primary-subtle small fw-medium mt-auto">
                                            <i class="bi bi-toggle2-on me-1"></i> Switch: Developer Mode ON
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">4</span>
                                            <h6 class="fw-bold text-dark mb-0">Klik "Load Unpacked"</h6>
                                        </div>
                                        <p class="text-secondary small mb-2">
                                            Klik tombol <strong>"Load unpacked"</strong> (Muat yang belum dibongkar) di pojok kiri atas. Pada kotak dialog berkas, pilih <strong>folder hasil ekstrak ZIP</strong> yang sudah di-download tadi lalu klik <strong>Select Folder</strong>.
                                        </p>
                                        <div class="p-2 rounded-3 bg-white border small fw-medium mt-auto text-dark">
                                            <i class="bi bi-box-arrow-in-down me-1 text-primary"></i> Pilih folder hasil unzip
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-success text-white rounded-circle p-2 px-3 fw-bold">5</span>
                                            <h6 class="fw-bold text-dark mb-0">Selesai & Sematkan (Pin)</h6>
                                        </div>
                                        <p class="text-secondary small mb-2">
                                            Ekstensi <strong>VDownload Pro Helper</strong> akan langsung aktif! Klik ikon Puzzle di toolbar browser dan klik ikon <strong>Pin (📌)</strong> agar selalu tampil di bilah atas untuk akses cepat.
                                        </p>
                                        <div class="p-2 rounded-3 bg-success-subtle text-success border border-success-subtle small fw-bold mt-auto">
                                            <i class="bi bi-check-circle-fill me-1"></i> Ekstensi siap digunakan 100%!
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Edge -->
                        <div class="tab-pane fade" id="content-edge" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">1</span>
                                            <h6 class="fw-bold text-dark mb-0">Download & Ekstrak</h6>
                                        </div>
                                        <p class="text-secondary small mb-0">
                                            Download paket <code>vdownload-extension.zip</code> lalu ekstrak ke komputer Anda.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">2</span>
                                            <h6 class="fw-bold text-dark mb-0">Buka edge://extensions</h6>
                                        </div>
                                        <p class="text-secondary small mb-0">
                                            Ketik <code>edge://extensions</code> di URL bar Microsoft Edge dan aktifkan <strong>Developer mode</strong> di panel kiri.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary text-white rounded-circle p-2 px-3 fw-bold">3</span>
                                            <h6 class="fw-bold text-dark mb-0">Muat Unpacked</h6>
                                        </div>
                                        <p class="text-secondary small mb-0">
                                            Klik tombol <strong>"Load unpacked"</strong> &rarr; pilih folder hasil ekstrak ZIP tadi. Selesai!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Brave / Opera -->
                        <div class="tab-pane fade" id="content-brave" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-shaded text-danger me-1"></i> Brave Browser</h6>
                                        <ol class="text-secondary small ps-3 mb-0">
                                            <li class="mb-2">Download dan ekstrak file <code>vdownload-extension.zip</code>.</li>
                                            <li class="mb-2">Buka <code>brave://extensions</code> di URL bar.</li>
                                            <li class="mb-2">Aktifkan toggle <strong>Developer mode</strong> di pojok kanan atas.</li>
                                            <li>Klik <strong>Load unpacked</strong> dan pilih folder hasil ekstrak.</li>
                                        </ol>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light h-100 border border-light-subtle">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-circle-fill text-danger me-1"></i> Opera / Opera GX</h6>
                                        <ol class="text-secondary small ps-3 mb-0">
                                            <li class="mb-2">Download dan ekstrak paket <code>vdownload-extension.zip</code>.</li>
                                            <li class="mb-2">Buka <code>opera://extensions</code> di URL bar.</li>
                                            <li class="mb-2">Nyalakan saklar <strong>Developer mode</strong> di kanan atas.</li>
                                            <li>Klik tombol <strong>Load unpacked</strong> dan pilih folder hasil ekstrak.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Feature Highlights (Standalone, Reload, & Cookie Auto-Sync) -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="row g-4">
                    <!-- Feature 1: Standalone Direct Downloader -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card glass-card h-100 border-0 p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="brand-logo-icon bg-primary text-white" style="background: linear-gradient(135deg, #1d4ed8 0%, #0284c7 100%);">
                                    <i class="bi bi-lightning-charge-fill fs-4"></i>
                                </div>
                                <div>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-2 py-1 mb-1">
                                        100% Mandiri
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0">Unduh Standalone</h5>
                                </div>
                            </div>
                            <p class="text-secondary small mb-3">
                                Ekstensi bekerja langsung di browser tanpa membutuhkan XAMPP atau localhost aktif.
                            </p>
                            <div class="p-3 rounded-3 bg-light border border-light-subtle small text-dark mb-3">
                                <div class="fw-bold text-primary mb-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> Keunggulan Langsung:
                                </div>
                                <ul class="mb-0 ps-3 text-secondary">
                                    <li>Unduh True HD tanpa watermark langsung ke folder Downloads.</li>
                                    <li>Deteksi tab aktif TikTok, Shopee, & IG otomatis.</li>
                                    <li>Tombol mengambang instan di halaman video.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2: Button Reload Ekstensi -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card glass-card h-100 border-0 p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="brand-logo-icon bg-info text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);">
                                    <i class="bi bi-arrow-clockwise fs-4"></i>
                                </div>
                                <div>
                                    <span class="badge bg-info-subtle text-info fw-bold text-uppercase px-2 py-1 mb-1">
                                        Shortcut
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0">Tombol Reload Ekstensi</h5>
                                </div>
                            </div>
                            <p class="text-secondary small mb-3">
                                Tidak perlu membuka halaman <code>chrome://extensions</code> saat memperbarui file ekstensi.
                            </p>
                            <div class="p-3 rounded-3 bg-light border border-light-subtle small text-dark mb-3">
                                <div class="fw-bold text-info mb-1">
                                    <i class="bi bi-arrow-repeat me-1"></i> 1-Click Reload:
                                </div>
                                <ul class="mb-0 ps-3 text-secondary">
                                    <li>Klik ikon ekstensi di toolbar browser.</li>
                                    <li>Klik tombol <strong>"Reload"</strong> (ikon 🔄).</li>
                                    <li>Ekstensi langsung memuat ulang file terbaru dari disk!</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3: Multi-Platform HD Downloader -->
                    <div class="col-lg-4 col-md-12">
                        <div class="card glass-card h-100 border-0 p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="brand-logo-icon text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                                    <i class="bi bi-collection-play-fill fs-4"></i>
                                </div>
                                <div>
                                    <span class="badge bg-success-subtle text-success fw-bold text-uppercase px-2 py-1 mb-1">
                                        Multi-Platform
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0">TikTok, Shopee, & IG</h5>
                                </div>
                            </div>
                            <p class="text-secondary small mb-3">
                                Ekstrak berbagai platform video populer langsung dari bilah browser secara aman tanpa iklan.
                            </p>
                            <div class="p-3 rounded-3 bg-light border border-light-subtle small text-dark mb-3">
                                <div class="fw-bold text-success mb-1">
                                    <i class="bi bi-shield-check me-1"></i> Format Lengkap:
                                </div>
                                <ul class="mb-0 ps-3 text-secondary">
                                    <li>Unduh True HD tanpa tanda air.</li>
                                    <li>Format audio jernih MP3 untuk musik TikTok.</li>
                                    <li>Cover dan gambar thumbnail resolusi tinggi.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Action Navigation -->
        <div class="text-center mt-3 d-flex flex-wrap align-items-center justify-content-center gap-3">
            <a href="<?= $baseUrl ?>/extension/download" class="btn btn-gradient-primary px-4 py-2 fw-bold rounded-pill">
                <i class="bi bi-download me-1"></i> Download Ekstensi (.ZIP)
            </a>
            <a href="<?= $baseUrl ?>/" class="btn btn-outline-primary px-4 py-2 fw-bold rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Downloader
            </a>
            <a href="<?= $baseUrl ?>/settings" class="btn btn-light border px-4 py-2 fw-bold rounded-pill text-secondary">
                <i class="bi bi-gear me-1"></i> Pengaturan Cookie
            </a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnDownloadZip');
    const label = document.getElementById('downloadZipText');
    const spinner = document.getElementById('downloadZipSpinner');
    const icon = document.getElementById('downloadZipIcon');

    if (btn) {
        btn.addEventListener('click', function () {
            if (label) label.textContent = 'Memulai Unduhan...';
            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');

            if (window.showAppToast) {
                window.showAppToast('Mengunduh vdownload-extension.zip. Silakan ekstrak file setelah selesai.');
            }

            setTimeout(function () {
                if (label) label.textContent = 'Unduh Lagi (.ZIP)';
                if (spinner) spinner.classList.add('d-none');
                if (icon) icon.classList.remove('d-none');
            }, 2500);
        });
    }
});
</script>
