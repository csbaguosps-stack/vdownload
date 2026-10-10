<?php
/**
 * VDownload Pro - Instagram Reels & Video Downloader
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

class InstagramDownloader implements DownloaderInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    public function supports(string $url): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return str_contains($host, 'instagram.com') || str_contains($host, 'instagr.am');
    }

    public function extract(string $url): array
    {
        $shortcode = $this->extractShortcode($url);
        if (!$shortcode) {
            throw new \InvalidArgumentException('Format URL Instagram tidak valid. Contoh: https://www.instagram.com/reel/C8h0YdKP2fC/ atau https://www.instagram.com/p/DB-L9Z8O1tF/');
        }

        $sessionId = SettingModel::getInstagramSessionId();

        // 1. Attempt GraphQL API (especially effective if sessionid is set)
        try {
            $data = $this->extractViaGraphQL($shortcode, $sessionId);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to next strategy
        }

        // 2. Attempt Snapsave scraper with JS unpacking (supports photos & carousels)
        try {
            $data = $this->extractViaSnapsave($url);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to next strategy
        }

        // 3. Attempt Embed HTML Scraper
        try {
            $data = $this->extractViaEmbedHtml($shortcode);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to next strategy
        }

        // 4. Attempt yt-dlp
        try {
            $data = $this->extractViaYtDlp($url, $sessionId);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to fallback
        }

        // 5. If all failed, provide a clear instructional message
        $hint = empty($sessionId) 
            ? ' (Tip: Instagram membatasi akses tanpa login. Masukkan cookie "sessionid" di Pengaturan atau gunakan Ekstensi Browser VDownload Pro untuk unduh foto & video langsung dari tab).' 
            : '';
        throw new \RuntimeException("Gagal mengambil foto atau video Instagram. Pastikan akun tidak privat dan URL valid{$hint}");
    }

    public function extractShortcode(string $url): ?string
    {
        if (preg_match('/(?:reel|reels|p|tv)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractViaGraphQL(string $shortcode, string $sessionId = ''): ?array
    {
        $docIds = ['10015901848480474', '8845758582119845'];
        
        foreach ($docIds as $docId) {
            $ch = curl_init('https://www.instagram.com/api/graphql');
            $headers = [
                'User-Agent: ' . self::USER_AGENT,
                'X-IG-App-ID: 936619743392459',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://www.instagram.com',
                'Referer: https://www.instagram.com/p/' . $shortcode . '/',
                'X-Requested-With: XMLHttpRequest',
                'Accept: application/json'
            ];

            if (!empty($sessionId)) {
                $headers[] = 'Cookie: sessionid=' . $sessionId . ';';
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'doc_id' => $docId,
                    'variables' => json_encode(['shortcode' => $shortcode])
                ]),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 12
            ]);

            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($res)) {
                $json = json_decode($res, true);
                if (isset($json['data']['xdt_shortcode_media'])) {
                    $media = $json['data']['xdt_shortcode_media'];
                    return $this->formatGraphQlResponse($media, $shortcode);
                }
            }
        }

        return null;
    }

    private function formatGraphQlResponse(array $media, string $shortcode): array
    {
        $isVideo = (bool)($media['is_video'] ?? false);
        $videoUrl = $media['video_url'] ?? '';
        $thumbnail = $media['display_url'] ?? '';
        $caption = $media['edge_media_to_caption']['edges'][0]['node']['text'] ?? 'Instagram Media';
        $author = $media['owner']['username'] ?? 'Instagram User';
        $avatar = $media['owner']['profile_pic_url'] ?? '';
        $duration = isset($media['video_duration']) ? gmdate('i:s', (int)$media['video_duration']) : null;
        $likes = (int)($media['edge_media_preview_like']['count'] ?? 0);
        $comments = (int)($media['edge_media_to_comment']['count'] ?? 0);

        $downloads = [];
        $gallery = [];
        $baseUrl = Config::getBaseUrl();

        // Check for Carousel (Sidecar) with multiple photos/videos
        $isSidecar = !empty($media['edge_sidecar_to_children']['edges']);
        
        if ($isSidecar) {
            $children = $media['edge_sidecar_to_children']['edges'];
            $photoUrls = [];

            foreach ($children as $index => $edge) {
                $node = $edge['node'] ?? [];
                if (empty($node)) continue;

                $childIsVideo = (bool)($node['is_video'] ?? false);
                $childVideoUrl = $node['video_url'] ?? '';
                
                // Find highest resolution photo
                $childImageUrl = $node['display_url'] ?? '';
                if (!empty($node['display_resources']) && is_array($node['display_resources'])) {
                    $bestRes = end($node['display_resources']);
                    if (!empty($bestRes['src'])) {
                        $childImageUrl = $bestRes['src'];
                    }
                }

                $itemNum = $index + 1;

                if ($childIsVideo && !empty($childVideoUrl)) {
                    $downloads[] = [
                        'type' => 'video',
                        'label' => "Unduh Video #{$itemNum} (Kualitas Full HD)",
                        'quality' => '⭐ True HD',
                        'icon' => 'bi-download',
                        'btn_class' => 'btn-instagram',
                        'url' => $childVideoUrl,
                        'filename' => "instagram_{$author}_{$shortcode}_video_{$itemNum}.mp4"
                    ];
                    if (!empty($childImageUrl)) {
                        $gallery[] = $childImageUrl;
                    }
                } elseif (!empty($childImageUrl)) {
                    $downloads[] = [
                        'type' => 'image',
                        'label' => "Unduh Foto #{$itemNum} HD (Resolusi Penuh)",
                        'quality' => '⭐ HD Foto',
                        'icon' => 'bi-image',
                        'btn_class' => 'btn-instagram',
                        'url' => $childImageUrl,
                        'filename' => "instagram_{$author}_{$shortcode}_photo_{$itemNum}.jpg"
                    ];
                    $gallery[] = $childImageUrl;
                    $photoUrls[] = $childImageUrl;
                }
            }

            // Add ZIP Download Option if multiple photos in carousel
            if (count($photoUrls) > 1) {
                $zipQuery = http_build_query([
                    'urls' => $photoUrls,
                    'name' => "instagram_{$author}_{$shortcode}_photos.zip"
                ]);
                array_unshift($downloads, [
                    'type' => 'zip',
                    'label' => 'Unduh Semua Foto Sekaligus (' . count($photoUrls) . ' Foto Full HD)',
                    'quality' => '⭐ Paket ZIP',
                    'icon' => 'bi-file-earmark-zip-fill',
                    'btn_class' => 'btn-instagram',
                    'url' => $baseUrl . '/download/zip?' . $zipQuery,
                    'filename' => "instagram_{$author}_{$shortcode}_photos.zip"
                ]);
            }
        } else {
            // Single Post
            if ($isVideo && !empty($videoUrl)) {
                $videoInfo = MediaInspector::inspect($videoUrl);
                $resLabel = !empty($videoInfo['resolution']) ? " ({$videoInfo['resolution']})" : '';
                $sizeLabel = !empty($videoInfo['size_formatted']) ? " • {$videoInfo['size_formatted']}" : '';

                $downloads[] = [
                    'type' => 'video',
                    'label' => "Unduh Video Instagram{$resLabel} - Tanpa Pecah",
                    'quality' => '⭐ True HD' . $sizeLabel,
                    'icon' => 'bi-download',
                    'btn_class' => 'btn-instagram',
                    'url' => $videoUrl,
                    'filename' => "instagram_{$author}_{$shortcode}_HD.mp4"
                ];

                if (!empty($thumbnail)) {
                    $downloads[] = [
                        'type' => 'image',
                        'label' => 'Unduh Thumbnail HD (Resolusi Penuh)',
                        'quality' => 'HD Image',
                        'icon' => 'bi-image',
                        'btn_class' => 'btn-download-cover',
                        'url' => $thumbnail,
                        'filename' => "instagram_{$author}_{$shortcode}_thumb.jpg"
                    ];
                    $gallery[] = $thumbnail;
                }
            } else {
                // SINGLE PHOTO POST
                $bestImageUrl = $thumbnail;
                if (!empty($media['display_resources']) && is_array($media['display_resources'])) {
                    $bestRes = end($media['display_resources']);
                    if (!empty($bestRes['src'])) {
                        $bestImageUrl = $bestRes['src'];
                    }
                }

                if (!empty($bestImageUrl)) {
                    $downloads[] = [
                        'type' => 'image',
                        'label' => 'Unduh Foto Instagram HD (Resolusi Penuh)',
                        'quality' => '⭐ HD Foto',
                        'icon' => 'bi-image',
                        'btn_class' => 'btn-instagram',
                        'url' => $bestImageUrl,
                        'filename' => "instagram_{$author}_{$shortcode}.jpg"
                    ];
                    $gallery[] = $bestImageUrl;
                }
            }
        }

        $resText = 'Full HD';
        if ($isSidecar) {
            $resText = count($gallery) > 0 ? (count($gallery) . ' Foto/Media') : 'Carousel HD';
        } elseif (!$isVideo) {
            $resText = 'Foto HD 1080p';
        }

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'media_type' => $isSidecar ? 'carousel' : ($isVideo ? 'video' : 'photo'),
            'title' => $caption,
            'author' => $author,
            'author_avatar' => $avatar,
            'duration' => $duration,
            'resolution' => $resText,
            'thumbnail' => !empty($gallery[0]) ? $gallery[0] : $thumbnail,
            'preview_video' => ($isVideo && !$isSidecar) ? $videoUrl : null,
            'gallery' => $gallery,
            'stats' => [
                'likes' => $likes,
                'comments' => $comments
            ],
            'downloads' => $downloads
        ];
    }

    private function extractViaSnapsave(string $url): ?array
    {
        $ch = curl_init('https://snapsave.app/action.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'url' => $url,
                'action' => 'post'
            ]),
            CURLOPT_HTTPHEADER => [
                'User-Agent: ' . self::USER_AGENT,
                'Referer: https://snapsave.app/',
                'Origin: https://snapsave.app',
                'X-Requested-With: XMLHttpRequest'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 15
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || empty($res)) {
            return null;
        }

        // Check if packed
        if (!preg_match('/\("([^"]+)",(\d+),"([^"]+)",(\d+),(\d+),(\d+)\)/', $res, $m)) {
            return null;
        }

        $unpacked = $this->unpackSnapsave($m[1], (int)$m[2], $m[3], (int)$m[4], (int)$m[5], (int)$m[6]);
        if (empty($unpacked) || str_contains($unpacked, 'Error:')) {
            return null;
        }

        $unpacked = stripslashes($unpacked);

        $cleanId = substr(md5($url), 0, 8);
        $downloads = [];
        $gallery = [];
        $photoUrls = [];
        $baseUrl = Config::getBaseUrl();

        // 1. Check card-based items (.download-items)
        if (preg_match_all('/<div\s+class="download-items"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/i', $unpacked, $mCards) && !empty($mCards[1])) {
            $itemIdx = 1;
            foreach ($mCards[1] as $cardHtml) {
                $thumbUrl = '';
                if (preg_match('/<img[^>]*src="([^"]+)"/i', $cardHtml, $mImg)) {
                    $thumbUrl = html_entity_decode($mImg[1]);
                }
                
                $itemUrl = '';
                if (preg_match('/<a\b[^>]*href="([^"]+)"/i', $cardHtml, $mA)) {
                    $itemUrl = html_entity_decode($mA[1]);
                }
                if (empty($itemUrl) || str_starts_with($itemUrl, 'javascript:') || $itemUrl === '#') {
                    continue;
                }

                $title = '';
                if (preg_match('/title="([^"]*)"/i', $cardHtml, $mT)) {
                    $title = $mT[1];
                }

                $isPhoto = (stripos($title, 'Photo') !== false 
                    || stripos($title, 'Image') !== false 
                    || stripos($cardHtml, 'icon-dlimage') !== false
                    || preg_match('/\.(?:jpg|jpeg|png|webp)/i', $itemUrl));

                if ($isPhoto) {
                    $downloads[] = [
                        'type' => 'image',
                        'label' => "Unduh Foto #{$itemIdx} HD (Resolusi Penuh)",
                        'quality' => '⭐ HD Foto',
                        'icon' => 'bi-image',
                        'btn_class' => 'btn-instagram',
                        'url' => $itemUrl,
                        'filename' => "instagram_{$cleanId}_photo_{$itemIdx}.jpg"
                    ];
                    $photoUrls[] = $itemUrl;
                    $displayImg = !empty($thumbUrl) ? $thumbUrl : $itemUrl;
                    if (!in_array($displayImg, $gallery, true)) {
                        $gallery[] = $displayImg;
                    }
                } else {
                    $downloads[] = [
                        'type' => 'video',
                        'label' => "Unduh Video #{$itemIdx} (Kualitas HD)",
                        'quality' => '⭐ True HD',
                        'icon' => 'bi-download',
                        'btn_class' => 'btn-instagram',
                        'url' => $itemUrl,
                        'filename' => "instagram_{$cleanId}_video_{$itemIdx}.mp4"
                    ];
                    if (!empty($thumbUrl) && !in_array($thumbUrl, $gallery, true)) {
                        $gallery[] = $thumbUrl;
                    }
                }
                $itemIdx++;
            }
        } elseif (preg_match_all('/<tr[^>]*>([\s\S]*?)<\/tr>/i', $unpacked, $mRows) && !empty($mRows[1])) {
            // Table-based video formats
            $itemIdx = 1;
            foreach ($mRows[1] as $rowHtml) {
                if (!preg_match('/<a\b[^>]*href="([^"]+)"/i', $rowHtml, $mA)) {
                    continue;
                }
                $itemUrl = html_entity_decode($mA[1]);
                if (empty($itemUrl) || str_starts_with($itemUrl, 'javascript:') || $itemUrl === '#') {
                    continue;
                }

                $quality = 'HD Video';
                if (preg_match('/<td[^>]*class="[^"]*video-quality[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $rowHtml, $mQ)) {
                    $quality = trim(strip_tags($mQ[1]));
                } elseif (preg_match('/<td[^>]*>([\s\S]*?)<\/td>/i', $rowHtml, $mQ)) {
                    $quality = trim(strip_tags($mQ[1]));
                }

                $downloads[] = [
                    'type' => 'video',
                    'label' => "Unduh Video Instagram ({$quality})",
                    'quality' => '⭐ ' . $quality,
                    'icon' => 'bi-download',
                    'btn_class' => 'btn-instagram',
                    'url' => $itemUrl,
                    'filename' => "instagram_{$cleanId}_video_{$itemIdx}.mp4"
                ];
                $itemIdx++;
            }
        } else {
            // Fallback: parse all valid <a> download links
            if (preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*>([\s\S]*?)<\/a>/i', $unpacked, $mLinks, PREG_SET_ORDER)) {
                $itemIdx = 1;
                foreach ($mLinks as $l) {
                    $itemUrl = html_entity_decode($l[1]);
                    if (empty($itemUrl) || str_starts_with($itemUrl, 'javascript:') || $itemUrl === '#' || str_contains($itemUrl, 'google.com')) {
                        continue;
                    }
                    $linkText = strip_tags($l[2] ?? '');
                    $isPhoto = (stripos($linkText, 'Photo') !== false || stripos($linkText, 'Image') !== false || preg_match('/\.(?:jpg|jpeg|png|webp)/i', $itemUrl));

                    if ($isPhoto) {
                        $downloads[] = [
                            'type' => 'image',
                            'label' => "Unduh Foto #{$itemIdx} HD",
                            'quality' => '⭐ HD Foto',
                            'icon' => 'bi-image',
                            'btn_class' => 'btn-instagram',
                            'url' => $itemUrl,
                            'filename' => "instagram_{$cleanId}_photo_{$itemIdx}.jpg"
                        ];
                        $photoUrls[] = $itemUrl;
                        $gallery[] = $itemUrl;
                    } else {
                        $downloads[] = [
                            'type' => 'video',
                            'label' => "Unduh Video #{$itemIdx} HD",
                            'quality' => '⭐ True HD',
                            'icon' => 'bi-download',
                            'btn_class' => 'btn-instagram',
                            'url' => $itemUrl,
                            'filename' => "instagram_{$cleanId}_video_{$itemIdx}.mp4"
                        ];
                    }
                    $itemIdx++;
                }
            }
        }

        // Add ZIP download if multiple photos
        if (count($photoUrls) > 1) {
            $zipQuery = http_build_query([
                'urls' => $photoUrls,
                'name' => "instagram_{$cleanId}_photos.zip"
            ]);
            array_unshift($downloads, [
                'type' => 'zip',
                'label' => 'Unduh Semua Foto Sekaligus (' . count($photoUrls) . ' Foto Full HD)',
                'quality' => '⭐ Paket ZIP',
                'icon' => 'bi-file-earmark-zip-fill',
                'btn_class' => 'btn-instagram',
                'url' => $baseUrl . '/download/zip?' . $zipQuery,
                'filename' => "instagram_{$cleanId}_photos.zip"
            ]);
        }

        if (empty($downloads)) {
            return null;
        }

        $firstVideo = null;
        foreach ($downloads as $d) {
            if ($d['type'] === 'video') {
                $firstVideo = $d['url'];
                break;
            }
        }

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'media_type' => count($downloads) > 1 ? 'carousel' : (!empty($firstVideo) ? 'video' : 'photo'),
            'title' => 'Instagram Post',
            'author' => 'Instagram Creator',
            'author_avatar' => '',
            'duration' => null,
            'resolution' => count($downloads) > 1 ? (count($downloads) . ' Berkas HD') : 'HD',
            'thumbnail' => !empty($gallery[0]) ? $gallery[0] : '',
            'preview_video' => $firstVideo,
            'gallery' => $gallery,
            'stats' => ['likes' => 0, 'comments' => 0],
            'downloads' => $downloads
        ];
    }

    private function extractViaEmbedHtml(string $shortcode): ?array
    {
        $ch = curl_init("https://www.instagram.com/p/{$shortcode}/embed/captioned/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 12
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || empty($html)) {
            return null;
        }

        $imageUrl = '';
        $videoUrl = '';

        // Check for EmbeddedMediaImage
        if (preg_match('/class="EmbeddedMediaImage"[^>]*src="([^"]+)"/i', $html, $mImg)) {
            $imageUrl = html_entity_decode($mImg[1]);
        } elseif (preg_match('/<img[^>]*class="[^"]*EmbeddedMedia[^"]*"[^>]*src="([^"]+)"/i', $html, $mImg)) {
            $imageUrl = html_entity_decode($mImg[1]);
        }

        // Check for EmbeddedMediaVideo
        if (preg_match('/class="EmbeddedMediaVideo"[^>]*src="([^"]+)"/i', $html, $mVid)) {
            $videoUrl = html_entity_decode($mVid[1]);
        }

        if (empty($imageUrl) && empty($videoUrl)) {
            return null;
        }

        // Extract author and caption if available
        $author = 'Instagram Creator';
        if (preg_match('/class="EmbeddedMediaHeader[^"]*"[^>]*>.*?href="[^"]*\/([^\/"]+)\/"/is', $html, $mAuth)) {
            $author = $mAuth[1];
        }

        $caption = 'Instagram Media';
        if (preg_match('/class="Caption"[^>]*>(.*?)<\/div>/is', $html, $mCap)) {
            $caption = trim(strip_tags($mCap[1]));
        }

        $downloads = [];
        $gallery = [];

        if (!empty($videoUrl)) {
            $downloads[] = [
                'type' => 'video',
                'label' => 'Unduh Video Instagram (Kualitas HD)',
                'quality' => '⭐ True HD',
                'icon' => 'bi-download',
                'btn_class' => 'btn-instagram',
                'url' => $videoUrl,
                'filename' => "instagram_{$author}_{$shortcode}_HD.mp4"
            ];
        }

        if (!empty($imageUrl)) {
            $downloads[] = [
                'type' => 'image',
                'label' => 'Unduh Foto Instagram HD (Resolusi Penuh)',
                'quality' => '⭐ HD Foto',
                'icon' => 'bi-image',
                'btn_class' => 'btn-instagram',
                'url' => $imageUrl,
                'filename' => "instagram_{$author}_{$shortcode}.jpg"
            ];
            $gallery[] = $imageUrl;
        }

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'media_type' => !empty($videoUrl) ? 'video' : 'photo',
            'title' => $caption,
            'author' => $author,
            'author_avatar' => '',
            'duration' => null,
            'resolution' => !empty($videoUrl) ? 'Full HD' : 'Foto HD 1080p',
            'thumbnail' => $imageUrl,
            'preview_video' => $videoUrl ?: null,
            'gallery' => $gallery,
            'stats' => ['likes' => 0, 'comments' => 0],
            'downloads' => $downloads
        ];
    }

    private function unpackSnapsave(string $h, int $u, string $n, int $t, int $e, int $r): string
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/";
        $g = str_split($chars);
        $sliceH = array_slice($g, 0, $e);

        $res = "";
        $len = strlen($h);
        $i = 0;
        $n_arr = str_split($n);
        $delimiter = $n_arr[$e] ?? '';

        while ($i < $len) {
            $s = "";
            while ($i < $len && $h[$i] !== $delimiter) {
                $s .= $h[$i];
                $i++;
            }
            $i++;

            for ($j = 0; $j < count($n_arr); $j++) {
                $s = str_replace($n_arr[$j], (string)$j, $s);
            }

            if ($s !== "") {
                $d_arr = array_reverse(str_split($s));
                $val = 0;
                foreach ($d_arr as $c => $b) {
                    $idx = array_search($b, $sliceH);
                    if ($idx !== false) {
                        $val += $idx * pow($e, $c);
                    }
                }
                $charCode = (int)$val - $t;
                if ($charCode > 0 && $charCode < 65536) {
                    $res .= chr($charCode);
                }
            }
        }

        return urldecode($res);
    }

    private function extractViaYtDlp(string $url, string $sessionId = ''): ?array
    {
        $escapedUrl = escapeshellarg($url);
        $cookieParam = '';

        if (!empty($sessionId)) {
            $cookieFile = Config::getStoragePath('cache' . DIRECTORY_SEPARATOR . 'ig_cookie.txt');
            file_put_contents($cookieFile, "# Netscape HTTP Cookie File\n.instagram.com\tTRUE\t/\tTRUE\t0\tsessionid\t{$sessionId}\n");
            $cookieParam = ' --cookies ' . escapeshellarg($cookieFile);
        }

        $command = "python -m yt_dlp --skip-download --dump-single-json --no-warnings{$cookieParam} {$escapedUrl} 2>&1";
        $output = shell_exec($command);

        if (!$output) {
            return null;
        }

        $data = json_decode($output, true);
        if (!$data) {
            return null;
        }

        $id = $data['id'] ?? substr(md5($url), 0, 8);
        $title = $data['title'] ?? 'Instagram Post';
        $cover = $data['thumbnail'] ?? '';
        $author = $data['uploader'] ?? ($data['channel'] ?? 'Instagram User');
        $videoUrl = $data['url'] ?? '';

        $entries = $data['entries'] ?? [];
        $downloads = [];
        $gallery = [];
        $photoUrls = [];
        $baseUrl = Config::getBaseUrl();

        if (!empty($entries) && is_array($entries)) {
            foreach ($entries as $idx => $entry) {
                $num = $idx + 1;
                $eUrl = $entry['url'] ?? '';
                $eThumb = $entry['thumbnail'] ?? '';
                $isEntryVideo = !empty($entry['vcodec']) && $entry['vcodec'] !== 'none';

                if ($isEntryVideo && !empty($eUrl)) {
                    $downloads[] = [
                        'type' => 'video',
                        'label' => "Unduh Video #{$num} (Kualitas HD)",
                        'quality' => '⭐ True HD',
                        'icon' => 'bi-download',
                        'btn_class' => 'btn-instagram',
                        'url' => $eUrl,
                        'filename' => "instagram_{$author}_{$id}_video_{$num}.mp4"
                    ];
                } else {
                    $targetImg = $eUrl ?: $eThumb;
                    if (!empty($targetImg)) {
                        $downloads[] = [
                            'type' => 'image',
                            'label' => "Unduh Foto #{$num} HD (Resolusi Penuh)",
                            'quality' => '⭐ HD Foto',
                            'icon' => 'bi-image',
                            'btn_class' => 'btn-instagram',
                            'url' => $targetImg,
                            'filename' => "instagram_{$author}_{$id}_photo_{$num}.jpg"
                        ];
                        $gallery[] = $targetImg;
                        $photoUrls[] = $targetImg;
                    }
                }
            }
        } elseif (!empty($videoUrl)) {
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Instagram - Tanpa Pecah",
                'quality' => '⭐ True HD',
                'icon' => 'bi-download',
                'btn_class' => 'btn-instagram',
                'url' => $videoUrl,
                'filename' => "instagram_{$id}_HD.mp4"
            ];
            if (!empty($cover)) {
                $gallery[] = $cover;
            }
        } elseif (!empty($cover)) {
            $downloads[] = [
                'type' => 'image',
                'label' => "Unduh Foto Instagram HD (Resolusi Penuh)",
                'quality' => '⭐ HD Foto',
                'icon' => 'bi-image',
                'btn_class' => 'btn-instagram',
                'url' => $cover,
                'filename' => "instagram_{$id}.jpg"
            ];
            $gallery[] = $cover;
        }

        if (count($photoUrls) > 1) {
            $zipQuery = http_build_query([
                'urls' => $photoUrls,
                'name' => "instagram_{$author}_{$id}_photos.zip"
            ]);
            array_unshift($downloads, [
                'type' => 'zip',
                'label' => 'Unduh Semua Foto Sekaligus (' . count($photoUrls) . ' Foto Full HD)',
                'quality' => '⭐ Paket ZIP',
                'icon' => 'bi-file-earmark-zip-fill',
                'btn_class' => 'btn-instagram',
                'url' => $baseUrl . '/download/zip?' . $zipQuery,
                'filename' => "instagram_{$author}_{$id}_photos.zip"
            ]);
        }

        if (empty($downloads)) {
            return null;
        }

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'media_type' => count($downloads) > 1 ? 'carousel' : (!empty($videoUrl) ? 'video' : 'photo'),
            'title' => $title,
            'author' => $author,
            'author_avatar' => '',
            'duration' => isset($data['duration']) ? gmdate('i:s', (int)$data['duration']) : null,
            'resolution' => !empty($videoUrl) ? 'Full HD' : (count($gallery) > 0 ? count($gallery) . ' Foto HD' : 'Foto HD'),
            'thumbnail' => !empty($gallery[0]) ? $gallery[0] : $cover,
            'preview_video' => $videoUrl ?: null,
            'gallery' => $gallery,
            'stats' => [
                'likes' => (int)($data['like_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0)
            ],
            'downloads' => $downloads
        ];
    }
}
