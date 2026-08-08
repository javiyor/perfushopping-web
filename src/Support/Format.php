<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Format
{
    public static function moneyFromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $val = number_format($cents / 100, 2, ',', '.');
        return $sign . '$' . $val;
    }

    public static function moneyRoundedFromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $val = number_format((float)round($cents / 100), 0, ',', '.');
        return $sign . '$' . $val;
    }

    public static function slugKey(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        $s = trim($s, '-');
        return $s;
    }

    public static function uploadUrl(string $nameOrUrl): string
    {
        $v = trim($nameOrUrl);
        if ($v === '' || $v === '*') {
            return '';
        }
        // Absolute URL
        if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://')) {
            return $v;
        }
        // Already a web path
        if (str_starts_with($v, '/')) {
            return $v;
        }
        // If it contains a slash, treat it as a relative path.
        if (str_contains($v, '/') || str_contains($v, '\\')) {
            return '/' . ltrim(str_replace('\\\\', '/', $v), '/');
        }
        // Plain filename stored in DB
        return '/upload/' . rawurlencode($v);
    }

    /** URL absoluta de la app (sin barra final). Usa APP_URL si está configurado, si no HTTP_HOST. */
    public static function baseUrl(): string
    {
        $app = trim((string)\Perfushopping\Web\Support\Env::get('APP_URL', ''));
        if ($app !== '') {
            return rtrim($app, '/');
        }
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return '';
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $host;
    }

    /** Convierte una URL relativa de upload en absoluta para usar en OG/redes. */
    public static function absoluteUploadUrl(string $nameOrUrl): string
    {
        $u = self::uploadUrl($nameOrUrl);
        if ($u === '' || str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            return $u;
        }
        return self::baseUrl() . $u;
    }
}
