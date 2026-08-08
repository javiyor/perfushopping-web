<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CompraRepo
{
    /** @param array{q?:string, estado?:string, desde?:string, hasta?:string} $f
     *  @return array<int, array<string,mixed>>
     */
    public function findAll(array $f = []): array
    {
        $params = [];
        $where = [];

        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(fc.razon_proveedor LIKE :q OR fc.cuit_proveedor LIKE :q OR fc.tipo LIKE :q OR CONCAT(fc.punto_venta, "-", fc.numero_desde) LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $estado = trim((string)($f['estado'] ?? ''));
        if ($estado !== '') {
            $where[] = 'fc.estado = :e';
            $params[':e'] = $estado;
        }
        $desde = trim((string)($f['desde'] ?? ''));
        if ($desde !== '') {
            $where[] = 'fc.fecha >= :desde';
            $params[':desde'] = $desde;
        }
        $hasta = trim((string)($f['hasta'] ?? ''));
        if ($hasta !== '') {
            $where[] = 'fc.fecha <= :hasta';
            $params[':hasta'] = $hasta;
        }

        $sql = '
            SELECT fc.*,
                   pv.razon AS proveedor_razon,
                   c1.nomcta1 AS cuenta_nombre,
                   c.nomcta AS cuenta_grupo,
                   (SELECT COUNT(*) FROM factura_compra_items i WHERE i.factura_compra_id = fc.id) AS items_count,
                   au.nombre AS created_by_nombre
            FROM factura_compra fc
            LEFT JOIN proveedo pv ON pv.idprovee = fc.idprovee
            LEFT JOIN contable1 c1 ON c1.idcta1 = fc.idcta1
            LEFT JOIN contable c ON c.idcta = c1.idcta
            LEFT JOIN admin_users au ON au.id = fc.created_by
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY fc.fecha DESC, fc.id DESC LIMIT 500';

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT fc.*,
                   pv.razon AS proveedor_razon,
                   c1.nomcta1 AS cuenta_nombre,
                   c.nomcta AS cuenta_grupo,
                   d.nomdepo AS deposito_nombre,
                   au.nombre AS created_by_nombre
            FROM factura_compra fc
            LEFT JOIN proveedo pv ON pv.idprovee = fc.idprovee
            LEFT JOIN contable1 c1 ON c1.idcta1 = fc.idcta1
            LEFT JOIN contable c ON c.idcta = c1.idcta
            LEFT JOIN deposito d ON d.iddepo = fc.iddepo
            LEFT JOIN admin_users au ON au.id = fc.created_by
            WHERE fc.id = :id LIMIT 1
        ');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function items(int $facturaCompraId): array
    {
        $st = Db::pdo()->prepare('
            SELECT i.*, p.codprodu, g.nomgusto
            FROM factura_compra_items i
            LEFT JOIN producto p ON p.idprodu = i.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = i.idcodgusto
            WHERE i.factura_compra_id = :id
            ORDER BY i.id ASC
        ');
        $st->execute([':id' => $facturaCompraId]);
        return $st->fetchAll();
    }

    public function existsComprobante(string $cuit, string $tipo, string $puntoVenta, string $numero): bool
    {
        $st = Db::pdo()->prepare('
            SELECT id FROM factura_compra
            WHERE cuit_proveedor = :c AND tipo = :t AND punto_venta = :pv AND numero_desde = :n
            LIMIT 1
        ');
        $st->execute([
            ':c' => $cuit,
            ':t' => $tipo,
            ':pv' => $puntoVenta,
            ':n' => $numero,
        ]);
        return $st->fetchColumn() !== false;
    }

    /** @param array<string,mixed> $d */
    public function insert(array $d): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO factura_compra
              (origen, estado, fecha, tipo, punto_venta, numero_desde, numero_hasta, cod_autorizacion,
               cuit_proveedor, razon_proveedor, idprovee, moneda, tipo_cambio, imp_neto_gravado,
               imp_no_gravado, imp_exento, otros_tributos, imp_iva, imp_total, idcta1, iddepo,
               observaciones, created_by)
            VALUES
              (:origen, :estado, :fecha, :tipo, :pv, :nd, :nh, :cae,
               :cuit, :razon, :idprov, :moneda, :tc, :ing, :inng, :iex, :ot, :iva, :total, :idcta1, :depo,
               :obs, :cb)
        ');
        $st->execute($this->params($d));
        return (int)Db::pdo()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public function update(int $id, array $d): void
    {
        $st = Db::pdo()->prepare('
            UPDATE factura_compra SET
              estado = :estado, fecha = :fecha, tipo = :tipo, punto_venta = :pv,
              numero_desde = :nd, numero_hasta = :nh, cod_autorizacion = :cae,
              cuit_proveedor = :cuit, razon_proveedor = :razon, idprovee = :idprov,
              moneda = :moneda, tipo_cambio = :tc, imp_neto_gravado = :ing,
              imp_no_gravado = :inng, imp_exento = :iex, otros_tributos = :ot,
              imp_iva = :iva, imp_total = :total, idcta1 = :idcta1, iddepo = :depo,
              observaciones = :obs
            WHERE id = :id LIMIT 1
        ');
        $p = $this->params($d);
        $p[':id'] = $id;
        $st->execute($p);
    }

    public function delete(int $id): void
    {
        Db::pdo()->prepare('DELETE FROM factura_compra WHERE id = :id LIMIT 1')->execute([':id' => $id]);
    }

    /** @return array<string,mixed> */
    private function params(array $d): array
    {
        return [
            ':origen' => (string)($d['origen'] ?? 'manual'),
            ':estado' => (string)($d['estado'] ?? 'pendiente'),
            ':fecha' => ($d['fecha'] ?? '') !== '' ? (string)$d['fecha'] : null,
            ':tipo' => (string)($d['tipo'] ?? ''),
            ':pv' => (string)($d['punto_venta'] ?? ''),
            ':nd' => (string)($d['numero_desde'] ?? ''),
            ':nh' => (string)($d['numero_hasta'] ?? ''),
            ':cae' => (string)($d['cod_autorizacion'] ?? '') !== '' ? (string)$d['cod_autorizacion'] : null,
            ':cuit' => (string)($d['cuit_proveedor'] ?? ''),
            ':razon' => (string)($d['razon_proveedor'] ?? ''),
            ':idprov' => ((int)($d['idprovee'] ?? 0)) > 0 ? (int)$d['idprovee'] : null,
            ':moneda' => (string)($d['moneda'] ?? 'PES'),
            ':tc' => (float)($d['tipo_cambio'] ?? 1) > 0 ? (float)$d['tipo_cambio'] : 1,
            ':ing' => (float)($d['imp_neto_gravado'] ?? 0),
            ':inng' => (float)($d['imp_no_gravado'] ?? 0),
            ':iex' => (float)($d['imp_exento'] ?? 0),
            ':ot' => (float)($d['otros_tributos'] ?? 0),
            ':iva' => (float)($d['imp_iva'] ?? 0),
            ':total' => (float)($d['imp_total'] ?? 0),
            ':idcta1' => ((int)($d['idcta1'] ?? 0)) > 0 ? (int)$d['idcta1'] : null,
            ':depo' => ((int)($d['iddepo'] ?? 0)) > 0 ? (int)$d['iddepo'] : null,
            ':obs' => (string)($d['observaciones'] ?? ''),
            ':cb' => ((int)($d['created_by'] ?? 0)) > 0 ? (int)$d['created_by'] : null,
        ];
    }

    // ── Cuentas contables ──

    /** @return array<int, array<string,mixed>> */
    public function cuentas(): array
    {
        $st = Db::pdo()->query('
            SELECT c.idcta, c.nomcta, c1.idcta1, c1.nomcta1
            FROM contable1 c1
            INNER JOIN contable c ON c.idcta = c1.idcta
            ORDER BY c.nomcta ASC, c1.nomcta1 ASC
        ');
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function cuentasGrupo(): array
    {
        $st = Db::pdo()->query('SELECT idcta, nomcta FROM contable ORDER BY nomcta ASC');
        return $st->fetchAll();
    }

    public function crearSubcuenta(string $nomcta1, int $idcta): int
    {
        $st = Db::pdo()->prepare('INSERT INTO contable1 (nomcta1, idcta) VALUES (:n, :c)');
        $st->execute([':n' => trim($nomcta1), ':c' => $idcta]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function crearCuenta(string $nomcta): int
    {
        $st = Db::pdo()->prepare('INSERT INTO contable (nomcta) VALUES (:n)');
        $st->execute([':n' => trim($nomcta)]);
        return (int)Db::pdo()->lastInsertId();
    }

    // ── Proveedores ──

    public function proveedorByCuit(string $cuit): ?array
    {
        $cuit = preg_replace('/\D/', '', (string)$cuit);
        if ($cuit === '' || $cuit === '0') {
            return null;
        }
        $st = Db::pdo()->prepare('SELECT * FROM proveedo WHERE cuit = :c LIMIT 1');
        $st->execute([':c' => $cuit]);
        return $st->fetch() ?: null;
    }

    /** Crea un proveedor si no existe por CUIT. Devuelve idprovee. */
    public function proveedorEnsure(string $cuit, string $razon): ?int
    {
        $cuit = preg_replace('/\D/', '', (string)$cuit);
        if ($cuit === '' || $cuit === '0') {
            return null;
        }
        $existing = $this->proveedorByCuit($cuit);
        if ($existing) {
            if (trim((string)$existing['razon']) === '' && trim($razon) !== '') {
                Db::pdo()->prepare('UPDATE proveedo SET razon = :r WHERE idprovee = :i LIMIT 1')
                    ->execute([':r' => $razon, ':i' => (int)$existing['idprovee']]);
            }
            return (int)$existing['idprovee'];
        }

        $st = Db::pdo()->query('SELECT COALESCE(MAX(idprovee), 0) + 1 AS next FROM proveedo');
        $next = (int)$st->fetchColumn();
        $codprove = str_pad((string)$next, 5, '0', STR_PAD_LEFT);

        $ins = Db::pdo()->prepare('
            INSERT INTO proveedo (codprove, razon, cuit, activo, fealta)
            VALUES (:cp, :r, :c, 1, CURDATE())
        ');
        $ins->execute([':cp' => $codprove, ':r' => $razon !== '' ? $razon : ('Proveedor ' . $cuit), ':c' => $cuit]);
        return (int)Db::pdo()->lastInsertId();
    }

    // ── Depósitos ──

    /** @return array<int, array<string,mixed>> */
    public function depositosVenta(): array
    {
        $st = Db::pdo()->query('SELECT iddepo, nomdepo FROM deposito WHERE marca = 2 ORDER BY nomdepo ASC');
        return $st->fetchAll();
    }

    public function depositoPrincipal(): int
    {
        $st = Db::pdo()->query('SELECT iddepo FROM deposito WHERE marca = 2 ORDER BY iddepo ASC LIMIT 1');
        $v = $st->fetchColumn();
        return $v !== false ? (int)$v : 0;
    }

    // ── Aplicar ítems: stock + precios ──

    /**
     * @param array<int, array{idprodu:int, idcodgusto:?int, product_name:string, qty:float, unit_cost:float}> $items
     */
    public function aplicarItems(int $facturaCompraId, array $items, int $iddepo, string $fecha, ?string $notas): void
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Limpiar ítems previos (re-carga)
            $pdo->prepare('DELETE FROM factura_compra_items WHERE factura_compra_id = :id')->execute([':id' => $facturaCompraId]);

            $stItem = $pdo->prepare('
                INSERT INTO factura_compra_items (factura_compra_id, idprodu, idcodgusto, product_name, qty, unit_cost, line_total)
                VALUES (:fc, :prod, :gusto, :name, :qty, :cost, :line)
            ');

            foreach ($items as $it) {
                $idprodu = (int)($it['idprodu'] ?? 0);
                if ($idprodu <= 0) {
                    continue;
                }
                $idcodgusto = ((int)($it['idcodgusto'] ?? 0)) > 0 ? (int)$it['idcodgusto'] : null;
                $qty = (float)($it['qty'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }
                $unitCost = (float)($it['unit_cost'] ?? 0);
                $name = (string)($it['product_name'] ?? '');

                // Stock + precios por producto
                $prod = $this->productoCosto($idprodu);
                if ($prod) {
                    if ($idcodgusto === null) {
                        $idcodgusto = $this->resolveVariant($idprodu);
                    }
                    if ($unitCost > 0) {
                        $precomp = round($unitCost, 2);
                        $ganan1 = (float)($prod['ganan1'] ?? 0);
                        $ganan2 = (float)($prod['ganan2'] ?? 0);
                        $precio = round($precomp * (1 + $ganan1 / 100), 2);
                        $precio1 = round($precomp * (1 + $ganan2 / 100), 2);
                        $pdo->prepare('
                            UPDATE producto
                            SET precomp = :pc, precio = :p, precio1 = :p1, fecompra = :f
                            WHERE idprodu = :id LIMIT 1
                        ')->execute([
                            ':pc' => $precomp, ':p' => $precio, ':p1' => $precio1,
                            ':f' => $fecha !== '' ? $fecha : null, ':id' => $idprodu,
                        ]);
                    }
                    // Movimiento de stock (compra)
                    $pdo->prepare('
                        INSERT INTO stockcab (iddepoh, iddepod, fecha, notas, tipo_movimiento)
                        VALUES (:depoh, NULL, :fecha, :notas, \'compra\')
                    ')->execute([':depoh' => $iddepo, ':fecha' => $fecha, ':notas' => $notas]);
                    $cabId = (int)$pdo->lastInsertId();

                    $pdo->prepare('
                        INSERT INTO stockdet (idstockcab, idprodu, idcodgusto, canti)
                        VALUES (:cab, :prod, :gusto, :cant)
                    ')->execute([':cab' => $cabId, ':prod' => $idprodu, ':gusto' => $idcodgusto, ':cant' => $qty]);

                    $this->addStockDeposito($pdo, $idprodu, $idcodgusto, $iddepo, $qty);
                    $this->recalcularProducto($pdo, $idprodu, $idcodgusto);
                }

                $stItem->execute([
                    ':fc' => $facturaCompraId,
                    ':prod' => $idprodu,
                    ':gusto' => $idcodgusto,
                    ':name' => $name,
                    ':qty' => $qty,
                    ':cost' => $unitCost,
                    ':line' => round($qty * $unitCost, 2),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    private function productoCosto(int $idprodu): ?array
    {
        $st = Db::pdo()->prepare('SELECT idprodu, precomp, ganan1, ganan2 FROM producto WHERE idprodu = :id LIMIT 1');
        $st->execute([':id' => $idprodu]);
        return $st->fetch() ?: null;
    }

    public function resolveVariant(int $idprodu): ?int
    {
        $st = Db::pdo()->prepare('SELECT MIN(idcodgusto) AS g FROM gustos WHERE idprodu = :id AND discont = 0');
        $st->execute([':id' => $idprodu]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int)$v : null;
    }

    /**
     * Persiste los ítems de un comprobante ya aplicado (no vuelve a sumar stock).
     * @param array<int, array{idprodu:int, idcodgusto:?int, product_name:string, qty:float, unit_cost:float}> $items
     */
    public function reemplazarItems(int $facturaCompraId, array $items): void
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM factura_compra_items WHERE factura_compra_id = :id')->execute([':id' => $facturaCompraId]);
            $st = $pdo->prepare('
                INSERT INTO factura_compra_items (factura_compra_id, idprodu, idcodgusto, product_name, qty, unit_cost, line_total)
                VALUES (:fc, :prod, :gusto, :name, :qty, :cost, :line)
            ');
            foreach ($items as $it) {
                $idprodu = (int)($it['idprodu'] ?? 0);
                if ($idprodu <= 0) {
                    continue;
                }
                $qty = (float)($it['qty'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }
                $unitCost = (float)($it['unit_cost'] ?? 0);
                $st->execute([
                    ':fc' => $facturaCompraId,
                    ':prod' => $idprodu,
                    ':gusto' => ((int)($it['idcodgusto'] ?? 0)) > 0 ? (int)$it['idcodgusto'] : null,
                    ':name' => (string)($it['product_name'] ?? ''),
                    ':qty' => $qty,
                    ':cost' => $unitCost,
                    ':line' => round($qty * $unitCost, 2),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function addStockDeposito(\PDO $pdo, int $idprodu, ?int $idcodgusto, int $iddepo, float $qty): void
    {
        $st = $pdo->prepare('
            SELECT idstock, stock FROM stock
            WHERE iddepo = :depo AND idprodu = :prod AND (idcodgusto = :g OR (idcodgusto IS NULL AND :g2 IS NULL))
            LIMIT 1
        ');
        $st->execute([':depo' => $iddepo, ':prod' => $idprodu, ':g' => $idcodgusto, ':g2' => $idcodgusto]);
        $existing = $st->fetch();

        if ($existing) {
            $pdo->prepare('UPDATE stock SET stock = :s WHERE idstock = :id LIMIT 1')
                ->execute([':s' => (float)$existing['stock'] + $qty, ':id' => (int)$existing['idstock']]);
        } else {
            $pdo->prepare('INSERT INTO stock (iddepo, idprodu, idcodgusto, stock) VALUES (:depo, :prod, :g, :s)')
                ->execute([':depo' => $iddepo, ':prod' => $idprodu, ':g' => $idcodgusto, ':s' => $qty]);
        }
    }

    private function recalcularProducto(\PDO $pdo, int $idprodu, ?int $idcodgusto): void
    {
        $st = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idprodu = :p');
        $st->execute([':p' => $idprodu]);
        $pdo->prepare('UPDATE producto SET stocact = :s WHERE idprodu = :id LIMIT 1')
            ->execute([':s' => (float)$st->fetchColumn(), ':id' => $idprodu]);

        if ($idcodgusto) {
            $sg = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idcodgusto = :g');
            $sg->execute([':g' => $idcodgusto]);
            $pdo->prepare('UPDATE gustos SET stockact = :s WHERE idcodgusto = :id LIMIT 1')
                ->execute([':s' => (float)$sg->fetchColumn(), ':id' => $idcodgusto]);
        }
    }
}
