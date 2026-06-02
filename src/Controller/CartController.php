<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Service\CartService;
use Perfushopping\Web\Service\PricingService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CartController
{
    public function view(array $params): void
    {
        $cart = new CartService();
        $repo = new ProductRepo();
        $pricing = new PricingService();
        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);

        $items = [];
        $subtotalNet = 0;
        $iva = 0;
        $total = 0;
        foreach ($cart->items() as $idcodgusto => $qty) {
            $v = $repo->findVariant($idcodgusto);
            if (!$v) {
                continue;
            }
            $unitNet = $pricing->cents((float)($isWholesale ? $v['precio1'] : $v['precio']));
            $lineNet = $unitNet * $qty;
            $lineIva = $pricing->ivaCents($lineNet, (float)($v['tiva'] ?? 0));
            $lineTotal = $lineNet + $lineIva;

            $subtotalNet += $lineNet;
            $iva += $lineIva;
            $total += $lineTotal;

            $items[] = [
                'idcodgusto' => $idcodgusto,
                'qty' => $qty,
                'variant' => $v,
                'unit_net_cents' => $unitNet,
                'line_net_cents' => $lineNet,
                'line_iva_cents' => $lineIva,
                'line_total_cents' => $lineTotal,
            ];
        }

        echo View::page('cart.php', [
            'items' => $items,
            'subtotalNet' => $subtotalNet,
            'iva' => $iva,
            'total' => $total,
            'csrf' => Csrf::token(),
            'user' => $user,
            'isWholesale' => $isWholesale,
        ]);
    }

    public function add(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['idcodgusto'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($id <= 0) {
            Response::redirect('/cart');
        }
        // Validate stock/discont
        $v = (new ProductRepo())->findVariant($id);
        if (!$v || (float)($v['stockact'] ?? 0) <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Sin stock o no disponible.'];
            Response::redirect('/cart');
        }
        (new CartService())->add($id, $qty);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Agregado al carrito.'];
        Response::redirect('/cart');
    }

    public function update(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['idcodgusto'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 0);
        if ($id > 0) {
            (new CartService())->update($id, $qty);
        }
        Response::redirect('/cart');
    }

    public function remove(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['idcodgusto'] ?? 0);
        if ($id > 0) {
            (new CartService())->remove($id);
        }
        Response::redirect('/cart');
    }

    public function clear(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        (new CartService())->clear();
        Response::redirect('/cart');
    }
}
