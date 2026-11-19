<?php
/**
 * VDownload Pro - YouTube Video & Audio Downloader
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Models;

use App\Core\DownloaderInterface;

class YouTubeDownloader implements DownloaderInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return str_contains($host, 'youtube.com') 
            || str_contains($host, 'youtu.be')
            || str_contains($host, 'music.youtube.com')
            || str_contains($host, 'm.youtube.com');
    }

    public function extract(string $url): array
    {
        // Normalize YouTube URL: convert Shorts & youtu.be to canonical watch URL for maximum compatibility
        $canonicalUrl = $url;
        if (preg_match('/(?:shorts\/|v\/|watch\?v=|\/embed\/|youtu\.be\/)([a-zA-Z0-9_\-]{11})/', $url, $m)) {
            if (!str_contains($url, 'music.youtube.com')) {
                $canonicalUrl = 'https://www.youtube.com/watch?v=' . $m[1];
            }
        }

        $cacheDir = \App\Config\Config::getStoragePath('cache' . DIRECTORY_SEPARATOR . 'yt_meta');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($canonicalUrl) . '.json';
        $data = null;
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
            $cachedContent = @file_get_contents($cacheFile);
            if ($cachedContent) {
                $decoded = json_decode($cachedContent, true);
                if (!empty($decoded) && isset($decoded['id'])) {
                    $data = $decoded;
                }
            }
        }

        if (!$data) {
            $safeUrl = MediaCompressor::escapeArg($canonicalUrl);
            $baseCmd = MediaCompressor::getYtDlpBaseCommand();
            $command = "{$baseCmd} --skip-download --dump-single-json {$safeUrl}";
            
            $stdout = '';
            $stderr = '';
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'], // stdout: clean JSON
                2 => ['pipe', 'w']  // stderr: logs & error diagnostic
            ];

            $proc = @proc_open($command, $descriptors, $pipes);
            if (is_resource($proc)) {
                @fclose($pipes[0]);
                $stdout = (string)@stream_get_contents($pipes[1]);
                $stderr = (string)@stream_get_contents($pipes[2]);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                @proc_close($proc);
            } else {
                // Fallback to shell_exec if proc_open is restricted
                $combinedOutput = (string)@shell_exec($command . ' 2>&1');
                $stdout = $combinedOutput;
                $stderr = $combinedOutput;
            }

            // First attempt: direct json_decode on clean stdout
            $stdoutTrim = trim($stdout);
            if (!empty($stdoutTrim) && str_starts_with($stdoutTrim, '{') && str_ends_with($stdoutTrim, '}')) {
                $data = json_decode($stdoutTrim, true);
            }

            // Second attempt: extract JSON substring if extra output was captured
            if (!$data || !isset($data['id'])) {
                $jsonStart = strpos($stdout, '{');
                $jsonEnd = strrpos($stdout, '}');
                if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                    $cleanJson = substr($stdout, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $data = json_decode($cleanJson, true);
                }
            }

            if (!$data || !isset($data['id'])) {
                $fullLog = $stderr . "\n" . $stdout;
                $errorMsg = 'Video YouTube / Shorts tidak dapat dianalisis. Pastikan tautan valid dan video bersifat publik.';

                if (str_contains($fullLog, 'Private video')) {
                    $errorMsg = 'Video/lagu YouTube berstatus privat.';
                } elseif (str_contains($fullLog, 'Video unavailable') || str_contains($fullLog, 'This video is not available')) {
                    $errorMsg = 'Video YouTube tidak tersedia atau telah dihapus oleh pengunggah.';
                } elseif (str_contains($fullLog, 'Too Many Requests') || str_contains($fullLog, 'HTTP Error 429') || str_contains($fullLog, '429')) {
                    $errorMsg = 'Server YouTube membatasi permintaan sementara (Rate Limit / 429). Silakan tunggu 10 detik dan coba kembali.';
                } elseif (str_contains($fullLog, 'Sign in to confirm you’re not a bot') || str_contains($fullLog, 'Sign in to confirm you\'re not a bot')) {
                    $errorMsg = 'YouTube meminta verifikasi bot/anti-spam. Silakan coba kembali beberapa saat lagi.';
                } elseif (str_contains($fullLog, 'age-restricted') || str_contains($fullLog, 'Sign in to confirm your age')) {
                    $errorMsg = 'Video YouTube ini memiliki batasan usia (age-restricted).';
                } elseif (str_contains($fullLog, 'Join this channel') || str_contains($fullLog, 'members-only')) {
                    $errorMsg = 'Video YouTube ini khusus member channel (berbayar).';
                } elseif (preg_match('/ERROR:\s*(?:\[youtube\]\s*)?([^\r\n]+)/i', $fullLog, $errMatches)) {
                    $errorMsg = 'YouTube: ' . trim($errMatches[1]);
                }
                throw new \RuntimeException($errorMsg);
            }

            @file_put_contents($cacheFile, json_encode($data));
        }

        $id = (string)$data['id'];
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $isMusic = str_contains($host, 'music.youtube.com') 
            || !empty($data['track']) 
            || !empty($data['artist']) 
            || !empty($data['album']);

        $title = !empty($data['track']) 
            ? $data['track'] 
            : (!empty($data['title']) ? $data['title'] : ($isMusic ? 'YouTube Music Track' : 'YouTube Video'));

        $author = !empty($data['artist']) 
            ? $data['artist'] 
            : (!empty($data['uploader']) 
                ? $data['uploader'] 
                : (!empty($data['channel']) ? $data['channel'] : ($isMusic ? 'Music Artist' : 'YouTube Creator')));

        $album = !empty($data['album']) ? $data['album'] : '';
        $cleanAuthor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $author);
        $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $id);

        // Format duration
        $durationSec = (int)($data['duration'] ?? 0);
        if ($durationSec > 0) {
            $h = floor($durationSec / 3600);
            $m = floor(($durationSec % 3600) / 60);
            $s = $durationSec % 60;
            $durationStr = $h > 0 
                ? sprintf('%02d:%02d:%02d', $h, $m, $s) 
                : sprintf('%02d:%02d', $m, $s);
        } else {
            $durationStr = '00:00';
        }

        // Best Thumbnail (Find highest resolution artwork, especially for YT Music album covers)
        $thumbnail = "https://i.ytimg.com/vi/{$id}/maxresdefault.jpg";
        if (!empty($data['thumbnails']) && is_array($data['thumbnails'])) {
            $bestThumb = null;
            $maxArea = 0;
            foreach ($data['thumbnails'] as $t) {
                $w = (int)($t['width'] ?? 0);
                $h = (int)($t['height'] ?? 0);
                $area = $w * $h;
                if ($area > $maxArea && !empty($t['url'])) {
                    $maxArea = $area;
                    $bestThumb = $t['url'];
                }
            }
            if ($bestThumb) {
                $thumbnail = $bestThumb;
            }
        } elseif (!empty($data['thumbnail'])) {
            $thumbnail = $data['thumbnail'];
        }

        $formats = $data['formats'] ?? [];
        $downloads = [];

        // 1. Separate Progressive (Video+Audio combined) vs DASH Streams
        $prog720 = null;
        $prog360 = null;
        $dash1080 = null;
        $dash720 = null;
        $dash360 = null;
        $bestAudio = null;
        $bestM4a = null;

        foreach ($formats as $f) {
            $vcodec = $f['vcodec'] ?? 'none';
            $acodec = $f['acodec'] ?? 'none';
            $height = (int)($f['height'] ?? 0);
            $width = (int)($f['width'] ?? 0);
            $effectiveH = ($height > 0) ? $height : (int)round($width * 9 / 16);
            $ext = $f['ext'] ?? 'mp4';
            $urlStream = $f['url'] ?? '';

            if (empty($urlStream)) continue;

            $hasVideo = ($vcodec !== 'none');
            $hasAudio = ($acodec !== 'none');
            $isAvc = str_contains(strtolower($vcodec), 'avc') || str_contains(strtolower($vcodec), 'h264');

            // Progressive: both audio and video in single stream (INSTANT DIRECT DOWNLOAD - NO MERGE!)
            if ($hasVideo && $hasAudio) {
                if ($effectiveH >= 720) {
                    if (!$prog720 || $effectiveH > ($prog720['height'] ?? 0)) {
                        $prog720 = $f;
                    }
                } elseif ($effectiveH >= 360) {
                    if (!$prog360 || $effectiveH > ($prog360['height'] ?? 0)) {
                        $prog360 = $f;
                    }
                }
            }

            // DASH Video-only stream
            if ($hasVideo && !$hasAudio) {
                if ($effectiveH >= 1080) {
                    if (!$dash1080 || ($ext === 'mp4' && $isAvc) || (($dash1080['ext'] ?? '') !== 'mp4')) {
                        $dash1080 = $f;
                    }
                } elseif ($effectiveH >= 720 && $effectiveH < 1080) {
                    if (!$dash720 || ($ext === 'mp4' && $isAvc) || (($dash720['ext'] ?? '') !== 'mp4')) {
                        $dash720 = $f;
                    }
                } elseif ($effectiveH >= 360 && $effectiveH < 720) {
                    if (!$dash360 || ($ext === 'mp4' && $isAvc) || (($dash360['ext'] ?? '') !== 'mp4')) {
                        $dash360 = $f;
                    }
                }
            }

            // Audio only stream
            if (!$hasVideo && $hasAudio) {
                $abr = (float)($f['abr'] ?? 0);
                if (!$bestAudio || $abr > (float)($bestAudio['abr'] ?? 0)) {
                    $bestAudio = $f;
                }
                if ($ext === 'm4a' || str_contains($acodec, 'mp4a')) {
                    if (!$bestM4a || $abr > (float)($bestM4a['abr'] ?? 0)) {
                        $bestM4a = $f;
                    }
                }
            }
        }

        // Fallbacks
        $bestProgressive = $prog720 ?: $prog360;
        if (!$bestAudio && $bestProgressive) {
            $bestAudio = $bestProgressive;
        }
        if (!$bestM4a && $bestAudio) {
            $bestM4a = $bestAudio;
        }

        $f1080 = $dash1080;
        $f720 = $prog720 ?: $dash720;
        $f360 = $prog360 ?: $dash360;

        $filePrefix = $isMusic ? 'ytmusic' : 'youtube';

        // 2. Build tailored downloads list
        if ($isMusic) {
            // === YOUTUBE MUSIC DOWNLOAD OPTIONS ===
            // Primary 1: HQ 320kbps MP3 Audio (Studio Quality, Auto-Transcoded / Compressed)
            if ($bestAudio) {
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Musik MP3 (Kualitas Tinggi 320k Studio)',
                    'quality' => '⭐ MP3 320k HQ',
                    'icon' => 'bi-music-note-beamed',
                    'btn_class' => 'btn-youtube-music',
                    'url' => $bestAudio['url'],
                    'source_url' => $url,
                    'needs_merge' => true,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_320k.mp3"
                ];
            }

            // Primary 2: Original M4A / AAC (Direct Playable on iOS/Android/Windows - Instant Download)
            if ($bestM4a) {
                $szM4a = (int)($bestM4a['filesize'] ?? ($bestM4a['filesize_approx'] ?? 0));
                $szM4aFormatted = $szM4a > 0 ? round($szM4a / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Format M4A / AAC (Format Asli Tanpa Konversi - Super Cepat)',
                    'quality' => 'M4A Original' . ($szM4aFormatted ? " • {$szM4aFormatted}" : ''),
                    'icon' => 'bi-file-earmark-music-fill',
                    'btn_class' => 'btn-download-audio',
                    'url' => $bestM4a['url'],
                    'source_url' => null,
                    'needs_merge' => false,
                    'size_bytes' => $szM4a,
                    'size_formatted' => $szM4aFormatted,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}.m4a"
                ];
            }

            // Primary 3: Full HD 1080p Music Video with Audio
            $target1080 = $f1080 ?: ($f720 ?: $bestProgressive);
            if ($target1080) {
                $sz1080 = (int)($target1080['filesize'] ?? ($target1080['filesize_approx'] ?? 0));
                $sz1080Formatted = $sz1080 > 0 ? round($sz1080 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Musik Full HD 1080p (MP4 Jernih + Audio)',
                    'quality' => '⭐ 1080p Full HD' . ($sz1080Formatted ? " • {$sz1080Formatted}" : ''),
                    'icon' => 'bi-badge-hd-fill',
                    'btn_class' => 'btn-youtube-hd',
                    'url' => $target1080['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'needs_merge' => true,
                    'size_bytes' => $sz1080,
                    'size_formatted' => $sz1080Formatted,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_1080p.mp4"
                ];
            }

            // Primary 4: HD 720p Music Video
            $target720 = $bestProgressive ?: ($f720 ?: ($f1080 ?: $f360));
            if ($target720) {
                $sz720 = (int)($target720['filesize'] ?? ($target720['filesize_approx'] ?? 0));
                $sz720Formatted = $sz720 > 0 ? round($sz720 / (1024 * 1024), 1) . ' MB' : '';
                $isProg720 = ($target720 === $bestProgressive);
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Musik HD 720p (MP4 Cerdas + Audio)',
                    'quality' => 'HD 720p' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                    'icon' => 'bi-play-circle-fill',
                    'btn_class' => 'btn-youtube',
                    'url' => $target720['url'],
                    'audio_url' => $isProg720 ? null : ($bestAudio ? $bestAudio['url'] : null),
                    'source_url' => $isProg720 ? null : $url,
                    'needs_merge' => !$isProg720,
                    'size_bytes' => $sz720,
                    'size_formatted' => $sz720Formatted,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_720p.mp4"
                ];
            }

            // Primary 5: SD 360p Music Video (Hemat Kuota)
            $target360 = $f360 ?: ($bestProgressive ?: ($f720 ?: $f1080));
            if ($target360) {
                $sz360 = (int)($target360['filesize'] ?? ($target360['filesize_approx'] ?? 0));
                $sz360Formatted = $sz360 > 0 ? round($sz360 / (1024 * 1024), 1) . ' MB' : '';
                $isProg360 = ($target360 === $bestProgressive || (isset($target360['acodec']) && $target360['acodec'] !== 'none'));
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Musik SD 360p (MP4 Hemat Kuota + Audio)',
                    'quality' => 'SD 360p (Hemat Kuota)' . ($sz360Formatted ? " • {$sz360Formatted}" : ''),
                    'icon' => 'bi-lightning-charge-fill',
                    'btn_class' => 'btn-download-sd',
                    'url' => $target360['url'],
                    'audio_url' => $isProg360 ? null : ($bestAudio ? $bestAudio['url'] : null),
                    'source_url' => $isProg360 ? null : $url,
                    'needs_merge' => !$isProg360,
                    'size_bytes' => $sz360,
                    'size_formatted' => $sz360Formatted,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_360p.mp4"
                ];
            }

            // Primary 6: Album Art / Cover HD
            if (!empty($thumbnail)) {
                $downloads[] = [
                    'type' => 'image',
                    'label' => 'Unduh Cover Album HD (Artwork Resolusi Penuh)',
                    'quality' => 'HD Artwork',
                    'icon' => 'bi-image',
                    'btn_class' => 'btn-download-cover',
                    'url' => $thumbnail,
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_cover.jpg"
                ];
            }
        } else {
            // === REGULAR YOUTUBE VIDEO DOWNLOAD OPTIONS ===
            // Option 1: Full HD 1080p
            if ($dash1080) {
                $sz1080 = (int)($dash1080['filesize'] ?? ($dash1080['filesize_approx'] ?? 0));
                $sz1080Formatted = $sz1080 > 0 ? round($sz1080 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Full HD 1080p (MP4 Jernih + Audio)',
                    'quality' => '⭐ 1080p Full HD' . ($sz1080Formatted ? " • {$sz1080Formatted}" : ''),
                    'icon' => 'bi-badge-hd-fill',
                    'btn_class' => 'btn-youtube-hd',
                    'url' => $dash1080['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'needs_merge' => true,
                    'size_bytes' => $sz1080,
                    'size_formatted' => $sz1080Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_1080p.mp4"
                ];
            }

            // Option 2: HD 720p
            if ($prog720) {
                $sz720 = (int)($prog720['filesize'] ?? ($prog720['filesize_approx'] ?? 0));
                $sz720Formatted = $sz720 > 0 ? round($sz720 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video HD 720p (MP4 Cerdas Langsung + Audio)',
                    'quality' => '⭐ 720p HD (Instan)' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                    'icon' => 'bi-play-circle-fill',
                    'btn_class' => 'btn-youtube',
                    'url' => $prog720['url'],
                    'audio_url' => null,
                    'source_url' => null,
                    'needs_merge' => false,
                    'size_bytes' => $sz720,
                    'size_formatted' => $sz720Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_720p.mp4"
                ];
            } elseif ($dash720) {
                $sz720 = (int)($dash720['filesize'] ?? ($dash720['filesize_approx'] ?? 0));
                $sz720Formatted = $sz720 > 0 ? round($sz720 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video HD 720p (MP4 Cerdas + Audio)',
                    'quality' => 'HD 720p' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                    'icon' => 'bi-play-circle-fill',
                    'btn_class' => 'btn-youtube',
                    'url' => $dash720['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'needs_merge' => true,
                    'size_bytes' => $sz720,
                    'size_formatted' => $sz720Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_720p.mp4"
                ];
            }

            // Option 3: Standard SD 360p (Format 18 progressive: 100% direct instant stream!)
            if ($prog360) {
                $sz360 = (int)($prog360['filesize'] ?? ($prog360['filesize_approx'] ?? 0));
                $sz360Formatted = $sz360 > 0 ? round($sz360 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video SD 360p (MP4 Hemat Kuota - Unduh Cepat Langsung)',
                    'quality' => '⚡ SD 360p (Instan)' . ($sz360Formatted ? " • {$sz360Formatted}" : ''),
                    'icon' => 'bi-lightning-charge-fill',
                    'btn_class' => 'btn-download-sd',
                    'url' => $prog360['url'],
                    'audio_url' => null,
                    'source_url' => null,
                    'needs_merge' => false,
                    'size_bytes' => $sz360,
                    'size_formatted' => $sz360Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_360p.mp4"
                ];
            } elseif ($dash360) {
                $sz360 = (int)($dash360['filesize'] ?? ($dash360['filesize_approx'] ?? 0));
                $sz360Formatted = $sz360 > 0 ? round($sz360 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video SD 360p (MP4 Hemat Kuota + Audio)',
                    'quality' => 'SD 360p (Hemat Kuota)' . ($sz360Formatted ? " • {$sz360Formatted}" : ''),
                    'icon' => 'bi-lightning-charge-fill',
                    'btn_class' => 'btn-download-sd',
                    'url' => $dash360['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'needs_merge' => true,
                    'size_bytes' => $sz360,
                    'size_formatted' => $sz360Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_360p.mp4"
                ];
            }

            // Option 4: Audio Format Asli M4A / AAC (Direct Playable - Instant Download)
            if ($bestM4a) {
                $szM4a = (int)($bestM4a['filesize'] ?? ($bestM4a['filesize_approx'] ?? 0));
                $szM4aFormatted = $szM4a > 0 ? round($szM4a / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Format Asli M4A / AAC (Super Cepat Tanpa Konversi)',
                    'quality' => '⚡ M4A Audio (Instan)' . ($szM4aFormatted ? " • {$szM4aFormatted}" : ''),
                    'icon' => 'bi-file-earmark-music-fill',
                    'btn_class' => 'btn-download-audio',
                    'url' => $bestM4a['url'],
                    'source_url' => null,
                    'needs_merge' => false,
                    'size_bytes' => $szM4a,
                    'size_formatted' => $szM4aFormatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}.m4a"
                ];
            }

            // Option 5: Audio Musik MP3 (Auto-Compressed HQ)
            if ($bestAudio) {
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Musik MP3 (Kompres Otomatis - Jernih & Ringan)',
                    'quality' => 'Audio MP3 HQ',
                    'icon' => 'bi-music-note-beamed',
                    'btn_class' => 'btn-download-audio',
                    'url' => $bestAudio['url'],
                    'source_url' => $url,
                    'needs_merge' => true,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_audio.mp3"
                ];
            }

            // Option 6: Cover / Thumbnail HD
            if (!empty($thumbnail)) {
                $downloads[] = [
                    'type' => 'image',
                    'label' => 'Unduh Cover / Thumbnail HD (Resolusi Penuh)',
                    'quality' => 'HD Image',
                    'icon' => 'bi-image',
                    'btn_class' => 'btn-download-cover',
                    'url' => $thumbnail,
                    'needs_merge' => false,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_cover.jpg"
                ];
            }
        }

        $previewVideoUrl = $bestProgressive['url'] ?? ($f720['url'] ?? ($fSD['url'] ?? ''));

        return [
            'platform' => $isMusic ? 'ytmusic' : 'youtube',
            'platform_name' => $isMusic ? 'YouTube Music' : 'YouTube',
            'media_type' => $isMusic ? 'audio' : 'video',
            'title' => $title,
            'author' => $author,
            'album' => $album,
            'author_avatar' => $isMusic ? 'https://music.youtube.com/favicon.ico' : 'https://www.youtube.com/favicon.ico',
            'duration' => $durationStr,
            'resolution' => $f1080 ? '1080p' : ($f720 ? '720p' : '360p'),
            'thumbnail' => $thumbnail,
            'preview_video' => $previewVideoUrl,
            'preview_audio' => $bestAudio['url'] ?? null,
            'stats' => [
                'likes' => (int)($data['like_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0),
                'shares' => 0,
                'views' => (int)($data['view_count'] ?? 0)
            ],
            'downloads' => $downloads
        ];
    }
}
