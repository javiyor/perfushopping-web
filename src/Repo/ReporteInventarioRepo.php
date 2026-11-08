<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ReporteInventarioRepo
{
    private static bool $tableReady = false;

    private function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS reportes_inventario_mensual (
            periodo_mes DATE NOT NULL PRIMARY KEY,
            fecha_captura DATE NOT NULL,
            valor_cents BIGINT NOT NULL DEFAULT 0,
            unidades DECIMAL(18,3) NOT NULL DEFAULT 0,
            productos_con_stock INT UNSIGNED NOT NULL DEFAULT 0,
            formula_version TINYINT UNSIGNED NOT NULL DEFAULT 3,
            capturado_por INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_reportes_inventario_fecha (fecha_captura)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->ensureSnapshotColumn('productos_con_stock', 'INT UNSIGNED NOT NULL DEFAULT 0');
        $this->ensureSnapshotColumn('formula_version', 'TINYINT UNSIGNED NOT NULL DEFAULT 0');
        self::$tableReady = true;
    }

    private function ensureSnapshotColumn(string $column, string $definition): void
    {
        $st = Db::pdo()->prepare('SHOW COLUMNS FROM reportes_inventario_mensual LIKE :column_name');
        $st->execute([':column_name' => $column]);
        if ($st->fetch() === false) {
            Db::pdo()->exec('ALTER TABLE reportes_inventario_mensual ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    /**
     * Captura una foto mensual; si fue guardada con una fórmula anterior,
     * recalcula una sola vez el snapshot del mes actual.
     * Valora las existencias positivas de la tabla stock en depósitos operativos
     * (marca=2), agrupadas por producto e imputadas al precomp vigente.
     */
    public function capturarMesActual(int $adminId): ?array
    {
        $this->ensureTable();
        $pdo = Db::pdo();
        $periodo = (string)$pdo->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
        $st = $pdo->prepare('SELECT periodo_mes, fecha_captura, valor_cents, unidades, productos_con_stock, formula_version
            FROM reportes_inventario_mensual WHERE periodo_mes = :periodo LIMIT 1');
        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch();
        if ($snapshot) {
            if ((int)($snapshot['formula_version'] ?? 0) < 3) {
                $valor = $this->calcularValorActual();
                $refresh = $pdo->prepare('UPDATE reportes_inventario_mensual
                    SET fecha_captura = CURDATE(), valor_cents = :valor, unidades = :unidades,
                        productos_con_stock = :productos, formula_version = 3,
                        capturado_por = :admin, created_at = NOW()
                    WHERE periodo_mes = :periodo AND formula_version < 3');
                $refresh->execute([
                    ':valor' => $valor['valor_cents'],
                    ':unidades' => $valor['unidades'],
                    ':productos' => $valor['productos_con_stock'],
                    ':admin' => $adminId > 0 ? $adminId : null,
                    ':periodo' => $periodo,
                ]);
                $st->execute([':periodo' => $periodo]);
                $snapshot = $st->fetch() ?: $snapshot;
            }
            return $snapshot;
        }

        $valor = $this->calcularValorActual();
        $captura = $pdo->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01') AS periodo_mes, CURDATE() AS fecha_captura")->fetch() ?: [];
        $periodo = (string)($captura['periodo_mes'] ?? $periodo);
        $fechaCaptura = (string)($captura['fecha_captura'] ?? date('Y-m-d'));
        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch();
        if ($snapshot) {
            return $snapshot;
        }

        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT IGNORE INTO reportes_inventario_mensual
                (periodo_mes, fecha_captura, valor_cents, unidades, productos_con_stock, formula_version, capturado_por, created_at)
                VALUES (:periodo, :fecha, :valor, :unidades, :productos, 3, :admin, NOW())');
            $insert->execute([
                ':periodo' => $periodo,
                ':fecha' => $fechaCaptura,
                ':valor' => $valor['valor_cents'],
                ':unidades' => $valor['unidades'],
                ':productos' => $valor['productos_con_stock'],
                ':admin' => $adminId > 0 ? $adminId : null,
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch() ?: null;
        return $snapshot ?: null;
    }

    /** @return array<int,array<string,mixed>> Snapshots guardados, más antiguo primero. */
    public function historial(int $limit = 24): array
    {
        $this->ensureTable();
        $limit = max(1, min(60, $limit));
        $rows = Db::pdo()->query("SELECT periodo_mes, fecha_captura, valor_cents, unidades,
                productos_con_stock
            FROM reportes_inventario_mensual
            ORDER BY periodo_mes DESC LIMIT {$limit}")->fetchAll();
        return array_reverse($rows ?: []);
    }

    /** @return array{valor_cents:int,unidades:float,productos_con_stock:int} */
    private function calcularValorActual(): array
    {
        $sql = "
            SELECT
                COALESCE(ROUND(SUM(CASE WHEN p.precomp > 0 THEN inv.stock * p.precomp * 100 ELSE 0 END)), 0) AS valor_cents,
                COALESCE(SUM(inv.stock), 0) AS unidades,
                COUNT(DISTINCT inv.idprodu) AS productos_con_stock
            FROM (
                SELECT s.idprodu, SUM(s.stock) AS stock
                FROM stock s
                INNER JOIN deposito d ON d.iddepo = s.iddepo AND d.marca = 2
                WHERE s.stock > 0
                GROUP BY s.idprodu
                HAVING SUM(s.stock) > 0
            ) inv
            INNER JOIN producto p ON p.idprodu = inv.idprodu
        ";
        $row = Db::pdo()->query($sql)->fetch() ?: [];
        return [
            'valor_cents' => (int)($row['valor_cents'] ?? 0),
            'unidades' => (float)($row['unidades'] ?? 0),
            'productos_con_stock' => (int)($row['productos_con_stock'] ?? 0),
        ];
    }

}
