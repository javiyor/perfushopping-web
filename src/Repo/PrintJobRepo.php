<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PrintJobRepo
{
    private static ?bool $tablesReady = null;

    public static function ensureTables(): void
    {
        if (self::$tablesReady !== null) {
            return;
        }
        self::$tablesReady = true;
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS impresoras (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    nombre VARCHAR(80) NOT NULL,
                    punto_venta INT NOT NULL DEFAULT 0,
                    sucursal_id INT UNSIGNED DEFAULT NULL,
                    token VARCHAR(64) NOT NULL,
                    formato VARCHAR(10) NOT NULL DEFAULT '80mm',
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_token (token),
                    KEY idx_pv (punto_venta)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $cols = Db::pdo()->query('SHOW COLUMNS FROM impresoras')->fetchAll();
            $fields = array_column($cols, 'Field');
            $need = [
                'nombre' => 'ADD COLUMN nombre VARCHAR(80) NOT NULL',
                'punto_venta' => 'ADD COLUMN punto_venta INT NOT NULL DEFAULT 0',
                'sucursal_id' => 'ADD COLUMN sucursal_id INT UNSIGNED DEFAULT NULL',
                'token' => 'ADD COLUMN token VARCHAR(64) NOT NULL',
                'activo' => 'ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1',
                'formato' => "ADD COLUMN formato VARCHAR(10) NOT NULL DEFAULT '80mm'",
                'created_at' => 'ADD COLUMN created_at DATETIME DEFAULT NULL',
                'updated_at' => 'ADD COLUMN updated_at DATETIME DEFAULT NULL',
            ];
            foreach ($need as $col => $ddl) {
                if (!in_array($col, $fields, true)) {
                    try {
                        Db::pdo()->exec('ALTER TABLE impresoras ' . $ddl);
                    } catch (\Throwable $e) {
                        error_log('PrintJobRepo::ensureTables impresoras ' . $col . ': ' . $e->getMessage());
                    }
                }
            }
            try {
                Db::pdo()->exec('ALTER TABLE impresoras ADD UNIQUE KEY uq_token (token)');
            } catch (\Throwable $e) {
            }
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS print_jobs (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    factura_id INT UNSIGNED NOT NULL,
                    punto_venta INT NOT NULL DEFAULT 0,
                    sucursal_id INT UNSIGNED DEFAULT NULL,
                    estado ENUM('pendiente','impreso','error') NOT NULL DEFAULT 'pendiente',
                    intentos INT NOT NULL DEFAULT 0,
                    mensaje VARCHAR(255) DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL,
                    printed_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_pv_estado (punto_venta, estado),
                    KEY idx_factura (factura_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('PrintJobRepo::ensureTables error: ' . $e->getMessage());
        }
    }

    // ── Impresoras ──

    /** @return array<int,array<string,mixed>> */
    public function impresorasTodas(): array
    {
        self::ensureTables();
        try {
            return Db::pdo()->query("
                SELECT i.*, s.nomsuc AS sucursal_nombre
                FROM impresoras i
                LEFT JOIN admin_sucursales s ON s.id = i.sucursal_id
                ORDER BY i.punto_venta ASC, i.nombre ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function impresoraActivaPorPV(int $puntoVenta): ?array
    {
        self::ensureTables();
        try {
            $st = Db::pdo()->prepare("
                SELECT * FROM impresoras
                WHERE punto_venta = :pv AND activo = 1
                ORDER BY id ASC LIMIT 1
            ");
            $st->execute([':pv' => $puntoVenta]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function impresoraPorToken(string $token): ?array
    {
        self::ensureTables();
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $st = Db::pdo()->prepare('SELECT * FROM impresoras WHERE token = :t AND activo = 1 LIMIT 1');
        $st->execute([':t' => $token]);
        return $st->fetch() ?: null;
    }

    public function guardarImpresora(?int $id, string $nombre, int $puntoVenta, ?int $sucursalId, int $activo, string $formato = '80mm'): int
    {
        self::ensureTables();
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new \RuntimeException('El nombre es obligatorio.');
        }
        if ($puntoVenta <= 0) {
            throw new \RuntimeException('El punto de venta es obligatorio.');
        }
        if (!in_array($formato, ['80mm', '58mm'], true)) {
            $formato = '80mm';
        }
        if ($id) {
            Db::pdo()->prepare('
                UPDATE impresoras SET nombre = :n, punto_venta = :pv, sucursal_id = :suc,
                    formato = :f, activo = :a, updated_at = NOW() WHERE id = :id LIMIT 1
            ')->execute([':n' => $nombre, ':pv' => $puntoVenta, ':suc' => $sucursalId, ':f' => $formato, ':a' => $activo, ':id' => $id]);
            return $id;
        }
        $token = bin2hex(random_bytes(16));
        Db::pdo()->prepare('
            INSERT INTO impresoras (nombre, punto_venta, sucursal_id, token, formato, activo, created_at, updated_at)
            VALUES (:n, :pv, :suc, :t, :f, :a, NOW(), NOW())
        ')->execute([':n' => $nombre, ':pv' => $puntoVenta, ':suc' => $sucursalId, ':t' => $token, ':f' => $formato, ':a' => $activo]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function toggleImpresora(int $id): void
    {
        self::ensureTables();
        Db::pdo()->prepare('UPDATE impresoras SET activo = 1 - activo, updated_at = NOW() WHERE id = :id LIMIT 1')
            ->execute([':id' => $id]);
    }

    public function eliminarImpresora(int $id): void
    {
        self::ensureTables();
        Db::pdo()->prepare('DELETE FROM impresoras WHERE id = :id LIMIT 1')->execute([':id' => $id]);
    }

    // ── Cola ──

    public function encolar(int $facturaId, int $puntoVenta, ?int $sucursalId): int
    {
        self::ensureTables();
        try {
            // Evitar duplicados si se reintenta la emisión.
            $st = Db::pdo()->prepare("
                SELECT id FROM print_jobs
                WHERE factura_id = :f AND estado = 'pendiente' LIMIT 1
            ");
            $st->execute([':f' => $facturaId]);
            $dup = $st->fetchColumn();
            if ($dup !== false) {
                return (int)$dup;
            }
            Db::pdo()->prepare('
                INSERT INTO print_jobs (factura_id, punto_venta, sucursal_id, estado, created_at)
                VALUES (:f, :pv, :suc, \'pendiente\', NOW())
            ')->execute([':f' => $facturaId, ':pv' => $puntoVenta, ':suc' => $sucursalId]);
            return (int)Db::pdo()->lastInsertId();
        } catch (\Throwable $e) {
            error_log('PrintJobRepo::encolar error: ' . $e->getMessage());
            return 0;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function pendientesPorPV(int $puntoVenta, int $limite = 20): array
    {
        self::ensureTables();
        $limite = max(1, min(50, $limite));
        try {
            $st = Db::pdo()->prepare("
                SELECT j.*, f.codigo AS factura_codigo,
                       f.cliente_nombre, f.cliente_tele, f.total_cents
                FROM print_jobs j
                LEFT JOIN facturas f ON f.id = j.factura_id
                WHERE j.punto_venta = :pv AND j.estado = 'pendiente'
                ORDER BY j.id ASC LIMIT {$limite}
            ");
            $st->execute([':pv' => $puntoVenta]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function marcar(int $jobId, string $estado, ?string $mensaje = null): void
    {
        self::ensureTables();
        if (!in_array($estado, ['impreso', 'error', 'pendiente'], true)) {
            return;
        }
        Db::pdo()->prepare("
            UPDATE print_jobs
            SET estado = :est, mensaje = :msg, intentos = intentos + 1,
                printed_at = CASE WHEN :est = 'impreso' THEN NOW() ELSE printed_at END
            WHERE id = :id LIMIT 1
        ")->execute([':est' => $estado, ':msg' => $mensaje, ':id' => $jobId]);
    }

    public function pendientesCount(int $puntoVenta): int
    {
        self::ensureTables();
        try {
            $st = Db::pdo()->prepare("SELECT COUNT(*) FROM print_jobs WHERE punto_venta = :pv AND estado = 'pendiente'");
            $st->execute([':pv' => $puntoVenta]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
