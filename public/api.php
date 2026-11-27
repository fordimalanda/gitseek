<?php
declare(strict_types=1);

use GitSeek\Router;
use GitSeek\Support\Response;

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Capture les erreurs fatales (syntaxe, classe introuvable, etc.) pour renvoyer du JSON
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal PHP Error: ' . $error['message'],
            'file' => $error['file'] . ':' . $error['line']
        ]);
    }
});

try {
    $action  = $_GET['action'] ?? '';
    $raw     = file_get_contents('php://input');
    $payload = $raw ? (json_decode($raw, true) ?? []) : [];

    (new Router())->dispatch($action, $payload);
} catch (\Throwable $e) {
    Response::error($e->getMessage(), 500, [
        'type' => get_class($e),
        'file' => $e->getFile() . ':' . $e->getLine()
    ]);
}