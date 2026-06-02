<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PromoRepo
{
    /** @return array<int, array<string,mixed>> */
    public function tarjetas(): array
    {
        $st = Db::pdo()->query('SELECT idtarje, nomtar FROM tarjeta ORDER BY nomtar ASC');
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function bestPromoForTarjeta(int $idtarje, int $weekday1to7): ?array
    {
        $st = Db::pdo()->prepare(
            'SELECT idpromotar, descrip, recargo, descuento FROM tarjepromo WHERE idtarje=:t AND CURDATE() BETWEEN fecha AND vecim AND (diasemana=0 OR diasemana=:d) ORDER BY (descuento IS NULL) ASC, descuento DESC, (recargo IS NULL) ASC, recargo ASC LIMIT 1'
        );
        $st->execute([':t' => $idtarje, ':d' => $weekday1to7]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /**
     * Promo for all Mercado Pago cards: idtarje=99 and web=1.
     * Falls back gracefully if the column `web` does not exist.
     * @return array<string,mixed>|null
     */
    public function bestWebPromoForAllCards(int $weekday1to7): ?array
    {
        $pdo = Db::pdo();
        $sqlWithWeb = "SELECT idpromotar, descrip, recargo, descuento FROM tarjepromo WHERE idtarje=99 AND web=1 AND CURDATE() BETWEEN fecha AND vecim AND (diasemana=0 OR diasemana=:d) ORDER BY (descuento IS NULL) ASC, descuento DESC, (recargo IS NULL) ASC, recargo ASC LIMIT 1";
        $sqlNoWeb = "SELECT idpromotar, descrip, recargo, descuento FROM tarjepromo WHERE idtarje=99 AND CURDATE() BETWEEN fecha AND vecim AND (diasemana=0 OR diasemana=:d) ORDER BY (descuento IS NULL) ASC, descuento DESC, (recargo IS NULL) ASC, recargo ASC LIMIT 1";

        try {
            $st = $pdo->prepare($sqlWithWeb);
            $st->execute([':d' => $weekday1to7]);
            $r = $st->fetch();
            return $r ?: null;
        } catch (\PDOException $e) {
            $st = $pdo->prepare($sqlNoWeb);
            $st->execute([':d' => $weekday1to7]);
            $r = $st->fetch();
            return $r ?: null;
        }
    }
}
