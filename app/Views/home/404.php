<?php
/**
 * VDownload Pro - 404 Not Found View
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
?>
<main class="py-5 text-center">
    <div class="container py-5">
        <div class="card glass-card border-0 p-5 max-w-500 mx-auto shadow-sm">
            <h1 class="display-1 fw-black text-danger mb-2">404</h1>
            <h4 class="fw-bold text-dark mb-3">Halaman Tidak Ditemukan</h4>
            <p class="text-secondary small mb-4">Halaman atau tautan yang Anda cari tidak tersedia atau telah dipindahkan.</p>
            <div>
                <a href="<?= $baseUrl ?>/" class="btn btn-gradient-primary px-4 fw-bold">
                    <i class="bi bi-house-door-fill me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</main>
