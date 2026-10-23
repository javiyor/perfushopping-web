<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class OrdenPagoRepo
{
    private static bool $ctacteAjustesTableReady = false;

    private function ensureCtacteAjustesTable(): void
    {
        if (self::$ctacteAjustesTableReady) return;
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS orden_pago_ctacte_ajustes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            orden_pago_id INT UNSIGNED NOT NULL,
            movimiento_id INT UNSIGNED NOT NULL,
            monto_cents BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_op_ctacte_ajuste (orden_pago_id, movimiento_id),
            KEY idx_op_ctacte_ajuste_mov (movimiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$ctacteAjustesTableReady = true;
    }

    private function ensurePagosBanco(): void
    {
        try {
            $like = Db::pdo()->prepare('SHOW COLUMNS FROM `orden_pago_pagos` LIKE :c');
            $like->execute([':c' => 'banco_cuenta_id']);
            if ($like->fetch() === false) {
                Db::pdo()->exec('ALTER TABLE `orden_pago_pagos` ADD COLUMN `banco_cuenta_id` INT UNSIGNED DEFAULT NULL');
            }
        } catch (\Throwable $e) {
        }
    }

    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = [];

        if (trim($q) !== '') {
            $where[] = '(o.codigo LIKE :like OR o.proveedor_nombre LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'o.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT o.*, a.nombre AS created_by_nombre
            FROM ordenes_pago o
            LEFT JOIN admin_users a ON a.id = o.created_by
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT o.*, a.nombre AS created_by_nombre
            FROM ordenes_pago o
            LEFT JOIN admin_users a ON a.id = o.created_by
            WHERE o.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function pagos(int $ordenPagoId): array
    {
        $this->ensurePagosBanco();
        $st = Db::pdo()->prepare('
            SELECT op.*, c.tipo AS cheque_tipo, c.banco_emisor, c.numero_cheque, c.estado AS cheque_estado, c.titular AS cheque_titular,
                   bc.banco AS banco_nombre, bc.numero_cuenta AS banco_cuenta
            FROM orden_pago_pagos op
            LEFT JOIN cheques c ON c.id = op.cheque_id
            LEFT JOIN banco_cuentas bc ON bc.id = op.banco_cuenta_id
            WHERE op.orden_pago_id = :o ORDER BY op.id ASC
        ');
        $st->execute([':o' => $ordenPagoId]);
        return $st->fetchAll();
    }

    public function nextCodigo(): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM ordenes_pago WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'OP-' . date('Y') . '-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function findProveedores(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $st = Db::pdo()->prepare('
            SELECT idprovee, codprove, razon, cuit FROM proveedo
            WHERE razon LIKE :like OR codprove LIKE :like OR cuit LIKE :like
            ORDER BY razon ASC LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    /** @param array<int, array{factura_compra_id:int, monto_cents:int}> $compras */
    public function asignarCompras(int $ordenPagoId, array $compras): void
    {
        if (!$compras) {
            return;
        }
        $st = Db::pdo()->prepare('
            INSERT INTO orden_pago_compras (orden_pago_id, factura_compra_id, monto_cents, created_at)
            VALUES (:op, :compra, :monto, NOW())
        ');
        foreach ($compras as $c) {
            $st->execute([
                ':op' => $ordenPagoId,
                ':compra' => (int)$c['factura_compra_id'],
                ':monto' => (int)$c['monto_cents'],
            ]);
        }
    }

    /** Total asignado a una factura de compra (centavos). */
    public function totalAsignadoCompra(int $facturaCompraId): int
    {
        $st = Db::pdo()->prepare("SELECT COALESCE(SUM(opc.monto_cents), 0)
            FROM orden_pago_compras opc
            INNER JOIN ordenes_pago o ON o.id = opc.orden_pago_id AND o.estado = 'pagada'
            INNER JOIN factura_compra fc ON fc.id = opc.factura_compra_id AND COALESCE(fc.estado, '') <> 'anulada'
            WHERE opc.factura_compra_id = :c AND UPPER(fc.tipo) NOT LIKE '%NOTA%CR%DITO%' AND UPPER(fc.tipo) NOT LIKE 'NC%'");
        $st->execute([':c' => $facturaCompraId]);
        return (int)$st->fetchColumn();
    }

    /** Total de órdenes de pago vigentes aplicado a un ajuste manual de cta. cte. */
    public function totalAsignadoAjuste(int $movimientoId): int
    {
        $this->ensureCtacteAjustesTable();
        $st = Db::pdo()->prepare("SELECT COALESCE(SUM(a.monto_cents), 0)
            FROM orden_pago_ctacte_ajustes a
            INNER JOIN ordenes_pago o ON o.id=a.orden_pago_id AND o.estado='pagada'
            WHERE a.movimiento_id=:m");
        $st->execute([':m' => $movimientoId]);
        return (int)$st->fetchColumn();
    }

    /** @param array<int,int> $proveedorIds @return array<int,int> proveedor_id => monto asignado en centavos */
    public function ajustesAsignadosPorProveedor(array $proveedorIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $proveedorIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) return [];
        $this->ensureCtacteAjustesTable();
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $st = Db::pdo()->prepare("SELECT o.proveedor_id, COALESCE(SUM(a.monto_cents), 0) AS total
            FROM orden_pago_ctacte_ajustes a
            INNER JOIN ordenes_pago o ON o.id=a.orden_pago_id AND o.estado='pagada'
            WHERE o.proveedor_id IN ({$marks})
            GROUP BY o.proveedor_id");
        $st->execute($ids);
        $out = [];
        foreach ($st->fetchAll() ?: [] as $row) $out[(int)$row['proveedor_id']] = (int)$row['total'];
        return $out;
    }

    /** @param array<int,array{movimiento_id:int,monto_cents:int}> $ajustes */
    public function asignarAjustes(int $ordenPagoId, array $ajustes): void
    {
        if (!$ajustes) return;
        $this->ensureCtacteAjustesTable();
        $st = Db::pdo()->prepare('INSERT INTO orden_pago_ctacte_ajustes (orden_pago_id, movimiento_id, monto_cents) VALUES (:o, :m, :c)');
        foreach ($ajustes as $ajuste) {
            $movimientoId = (int)($ajuste['movimiento_id'] ?? 0);
            $montoCents = (int)($ajuste['monto_cents'] ?? 0);
            if ($movimientoId <= 0 || $montoCents <= 0) continue;
            $st->execute([':o' => $ordenPagoId, ':m' => $movimientoId, ':c' => $montoCents]);
        }
    }

    public function create(array $data, array $pagos, int $createdBy): int
    {
        $this->ensurePagosBanco();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO ordenes_pago (codigo, proveedor_id, proveedor_nombre, fecha, monto_cents, estado, concepto, created_by, created_at, updated_at)
                VALUES (:codigo, :prov_id, :prov_nombre, :fecha, :monto, :estado, :concepto, :cb, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':prov_id' => $data['proveedor_id'],
                ':prov_nombre' => $data['proveedor_nombre'],
                ':fecha' => $data['fecha'],
                ':monto' => (int)($data['monto_cents'] ?? 0),
                ':estado' => $data['estado'] ?? 'pendiente',
                ':concepto' => $data['concepto'] ?? null,
                ':cb' => $createdBy,
            ]);
            $id = (int)$pdo->lastInsertId();

            $stp = $pdo->prepare('
                INSERT INTO orden_pago_pagos (orden_pago_id, forma_pago, cheque_id, banco_cuenta_id, monto_cents)
                VALUES (:oid, :forma, :chq, :banco, :monto)
            ');
            foreach ($pagos as $p) {
                $stp->execute([
                    ':oid' => $id,
                    ':forma' => $p['forma_pago'],
                    ':chq' => $p['cheque_id'] ?? null,
                    ':banco' => $p['banco_cuenta_id'] ?? null,
                    ':monto' => (int)($p['monto_cents'] ?? 0),
                ]);
            }

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateEstado(int $id, string $estado): void
    {
        $st = Db::pdo()->prepare('UPDATE ordenes_pago SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM orden_pago_pagos WHERE orden_pago_id = :i')->execute([':i' => $id]);
        $pdo->prepare('DELETE FROM ordenes_pago WHERE id = :i LIMIT 1')->execute([':i' => $id]);
    }
}
