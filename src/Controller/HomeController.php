<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\View;

final class HomeController
{
    public function index(array $params): void
    {
        $meta = new MetaRepo();
        $q = trim((string)($_GET['q'] ?? ''));
        $codrub = (int)($_GET['codrub'] ?? 0);
        $codsub = (int)($_GET['codsub'] ?? 0);
        $isFiltered = ($q !== '' || $codrub > 0 || $codsub > 0);

        if ($isFiltered) {
            $products = (new ProductRepo())->list(['q' => $q, 'codrub' => $codrub, 'codsub' => $codsub]);
            $portadaInfo = null;
        } else {
            $portadaRepo = new \Perfushopping\Web\Repo\PortadaRepo();
            $cfg = $portadaRepo->getConfig();
            $products = (new ProductRepo())->portada($cfg);
            $portadaInfo = $cfg;
        }

        $auth = new AuthService();
        $user = $auth->user();

        echo View::page('home.php', [
            'products' => $products,
            'rubros' => $meta->rubros(),
            'marcas' => $meta->marcas(),
            'user' => $user,
            'isWholesale' => $auth->isWholesaleApproved($user),
            'portadaInfo' => $portadaInfo,
        ]);
    }
}
