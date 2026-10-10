<?php
/**
 * VDownload Pro - Application Configuration
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Config;

class Config
{
    public const APP_NAME = 'VDownload Pro';
    public const APP_VERSION = '2.1.0';
    public const APP_AUTHOR = 'cs.baguosps@gmail.com';
    public const APP_TAGLINE = 'Unduh Video TikTok, Instagram & Shopee Tanpa Ribet';

    public static function getBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        return rtrim($dir, '/');
    }

    public static function getBaseUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = self::getBasePath();
        return "{$scheme}://{$host}{$basePath}";
    }

    public static function getAppRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function getStoragePath(string $subpath = ''): string
    {
        $base = self::getAppRoot() . DIRECTORY_SEPARATOR . 'storage';
        return $subpath !== '' ? $base . DIRECTORY_SEPARATOR . ltrim($subpath, '/\\') : $base;
    }

    public static function getSupportedPlatforms(): array
    {
        return [
            'youtube' => [
                'name' => 'YouTube',
                'badge' => 'Video & Musik',
                'color' => '#ff0000',
                'icon' => 'bi-youtube',
                'features' => ['Video 1080p Full HD', 'HD 720p', 'Audio Musik MP3/M4A', 'Cover Thumbnail HD'],
                'examples' => ['https://www.youtube.com/watch?v=...', 'https://youtu.be/...', 'https://www.youtube.com/shorts/...']
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'badge' => 'TikTok HD',
                'color' => '#00f2fe',
                'icon' => 'bi-tiktok',
                'features' => ['No Watermark', 'HD Quality', 'Audio MP3', 'Cover Image'],
                'examples' => ['https://www.tiktok.com/@user/video/123456...', 'https://vt.tiktok.com/ZS.../']
            ],
            'instagram' => [
                'name' => 'Instagram',
                'badge' => 'Reels & Video',
                'color' => '#f09433',
                'icon' => 'bi-instagram',
                'features' => ['Reels Video', 'Post Video', 'Thumbnail High-Res', 'Fast Extraction'],
                'examples' => ['https://www.instagram.com/reel/C8h0YdKP2fC/', 'https://www.instagram.com/p/C.../']
            ],
            'shopee' => [
                'name' => 'Shopee Video',
                'badge' => 'Shopee Video',
                'color' => '#ee4d2d',
                'icon' => 'bi-bag-check-fill',
                'features' => ['Shopee Video Original', 'Kualitas Penuh', 'Cover HD', 'Direct MP4 Stream'],
                'examples' => ['https://sv.shopee.co.id/share-video/...', 'https://s.shopee.co.id/...']
            ]
        ];
    }
}
