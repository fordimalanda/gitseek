<?php
declare(strict_types=1);

namespace GitSeek\Support;

final class Cache
{
    public static function get(string $key): mixed
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return null;
        }

        $ttl = (int) Env::get('CACHE_TTL', 10);
        if ($ttl <= 0 || (time() - filemtime($path)) > $ttl) {
            @unlink($path);
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        return json_decode($raw, true);
    }

    public static function set(string $key, mixed $value): void
    {
        $path = self::path($key);
        @file_put_contents(
            $path,
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public static function forget(string $key): void
    {
        @unlink(self::path($key));
    }

    private static function path(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) ?: 'default';
        return dirname(__DIR__, 2) . '/storage/cache/' . $safe . '.json';
    }
}