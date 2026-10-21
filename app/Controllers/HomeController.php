<?php
/**
 * VDownload Pro - Home Controller
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Config\Config;
use App\Models\DownloadHistory;
use App\Models\SettingModel;
use App\Models\MediaCompressor;

class HomeController extends Controller
{
    public function index(): void
    {
        $platforms = Config::getSupportedPlatforms();
        $recentDownloads = array_slice(DownloadHistory::getAll(), 0, 6);
        $settings = SettingModel::get();

        $this->render('home/index', [
            'pageTitle' => 'VDownload Pro - Unduh Video TikTok, Instagram & Shopee',
            'activeNav' => 'home',
            'platforms' => $platforms,
            'recentDownloads' => $recentDownloads,
            'settings' => $settings
        ]);
    }

    public function history(): void
    {
        $history = DownloadHistory::getAll();

        $this->render('home/history', [
            'pageTitle' => 'Riwayat Unduhan - VDownload Pro',
            'activeNav' => 'history',
            'history' => $history
        ]);
    }

    public function guide(): void
    {
        $platforms = Config::getSupportedPlatforms();

        $this->render('home/guide', [
            'pageTitle' => 'Panduan Penggunaan - VDownload Pro',
            'activeNav' => 'guide',
            'platforms' => $platforms
        ]);
    }

    public function extension(): void
    {
        $zipFile = dirname(__DIR__, 2) . '/downloads/vdownload-extension.zip';
        $fileSize = file_exists($zipFile) ? (int)filesize($zipFile) : 15360;
        $fileSizeKb = max(1, round($fileSize / 1024, 1));

        $this->render('home/extension', [
            'pageTitle' => 'Download & Panduan Ekstensi Browser - VDownload Pro',
            'activeNav' => 'extension',
            'fileSizeKb' => $fileSizeKb
        ]);
    }

    public function downloadExtension(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $extensionDir = $baseDir . '/extension';
        $downloadDir = $baseDir . '/downloads';
        $zipPath = $downloadDir . '/vdownload-extension.zip';

        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        // Dynamically pack or refresh ZIP with the current website base URL
        if (class_exists(\ZipArchive::class) && is_dir($extensionDir)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $currentBaseUrl = Config::getBaseUrl();
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($extensionDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($extensionDir) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);

                        if ($relativePath === 'popup.js' || $relativePath === 'background.js') {
                            $content = (string)file_get_contents($filePath);
                            $content = str_replace(
                                "const DEFAULT_SERVER_URL = 'http://localhost/vdownload';",
                                "const DEFAULT_SERVER_URL = '" . addslashes($currentBaseUrl) . "';",
                                $content
                            );
                            $zip->addFromString($relativePath, $content);
                        } elseif ($relativePath === 'popup.html') {
                            $content = (string)file_get_contents($filePath);
                            $content = str_replace(
                                'value="http://localhost/vdownload"',
                                'value="' . htmlspecialchars($currentBaseUrl) . '"',
                                $content
                            );
                            $zip->addFromString($relativePath, $content);
                        } else {
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                }
                $zip->close();
            }
        }

        if (file_exists($zipPath)) {
            // Clean output buffer to ensure clean binary download
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="vdownload-extension.zip"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Access-Control-Allow-Origin: *');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            exit;
        }

        http_response_code(404);
        echo 'Berkas ekstensi belum tersedia untuk diunduh.';
        exit;
    }

    public function settings(): void
    {
        $settings = SettingModel::get();
        $cacheStats = MediaCompressor::getCacheStats();
        $isFfmpegAvailable = MediaCompressor::isAvailable();
        $ffmpegPath = MediaCompressor::getFfmpegPath();

        $this->render('home/settings', [
            'pageTitle' => 'Pengaturan Downloader - VDownload Pro',
            'activeNav' => 'settings',
            'settings' => $settings,
            'cacheStats' => $cacheStats,
            'isFfmpegAvailable' => $isFfmpegAvailable,
            'ffmpegPath' => $ffmpegPath
        ]);
    }

    public function saveSettings(): void
    {
        $sessionId = trim((string)($_POST['instagram_sessionid'] ?? ''));
        $theme = trim((string)($_POST['theme'] ?? 'light'));
        $autoCompress = isset($_POST['auto_compress']);
        $compressionQuality = trim((string)($_POST['compression_quality'] ?? 'balanced'));
        $audioBitrate = trim((string)($_POST['audio_bitrate'] ?? '128k'));

        SettingModel::save([
            'instagram_sessionid' => $sessionId,
            'theme' => $theme,
            'auto_compress' => $autoCompress,
            'compression_quality' => $compressionQuality,
            'audio_bitrate' => $audioBitrate
        ]);

        $this->redirect('settings?success=1');
    }

    /**
     * AJAX endpoint to save or update Instagram session ID
     */
    public function saveInstagramSession(): void
    {
        $input = json_decode((string)file_get_contents('php://input'), true);
        $sessionId = trim((string)($input['instagram_sessionid'] ?? ($_POST['instagram_sessionid'] ?? '')));

        SettingModel::save([
            'instagram_sessionid' => $sessionId
        ]);

        $this->jsonResponse([
            'success' => true,
            'message' => empty($sessionId) ? 'Sesi Instagram berhasil dihapus.' : 'Sesi Instagram berhasil disimpan dan siap digunakan!',
            'has_session' => !empty($sessionId)
        ]);
    }

    /**
     * AJAX endpoint to get Instagram session status
     */
    public function getInstagramStatus(): void
    {
        $sessionId = SettingModel::getInstagramSessionId();
        $this->jsonResponse([
            'success' => true,
            'has_session' => !empty($sessionId),
            'masked_session' => !empty($sessionId) ? (substr($sessionId, 0, 8) . '...' . substr($sessionId, -4)) : ''
        ]);
    }

    public function clearCompressionCache(): void
    {
        MediaCompressor::clearCache();
        $this->redirect('settings?cache_cleared=1');
    }

    public function clearHistory(): void
    {
        DownloadHistory::clear();
        $this->redirect('history?cleared=1');
    }

    public function deleteHistoryItem(): void
    {
        $id = $_POST['id'] ?? ($_GET['id'] ?? '');
        if (!empty($id)) {
            DownloadHistory::delete($id);
        }
        $this->redirect('history');
    }

    public function notFound(): void
    {
        $this->render('home/404', [
            'pageTitle' => 'Halaman Tidak Ditemukan - VDownload Pro',
            'activeNav' => ''
        ]);
    }
}
