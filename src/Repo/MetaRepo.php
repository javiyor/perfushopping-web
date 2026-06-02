<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class MetaRepo
{
    /** @return array<int, array<string,mixed>> */
    public function rubros(): array
    {
        $st = Db::pdo()->query("SELECT codrub, nomrub FROM rubros ORDER BY nomrub ASC");
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function marcas(): array
    {
        $st = Db::pdo()->query("SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC");
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function provincias(): array
    {
        $st = Db::pdo()->query("SELECT codprov, provinci, idzona FROM provincias ORDER BY provinci ASC");
        return $st->fetchAll();
    }
}
