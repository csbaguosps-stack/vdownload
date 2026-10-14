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
        $sourceUrl = $_GET['source_url'] ?? null;
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
        $isYtSource = !empty($sourceUrl) && (str_contains($sourceUrl, 'youtube.com') || str_contains($sourceUrl, 'youtu.be'));
        $needsMergeParam = isset($_GET['needs_merge']) ? ($_GET['needs_merge'] === '1' || $_GET['needs_merge'] === 'true') : null;

        // 1. DEDICATED YOUTUBE / YT MUSIC CONVERSION & MERGING (1080p, 720p, 360p, 320k MP3)
        if (!$forceRaw && $isYtSource && MediaCompressor::isAvailable()) {
            if ($type === 'audio' && str_ends_with(strtolower($fileName), '.mp3')) {
                // Pass direct audio URL to enable instantaneous direct transcode!
                $targetFile = MediaCompressor::transcodeYtAudio($sourceUrl, '320k', $fileUrl);
                if ($targetFile && file_exists($targetFile) && filesize($targetFile) > 1024) {
                    $this->streamLocalFile($targetFile, $fileName, 'audio/mpeg', $isHead);
                    exit;
                }
            } elseif ($type === 'video') {
                $maxH = '1080';
                if (str_contains($fileName, '360p') || str_contains($fileName, '_SD')) {
                    $maxH = '360';
                } elseif (str_contains($fileName, '720p')) {
                    $maxH = '720';
                } elseif (str_contains($fileName, '1080p')) {
                    $maxH = '1080';
                }

                // Merge only if specifically required (1080p DASH, or needs_merge is explicitly true, or separate audio stream is attached)
                $shouldMerge = ($needsMergeParam === true) 
                    || str_contains($fileName, '1080p') 
                    || (!empty($audioUrl) && $audioUrl !== $fileUrl);

                if ($shouldMerge) {
                    $targetFile = MediaCompressor::mergeYtVideo($sourceUrl, $maxH);
                    if ($targetFile && file_exists($targetFile) && filesize($targetFile) > 1024) {
                        $this->streamLocalFile($targetFile, $fileName, 'video/mp4', $isHead);
                        exit;
                    }
                }
            }
        }

        // 2. RESOLUTION SCALING & SMART COMPRESSION FOR ALL PLATFORMS (360p, 720p, 1080p)
        $isSpecificRes = ($type === 'video') && (str_contains($fileName, '360p') || str_contains($fileName, '720p'));
        $shouldCompress = !$forceRaw && ($cachedParam || $compressParam === '1' || $isSpecificRes) && MediaCompressor::isAvailable() && in_array($type, ['video', 'audio'], true);

        if ($shouldCompress) {
            $options = [];
            if (str_contains($fileName, '360p')) {
                $options['height'] = 360;
                $options['crf'] = 24;
            } elseif (str_contains($fileName, '720p')) {
                $options['height'] = 720;
                $options['crf'] = 22;
            }

            $cachedFile = MediaCompressor::getCachedFilePath($fileUrl, $type, $audioUrl, $options);
            $targetFile = $cachedFile ?: ($type === 'video' 
                ? MediaCompressor::compressVideo($fileUrl, $audioUrl, $options) 
                : MediaCompressor::process($fileUrl, $type, $audioUrl));

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

                $this->streamLocalFile($targetFile, $fileName, $mime, $isHead);
                exit;
            }
        }

        // 3. FALLBACK: Direct cURL proxy streaming with HTTP Range and 1MB buffer
        $mime = match ($type) {
            'audio' => (str_ends_with(strtolower($fileName), '.m4a') ? 'audio/mp4' : 'audio/mpeg'),
            'image' => 'image/jpeg',
            default => 'video/mp4'
        };

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Accept-Ranges: bytes');

        $headers = [
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.9',
            'Sec-Fetch-Mode: no-cors'
        ];
        if (str_contains($fileUrl, 'googlevideo.com')) {
            $headers[] = 'Referer: https://music.youtube.com/';
            $headers[] = 'Origin: https://music.youtube.com';
        }
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
        }

        // Stream using cURL with live buffer flush and Content-Length / Range passthrough
        $ch = curl_init($fileUrl);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_NOBODY => $isHead,
            CURLOPT_BUFFERSIZE => 1024 * 1024,
            CURLOPT_HEADERFUNCTION => function($curl, $headerLine) {
                if (stripos($headerLine, 'Content-Length:') === 0 
                    || stripos($headerLine, 'Content-Range:') === 0 
                    || stripos($headerLine, 'Accept-Ranges:') === 0) {
                    header(trim($headerLine));
                }
                if (stripos($headerLine, 'HTTP/') === 0 && str_contains($headerLine, '206')) {
                    http_response_code(206);
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
     * Helper to stream local cached file with HTTP Range support for resumable multi-threaded downloads
     */
    private function streamLocalFile(string $filePath, string $downloadName, string $mime, bool $isHead): void
    {
        $fileSize = filesize($filePath);
        $start = 0;
        $end = $fileSize - 1;

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Accept-Ranges: bytes');
        header('X-VDownload-Auto-Compressed: 1');

        // Handle HTTP Range header (Resumable & Parallel multi-part downloads)
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = (int)$matches[1];
            if (!empty($matches[2])) {
                $end = (int)$matches[2];
            }
            if ($start > $end || $start >= $fileSize) {
                http_response_code(416);
                header("Content-Range: bytes */{$fileSize}");
                exit;
            }
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
            header('Content-Length: ' . ($end - $start + 1));
        } else {
            header('Content-Length: ' . $fileSize);
        }

        if (!$isHead) {
            $fp = fopen($filePath, 'rb');
            if ($fp) {
                if ($start > 0) {
                    fseek($fp, $start);
                }
                $bytesRemaining = $end - $start + 1;
                $chunkSize = 1024 * 1024; // 1 MB high-throughput chunk
                while (!feof($fp) && $bytesRemaining > 0 && connection_status() === CONNECTION_NORMAL) {
                    $readLen = min($chunkSize, $bytesRemaining);
                    $data = fread($fp, $readLen);
                    echo $data;
                    flush();
                    $bytesRemaining -= strlen($data);
                }
                fclose($fp);
            }
        }
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
