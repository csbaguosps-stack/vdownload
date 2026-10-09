<?php
/**
 * VDownload Pro - Usage Guide View
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
        <!-- Title -->
        <div class="text-center mb-5">
            <h2 class="display-6 fw-extrabold text-dark mb-2">Panduan Penggunaan</h2>
            <p class="text-secondary">Cara mudah menyalin dan mengunduh video dari TikTok, Instagram, dan Shopee</p>
        </div>

        <div class="row g-4">
            <!-- YouTube Guide -->
            <div class="col-md-6 col-lg-3">
                <div class="card glass-card h-100 border-0 p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon text-danger bg-danger-subtle">
                            <i class="bi bi-youtube fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">YouTube Video</h5>
                            <span class="badge bg-danger-subtle text-danger-emphasis small">Full HD & Audio MP3</span>
                        </div>
                    </div>

                    <ol class="guide-steps ps-3 text-secondary small mb-4">
                        <li class="mb-2">Buka <strong>YouTube</strong> atau YouTube Music di browser atau aplikasi.</li>
                        <li class="mb-2">Buka video atau lagu yang ingin diunduh.</li>
                        <li class="mb-2">Klik tombol <strong>Bagikan / Share</strong> &rarr; pilih <strong>Salin Tautan</strong>.</li>
                        <li class="mb-2">Kembali ke <strong><?= Config::APP_NAME ?></strong>, tempel tautan lalu klik <strong>Unduh</strong>.</li>
                        <li>Pilih opsi Video 1080p/720p atau Audio Musik MP3.</li>
                    </ol>

                    <div class="p-3 rounded-3 bg-light border border-light-subtle small mt-auto">
                        <div class="fw-semibold text-danger mb-1"><i class="bi bi-info-circle me-1"></i>Format Link:</div>
                        <code class="text-secondary text-break">youtube.com/watch?v=...</code> atau <code>youtu.be/...</code>
                    </div>
                </div>
            </div>

            <!-- TikTok Guide -->
            <div class="col-md-6 col-lg-3">
                <div class="card glass-card h-100 border-0 p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon text-info bg-info-subtle">
                            <i class="bi bi-tiktok fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">TikTok Video</h5>
                            <span class="badge bg-info-subtle text-info-emphasis small">Tanpa Tanda Air (No WM)</span>
                        </div>
                    </div>

                    <ol class="guide-steps ps-3 text-secondary small mb-4">
                        <li class="mb-2">Buka aplikasi atau situs web <strong>TikTok</strong>.</li>
                        <li class="mb-2">Pilih video yang diinginkan.</li>
                        <li class="mb-2">Klik tombol <strong>Bagikan</strong> (ikon panah lengkung).</li>
                        <li class="mb-2">Klik ikon <strong>Salin Tautan</strong> (Copy Link).</li>
                        <li>Tempelkan tautan ke <strong><?= Config::APP_NAME ?></strong> untuk mengunduh video & MP3.</li>
                    </ol>

                    <div class="p-3 rounded-3 bg-light border border-light-subtle small mt-auto">
                        <div class="fw-semibold text-info mb-1"><i class="bi bi-info-circle me-1"></i>Format Link:</div>
                        <code class="text-secondary text-break">tiktok.com/@user/video/...</code>
                    </div>
                </div>
            </div>

            <!-- Instagram Guide -->
            <div class="col-md-6 col-lg-3">
                <div class="card glass-card h-100 border-0 p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon text-danger bg-danger-subtle">
                            <i class="bi bi-instagram fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Instagram Reels</h5>
                            <span class="badge bg-danger-subtle text-danger-emphasis small">Reels & Post</span>
                        </div>
                    </div>

                    <ol class="guide-steps ps-3 text-secondary small mb-4">
                        <li class="mb-2">Buka aplikasi atau situs <strong>Instagram</strong>.</li>
                        <li class="mb-2">Cari video Reel yang ingin disimpan.</li>
                        <li class="mb-2">Tekan ikon <strong>Bagikan</strong> atau titik tiga <code>&hellip;</code>.</li>
                        <li class="mb-2">Pilih <strong>Salin Tautan</strong> (Copy link).</li>
                        <li>Tempel tautan ke <strong><?= Config::APP_NAME ?></strong> untuk mengunduh.</li>
                    </ol>

                    <div class="p-3 rounded-3 bg-light border border-light-subtle small mt-auto">
                        <div class="fw-semibold text-danger mb-1"><i class="bi bi-info-circle me-1"></i>Format Link:</div>
                        <code class="text-secondary text-break">instagram.com/reel/C.../</code>
                    </div>
                </div>
            </div>

            <!-- Shopee Video Guide -->
            <div class="col-md-6 col-lg-3">
                <div class="card glass-card h-100 border-0 p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon text-warning bg-warning-subtle">
                            <i class="bi bi-bag-check-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Shopee Video</h5>
                            <span class="badge bg-warning-subtle text-warning-emphasis small">sv.shopee / shp.ee</span>
                        </div>
                    </div>

                    <ol class="guide-steps ps-3 text-secondary small mb-4">
                        <li class="mb-2">Buka aplikasi <strong>Shopee</strong> di ponsel Anda.</li>
                        <li class="mb-2">Masuk ke menu tab <strong>Shopee Video</strong>.</li>
                        <li class="mb-2">Tekan tombol <strong>Bagikan / Share</strong> pada video.</li>
                        <li class="mb-2">Pilih opsi <strong>Salin Tautan</strong> (Copy Link).</li>
                        <li>Tempel tautan pada kolom pencarian dan klik <strong>Unduh</strong>.</li>
                    </ol>

                    <div class="p-3 rounded-3 bg-light border border-light-subtle small mt-auto">
                        <div class="fw-semibold text-warning mb-1"><i class="bi bi-info-circle me-1"></i>Format Link:</div>
                        <code class="text-secondary text-break">sv.shopee.co.id/share-video/...</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= $baseUrl ?>/" class="btn btn-gradient-primary px-4 fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Downloader
            </a>
        </div>
    </div>
</main>
