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
            throw new \InvalidArgumentException('Format URL Instagram tidak valid. Contoh: https://www.instagram.com/reel/C8h0YdKP2fC/');
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

        // 2. Attempt Snapsave scraper with JS unpacking
        try {
            $data = $this->extractViaSnapsave($url);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to next strategy
        }

        // 3. Attempt yt-dlp
        try {
            $data = $this->extractViaYtDlp($url, $sessionId);
            if ($data) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Continue to fallback
        }

        // 4. If all failed, provide a clear instructional message
        $hint = empty($sessionId) 
            ? ' (Tip: Instagram membatasi akses tanpa login. Anda dapat memasukkan cookie Instagram "sessionid" di Pengaturan untuk akses 100% stabil).' 
            : '';
        throw new \RuntimeException("Gagal mengambil video Instagram. Pastikan akun tidak privat dan URL valid{$hint}");
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
        $docIds = ['8845758582119845', '10015901848480474'];
        
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

        $videoInfo = !empty($videoUrl) ? MediaInspector::inspect($videoUrl) : ['accessible' => false, 'resolution' => '', 'size_formatted' => ''];
        $resLabel = !empty($videoInfo['resolution']) ? " ({$videoInfo['resolution']})" : '';
        $sizeLabel = !empty($videoInfo['size_formatted']) ? " • {$videoInfo['size_formatted']}" : '';

        $downloads = [];
        if ($isVideo && !empty($videoUrl)) {
            $downloads[] = [
                'type' => 'video',
                'label' => "Unduh Video Instagram{$resLabel} - Tanpa Pecah",
                'quality' => '⭐ True HD' . $sizeLabel,
                'icon' => 'bi-download',
                'btn_class' => 'btn-instagram',
                'url' => $videoUrl,
                'filename' => "instagram_{$author}_{$shortcode}_HD.mp4"
            ];
        }

        if (!empty($thumbnail)) {
            $downloads[] = [
                'type' => 'image',
                'label' => 'Unduh Thumbnail HD (Resolusi Penuh)',
                'quality' => 'HD Image',
                'icon' => 'bi-image',
                'btn_class' => 'btn-download-cover',
                'url' => $thumbnail,
                'filename' => "instagram_{$shortcode}_thumb.jpg"
            ];
        }

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'title' => $caption,
            'author' => $author,
            'author_avatar' => $avatar,
            'duration' => $duration,
            'resolution' => !empty($videoInfo['resolution']) ? $videoInfo['resolution'] . (!empty($videoInfo['label']) ? " ({$videoInfo['label']})" : '') : 'Full HD',
            'thumbnail' => $thumbnail,
            'preview_video' => $videoUrl,
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
        if (preg_match('/\("([^"]+)",(\d+),"([^"]+)",(\d+),(\d+),(\d+)\)/', $res, $m)) {
            $unpacked = $this->unpackSnapsave($m[1], (int)$m[2], $m[3], (int)$m[4], (int)$m[5], (int)$m[6]);
            
            // Look for video download link
            if (preg_match('/href="([^"]+)"[^>]*title="Download Video"/i', $unpacked, $mVideo) 
                || preg_match('/href="([^"]+)"[^>]*class="[^"]*download-items__btn/i', $unpacked, $mVideo)
                || preg_match('/href="([^"]+\.mp4[^"]*)"/i', $unpacked, $mVideo)) {
                
                $videoUrl = html_entity_decode($mVideo[1]);
                $thumbUrl = '';
                if (preg_match('/src="([^"]+)"[^>]*class="[^"]*download-items__thumb/i', $unpacked, $mThumb)) {
                    $thumbUrl = html_entity_decode($mThumb[1]);
                }

                $cleanId = substr(md5($url), 0, 8);
                $videoInfo = MediaInspector::inspect($videoUrl);
                $resLabel = !empty($videoInfo['resolution']) ? " ({$videoInfo['resolution']})" : '';
                $sizeLabel = !empty($videoInfo['size_formatted']) ? " • {$videoInfo['size_formatted']}" : '';

                return [
                    'platform' => 'instagram',
                    'platform_name' => 'Instagram',
                    'title' => 'Instagram Video',
                    'author' => 'Instagram Creator',
                    'author_avatar' => '',
                    'duration' => null,
                    'resolution' => !empty($videoInfo['resolution']) ? $videoInfo['resolution'] . (!empty($videoInfo['label']) ? " ({$videoInfo['label']})" : '') : 'Full HD',
                    'thumbnail' => $thumbUrl,
                    'preview_video' => $videoUrl,
                    'stats' => ['likes' => 0, 'comments' => 0],
                    'downloads' => [
                        [
                            'type' => 'video',
                            'label' => "Unduh Video Instagram{$resLabel} - Tanpa Pecah",
                            'quality' => '⭐ True HD' . $sizeLabel,
                            'icon' => 'bi-download',
                            'btn_class' => 'btn-instagram',
                            'url' => $videoUrl,
                            'filename' => "instagram_{$cleanId}_HD.mp4"
                        ]
                    ]
                ];
            }
        }

        return null;
    }

    private function unpackSnapsave(string $h, int $u, string $n, int $t, int $e, int $r): string
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/";
        $g = str_split($chars);
        $sliceH = array_slice($g, 0, $e);
        $sliceI = array_slice($g, 0, 10);

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
                // Decode base
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
            // Write temporary cookie file
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
        if (!$data || empty($data['url'])) {
            return null;
        }

        $id = $data['id'] ?? substr(md5($url), 0, 8);
        $title = $data['title'] ?? 'Instagram Video';
        $cover = $data['thumbnail'] ?? '';
        $author = $data['uploader'] ?? ($data['channel'] ?? 'Instagram User');
        $videoUrl = $data['url'];

        $videoInfo = MediaInspector::inspect($videoUrl);
        $resLabel = !empty($videoInfo['resolution']) ? " ({$videoInfo['resolution']})" : '';
        $sizeLabel = !empty($videoInfo['size_formatted']) ? " • {$videoInfo['size_formatted']}" : '';

        return [
            'platform' => 'instagram',
            'platform_name' => 'Instagram',
            'title' => $title,
            'author' => $author,
            'author_avatar' => '',
            'duration' => isset($data['duration']) ? gmdate('i:s', (int)$data['duration']) : null,
            'resolution' => !empty($videoInfo['resolution']) ? $videoInfo['resolution'] . (!empty($videoInfo['label']) ? " ({$videoInfo['label']})" : '') : 'Full HD',
            'thumbnail' => $cover,
            'preview_video' => $videoUrl,
            'stats' => [
                'likes' => (int)($data['like_count'] ?? 0),
                'comments' => (int)($data['comment_count'] ?? 0)
            ],
            'downloads' => [
                [
                    'type' => 'video',
                    'label' => "Unduh Video Instagram{$resLabel} - Tanpa Pecah",
                    'quality' => '⭐ True HD' . $sizeLabel,
                    'icon' => 'bi-download',
                    'btn_class' => 'btn-instagram',
                    'url' => $videoUrl,
                    'filename' => "instagram_{$id}_HD.mp4"
                ]
            ]
        ];
    }
}
