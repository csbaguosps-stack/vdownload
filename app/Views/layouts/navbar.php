<?php
/**
 * VDownload Pro - Navbar Layout
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
$activeNav = $activeNav ?? 'home';
?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top glass-nav">
    <div class="container py-1">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="<?= $baseUrl ?>/">
            <span class="brand-logo-icon">
                <i class="bi bi-cloud-arrow-down-fill"></i>
            </span>
            <span class="brand-text">
                <span class="gradient-text">VDownload</span><span class="text-primary fw-black">Pro</span>
            </span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'home' ? 'active fw-bold text-primary' : '' ?>" href="<?= $baseUrl ?>/">
                        <i class="bi bi-house-door me-1"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'history' ? 'active fw-bold text-primary' : '' ?>" href="<?= $baseUrl ?>/history">
                        <i class="bi bi-clock-history me-1"></i> Riwayat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'guide' ? 'active fw-bold text-primary' : '' ?>" href="<?= $baseUrl ?>/guide">
                        <i class="bi bi-journal-text me-1"></i> Panduan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'extension' ? 'active fw-bold text-primary' : '' ?>" href="<?= $baseUrl ?>/extension">
                        <i class="bi bi-puzzle me-1"></i> Ekstensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeNav === 'settings' ? 'active fw-bold text-primary' : '' ?>" href="<?= $baseUrl ?>/settings">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0 pt-2 pt-lg-0 border-top border-lg-0 border-light-subtle">
                <div class="platform-indicators d-flex align-items-center gap-1 flex-wrap">
                    <span class="badge badge-platform badge-youtube" title="YouTube Video & Musik Didukung">
                        <i class="bi bi-youtube"></i> YouTube
                    </span>
                    <span class="badge badge-platform badge-tiktok" title="TikTok Video & Foto Slide Didukung">
                        <i class="bi bi-tiktok"></i> TikTok
                    </span>
                    <span class="badge badge-platform badge-instagram" title="Instagram Reels, Foto & Carousel Didukung">
                        <i class="bi bi-instagram"></i> IG
                    </span>
                    <span class="badge badge-platform badge-shopee" title="Shopee Video Didukung">
                        <i class="bi bi-bag-check-fill"></i> Shopee
                    </span>
                </div>
            </div>
        </div>
    </div>
</nav>
