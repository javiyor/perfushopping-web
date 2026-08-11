<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class FacturaRepo
{
    public static function normalizeCondIva(?string $value): string
    {
        $normalized = strtolower(trim((string)$value));
        $map = [
            'responsable inscripto' => 'responsable_inscripto',
            'ri' => 'responsable_inscripto',
            'consumidor final' => 'consumidor_final',
            'cf' => 'consumidor_final',
            'monotributista' => 'monotributista',
            'monotributo' => 'monotributista',
            'mono' => 'monotributista',
            'exento' => 'exento',
            'ex' => 'exento',
        ];
        return $map[$normalized] ?? $normalized ?: 'consumidor_final';
    }

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
            SELECT fp.*, c.banco_emisor AS cheque_banco, c.numero_cheque, c.titular AS cheque_titular, c.fecha_vencimiento AS cheque_vto, c.estado AS cheque_estado,
                   b.nombanc AS banco_nombre, p.descripcion AS plazo_descripcion, p.cuotas AS plazo_cuotas, p.dias AS plazo_dias, p.pricuo AS plazo_pricuo
            FROM factura_pagos fp
            LEFT JOIN cheques c ON c.id = fp.cheque_id
            LEFT JOIN bancos b ON b.idban = fp.banco_id
            LEFT JOIN plazopago p ON p.idplazo = fp.idplazo
            WHERE fp.factura_id = :f ORDER BY fp.id ASC
        ');
        $st->execute([':f' => $facturaId]);
        return $st->fetchAll();
    }

    public function countActivas(): int
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM facturas WHERE estado <> 'anulada'");
        return (int)$st->fetchColumn();
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
                INSERT INTO facturas (codigo, tipo_comprobante, punto_venta, remito_id, presupuesto_id, cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_tele, cliente_mail, cliente_condicion_iva, fecha, subtotal_cents, iva_cents, descuento_cents, puntos_cents, total_cents, estado, forma_pago, notas, created_by, vendedor_id, created_at, updated_at)
                VALUES (:codigo, :tipo, :punto_venta, :remito_id, :presupuesto_id, :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_tele, :cliente_mail, :cliente_condicion_iva, :fecha, :subtotal, :iva, :descuento, :puntos, :total, :estado, :forma_pago, :notas, :created_by, :vendedor_id, NOW(), NOW())
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
                ':puntos' => $data['puntos_cents'] ?? 0,
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

            $stp = $pdo->prepare('INSERT INTO factura_pagos (factura_id, forma_pago, cheque_id, monto_cents, cupon_numero, cupon_monto_cents, idplazo, banco_id) VALUES (:fid, :forma, :chq, :monto, :cupon, :cuponm, :plazo, :banco)');
            foreach ($pagos as $pg) {
                $stp->execute([
                    ':fid' => $id,
                    ':forma' => $pg['forma_pago'],
                    ':chq' => $pg['cheque_id'] ?? null,
                    ':monto' => $pg['monto_cents'],
                    ':cupon' => $pg['cupon_numero'] ?? null,
                    ':cuponm' => $pg['cupon_monto_cents'] ?? null,
                    ':plazo' => $pg['idplazo'] ?? null,
                    ':banco' => $pg['banco_id'] ?? null,
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

    public function searchProducts(string $q, int $limit = 20, ?int $iddepo = null): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $pdo = Db::pdo();
        $params = [':like' => '%' . $q . '%'];

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precomp, p.codprodup, p.enweb, p.stocact,
                   i.codivaprodu, i.tiva
            FROM producto p
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like
            GROUP BY p.idprodu
            ORDER BY p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        $matchedVariant = null;
        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precomp, p.codprodup, p.enweb, p.stocact,
                       i.codivaprodu, i.tiva,
                       g.idcodgusto, g.nomgusto AS matched_nomgusto
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                WHERE g.codscan = :c
                GROUP BY p.idprodu
                LIMIT 1
            ');
            $st2->execute([':c' => $q]);
            $byCode = $st2->fetch();
            if ($byCode) {
                $matchedVariant = [
                    'idcodgusto' => (int)$byCode['idcodgusto'],
                    'nomgusto' => $byCode['matched_nomgusto'],
                    'codscan' => $q,
                ];
                unset($byCode['idcodgusto'], $byCode['matched_nomgusto']);
                $exists = false;
                foreach ($products as $pr) {
                    if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) { $exists = true; break;
                    }
                }
                if (!$exists) array_unshift($products, $byCode);
            }
        }

        $matchedV = $matchedVariant;
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

            $products[$idx]['stock_total'] = (int)($pr['stocact'] ?? 0);

            $products[$idx]['stock_deposito'] = 0;
            if ($iddepo) {
                $st4 = $pdo->prepare('
                    SELECT COALESCE(SUM(stock), 0)
                    FROM stock
                    WHERE idprodu = :p AND iddepo = :d
                ');
                $st4->execute([':p' => $idprodu, ':d' => $iddepo]);
                $products[$idx]['stock_deposito'] = (int)$st4->fetchColumn();
            }

            foreach (($products[$idx]['variants'] ?? []) as $vi => $v) {
                $idg = (int)$v['idcodgusto'];
                $products[$idx]['variants'][$vi]['stock_total'] = (int)($v['stockact'] ?? 0);
                $products[$idx]['variants'][$vi]['stock_deposito'] = 0;
                if ($iddepo) {
                    $st5 = $pdo->prepare('
                        SELECT COALESCE(SUM(stock), 0)
                        FROM stock
                        WHERE idcodgusto = :g AND iddepo = :d
                    ');
                    $st5->execute([':g' => $idg, ':d' => $iddepo]);
                    $products[$idx]['variants'][$vi]['stock_deposito'] = (int)$st5->fetchColumn();
                }
            }
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
        if ($q === '') return [];

        $condIvaExpr = $this->clientesTieneCondicionIva()
            ? 'COALESCE(c.condicion_iva, \'consumidor_final\')'
            : '\'consumidor_final\'';

        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   ' . $condIvaExpr . ' AS condicion_iva
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.razon LIKE :like OR c.cuit LIKE :like2
            ORDER BY c.razon ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
        }
        return $rows;
    }

    private static ?bool $clientesCondicionIva = null;

    private function clientesTieneCondicionIva(): bool
    {
        if (self::$clientesCondicionIva === null) {
            try {
                $cols = Db::pdo()->query('SHOW COLUMNS FROM clientes');
                self::$clientesCondicionIva = in_array('condicion_iva', array_column($cols->fetchAll(), 'Field'), true);
            } catch (\Throwable $e) {
                self::$clientesCondicionIva = false;
            }
        }
        return self::$clientesCondicionIva;
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

    public function findPresupuestosDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT p.id, p.codigo, p.cliente_nombre, p.total_cents, p.fecha, p.cliente_id, p.idclien, p.cliente_cuit, p.cliente_direc, p.cliente_tele, p.cliente_mail
            FROM presupuestos p
            WHERE p.estado = \'aprobado\'
              AND (p.codigo LIKE :like OR p.cliente_nombre LIKE :like)
            ORDER BY p.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function itemsByPresupuesto(int $presupuestoId): array
    {
        $st = Db::pdo()->prepare('
            SELECT pi.*, p.precio, i.tiva
            FROM presupuesto_items pi
            LEFT JOIN producto p ON p.idprodu = pi.idprodu
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE pi.presupuesto_id = :p
            ORDER BY pi.id ASC
        ');
        $st->execute([':p' => $presupuestoId]);
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
    {        $st = Db::pdo()->prepare('
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
        // Check if exists by CUIT
        $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE cuit = :c LIMIT 1');
        $st->execute([':c' => $cuit]);
        $existing = $st->fetch();

        if ($existing) {
            $st = Db::pdo()->prepare('
                UPDATE clientes SET razon = :r, direc = :d, Localidad = :l
                WHERE idclien = :id LIMIT 1
            ');
            $st->execute([
                ':r' => $razon,
                ':d' => $direc,
                ':l' => $localidad,
                ':id' => $existing['idclien'],
            ]);
            $idclien = (int)$existing['idclien'];
        } else {
            $st = Db::pdo()->prepare('
                INSERT INTO clientes (razon, cuit, direc, Localidad, activo, fealta)
                VALUES (:r, :c, :d, :l, 1, NOW())
            ');
            $st->execute([
                ':r' => $razon,
                ':c' => $cuit,
                ':d' => $direc,
                ':l' => $localidad,
            ]);
            $idclien = (int)Db::pdo()->lastInsertId();
        }

        // Return in same format as findClienteWeb
        $st = Db::pdo()->prepare('
            SELECT 0 AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   COALESCE(c.condicion_iva, \'consumidor_final\') AS condicion_iva
            FROM clientes c
            WHERE c.idclien = :id LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        $r = $st->fetch();
        if ($r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
        }
        return $r ?: null;
    }

    public function crearClientePos(array $data): ?array
    {
        $cuit = trim($data['cuit'] ?? '');
        $razon = trim($data['razon'] ?? '');
        if ($razon === '') return null;

        $direc = trim($data['direc'] ?? '');
        $tele = trim($data['tele'] ?? '');
        $mail = trim($data['mail'] ?? '');
        $condIva = self::normalizeCondIva($data['condicion_iva'] ?? 'consumidor_final');
        $tieneCondIva = $this->clientesTieneCondicionIva();

        $existing = null;
        if ($cuit !== '') {
            $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE cuit = :c LIMIT 1');
            $st->execute([':c' => $cuit]);
            $existing = $st->fetch();
        }

        if ($existing) {
            $setCond = $tieneCondIva ? ', condicion_iva = :ci' : '';
            $st = Db::pdo()->prepare('
                UPDATE clientes SET razon = :r, direc = :d, tele = :t, mail = :m' . $setCond . '
                WHERE idclien = :id LIMIT 1
            ');
            $params = [':r' => $razon, ':d' => $direc, ':t' => $tele, ':m' => $mail, ':id' => $existing['idclien']];
            if ($tieneCondIva) $params[':ci'] = $condIva;
            $st->execute($params);
            $idclien = (int)$existing['idclien'];
        } else {
            $condCol = $tieneCondIva ? ', condicion_iva' : '';
            $condVal = $tieneCondIva ? ', :ci' : '';
            $st = Db::pdo()->prepare('
                INSERT INTO clientes (razon, cuit, direc, tele, mail, activo, fealta' . $condCol . ')
                VALUES (:r, :c, :d, :t, :m, 1, NOW()' . $condVal . ')
            ');
            $params = [':r' => $razon, ':c' => $cuit, ':d' => $direc, ':t' => $tele, ':m' => $mail];
            if ($tieneCondIva) $params[':ci'] = $condIva;
            $st->execute($params);
            $idclien = (int)Db::pdo()->lastInsertId();
        }

        // Return in same shape as findClienteWeb
        $condIvaExpr = $tieneCondIva
            ? 'COALESCE(c.condicion_iva, \'consumidor_final\')'
            : '\'consumidor_final\'';
        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   ' . $condIvaExpr . ' AS condicion_iva
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.idclien = :id LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        $r = $st->fetch();
        if ($r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
        }
        return $r ?: null;
    }
}
