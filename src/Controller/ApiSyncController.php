<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\SyncRepo;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

final class ApiSyncController
{
    public function sync(array $params): void
    {
        $token = Env::get('SYNC_TOKEN', '');
        if ($token === '') {
            Response::json(['ok' => false, 'error' => 'SYNC_TOKEN not configured'], 500);
            return;
        }

        $provided = $this->getToken();
        if ($provided === '' || !hash_equals($token, $provided)) {
            Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            Response::json(['ok' => false, 'error' => 'Empty body'], 400);
            return;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            Response::json(['ok' => false, 'error' => 'Invalid JSON'], 400);
            return;
        }

        $products = $json['products'] ?? [];
        $gustos = $json['gustos'] ?? [];
        $images = $json['images'] ?? [];
        $stockResumen = $json['stock_resumen'] ?? [];

        if (!is_array($products) || !is_array($gustos) || !is_array($images) || !is_array($stockResumen)) {
            Response::json(['ok' => false, 'error' => 'Payload must contain arrays: products, gustos, images, stock_resumen'], 400);
            return;
        }

        $repo = new SyncRepo();
        try {
            $result = $repo->sync($products, $gustos, $images, $stockResumen);
            Response::json(['ok' => true] + $result, 200);
        } catch (\Throwable $e) {
            $repo->log('sync_error: ' . $e->getMessage());
            Response::json(['ok' => false, 'error' => 'Sync failed'], 500);
        }
    }

    private function getToken(): string
    {
        $h = $this->headers();
        $auth = $h['authorization'] ?? '';
        if (is_string($auth) && stripos($auth, 'bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        $t = $h['x-sync-token'] ?? '';
        if (is_string($t)) {
            return trim($t);
        }
        return '';
    }

    /** @return array<string,string> */
    private function headers(): array
    {
        $out = [];
        foreach ($_SERVER as $k => $v) {
            if (!is_string($k) || !is_string($v)) {
                continue;
            }
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $out[$name] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE']) && is_string($_SERVER['CONTENT_TYPE'])) {
            $out['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['AUTHORIZATION']) && is_string($_SERVER['AUTHORIZATION'])) {
            $out['authorization'] = $_SERVER['AUTHORIZATION'];
        }
        return $out;
    }
}
