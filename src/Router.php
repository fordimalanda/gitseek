<?php
declare(strict_types=1);

namespace GitSeek;

use GitSeek\Controllers\DiscoveryController;
use GitSeek\Support\Response;

final class Router
{
    public function dispatch(string $action, array $payload): void
    {
        match ($action) {
            'ping'     => Response::ok(['pong' => true, 'time' => date('c')]),
            'discover' => (new DiscoveryController())->discover($payload),
            default    => Response::error("Unknown action: {$action}", 404),
        };
    }
}