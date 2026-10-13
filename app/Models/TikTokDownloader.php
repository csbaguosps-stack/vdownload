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
use App\Config\Config;

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

        // Check for Photo Mode / Slideshow Images
        $rawImages = $data['images'] ?? [];
        $images = [];
        if (is_array($rawImages)) {
            foreach ($rawImages as $img) {
                $imgUrl = is_array($img) ? ($img['url'] ?? $img['download_url'] ?? '') : (string)$img;
                if (!empty($imgUrl)) {
                    if (!str_starts_with($imgUrl, 'http')) {
                        $imgUrl = 'https://www.tikwm.com' . $imgUrl;
                    }
                    $images[] = $imgUrl;
                }
            }
        }

        $downloads = [];
        $baseUrl = Config::getBaseUrl();

        // 1. IF PHOTO POST: Generate photo download links and ZIP bundle option
        if (!empty($images)) {
            if (count($images) > 1) {
                $zipQuery = http_build_query([
                    'urls' => $images,
                    'name' => "tiktok_{$author}_{$id}_photos.zip"
                ]);
                $downloads[] = [
                    'type' => 'zip',
                    'label' => 'Unduh Semua Foto Sekaligus (' . count($images) . ' Foto Full HD)',
                    'quality' => '⭐ Paket ZIP',
                    'icon' => 'bi-file-earmark-zip-fill',
                    'btn_class' => 'btn-tiktok-hd',
                    'url' => $baseUrl . '/download/zip?' . $zipQuery,
                    'filename' => "tiktok_{$author}_{$id}_photos.zip"
                ];
            }

            foreach ($images as $idx => $imgUrl) {
                $num = $idx + 1;
                $downloads[] = [
                    'type' => 'image',
                    'label' => "Unduh Foto #{$num} (Kualitas HD Asli)",
                    'quality' => '⭐ HD Foto',
                    'icon' => 'bi-image',
                    'btn_class' => 'btn-tiktok',
                    'url' => $imgUrl,
                    'filename' => "tiktok_{$author}_{$id}_photo_{$num}.jpg"
                ];
            }
        }

        // Prepare Video URLs
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
        if (!empty($images)) {
            $detectedRes = count($images) . ' Foto HD';
        } elseif (!empty($hdInfo['resolution'])) {
            $detectedRes = $hdInfo['resolution'] . (!empty($hdInfo['label']) ? " ({$hdInfo['label']})" : '');
        } elseif (!empty($hdUrl)) {
            $detectedRes = '1080x1920 (Full HD)';
        } elseif (!empty($sdInfo['resolution'])) {
            $detectedRes = $sdInfo['resolution'];
        }

        // 1. Full HD 1080p No Watermark Video
        if (!empty($hdUrl)) {
            $resBadge = !empty($hdInfo['resolution']) ? " ({$hdInfo['resolution']})" : ' (Full HD)';
            $label = empty($images) 
                ? "Unduh Video Full HD 1080p{$resBadge} - Tanpa Watermark (Kualitas Terbaik)"
                : "Unduh Video Slideshow Full HD 1080p{$resBadge} (MP4)";
            $downloads[] = [
                'type' => 'video',
                'label' => $label,
                'quality' => '⭐ 1080p Full HD' . $hdSizeLabel,
                'icon' => 'bi-stars',
                'btn_class' => empty($images) ? 'btn-tiktok-hd' : 'btn-tiktok',
                'url' => $hdUrl,
                'size_bytes' => (int)($hdInfo['size'] ?? ($data['hd_size'] ?? 0)),
                'size_formatted' => (string)($hdInfo['size_formatted'] ?? ''),
                'filename' => "tiktok_{$author}_{$id}_1080p_nowm.mp4"
            ];
        }

        // 2. HD 720p No Watermark Video
        $target720 = !empty($playUrl) ? $playUrl : $hdUrl;
        if (!empty($target720) && empty($images)) {
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video HD 720p - Tanpa Watermark (Kualitas Seimbang)",
                'quality' => 'HD 720p' . $sdSizeLabel,
                'icon' => 'bi-badge-hd',
                'btn_class' => 'btn-tiktok',
                'url' => $target720,
                'size_bytes' => (int)($sdInfo['size'] ?? ($data['size'] ?? 0)),
                'size_formatted' => (string)($sdInfo['size_formatted'] ?? ''),
                'filename' => "tiktok_{$author}_{$id}_720p_nowm.mp4"
            ];
        }

        // 3. SD 360p No Watermark Video (Hemat Kuota)
        $target360 = !empty($playUrl) ? $playUrl : $hdUrl;
        if (!empty($target360) && empty($images)) {
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Hemat Kuota SD 360p - Tanpa Watermark (Ekstra Ringan)",
                'quality' => 'SD 360p (Hemat Kuota)',
                'icon' => 'bi-lightning-charge-fill',
                'btn_class' => 'btn-download-sd',
                'url' => $target360,
                'filename' => "tiktok_{$author}_{$id}_360p_nowm.mp4"
            ];
        }

        // Original with Watermark
        if (!empty($data['wmplay']) && empty($images)) {
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

        // MP3 Audio (Always highly requested for TikTok!)
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

        // Thumbnail / Cover
        if (!empty($cover) && empty($images)) {
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

        // Preview URL
        $isPhotoPost = !empty($images);
        $previewUrl = !$isPhotoPost ? (!empty($hdUrl) ? $hdUrl : $playUrl) : (!empty($hdUrl) ? $hdUrl : null);
        $coverThumbnail = !empty($images[0]) ? $images[0] : $cover;

        return [
            'platform' => 'tiktok',
            'platform_name' => 'TikTok',
            'media_type' => $isPhotoPost ? 'photo' : 'video',
            'title' => $title,
            'author' => $author,
            'author_avatar' => $avatar,
            'duration' => $duration,
            'resolution' => $detectedRes,
            'thumbnail' => $coverThumbnail,
            'preview_video' => $previewUrl,
            'preview_audio' => !empty($data['music']) ? (str_starts_with($data['music'], 'http') ? $data['music'] : 'https://www.tikwm.com' . $data['music']) : null,
            'gallery' => $images,
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
        if (!$data) {
            throw new \RuntimeException('Gagal membaca data video TikTok via yt-dlp.');
        }

        $id = $data['id'] ?? substr(md5($url), 0, 8);
        $title = $data['title'] ?? 'TikTok Video';
        $cover = $data['thumbnail'] ?? '';
        $author = $data['uploader'] ?? ($data['channel'] ?? 'TikTok User');
        $videoUrl = $data['url'] ?? '';

        // Check for entries if photo mode or playlist
        $entries = $data['entries'] ?? [];
        $images = [];
        if (!empty($entries) && is_array($entries)) {
            foreach ($entries as $e) {
                if (!empty($e['url'])) {
                    $images[] = $e['url'];
                } elseif (!empty($e['thumbnail'])) {
                    $images[] = $e['thumbnail'];
                }
            }
        }

        $downloads = [];
        $baseUrl = Config::getBaseUrl();

        if (!empty($images)) {
            if (count($images) > 1) {
                $zipQuery = http_build_query([
                    'urls' => $images,
                    'name' => "tiktok_{$author}_{$id}_photos.zip"
                ]);
                $downloads[] = [
                    'type' => 'zip',
                    'label' => 'Unduh Semua Foto Sekaligus (' . count($images) . ' Foto Full HD)',
                    'quality' => '⭐ Paket ZIP',
                    'icon' => 'bi-file-earmark-zip-fill',
                    'btn_class' => 'btn-tiktok-hd',
                    'url' => $baseUrl . '/download/zip?' . $zipQuery,
                    'filename' => "tiktok_{$author}_{$id}_photos.zip"
                ];
            }

            foreach ($images as $idx => $imgUrl) {
                $num = $idx + 1;
                $downloads[] = [
                    'type' => 'image',
                    'label' => "Unduh Foto #{$num} (Kualitas HD Asli)",
                    'quality' => '⭐ HD Foto',
                    'icon' => 'bi-image',
                    'btn_class' => 'btn-tiktok',
                    'url' => $imgUrl,
                    'filename' => "tiktok_{$author}_{$id}_photo_{$num}.jpg"
                ];
            }
        }

        if (!empty($videoUrl)) {
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video Full HD 1080p (MP4 Jernih)',
                'quality' => '⭐ 1080p Full HD',
                'icon' => 'bi-stars',
                'btn_class' => 'btn-tiktok-hd',
                'url' => $videoUrl,
                'filename' => "tiktok_{$id}_1080p.mp4"
            ];
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video HD 720p (MP4 Seimbang)',
                'quality' => 'HD 720p',
                'icon' => 'bi-badge-hd',
                'btn_class' => 'btn-tiktok',
                'url' => $videoUrl,
                'filename' => "tiktok_{$id}_720p.mp4"
            ];
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video SD 360p (MP4 Hemat Kuota)',
                'quality' => 'SD 360p (Hemat Kuota)',
                'icon' => 'bi-lightning-charge-fill',
                'btn_class' => 'btn-download-sd',
                'url' => $videoUrl,
                'filename' => "tiktok_{$id}_360p.mp4"
            ];
        }

        if (empty($downloads)) {
            throw new \RuntimeException('Tidak ada stream video atau foto yang ditemukan pada tautan TikTok ini.');
        }

        return [
            'platform' => 'tiktok',
            'platform_name' => 'TikTok',
            'media_type' => !empty($images) ? 'photo' : 'video',
            'title' => $title,
            'author' => $author,
            'author_avatar' => '',
            'duration' => isset($data['duration']) ? gmdate('i:s', (int)$data['duration']) : null,
            'resolution' => !empty($images) ? (count($images) . ' Foto HD') : 'HD',
            'thumbnail' => !empty($images[0]) ? $images[0] : $cover,
            'preview_video' => !empty($images) ? null : $videoUrl,
            'gallery' => $images,
            'stats' => [
                'likes' => (int)($data['like_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0),
                'shares' => (int)($data['repost_count'] ?? 0),
                'views' => (int)($data['view_count'] ?? 0)
            ],
            'downloads' => $downloads
        ];
    }
}
