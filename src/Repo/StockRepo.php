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
            WHERE g.idprodu = :id
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
            INNER JOIN deposito d ON d.iddepo = s.iddepo
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
        $st = Db::pdo()->query('SELECT codepar, nomdepar FROM departa WHERE activo = 1 OR activo IS NULL ORDER BY nomdepar ASC');
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
            // 1. Insert stockcab
            $st = $pdo->prepare('
                INSERT INTO stockcab (iddepoh, iddepod, fecha, observ)
                VALUES (NULL, :depo, CURDATE(), :obs)
            ');
            $st->execute([':depo' => $iddepo, ':obs' => 'Ajuste manual: ' . $motivo]);
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
                ':cant' => $cantidad,
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

            if ($existing) {
                $newStock = (int)$existing['stock'] + $cantidad;
                $st = $pdo->prepare('UPDATE stock SET stock = :s WHERE idstock = :id LIMIT 1');
                $st->execute([':s' => max(0, $newStock), ':id' => (int)$existing['idstock']]);
            } else {
                $newStock = max(0, $cantidad);
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
}
