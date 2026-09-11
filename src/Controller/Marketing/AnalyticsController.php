<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\AnalyticsRepo;
use Perfushopping\Web\Support\Response;

final class AnalyticsController
{
    public function event(array $params): void
    {
        $type = trim((string)($_POST['t'] ?? $_GET['t'] ?? ''));
        if ($type === '') {
            Response::json(['ok' => false], 400);
            return;
        }
        $allowed = ['pageview', 'product_view', 'add_to_cart', 'add_routine', 'quiz_start', 'quiz_complete', 'campaign_view', 'checkout_start', 'checkout_complete'];
        if (!in_array($type, $allowed, true)) {
            Response::json(['ok' => false], 400);
            return;
        }
        $data = [
            'session_id' => $_COOKIE['pfs_sid'] ?? $_POST['sid'] ?? $_GET['sid'] ?? '',
            'url' => $_POST['u'] ?? $_GET['u'] ?? '',
            'path' => $_POST['p'] ?? $_GET['p'] ?? '',
            'product_id' => (int)($_POST['pid'] ?? $_GET['pid'] ?? 0),
            'value' => $_POST['v'] ?? $_GET['v'] ?? null,
            'payload' => $_POST['payload'] ?? $_GET['payload'] ?? null,
        ];
        try {
            (new AnalyticsRepo())->track($type, $data);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sessionPixel(array $params): void
    {
        $sid = $_GET['sid'] ?? '';
        if ($sid !== '' && !isset($_COOKIE['pfs_sid'])) {
            setcookie('pfs_sid', $sid, [
                'expires' => time() + 86400 * 30,
                'path' => '/',
                'samesite' => 'Lax',
            ]);
        }
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
}
