<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

final class ApiSyncTablesController
{
    public function push(array $params): void
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

        $table = (string)($json['table'] ?? '');
        $rows = $json['rows'] ?? [];

        if (!in_array($table, ['producto', 'gustos', 'stockcab', 'stockdet'], true)) {
            Response::json(['ok' => false, 'error' => "Invalid table: {$table}"], 400);
            return;
        }
        if (!is_array($rows) || count($rows) === 0) {
            Response::json(['ok' => false, 'error' => 'rows must be a non-empty array'], 400);
            return;
        }

        try {
            $pdo = Db::pdo();
            $pdo->beginTransaction();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $recalcIds = [];
            $count = 0;
            if ($table === 'producto') {
                $count = $this->syncProducto($pdo, $rows);
                foreach ($rows as $r) {
                    if (!empty($r['idprodu'])) $recalcIds[] = (int)$r['idprodu'];
                }
            } elseif ($table === 'gustos') {
                $count = $this->syncGustos($pdo, $rows);
            } elseif ($table === 'stockdet') {
                $count = $this->syncInsertIgnore($pdo, $table, $rows);
                foreach ($rows as $r) {
                    if (!empty($r['idprodu'])) $recalcIds[] = (int)$r['idprodu'];
                }
            } elseif ($table === 'stockcab') {
                $count = $this->syncInsertIgnore($pdo, $table, $rows);
            } else {
                $count = $this->syncReplace($pdo, $table, $rows);
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $pdo->commit();

            // Recalculate stock for affected products (after commit)
            $recalcIds = array_unique(array_filter($recalcIds));
            if ($recalcIds) {
                (new StockRepo())->recalcularProductos($recalcIds);
            }

            $response = ['ok' => true, 'table' => $table, 'count' => $count];
            if ($recalcIds) {
                $response['stock_recalc'] = count($recalcIds);
            }
            Response::json($response, 200);
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
            Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function syncProducto(\PDO $pdo, array $rows): int
    {
        $cols = array_keys($rows[0]);
        $setClauses = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $cols));
        $insertCols = implode(', ', $cols);
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));

        $stmt = $pdo->prepare(
            "INSERT INTO producto ({$insertCols}) VALUES ({$placeholders})
             ON DUPLICATE KEY UPDATE {$setClauses}"
        );

        $count = 0;
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $stmt->bindValue(":{$c}", $r[$c] ?? null);
            }
            $stmt->execute();
            $count++;
        }
        return $count;
    }

    private function syncGustos(\PDO $pdo, array $rows): int
    {
        $updateCols = ['nomgusto', 'codscan', 'stockact'];
        $setClauses = implode(', ', array_map(fn($c) => "{$c} = :{$c}", $updateCols));

        $updateStmt = $pdo->prepare(
            "UPDATE gustos SET {$setClauses} WHERE idcodgusto = :idcodgusto"
        );

        $count = 0;
        foreach ($rows as $r) {
            $idcodgusto = (int)($r['idcodgusto'] ?? 0);
            if ($idcodgusto <= 0) {
                continue;
            }

            // Try UPDATE first
            $updateStmt->bindValue(':idcodgusto', $idcodgusto, \PDO::PARAM_INT);
            foreach ($updateCols as $c) {
                $updateStmt->bindValue(":{$c}", $r[$c] ?? null);
            }
            $updateStmt->execute();

            if ($updateStmt->rowCount() > 0) {
                $count++;
                continue;
            }

            // INSERT new row (only synced columns, rest get defaults)
            $insertCols = array_merge(['idcodgusto'], $updateCols);
            $insCols = implode(', ', $insertCols);
            $insPlaces = implode(', ', array_map(fn($c) => ":{$c}", $insertCols));
            $insertStmt = $pdo->prepare(
                "INSERT INTO gustos ({$insCols}) VALUES ({$insPlaces})"
            );
            $insertStmt->bindValue(':idcodgusto', $idcodgusto, \PDO::PARAM_INT);
            foreach ($updateCols as $c) {
                $insertStmt->bindValue(":{$c}", $r[$c] ?? null);
            }
            $insertStmt->execute();
            $count++;
        }
        return $count;
    }

    private function syncInsertIgnore(\PDO $pdo, string $table, array $rows): int
    {
        $cols = array_keys($rows[0]);
        $insertCols = implode(', ', $cols);
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$table} ({$insertCols}) VALUES ({$placeholders})"
        );

        $count = 0;
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $stmt->bindValue(":{$c}", $r[$c] ?? null);
            }
            $stmt->execute();
            $count += $stmt->rowCount();
        }
        return $count;
    }

    private function syncReplace(\PDO $pdo, string $table, array $rows): int
    {
        $cols = array_keys($rows[0]);
        $insertCols = implode(', ', $cols);
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));

        $stmt = $pdo->prepare("REPLACE INTO {$table} ({$insertCols}) VALUES ({$placeholders})");

        $count = 0;
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $stmt->bindValue(":{$c}", $r[$c] ?? null);
            }
            $stmt->execute();
            $count++;
        }
        return $count;
    }

    public function recalcular(array $params): void
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

        try {
            $info = (new StockRepo())->recalcular();
            Response::json(['ok' => true, 'info' => $info], 200);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
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
