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
     * Safely escape command-line arguments on Windows without stripping % characters
     */
    public static function escapeArg(string $arg): string
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return '"' . str_replace('"', '\"', $arg) . '"';
        }
        return escapeshellarg($arg);
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

        $test = @shell_exec(self::escapeArg($ffmpeg) . ' -hide_banner -encoders 2>NUL');
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
    public static function downloadToTemp(string $url, int $timeout = 45, ?string $customUserAgent = null): ?string
    {
        $ext = (str_contains($url, 'mime=audio') || str_contains($url, 'audio')) ? '.tmp' : '.mp4';
        $tmp = self::getCacheDir() . DIRECTORY_SEPARATOR . 'src_' . uniqid() . $ext;
        $fp = fopen($tmp, 'w+');
        if (!$fp) {
            return null;
        }

        $headers = [
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.9',
            'Sec-Fetch-Mode: no-cors'
        ];
        if (str_contains($url, 'googlevideo.com')) {
            $headers[] = 'Referer: https://music.youtube.com/';
            $headers[] = 'Origin: https://music.youtube.com';
        }

        $ua = $customUserAgent ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_BUFFERSIZE => 512 * 1024,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => $ua
        ]);

        $success = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($success && ($code === 200 || $code === 206) && file_exists($tmp) && filesize($tmp) > 1024) {
            return $tmp;
        }

        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        return null;
    }

    /**
     * Transcode YouTube / YouTube Music audio directly to pristine MP3 (320kbps)
     */
    public static function transcodeYtAudio(string $sourceUrl, string $bitrate = '320k'): ?string
    {
        $ffmpeg = self::getFfmpegPath();
        $cacheDir = self::getCacheDir();
        $cacheKey = md5($sourceUrl . '_yt_audio_' . $bitrate);
        $finalFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.mp3';

        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        $tempTemplate = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.%(ext)s';
        $ffmpegArg = $ffmpeg ? ' --ffmpeg-location ' . self::escapeArg($ffmpeg) : '';
        $cmd = 'python -m yt_dlp --js-runtimes node --no-warnings --no-playlist -x --audio-format mp3 --audio-quality 0' 
            . $ffmpegArg 
            . ' -o ' . self::escapeArg($tempTemplate) 
            . ' ' . self::escapeArg($sourceUrl) . ' 2>&1';

        $output = @shell_exec($cmd);
        $tempMp3 = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.mp3';

        if (file_exists($tempMp3) && filesize($tempMp3) > 10240) {
            @rename($tempMp3, $finalFile);
            return $finalFile;
        }

        return null;
    }

    /**
     * Merge YouTube / YouTube Music video and audio streams into single MP4 with audio (1080p, 720p, 360p)
     */
    public static function mergeYtVideo(string $sourceUrl, string $maxHeight = '1080'): ?string
    {
        $ffmpeg = self::getFfmpegPath();
        $cacheDir = self::getCacheDir();
        $cacheKey = md5($sourceUrl . '_yt_video_' . $maxHeight);
        $finalFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.mp4';

        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        $tempTemplate = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.%(ext)s';
        $ffmpegArg = $ffmpeg ? ' --ffmpeg-location ' . self::escapeArg($ffmpeg) : '';
        $formatArg = "bestvideo[height<={$maxHeight}]+bestaudio/best[height<={$maxHeight}]/best";
        $cmd = 'python -m yt_dlp --js-runtimes node --no-warnings --no-playlist -f ' . self::escapeArg($formatArg)
            . ' --merge-output-format mp4 --remux-video mp4'
            . $ffmpegArg
            . ' -o ' . self::escapeArg($tempTemplate)
            . ' ' . self::escapeArg($sourceUrl) . ' 2>&1';

        $output = @shell_exec($cmd);
        $tempMp4 = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.mp4';

        if (file_exists($tempMp4) && filesize($tempMp4) > 10240) {
            @rename($tempMp4, $finalFile);
            return $finalFile;
        }

        // Check if any matching tmp file was created
        $matches = glob($cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.*');
        if (!empty($matches)) {
            foreach ($matches as $match) {
                if (file_exists($match) && filesize($match) > 10240) {
                    if (str_ends_with(strtolower($match), '.mp4')) {
                        @rename($match, $finalFile);
                        return $finalFile;
                    }
                    if ($ffmpeg) {
                        $remuxCmd = self::escapeArg($ffmpeg) . ' -y -i ' . self::escapeArg($match) . ' -c copy -movflags +faststart ' . self::escapeArg($finalFile);
                        @shell_exec($remuxCmd);
                        @unlink($match);
                        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
                            return $finalFile;
                        }
                    }
                }
            }
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
        $height = (int)($options['height'] ?? 0);
        $cacheKey = md5($videoUrl . ($audioUrl ?? '') . '_crf' . $crf . ($height ? '_h' . $height : ''));
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
        $localVideo = $isRemote ? self::downloadToTemp($videoUrl, 35) : $videoUrl;
        if (!$localVideo || !file_exists($localVideo)) {
            $localVideo = $videoUrl; // fallback
        }

        $localAudio = null;
        if (!empty($audioUrl) && (str_starts_with($audioUrl, 'http://') || str_starts_with($audioUrl, 'https://'))) {
            $localAudio = self::downloadToTemp($audioUrl, 25);
        }

        // 2. Hardware Acceleration or Ultrafast Multithread Codec
        $useNvenc = self::hasNvenc();
        if ($useNvenc) {
            $codecParams = '-c:v h264_nvenc -preset p4 -rc:v vbr -cq ' . $crf . ' -b:v 0 -maxrate 5000k -bufsize 10000k -spatial-aq 1 -temporal-aq 1 -pix_fmt yuv420p';
        } else {
            $codecParams = '-c:v libx264 -preset veryfast -crf ' . $crf . ' -threads 0 -pix_fmt yuv420p';
        }

        $scaleFilter = ($height > 0)
            ? 'scale=\'if(gt(ih,iw),min(iw,' . $height . '),-2)\':\'if(gt(ih,iw),-2,min(ih,' . $height . '))\',scale=trunc(iw/2)*2:trunc(ih/2)*2'
            : 'scale=trunc(iw/2)*2:trunc(ih/2)*2';

        $cmd = self::escapeArg($ffmpeg) . ' -y -v error ';
        if ($localVideo === $videoUrl) {
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
            $cmd .= '-user_agent ' . self::escapeArg($ua) . ' ';
        }
        $cmd .= '-i ' . self::escapeArg($localVideo) . ' ';

        if (!empty($localAudio)) {
            $cmd .= '-i ' . self::escapeArg($localAudio) . ' ';
            $cmd .= '-map 0:v:0 -map 1:a:0? ' . $codecParams . ' ';
            $cmd .= '-vf "' . $scaleFilter . '" -c:a aac -b:a 128k -ar 44100 -shortest ';
        } else {
            $cmd .= $codecParams . ' ';
            $cmd .= '-vf "' . $scaleFilter . '" -c:a aac -b:a 128k -ar 44100 ';
        }

        $cmd .= '-movflags +faststart ' . self::escapeArg($tempFile);

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
     * Compress audio to MP3 HQ
     */
    public static function compressAudio(string $audioUrl, array $options = []): ?string
    {
        $ffmpeg = self::getFfmpegPath();
        if (!$ffmpeg) {
            return null;
        }

        $bitrate = (string)($options['bitrate'] ?? '320k');
        $cacheKey = md5($audioUrl . '_audio_' . $bitrate);
        $finalFile = self::getCacheDir() . DIRECTORY_SEPARATOR . $cacheKey . '.mp3';
        $tempFile = self::getCacheDir() . DIRECTORY_SEPARATOR . 'tmp_aud_' . $cacheKey . '_' . uniqid() . '.mp3';

        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        $isRemote = str_starts_with($audioUrl, 'http://') || str_starts_with($audioUrl, 'https://');
        $localAudio = $isRemote ? self::downloadToTemp($audioUrl, 25) : $audioUrl;
        if (!$localAudio || !file_exists($localAudio)) {
            $localAudio = $audioUrl;
        }

        $cmd = self::escapeArg($ffmpeg) . ' -y -v error ';
        if ($localAudio === $audioUrl) {
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
            $cmd .= '-user_agent ' . self::escapeArg($ua) . ' ';
        }
        $cmd .= '-i ' . self::escapeArg($localAudio) . ' ';
        $cmd .= '-vn -c:a libmp3lame -b:a ' . $bitrate . ' -ar 44100 ' . self::escapeArg($tempFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            if ($isRemote && $localAudio !== $audioUrl) @unlink($localAudio);
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($isRemote && $localAudio !== $audioUrl) {
            @unlink($localAudio);
        }

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
        $height = (int)($options['height'] ?? 0);
        $cacheKey = $type === 'audio' 
            ? md5($url . '_audio_' . ($options['bitrate'] ?? '128k'))
            : md5($url . ($audioUrl ?? '') . '_crf' . $crf . ($height ? '_h' . $height : ''));
        
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
