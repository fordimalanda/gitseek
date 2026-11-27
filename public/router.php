<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Static files → let PHP's built-in server serve them
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

// API
if (str_starts_with($path, '/api')) {
    require __DIR__ . '/api.php';
    return true;
}

// SPA shell
require __DIR__ . '/index.php';
return true;