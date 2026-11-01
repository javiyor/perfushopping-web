<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class FacturaEdicionAutorizacionRepo
{
    private static bool $tableReady = false;

    private function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS factura_edicion_autorizaciones (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            factura_id INT UNSIGNED NOT NULL,
            factura_codigo VARCHAR(60) NOT NULL,
            requested_by INT UNSIGNED NOT NULL,
            requested_by_nombre VARCHAR(120) DEFAULT NULL,
            motivo VARCHAR(500) NOT NULL,
            original_hash CHAR(64) NOT NULL,
            factura_hash CHAR(64) NOT NULL,
            original_json LONGTEXT NOT NULL,
            propuesta_json LONGTEXT NOT NULL,
            status ENUM('pendiente','procesando','aprobada','rechazada','obsoleta','error') NOT NULL DEFAULT 'pendiente',
            decided_by INT UNSIGNED DEFAULT NULL,
            decided_by_nombre VARCHAR(120) DEFAULT NULL,
            decided_at DATETIME DEFAULT NULL,
            decision_note VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_fea_status_created (status, created_at),
            KEY idx_fea_factura_status (factura_id, status),
            KEY idx_fea_requester (requested_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$tableReady = true;
    }

    public function countPendientesPara(int $adminId): int
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM factura_edicion_autorizaciones
            WHERE status IN ('pendiente','procesando','error') AND requested_by <> :admin");
        $st->execute([':admin' => $adminId]);
        return (int)$st->fetchColumn();
    }

    public function pendientesPara(int $adminId, int $limit = 50): array
    {
        $this->ensureTable();
        $limit = max(1, min(200, $limit));
        $st = Db::pdo()->prepare("SELECT a.*, f.cliente_nombre, f.fecha, f.total_cents, f.estado AS factura_estado
            FROM factura_edicion_autorizaciones a
            LEFT JOIN facturas f ON f.id = a.factura_id
            WHERE a.status IN ('pendiente','procesando','error') AND a.requested_by <> :admin
            ORDER BY a.created_at ASC LIMIT {$limit}");
        $st->execute([':admin' => $adminId]);
        return $st->fetchAll();
    }

    public function pendientePorFactura(int $facturaId): ?array
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("SELECT * FROM factura_edicion_autorizaciones
            WHERE factura_id = :factura AND status IN ('pendiente','procesando','error') ORDER BY id DESC LIMIT 1");
        $st->execute([':factura' => $facturaId]);
        return $st->fetch() ?: null;
    }

    public function crear(
        int $facturaId,
        string $facturaCodigo,
        int $requestedBy,
        string $requestedByNombre,
        string $motivo,
        string $originalHash,
        array $propuesta,
        FacturaRepo $facturaRepo
    ): int {
        $this->ensureTable();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id FROM facturas WHERE id = :id LIMIT 1 FOR UPDATE');
            $lock->execute([':id' => $facturaId]);
            if (!$lock->fetchColumn()) {
                throw new \RuntimeException('La factura ya no existe.');
            }

            $pending = $pdo->prepare("SELECT id FROM factura_edicion_autorizaciones
                WHERE factura_id = :factura AND status IN ('pendiente','procesando','error') LIMIT 1 FOR UPDATE");
            $pending->execute([':factura' => $facturaId]);
            if ($pending->fetchColumn()) {
                throw new \RuntimeException('Ya existe una solicitud de edición pendiente para esta factura.');
            }

            $snapshot = $facturaRepo->snapshotEdicion($facturaId);
            $actualHash = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            if (!hash_equals($originalHash, $actualHash)) {
                throw new \RuntimeException('La factura cambió mientras se preparaba la solicitud. Volvé a cargarla e intentá de nuevo.');
            }
            $facturaSnapshot = $snapshot;
            unset($facturaSnapshot['cheques']);
            $facturaHash = hash('sha256', json_encode($facturaSnapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

            $st = $pdo->prepare('INSERT INTO factura_edicion_autorizaciones
                (factura_id, factura_codigo, requested_by, requested_by_nombre, motivo, original_hash, factura_hash, original_json, propuesta_json, status, created_at, updated_at)
                VALUES (:factura, :codigo, :requester, :requester_nombre, :motivo, :hash, :factura_hash, :original, :propuesta, \'pendiente\', NOW(), NOW())');
            $st->execute([
                ':factura' => $facturaId,
                ':codigo' => mb_substr($facturaCodigo, 0, 60),
                ':requester' => $requestedBy,
                ':requester_nombre' => mb_substr($requestedByNombre, 0, 120),
                ':motivo' => mb_substr(trim($motivo), 0, 500),
                ':hash' => $actualHash,
                ':factura_hash' => $facturaHash,
                ':original' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':propuesta' => json_encode($propuesta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
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

    public function findPending(int $id, int $adminId): ?array
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("SELECT * FROM factura_edicion_autorizaciones
            WHERE id = :id AND status = 'pendiente' AND requested_by <> :admin LIMIT 1");
        $st->execute([':id' => $id, ':admin' => $adminId]);
        return $st->fetch() ?: null;
    }

    public function marcarObsoletaPendiente(int $id, int $adminId, string $adminNombre, string $nota): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_edicion_autorizaciones
            SET status = 'obsoleta', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), decision_note = :note, updated_at = NOW()
            WHERE id = :id AND status = 'pendiente'")
            ->execute([
                ':admin' => $adminId,
                ':nombre' => mb_substr($adminNombre, 0, 120),
                ':note' => mb_substr($nota, 0, 500),
                ':id' => $id,
            ]);
    }

    public function tomarParaAprobar(int $id, int $adminId, string $adminNombre, FacturaRepo $facturaRepo): array
    {
        $this->ensureTable();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $lookup = $pdo->prepare('SELECT factura_id FROM factura_edicion_autorizaciones WHERE id = :id LIMIT 1');
            $lookup->execute([':id' => $id]);
            $facturaId = (int)$lookup->fetchColumn();
            if ($facturaId <= 0) {
                throw new \RuntimeException('Solicitud no encontrada.');
            }

            $invoiceLock = $pdo->prepare('SELECT id FROM facturas WHERE id = :id LIMIT 1 FOR UPDATE');
            $invoiceLock->execute([':id' => $facturaId]);
            if (!$invoiceLock->fetchColumn()) {
                $staleRequest = $pdo->prepare("SELECT * FROM factura_edicion_autorizaciones WHERE id = :id LIMIT 1 FOR UPDATE");
                $staleRequest->execute([':id' => $id]);
                $request = $staleRequest->fetch();
                if (!$request || (string)$request['status'] !== 'pendiente' || (int)$request['requested_by'] === $adminId) {
                    throw new \RuntimeException('La solicitud no está pendiente o no podés aprobar tu propia solicitud.');
                }
                $this->decidirEnTransaccion($pdo, $id, $adminId, $adminNombre, 'obsoleta', 'La factura ya no existe.');
                $pdo->commit();
                throw new \RuntimeException('La factura ya no existe; la solicitud quedó obsoleta.');
            }

            $st = $pdo->prepare("SELECT * FROM factura_edicion_autorizaciones WHERE id = :id LIMIT 1 FOR UPDATE");
            $st->execute([':id' => $id]);
            $request = $st->fetch();
            if (!$request || (string)$request['status'] !== 'pendiente' || (int)$request['requested_by'] === $adminId) {
                throw new \RuntimeException('La solicitud no está pendiente o no podés aprobar tu propia solicitud.');
            }

            $actual = $facturaRepo->snapshotEdicion((int)$request['factura_id']);
            $actualHash = hash('sha256', json_encode($actual, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            if (!hash_equals((string)$request['original_hash'], $actualHash)) {
                $this->decidirEnTransaccion($pdo, $id, $adminId, $adminNombre, 'obsoleta', 'La factura cambió desde que se solicitó la edición.');
                $pdo->commit();
                throw new \RuntimeException('La factura cambió desde que se solicitó la edición; no se aplicó la propuesta.');
            }

            $claim = $pdo->prepare("UPDATE factura_edicion_autorizaciones
                SET status = 'procesando', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), updated_at = NOW()
                WHERE id = :id AND status = 'pendiente' AND requested_by <> :admin2 LIMIT 1");
            $claim->execute([
                ':admin' => $adminId,
                ':nombre' => mb_substr($adminNombre, 0, 120),
                ':id' => $id,
                ':admin2' => $adminId,
            ]);
            if ($claim->rowCount() <= 0) {
                throw new \RuntimeException('La solicitud ya fue procesada por otro administrador.');
            }
            $pdo->commit();
            return $request;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function decidirEnTransaccion(\PDO $pdo, int $id, int $adminId, string $adminNombre, string $status, string $note): void
    {
        $st = $pdo->prepare('UPDATE factura_edicion_autorizaciones
            SET status = :status, decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), decision_note = :note, updated_at = NOW()
            WHERE id = :id LIMIT 1');
        $st->execute([
            ':status' => $status,
            ':admin' => $adminId,
            ':nombre' => mb_substr($adminNombre, 0, 120),
            ':note' => mb_substr($note, 0, 500),
            ':id' => $id,
        ]);
    }

    public function aprobar(int $id): void
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_edicion_autorizaciones SET status = 'aprobada', updated_at = NOW() WHERE id = :id AND status = 'procesando' LIMIT 1");
        $st->execute([':id' => $id]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('No se pudo marcar la solicitud como aprobada.');
        }
    }

    public function marcarObsoletaProcesando(int $id, string $mensaje): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_edicion_autorizaciones
            SET status = 'obsoleta', decision_note = :note, updated_at = NOW()
            WHERE id = :id AND status = 'procesando' LIMIT 1")
            ->execute([':note' => mb_substr($mensaje, 0, 500), ':id' => $id]);
    }

    public function marcarError(int $id, string $mensaje): void
    {
        $this->ensureTable();
        Db::pdo()->prepare("UPDATE factura_edicion_autorizaciones
            SET status = 'error', decision_note = :note, updated_at = NOW()
            WHERE id = :id AND status = 'procesando' LIMIT 1")
            ->execute([':note' => mb_substr($mensaje, 0, 500), ':id' => $id]);
    }

    public function rechazar(int $id, int $adminId, string $adminNombre, string $nota): void
    {
        $this->ensureTable();
        $st = Db::pdo()->prepare("UPDATE factura_edicion_autorizaciones
            SET status = 'rechazada', decided_by = :admin, decided_by_nombre = :nombre, decided_at = NOW(), decision_note = :note, updated_at = NOW()
            WHERE id = :id AND status IN ('pendiente','error') AND requested_by <> :admin2 LIMIT 1");
        $st->execute([
            ':admin' => $adminId,
            ':nombre' => mb_substr($adminNombre, 0, 120),
            ':note' => mb_substr(trim($nota) !== '' ? trim($nota) : 'Rechazada por administración.', 0, 500),
            ':id' => $id,
            ':admin2' => $adminId,
        ]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud no está pendiente o no podés rechazar tu propia solicitud.');
        }
    }
}
