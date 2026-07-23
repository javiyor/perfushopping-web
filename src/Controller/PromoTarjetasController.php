<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\PromoTarjetaRepo;
use Perfushopping\Web\Support\View;

final class PromoTarjetasController
{
    public function index(array $params): void
    {
        $promos = (new PromoTarjetaRepo())->findActivos();

        echo View::page('promo-tarjetas.php', [
            'promos' => $promos,
            'pageTitle' => 'Promociones Bancarias Vigentes — Perfushopping',
        ]);
    }
}