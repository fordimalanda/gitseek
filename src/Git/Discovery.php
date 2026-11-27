<?php
declare(strict_types=1);

namespace GitSeek\Git;

use GitSeek\Support\Cache;
use GitSeek\Support\Env;
use GitSeek\Support\Logger;

final class Discovery
{
    public static function scan(bool $useCache = true): array
    {
        if ($useCache) {
            $cached = Cache::get('discovery');
            if (is_array($cached)) {
                $cached['cached'] = true;
                return $cached;
            }
        }

        $root = (string) Env::get('PROJECTS_ROOT', '');
        if ($root === '') {
            throw new \RuntimeException('PROJECTS_ROOT is not set in .env');
        }
        if (!is_dir($root)) {
            throw new \RuntimeException("PROJECTS_ROOT does not exist: {$root}");
        }

        $root = rtrim($root, "\\/");
        $projects = [];

        $items = @scandir($root) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $root . DIRECTORY_SEPARATOR . $item;
            if (!is_dir($path)) continue;
            if (!is_dir($path . DIRECTORY_SEPARATOR . '.git')) continue;

            $projects[] = self::describe($item, $path);
        }

        usort($projects, static fn($a, $b) => strcasecmp($a['name'], $b['name']));

        $payload = [
            'root'      => $root,
            'projects'  => $projects,
            'count'     => count($projects),
            'scannedAt' => date('c'),
            'cached'    => false,
        ];

        Cache::set('discovery', $payload);
        Logger::info('Discovery scan', ['root' => $root, 'count' => count($projects)]);

        return $payload;
    }

    private static function describe(string $name, string $path): array
    {
        $base = [
            'name'        => $name,
            'path'        => $path,
            'branch'      => null,
            'detached'    => false,
            'remote'      => null,
            'dirty'       => 0,
            'untracked'   => 0,
            'ahead'       => 0,
            'behind'      => 0,
            'hasUpstream' => false,
            'error'       => null,
        ];

        try {
            $repo = new Repository($path);

            $branch       = $repo->branch();
            $status       = $repo->statusPorcelain();
            $tracking     = $repo->aheadBehind();
            $untracked    = 0;
            $dirty        = 0;

            foreach ($status as $code) {
                if ($code === '??') {
                    $untracked++;
                } else {
                    $dirty++;
                }
            }

            return array_merge($base, [
                'branch'      => $branch,
                'detached'    => $branch === 'HEAD',
                'remote'      => $repo->remote(),
                'dirty'       => $dirty,
                'untracked'   => $untracked,
                'ahead'       => $tracking['ahead'],
                'behind'      => $tracking['behind'],
                'hasUpstream' => $tracking['hasUpstream'],
            ]);
        } catch (\Throwable $e) {
            Logger::warn('Discovery: failed to describe repo', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            return array_merge($base, ['error' => $e->getMessage()]);
        }
    }
}