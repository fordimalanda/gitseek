<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

use GitSeek\Support\Response;
use GitSeek\Router;

try {
    $action  = $_GET['action'] ?? '';
    $raw     = file_get_contents('php://input');
    $payload = $raw ? (json_decode($raw, true) ?? []) : [];

    (new Router())->dispatch($action, $payload);
} catch (\Throwable $e) {
    Response::error($e->getMessage(), 500, [
        'type' => get_class($e),
    ]);
}