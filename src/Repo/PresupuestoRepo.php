<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PresupuestoRepo
{
    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(p.codigo LIKE :like OR p.cliente_nombre LIKE :like OR p.cliente_cuit LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'p.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT p.*, a.nombre AS created_by_nombre, COUNT(pi.id) AS items_count
            FROM presupuestos p
            LEFT JOIN admin_users a ON a.id = p.created_by
            LEFT JOIN presupuesto_items pi ON pi.presupuesto_id = p.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY p.id ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT p.*, a.nombre AS created_by_nombre
            FROM presupuestos p
            LEFT JOIN admin_users a ON a.id = p.created_by
            WHERE p.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $presupuestoId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id = :p ORDER BY id ASC');
        $st->execute([':p' => $presupuestoId]);
        return $st->fetchAll();
    }

    public function nextCodigo(): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM presupuestos WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'PRES-' . date('Y') . '-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO presupuestos (codigo, cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_tele, cliente_mail, fecha, valido_hasta, subtotal_cents, iva_cents, total_cents, estado, notas, created_by, created_at, updated_at)
                VALUES (:codigo, :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_tele, :cliente_mail, :fecha, :valido_hasta, :subtotal, :iva, :total, :estado, :notas, :created_by, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':cliente_cuit' => $data['cliente_cuit'],
                ':cliente_direc' => $data['cliente_direc'],
                ':cliente_tele' => $data['cliente_tele'],
                ':cliente_mail' => $data['cliente_mail'],
                ':fecha' => $data['fecha'],
                ':valido_hasta' => $data['valido_hasta'],
                ':subtotal' => $data['subtotal_cents'],
                ':iva' => $data['iva_cents'],
                ':total' => $data['total_cents'],
                ':estado' => $data['estado'] ?? 'pendiente',
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
            ]);
            $id = (int)$pdo->lastInsertId();

            $sti = $pdo->prepare('
                INSERT INTO presupuesto_items (presupuesto_id, idprodu, idcodgusto, producto, variedad, qty, unit_price_cents, iva_rate, total_cents)
                VALUES (:pid, :idprodu, :idcodgusto, :producto, :variedad, :qty, :unit_price, :iva, :total)
            ');
            foreach ($items as $it) {
                $sti->execute([
                    ':pid' => $id,
                    ':idprodu' => $it['idprodu'],
                    ':idcodgusto' => $it['idcodgusto'],
                    ':producto' => $it['producto'],
                    ':variedad' => $it['variedad'],
                    ':qty' => $it['qty'],
                    ':unit_price' => $it['unit_price_cents'],
                    ':iva' => $it['iva_rate'],
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
        $st = Db::pdo()->prepare('UPDATE presupuestos SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM presupuestos WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    /** Busca productos para agregar como items */
    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $pdo = Db::pdo();
        $params = [':like' => '%' . $q . '%'];

        // Search producto by name/code
        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.codprodup, p.enweb, i.tiva
            FROM producto p
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like
            ORDER BY p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        // Also search by barcode in gustos
        $matchedV = null;
        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.codprodup, p.enweb, i.tiva,
                       g.idcodgusto, g.nomgusto AS matched_nomgusto
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
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
                // Prepend to results
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

        // Attach variants to each product
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

    public function findClienteWeb(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                    \'consumidor_final\' AS condicion_iva
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.razon LIKE :like OR c.cuit LIKE :like2
            ORDER BY c.razon ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        return $st->fetchAll();
    }
}
