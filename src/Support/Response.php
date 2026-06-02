<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Response
{
    public static function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
    }

    /** @param array<string,mixed> $data */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $to): void
    {
        header('Location: ' . $to, true, 302);
        exit;
    }

    public static function notFound(): void
    {
        self::html(View::render('errors/404.php', []), 404);
    }

    public static function error(\Throwable $e): void
    {
        $env = Env::get('APP_ENV', 'local');
        $msg = $env === 'local' ? (string)$e : 'Error interno.';
        self::html(View::render('errors/500.php', ['message' => $msg]), 500);
    }
}
