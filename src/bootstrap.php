<?php
declare(strict_types=1);

use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

// Base directory for resolving templates and .env.
if (!defined('APP_BASE_DIR')) {
    $candidates = [];
    $candidates[] = realpath(__DIR__ . '/..');       // expected: <base>/src
    $candidates[] = realpath(__DIR__ . '/../..');    // if src is inside public_html/src
    $candidates[] = realpath(__DIR__ . '/../../..'); // extra safety
    $base = null;
    foreach ($candidates as $cand) {
        if (!is_string($cand) || $cand === '') {
            continue;
        }
        if (is_dir($cand . '/templates') && is_dir($cand . '/src')) {
            $base = $cand;
            break;
        }
    }
    if (!is_string($base) || $base === '') {
        $base = (string)realpath(__DIR__ . '/..');
    }
    define('APP_BASE_DIR', $base);
}

// Basic autoloader
spl_autoload_register(static function (string $class): void {
    $prefix = 'Perfushopping\\Web\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $rel = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// Load .env from base dir or parent.
$envPaths = [
    rtrim((string)APP_BASE_DIR, '/\\') . '/.env',
    rtrim((string)dirname((string)APP_BASE_DIR), '/\\') . '/.env',
    __DIR__ . '/../.env',
];
foreach ($envPaths as $p) {
    if (is_string($p) && is_file($p)) {
        Env::load($p);
        break;
    }
}

ini_set('display_errors', Env::get('APP_ENV', 'local') === 'local' ? '1' : '0');
error_reporting(E_ALL);

session_name('perfushopping_web');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

set_exception_handler(static function (Throwable $e): void {
    Response::error($e);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error !== null && in_array((int)$error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        $file = (defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : (string)realpath(__DIR__ . '/..')) . '/templates/errors/maintenance.php';
        if (is_file($file)) {
            include $file;
        } else {
            echo '<h1>En mantenimiento</h1><p>Volvé a intentar en unos minutos.</p>';
        }
    }
});
