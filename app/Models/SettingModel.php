<?php
/**
 * VDownload Pro - Settings Storage Model
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Models;

use App\Config\Config;

class SettingModel
{
    private static function getFilePath(): string
    {
        return Config::getStoragePath('settings.json');
    }

    public static function get(): array
    {
        $file = self::getFilePath();
        $defaults = [
            'instagram_sessionid' => '',
            'custom_user_agent' => '',
            'enable_yt_dlp' => true,
            'auto_compress' => true,
            'compression_quality' => 'balanced',
            'audio_bitrate' => '128k',
            'theme' => 'light'
        ];

        if (!file_exists($file)) {
            return $defaults;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? array_merge($defaults, $data) : $defaults;
    }

    public static function save(array $settings): bool
    {
        $current = self::get();
        $updated = array_merge($current, $settings);
        $file = self::getFilePath();

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return file_put_contents($file, json_encode($updated, JSON_PRETTY_PRINT)) !== false;
    }

    public static function getInstagramSessionId(): string
    {
        $settings = self::get();
        return trim((string)($settings['instagram_sessionid'] ?? ''));
    }
}
