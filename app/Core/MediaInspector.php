<?php
/**
 * VDownload Pro - Native Media & MP4 Atom Inspector
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Core;

class MediaInspector
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * Inspect stream accessibility, exact content size, and MP4 pixel dimensions (e.g. 1080x1920, 720x1280)
     *
     * @param string $url
     * @param string|null $customUserAgent
     * @return array{accessible: bool, size: int, size_formatted: string, width: int, height: int, resolution: string, label: string}
     */
    public static function inspect(string $url, ?string $customUserAgent = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_RANGE => '0-32767',
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $customUserAgent ?? self::USER_AGENT
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if (!$res || ($code !== 200 && $code !== 206)) {
            return [
                'accessible' => false,
                'size' => 0,
                'size_formatted' => '',
                'width' => 0,
                'height' => 0,
                'resolution' => '',
                'label' => ''
            ];
        }

        $headers = substr($res, 0, $headerSize);
        $body = substr($res, $headerSize);

        // 1. Total file size from Content-Range or Content-Length
        $totalSize = 0;
        if (preg_match('/Content-Range:\s*bytes\s*\d+-\d+\/(\d+)/i', $headers, $mRange)) {
            $totalSize = (int)$mRange[1];
        } elseif (preg_match('/Content-Length:\s*(\d+)/i', $headers, $mLen)) {
            $totalSize = (int)$mLen[1];
        }

        $sizeFormatted = '';
        if ($totalSize > 0) {
            $sizeFormatted = round($totalSize / (1024 * 1024), 1) . ' MB';
        }

        // 2. Parse resolution from MP4 'tkhd' atom
        $width = 0;
        $height = 0;
        $resolution = '';
        $label = '';

        $pos = strpos($body, 'tkhd');
        if ($pos !== false && strlen($body) >= $pos + 88) {
            $ver = ord($body[$pos + 4]);
            $offset = ($ver === 0) ? ($pos + 80) : ($pos + 92);
            if (strlen($body) >= $offset + 8) {
                $width = (unpack('N', substr($body, $offset, 4))[1] ?? 0) >> 16;
                $height = (unpack('N', substr($body, $offset + 4, 4))[1] ?? 0) >> 16;
                if ($width > 0 && $height > 0) {
                    $resolution = "{$width}x{$height}";
                    $maxDim = max($width, $height);
                    if ($maxDim >= 1920) {
                        $label = '1080p Full HD';
                    } elseif ($maxDim >= 1280) {
                        $label = '720p HD';
                    } elseif ($maxDim >= 854) {
                        $label = '480p SD';
                    } else {
                        $label = 'SD';
                    }
                }
            }
        }

        return [
            'accessible' => true,
            'size' => $totalSize,
            'size_formatted' => $sizeFormatted,
            'width' => $width,
            'height' => $height,
            'resolution' => $resolution,
            'label' => $label
        ];
    }
}
