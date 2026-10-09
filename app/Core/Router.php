<?php
/**
 * VDownload Pro - Core Router
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => []
    ];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $cleanPath = trim($path, '/');
        $this->routes[$method][$cleanPath] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = $this->getCurrentPath();

        if (isset($this->routes[$method][$path])) {
            $this->executeHandler($this->routes[$method][$path]);
            return;
        }

        // Try route matching with parameters if needed
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->executeHandler($handler, $params);
                return;
            }
        }

        // Not found
        http_response_code(404);
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Endpoint tidak ditemukan']);
        } else {
            $controller = new \App\Controllers\HomeController();
            $controller->notFound();
        }
    }

    private function getCurrentPath(): string
    {
        // 1. Check $_GET['_route'] from .htaccess rewrite
        if (isset($_GET['_route']) && !empty($_GET['_route'])) {
            return trim($_GET['_route'], '/');
        }

        // 2. Parse from REQUEST_URI
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = Config::getBasePath();

        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        return trim($uri, '/');
    }

    private function executeHandler(array $handler, array $params = []): void
    {
        [$controllerClass, $method] = $handler;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} tidak ditemukan");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} tidak ditemukan pada {$controllerClass}");
        }

        call_user_func_array([$controller, $method], $params);
    }

    private function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }
}
