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
}
