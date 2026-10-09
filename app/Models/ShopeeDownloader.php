<?php
/**
 * VDownload Pro - Shopee Video Master HD Downloader
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Models;

use App\Core\DownloaderInterface;
use App\Core\MediaInspector;

class ShopeeDownloader implements DownloaderInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1';

    public function supports(string $url): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return str_contains($host, 'shopee.co.id') 
            || str_contains($host, 'shopee.com') 
            || str_contains($host, 'shp.ee')
            || str_contains($host, 'sv.shopee');
    }

    public function extract(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Sec-Fetch-Mode: navigate'
            ]
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if (!$html || $httpCode >= 400) {
            throw new \RuntimeException("Gagal mengakses tautan Shopee (HTTP {$httpCode}). Periksa koneksi atau URL.");
        }

        // Try extracting __NEXT_DATA__
        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData && isset($jsonData['props']['pageProps']['mediaInfo'])) {
                return $this->formatMediaInfo($jsonData['props']['pageProps']['mediaInfo'], $url, $effectiveUrl);
            }
        }

        // Fallback: direct regex search on HTML
        if (preg_match('/"watermarkVideoUrl"\s*:\s*"([^"]+)"/', $html, $mVideo)) {
            $videoUrl = stripslashes($mVideo[1]);
            $caption = '';
            if (preg_match('/"caption"\s*:\s*"([^"]+)"/', $html, $mCap)) {
                $caption = stripslashes($mCap[1]);
            }
            $coverUrl = '';
            if (preg_match('/"watermarkCoverUrl"\s*:\s*"([^"]+)"/', $html, $mCover)) {
                $coverUrl = stripslashes($mCover[1]);
            }

            return [
                'platform' => 'shopee',
                'platform_name' => 'Shopee Video',
                'title' => !empty($caption) ? $caption : 'Shopee Video',
                'author' => 'Shopee User',
                'author_avatar' => '',
                'duration' => null,
                'thumbnail' => $coverUrl,
                'preview_video' => $videoUrl,
                'stats' => ['likes' => 0, 'comments' => 0],
                'downloads' => [
                    [
                        'type' => 'video',
                        'label' => 'Unduh Shopee Video (MP4)',
                        'quality' => 'Original MP4',
                        'icon' => 'bi-play-circle-fill',
                        'btn_class' => 'btn-shopee',
                        'url' => $videoUrl,
                        'filename' => 'shopee_video_' . substr(md5($videoUrl), 0, 8) . '.mp4'
                    ]
                ]
            ];
        }

        throw new \RuntimeException('Video Shopee tidak ditemukan dalam halaman tersebut. Pastikan tautan adalah Shopee Video publik.');
    }

    private function formatMediaInfo(array $mediaInfo, string $originalUrl, string $effectiveUrl): array
    {
        $video = $mediaInfo['video'] ?? [];
        $userInfo = $mediaInfo['userInfo'] ?? [];
        $counts = $mediaInfo['count'] ?? [];

        $videoUrl = $video['watermarkVideoUrl'] ?? $video['videoUrl'] ?? '';
        if (empty($videoUrl)) {
            throw new \RuntimeException('Tautan stream video Shopee tidak dapat diekstraksi.');
        }

        $caption = $video['caption'] ?? 'Shopee Video';
        $cover = $video['watermarkCoverUrl'] ?? $video['coverUrl'] ?? '';
        $durationMs = $video['duration'] ?? 0;
        $durationStr = $durationMs > 0 ? gmdate('i:s', (int)($durationMs / 1000)) : null;

        $author = $userInfo['videoUserName'] ?? 'Shopee Creator';
        $avatarKey = $userInfo['videoUserAvatar'] ?? '';
        $avatarUrl = !empty($avatarKey) ? "https://down-aka-id.vod.susercontent.com/{$avatarKey}" : '';

        $cleanId = substr(md5($videoUrl), 0, 8);

        // Resolve all quality variants (Master Raw HD, Default Web, Watermarked)
        $variants = $this->resolveVideoVariants($videoUrl);
        $master = $variants['master'] ?? null;
        $cleanDefault = $variants['no_watermark'] ?? null;

        // Use the highest quality master stream for preview and primary download
        $previewUrl = $master['url'] ?? $cleanDefault['url'] ?? $videoUrl;
        $maxResolution = $master['resolution'] ?? $cleanDefault['resolution'] ?? '720p HD';
        if ($maxResolution && !empty($master['label'])) {
            $maxResolution .= " ({$master['label']})";
        }

        $downloads = [];

        // 1. TOP PRIORITY: True Master HD (Highest Resolution, Highest Bitrate, Tanpa Pecah)
        if (!empty($master['url'])) {
            $resBadge = !empty($master['resolution']) ? " ({$master['resolution']})" : ' (HD)';
            $sizeBadge = !empty($master['size_formatted']) ? " • {$master['size_formatted']}" : '';
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Full HD Asli{$resBadge} - Tanpa Watermark",
                'quality' => '⭐ True Master HD' . $sizeBadge,
                'icon' => 'bi-badge-hd-fill',
                'btn_class' => 'btn-shopee',
                'url' => $master['url'],
                'size_bytes' => (int)($master['size'] ?? 0),
                'size_formatted' => (string)($master['size_formatted'] ?? ''),
                'filename' => "shopee_{$author}_{$cleanId}_MasterHD_nowm.mp4"
            ];
        }

        // 2. SECONDARY: Web SD/Compress (Fast Mobile Download)
        if (!empty($cleanDefault['url'])) {
            $resBadge = !empty($cleanDefault['resolution']) ? " ({$cleanDefault['resolution']})" : '';
            $sizeBadge = !empty($cleanDefault['size_formatted']) ? " • {$cleanDefault['size_formatted']}" : '';
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Format Hemat Kuota{$resBadge} - Tanpa Watermark",
                'quality' => 'Web SD' . $sizeBadge,
                'icon' => 'bi-lightning-charge-fill',
                'btn_class' => 'btn-download-sd',
                'url' => $cleanDefault['url'],
                'size_bytes' => (int)($cleanDefault['size'] ?? 0),
                'size_formatted' => (string)($cleanDefault['size_formatted'] ?? ''),
                'filename' => "shopee_{$author}_{$cleanId}_SD_nowm.mp4"
            ];
        }

        // 3. Watermarked Original Variant
        $downloads[] = [
            'type' => 'video',
            'label' => 'Unduh Video Versi Aplikasi (Dengan Watermark Shopee)',
            'quality' => 'With Watermark',
            'icon' => 'bi-play-btn',
            'btn_class' => 'btn-download-wm',
            'url' => $videoUrl,
            'filename' => "shopee_{$author}_{$cleanId}_wm.mp4"
        ];

        // 4. Clean Cover Image (High-Res)
        if (!empty($cover)) {
            $downloads[] = [
                'type' => 'image',
                'label' => 'Unduh Cover / Thumbnail HD (Resolusi Penuh)',
                'quality' => 'HD JPG',
                'icon' => 'bi-image',
                'btn_class' => 'btn-download-cover',
                'url' => $cover,
                'filename' => "shopee_cover_{$cleanId}.jpg"
            ];
        }

        return [
            'platform' => 'shopee',
            'platform_name' => 'Shopee Video',
            'title' => $caption,
            'author' => $author,
            'author_avatar' => $avatarUrl,
            'duration' => $durationStr,
            'resolution' => $maxResolution,
            'thumbnail' => $cover,
            'preview_video' => $previewUrl,
            'stats' => [
                'likes' => (int)($counts['likeCount'] ?? 0),
                'comments' => (int)($counts['commentCount'] ?? 0)
            ],
            'downloads' => $downloads
        ];
    }

    /**
     * Resolve clean (No Watermark) and master raw video streams from Shopee VOD
     */
    private function resolveVideoVariants(string $watermarkUrl): array
    {
        $variants = [
            'no_watermark' => null,
            'master' => null
        ];

        // Match pattern: https://down-aka-id.vod.susercontent.com/api/v4/11110124/mms/id-11110124-6vgyf-mu1gvlscu6f73f.16003551791424720.1594.mp4
        if (preg_match('/(https?:\/\/[^\/]+\/api\/v4\/\d+\/mms\/)(id-[^\.]+)/i', $watermarkUrl, $matches)) {
            $base = $matches[1];
            $assetId = $matches[2];

            $cleanDefaultUrl = "{$base}{$assetId}.default.mp4";
            $masterRawUrl = "{$base}{$assetId}.mp4";

            // Check Master Raw stream (highest resolution/bitrate)
            $masterInfo = MediaInspector::inspect($masterRawUrl, self::USER_AGENT);
            if ($masterInfo['accessible']) {
                $variants['master'] = array_merge($masterInfo, ['url' => $masterRawUrl]);
            }

            // Check Clean Default stream (compressed mobile stream)
            $cleanInfo = MediaInspector::inspect($cleanDefaultUrl, self::USER_AGENT);
            if ($cleanInfo['accessible']) {
                $variants['no_watermark'] = array_merge($cleanInfo, ['url' => $cleanDefaultUrl]);
            }
        }

        return $variants;
    }
}
