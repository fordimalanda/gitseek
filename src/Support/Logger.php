<?php
declare(strict_types=1);

namespace GitSeek\Support;

final class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warn' => 2, 'error' => 3];

    public static function debug(string $msg, array $ctx = []): void { self::write('debug', $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void  { self::write('info', $msg, $ctx); }
    public static function warn(string $msg, array $ctx = []): void  { self::write('warn', $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void { self::write('error', $msg, $ctx); }

    private static function write(string $level, string $msg, array $ctx): void
    {
        $minLevel = (string) Env::get('LOG_LEVEL', 'info');
        $min = self::LEVELS[$minLevel] ?? 1;
        if (self::LEVELS[$level] < $min) {
            return;
        }

        $line = sprintf(
            "[%s] %-5s %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $msg,
            $ctx === [] ? '' : ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $file = dirname(__DIR__, 2) . '/storage/logs/app.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}