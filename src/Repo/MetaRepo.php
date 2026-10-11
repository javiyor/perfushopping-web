<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class MetaRepo
{
    /** @return array<int, array<string,mixed>> */
    public function rubros(): array
    {
        $st = Db::pdo()->query("SELECT codrub, nomrub FROM rubros WHERE codrub NOT IN (228, 192, 193, 198, 146) ORDER BY nomrub ASC");
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function marcas(): array
    {
        $st = Db::pdo()->query("SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC");
        return $st->fetchAll();
    }

    /** Marcas con al menos un producto visible con stock en depósitos operativos.
     *  @return array<int, array<string,mixed>> */
    public function marcasConStock(): array
    {
        $st = Db::pdo()->query("
            SELECT DISTINCT s.codsub, s.nomsub
            FROM subrubro s
            INNER JOIN producto p ON p.codsub = s.codsub AND p.enweb = 1
            INNER JOIN gustos g ON g.idprodu = p.idprodu AND g.discont = 0
            INNER JOIN (
                SELECT st.idcodgusto
                FROM stock st
                INNER JOIN deposito d ON d.iddepo = st.iddepo AND d.marca = 2
                GROUP BY st.idcodgusto
                HAVING SUM(st.stock) > 0
            ) cst ON cst.idcodgusto = g.idcodgusto
            WHERE s.nomsub IS NOT NULL AND s.nomsub <> ''
            ORDER BY s.nomsub ASC
        ");
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function provincias(): array
    {
        $st = Db::pdo()->query("SELECT codprov, provinci, idzona FROM provincias ORDER BY provinci ASC");
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function lugares(): array
    {
        $st = Db::pdo()->query("SELECT cod_lugar, lug_lugar, codpost, codprov FROM lugares ORDER BY codprov ASC, lug_lugar ASC");
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findLugar(int $codLugar): ?array
    {
        if ($codLugar <= 0) {
            return null;
        }

        $st = Db::pdo()->prepare('SELECT cod_lugar, lug_lugar, codpost, codprov FROM lugares WHERE cod_lugar = :id LIMIT 1');
        $st->execute([':id' => $codLugar]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findLugarByNameAndProvince(string $city, int $codprov): ?array
    {
        $city = trim($city);
        if ($city === '' || $codprov <= 0) {
            return null;
        }

        $st = Db::pdo()->prepare('SELECT cod_lugar, lug_lugar, codpost, codprov FROM lugares WHERE codprov = :prov AND TRIM(LOWER(lug_lugar)) = TRIM(LOWER(:city)) LIMIT 1');
        $st->execute([':prov' => $codprov, ':city' => $city]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
