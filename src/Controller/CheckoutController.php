<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Repo\PromoRepo;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Service\CartService;
use Perfushopping\Web\Service\InstallmentsService;
use Perfushopping\Web\Service\PricingService;
use Perfushopping\Web\Service\ShippingService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CheckoutController
{
    public function index(array $params): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);

        // If a referral is present, require login/registration so commissions are not anonymous.
        $hasRef = isset($_COOKIE['ref_code']) && is_string($_COOKIE['ref_code']) && trim((string)$_COOKIE['ref_code']) !== '';
        if (!$isWholesale && $hasRef && !$user) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Para que el referido aplique, tenes que crear cuenta o iniciar sesion.'];
            \Perfushopping\Web\Support\Response::redirect('/register');
        }

        if ($isWholesale) {
            // Wholesale checkout requires login (already implied) and only transferencia
            $auth->requireLogin();
        }

        $meta = new MetaRepo();
        $promoRepo = new PromoRepo();
        $form = $_SESSION['checkout_form'] ?? [];
        $selectedLugarId = (int)($form['cod_lugar'] ?? 0);
        if ($selectedLugarId <= 0) {
            $selectedProv = (int)($form['province_codprov'] ?? 0);
            $selectedCity = trim((string)($form['city'] ?? ''));
            $selectedLugar = $meta->findLugarByNameAndProvince($selectedCity, $selectedProv);
            $selectedLugarId = (int)($selectedLugar['cod_lugar'] ?? 0);
        }

        // For display: compute installment promo (all cards) based on current cart total (with IVA).
        $cart = new CartService();
        $repo = new ProductRepo();
        $pricing = new PricingService();
        $totalCents = 0;
        foreach ($cart->items() as $idcodgusto => $qty) {
            $v = $repo->findVariant($idcodgusto);
            if (!$v) {
                continue;
            }
            $unitNet = $pricing->cents((float)($isWholesale ? $v['precio1'] : $v['precio']));
            $lineNet = $unitNet * $qty;
            $lineIva = $pricing->ivaCents($lineNet, (float)($v['tiva'] ?? 0));
            $totalCents += ($lineNet + $lineIva);
        }
        $inst = null;
        if (!$isWholesale && $totalCents > 0) {
            $weekday = (int)date('w') + 1; // 1=domingo
            $inst = (new InstallmentsService())->computeAllCardsPromo($totalCents, $weekday);
        }

        $creditBalance = 0;
        if ($user && !$isWholesale) {
            try {
                $creditBalance = (new AffiliateLedgerRepo())->balanceAvailableCents((int)$user['id']);
            } catch (\Throwable $e) {
                $creditBalance = 0;
            }
        }

        // Compute Correo Argentino cost for selected province (if any)
        $selectedProv = (int)($form['province_codprov'] ?? 0);
        $correoCost = null;
        if ($selectedProv > 0) {
            $correoCost = (new ShippingService())->correoCostForProvince($selectedProv, $totalCents);
        }

        echo View::page('checkout.php', [
            'csrf' => Csrf::token(),
            'user' => $user,
            'isWholesale' => $isWholesale,
            'provincias' => $meta->provincias(),
            'lugares' => $meta->lugares(),
            'tarjetas' => $promoRepo->tarjetas(),
            'inst' => $inst,
            'creditBalance' => $creditBalance,
            'form' => $form,
            'selectedLugarId' => $selectedLugarId,
            'shippingOptions' => (new ShippingService())->deliveryLocalOptionsForDestination((string)(($_SESSION['checkout_form'] ?? [])['city'] ?? ''), (int)(($_SESSION['checkout_form'] ?? [])['province_codprov'] ?? 0)),
            'correoCost' => $correoCost,
            'cartTotalCents' => $totalCents,
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function submit(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);
        if ($isWholesale) {
            $auth->requireLogin();
        }

        // If a referral is present, require login/registration so commissions are not anonymous.
        $hasRef = isset($_COOKIE['ref_code']) && is_string($_COOKIE['ref_code']) && trim((string)$_COOKIE['ref_code']) !== '';
        if (!$isWholesale && $hasRef && !$user) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Para que el referido aplique, tenes que crear cuenta o iniciar sesion antes de pagar.'];
            \Perfushopping\Web\Support\Response::redirect('/register');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $postal = trim((string)($_POST['postal_code'] ?? ''));
        $prov = (int)($_POST['province_codprov'] ?? 0);
        $codLugar = (int)($_POST['cod_lugar'] ?? 0);
        if ($codLugar > 0) {
            $lugar = (new MetaRepo())->findLugar($codLugar);
            if ($lugar && (int)($lugar['codprov'] ?? 0) === $prov) {
                $city = trim((string)($lugar['lug_lugar'] ?? $city));
                if ($postal === '') {
                    $postal = trim((string)($lugar['codpost'] ?? ''));
                }
            }
        }
        $shippingChoice = trim((string)($_POST['shipping_choice'] ?? ''));
        $paymentMethod = trim((string)($_POST['payment_method'] ?? ''));
        $tarjetaId = (int)($_POST['tarjeta_id'] ?? 0);
        $cuotas = (int)($_POST['cuotas'] ?? 3);
        $creditUseRaw = trim((string)($_POST['credit_use'] ?? ''));
        $shippingTime = trim((string)($_POST['shipping_time'] ?? ''));

        $form = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal,
            'province_codprov' => $prov,
            'cod_lugar' => $codLugar,
            'shipping_choice' => $shippingChoice,
            'shipping_time' => $shippingTime,
            'payment_method' => $paymentMethod,
            'tarjeta_id' => $tarjetaId,
            'cuotas' => $cuotas,
            'credit_use' => $creditUseRaw,
        ];
        $_SESSION['checkout_form'] = $form;

        $errors = [];
        foreach (['name','email','phone','address','city','postal_code'] as $f) {
            if (($form[$f] ?? '') === '') {
                $errors[] = 'Completa ' . $f . '.';
            }
        }
        if ($prov <= 0) {
            $errors[] = 'Selecciona provincia.';
        }
        if ($codLugar <= 0) {
            $errors[] = 'Selecciona localidad.';
        }
        if (!$isWholesale && $paymentMethod !== 'transfer' && $tarjetaId <= 0) {
            $errors[] = 'Selecciona tarjeta.';
        }
        if ($cuotas !== 3 && $cuotas !== 6) {
            $cuotas = 3;
        }

        if ($errors) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => implode(' ', $errors)];
            Response::redirect('/checkout');
        }

        // Build cart lines
        $cart = new CartService();
        $repo = new ProductRepo();
        $pricing = new PricingService();
        $items = [];
        $subtotalNet = 0;
        $iva = 0;
        $total = 0;
        foreach ($cart->items() as $idcodgusto => $qty) {
            $v = $repo->findVariant($idcodgusto);
            if (!$v) {
                continue;
            }
            if ((float)($v['stockact'] ?? 0) <= 0) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Hay productos sin stock en el carrito.'];
                Response::redirect('/cart');
            }
            $unitNet = $pricing->cents((float)($isWholesale ? $v['precio1'] : $v['precio']));
            $lineNet = $unitNet * $qty;
            $lineIva = $pricing->ivaCents($lineNet, (float)($v['tiva'] ?? 0));
            $lineTotal = $lineNet + $lineIva;

            $subtotalNet += $lineNet;
            $iva += $lineIva;
            $total += $lineTotal;

            $items[] = [
                'idprodu' => (int)$v['idprodu'],
                'idcodgusto' => (int)$v['idcodgusto'],
                'product_name' => (string)$v['produ'],
                'variant_name' => (string)($v['nomgusto'] ?? ''),
                'qty' => $qty,
                'unit_net_cents' => $unitNet,
                'iva_rate' => (float)($v['tiva'] ?? 0),
                'line_net_cents' => $lineNet,
                'line_iva_cents' => $lineIva,
                'line_total_cents' => $lineTotal,
            ];
        }
        if (!$items) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Tu carrito esta vacio.'];
            Response::redirect('/cart');
        }

        // Promotions apply only to retail MP payments
        $discountPercent = 0.0;
        $recargoPercent = 0.0;
        if (!$isWholesale && $paymentMethod !== 'transfer') {
            $weekday = (int)date('w') + 1; // 1=domingo
            $promo = (new PromoRepo())->bestPromoForTarjeta($tarjetaId, $weekday);
            if ($promo) {
                $discountPercent = (float)($promo['descuento'] ?? 0);
                $recargoPercent = (float)($promo['recargo'] ?? 0);
            }
        }

        // Apply discount on net
        $subtotalNetDisc = $pricing->applyDiscountNet($subtotalNet, $discountPercent);
        $ivaDisc = $pricing->ivaCents($subtotalNetDisc, ($subtotalNet > 0) ? ((float)$iva * 100.0 / (float)$subtotalNet) : 0.0);
        // Note: IVA rate can vary by item. For now, we recompute by proportional ratio. MVP acceptable.
        // Better: compute per line with discount applied.
        $discountCents = $subtotalNet - $subtotalNetDisc;

        // Compute IVA precisely per line after discount
        $ivaDisc = 0;
        $totalDisc = 0;
        foreach ($items as &$it) {
            $lineNetDisc = $pricing->applyDiscountNet($it['line_net_cents'], $discountPercent);
            $lineIvaDisc = $pricing->ivaCents($lineNetDisc, (float)$it['iva_rate']);
            $it['line_net_cents'] = $lineNetDisc;
            $it['line_iva_cents'] = $lineIvaDisc;
            $it['line_total_cents'] = $lineNetDisc + $lineIvaDisc;
            $ivaDisc += $lineIvaDisc;
            $totalDisc += $it['line_total_cents'];
        }
        unset($it);
        $subtotalNetDisc = array_sum(array_map(static fn($x) => (int)$x['line_net_cents'], $items));
        $discountCents = $subtotalNet - $subtotalNetDisc;

        // Shipping
        $provName = '';
        foreach ((new MetaRepo())->provincias() as $pr) {
            if ((int)$pr['codprov'] === $prov) {
                $provName = (string)$pr['provinci'];
                break;
            }
        }
        if ($provName === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Provincia invalida.'];
            Response::redirect('/checkout');
        }
        $shipping = new ShippingService();

        $shippingCost = null;
        $shippingMethod = null;
        $shippingDetail = null;

        if ($shippingChoice === 'correo') {
            $correoInfo = $shipping->correoCostForProvince($prov, $totalDisc);
            if ($correoInfo === null) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Correo Argentino no disponible para tu provincia.'];
                Response::redirect('/checkout');
            }
            $shippingCost = $correoInfo['final_cents'];
            $shippingMethod = 'correo_argentino';
            $shippingDetail = 'Correo Argentino';
        } elseif (str_starts_with($shippingChoice, 'local_')) {
            $opts = $shipping->deliveryLocalOptionsForDestination($city, $prov);
            $pick = null;
            foreach ($opts as $o) {
                if ($o['id'] === $shippingChoice) {
                    $pick = $o;
                    break;
                }
            }
            if (!$pick) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Delivery local no disponible para tu localidad.'];
                Response::redirect('/checkout');
            }
            $shippingCost = (int)$pick['price_cents'];
            $shippingMethod = 'local_delivery';
            $shippingDetail = (string)$pick['label'];
            if (str_ends_with($shippingChoice, '_gratis') && $shippingTime !== '') {
                $shippingDetail .= ' - ' . $shippingTime . ' hs';
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Selecciona metodo de envio.'];
            Response::redirect('/checkout');
        }

        // Minimum order
        if ($totalDisc < 3000000) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'El minimo de compra es $30.000 (con descuento aplicado).'];
            Response::redirect('/cart');
        }

        // Affiliate credit (only for logged-in retail). Applies only to products, max 50%.
        $creditApplied = 0;
        if (!$isWholesale && $user) {
            $balance = 0;
            try {
                $balance = (new AffiliateLedgerRepo())->balanceAvailableCents((int)$user['id']);
            } catch (\Throwable $e) {
                $balance = 0;
            }
            if ($balance > 0 && $creditUseRaw !== '') {
                $want = (float)str_replace([',', ' '], ['.', ''], $creditUseRaw);
                $wantCents = (int)round($want * 100);
                $maxByRule = (int)floor($totalDisc * 0.50);
                $creditApplied = max(0, min($wantCents, $balance, $maxByRule, $totalDisc));
            }
        }

        $productsAfterCredit = $totalDisc - $creditApplied;
        $totalFinal = $productsAfterCredit + (int)$shippingCost;
        $totalForMp = $totalFinal;
        if (!$isWholesale && $cuotas === 6 && $recargoPercent > 0) {
            $totalForMp = (int)round($totalFinal * (1.0 + $recargoPercent / 100.0));
        }

        // Create order
        $code = strtoupper(bin2hex(random_bytes(8)));
        $orderStatus = ($isWholesale || $paymentMethod === 'transfer') ? 'pending_transfer' : 'pending_payment';
        $orderId = (new OrderRepo())->create([
            'order_code' => $code,
            'user_id' => $user ? (int)$user['id'] : null,
            'customer_type' => $isWholesale ? 'wholesale' : 'retail',
            'status' => $orderStatus,
            'email' => $email,
            'phone' => $phone,
            'ship_name' => $name,
            'ship_address' => $address,
            'ship_city' => $city,
            'ship_postal_code' => $postal,
            'ship_province_codprov' => $prov,
            'ship_cod_lugar' => $codLugar > 0 ? $codLugar : null,
            'ship_province_name' => $provName,
            'shipping_method' => $shippingMethod,
            'shipping_detail' => $shippingDetail,
            'shipping_cost_cents' => (int)$shippingCost,
            'subtotal_net_cents' => $subtotalNetDisc,
            'discount_percent' => $discountPercent,
            'discount_cents' => $discountCents,
            'iva_cents' => $ivaDisc,
            'total_cents' => $totalFinal,
        ], $items);

        // Apply affiliate credit spend
        if (!$isWholesale && $user && $creditApplied > 0) {
            try {
                (new AffiliateLedgerRepo())->addSpendOnOrder((int)$user['id'], $orderId, $creditApplied, 'Uso de credito en compra');
            } catch (\Throwable $e) {
                // If ledger missing, ignore
            }
        }

        // If logged-in and profile address is empty, store it (recommended)
        if ($user) {
            (new UserRepo())->setProfileAddressIfEmpty((int)$user['id'], $address, $city, $postal, $prov);
        }

        // Clear cart
        $cart->clear();
        unset($_SESSION['checkout_form']);

        if ($isWholesale || $paymentMethod === 'transfer') {
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Pedido generado. Te mostramos los datos de transferencia.'];
            Response::redirect('/pay/mp/pending?order=' . urlencode($code) . '&mode=transfer');
        }

        // Store MP checkout intent in session
        $_SESSION['mp_checkout'] = [
            'order_id' => $orderId,
            'order_code' => $code,
            'total_cents' => $totalForMp,
            'tarjeta_id' => $tarjetaId,
            'cuotas' => $cuotas,
        ];

        Response::redirect('/pay/mp/start');
    }
}
