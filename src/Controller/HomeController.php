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
        $products = (new ProductRepo())->list([
            'q' => $_GET['q'] ?? '',
            'codrub' => $_GET['codrub'] ?? 0,
            'codsub' => $_GET['codsub'] ?? 0,
        ]);
        $auth = new AuthService();
        $user = $auth->user();

        echo View::page('home.php', [
            'products' => $products,
            'rubros' => $meta->rubros(),
            'marcas' => $meta->marcas(),
            'user' => $user,
            'isWholesale' => $auth->isWholesaleApproved($user),
        ]);
    }
}
