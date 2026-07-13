<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class StockRepo
{
    public function listarStock(string $q = '', int $codepar = 0, string $stockFilter = '', int $limit = 80): array
    {
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = ['p.enweb = 1'];

        if ($q !== '') {
            $where[] = '(p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($codepar > 0) {
            $where[] = 'p.codepar = :codepar';
            $params[':codepar'] = $codepar;
        }
        if ($stockFilter === 'sin_stock') {
            $where[] = '(p.stocact IS NULL OR p.stocact <= 0)';
        } elseif ($stockFilter === 'bajo_stock') {
            $where[] = '(p.stocact IS NOT NULL AND p.stocact > 0 AND p.stocact <= 5)';
        } elseif ($stockFilter === 'con_stock') {
            $where[] = '(p.stocact IS NOT NULL AND p.stocact > 5)';
        }

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precomp, p.stocact, p.stocdep, p.codepar, p.enweb, p.observ, p.imagen,
                   d.nomdepar, COUNT(g.idcodgusto) AS variantes
            FROM producto p
            LEFT JOIN gustos g ON g.idprodu = p.idprodu AND g.discont = 0
            LEFT JOIN departa d ON d.codepar = p.codepar
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY p.idprodu
            ORDER BY p.stocact ASC, p.produ ASC
            LIMIT ' . $limit;
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function productoDetalle(int $idprodu): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT p.*, d.nomdepar
            FROM producto p
            LEFT JOIN departa d ON d.codepar = p.codepar
            WHERE p.idprodu = :id LIMIT 1
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetch() ?: null;
    }

    public function variantesConStock(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT g.idcodgusto, g.nomgusto, g.codscan, g.stockact, g.discont
            FROM gustos g
            WHERE g.idprodu = :id AND g.discont = 0
            ORDER BY g.nomgusto ASC
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }

    public function stockPorDeposito(?int $idprodu = null, ?int $idcodgusto = null): array
    {
        $params = [];
        $where = [];
        if ($idprodu) {
            $where[] = 's.idprodu = :idp';
            $params[':idp'] = $idprodu;
        }
        if ($idcodgusto) {
            $where[] = 's.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }
        $sql = '
            SELECT s.*, d.nomdepo, p.produ, g.nomgusto
            FROM stock s
            INNER JOIN deposito d ON d.iddepo = s.iddepo AND d.marca = 2
            LEFT JOIN producto p ON p.idprodu = s.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = s.idcodgusto
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY d.nomdepo ASC';
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function depositos(): array
    {
        $st = Db::pdo()->query('SELECT iddepo, nomdepo, marca FROM deposito ORDER BY nomdepo ASC');
        return $st->fetchAll();
    }

    public function departamentos(): array
    {
        $st = Db::pdo()->query('SELECT codepar, nomdepar FROM departa ORDER BY nomdepar ASC');
        return $st->fetchAll();
    }

    public function movimientos(int $idprodu, ?int $idcodgusto = null, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [':idp' => $idprodu];
        $where = 'sd.idprodu = :idp';

        if ($idcodgusto) {
            $where .= ' AND sd.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }

        $sql = "
            SELECT sd.*, sc.fecha AS mov_fecha, sc.iddepoh, sc.iddepod,
                   dh.nomdepo AS nom_depoh, dd.nomdepo AS nom_depod,
                   g.nomgusto, g.codscan
            FROM stockdet sd
            INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
            LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
            LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
            LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
            WHERE {$where}
            ORDER BY sc.fecha DESC, sd.idstockcab DESC
            LIMIT {$limit}
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $st = Db::pdo()->prepare('
            SELECT idprodu, codprodu, produ, stocact, precomp, precio
            FROM producto
            WHERE produ LIKE :like OR codprodu LIKE :like OR codprodup LIKE :like
            ORDER BY produ ASC LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function variantesPorProducto(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT idcodgusto, nomgusto, codscan, stockact
            FROM gustos
            WHERE idprodu = :id AND discont = 0
            ORDER BY nomgusto ASC
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }

    public function registrarAjuste(
        int $idprodu,
        ?int $idcodgusto,
        int $iddepo,
        int $cantidad,
        string $motivo,
        int $adminUserId
    ): int {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // VFP convention:
            // iddepoh = deposit receiving goods (adds to stock)
            // iddepod = deposit sending goods (subtracts from stock)
            // canti is always positive
            if ($cantidad >= 0) {
                $iddepoh = $iddepo;
                $iddepod = null;
                $canti = $cantidad;
            } else {
                $iddepoh = null;
                $iddepod = $iddepo;
                $canti = abs($cantidad);
            }

            // 1. Insert stockcab
            $st = $pdo->prepare('
                INSERT INTO stockcab (iddepoh, iddepod, fecha, observ)
                VALUES (:depoh, :depod, CURDATE(), :obs)
            ');
            $st->execute([':depoh' => $iddepoh, ':depod' => $iddepod, ':obs' => 'Ajuste manual: ' . $motivo]);
            $cabId = (int)$pdo->lastInsertId();

            // 2. Insert stockdet
            $st = $pdo->prepare('
                INSERT INTO stockdet (idstockcab, idprodu, idcodgusto, canti)
                VALUES (:cab, :prod, :gusto, :cant)
            ');
            $st->execute([
                ':cab' => $cabId,
                ':prod' => $idprodu,
                ':gusto' => $idcodgusto ?: null,
                ':cant' => $canti,
            ]);

            // 3. Update/insert stock table per deposit
            $st = $pdo->prepare('
                SELECT idstock, stock FROM stock
                WHERE iddepo = :depo AND idprodu = :prod AND (idcodgusto = :gusto OR (idcodgusto IS NULL AND :gusto2 IS NULL))
                LIMIT 1
            ');
            $st->execute([
                ':depo' => $iddepo,
                ':prod' => $idprodu,
                ':gusto' => $idcodgusto ?: null,
                ':gusto2' => $idcodgusto ?: null,
            ]);
            $existing = $st->fetch();

            $netChange = $cantidad;
            if ($existing) {
                $newStock = (int)$existing['stock'] + $netChange;
                $st = $pdo->prepare('UPDATE stock SET stock = :s WHERE idstock = :id LIMIT 1');
                $st->execute([':s' => max(0, $newStock), ':id' => (int)$existing['idstock']]);
            } else {
                $newStock = max(0, $netChange);
                $st = $pdo->prepare('
                    INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                    VALUES (:depo, :prod, :gusto, :s)
                ');
                $st->execute([
                    ':depo' => $iddepo,
                    ':prod' => $idprodu,
                    ':gusto' => $idcodgusto ?: null,
                    ':s' => $newStock,
                ]);
            }

            // 4. Recalculate producto.stocact
            $st = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idprodu = :prod');
            $st->execute([':prod' => $idprodu]);
            $totalStock = (int)$st->fetchColumn();
            $pdo->prepare('UPDATE producto SET stocact = :s WHERE idprodu = :id LIMIT 1')
                ->execute([':s' => $totalStock, ':id' => $idprodu]);

            // 5. Update gustos.stockact if variant
            if ($idcodgusto) {
                $st = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idcodgusto = :g');
                $st->execute([':g' => $idcodgusto]);
                $variantStock = (int)$st->fetchColumn();
                $pdo->prepare('UPDATE gustos SET stockact = :s WHERE idcodgusto = :id LIMIT 1')
                    ->execute([':s' => $variantStock, ':id' => $idcodgusto]);
            }

            $pdo->commit();
            return $cabId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── Recalcular stock desde movimientos ──

    public function recalcular(): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Rebuild stock table from stockdet + stockcab (VFP convention)
            // iddepoh = goods entering deposit (adds), iddepod = goods leaving deposit (subtracts)
            $pdo->exec('DELETE FROM stock');
            $pdo->exec('
                INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                SELECT mov.iddepo, mov.idprodu, mov.idcodgusto, SUM(mov.net) AS stock
                FROM (
                    SELECT sc.iddepoh AS iddepo, sd.idprodu, sd.idcodgusto, sd.canti AS net
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    WHERE sc.iddepoh IS NOT NULL
                    UNION ALL
                    SELECT sc.iddepod AS iddepo, sd.idprodu, sd.idcodgusto, -sd.canti AS net
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    WHERE sc.iddepod IS NOT NULL
                ) mov
                GROUP BY mov.iddepo, mov.idprodu, mov.idcodgusto
                HAVING stock != 0
            ');
            $inserted = (int)$pdo->query('SELECT ROW_COUNT()')->fetchColumn();

            // 2. Recalculate producto.stocact
            $pdo->exec('
                UPDATE producto p
                LEFT JOIN (
                    SELECT idprodu, COALESCE(SUM(stock), 0) AS total
                    FROM stock
                    GROUP BY idprodu
                ) s ON s.idprodu = p.idprodu
                SET p.stocact = COALESCE(s.total, 0)
            ');

            // 3. Recalculate gustos.stockact
            $pdo->exec('
                UPDATE gustos g
                LEFT JOIN (
                    SELECT idcodgusto, COALESCE(SUM(stock), 0) AS total
                    FROM stock
                    WHERE idcodgusto IS NOT NULL
                    GROUP BY idcodgusto
                ) s ON s.idcodgusto = g.idcodgusto
                SET g.stockact = COALESCE(s.total, 0)
            ');

            $pdo->commit();
            return $inserted;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── Grilla de reposición ──

    public function grillaRubros(): array
    {
        $st = Db::pdo()->query('SELECT codrub, nomrub FROM rubros ORDER BY nomrub ASC');
        return $st->fetchAll();
    }

    public function grillaSubrubros(int $codrub = 0): array
    {
        if ($codrub > 0) {
            $st = Db::pdo()->prepare('SELECT codsub, nomsub FROM subrubro WHERE codrub = :cr ORDER BY nomsub ASC');
            $st->execute([':cr' => $codrub]);
        } else {
            $st = Db::pdo()->query('SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC');
        }
        return $st->fetchAll();
    }

    public function grillaProveedores(): array
    {
        $st = Db::pdo()->query("
            SELECT DISTINCT pv.codprove, pv.razon AS nomprovee
            FROM producto p
            INNER JOIN proveedo pv ON pv.codprove = p.codprove
            WHERE p.enweb = 1 AND p.codprove IS NOT NULL AND p.codprove > 0
            ORDER BY pv.razon ASC
        ");
        return $st->fetchAll();
    }

    public function grillaProductos(string $q = '', int $codrub = 0, int $codsub = 0, int $codprove = 0, string $desde = '', string $hasta = '', int $limit = 500): array
    {
        $limit = max(1, min(1000, $limit));
        $params = [];
        $where = ['p.enweb = 1'];

        if ($codrub > 0) {
            $where[] = 'p.codrub = :cr';
            $params[':cr'] = $codrub;
        }
        if ($codsub > 0) {
            $where[] = 'p.codsub = :cs';
            $params[':cs'] = $codsub;
        }
        if ($codprove > 0) {
            $where[] = 'p.codprove = :cp';
            $params[':cp'] = $codprove;
        }

        $q = trim($q);
        if ($q !== '') {
            $where[] = '(p.produ LIKE :q1 OR p.codprodu LIKE :q2 OR p.codprodup LIKE :q3 OR EXISTS (SELECT 1 FROM gustos g2 WHERE g2.idprodu = p.idprodu AND (g2.codscan LIKE :q4 OR g2.nomgusto LIKE :q5)))';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
            $params[':q4'] = '%' . $q . '%';
            $params[':q5'] = '%' . $q . '%';
        }

        $desde = trim($desde);
        $hasta = trim($hasta);
        $ventasJoin = '';
        if ($desde === '' && $hasta === '') {
            $ventasJoin = 'LEFT JOIN (SELECT fi.idprodu, SUM(fi.qty) AS vendidos FROM factura_items fi INNER JOIN facturas f ON f.id = fi.factura_id AND f.estado = \'emitida\' AND f.fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY fi.idprodu) v ON v.idprodu = p.idprodu';
        } else {
            if ($desde === '') $desde = '2000-01-01';
            if ($hasta === '') $hasta = date('Y-m-d');
            $params[':vdesde'] = $desde;
            $params[':vhasta'] = $hasta;
            $ventasJoin = 'LEFT JOIN (SELECT fi.idprodu, SUM(fi.qty) AS vendidos FROM factura_items fi INNER JOIN facturas f ON f.id = fi.factura_id AND f.estado = \'emitida\' AND f.fecha BETWEEN :vdesde AND :vhasta GROUP BY fi.idprodu) v ON v.idprodu = p.idprodu';
        }

        $sql = "
            SELECT p.idprodu, p.codprodu, p.produ, p.precomp, p.stocact, p.codprove, p.codprodup, p.precio, p.imagen,
                   pv.razon AS nomprovee,
                   (SELECT MIN(g.codscan) FROM gustos g WHERE g.idprodu = p.idprodu AND g.codscan IS NOT NULL AND g.codscan != '' LIMIT 1) AS codscan,
                   COALESCE(v.vendidos, 0) AS vendidos
            FROM producto p
            LEFT JOIN proveedo pv ON pv.codprove = p.codprove
            {$ventasJoin}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY v.vendidos DESC, p.produ ASC
            LIMIT {$limit}
        ";

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
