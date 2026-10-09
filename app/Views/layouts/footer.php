<?php
/**
 * VDownload Pro - Footer Layout
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
?>
<footer class="mt-auto py-5 border-top bg-modern-footer">
    <div class="container">
        <div class="row g-4 justify-content-between align-items-center mb-4">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <span class="brand-logo-icon sm">
                        <i class="bi bi-cloud-arrow-down-fill"></i>
                    </span>
                    <span class="fw-bold fs-5 text-dark"><?= Config::APP_NAME ?></span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill fw-semibold">v<?= Config::APP_VERSION ?></span>
                </div>
                <p class="text-secondary small mb-2">
                    Aplikasi pengunduh video & musik instan modern. Mendukung ekstraksi dan pengunduhan langsung kualitas Full HD & Audio MP3 untuk YouTube, TikTok, Instagram Reels, dan Shopee Video tanpa tanda air.
                </p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-shield-check text-success me-1"></i> Gunakan hanya untuk konten publik atau arsip pribadi sesuai ketentuan platform.
                </p>
            </div>
            <div class="col-md-5 text-center text-md-end">
                <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-3 mb-3">
                    <a href="<?= $baseUrl ?>/" class="text-secondary text-decoration-none hover-blue small fw-medium">Beranda</a>
                    <a href="<?= $baseUrl ?>/history" class="text-secondary text-decoration-none hover-blue small fw-medium">Riwayat Unduhan</a>
                    <a href="<?= $baseUrl ?>/guide" class="text-secondary text-decoration-none hover-blue small fw-medium">Panduan</a>
                    <a href="<?= $baseUrl ?>/extension" class="text-secondary text-decoration-none hover-blue small fw-medium">Ekstensi Browser</a>
                    <a href="<?= $baseUrl ?>/settings" class="text-secondary text-decoration-none hover-blue small fw-medium">Pengaturan</a>
                </div>
                <div class="text-muted small">
                    Platform pengunduh video multi-platform dengan tampilan modern putih-biru & performa tinggi.
                </div>
            </div>
        </div>

        <!-- Copyright & Developer Credit Section -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 pt-4 border-top border-light-subtle">
            <div class="text-secondary small text-center text-md-start">
                &copy; <?= date('Y') ?> <strong><?= Config::APP_NAME ?></strong>. All rights reserved.
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                <span class="small text-secondary">Created by:</span>
                <a href="mailto:cs.baguosps@gmail.com" class="copyright-badge text-decoration-none" title="Hubungi Developer via Email">
                    <i class="bi bi-envelope-at-fill text-primary"></i>
                    <span class="fw-bold">cs.baguosps@gmail.com</span>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="appToast" class="toast align-items-center bg-white border border-primary-subtle shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <span class="text-dark fw-medium">Operasi berhasil!</span>
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom App JS -->
<script src="<?= $baseUrl ?>/assets/js/app.js?v=2.5"></script>
</body>
</html>
