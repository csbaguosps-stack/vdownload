<?php
declare(strict_types=1);

/**
 * VDownload Pro - Video Downloader MVC Application
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 * Built with PHP 8.2 and Bootstrap 5.3
 */

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Jakarta');

// PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\DownloadController;

// CORS handling for browser extension & cross-origin requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origin}");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request immediately
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $router = new Router();

    // Web routes
    $router->get('', [HomeController::class, 'index']);
    $router->get('history', [HomeController::class, 'history']);
    $router->get('guide', [HomeController::class, 'guide']);
    $router->get('extension', [HomeController::class, 'extension']);
    $router->get('extension/download', [HomeController::class, 'downloadExtension']);
    $router->get('settings', [HomeController::class, 'settings']);
    $router->post('settings/save', [HomeController::class, 'saveSettings']);
    $router->post('settings/clear-cache', [HomeController::class, 'clearCompressionCache']);
    $router->post('history/clear', [HomeController::class, 'clearHistory']);
    $router->get('history/delete', [HomeController::class, 'deleteHistoryItem']);
    $router->post('history/delete', [HomeController::class, 'deleteHistoryItem']);

    // API & Streaming routes
    $router->post('download/fetch', [DownloadController::class, 'fetch']);
    $router->post('download/compress', [DownloadController::class, 'compress']);
    $router->get('download/file', [DownloadController::class, 'file']);
    $router->get('download/zip', [DownloadController::class, 'zip']);
    $router->post('download/zip', [DownloadController::class, 'zip']);

    // Run router
    $router->dispatch();
} catch (\Throwable $e) {
    http_response_code(500);
    $isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Server Error: ' . $e->getMessage()
        ]);
    } else {
        echo '<div style="font-family:sans-serif;padding:2rem;background:#1a1a1a;color:#fff;border-radius:12px;margin:2rem auto;max-width:600px;">';
        echo '<h2 style="color:#ff4757;">Aplikasi Mengalami Kendala</h2>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
}
