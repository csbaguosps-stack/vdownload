<?php
/**
 * VDownload Pro - Download Controller
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ShopeeDownloader;
use App\Models\TikTokDownloader;
use App\Models\InstagramDownloader;
use App\Models\YouTubeDownloader;
use App\Models\DownloadHistory;
use App\Models\MediaCompressor;

class DownloadController extends Controller
{
    private array $downloaders;

    public function __construct()
    {
        $this->downloaders = [
            new YouTubeDownloader(),
            new ShopeeDownloader(),
            new TikTokDownloader(),
            new InstagramDownloader()
        ];
    }

    /**
     * AJAX endpoint to analyze URL and fetch download info
     */
    public function fetch(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = trim((string)($input['url'] ?? ($_POST['url'] ?? '')));

        if (empty($url)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Silakan masukkan tautan video terlebih dahulu.'
            ], 400);
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Format tautan tidak valid. Pastikan diawali dengan http:// atau https://'
            ], 400);
        }

        // Find matching downloader
        $selectedDownloader = null;
        foreach ($this->downloaders as $downloader) {
            if ($downloader->supports($url)) {
                $selectedDownloader = $downloader;
                break;
            }
        }

        if (!$selectedDownloader) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Platform tidak didukung. Saat ini kami mendukung YouTube, TikTok, Instagram, dan Shopee Video.'
            ], 400);
        }

        try {
            $result = $selectedDownloader->extract($url);

            // Save to recent history
            DownloadHistory::add($result);

            $this->jsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * AJAX endpoint to trigger video/audio compression with status tracking
     */
    public function compress(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = trim((string)($input['url'] ?? ($_POST['url'] ?? '')));
        $name = trim((string)($input['name'] ?? ($_POST['name'] ?? 'video.mp4')));
        $type = trim((string)($input['type'] ?? ($_POST['type'] ?? 'video')));
        $audioUrl = trim((string)($input['audio_url'] ?? ($_POST['audio_url'] ?? '')));
        $origBytes = (int)($input['size_bytes'] ?? ($_POST['size_bytes'] ?? 0));

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'URL berkas tidak valid untuk dikompresi.'
            ], 400);
        }

        if (!MediaCompressor::isAvailable()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Mesin kompresi FFmpeg tidak tersedia di server.'
            ], 500);
        }

        $res = MediaCompressor::compressWithInfo($url, $type, $audioUrl ?: null, $origBytes);

        if (!$res['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => $res['error'] ?? 'Gagal mengompresi berkas.'
            ], 422);
        }

        // Clean filename
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
        if ($type === 'audio' && !str_ends_with(strtolower($name), '.mp3')) {
            $name = preg_replace('/\.[a-zA-Z0-9]+$/', '', $name) . '.mp3';
        } elseif ($type === 'video' && !str_ends_with(strtolower($name), '.mp4')) {
            $name = preg_replace('/\.[a-zA-Z0-9]+$/', '', $name) . '.mp4';
        }

        $baseUrl = \App\Config\Config::getBaseUrl();
        $downloadUrl = $baseUrl . '/download/file?url=' . urlencode($url) . '&name=' . urlencode($name) . '&type=' . urlencode($type) . '&cached=1';
        if (!empty($audioUrl)) {
            $downloadUrl .= '&audio_url=' . urlencode($audioUrl);
        }

        $this->jsonResponse([
            'success' => true,
            'download_url' => $downloadUrl,
            'filename' => $name,
            'is_cached' => $res['is_cached'],
            'original_size' => $res['original_size_formatted'],
            'compressed_size' => $res['compressed_size_formatted'],
            'savings_percent' => $res['savings_percent'] . '%'
        ]);
    }

    /**
     * File streaming / proxy download endpoint
     * Instant streaming by default; serves compressed file if cached=1 or compress=1
     */
    public function file(): void
    {
        $fileUrl = $_GET['url'] ?? '';
        $fileName = $_GET['name'] ?? $_GET['filename'] ?? 'downloaded_video.mp4';
        $type = $_GET['type'] ?? 'video';
        $audioUrl = $_GET['audio_url'] ?? null;
        $cachedParam = isset($_GET['cached']);
        $compressParam = $_GET['compress'] ?? null;
        $forceRaw = ($compressParam === '0' || $compressParam === 'false' || isset($_GET['raw']));

        if (empty($fileUrl) || !filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo 'Parameter URL tidak valid.';
            exit;
        }

        // Clean filename
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
        if (empty($fileName)) {
            $fileName = ($type === 'audio' ? 'audio_' : 'video_') . time() . ($type === 'audio' ? '.mp3' : '.mp4');
        }

        // Turn off output buffering to prevent memory exhaustion and allow high-speed streaming
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Allow streaming / compressing without script timeout
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $isHead = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD';

        // 1. FAST CACHED / EXPLICIT COMPRESSED DELIVERY
        $shouldCompress = !$forceRaw && ($cachedParam || $compressParam === '1') && MediaCompressor::isAvailable() && in_array($type, ['video', 'audio'], true);

        if ($shouldCompress) {
            $cachedFile = MediaCompressor::getCachedFilePath($fileUrl, $type, $audioUrl);
            $targetFile = $cachedFile ?: ($compressParam === '1' ? MediaCompressor::process($fileUrl, $type, $audioUrl) : null);

            if ($targetFile && file_exists($targetFile) && filesize($targetFile) > 1024) {
                // Adjust file extension & mime
                if ($type === 'audio') {
                    $mime = 'audio/mpeg';
                    if (!str_ends_with(strtolower($fileName), '.mp3')) {
                        $fileName = preg_replace('/\.[a-zA-Z0-9]+$/', '', $fileName) . '.mp3';
                    }
                } else {
                    $mime = 'video/mp4';
                    if (!str_ends_with(strtolower($fileName), '.mp4')) {
                        $fileName = preg_replace('/\.[a-zA-Z0-9]+$/', '', $fileName) . '.mp4';
                    }
                }

                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mime);
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($targetFile));
                header('X-VDownload-Auto-Compressed: 1');

                if (!$isHead) {
                    $fp = fopen($targetFile, 'rb');
                    if ($fp) {
                        while (!feof($fp)) {
                            echo fread($fp, 256 * 1024);
                            flush();
                        }
                        fclose($fp);
                    }
                }
                exit;
            }
        }

        // 2. FALLBACK: Direct cURL proxy streaming
        $mime = match ($type) {
            'audio' => 'audio/mpeg',
            'image' => 'image/jpeg',
            default => 'video/mp4'
        };

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Stream using cURL with live buffer flush and Content-Length passthrough
        $ch = curl_init($fileUrl);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_NOBODY => $isHead,
            CURLOPT_BUFFERSIZE => 128 * 1024,
            CURLOPT_HEADERFUNCTION => function($curl, $headerLine) {
                if (stripos($headerLine, 'Content-Length:') === 0) {
                    header(trim($headerLine));
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => function($curl, $data) use ($isHead) {
                if (!$isHead) {
                    echo $data;
                    flush();
                }
                return strlen($data);
            }
        ]);

        curl_exec($ch);
        curl_close($ch);
        exit;
    }

    /**
     * Download multiple images/media as a single compressed ZIP archive
     */
    public function zip(): void
    {
        $urls = $_GET['urls'] ?? $_POST['urls'] ?? [];
        $zipName = $_GET['name'] ?? $_POST['name'] ?? 'photos.zip';

        if (is_string($urls)) {
            $urls = explode(',', $urls);
        }

        $urls = array_filter(array_map('trim', (array)$urls), function($u) {
            return !empty($u) && filter_var($u, FILTER_VALIDATE_URL);
        });

        if (empty($urls)) {
            http_response_code(400);
            echo 'Tidak ada tautan gambar yang valid untuk dibuatkan ZIP.';
            exit;
        }

        $zipName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string)$zipName);
        if (!str_ends_with(strtolower($zipName), '.zip')) {
            $zipName .= '.zip';
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $cacheDir = \App\Config\Config::getStoragePath('cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $tempZipPath = $cacheDir . DIRECTORY_SEPARATOR . 'photos_' . md5(json_encode($urls) . microtime()) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo 'Gagal menyiapkan berkas ZIP di server.';
            exit;
        }

        $idx = 1;
        foreach ($urls as $fileUrl) {
            $ch = curl_init($fileUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                CURLOPT_TIMEOUT => 25
            ]);
            $fileData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($fileData)) {
                $ext = 'jpg';
                if (stripos((string)$contentType, 'png') !== false) {
                    $ext = 'png';
                } elseif (stripos((string)$contentType, 'webp') !== false) {
                    $ext = 'webp';
                } elseif (stripos((string)$contentType, 'mp4') !== false) {
                    $ext = 'mp4';
                }

                $entryName = sprintf("photo_%02d.%s", $idx, $ext);
                $zip->addFromString($entryName, $fileData);
                $idx++;
            }
        }

        $zip->close();

        if (!file_exists($tempZipPath) || filesize($tempZipPath) < 64) {
            http_response_code(500);
            echo 'Gagal mengemas berkas ke dalam ZIP.';
            if (file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tempZipPath));

        $fp = fopen($tempZipPath, 'rb');
        if ($fp) {
            while (!feof($fp)) {
                echo fread($fp, 256 * 1024);
                flush();
            }
            fclose($fp);
        }

        @unlink($tempZipPath);
        exit;
    }
}
