<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }

    public static function check(?string $token): void
    {
        $ok = is_string($token) && isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
        if (!$ok) {
            Response::html(View::page('errors/400.php', ['message' => 'Token CSRF invalido. Refresca la pagina e intenta de nuevo.']), 400);
            exit;
        }
    }
}
