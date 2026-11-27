<?php
declare(strict_types=1);

namespace GitSeek;

use GitSeek\Controllers\DiscoveryController;
use GitSeek\Controllers\RepoController;
use GitSeek\Support\Response;
use GitSeek\Controllers\CommitController;

final class Router
{
    public function dispatch(string $action, array $payload): void
    {
        match ($action) {
            'ping'       => Response::ok(['pong' => true, 'time' => date('c')]),
            'discover'   => (new DiscoveryController())->discover($payload),
            'repo-state' => (new RepoController())->state($payload),
            default      => Response::error("Unknown action: {$action}", 404),
            'commit' => (new CommitController())->commit($payload),
        };
    }
}