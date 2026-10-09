<?php
/**
 * VDownload Pro - Download History View
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
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom border-light-subtle">
            <div>
                <h2 class="fw-extrabold text-dark mb-1">
                    <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Unduhan
                </h2>
                <p class="text-secondary small mb-0">Daftar media dan video yang baru saja Anda analisa dan unduh</p>
            </div>
            <?php if (!empty($history)): ?>
            <form action="<?= $baseUrl ?>/history/clear" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat unduhan?');">
                <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-trash3-fill"></i> Bersihkan Semua Riwayat
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert alert-success border-0 glass-alert mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> Riwayat unduhan berhasil dibersihkan!
            </div>
        <?php endif; ?>

        <?php if (empty($history)): ?>
            <div class="card glass-card border-0 p-5 text-center my-4">
                <div class="mb-3 text-secondary">
                    <i class="bi bi-inbox fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Belum Ada Riwayat Unduhan</h5>
                <p class="text-secondary small mb-4">Video yang Anda cari dan unduh akan tersimpan di sini secara otomatis untuk akses cepat.</p>
                <div>
                    <a href="<?= $baseUrl ?>/" class="btn btn-gradient-primary px-4 fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Mulai Unduh Video
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($history as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card glass-card border-0 h-100 overflow-hidden shadow-sm d-flex flex-column justify-content-between">
                        <div class="p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge badge-platform badge-<?= htmlspecialchars($item['platform'] ?? 'video') ?>">
                                        <?= htmlspecialchars($item['platform_name'] ?? 'Video') ?>
                                    </span>
                                    <?php if (!empty($item['resolution'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 small">
                                            <?= htmlspecialchars($item['resolution']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-secondary small opacity-75">
                                    <?= htmlspecialchars($item['formatted_time'] ?? '') ?>
                                </span>
                            </div>

                            <div class="d-flex gap-3 align-items-start mb-3">
                                <div class="history-thumb-wrapper rounded-3 overflow-hidden bg-black flex-shrink-0 shadow-sm" style="width: 80px; height: 110px;">
                                    <?php if (!empty($item['thumbnail'])): ?>
                                        <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="Thumbnail" class="w-100 h-100 object-fit-cover" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23e2e8f0%22/><text x=%2250%22 y=%2255%22 font-size=%2225%22 text-anchor=%22middle%22 fill=%22%23475569%22>🎬</text></svg>'">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 text-secondary">
                                            <i class="bi bi-camera-video fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="text-dark fw-bold mb-1 line-clamp-2" title="<?= htmlspecialchars($item['title'] ?? '') ?>">
                                        <?= htmlspecialchars($item['title'] ?? 'Video') ?>
                                    </h6>
                                    <div class="text-secondary small mb-2">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($item['author'] ?? 'Creator') ?>
                                    </div>
                                    <?php if (!empty($item['preview_video'])): ?>
                                    <a href="<?= htmlspecialchars($item['preview_video']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                                        <i class="bi bi-play-fill"></i> Tonton
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-top border-light-subtle p-3">
                            <div class="d-flex flex-column gap-2">
                                <?php if (!empty($item['downloads'])): ?>
                                    <?php foreach ($item['downloads'] as $dl): 
                                        $streamUrl = $baseUrl . '/download/file?url=' . urlencode($dl['url']) . '&name=' . urlencode($dl['filename'] ?? 'video.mp4') . '&type=' . ($dl['type'] ?? 'video');
                                    ?>
                                    <a href="<?= $streamUrl ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-download me-1"></i> <?= htmlspecialchars($dl['quality'] ?? 'Unduh') ?></span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><?= strtoupper($dl['type'] ?? 'MP4') ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <form action="<?= $baseUrl ?>/history/delete" method="POST" class="mt-1 text-end">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? '') ?>">
                                    <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small">
                                        <i class="bi bi-trash"></i> Hapus dari riwayat
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
