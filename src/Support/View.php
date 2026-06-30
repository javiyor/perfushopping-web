<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $tpl, array $data): string
    {
        $tpl = ltrim($tpl, '/');

        $baseDir = defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : (string)realpath(__DIR__ . '/../..');
        $bases = [];
        if (is_string($baseDir) && $baseDir !== '') {
            $bases[] = rtrim($baseDir, '/\\') . '/templates/';
            $bases[] = rtrim(dirname($baseDir), '/\\') . '/templates/';
        }
        $bases[] = rtrim((string)realpath(__DIR__ . '/../../..'), '/\\') . '/templates/';

        $file = null;
        foreach ($bases as $b) {
            if (!is_string($b) || $b === '') {
                continue;
            }
            $cand = $b . $tpl;
            if (is_file($cand)) {
                $file = $cand;
                break;
            }
        }
        if (!is_string($file) || $file === '') {
            return 'Template not found: ' . htmlspecialchars($tpl);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    /** @param array<string,mixed> $data */
    public static function page(string $tpl, array $data = []): string
    {
        $body = self::render($tpl, $data);
        return self::render('layout.php', $data + ['body' => $body]);
    }

    /** @param array<string,mixed> $data */
    public static function adminPage(string $tpl, array $data = []): string
    {
        $body = self::render($tpl, $data);
        return self::render('admin/layout.php', $data + ['body' => $body]);
    }
}
