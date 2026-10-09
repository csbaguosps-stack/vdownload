<?php
/**
 * VDownload Pro - Downloader Interface
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 */
declare(strict_types=1);

namespace App\Core;

interface DownloaderInterface
{
    /**
     * Determine if this downloader handles the provided URL
     */
    public function supports(string $url): bool;

    /**
     * Extract video and media metadata from the URL
     *
     * @param string $url
     * @return array Standardized array of media metadata and download options
     */
    public function extract(string $url): array;
}
