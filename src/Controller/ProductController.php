<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProductController
{
    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::notFound();
            return;
        }
        $repo = new ProductRepo();
        $p = $repo->find($id);
        if (!$p) {
            Response::notFound();
            return;
        }
        $variants = $repo->variants($id);
        $auth = new AuthService();
        $user = $auth->user();

        echo View::page('product.php', [
            'product' => $p,
            'variants' => $variants,
            'user' => $user,
            'isWholesale' => $auth->isWholesaleApproved($user),
        ]);
    }
}
