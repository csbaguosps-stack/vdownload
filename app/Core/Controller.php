<?php
/**
 * VDownload Pro - Base Controller
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

abstract class Controller
{
    /**
     * Render a view file with data wrapped in layout
     */
    protected function render(string $view, array $data = []): void
    {
        // Extract variables for view access
        extract($data);
        
        $baseUrl = Config::getBaseUrl();
        $appName = Config::APP_NAME;
        $appTagline = Config::APP_TAGLINE;
        
        $viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        $headerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
        $navbarPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'navbar.php';
        $footerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo "<h1>View {$view} not found.</h1>";
            return;
        }

        // Output complete page
        require $headerPath;
        require $navbarPath;
        require $viewPath;
        require $footerPath;
    }

    /**
     * Return a standardized JSON response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to another path
     */
    protected function redirect(string $path): void
    {
        $baseUrl = Config::getBaseUrl();
        $cleanPath = ltrim($path, '/');
        header("Location: {$baseUrl}/{$cleanPath}");
        exit;
    }
}
