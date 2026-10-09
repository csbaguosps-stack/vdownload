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
            || str_contains($host, 'music.youtube.com');
    }

    public function extract(string $url): array
    {
        $escapedUrl = escapeshellarg($url);
        // Use python -m yt_dlp with json dump
        $command = "python -m yt_dlp --skip-download --dump-single-json --no-warnings --no-playlist {$escapedUrl} 2>&1";
        
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
            $errorMsg = 'Video YouTube tidak ditemukan atau berstatus privat/dibatasi usia.';
            if (str_contains($output, 'Private video')) {
                $errorMsg = 'Video YouTube berstatus privat.';
            } elseif (str_contains($output, 'Video unavailable')) {
                $errorMsg = 'Video YouTube tidak tersedia atau telah dihapus.';
            }
            throw new \RuntimeException($errorMsg);
        }

        $id = (string)$data['id'];
        $title = !empty($data['title']) ? $data['title'] : 'YouTube Video';
        $author = !empty($data['uploader']) ? $data['uploader'] : (!empty($data['channel']) ? $data['channel'] : 'YouTube Creator');
        $cleanAuthor = preg_replace('/[^a-zA-Z0-9_-]/', '_', $author);
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

        // Best Thumbnail
        $thumbnail = "https://i.ytimg.com/vi/{$id}/maxresdefault.jpg";
        if (!empty($data['thumbnail'])) {
            $thumbnail = $data['thumbnail'];
        }

        $formats = $data['formats'] ?? [];
        $downloads = [];

        // 1. Find Best 1080p Full HD Video Stream
        $f1080 = null;
        $f720 = null;
        $fSD = null;
        $bestAudio = null;
        $bestProgressive = null;

        foreach ($formats as $f) {
            $vcodec = $f['vcodec'] ?? 'none';
            $acodec = $f['acodec'] ?? 'none';
            $height = (int)($f['height'] ?? 0);
            $ext = $f['ext'] ?? 'mp4';
            $url = $f['url'] ?? '';

            if (empty($url)) continue;

            // Progressive (both audio and video)
            if ($vcodec !== 'none' && $acodec !== 'none') {
                if ($height >= 720 && (!$bestProgressive || $height > ($bestProgressive['height'] ?? 0))) {
                    $bestProgressive = $f;
                } elseif ($height >= 360 && !$fSD) {
                    $fSD = $f;
                }
            }

            // Video streams
            if ($vcodec !== 'none') {
                if ($height >= 1080 && (!$f1080 || $ext === 'mp4')) {
                    $f1080 = $f;
                } elseif ($height === 720 && (!$f720 || $ext === 'mp4')) {
                    $f720 = $f;
                } elseif ($height <= 480 && $height >= 360 && (!$fSD || $ext === 'mp4')) {
                    $fSD = $f;
                }
            }

            // Audio only stream
            if ($vcodec === 'none' && $acodec !== 'none') {
                $abr = (float)($f['abr'] ?? 0);
                if (!$bestAudio || $abr > (float)($bestAudio['abr'] ?? 0)) {
                    $bestAudio = $f;
                }
            }
        }

        // Option 1: Full HD 1080p (Auto-Compressed with Audio)
        if ($f1080) {
            $sz1080 = (int)($f1080['filesize'] ?? ($f1080['filesize_approx'] ?? 0));
            $sz1080Formatted = $sz1080 > 0 ? round($sz1080 / (1024 * 1024), 1) . ' MB' : '';
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video Full HD 1080p (MP4 - Kompres Otomatis Jernih)',
                'quality' => '⭐ 1080p Full HD' . ($sz1080Formatted ? " • {$sz1080Formatted}" : ''),
                'icon' => 'bi-badge-hd-fill',
                'btn_class' => 'btn-youtube-hd',
                'url' => $f1080['url'],
                'audio_url' => $bestAudio ? $bestAudio['url'] : null,
                'size_bytes' => $sz1080,
                'size_formatted' => $sz1080Formatted,
                'filename' => "youtube_{$cleanAuthor}_{$cleanId}_1080p.mp4"
            ];
        }

        // Option 2: HD 720p
        if ($f720 || $bestProgressive) {
            $target720 = $bestProgressive ?: $f720;
            $hasAudio = isset($target720['acodec']) && $target720['acodec'] !== 'none';
            $sz720 = (int)($target720['filesize'] ?? ($target720['filesize_approx'] ?? 0));
            $sz720Formatted = $sz720 > 0 ? round($sz720 / (1024 * 1024), 1) . ' MB' : '';
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video HD 720p (MP4 - Kompres Cerdas)',
                'quality' => 'HD 720p' . ($sz720Formatted ? " • {$sz720Formatted}" : ''),
                'icon' => 'bi-play-circle-fill',
                'btn_class' => 'btn-youtube',
                'url' => $target720['url'],
                'audio_url' => (!$hasAudio && $bestAudio) ? $bestAudio['url'] : null,
                'size_bytes' => $sz720,
                'size_formatted' => $sz720Formatted,
                'filename' => "youtube_{$cleanAuthor}_{$cleanId}_720p.mp4"
            ];
        }

        // Option 3: Standard SD 360p / 480p
        if ($fSD) {
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video Format Hemat Kuota (SD 360p/480p)',
                'quality' => 'Web SD (Ekstra Ringan)',
                'icon' => 'bi-lightning-charge-fill',
                'btn_class' => 'btn-download-sd',
                'url' => $fSD['url'],
                'audio_url' => (isset($fSD['acodec']) && $fSD['acodec'] === 'none' && $bestAudio) ? $bestAudio['url'] : null,
                'filename' => "youtube_{$cleanAuthor}_{$cleanId}_SD.mp4"
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

        $previewVideoUrl = $bestProgressive['url'] ?? ($f720['url'] ?? ($fSD['url'] ?? ''));

        return [
            'platform' => 'youtube',
            'platform_name' => 'YouTube',
            'title' => $title,
            'author' => $author,
            'author_avatar' => 'https://www.youtube.com/favicon.ico',
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
