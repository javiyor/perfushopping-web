<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class RemitoRepo
{
    public function search(string $q = '', string $tipo = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);
        $tipo = trim($tipo);
        $estado = trim($estado);
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(r.codigo LIKE :like OR r.cliente_nombre LIKE :like OR r.proveedor_nombre LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($tipo !== '') {
            $where[] = 'r.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($estado !== '') {
            $where[] = 'r.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT r.*, a.nombre AS created_by_nombre, COUNT(ri.id) AS items_count
            FROM remitos r
            LEFT JOIN admin_users a ON a.id = r.created_by
            LEFT JOIN remito_items ri ON ri.remito_id = r.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY r.id ORDER BY r.created_at DESC, r.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT r.*, a.nombre AS created_by_nombre
            FROM remitos r
            LEFT JOIN admin_users a ON a.id = r.created_by
            WHERE r.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $remitoId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM remito_items WHERE remito_id = :r ORDER BY id ASC');
        $st->execute([':r' => $remitoId]);
        return $st->fetchAll();
    }

    public function nextCodigo(string $tipo = 'salida'): string
    {
        $prefix = $tipo === 'entrada' ? 'RME' : 'RMS';
        $st = Db::pdo()->query("SELECT COUNT(*) FROM remitos WHERE YEAR(created_at) = YEAR(CURDATE()) AND tipo = " . Db::pdo()->quote($tipo));
        $count = (int)$st->fetchColumn();
        return $prefix . '-' . date('Y') . '-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO remitos (codigo, tipo, cliente_id, idclien, cliente_nombre, proveedor_id, proveedor_nombre, presupuesto_id, fecha, total_cents, estado, notas, created_by, created_at, updated_at)
                VALUES (:codigo, :tipo, :cliente_id, :idclien, :cliente_nombre, :proveedor_id, :proveedor_nombre, :presupuesto_id, :fecha, :total_cents, :estado, :notas, :created_by, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':tipo' => $data['tipo'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':proveedor_id' => $data['proveedor_id'],
                ':proveedor_nombre' => $data['proveedor_nombre'],
                ':presupuesto_id' => $data['presupuesto_id'],
                ':fecha' => $data['fecha'],
                ':total_cents' => $data['total_cents'],
                ':estado' => $data['estado'] ?? 'pendiente',
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
            ]);
            $id = (int)$pdo->lastInsertId();

            $sti = $pdo->prepare('
                INSERT INTO remito_items (remito_id, idprodu, idcodgusto, producto, variedad, qty, created_at)
                VALUES (:rid, :idprodu, :idcodgusto, :producto, :variedad, :qty, NOW())
            ');
            foreach ($items as $it) {
                $sti->execute([
                    ':rid' => $id,
                    ':idprodu' => $it['idprodu'],
                    ':idcodgusto' => $it['idcodgusto'],
                    ':producto' => $it['producto'],
                    ':variedad' => $it['variedad'],
                    ':qty' => $it['qty'],
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
        $st = Db::pdo()->prepare('UPDATE remitos SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM remitos WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $pdo = Db::pdo();
        $params = [':like' => '%' . $q . '%'];

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.codprodup, p.enweb
            FROM producto p
            WHERE p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like
            ORDER BY p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.codprodup, p.enweb
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                WHERE g.codscan = :c
                LIMIT 1
            ');
            $st2->execute([':c' => $q]);
            $byCode = $st2->fetch();
            if ($byCode) {
                $exists = false;
                foreach ($products as $pr) {
                    if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    array_unshift($products, $byCode);
                }
            }
        }

        foreach ($products as $idx => $pr) {
            $idprodu = (int)$pr['idprodu'];
            $st3 = $pdo->prepare('
                SELECT idcodgusto, nomgusto, codscan, stockact
                FROM gustos
                WHERE idprodu = :id AND discont = 0
                ORDER BY nomgusto ASC
                LIMIT 20
            ');
            $st3->execute([':id' => $idprodu]);
            $products[$idx]['variants'] = $st3->fetchAll();
        }

        return $products;
    }

    public function findClienteWeb(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $st = Db::pdo()->prepare('
            SELECT id, email, name, phone, address, city
            FROM web_users
            WHERE name LIKE :like OR email LIKE :like OR phone LIKE :like
            ORDER BY name ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function findPresupuestosDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $params = [':like' => '%' . $q . '%'];

        $st = Db::pdo()->prepare('
            SELECT p.id, p.codigo, p.cliente_nombre, p.total_cents, p.fecha
            FROM presupuestos p
            WHERE p.estado = \'aprobado\'
              AND (p.codigo LIKE :like OR p.cliente_nombre LIKE :like)
            ORDER BY p.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute($params);
        return $st->fetchAll();
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
