<?php
/**
 * VDownload Pro - Ultra Fast Media Compression Engine (Hardware & Multithread Core)
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 * Optimal Speed: cURL Local Buffer + NVIDIA NVENC / Ultrafast Multithread x264
 */
declare(strict_types=1);

namespace App\Models;

use App\Config\Config;
use App\Core\MediaInspector;

class MediaCompressor
{
    private static ?string $cachedFfmpegPath = null;
    private static ?bool $hasNvencCache = null;

    /**
     * Locate FFmpeg binary on the system
     */
    public static function getFfmpegPath(): ?string
    {
        if (self::$cachedFfmpegPath !== null) {
            return self::$cachedFfmpegPath;
        }

        $appRoot = Config::getAppRoot();
        $candidates = [
            $appRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe',
            'C:\\Users\\Baguosps\\AppData\\Roaming\\Python\\Python314\\site-packages\\imageio_ffmpeg\\binaries\\ffmpeg-win-x86_64-v7.1.exe',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\xampp\\ffmpeg\\ffmpeg.exe'
        ];

        foreach ($candidates as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                self::$cachedFfmpegPath = $path;
                return $path;
            }
        }

        $pathOutput = @shell_exec('where.exe ffmpeg 2>NUL');
        if (!empty($pathOutput)) {
            $lines = explode("\n", trim($pathOutput));
            $found = trim($lines[0]);
            if (!empty($found) && file_exists($found)) {
                self::$cachedFfmpegPath = $found;
                return $found;
            }
        }

        return null;
    }

    /**
     * Check if FFmpeg is available
     */
    public static function isAvailable(): bool
    {
        return self::getFfmpegPath() !== null;
    }

    /**
     * Check if NVIDIA NVENC hardware acceleration is supported
     */
    public static function hasNvenc(): bool
    {
        if (self::$hasNvencCache !== null) {
            return self::$hasNvencCache;
        }

        $ffmpeg = self::getFfmpegPath();
        if (!$ffmpeg) {
            self::$hasNvencCache = false;
            return false;
        }

        $test = @shell_exec(escapeshellarg($ffmpeg) . ' -hide_banner -encoders 2>NUL');
        self::$hasNvencCache = !empty($test) && str_contains($test, 'h264_nvenc');
        return self::$hasNvencCache;
    }

    /**
     * Get directory for caching compressed files
     */
    public static function getCacheDir(): string
    {
        $dir = Config::getStoragePath('cache' . DIRECTORY_SEPARATOR . 'compressed');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Get configured CRF (Constant Rate Factor)
     */
    public static function getCrf(): int
    {
        $settings = SettingModel::get();
        $quality = $settings['compression_quality'] ?? 'balanced';
        return match ($quality) {
            'extra' => 25,     // Hemat kuota
            'hq' => 19,        // Kualitas studio
            default => 22      // Optimal seimbang: visual lossless, tajam anti-pecah
        };
    }

    /**
     * Check if automatic compression is enabled in settings
     */
    public static function isEnabled(): bool
    {
        $settings = SettingModel::get();
        return (bool)($settings['auto_compress'] ?? true);
    }

    /**
     * High-speed parallel cURL buffer download to local temp file
     * Prevents FFmpeg's single-threaded HTTP client from lagging on large files.
     */
    public static function downloadToTemp(string $url, int $timeout = 30): ?string
    {
        $tmp = self::getCacheDir() . DIRECTORY_SEPARATOR . 'src_' . uniqid() . '.mp4';
        $fp = fopen($tmp, 'w+');
        if (!$fp) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_BUFFERSIZE => 512 * 1024,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        ]);

        $success = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($success && ($code === 200 || $code === 206) && file_exists($tmp) && filesize($tmp) > 5000) {
            return $tmp;
        }

        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        return null;
    }

    /**
     * Ultra-fast video compression
     * Uses NVIDIA NVENC hardware acceleration when available, or multithreaded ultrafast x264.
     */
    public static function compressVideo(string $videoUrl, ?string $audioUrl = null, array $options = []): ?string
    {
        $ffmpeg = self::getFfmpegPath();
        if (!$ffmpeg) {
            return null;
        }

        $crf = (int)($options['crf'] ?? self::getCrf());
        $cacheKey = md5($videoUrl . ($audioUrl ?? '') . '_crf' . $crf);
        $finalFile = self::getCacheDir() . DIRECTORY_SEPARATOR . $cacheKey . '.mp4';
        $tempFile = self::getCacheDir() . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '_' . uniqid() . '.mp4';

        // Check if already in cache (< 48 hours)
        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        // 1. High-speed local temp download (drastically faster than FFmpeg's HTTP client)
        $isRemote = str_starts_with($videoUrl, 'http://') || str_starts_with($videoUrl, 'https://');
        $localVideo = $isRemote ? self::downloadToTemp($videoUrl, 30) : $videoUrl;
        if (!$localVideo || !file_exists($localVideo)) {
            $localVideo = $videoUrl; // fallback
        }

        $localAudio = null;
        if (!empty($audioUrl) && (str_starts_with($audioUrl, 'http://') || str_starts_with($audioUrl, 'https://'))) {
            $localAudio = self::downloadToTemp($audioUrl, 20);
        }

        // 2. Hardware Acceleration or Ultrafast Multithread Codec
        $useNvenc = self::hasNvenc();
        if ($useNvenc) {
            $codecParams = '-c:v h264_nvenc -preset p4 -rc:v vbr -cq ' . $crf . ' -b:v 0 -maxrate 5000k -bufsize 10000k -spatial-aq 1 -temporal-aq 1 -pix_fmt yuv420p';
        } else {
            $codecParams = '-c:v libx264 -preset veryfast -crf ' . $crf . ' -threads 0 -pix_fmt yuv420p';
        }

        $cmd = escapeshellarg($ffmpeg) . ' -y -v error ';
        if ($localVideo === $videoUrl) {
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0';
            $cmd .= '-user_agent ' . escapeshellarg($ua) . ' ';
        }
        $cmd .= '-i ' . escapeshellarg($localVideo) . ' ';

        if (!empty($localAudio)) {
            $cmd .= '-i ' . escapeshellarg($localAudio) . ' ';
            $cmd .= '-map 0:v:0 -map 1:a:0? ' . $codecParams . ' ';
            $cmd .= '-vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:a aac -b:a 128k -ar 44100 -shortest ';
        } else {
            $cmd .= $codecParams . ' ';
            $cmd .= '-vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:a aac -b:a 128k -ar 44100 ';
        }

        $cmd .= '-movflags +faststart ' . escapeshellarg($tempFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            if ($isRemote && $localVideo !== $videoUrl) @unlink($localVideo);
            if ($localAudio) @unlink($localAudio);
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        // Clean local temp downloaded inputs
        if ($isRemote && $localVideo !== $videoUrl) {
            @unlink($localVideo);
        }
        if ($localAudio) {
            @unlink($localAudio);
        }

        if ($exitCode === 0 && file_exists($tempFile) && filesize($tempFile) > 10240) {
            @rename($tempFile, $finalFile);
            return $finalFile;
        }

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        return null;
    }

    /**
     * Compress audio to MP3 128kbps HQ
     */
    public static function compressAudio(string $audioUrl, array $options = []): ?string
    {
        $ffmpeg = self::getFfmpegPath();
        if (!$ffmpeg) {
            return null;
        }

        $bitrate = (string)($options['bitrate'] ?? '128k');
        $cacheKey = md5($audioUrl . '_audio_' . $bitrate);
        $finalFile = self::getCacheDir() . DIRECTORY_SEPARATOR . $cacheKey . '.mp3';
        $tempFile = self::getCacheDir() . DIRECTORY_SEPARATOR . 'tmp_aud_' . $cacheKey . '_' . uniqid() . '.mp3';

        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0';
        $cmd = escapeshellarg($ffmpeg) . ' -y -v error -user_agent ' . escapeshellarg($ua) . ' -i ' . escapeshellarg($audioUrl) . ' ';
        $cmd .= '-vn -c:a libmp3lame -b:a ' . $bitrate . ' -ar 44100 ' . escapeshellarg($tempFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0 && file_exists($tempFile) && filesize($tempFile) > 5000) {
            @rename($tempFile, $finalFile);
            return $finalFile;
        }

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        return null;
    }

    public static function process(string $url, string $type = 'video', ?string $audioUrl = null): ?string
    {
        if ($type === 'audio') {
            return self::compressAudio($url);
        }
        if ($type === 'video') {
            return self::compressVideo($url, $audioUrl);
        }
        return null;
    }

    /**
     * Check if a compressed file already exists in cache
     */
    public static function getCachedFilePath(string $url, string $type = 'video', ?string $audioUrl = null, array $options = []): ?string
    {
        $crf = (int)($options['crf'] ?? self::getCrf());
        $cacheKey = $type === 'audio' 
            ? md5($url . '_audio_' . ($options['bitrate'] ?? '128k'))
            : md5($url . ($audioUrl ?? '') . '_crf' . $crf);
        
        $ext = $type === 'audio' ? '.mp3' : '.mp4';
        $finalFile = self::getCacheDir() . DIRECTORY_SEPARATOR . $cacheKey . $ext;

        if (file_exists($finalFile) && filesize($finalFile) > 5000) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        return null;
    }

    /**
     * Compress media with detailed metrics and instant clean stream shortcut
     */
    public static function compressWithInfo(string $url, string $type = 'video', ?string $audioUrl = null, int $originalBytes = 0): array
    {
        // 1. Check existing cache
        $cached = self::getCachedFilePath($url, $type, $audioUrl);
        if ($cached) {
            $compBytes = filesize($cached);
            $origBytes = $originalBytes > 0 ? $originalBytes : $compBytes;
            $savings = ($origBytes > $compBytes) ? (int)round((1 - ($compBytes / $origBytes)) * 100) : 0;
            return [
                'success' => true,
                'is_cached' => true,
                'compressed_file' => $cached,
                'compressed_size_bytes' => $compBytes,
                'compressed_size_formatted' => round($compBytes / (1024 * 1024), 1) . ' MB',
                'original_size_bytes' => $origBytes,
                'original_size_formatted' => round($origBytes / (1024 * 1024), 1) . ' MB',
                'savings_percent' => $savings
            ];
        }

        // 2. Full High-speed Hardware/Ultrafast Compression
        $compressedFile = self::process($url, $type, $audioUrl);

        if (!$compressedFile || !file_exists($compressedFile) || filesize($compressedFile) < 1024) {
            return [
                'success' => false,
                'error' => 'Gagal mengompresi media. Silakan unduh berkas asli langsung.'
            ];
        }

        $compBytes = filesize($compressedFile);
        $compMb = round($compBytes / (1024 * 1024), 1);
        $compFormatted = $compMb . ' MB';

        $origBytes = $originalBytes;
        if ($origBytes <= 0) {
            $inspect = MediaInspector::inspect($url);
            $origBytes = (int)($inspect['size'] ?? 0);
        }

        $origMb = $origBytes > 0 ? round($origBytes / (1024 * 1024), 1) : 0;
        $origFormatted = $origMb > 0 ? $origMb . ' MB' : 'Original';

        $savingsPercent = 0;
        if ($origBytes > $compBytes) {
            $savingsPercent = (int)round((1 - ($compBytes / $origBytes)) * 100);
        }

        return [
            'success' => true,
            'is_cached' => false,
            'compressed_file' => $compressedFile,
            'compressed_size_bytes' => $compBytes,
            'compressed_size_formatted' => $compFormatted,
            'original_size_bytes' => $origBytes,
            'original_size_formatted' => $origFormatted,
            'savings_percent' => $savingsPercent
        ];
    }

    /**
     * Purge all compressed cache files
     */
    public static function clearCache(): int
    {
        $dir = self::getCacheDir();
        $deleted = 0;

        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $f;
                if (is_file($path)) {
                    if (@unlink($path)) {
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }

    public static function getCacheStats(): array
    {
        $dir = self::getCacheDir();
        $count = 0;
        $totalBytes = 0;

        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $f;
                if (is_file($path)) {
                    $count++;
                    $totalBytes += filesize($path);
                }
            }
        }

        $mb = round($totalBytes / (1024 * 1024), 2);
        return [
            'count' => $count,
            'total_bytes' => $totalBytes,
            'formatted_size' => $mb > 1024 ? round($mb / 1024, 2) . ' GB' : $mb . ' MB'
        ];
    }
}
