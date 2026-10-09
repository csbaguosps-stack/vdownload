<?php
/**
 * VDownload Pro - TikTok Full HD Video & MP3 Downloader
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Models;

use App\Core\DownloaderInterface;
use App\Core\MediaInspector;

class TikTokDownloader implements DownloaderInterface
{
    private const TIKWM_API = 'https://www.tikwm.com/api/';

    public function supports(string $url): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return str_contains($host, 'tiktok.com') 
            || str_contains($host, 'douyin.com')
            || str_contains($host, 'vt.tiktok')
            || str_contains($host, 'vm.tiktok');
    }

    public function extract(string $url): array
    {
        // 1. Primary: TikWM API
        try {
            return $this->extractViaTikWm($url);
        } catch (\Throwable $e) {
            // 2. Fallback: yt-dlp via python
            return $this->extractViaYtDlp($url);
        }
    }

    private function extractViaTikWm(string $url): array
    {
        $apiUrl = self::TIKWM_API . '?url=' . urlencode($url) . '&hd=1';
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 15
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || empty($res)) {
            throw new \RuntimeException('Gagal menghubungi server TikWM.');
        }

        $json = json_decode($res, true);
        if (!$json || !isset($json['data']) || ($json['code'] ?? -1) !== 0) {
            $msg = $json['msg'] ?? 'Video TikTok tidak ditemukan atau berstatus privat.';
            throw new \RuntimeException("TikWM: {$msg}");
        }

        $data = $json['data'];
        $id = $data['id'] ?? substr(md5($url), 0, 8);
        $title = !empty($data['title']) ? $data['title'] : 'TikTok Video';
        $cover = $data['cover'] ?? '';
        $author = $data['author']['nickname'] ?? ($data['author']['unique_id'] ?? 'TikTok Creator');
        $avatar = $data['author']['avatar'] ?? '';
        $durationSec = (int)($data['duration'] ?? 0);
        $duration = $durationSec > 0 ? gmdate('i:s', $durationSec) : null;

        $downloads = [];

        // Prepare URLs
        $hdUrl = '';
        if (!empty($data['hdplay'])) {
            $hdUrl = $data['hdplay'];
            if (!str_starts_with($hdUrl, 'http')) {
                $hdUrl = 'https://www.tikwm.com' . $hdUrl;
            }
        }

        $playUrl = '';
        if (!empty($data['play'])) {
            $playUrl = $data['play'];
            if (!str_starts_with($playUrl, 'http')) {
                $playUrl = 'https://www.tikwm.com' . $playUrl;
            }
        }

        // Inspect HD stream for genuine resolution and size
        $hdInfo = !empty($hdUrl) ? MediaInspector::inspect($hdUrl) : ['accessible' => false, 'resolution' => '', 'size_formatted' => ''];
        $sdInfo = !empty($playUrl) ? MediaInspector::inspect($playUrl) : ['accessible' => false, 'resolution' => '', 'size_formatted' => ''];

        // Compute size labels
        $hdSizeLabel = '';
        if (!empty($hdInfo['size_formatted'])) {
            $hdSizeLabel = ' • ' . $hdInfo['size_formatted'];
        } elseif (!empty($data['hd_size'])) {
            $hdSizeLabel = ' • ' . round((int)$data['hd_size'] / (1024 * 1024), 1) . ' MB';
        }

        $sdSizeLabel = '';
        if (!empty($sdInfo['size_formatted'])) {
            $sdSizeLabel = ' • ' . $sdInfo['size_formatted'];
        } elseif (!empty($data['size'])) {
            $sdSizeLabel = ' • ' . round((int)$data['size'] / (1024 * 1024), 1) . ' MB';
        }

        // Detected resolution string
        $detectedRes = '';
        if (!empty($hdInfo['resolution'])) {
            $detectedRes = $hdInfo['resolution'] . (!empty($hdInfo['label']) ? " ({$hdInfo['label']})" : '');
        } elseif (!empty($hdUrl)) {
            $detectedRes = '1080x1920 (Full HD)';
        } elseif (!empty($sdInfo['resolution'])) {
            $detectedRes = $sdInfo['resolution'];
        }

        // 1. TOP PRIORITY: HD No Watermark Video (Genuine HD, Bening Tidak Pecah)
        if (!empty($hdUrl)) {
            $resBadge = !empty($hdInfo['resolution']) ? " ({$hdInfo['resolution']})" : ' (Full HD)';
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Full HD{$resBadge} - Tanpa Watermark (Kualitas Terbaik)",
                'quality' => '⭐ True HD' . $hdSizeLabel,
                'icon' => 'bi-stars',
                'btn_class' => 'btn-tiktok-hd',
                'url' => $hdUrl,
                'size_bytes' => (int)($hdInfo['size'] ?? ($data['hd_size'] ?? 0)),
                'size_formatted' => (string)($hdInfo['size_formatted'] ?? ''),
                'filename' => "tiktok_{$author}_{$id}_HD_nowm.mp4"
            ];
        }

        // 2. Standard No Watermark Video
        if (!empty($playUrl)) {
            $resBadge = !empty($sdInfo['resolution']) ? " ({$sdInfo['resolution']})" : '';
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Standar{$resBadge} - Tanpa Watermark",
                'quality' => 'Clean SD' . $sdSizeLabel,
                'icon' => 'bi-play-circle',
                'btn_class' => 'btn-tiktok',
                'url' => $playUrl,
                'size_bytes' => (int)($sdInfo['size'] ?? ($data['size'] ?? 0)),
                'size_formatted' => (string)($sdInfo['size_formatted'] ?? ''),
                'filename' => "tiktok_{$author}_{$id}_SD_nowm.mp4"
            ];
        }

        // 3. Original with Watermark (Comparison Option)
        if (!empty($data['wmplay'])) {
            $wmUrl = $data['wmplay'];
            if (!str_starts_with($wmUrl, 'http')) {
                $wmUrl = 'https://www.tikwm.com' . $wmUrl;
            }
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video Asli (Dengan Watermark TikTok)',
                'quality' => 'With Watermark',
                'icon' => 'bi-play-btn',
                'btn_class' => 'btn-download-wm',
                'url' => $wmUrl,
                'filename' => "tiktok_{$author}_{$id}_wm.mp4"
            ];
        }

        // 4. MP3 Audio
        if (!empty($data['music'])) {
            $musicUrl = $data['music'];
            if (!str_starts_with($musicUrl, 'http')) {
                $musicUrl = 'https://www.tikwm.com' . $musicUrl;
            }
            $musicTitle = $data['music_info']['title'] ?? 'Original Sound';
            $downloads[] = [
                'type' => 'audio',
                'label' => "Unduh Audio Musik MP3 ({$musicTitle})",
                'quality' => 'Audio MP3',
                'icon' => 'bi-music-note-beamed',
                'btn_class' => 'btn-download-audio',
                'url' => $musicUrl,
                'filename' => "tiktok_audio_{$id}.mp3"
            ];
        }

        // 5. Thumbnail / Cover
        if (!empty($cover)) {
            $downloads[] = [
                'type' => 'image',
                'label' => 'Unduh Cover / Thumbnail HD (Resolusi Penuh)',
                'quality' => 'HD Image',
                'icon' => 'bi-image',
                'btn_class' => 'btn-download-cover',
                'url' => $cover,
                'filename' => "tiktok_cover_{$id}.jpg"
            ];
        }

        // Preview URL: ALWAYS PRIORITIZE HD for crystal-clear preview
        $previewUrl = !empty($hdUrl) ? $hdUrl : $playUrl;

        return [
            'platform' => 'tiktok',
            'platform_name' => 'TikTok',
            'title' => $title,
            'author' => $author,
            'author_avatar' => $avatar,
            'duration' => $duration,
            'resolution' => $detectedRes,
            'thumbnail' => $cover,
            'preview_video' => $previewUrl,
            'preview_audio' => !empty($data['music']) ? (str_starts_with($data['music'], 'http') ? $data['music'] : 'https://www.tikwm.com' . $data['music']) : null,
            'stats' => [
                'likes' => (int)($data['digg_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0),
                'shares' => (int)($data['share_count'] ?? 0),
                'views' => (int)($data['play_count'] ?? 0)
            ],
            'downloads' => $downloads
        ];
    }

    private function extractViaYtDlp(string $url): array
    {
        $escapedUrl = escapeshellarg($url);
        $command = "python -m yt_dlp --skip-download --dump-single-json --no-warnings {$escapedUrl} 2>&1";
        
        $output = shell_exec($command);
        if (!$output) {
            throw new \RuntimeException('Gagal mengekstrak video TikTok.');
        }

        $data = json_decode($output, true);
        if (!$data || empty($data['url'])) {
            throw new \RuntimeException('Gagal membaca data video TikTok via yt-dlp.');
        }

        $id = $data['id'] ?? substr(md5($url), 0, 8);
        $title = $data['title'] ?? 'TikTok Video';
        $cover = $data['thumbnail'] ?? '';
        $author = $data['uploader'] ?? ($data['channel'] ?? 'TikTok User');
        $videoUrl = $data['url'];

        return [
            'platform' => 'tiktok',
            'platform_name' => 'TikTok',
            'title' => $title,
            'author' => $author,
            'author_avatar' => '',
            'duration' => isset($data['duration']) ? gmdate('i:s', (int)$data['duration']) : null,
            'thumbnail' => $cover,
            'preview_video' => $videoUrl,
            'stats' => [
                'likes' => (int)($data['like_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0),
                'shares' => (int)($data['repost_count'] ?? 0),
                'views' => (int)($data['view_count'] ?? 0)
            ],
            'downloads' => [
                [
                    'type' => 'video',
                    'label' => 'Unduh Video TikTok (MP4)',
                    'quality' => 'Direct MP4',
                    'icon' => 'bi-download',
                    'btn_class' => 'btn-tiktok',
                    'url' => $videoUrl,
                    'filename' => "tiktok_{$id}.mp4"
                ]
            ]
        ];
    }
}
