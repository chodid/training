<?php

declare(strict_types=1);

/**
 * Router für den PHP-Built-in-Server (nur lokal): vorhandene Dateien aus public/ direkt ausliefern,
 * alles andere an index.php. Aufruf in server/: php -S 127.0.0.1:8080 -t public bin/dev-router.php
 */

$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if ($path !== '/' && !str_contains($path, '..') && is_file(dirname(__DIR__) . '/public' . $path)) {
    return false;
}

require dirname(__DIR__) . '/public/index.php';
