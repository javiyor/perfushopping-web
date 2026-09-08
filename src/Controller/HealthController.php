<?php
declare(strict_types=1);
namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Infra\Db;

final class HealthController
{
    public function check(array $params): void
    {
        $token = $_GET['token'] ?? '';
        $expected = getenv('HEALTH_TOKEN') ?: '';
        if ($expected !== '' && $token !== $expected) { http_response_code(403); echo json_encode(['ok'=>false]); return; }
        $dbOk = false; try { Db::pdo()->query('SELECT 1'); $dbOk=true; } catch(\Throwable $e) {}
        $storageOk = is_writable(__DIR__ . '/../../storage') || is_writable(sys_get_temp_dir());
        header('Content-Type: application/json');
        echo json_encode(['ok'=>$dbOk && $storageOk, 'db'=>$dbOk?'ok':'fail', 'storage'=>$storageOk?'ok':'fail', 'time'=>date('c')]);
    }
}
