<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

// Referral capture (30 days)
if (isset($_GET['ref']) && is_string($_GET['ref'])) {
    $ref = strtoupper(trim($_GET['ref']));
    if ($ref !== '' && preg_match('/^[A-Z0-9]{4,24}$/', $ref)) {
        setcookie('ref_code', $ref, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['ref_code'] = $ref;
    }
}

use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\Router;
use Perfushopping\Web\Controller\HomeController;
use Perfushopping\Web\Controller\ProductController;
use Perfushopping\Web\Controller\CartController;
use Perfushopping\Web\Controller\CheckoutController;
use Perfushopping\Web\Controller\AuthController;
use Perfushopping\Web\Controller\AdminController;
use Perfushopping\Web\Controller\MercadoPagoController;
use Perfushopping\Web\Controller\LegalController;
use Perfushopping\Web\Controller\AffiliateController;
use Perfushopping\Web\Controller\ApiSyncController;
use Perfushopping\Web\Controller\ApiUploadController;
use Perfushopping\Web\Controller\DemoTechController;
use Perfushopping\Web\Controller\AdminProductController;

$router = new Router();

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/p/(?P<id>\d+)', [ProductController::class, 'show']);

// Legal
$router->get('/terms', [LegalController::class, 'terms']);
$router->get('/privacy', [LegalController::class, 'privacy']);
$router->get('/terms/affiliate', [LegalController::class, 'affiliateTerms']);

// Cart
$router->get('/cart', [CartController::class, 'view']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);

// Checkout
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'submit']);

// Payments (minorista)
$router->post('/pay/mp', [MercadoPagoController::class, 'createPreference']);
$router->get('/pay/mp/start', [MercadoPagoController::class, 'start']);
$router->get('/pay/mp/success', [MercadoPagoController::class, 'success']);
$router->get('/pay/mp/pending', [MercadoPagoController::class, 'pending']);
$router->get('/pay/mp/failure', [MercadoPagoController::class, 'failure']);

// Webhook
$router->post('/mp/webhook', [MercadoPagoController::class, 'webhook']);

// Auth
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/activate/resend', [AuthController::class, 'resendActivation']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/activate', [AuthController::class, 'activateForm']);
$router->post('/activate', [AuthController::class, 'activate']);
$router->get('/account/password', [AuthController::class, 'passwordForm']);
$router->post('/account/password', [AuthController::class, 'passwordSave']);

// Wholesale
$router->get('/wholesale/request', [AuthController::class, 'wholesaleRequestForm']);
$router->post('/wholesale/request', [AuthController::class, 'wholesaleRequest']);

// Affiliate (all registered users)
$router->get('/affiliate', [AffiliateController::class, 'dashboard']);
$router->post('/affiliate/withdraw', [AffiliateController::class, 'requestWithdraw']);

// Eventos (demo tecnica)
$router->get('/eventos/demo-tecnica', [DemoTechController::class, 'index']);
$router->get('/eventos/demo-tecnica/profesionales', [DemoTechController::class, 'professionalsForm']);
$router->post('/eventos/demo-tecnica/profesionales', [DemoTechController::class, 'professionalsSubmit']);
$router->get('/eventos/demo-tecnica/clientes', [DemoTechController::class, 'clientsForm']);
$router->post('/eventos/demo-tecnica/clientes', [DemoTechController::class, 'clientsSubmit']);

// API (VFP sync)
$router->post('/api/v1/sync', [ApiSyncController::class, 'sync']);
$router->post('/api/v1/upload', [ApiUploadController::class, 'uploadBase64']);

// Admin
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/correo', [AdminController::class, 'correo']);
$router->post('/admin/correo/auth', [AdminController::class, 'correoAuth']);
$router->post('/admin/correo/agencies', [AdminController::class, 'correoAgencies']);
$router->get('/admin/correo/saved', [AdminController::class, 'correoSavedAgencies']);
$router->get('/admin/productos', [AdminProductController::class, 'index']);
$router->get('/admin/productos/(?P<id>\d+)', [AdminProductController::class, 'index']);
$router->post('/admin/productos/save', [AdminProductController::class, 'save']);
$router->post('/admin/productos/main-image', [AdminProductController::class, 'uploadMainImage']);
$router->post('/admin/productos/main-image/clear', [AdminProductController::class, 'clearMainImage']);
$router->post('/admin/productos/variant-logistics', [AdminProductController::class, 'saveVariantLogistics']);
$router->post('/admin/productos/variant-images', [AdminProductController::class, 'uploadVariantImages']);
$router->post('/admin/productos/variant-images/delete', [AdminProductController::class, 'deleteVariantImage']);
$router->post('/admin/productos/describe', [AdminProductController::class, 'describe']);
$router->get('/admin/orders', [AdminController::class, 'orders']);
$router->get('/admin/prepare', [AdminController::class, 'prepare']);
$router->post('/admin/order/status', [AdminController::class, 'orderStatus']);
$router->post('/admin/orders/archive-abandoned', [AdminController::class, 'archiveAbandoned']);
$router->post('/admin/orders/recover-abandoned', [AdminController::class, 'recoverAbandoned']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/save', [AdminController::class, 'userSave']);
$router->post('/admin/users/role', [AdminController::class, 'userRoleSave']);
$router->post('/admin/users/password', [AdminController::class, 'userPasswordReset']);
$router->post('/admin/users/block', [AdminController::class, 'userToggleBlock']);
$router->post('/admin/users/delete', [AdminController::class, 'userDelete']);
$router->get('/admin/wholesale', [AdminController::class, 'wholesaleList']);
$router->post('/admin/wholesale/approve', [AdminController::class, 'wholesaleApprove']);
$router->post('/admin/wholesale/reject', [AdminController::class, 'wholesaleReject']);
$router->post('/admin/affiliate/release', [AdminController::class, 'affiliateRelease']);
$router->get('/admin/withdrawals', [AdminController::class, 'withdrawals']);
$router->post('/admin/withdrawals/approve', [AdminController::class, 'withdrawalsApprove']);
$router->post('/admin/withdrawals/paid', [AdminController::class, 'withdrawalsPaid']);
$router->post('/admin/withdrawals/reject', [AdminController::class, 'withdrawalsReject']);

// Admin: demo tecnica
$router->get('/admin/demo-tecnica', [AdminController::class, 'demoTech']);
$router->post('/admin/demo-tecnica/status', [AdminController::class, 'demoTechStatus']);
$router->get('/admin/demo-tecnica/horarios', [AdminController::class, 'demoTechEvents']);
$router->post('/admin/demo-tecnica/horarios/save', [AdminController::class, 'demoTechEventSave']);

try {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    // If the user hits /index.php directly, treat it as the home.
    if ($path === '/index.php') {
        $path = '/';
    }
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (Throwable $e) {
    Response::error($e);
}
