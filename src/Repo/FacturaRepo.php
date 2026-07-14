<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class FacturaRepo
{
    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(f.codigo LIKE :like OR f.cliente_nombre LIKE :like OR f.cliente_cuit LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'f.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT f.*, a.nombre AS created_by_nombre, v.nombre AS vendedor_nombre, COUNT(fi.id) AS items_count
            FROM facturas f
            LEFT JOIN admin_users a ON a.id = f.created_by
            LEFT JOIN admin_users v ON v.id = f.vendedor_id
            LEFT JOIN factura_items fi ON fi.factura_id = f.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY f.id ORDER BY f.created_at DESC, f.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT f.*, a.nombre AS created_by_nombre, v.nombre AS vendedor_nombre
            FROM facturas f
            LEFT JOIN admin_users a ON a.id = f.created_by
            LEFT JOIN admin_users v ON v.id = f.vendedor_id
            WHERE f.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $facturaId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM factura_items WHERE factura_id = :f ORDER BY id ASC');
        $st->execute([':f' => $facturaId]);
        return $st->fetchAll();
    }

    public function pagos(int $facturaId): array
    {
        $st = Db::pdo()->prepare('
            SELECT fp.*, c.banco_emisor AS cheque_banco, c.numero_cheque, c.titular AS cheque_titular, c.fecha_vencimiento AS cheque_vto, c.estado AS cheque_estado
            FROM factura_pagos fp
            LEFT JOIN cheques c ON c.id = fp.cheque_id
            WHERE fp.factura_id = :f ORDER BY fp.id ASC
        ');
        $st->execute([':f' => $facturaId]);
        return $st->fetchAll();
    }

    public function nextCodigo(string $tipo = 'FACT-B'): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM facturas WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'F-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items, array $pagos): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO facturas (codigo, tipo_comprobante, punto_venta, remito_id, presupuesto_id, cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_tele, cliente_mail, cliente_condicion_iva, fecha, subtotal_cents, iva_cents, descuento_cents, total_cents, estado, forma_pago, notas, created_by, vendedor_id, created_at, updated_at)
                VALUES (:codigo, :tipo, :punto_venta, :remito_id, :presupuesto_id, :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_tele, :cliente_mail, :cliente_condicion_iva, :fecha, :subtotal, :iva, :descuento, :total, :estado, :forma_pago, :notas, :created_by, :vendedor_id, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':tipo' => $data['tipo_comprobante'],
                ':punto_venta' => $data['punto_venta'] ?? 1,
                ':remito_id' => $data['remito_id'],
                ':presupuesto_id' => $data['presupuesto_id'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':cliente_cuit' => $data['cliente_cuit'],
                ':cliente_direc' => $data['cliente_direc'],
                ':cliente_tele' => $data['cliente_tele'],
                ':cliente_mail' => $data['cliente_mail'],
                ':cliente_condicion_iva' => $data['cliente_condicion_iva'],
                ':fecha' => $data['fecha'],
                ':subtotal' => $data['subtotal_cents'],
                ':iva' => $data['iva_cents'],
                ':descuento' => $data['descuento_cents'] ?? 0,
                ':total' => $data['total_cents'],
                ':estado' => $data['estado'] ?? 'emitida',
                ':forma_pago' => $data['forma_pago'],
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
                ':vendedor_id' => $data['vendedor_id'] ?? null,
            ]);
            $id = (int)$pdo->lastInsertId();

            $sti = $pdo->prepare('
                INSERT INTO factura_items (factura_id, idprodu, idcodgusto, producto, variedad, qty, unit_price_cents, iva_rate, iva_cents, total_cents)
                VALUES (:fid, :idprodu, :idcodgusto, :producto, :variedad, :qty, :unit_price, :iva_rate, :iva_cents, :total)
            ');
            foreach ($items as $it) {
                $sti->execute([
                    ':fid' => $id,
                    ':idprodu' => $it['idprodu'],
                    ':idcodgusto' => $it['idcodgusto'],
                    ':producto' => $it['producto'],
                    ':variedad' => $it['variedad'],
                    ':qty' => $it['qty'],
                    ':unit_price' => $it['unit_price_cents'],
                    ':iva_rate' => $it['iva_rate'],
                    ':iva_cents' => $it['iva_cents'],
                    ':total' => $it['total_cents'],
                ]);
            }

            $stp = $pdo->prepare('INSERT INTO factura_pagos (factura_id, forma_pago, cheque_id, monto_cents) VALUES (:fid, :forma, :chq, :monto)');
            foreach ($pagos as $pg) {
                $stp->execute([
                    ':fid' => $id,
                    ':forma' => $pg['forma_pago'],
                    ':chq' => $pg['cheque_id'] ?? null,
                    ':monto' => $pg['monto_cents'],
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
        $st = Db::pdo()->prepare('UPDATE facturas SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM facturas WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $pdo = Db::pdo();
        $params = [':like' => '%' . $q . '%'];

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precomp, p.codprodup, p.enweb,
                   i.codivaprodu, i.tiva
            FROM producto p
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like
            ORDER BY p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precomp, p.codprodup, p.enweb,
                       i.codivaprodu, i.tiva
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                WHERE g.codscan = :c
                LIMIT 1
            ');
            $st2->execute([':c' => $q]);
            $byCode = $st2->fetch();
            if ($byCode) {
                $exists = false;
                foreach ($products as $pr) {
                    if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) { $exists = true; break; }
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
        if ($q === '') return [];

        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city, c.codprov AS provincia,
                   COALESCE(c.condicion_iva, \'consumidor_final\') AS condicion_iva,
                   COALESCE(c.condicion_venta, \'\') AS condicion_venta
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.razon LIKE :like OR c.cuit LIKE :like2
            ORDER BY c.razon ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function findRemitosDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT r.id, r.codigo, r.cliente_nombre, r.total_cents, r.fecha
            FROM remitos r
            WHERE r.estado = \'completado\'
              AND r.tipo = \'salida\'
              AND (r.codigo LIKE :like OR r.cliente_nombre LIKE :like)
            ORDER BY r.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function itemsByRemito(int $remitoId): array
    {
        $st = Db::pdo()->prepare('
            SELECT ri.*, p.precio, i.tiva
            FROM remito_items ri
            LEFT JOIN producto p ON p.idprodu = ri.idprodu
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE ri.remito_id = :r
            ORDER BY ri.id ASC
        ');
        $st->execute([':r' => $remitoId]);
        return $st->fetchAll();
    }

    public function findClienteErpByWebId(int $webUserId): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT c.*
            FROM clientes c
            INNER JOIN web_users w ON w.cliente_id = c.idclien
            WHERE w.id = :id
            LIMIT 1
        ');
        $st->execute([':id' => $webUserId]);
        return $st->fetch() ?: null;
    }

    public function findClienteByIdclien(int $idclien): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT c.*
            FROM clientes c
            WHERE c.idclien = :id
            LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        return $st->fetch() ?: null;
    }

    public function upsertClienteArca(array $data): ?array
    {
        $cuit = trim($data['cuit'] ?? '');
        if ($cuit === '') return null;

        $razon = trim($data['razon'] ?? $data['razonSocial'] ?? '');
        $direc = trim($data['direc'] ?? '');
        $localidad = trim($data['localidad'] ?? '');
        $codprov = trim($data['provincia'] ?? '');
        $condicionIva = trim($data['condicion_iva'] ?? 'consumidor_final');

        // Check if exists by CUIT
        $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE cuit = :c LIMIT 1');
        $st->execute([':c' => $cuit]);
        $existing = $st->fetch();

        if ($existing) {
            $st = Db::pdo()->prepare('
                UPDATE clientes SET razon = :r, direc = :d, Localidad = :l, codprov = :p, condicion_iva = :ci
                WHERE idclien = :id LIMIT 1
            ');
            $st->execute([
                ':r' => $razon,
                ':d' => $direc,
                ':l' => $localidad,
                ':p' => $codprov,
                ':ci' => $condicionIva,
                ':id' => $existing['idclien'],
            ]);
            $idclien = (int)$existing['idclien'];
        } else {
            $st = Db::pdo()->prepare('
                INSERT INTO clientes (razon, cuit, direc, Localidad, codprov, condicion_iva, activo, fealta)
                VALUES (:r, :c, :d, :l, :p, :ci, 1, NOW())
            ');
            $st->execute([
                ':r' => $razon,
                ':c' => $cuit,
                ':d' => $direc,
                ':l' => $localidad,
                ':p' => $codprov,
                ':ci' => $condicionIva,
            ]);
            $idclien = (int)Db::pdo()->lastInsertId();
        }

        // Return in same format as findClienteWeb
        $st = Db::pdo()->prepare('
            SELECT 0 AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city, c.codprov AS provincia,
                   COALESCE(c.condicion_iva, \'consumidor_final\') AS condicion_iva,
                   COALESCE(c.condicion_venta, \'\') AS condicion_venta
            FROM clientes c
            WHERE c.idclien = :id LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        return $st->fetch() ?: null;
    }
}
