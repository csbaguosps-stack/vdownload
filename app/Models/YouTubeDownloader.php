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
        // Safe escaping on Windows to prevent % in query parameters from being stripped
        $safeUrl = '"' . str_replace('"', '\"', $url) . '"';
        // Use python -m yt_dlp with json dump and Node.js JavaScript runtime
        $command = "python -m yt_dlp --js-runtimes node --skip-download --dump-single-json --no-warnings --no-playlist {$safeUrl} 2>&1";
        
        $output = shell_exec($command);
        if (!$output) {
            throw new \RuntimeException('Gagal mengekstrak video YouTube. Pastikan URL valid dan video bersifat publik.');
        }

        // Clean any non-json lines before the json object
        $jsonStart = strpos($output, '{');
        if ($jsonStart !== false) {
            $output = substr($output, $jsonStart);
        }

        $data = json_decode($output, true);
        if (!$data || !isset($data['id'])) {
            $errorMsg = 'Video YouTube / YouTube Music tidak ditemukan atau berstatus privat/dibatasi usia.';
            if (str_contains($output, 'Private video')) {
                $errorMsg = 'Video/lagu YouTube berstatus privat.';
            } elseif (str_contains($output, 'Video unavailable')) {
                $errorMsg = 'Video/lagu YouTube tidak tersedia atau telah dihapus.';
            }
            throw new \RuntimeException($errorMsg);
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

        // 1. Find Best Streams
        $f1080 = null;
        $f720 = null;
        $f360 = null;
        $bestAudio = null;
        $bestM4a = null;
        $bestProgressive = null;

        foreach ($formats as $f) {
            $vcodec = $f['vcodec'] ?? 'none';
            $acodec = $f['acodec'] ?? 'none';
            $height = (int)($f['height'] ?? 0);
            $width = (int)($f['width'] ?? 0);
            $effectiveH = ($height > 0) ? $height : (int)round($width * 9 / 16);
            $ext = $f['ext'] ?? 'mp4';
            $urlStream = $f['url'] ?? '';

            if (empty($urlStream)) continue;

            // Progressive (both audio and video in single stream)
            if ($vcodec !== 'none' && $acodec !== 'none') {
                if ($effectiveH >= 720 && (!$bestProgressive || $effectiveH > ($bestProgressive['height'] ?? 0))) {
                    $bestProgressive = $f;
                } elseif ($effectiveH >= 360 && !$f360) {
                    $f360 = $f;
                }
            }

            // Video streams
            if ($vcodec !== 'none') {
                if ($effectiveH >= 1080 && (!$f1080 || $ext === 'mp4')) {
                    $f1080 = $f;
                } elseif ($effectiveH >= 720 && $effectiveH < 1080 && (!$f720 || $ext === 'mp4')) {
                    $f720 = $f;
                } elseif ($effectiveH >= 360 && $effectiveH <= 480 && (!$f360 || $ext === 'mp4')) {
                    $f360 = $f;
                }
            }

            // Audio only stream
            if ($vcodec === 'none' && $acodec !== 'none') {
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
                    'filename' => "{$filePrefix}_{$cleanAuthor}_{$cleanTitle}_320k.mp3"
                ];
            }

            // Primary 2: Original M4A / AAC (Direct Playable on iOS/Android/Windows)
            if ($bestM4a) {
                $szM4a = (int)($bestM4a['filesize'] ?? ($bestM4a['filesize_approx'] ?? 0));
                $szM4aFormatted = $szM4a > 0 ? round($szM4a / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Format M4A / AAC (Format Asli Tanpa Konversi)',
                    'quality' => 'M4A Original' . ($szM4aFormatted ? " • {$szM4aFormatted}" : ''),
                    'icon' => 'bi-file-earmark-music-fill',
                    'btn_class' => 'btn-download-audio',
                    'url' => $bestM4a['url'],
                    'source_url' => $url,
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
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Musik HD 720p (MP4 Cerdas + Audio)',
                    'quality' => 'HD 720p' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                    'icon' => 'bi-play-circle-fill',
                    'btn_class' => 'btn-youtube',
                    'url' => $target720['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
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
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Musik SD 360p (MP4 Hemat Kuota + Audio)',
                    'quality' => 'SD 360p (Hemat Kuota)' . ($sz360Formatted ? " • {$sz360Formatted}" : ''),
                    'icon' => 'bi-lightning-charge-fill',
                    'btn_class' => 'btn-download-sd',
                    'url' => $target360['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
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
            $target1080 = $f1080 ?: ($f720 ?: $bestProgressive);
            if ($target1080) {
                $sz1080 = (int)($target1080['filesize'] ?? ($target1080['filesize_approx'] ?? 0));
                $sz1080Formatted = $sz1080 > 0 ? round($sz1080 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video Full HD 1080p (MP4 Jernih + Audio)',
                    'quality' => '⭐ 1080p Full HD' . ($sz1080Formatted ? " • {$sz1080Formatted}" : ''),
                    'icon' => 'bi-badge-hd-fill',
                    'btn_class' => 'btn-youtube-hd',
                    'url' => $target1080['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'size_bytes' => $sz1080,
                    'size_formatted' => $sz1080Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_1080p.mp4"
                ];
            }

            // Option 2: HD 720p
            $target720 = $bestProgressive ?: ($f720 ?: ($f1080 ?: $f360));
            if ($target720) {
                $sz720 = (int)($target720['filesize'] ?? ($target720['filesize_approx'] ?? 0));
                $sz720Formatted = $sz720 > 0 ? round($sz720 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video HD 720p (MP4 Cerdas + Audio)',
                    'quality' => 'HD 720p' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                    'icon' => 'bi-play-circle-fill',
                    'btn_class' => 'btn-youtube',
                    'url' => $target720['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'size_bytes' => $sz720,
                    'size_formatted' => $sz720Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_720p.mp4"
                ];
            }

            // Option 3: Standard SD 360p
            $target360 = $f360 ?: ($bestProgressive ?: ($f720 ?: $f1080));
            if ($target360) {
                $sz360 = (int)($target360['filesize'] ?? ($target360['filesize_approx'] ?? 0));
                $sz360Formatted = $sz360 > 0 ? round($sz360 / (1024 * 1024), 1) . ' MB' : '';
                $downloads[] = [
                    'type' => 'video',
                    'label' => 'Unduh Video SD 360p (MP4 Hemat Kuota + Audio)',
                    'quality' => 'SD 360p (Hemat Kuota)' . ($sz360Formatted ? " • {$sz360Formatted}" : ''),
                    'icon' => 'bi-lightning-charge-fill',
                    'btn_class' => 'btn-download-sd',
                    'url' => $target360['url'],
                    'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                    'source_url' => $url,
                    'size_bytes' => $sz360,
                    'size_formatted' => $sz360Formatted,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_360p.mp4"
                ];
            }

            // Option 4: Audio Musik MP3 (Auto-Compressed HQ)
            if ($bestAudio) {
                $downloads[] = [
                    'type' => 'audio',
                    'label' => 'Unduh Audio Musik MP3 (Kompres Otomatis - Jernih & Ringan)',
                    'quality' => 'Audio MP3 HQ',
                    'icon' => 'bi-music-note-beamed',
                    'btn_class' => 'btn-download-audio',
                    'url' => $bestAudio['url'],
                    'source_url' => $url,
                    'filename' => "youtube_{$cleanAuthor}_{$cleanId}_audio.mp3"
                ];
            }

            // Option 5: Cover / Thumbnail HD
            if (!empty($thumbnail)) {
                $downloads[] = [
                    'type' => 'image',
                    'label' => 'Unduh Cover / Thumbnail HD (Resolusi Penuh)',
                    'quality' => 'HD Image',
                    'icon' => 'bi-image',
                    'btn_class' => 'btn-download-cover',
                    'url' => $thumbnail,
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
