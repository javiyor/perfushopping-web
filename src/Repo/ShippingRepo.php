<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ShippingRepo
{
    public function correoImporteCents(int $idzona): ?int
    {
        // Correo Argentino idtranspor=6
        $st = Db::pdo()->prepare('SELECT importe FROM envios WHERE idtransporte=6 AND idzona=:z LIMIT 1');
        $st->execute([':z' => $idzona]);
        $r = $st->fetch();
        if (!$r) {
            return null;
        }
        return (int)round(((float)$r['importe']) * 100);
    }

    public function isLocalityAllowed(string $localityKey): bool
    {
        $st = Db::pdo()->prepare('SELECT 1 FROM local_delivery_localities WHERE locality_key=:k AND active=1 LIMIT 1');
        $st->execute([':k' => $localityKey]);
        return (bool)$st->fetchColumn();
    }

    public function localPriceCents(string $localityKey): ?int
    {
        $st = Db::pdo()->prepare('SELECT price_cents FROM local_delivery_prices WHERE locality_key=:k LIMIT 1');
        $st->execute([':k' => $localityKey]);
        $v = $st->fetchColumn();
        if ($v === false) {
            return null;
        }
        return (int)$v;
    }
}
