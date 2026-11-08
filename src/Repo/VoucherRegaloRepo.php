<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class VoucherRegaloRepo
{
    private static bool $tableReady = false;

    public static function ensureTable(): void
    {
        if (self::$tableReady) return;
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS voucher_regalos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(24) NOT NULL,
            importe_cents INT UNSIGNED NOT NULL,
            destinatario_nombre VARCHAR(160) NOT NULL,
            destinatario_dni VARCHAR(30) NOT NULL,
            destinatario_telefono VARCHAR(40) NOT NULL,
            remitente_nombre VARCHAR(160) NOT NULL,
            remitente_telefono VARCHAR(40) NOT NULL,
            forma_pago VARCHAR(40) NOT NULL,
            banco_cuenta_id INT UNSIGNED DEFAULT NULL,
            sucursal_id INT UNSIGNED DEFAULT NULL,
            caja_apertura_id INT UNSIGNED DEFAULT NULL,
            estado ENUM('activo','canjeado','anulado') NOT NULL DEFAULT 'activo',
            factura_id INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            cobrado_at DATETIME DEFAULT NULL,
            canjeado_at DATETIME DEFAULT NULL,
            UNIQUE KEY uq_voucher_regalos_codigo (codigo),
            KEY idx_voucher_regalos_estado (estado),
            KEY idx_voucher_regalos_factura (factura_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $columns = array_column(Db::pdo()->query('SHOW COLUMNS FROM voucher_regalos')->fetchAll(), 'Field');
        if (!in_array('caja_apertura_id', $columns, true)) {
            Db::pdo()->exec('ALTER TABLE voucher_regalos ADD COLUMN caja_apertura_id INT UNSIGNED DEFAULT NULL AFTER sucursal_id');
        }
        self::$tableReady = true;
    }

    public function crear(array $data): array
    {
        self::ensureTable();
        $pdo = Db::pdo();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $codigo = 'VR-' . strtoupper(bin2hex(random_bytes(10)));
            try {
                $st = $pdo->prepare('INSERT INTO voucher_regalos
                    (codigo, importe_cents, destinatario_nombre, destinatario_dni, destinatario_telefono, remitente_nombre, remitente_telefono, forma_pago, banco_cuenta_id, sucursal_id, caja_apertura_id, estado, created_by, created_at, cobrado_at)
                    VALUES (:codigo, :importe, :destNombre, :destDni, :destTelefono, :remNombre, :remTelefono, :forma, :banco, :sucursal, :apertura, \'activo\', :usuario, NOW(), NOW())');
                $st->execute([
                    ':codigo'=>$codigo,
                    ':importe'=>(int)$data['importe_cents'],
                    ':destNombre'=>trim((string)$data['destinatario_nombre']),
                    ':destDni'=>trim((string)$data['destinatario_dni']),
                    ':destTelefono'=>trim((string)$data['destinatario_telefono']),
                    ':remNombre'=>trim((string)$data['remitente_nombre']),
                    ':remTelefono'=>trim((string)$data['remitente_telefono']),
                    ':forma'=>trim((string)$data['forma_pago']),
                    ':banco'=>(int)($data['banco_cuenta_id'] ?? 0) ?: null,
                    ':sucursal'=>(int)($data['sucursal_id'] ?? 0) ?: null,
                    ':apertura'=>(int)($data['caja_apertura_id'] ?? 0) ?: null,
                    ':usuario'=>(int)($data['created_by'] ?? 0) ?: null,
                ]);
                return $this->findByCode($codigo) ?? throw new \RuntimeException('No se pudo recuperar el voucher generado.');
            } catch (\PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1062 || $attempt === 4) throw $e;
            }
        }
        throw new \RuntimeException('No se pudo generar un código único para el voucher.');
    }

    public function findByCode(string $codigo): ?array
    {
        self::ensureTable();
        $st = Db::pdo()->prepare('SELECT * FROM voucher_regalos WHERE codigo=:codigo LIMIT 1');
        $st->execute([':codigo'=>strtoupper(trim($codigo))]);
        return $st->fetch() ?: null;
    }

    public function findAvailableByCode(string $codigo): ?array
    {
        self::ensureTable();
        $st = Db::pdo()->prepare("SELECT * FROM voucher_regalos WHERE codigo=:codigo AND estado='activo' LIMIT 1");
        $st->execute([':codigo'=>strtoupper(trim($codigo))]);
        return $st->fetch() ?: null;
    }

    public function disponibles(int $limit = 2000): array
    {
        self::ensureTable();
        $limit = max(1, min(5000, $limit));
        return Db::pdo()->query("SELECT codigo, destinatario_nombre, importe_cents
            FROM voucher_regalos WHERE estado='activo'
            ORDER BY destinatario_nombre ASC, codigo ASC LIMIT {$limit}")->fetchAll();
    }

    public function recientes(int $limit = 100): array
    {
        self::ensureTable();
        $limit = max(1, min(500, $limit));
        return Db::pdo()->query('SELECT v.*, s.nomsuc AS sucursal_nombre FROM voucher_regalos v LEFT JOIN admin_sucursales s ON s.id=v.sucursal_id ORDER BY v.id DESC LIMIT ' . $limit)->fetchAll();
    }

    public function anularEmision(int $id): void
    {
        self::ensureTable();
        Db::pdo()->prepare("UPDATE voucher_regalos SET estado='anulado' WHERE id=:id AND estado='activo' LIMIT 1")->execute([':id'=>$id]);
    }

    /** Debe ejecutarse dentro de la transacción de creación de factura. */
    public static function canjearEnFactura(string $codigo, int $importeCents, int $facturaId): void
    {
        $codigo = strtoupper(trim($codigo));
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT id, importe_cents, estado FROM voucher_regalos WHERE codigo=:codigo FOR UPDATE');
        $st->execute([':codigo'=>$codigo]);
        $voucher = $st->fetch();
        if (!$voucher || (string)$voucher['estado'] !== 'activo') {
            throw new \RuntimeException('El código de voucher no existe o ya fue canjeado.');
        }
        if ((int)$voucher['importe_cents'] !== $importeCents) {
            throw new \RuntimeException('El monto imputado no coincide con el valor del voucher.');
        }
        $upd = $pdo->prepare("UPDATE voucher_regalos SET estado='canjeado', factura_id=:factura, canjeado_at=NOW() WHERE id=:id AND estado='activo'");
        $upd->execute([':factura'=>$facturaId, ':id'=>(int)$voucher['id']]);
        if ($upd->rowCount() !== 1) {
            throw new \RuntimeException('El voucher ya fue canjeado en otra venta.');
        }
    }
}
