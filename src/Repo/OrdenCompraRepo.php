<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class OrdenCompraRepo
{
    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(o.codigo LIKE :like OR o.proveedor_nombre LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'o.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT o.*, a.nombre AS created_by_nombre, COUNT(oi.id) AS items_count
            FROM ordenes_compra o
            LEFT JOIN admin_users a ON a.id = o.created_by
            LEFT JOIN orden_compra_items oi ON oi.orden_id = o.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC, o.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT o.*, a.nombre AS created_by_nombre, c.nombre AS controlado_por_nombre
            FROM ordenes_compra o
            LEFT JOIN admin_users a ON a.id = o.created_by
            LEFT JOIN admin_users c ON c.id = o.controlado_por
            WHERE o.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $ordenId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM orden_compra_items WHERE orden_id = :o ORDER BY id ASC');
        $st->execute([':o' => $ordenId]);
        return $st->fetchAll();
    }

    public function nextCodigo(): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM ordenes_compra WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'OC-' . date('Y') . '-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO ordenes_compra (codigo, proveedor_id, proveedor_nombre, fecha, fecha_estimada, total_cents, estado, notas, created_by, created_at, updated_at)
                VALUES (:codigo, :proveedor_id, :proveedor_nombre, :fecha, :fecha_estimada, :total_cents, :estado, :notas, :created_by, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':proveedor_id' => $data['proveedor_id'],
                ':proveedor_nombre' => $data['proveedor_nombre'],
                ':fecha' => $data['fecha'],
                ':fecha_estimada' => $data['fecha_estimada'],
                ':total_cents' => $data['total_cents'],
                ':estado' => $data['estado'] ?? 'pendiente',
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
            ]);
            $id = (int)$pdo->lastInsertId();

            $sti = $pdo->prepare('
                INSERT INTO orden_compra_items (orden_id, idprodu, idcodgusto, producto, variedad, qty, unit_price_cents, total_cents)
                VALUES (:oid, :idprodu, :idcodgusto, :producto, :variedad, :qty, :unit_price, :total)
            ');
            foreach ($items as $it) {
                $sti->execute([
                    ':oid' => $id,
                    ':idprodu' => $it['idprodu'],
                    ':idcodgusto' => $it['idcodgusto'],
                    ':producto' => $it['producto'],
                    ':variedad' => $it['variedad'],
                    ':qty' => $it['qty'],
                    ':unit_price' => $it['unit_price_cents'],
                    ':total' => $it['total_cents'],
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
        $st = Db::pdo()->prepare('UPDATE ordenes_compra SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM orden_compra_items WHERE orden_id = :i')->execute([':i' => $id]);
        $pdo->prepare('DELETE FROM ordenes_compra WHERE id = :i LIMIT 1')->execute([':i' => $id]);
    }

    public function updateRecepcion(int $id, array $data): void
    {
        $st = Db::pdo()->prepare('
            UPDATE ordenes_compra SET
                fecha_recepcion = :fr,
                bultos_recibidos = :br,
                controlado_por = :cp,
                valor_declarado_cents = :vd,
                flete_cents = :fc,
                flete_pagado = :fp,
                estado = :estado,
                updated_at = NOW()
            WHERE id = :i LIMIT 1
        ');
        $st->execute([
            ':fr' => $data['fecha_recepcion'],
            ':br' => $data['bultos_recibidos'] !== null ? (int)$data['bultos_recibidos'] : null,
            ':cp' => $data['controlado_por'] !== null ? (int)$data['controlado_por'] : null,
            ':vd' => (int)($data['valor_declarado_cents'] ?? 0),
            ':fc' => (int)($data['flete_cents'] ?? 0),
            ':fp' => (int)($data['flete_pagado'] ?? 0),
            ':estado' => $data['estado'] ?? 'recibida',
            ':i' => $id,
        ]);
    }

    public function updateFleteComprobante(int $id, string $filename): void
    {
        $st = Db::pdo()->prepare('UPDATE ordenes_compra SET flete_comprobante = :fc, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':fc' => $filename, ':i' => $id]);
    }

    public function searchFletes(string $q = '', string $proveedor = '', int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [];
        $where = ['o.flete_cents > 0'];

        if (trim($q) !== '') {
            $where[] = '(o.codigo LIKE :like OR o.proveedor_nombre LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if (trim($proveedor) !== '') {
            $where[] = 'o.proveedor_nombre LIKE :prov';
            $params[':prov'] = '%' . $proveedor . '%';
        }

        $sql = '
            SELECT o.id, o.codigo, o.proveedor_nombre, o.fecha, o.flete_cents, o.flete_pagado,
                   o.flete_comprobante, o.total_cents, o.estado
            FROM ordenes_compra o
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY o.fecha DESC, o.id DESC
            LIMIT ' . $limit;
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listAdminUsers(): array
    {
        $st = Db::pdo()->query('SELECT id, nombre FROM admin_users WHERE activo = 1 ORDER BY nombre ASC');
        return $st->fetchAll();
    }

    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $pdo = Db::pdo();
        $params = [':like' => '%' . $q . '%'];

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precomp, p.codprodup, p.enweb
            FROM producto p
            WHERE p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like
            ORDER BY p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        $matchedV = null;
        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precomp, p.codprodup, p.enweb,
                       g.idcodgusto, g.nomgusto AS matched_nomgusto
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                WHERE g.codscan = :c
                LIMIT 1
            ');
            $st2->execute([':c' => $q]);
            $byCode = $st2->fetch();
            if ($byCode) {
                $matchedV = [
                    'idcodgusto' => (int)$byCode['idcodgusto'],
                    'nomgusto' => $byCode['matched_nomgusto'],
                    'codscan' => $q,
                ];
                unset($byCode['idcodgusto'], $byCode['matched_nomgusto']);
                $exists = false;
                foreach ($products as $pr) {
                    if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) array_unshift($products, $byCode);
            }
        }

        foreach ($products as $idx => $pr) {
            $idprodu = (int)$pr['idprodu'];
            $st3 = $pdo->prepare('
                SELECT idcodgusto, nomgusto, codscan, stockact
                FROM gustos
                WHERE idprodu = :id AND discont = 0
                GROUP BY nomgusto
                ORDER BY nomgusto ASC
                LIMIT 20
            ');
            $st3->execute([':id' => $idprodu]);
            $products[$idx]['variants'] = $st3->fetchAll();
        }

        if ($matchedV) {
            foreach ($products as $idx => $pr) {
                if ((int)$pr['idprodu'] === 0) continue;
                foreach (($pr['variants'] ?? []) as $v) {
                    if ((int)$v['idcodgusto'] === $matchedV['idcodgusto']) {
                        $products[$idx]['matched_variant_id'] = $matchedV['idcodgusto'];
                        $products[$idx]['matched_variant'] = $matchedV;
                        break;
                    }
                }
            }
        }

        return $products;
    }

    public function findProveedores(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT idprovee, codprove, razon, cuit
            FROM proveedo
            WHERE razon LIKE :like OR codprove LIKE :like OR cuit LIKE :like
            ORDER BY razon ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }
}
