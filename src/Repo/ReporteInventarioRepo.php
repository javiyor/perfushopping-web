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
            productos_sin_costo INT UNSIGNED NOT NULL DEFAULT 0,
            capturado_por INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_reportes_inventario_fecha (fecha_captura)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS reportes_inventario_mensual_sin_costo (
            periodo_mes DATE NOT NULL,
            idprodu INT UNSIGNED NOT NULL,
            codprodup VARCHAR(60) DEFAULT NULL,
            producto VARCHAR(220) NOT NULL,
            codscan TEXT DEFAULT NULL,
            precomp DECIMAL(14,2) DEFAULT NULL,
            precio DECIMAL(14,2) NOT NULL DEFAULT 0,
            precio_iva DECIMAL(14,2) NOT NULL DEFAULT 0,
            stock_unidades DECIMAL(18,3) NOT NULL DEFAULT 0,
            variantes INT UNSIGNED NOT NULL DEFAULT 0,
            detalle_version TINYINT UNSIGNED NOT NULL DEFAULT 2,
            PRIMARY KEY (periodo_mes, idprodu),
            KEY idx_reporte_inv_sin_costo_producto (idprodu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->ensureDetailColumn('codscan', 'TEXT DEFAULT NULL');
        $this->ensureDetailColumn('precio', 'DECIMAL(14,2) NOT NULL DEFAULT 0');
        $this->ensureDetailColumn('precio_iva', 'DECIMAL(14,2) NOT NULL DEFAULT 0');
        $this->ensureDetailColumn('detalle_version', 'TINYINT UNSIGNED NOT NULL DEFAULT 0');
        self::$tableReady = true;
    }

    private function ensureDetailColumn(string $column, string $definition): void
    {
        $st = Db::pdo()->prepare('SHOW COLUMNS FROM reportes_inventario_mensual_sin_costo LIKE :column_name');
        $st->execute([':column_name' => $column]);
        if ($st->fetch() === false) {
            Db::pdo()->exec('ALTER TABLE reportes_inventario_mensual_sin_costo ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    /**
     * Captura una sola foto por mes en la primera consulta de reportes.
     * Valora el stock neto de depósitos operativos (marca=2) al precomp vigente.
     */
    public function capturarMesActual(int $adminId): ?array
    {
        $this->ensureTable();
        $pdo = Db::pdo();
        $periodo = (string)$pdo->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
        $st = $pdo->prepare('SELECT * FROM reportes_inventario_mensual WHERE periodo_mes = :periodo LIMIT 1');
        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch();
        if ($snapshot) {
            return $this->anexarDetalleSinCosto($snapshot);
        }

        $valor = $this->calcularValorActual();
        $productosSinCosto = $this->productosSinCostoActual();
        $captura = $pdo->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01') AS periodo_mes, CURDATE() AS fecha_captura")->fetch() ?: [];
        $periodo = (string)($captura['periodo_mes'] ?? $periodo);
        $fechaCaptura = (string)($captura['fecha_captura'] ?? date('Y-m-d'));
        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch();
        if ($snapshot) {
            return $this->anexarDetalleSinCosto($snapshot);
        }

        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT IGNORE INTO reportes_inventario_mensual
                (periodo_mes, fecha_captura, valor_cents, unidades, productos_con_stock, productos_sin_costo, capturado_por, created_at)
                VALUES (:periodo, :fecha, :valor, :unidades, :productos, :sin_costo, :admin, NOW())');
            $insert->execute([
                ':periodo' => $periodo,
                ':fecha' => $fechaCaptura,
                ':valor' => $valor['valor_cents'],
                ':unidades' => $valor['unidades'],
                ':productos' => $valor['productos_con_stock'],
                ':sin_costo' => $valor['productos_sin_costo'],
                ':admin' => $adminId > 0 ? $adminId : null,
            ]);
            if ($insert->rowCount() > 0 && $productosSinCosto) {
                $detalle = $pdo->prepare('INSERT INTO reportes_inventario_mensual_sin_costo
                    (periodo_mes, idprodu, codprodup, producto, codscan, precomp, precio, precio_iva, stock_unidades, variantes, detalle_version)
                    VALUES (:periodo, :idprodu, :codprodup, :producto, :codscan, :precomp, :precio, :precio_iva, :stock, :variantes, 2)');
                foreach ($productosSinCosto as $producto) {
                    $detalle->execute([
                        ':periodo' => $periodo,
                        ':idprodu' => (int)$producto['idprodu'],
                        ':codprodup' => $producto['codprodup'] !== '' ? $producto['codprodup'] : null,
                        ':producto' => mb_substr((string)$producto['producto'], 0, 220),
                        ':codscan' => $producto['codscan'] !== '' ? $producto['codscan'] : null,
                        ':precomp' => $producto['precomp'],
                        ':precio' => $producto['precio'],
                        ':precio_iva' => $producto['precio_iva'],
                        ':stock' => $producto['stock_unidades'],
                        ':variantes' => $producto['variantes'],
                    ]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        $st->execute([':periodo' => $periodo]);
        $snapshot = $st->fetch() ?: null;
        return $snapshot ? $this->anexarDetalleSinCosto($snapshot) : null;
    }

    /** @return array<int,array<string,mixed>> Snapshots guardados, más antiguo primero. */
    public function historial(int $limit = 24): array
    {
        $this->ensureTable();
        $limit = max(1, min(60, $limit));
        $rows = Db::pdo()->query("SELECT periodo_mes, fecha_captura, valor_cents, unidades,
                productos_con_stock, productos_sin_costo
            FROM reportes_inventario_mensual
            ORDER BY periodo_mes DESC LIMIT {$limit}")->fetchAll();
        return array_reverse($rows ?: []);
    }

    /** @return array{valor_cents:int,unidades:float,productos_con_stock:int,productos_sin_costo:int} */
    private function calcularValorActual(): array
    {
        $sql = "
            SELECT
                COALESCE(ROUND(SUM(CASE WHEN p.precomp > 0 THEN inv.stock * p.precomp * 100 ELSE 0 END)), 0) AS valor_cents,
                COALESCE(SUM(inv.stock), 0) AS unidades,
                COUNT(DISTINCT inv.idprodu) AS productos_con_stock,
                COUNT(DISTINCT CASE WHEN p.precomp IS NULL OR p.precomp <= 0 THEN inv.idprodu END) AS productos_sin_costo
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
            'productos_sin_costo' => (int)($row['productos_sin_costo'] ?? 0),
        ];
    }

    /** Lista resumida de productos con existencia cuyo costo falta o vale cero. */
    private function productosSinCostoActual(): array
    {
        $sql = "
            SELECT p.idprodu, p.codprodup, p.produ AS producto,
                   GROUP_CONCAT(DISTINCT NULLIF(TRIM(g.codscan), '') ORDER BY g.codscan SEPARATOR ', ') AS codscan,
                   p.precomp, p.precio, COALESCE(MAX(i.tiva), 0) AS tiva,
                   SUM(s.stock) AS stock_unidades,
                   COUNT(DISTINCT s.idcodgusto) AS variantes
            FROM stock s
            INNER JOIN deposito d ON d.iddepo = s.iddepo AND d.marca = 2
            INNER JOIN producto p ON p.idprodu = s.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = s.idcodgusto
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE s.stock > 0 AND (p.precomp IS NULL OR p.precomp <= 0)
            GROUP BY p.idprodu, p.codprodup, p.produ, p.precomp, p.precio
            ORDER BY p.produ ASC
        ";
        $rows = Db::pdo()->query($sql)->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $row['precio_iva'] = round((float)($row['precio'] ?? 0) * (1 + (float)($row['tiva'] ?? 0) / 100), 2);
        }
        unset($row);
        return $rows;
    }

    private function anexarDetalleSinCosto(array $snapshot): array
    {
        $st = Db::pdo()->prepare('SELECT idprodu, codprodup, producto, codscan, precomp, precio, precio_iva, stock_unidades, variantes
            FROM reportes_inventario_mensual_sin_costo WHERE periodo_mes = :periodo AND detalle_version = 2 ORDER BY producto ASC');
        $st->execute([':periodo' => $snapshot['periodo_mes']]);
        $productos = $st->fetchAll() ?: [];
        $snapshot['productos_sin_costo'] = $productos;
        $snapshot['detalle_sin_costo_disponible'] = count($productos) > 0 || (int)($snapshot['productos_sin_costo'] ?? 0) === 0;
        if (!$snapshot['detalle_sin_costo_disponible']) {
            $snapshot['productos_sin_costo_actual'] = $this->productosSinCostoActual();
        }
        return $snapshot;
    }
}
