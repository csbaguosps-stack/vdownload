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
    private static ?string $cachedDenoPath = null;
    private static ?string $cachedAria2Path = null;

    /**
     * Check if running on Windows OS
     */
    public static function isWindows(): bool
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    /**
     * Check if shell_exec is available and not disabled in php.ini
     */
    public static function isShellExecAvailable(): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }
        $disabled = (string)ini_get('disable_functions');
        if (!empty($disabled)) {
            $list = array_map('trim', explode(',', $disabled));
            if (in_array('shell_exec', $list, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Locate Python binary on the system (python3 on Linux, python on Windows)
     */
    public static function getPythonCommand(): string
    {
        if (self::isWindows()) {
            $candidates = [
                'C:\\Python314\\python.exe',
                'C:\\Python313\\python.exe',
                'C:\\Python312\\python.exe',
                'C:\\Python311\\python.exe',
                'C:\\Python310\\python.exe'
            ];
            foreach ($candidates as $cand) {
                if (file_exists($cand) && is_executable($cand)) {
                    return self::escapeArg($cand);
                }
            }
            return 'python';
        }

        if (self::isShellExecAvailable()) {
            $py3 = @shell_exec('which python3 2>/dev/null');
            if (!empty(trim((string)$py3))) {
                return 'python3';
            }
            $py = @shell_exec('which python 2>/dev/null');
            if (!empty(trim((string)$py))) {
                return 'python';
            }
        }

        return 'python3';
    }

    /**
     * Locate FFmpeg binary on the system (Windows & Linux hosting compatible)
     */
    public static function getFfmpegPath(): ?string
    {
        if (self::$cachedFfmpegPath !== null) {
            return self::$cachedFfmpegPath;
        }

        $appRoot = Config::getAppRoot();
        $isWin = self::isWindows();

        if ($isWin) {
            $candidates = [
                $appRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe',
                'C:\\Users\\Baguosps\\AppData\\Roaming\\Python\\Python314\\site-packages\\imageio_ffmpeg\\binaries\\ffmpeg-win-x86_64-v7.1.exe',
                'C:\\ffmpeg\\bin\\ffmpeg.exe',
                'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
                'C:\\xampp\\ffmpeg\\ffmpeg.exe'
            ];
        } else {
            $candidates = [
                $appRoot . '/bin/ffmpeg',
                '/usr/bin/ffmpeg',
                '/usr/local/bin/ffmpeg',
                '/bin/ffmpeg',
                '/snap/bin/ffmpeg',
                '/opt/ffmpeg/bin/ffmpeg',
                '/usr/local/share/ffmpeg/ffmpeg'
            ];
            $home = getenv('HOME');
            if (!empty($home)) {
                $candidates[] = rtrim($home, '/') . '/bin/ffmpeg';
            }
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $candidates[] = dirname($_SERVER['DOCUMENT_ROOT']) . '/bin/ffmpeg';
            }
        }

        foreach ($candidates as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                self::$cachedFfmpegPath = $path;
                return $path;
            }
        }

        if (self::isShellExecAvailable()) {
            $cmd = $isWin ? 'where.exe ffmpeg 2>NUL' : 'which ffmpeg 2>/dev/null || command -v ffmpeg 2>/dev/null';
            $pathOutput = @shell_exec($cmd);
            if (!empty($pathOutput)) {
                $lines = explode("\n", trim($pathOutput));
                $found = trim($lines[0]);
                if (!empty($found) && file_exists($found)) {
                    self::$cachedFfmpegPath = $found;
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Locate Deno JavaScript runtime engine on the system
     */
    public static function getDenoPath(): ?string
    {
        if (self::$cachedDenoPath !== null) {
            return self::$cachedDenoPath;
        }

        $appRoot = Config::getAppRoot();
        $isWin = self::isWindows();

        if ($isWin) {
            $candidates = [
                $appRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'deno.exe',
                'C:\\Users\\Baguosps\\.deno\\bin\\deno.exe',
                'C:\\Program Files\\deno\\deno.exe',
                'C:\\deno\\deno.exe'
            ];
        } else {
            $candidates = [
                $appRoot . '/bin/deno',
                '/usr/bin/deno',
                '/usr/local/bin/deno',
                '/root/.deno/bin/deno'
            ];
            $home = getenv('HOME');
            if (!empty($home)) {
                $candidates[] = rtrim($home, '/') . '/.deno/bin/deno';
            }
        }

        foreach ($candidates as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                self::$cachedDenoPath = $path;
                return $path;
            }
        }

        if (self::isShellExecAvailable()) {
            $cmd = $isWin ? 'where.exe deno 2>NUL' : 'which deno 2>/dev/null || command -v deno 2>/dev/null';
            $pathOutput = @shell_exec($cmd);
            if (!empty($pathOutput)) {
                $lines = explode("\n", trim($pathOutput));
                $found = trim($lines[0]);
                if (!empty($found) && file_exists($found)) {
                    self::$cachedDenoPath = $found;
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Locate aria2c multi-connection accelerator engine on the system
     */
    public static function getAria2Path(): ?string
    {
        if (self::$cachedAria2Path !== null) {
            return self::$cachedAria2Path;
        }

        $appRoot = Config::getAppRoot();
        $isWin = self::isWindows();

        if ($isWin) {
            $candidates = [
                $appRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'aria2c.exe',
                'C:\\Users\\Baguosps\\AppData\\Local\\Microsoft\\WinGet\\Packages\\aria2.aria2_Microsoft.Winget.Source_8wekyb3d8bbwe\\aria2-1.37.0-win-64bit-build1\\aria2c.exe',
                'C:\\Program Files\\aria2\\aria2c.exe',
                'C:\\aria2\\aria2c.exe'
            ];
        } else {
            $candidates = [
                $appRoot . '/bin/aria2c',
                '/usr/bin/aria2c',
                '/usr/local/bin/aria2c',
                '/bin/aria2c'
            ];
        }

        foreach ($candidates as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                self::$cachedAria2Path = $path;
                return $path;
            }
        }

        if (self::isShellExecAvailable()) {
            $cmd = $isWin ? 'where.exe aria2c 2>NUL' : 'which aria2c 2>/dev/null || command -v aria2c 2>/dev/null';
            $pathOutput = @shell_exec($cmd);
            if (!empty($pathOutput)) {
                $lines = explode("\n", trim($pathOutput));
                $found = trim($lines[0]);
                if (!empty($found) && file_exists($found)) {
                    self::$cachedAria2Path = $found;
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Build base yt-dlp command with Deno JS engine, FFmpeg path, multi-fragment buffers, and SABR bypass
     */
    public static function getYtDlpBaseCommand(): string
    {
        $deno = self::getDenoPath();
        $ffmpeg = self::getFfmpegPath();
        $python = self::getPythonCommand();

        $cmd = $python . ' -m yt_dlp --no-warnings --no-update --no-playlist';

        if ($deno) {
            $cmd .= ' --js-runtimes ' . self::escapeArg('deno:' . $deno);
        } else {
            $cmd .= ' --js-runtimes node';
        }

        if ($ffmpeg) {
            $cmd .= ' --ffmpeg-location ' . self::escapeArg($ffmpeg);
        }

        // Acceleration flags: 5 concurrent fragments, 10MB chunk size, 1MB buffer, no check certs
        $cmd .= ' --concurrent-fragments 5 --http-chunk-size 10M --buffer-size 1024k --no-check-certificates';

        return $cmd;
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
        if (!$ffmpeg || !self::isShellExecAvailable()) {
            self::$hasNvencCache = false;
            return false;
        }

        $nullRedirect = self::isWindows() ? '2>NUL' : '2>/dev/null';
        $test = @shell_exec(self::escapeArg($ffmpeg) . ' -hide_banner -encoders ' . $nullRedirect);
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
    public static function downloadToTemp(string $url, int $timeout = 180, ?string $customUserAgent = null): ?string
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
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_BUFFERSIZE => 1024 * 1024,
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
    public static function transcodeYtAudio(string $sourceUrl, string $bitrate = '320k', ?string $directAudioUrl = null): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('max_execution_time', '0');

        $cacheDir = self::getCacheDir();
        $cacheKey = md5($sourceUrl . '_yt_audio_' . $bitrate);
        $finalFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.mp3';

        // 1. Check existing cache (< 48 hours)
        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        // 2. ULTRA FAST PATH: If direct audio URL is provided and valid, download & convert directly with FFmpeg
        if (!empty($directAudioUrl) && filter_var($directAudioUrl, FILTER_VALIDATE_URL)) {
            $directConverted = self::compressAudio($directAudioUrl, ['bitrate' => $bitrate]);
            if ($directConverted && file_exists($directConverted) && filesize($directConverted) > 10240) {
                @copy($directConverted, $finalFile);
                return $finalFile;
            }
        }

        // 3. TURBO YT-DLP PIPELINE (Multi-Fragment + Deno + Threads 0)
        $tempTemplate = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.%(ext)s';
        $baseCmd = self::getYtDlpBaseCommand();
        $cmd = $baseCmd . ' -x --audio-format mp3 --audio-quality ' . self::escapeArg($bitrate)
            . ' --postprocessor-args ' . self::escapeArg('ffmpeg:-threads 0')
            . ' -o ' . self::escapeArg($tempTemplate)
            . ' ' . self::escapeArg($sourceUrl) . ' 2>&1';

        $output = @shell_exec($cmd);
        $tempMp3 = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.mp3';

        if (file_exists($tempMp3) && filesize($tempMp3) > 10240) {
            @rename($tempMp3, $finalFile);
            return $finalFile;
        }

        // Check if any matching tmp file was created
        $matches = glob($cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.*');
        if (!empty($matches)) {
            foreach ($matches as $match) {
                if (file_exists($match) && filesize($match) > 10240) {
                    if (str_ends_with(strtolower($match), '.mp3')) {
                        @rename($match, $finalFile);
                        return $finalFile;
                    }
                    $ffmpeg = self::getFfmpegPath();
                    if ($ffmpeg) {
                        $remuxCmd = self::escapeArg($ffmpeg) . ' -y -i ' . self::escapeArg($match) . ' -vn -c:a libmp3lame -b:a ' . self::escapeArg($bitrate) . ' -threads 0 ' . self::escapeArg($finalFile);
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
     * Ultra-fast direct stream merge of separate video and audio URLs using FFmpeg stream copy (takes ~1-2 seconds!)
     */
    public static function fastMergeVideoAudio(string $videoUrl, string $audioUrl, string $cacheKey = ''): ?string
    {
        if (empty($videoUrl) || empty($audioUrl)) {
            return null;
        }

        $ffmpeg = self::getFfmpegPath();
        if (!$ffmpeg) {
            return null;
        }

        $cacheDir = self::getCacheDir();
        if (empty($cacheKey)) {
            $cacheKey = md5($videoUrl . '_' . $audioUrl);
        }
        $finalFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.mp4';

        if (file_exists($finalFile) && filesize($finalFile) > 10240) {
            if (time() - filemtime($finalFile) < 172800) {
                return $finalFile;
            }
        }

        $tempFile = $cacheDir . DIRECTORY_SEPARATOR . 'tmp_' . $cacheKey . '.mp4';
        @unlink($tempFile);

        // Attempt 1: Ultra-fast direct stream copy (no re-encoding, ~1-2 seconds)
        $cmd1 = self::escapeArg($ffmpeg)
            . ' -y -user_agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -referer "https://www.youtube.com/"'
            . ' -i ' . self::escapeArg($videoUrl)
            . ' -user_agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -referer "https://www.youtube.com/"'
            . ' -i ' . self::escapeArg($audioUrl)
            . ' -c copy -movflags +faststart '
            . self::escapeArg($tempFile) . ' 2>&1';

        @shell_exec($cmd1);

        if (file_exists($tempFile) && filesize($tempFile) > 10240) {
            @rename($tempFile, $finalFile);
            return $finalFile;
        }

        // Attempt 2: Audio transcode to AAC if video and audio container formats were incompatible
        @unlink($tempFile);
        $cmd2 = self::escapeArg($ffmpeg)
            . ' -y -user_agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -referer "https://www.youtube.com/"'
            . ' -i ' . self::escapeArg($videoUrl)
            . ' -user_agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -referer "https://www.youtube.com/"'
            . ' -i ' . self::escapeArg($audioUrl)
            . ' -c:v copy -c:a aac -b:a 192k -movflags +faststart '
            . self::escapeArg($tempFile) . ' 2>&1';

        @shell_exec($cmd2);

        if (file_exists($tempFile) && filesize($tempFile) > 10240) {
            @rename($tempFile, $finalFile);
            return $finalFile;
        }

        return null;
    }

    /**
     * Merge YouTube / YouTube Music video and audio streams into single MP4 with audio (1080p, 720p, 360p)
     */
    public static function mergeYtVideo(string $sourceUrl, string $maxHeight = '1080'): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('max_execution_time', '0');

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
        $formatArg = "bestvideo[width<={$maxHeight}][height>{$maxHeight}][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<={$maxHeight}][ext=mp4]+bestaudio[ext=m4a]/bestvideo[width<={$maxHeight}][height>{$maxHeight}]+bestaudio/bestvideo[height<={$maxHeight}]+bestaudio/best";
        $baseCmd = self::getYtDlpBaseCommand();

        $aria2 = self::getAria2Path();
        $aria2Arg = '';
        if ($aria2 && file_exists($aria2)) {
            $aria2Arg = ' --downloader ' . self::escapeArg($aria2) . ' --downloader-args "aria2c:-c -j 8 -x 8 -s 8 -k 1M --allow-overwrite=true"';
        }

        $cmd = $baseCmd . $aria2Arg . ' -f ' . self::escapeArg($formatArg)
            . ' --merge-output-format mp4 --remux-video mp4'
            . ' --postprocessor-args ' . self::escapeArg('ffmpeg:-threads 0')
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
     * Otomatis bersihkan berkas-berkas sementara di server agar media tidak tersimpan permanen
     */
    public static function pruneOldFiles(int $maxAgeSeconds = 300): int
    {
        $dir = self::getCacheDir();
        $deleted = 0;
        $now = time();

        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $f;
                if (is_file($path)) {
                    $mtime = @filemtime($path);
                    if ($mtime && ($now - $mtime) > $maxAgeSeconds) {
                        if (@unlink($path)) {
                            $deleted++;
                        }
                    }
                }
            }
        }

        return $deleted;
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
