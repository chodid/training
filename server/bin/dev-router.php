<?php

declare(strict_types=1);

/**
 * Router für den PHP-Built-in-Server (nur lokal): vorhandene Dateien aus public/ direkt ausliefern,
 * alles andere an index.php. Aufruf in server/: php -S 127.0.0.1:8080 -t public bin/dev-router.php
 * Favicon und Manifest bekommen dieselben Content-Types wie unter Apache (public/.htaccess, AddType).
 */

$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$file = dirname(__DIR__) . '/public' . $path;
if ($path !== '/' && !str_contains($path, '..') && is_file($file)) {
    $types = ['ico' => 'image/x-icon', 'webmanifest' => 'application/manifest+json'];
    $type = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
    if ($type === null) {
        return false;
    }
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($file));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        readfile($file);
    }

    return true;
}

require dirname(__DIR__) . '/public/index.php';
