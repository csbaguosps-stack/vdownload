<?php
/**
 * VDownload Pro - Header Layout
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);
use App\Config\Config;
$baseUrl = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle ?? Config::APP_NAME) ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Aplikasi download video gratis untuk TikTok (tanpa watermark), Instagram Reels & Video, dan Shopee Video dalam kualitas HD.">
    <meta name="keywords" content="download video tiktok tanpa watermark, unduh video shopee, instagram reel downloader, tiktok mp3, vdownload pro">
    <meta name="author" content="cs.baguosps@gmail.com">
    <meta name="theme-color" content="#2563eb">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (Modern White & Blue Theme) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=2.2">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='25' fill='%232563eb'/><path d='M35 25 L75 50 L35 75 Z' fill='%23ffffff'/></svg>">
</head>
<body class="bg-modern-white-blue text-dark">
<script>
    // Global App Configuration for JS
    window.APP_CONFIG = {
        baseUrl: "<?= $baseUrl ?>"
    };
</script>
