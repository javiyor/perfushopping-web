<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PagoFacturaAutorizacionRepo
{
    private static bool $tableReady = false;

    private function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS factura_pago_autorizaciones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            factura_id INT UNSIGNED NOT NULL,
            factura_codigo VARCHAR(60) NOT NULL,
            requested_by INT UNSIGNED NOT NULL,
            requested_by_nombre VARCHAR(120) DEFAULT NULL,
            motivo VARCHAR(500) NOT NULL,
            original_hash CHAR(64) NOT NULL,
            original_json LONGTEXT NOT NULL,
            pagos_json LONGTEXT NOT NULL,
            cheques_json LONGTEXT NOT NULL,
            status ENUM('pendiente','procesando','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
            decided_by INT UNSIGNED DEFAULT NULL,
            decided_by_nombre VARCHAR(120) DEFAULT NULL,
            decided_at DATETIME DEFAULT NULL,
            rejection_note VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_fpa_status (status),
            KEY idx_fpa_factura (factura_id),
            KEY idx_fpa_requested_by (requested_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$tableReady = true;
    }

    public function tienePendiente(int $facturaId): bool
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM factura_pago_autorizaciones WHERE factura_id = :f AND status IN ('pendiente','procesando')");
        $st->execute([':f' => $facturaId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function crear(
        int $facturaId,
        string $facturaCodigo,
        int $requestedBy,
        string $requestedByNombre,
        string $motivo,
        string $originalHash,
        array $original,
        array $pagos,
        array $cheques
    ): int {
        $this->ensureTable();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $invoiceLock = $pdo->prepare('SELECT id FROM facturas WHERE id = :f LIMIT 1 FOR UPDATE');
            $invoiceLock->execute([':f' => $facturaId]);
            if (!$invoiceLock->fetchColumn()) {
                throw new \RuntimeException('Factura no encontrada.');
            }
            $check = $pdo->prepare("SELECT id FROM factura_pago_autorizaciones WHERE factura_id = :f AND status IN ('pendiente','procesando') LIMIT 1 FOR UPDATE");
            $check->execute([':f' => $facturaId]);
            if ($check->fetchColumn()) {
                throw new \RuntimeException('Ya existe una solicitud de corrección de pagos pendiente para esta factura.');
            }
            $st = $pdo->prepare('INSERT INTO factura_pago_autorizaciones
                (factura_id, factura_codigo, requested_by, requested_by_nombre, motivo, original_hash, original_json, pagos_json, cheques_json, status, created_at, updated_at)
                VALUES (:factura, :codigo, :requested_by, :requested_by_nombre, :motivo, :hash, :original, :pagos, :cheques, \'pendiente\', NOW(), NOW())');
            $st->execute([
                ':factura' => $facturaId,
                ':codigo' => $facturaCodigo,
                ':requested_by' => $requestedBy,
                ':requested_by_nombre' => mb_substr($requestedByNombre, 0, 120),
                ':motivo' => mb_substr(trim($motivo), 0, 500),
                ':hash' => $originalHash,
                ':original' => json_encode($original, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':pagos' => json_encode($pagos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':cheques' => json_encode($cheques, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function pendientes(int $limit = 40): array
    {
        $this->ensureTable();
        $limit = max(1, min(200, $limit));
        return Db::pdo()->query("SELECT * FROM factura_pago_autorizaciones WHERE status = 'pendiente' ORDER BY created_at ASC LIMIT {$limit}")->fetchAll();
    }

    public function propias(int $adminId, int $limit = 30): array
    {
        $this->ensureTable();
        $limit = max(1, min(100, $limit));
        $st = Db::pdo()->prepare("SELECT * FROM factura_pago_autorizaciones WHERE requested_by = :u ORDER BY id DESC LIMIT {$limit}");
        $st->execute([':u' => $adminId]);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare('SELECT * FROM factura_pago_autorizaciones WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function actualizarPropiaPendiente(int $id, int $requestedBy, string $motivo, array $pagos, array $cheques): void
    {
        $this->ensureTable();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT requested_by, status FROM factura_pago_autorizaciones WHERE id = :id LIMIT 1 FOR UPDATE');
            $check->execute([':id' => $id]);
            $row = $check->fetch();
            if (!$row || (int)$row['requested_by'] !== $requestedBy || (string)$row['status'] !== 'pendiente') {
                throw new \RuntimeException('La solicitud ya no está pendiente o no pertenece a tu usuario.');
            }
            $st = $pdo->prepare("UPDATE factura_pago_autorizaciones
                SET motivo = :motivo, pagos_json = :pagos, cheques_json = :cheques, updated_at = NOW()
                WHERE id = :id AND requested_by = :u AND status = 'pendiente' LIMIT 1");
            $st->execute([
                ':motivo' => mb_substr(trim($motivo), 0, 500),
                ':pagos' => json_encode($pagos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':cheques' => json_encode($cheques, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':id' => $id,
                ':u' => $requestedBy,
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function aprobarPropiaDirectamente(int $id, int $adminId, string $adminNombre): void
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_pago_autorizaciones
            SET status = 'aprobada', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), updated_at = NOW()
            WHERE id = :id AND requested_by = :admin2 AND status = 'pendiente' LIMIT 1");
        $st->execute([
            ':admin' => $adminId,
            ':nombre' => mb_substr($adminNombre, 0, 120),
            ':id' => $id,
            ':admin2' => $adminId,
        ]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud propia dejó de estar pendiente; no se aplicó la corrección.');
        }
    }

    public function cerrarPendientesPorCorreccionDirecta(int $facturaId, int $adminId, string $adminNombre, string $nota): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_pago_autorizaciones
            SET status = 'rechazada', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), rejection_note = :nota, updated_at = NOW()
            WHERE factura_id = :factura AND status = 'pendiente'")
            ->execute([
                ':admin' => $adminId,
                ':nombre' => mb_substr($adminNombre, 0, 120),
                ':nota' => mb_substr($nota, 0, 255),
                ':factura' => $facturaId,
            ]);
    }

    public function tomarParaAprobar(int $id, int $adminId, string $adminNombre): array
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_pago_autorizaciones
            SET status = 'procesando', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), updated_at = NOW()
            WHERE id = :id AND status = 'pendiente' AND requested_by <> :admin2 LIMIT 1");
        $st->execute([
            ':admin' => $adminId,
            ':nombre' => mb_substr($adminNombre, 0, 120),
            ':id' => $id,
            ':admin2' => $adminId,
        ]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud no está pendiente o no podés aprobar tu propia solicitud.');
        }
        $get = Db::pdo()->prepare('SELECT * FROM factura_pago_autorizaciones WHERE id = :id LIMIT 1');
        $get->execute([':id' => $id]);
        $row = $get->fetch();
        if (!$row) {
            throw new \RuntimeException('Solicitud de pagos no encontrada.');
        }
        return $row;
    }

    public function aprobar(int $id): void
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_pago_autorizaciones SET status = 'aprobada', updated_at = NOW() WHERE id = :id AND status = 'procesando' LIMIT 1");
        $st->execute([':id' => $id]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('No se pudo cerrar la solicitud como aprobada.');
        }
    }

    public function rechazarProcesando(int $id, string $nota): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_pago_autorizaciones SET status = 'rechazada', rejection_note = :nota, updated_at = NOW() WHERE id = :id AND status = 'procesando' LIMIT 1")
            ->execute([':nota' => mb_substr($nota, 0, 255), ':id' => $id]);
    }

    public function restaurarPendiente(int $id): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_pago_autorizaciones SET status = 'pendiente', decided_by = NULL, decided_by_nombre = NULL, decided_at = NULL, updated_at = NOW() WHERE id = :id AND status = 'procesando' LIMIT 1")
            ->execute([':id' => $id]);
    }

    public function rechazar(int $id, int $adminId, string $adminNombre, string $nota): void
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_pago_autorizaciones
            SET status = 'rechazada', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), rejection_note = :nota, updated_at = NOW()
            WHERE id = :id AND status = 'pendiente' AND requested_by <> :admin2 LIMIT 1");
        $st->execute([
            ':admin' => $adminId,
            ':nombre' => mb_substr($adminNombre, 0, 120),
            ':nota' => mb_substr($nota, 0, 255),
            ':id' => $id,
            ':admin2' => $adminId,
        ]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud no está pendiente o no podés rechazar tu propia solicitud.');
        }
    }
}
