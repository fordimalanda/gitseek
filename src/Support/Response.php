<?php
declare(strict_types=1);

namespace GitSeek\Support;

final class Response
{
    public static function ok(array $data = []): never
    {
        self::send(200, ['success' => true] + $data);
    }

    public static function error(string $message, int $code = 400, array $extra = []): never
    {
        self::send($code, ['success' => false, 'error' => $message] + $extra);
    }

    private static function send(int $code, array $body): never
    {
        http_response_code($code);
        echo json_encode(
            $body,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}