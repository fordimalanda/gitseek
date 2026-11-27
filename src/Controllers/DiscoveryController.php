<?php
declare(strict_types=1);

namespace GitSeek\Controllers;

use GitSeek\Git\Discovery;
use GitSeek\Support\Cache;
use GitSeek\Support\Response;

final class DiscoveryController
{
    public function discover(array $payload): void
    {
        $refresh = (bool) ($payload['refresh'] ?? false);

        if ($refresh) {
            Cache::forget('discovery');
        }

        try {
            $result = Discovery::scan(useCache: !$refresh);
            Response::ok($result);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}