<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CtaCteClienteRepo
{
    private static bool $tableReady = false;

    private function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS ctacte_cliente_habilitaciones (
            idclien INT UNSIGNED NOT NULL PRIMARY KEY,
            habilitado TINYINT(1) NOT NULL DEFAULT 0,
            actualizado_por INT UNSIGNED DEFAULT NULL,
            actualizado_por_nombre VARCHAR(120) DEFAULT NULL,
            motivo VARCHAR(500) DEFAULT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_ctacte_cliente_habilitado (habilitado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$tableReady = true;
    }

    public function habilitado(int $idclien): bool
    {
        if ($idclien <= 0) {
            return false;
        }
        $this->ensureTable();
        $st = Db::pdo()->prepare('SELECT habilitado FROM ctacte_cliente_habilitaciones WHERE idclien = :id LIMIT 1');
        $st->execute([':id' => $idclien]);
        return (int)$st->fetchColumn() === 1;
    }

    /** @param array<int,mixed> $idclienList @return array<int,bool> */
    public function mapaHabilitaciones(array $idclienList): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idclienList), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $this->ensureTable();
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $st = Db::pdo()->prepare('SELECT idclien, habilitado FROM ctacte_cliente_habilitaciones WHERE idclien IN (' . $marks . ')');
        $st->execute($ids);
        $map = [];
        foreach ($st->fetchAll() as $row) {
            $map[(int)$row['idclien']] = (int)$row['habilitado'] === 1;
        }
        return $map;
    }

    public function actualizar(int $idclien, bool $habilitado, int $adminId, string $adminNombre, string $motivo): void
    {
        if ($idclien <= 0) {
            throw new \InvalidArgumentException('Cliente ERP inválido.');
        }
        $this->ensureTable();
        Db::pdo()->prepare('INSERT INTO ctacte_cliente_habilitaciones
            (idclien, habilitado, actualizado_por, actualizado_por_nombre, motivo, updated_at)
            VALUES (:id, :h, :admin, :nombre, :motivo, NOW())
            ON DUPLICATE KEY UPDATE habilitado = :h2, actualizado_por = :admin2, actualizado_por_nombre = :nombre2, motivo = :motivo2, updated_at = NOW()')
            ->execute([
                ':id' => $idclien,
                ':h' => $habilitado ? 1 : 0,
                ':admin' => $adminId,
                ':nombre' => mb_substr($adminNombre, 0, 120),
                ':motivo' => mb_substr(trim($motivo), 0, 500),
                ':h2' => $habilitado ? 1 : 0,
                ':admin2' => $adminId,
                ':nombre2' => mb_substr($adminNombre, 0, 120),
                ':motivo2' => mb_substr(trim($motivo), 0, 500),
            ]);
    }
}
