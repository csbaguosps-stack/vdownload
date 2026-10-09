<?php
/**
 * VDownload Pro - Download History Model
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Models;

use App\Config\Config;

class DownloadHistory
{
    private static function getFilePath(): string
    {
        return Config::getStoragePath('history.json');
    }

    public static function getAll(): array
    {
        $file = self::getFilePath();
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public static function add(array $item): void
    {
        $history = self::getAll();
        
        $entry = [
            'id' => uniqid('dl_', true),
            'timestamp' => time(),
            'formatted_time' => date('d M Y, H:i'),
            'platform' => $item['platform'] ?? 'video',
            'platform_name' => $item['platform_name'] ?? 'Video',
            'title' => $item['title'] ?? 'Video Download',
            'author' => $item['author'] ?? '',
            'resolution' => $item['resolution'] ?? '',
            'duration' => $item['duration'] ?? '',
            'thumbnail' => $item['thumbnail'] ?? '',
            'preview_video' => $item['preview_video'] ?? '',
            'downloads' => $item['downloads'] ?? []
        ];

        // Prepend to top and limit to 50 items
        array_unshift($history, $entry);
        $history = array_slice($history, 0, 50);

        $file = self::getFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function delete(string $id): bool
    {
        $history = self::getAll();
        $filtered = array_values(array_filter($history, fn($item) => ($item['id'] ?? '') !== $id));
        
        $file = self::getFilePath();
        return file_put_contents($file, json_encode($filtered, JSON_PRETTY_PRINT)) !== false;
    }

    public static function clear(): bool
    {
        $file = self::getFilePath();
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
}
