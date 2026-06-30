<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ProductRepo
{
    private const EXCLUDED_CODRUBS = [228, 192, 193, 198, 146];

    /** @return array<int, array<string,mixed>> */
    public function list(array $opts): array
    {
        $pdo = Db::pdo();
        $q = trim((string)($opts['q'] ?? ''));
        $codrub = (int)($opts['codrub'] ?? 0);
        $codsub = (int)($opts['codsub'] ?? 0);

        // Always search/filter within products visible on the web.
        $where = ['p.enweb = 1'];
        $params = [];
        $excluded = implode(',', array_map('intval', self::EXCLUDED_CODRUBS));
        $where[] = 'p.codrub NOT IN (' . $excluded . ')';
        $where[] = 'LOWER(p.produ) NOT LIKE :tester';
        $params[':tester'] = '%tester%';
        $where[] = 'p.fecompra IS NOT NULL';
        $where[] = "p.fecompra <> '0000-00-00'";
        $where[] = 'p.fecompra > DATE_SUB(CURDATE(), INTERVAL 6 MONTH)';

        // If the user is not searching or filtering, show only "novedades" (<30 days).
        if ($q === '' && $codrub <= 0 && $codsub <= 0) {
            $where[] = 'p.fecalta IS NOT NULL';
            $where[] = "p.fecalta <> '0000-00-00'";
            $where[] = 'p.fecalta <= CURDATE()';
            $where[] = 'p.fecalta > DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
        }
        if ($codrub > 0) {
            $where[] = 'p.codrub = :codrub';
            $params[':codrub'] = $codrub;
        }
        if ($codsub > 0) {
            $where[] = 'p.codsub = :codsub';
            $params[':codsub'] = $codsub;
        }
        if ($q !== '') {
            $where[] = '(MATCH(p.produ) AGAINST (:q IN BOOLEAN MODE) OR EXISTS (SELECT 1 FROM gustos g WHERE g.idprodu=p.idprodu AND g.discont=0 AND (g.nomgusto LIKE :qlike OR g.codscan LIKE :qlike)))';
            $params[':q'] = $q . '*';
            $params[':qlike'] = '%' . $q . '%';
        }

        $sql = "
            SELECT
              p.idprodu, p.produ, p.precio, p.precio1, p.imagen,
              r.nomrub,
              s.nomsub,
              i.tiva,
              (SELECT COUNT(*) FROM gustos g WHERE g.idprodu=p.idprodu AND g.discont=0) AS variants_count
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.codrub ASC, p.codsub ASC, p.produ ASC
            LIMIT 80
        ";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $idprodu): ?array
    {
        $pdo = Db::pdo();
        $excluded = implode(',', array_map('intval', self::EXCLUDED_CODRUBS));
        $st = $pdo->prepare("SELECT p.*, r.nomrub, s.nomsub, i.tiva FROM producto p LEFT JOIN rubros r ON r.codrub=p.codrub LEFT JOIN subrubro s ON s.codsub=p.codsub LEFT JOIN ivaprodu i ON i.codivaprodu=p.iva WHERE p.idprodu=:id AND p.enweb=1 AND p.codrub NOT IN (" . $excluded . ") AND LOWER(p.produ) NOT LIKE :tester LIMIT 1");
        $st->execute([':id' => $idprodu, ':tester' => '%tester%']);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function variants(int $idprodu): array
    {
        $pdo = Db::pdo();
        $excluded = implode(',', array_map('intval', self::EXCLUDED_CODRUBS));
        $st = $pdo->prepare("
            SELECT
                g.idcodgusto,
                g.nomgusto,
                g.codscan,
                COALESCE(ent.entradas, 0) - COALESCE(sal.salidas, 0) AS stockact
            FROM gustos g
            LEFT JOIN (
                SELECT sd.idcodgusto, SUM(sd.canti) AS entradas
                FROM stockdet sd
                INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
                INNER JOIN deposito d ON sc.iddepoh = d.iddepo
                INNER JOIN gustos gf ON gf.idcodgusto = sd.idcodgusto
                WHERE d.marca = 2 AND gf.idprodu = :id_ent AND gf.discont = 0
                GROUP BY sd.idcodgusto
            ) ent ON ent.idcodgusto = g.idcodgusto
            LEFT JOIN (
                SELECT sd.idcodgusto, SUM(sd.canti) AS salidas
                FROM stockdet sd
                INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
                INNER JOIN deposito d ON sc.iddepod = d.iddepo
                INNER JOIN gustos gf ON gf.idcodgusto = sd.idcodgusto
                WHERE d.marca = 2 AND gf.idprodu = :id_sal AND gf.discont = 0
                GROUP BY sd.idcodgusto
            ) sal ON sal.idcodgusto = g.idcodgusto
            WHERE g.idprodu = :id AND g.discont = 0
            ORDER BY g.nomgusto ASC
        ");
        $st->execute([':id' => $idprodu, ':id_ent' => $idprodu, ':id_sal' => $idprodu]);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function variantImages(int $idcodgusto): array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare("SELECT rutaimg FROM imagen WHERE idcodgusto=:g ORDER BY idimagen ASC LIMIT 6");
        $st->execute([':g' => $idcodgusto]);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findVariant(int $idcodgusto): ?array
    {
        $pdo = Db::pdo();
        $excluded = implode(',', array_map('intval', self::EXCLUDED_CODRUBS));
        $st = $pdo->prepare("
            SELECT
                g.idcodgusto,
                g.nomgusto,
                g.codscan,
                (
                    COALESCE((
                        SELECT SUM(sd.canti)
                        FROM stockdet sd
                        INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
                        INNER JOIN deposito d ON sc.iddepoh = d.iddepo
                        WHERE sd.idcodgusto = :g_ent AND d.marca = 2
                    ), 0)
                    -
                    COALESCE((
                        SELECT SUM(sd.canti)
                        FROM stockdet sd
                        INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
                        INNER JOIN deposito d ON sc.iddepod = d.iddepo
                        WHERE sd.idcodgusto = :g_sal AND d.marca = 2
                    ), 0)
                ) AS stockact,
                p.idprodu,
                p.produ,
                p.precio,
                p.precio1,
                p.imagen,
                i.tiva
            FROM gustos g
            INNER JOIN producto p ON p.idprodu=g.idprodu
            LEFT JOIN ivaprodu i ON i.codivaprodu=p.iva
            WHERE g.idcodgusto=:g AND g.discont=0 AND p.enweb=1 AND p.codrub NOT IN (" . $excluded . ") AND LOWER(p.produ) NOT LIKE :tester
            LIMIT 1
        ");
        $st->execute([':g' => $idcodgusto, ':g_ent' => $idcodgusto, ':g_sal' => $idcodgusto, ':tester' => '%tester%']);
        $row = $st->fetch();
        return $row ?: null;
    }
}
