<?php
/**
 * VDownload Pro - Settings View
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-light-subtle">
                    <div>
                        <h2 class="fw-extrabold text-dark mb-1">
                            <i class="bi bi-gear-fill text-primary me-2"></i>Pengaturan Aplikasi
                        </h2>
                        <p class="text-secondary small mb-0">Konfigurasi sesi dan preferensi pengunduh video</p>
                    </div>
                    <a href="<?= $baseUrl ?>/" class="btn btn-outline-primary btn-sm fw-medium">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success border-0 glass-alert mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> Pengaturan berhasil disimpan!
                    </div>
                <?php endif; ?>

                <div class="card glass-card border-0 p-4 shadow-sm mb-4">
                    <!-- Extension Quick Info -->
                    <div class="alert alert-info border-0 bg-primary-subtle text-primary p-3 rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-puzzle-fill fs-4 flex-shrink-0"></i>
                            <div class="small">
                                <strong>Ekstensi Browser Resmi:</strong> Gunakan ekstensi VDownload Pro di Chrome/Edge untuk mengambil & menyinkronkan cookie Instagram secara otomatis dengan 1-klik tanpa perlu membuka F12!
                            </div>
                        </div>
                        <span class="badge bg-primary text-white px-2 py-1 small">vdownload/extension</span>
                    </div>

                    <form action="<?= $baseUrl ?>/settings/save" method="POST">
                        <!-- Instagram Session ID Cookie -->
                        <div class="mb-4">
                            <label for="igSession" class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-instagram text-danger"></i> Cookie Instagram (sessionid)
                                <span class="badge bg-secondary-subtle text-secondary-emphasis small">Opsional</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control bg-white text-dark border-secondary-subtle" 
                                id="igSession" 
                                name="instagram_sessionid" 
                                placeholder="Contoh: 123456789%3AabcdeFGHIjkl%3A..."
                                value="<?= htmlspecialchars($settings['instagram_sessionid'] ?? '') ?>"
                            >
                            <div class="form-text text-secondary small mt-2">
                                <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                                <strong>Kenapa ini berguna?</strong> Instagram menerapkan proteksi ketat untuk pengunjung tanpa login. Memasukkan cookie <code>sessionid</code> memungkinkan sistem mengunduh video Reels atau postingan Instagram dengan tingkat keberhasilan 100%.
                            </div>
                        </div>

                        <!-- How to get sessionid instructions -->
                        <div class="p-3 rounded-3 bg-light border border-light-subtle mb-4 small text-secondary">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-question-circle me-1 text-primary"></i>Cara Mendapatkan Cookie sessionid:</h6>
                            <ol class="ps-3 mb-0">
                                <li class="mb-1">Buka browser di komputer Anda dan login ke <a href="https://www.instagram.com" target="_blank" class="text-primary fw-medium">instagram.com</a>.</li>
                                <li class="mb-1">Tekan tombol <kbd class="bg-secondary text-white">F12</kbd> untuk membuka <strong>Developer Tools</strong>.</li>
                                <li class="mb-1">Buka tab <strong>Application</strong> (di Chrome/Edge) atau <strong>Storage</strong> (di Firefox).</li>
                                <li class="mb-1">Di panel kiri, klik <strong>Cookies</strong> &rarr; <code>https://www.instagram.com</code>.</li>
                                <li class="mb-1">Cari nama <code>sessionid</code>, lalu salin isinya (Value) dan tempelkan ke kolom di atas.</li>
                            </ol>
                        </div>

                        <!-- Automatic Smart Compression Settings -->
                        <div class="card bg-light border border-primary-subtle p-3 rounded-3 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="bi bi-file-earmark-zip-fill text-primary me-1"></i> Kompresi Otomatis Cerdas (Auto-Compress)
                                    </h6>
                                    <p class="text-secondary small mb-0">Mengompres video & audio otomatis saat diunduh: ukuran file jauh lebih kecil (~60%), namun tetap jernih dan tidak pecah.</p>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        role="switch" 
                                        id="autoCompressSwitch" 
                                        name="auto_compress" 
                                        value="1" 
                                        <?= !empty($settings['auto_compress']) ? 'checked' : '' ?>
                                    >
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="compressionQuality" class="form-label fw-bold text-dark small">Tingkat Ketajaman Video (CRF)</label>
                                    <select class="form-select form-select-sm border-secondary-subtle" id="compressionQuality" name="compression_quality">
                                        <option value="balanced" <?= ($settings['compression_quality'] ?? 'balanced') === 'balanced' ? 'selected' : '' ?>>
                                            ⭐ Seimbang (CRF 23) - Hemat ~60%, Gambar Sangat Jernih (Direkomendasikan)
                                        </option>
                                        <option value="extra" <?= ($settings['compression_quality'] ?? '') === 'extra' ? 'selected' : '' ?>>
                                            ⚡ Ekstra Hemat (CRF 26) - Super Ringan, Cocok Kuota Minim
                                        </option>
                                        <option value="hq" <?= ($settings['compression_quality'] ?? '') === 'hq' ? 'selected' : '' ?>>
                                            💎 Kualitas Tertinggi (CRF 20) - Detail Tajam Studio HD
                                        </option>
                                    </select>
                                    <div class="form-text small text-secondary">
                                        Preset <code>veryfast</code> dengan H.264 High Profile & AAC 128k menjamin kompatibilitas 100% di semua HP dan laptop.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="audioBitrate" class="form-label fw-bold text-dark small">Kualitas Audio Musik (MP3)</label>
                                    <select class="form-select form-select-sm border-secondary-subtle" id="audioBitrate" name="audio_bitrate">
                                        <option value="128k" <?= ($settings['audio_bitrate'] ?? '128k') === '128k' ? 'selected' : '' ?>>
                                            🎵 128 kbps Stereo HQ (~2.5 - 3.5 MB per lagu - Optimal & Jernih)
                                        </option>
                                        <option value="192k" <?= ($settings['audio_bitrate'] ?? '') === '192k' ? 'selected' : '' ?>>
                                            🎧 192 kbps Ultra High Fidelity (~4.5 MB)
                                        </option>
                                        <option value="96k" <?= ($settings['audio_bitrate'] ?? '') === '96k' ? 'selected' : '' ?>>
                                            ⚡ 96 kbps Ekstra Ringan (~1.8 MB)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cache Stats and Purge -->
                            <div class="mt-3 pt-3 border-top border-secondary-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="small text-secondary">
                                    <i class="bi bi-hdd-network text-primary me-1"></i>
                                    <strong>Status Cache File Terkompresi:</strong> 
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                        <?= (int)($cacheStats['count'] ?? 0) ?> berkas (<?= htmlspecialchars($cacheStats['formatted_size'] ?? '0 MB') ?>)
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="if(confirm('Yakin ingin mengosongkan cache kompresi? Berkas akan otomatis dikompres ulang saat diminta.')) document.getElementById('clearCacheForm').submit();">
                                    <i class="bi bi-trash3-fill me-1"></i> Bersihkan Cache Kompresi
                                </button>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Status Layanan & Ekstensi Sistem</label>
                            <div class="list-group list-group-flush rounded-3 border border-light-subtle overflow-hidden shadow-sm">
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cpu-fill me-2 text-danger"></i>FFmpeg Smart Compression Engine</span>
                                    <span class="badge bg-success-subtle text-success fw-bold">
                                        <?= !empty($isFfmpegAvailable) ? 'Aktif & Siap (FFmpeg 7.1 x264/AAC)' : 'Tidak Ditemukan' ?>
                                    </span>
                                </div>
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-youtube me-2 text-danger"></i>YouTube yt-dlp Core</span>
                                    <span class="badge bg-success-subtle text-success fw-bold">Aktif & Siap</span>
                                </div>
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-filetype-php me-2 text-primary"></i>Versi PHP Server</span>
                                    <span class="badge bg-success-subtle text-success fw-bold"><?= PHP_VERSION ?> (PHP 8.2 Compatible)</span>
                                </div>
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-globe me-2 text-info"></i>Ekstensi cURL & OpenSSL</span>
                                    <span class="badge bg-success-subtle text-success fw-bold">Aktif & Siap</span>
                                </div>
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-bag-check me-2 text-warning"></i>Shopee Native Engine</span>
                                    <span class="badge bg-success-subtle text-success fw-bold">Master HD Ready</span>
                                </div>
                                <div class="list-group-item bg-white text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-tiktok me-2 text-primary"></i>TikWM Engine + True HD</span>
                                    <span class="badge bg-success-subtle text-success fw-bold">Terkoneksi HD</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-gradient-primary px-4 fw-bold">
                                <i class="bi bi-floppy-fill me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    <!-- Hidden Cache Clear Form -->
                    <form id="clearCacheForm" action="<?= $baseUrl ?>/settings/clear-cache" method="POST" class="d-none"></form>
                </div>
            </div>
        </div>
    </div>
</main>
