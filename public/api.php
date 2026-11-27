<?php
declare(strict_types=1);

use GitSeek\Router;
use GitSeek\Support\Response;

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

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