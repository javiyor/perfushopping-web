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
use Perfushopping\Web\Admin\AuthController as AdminAuthController;
use Perfushopping\Web\Admin\DashboardController as AdminDashboardController;
use Perfushopping\Web\Admin\UserController as AdminUserController;
use Perfushopping\Web\Admin\ProductController as AdminProductControllerNew;
use Perfushopping\Web\Admin\ImportController as AdminImportController;
use Perfushopping\Web\Admin\DepartamentoController as AdminDepartamentoController;
use Perfushopping\Web\Admin\CustomerController as AdminCustomerController;
use Perfushopping\Web\Admin\ProveedorController as AdminProveedorController;
use Perfushopping\Web\Admin\PresupuestoController as AdminPresupuestoController;
use Perfushopping\Web\Admin\RemitoController as AdminRemitoController;
use Perfushopping\Web\Admin\FacturaController as AdminFacturaController;
use Perfushopping\Web\Admin\ReciboController as AdminReciboController;
use Perfushopping\Web\Admin\CtaCteController as AdminCtaCteController;
use Perfushopping\Web\Admin\SesionController as AdminSesionController;
use Perfushopping\Web\Admin\StockController as AdminStockController;
use Perfushopping\Web\Admin\ReporteController as AdminReporteController;
use Perfushopping\Web\Admin\OrdenCompraController as AdminOrdenCompraController;
use Perfushopping\Web\Admin\CajaController as AdminCajaController;
use Perfushopping\Web\Admin\ArcaController as AdminArcaController;
use Perfushopping\Web\Admin\EmpleadoController as AdminEmpleadoController;

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

// Admin - Nuevo sistema
$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/usuarios', [AdminUserController::class, 'index']);
$router->post('/admin/usuarios/save', [AdminUserController::class, 'save']);
$router->post('/admin/usuarios/delete', [AdminUserController::class, 'delete']);

// Admin - Sistema original (mantener durante migración)
$router->get('/admin/correo', [AdminController::class, 'correo']);
$router->post('/admin/correo/auth', [AdminController::class, 'correoAuth']);
$router->post('/admin/correo/agencies', [AdminController::class, 'correoAgencies']);
$router->get('/admin/correo/saved', [AdminController::class, 'correoSavedAgencies']);
$router->get('/admin/productos', [AdminProductControllerNew::class, 'index']);
$router->get('/admin/productos/nuevo', [AdminProductControllerNew::class, 'create']);
$router->post('/admin/productos/crear', [AdminProductControllerNew::class, 'store']);
$router->get('/admin/productos/(?P<id>\d+)', [AdminProductControllerNew::class, 'show']);
$router->post('/admin/productos/save', [AdminProductControllerNew::class, 'save']);
$router->post('/admin/productos/main-image', [AdminProductControllerNew::class, 'uploadMainImage']);
$router->post('/admin/productos/delete', [AdminProductControllerNew::class, 'delete']);
$router->post('/admin/productos/variant/delete', [AdminProductControllerNew::class, 'deleteVariant']);
$router->post('/admin/productos/main-image/clear', [AdminProductControllerNew::class, 'clearMainImage']);
$router->post('/admin/productos/variant-logistics', [AdminProductControllerNew::class, 'saveVariantLogistics']);
$router->post('/admin/productos/variant-images', [AdminProductControllerNew::class, 'uploadVariantImages']);
$router->post('/admin/productos/variant-images/delete', [AdminProductControllerNew::class, 'deleteVariantImage']);
$router->post('/admin/productos/describe', [AdminProductControllerNew::class, 'describe']);
$router->get('/admin/productos/importar', [AdminImportController::class, 'form']);
$router->post('/admin/productos/importar/preview', [AdminImportController::class, 'preview']);
$router->post('/admin/productos/importar/confirm', [AdminImportController::class, 'confirm']);
$router->get('/admin/departamentos', [AdminDepartamentoController::class, 'index']);
$router->post('/admin/departamentos/save', [AdminDepartamentoController::class, 'save']);
$router->post('/admin/departamentos/delete', [AdminDepartamentoController::class, 'delete']);
$router->get('/admin/clientes', [AdminCustomerController::class, 'index']);
$router->get('/admin/clientes/(?P<id>\d+)', [AdminCustomerController::class, 'detail']);
$router->post('/admin/clientes/nota', [AdminCustomerController::class, 'addNota']);
$router->get('/admin/proveedores', [AdminProveedorController::class, 'index']);
$router->post('/admin/proveedores/save', [AdminProveedorController::class, 'save']);
$router->post('/admin/proveedores/delete', [AdminProveedorController::class, 'delete']);
$router->get('/admin/presupuestos', [AdminPresupuestoController::class, 'index']);
$router->get('/admin/presupuestos/nuevo', [AdminPresupuestoController::class, 'create']);
$router->post('/admin/presupuestos/guardar', [AdminPresupuestoController::class, 'store']);
$router->get('/admin/presupuestos/(?P<id>\d+)', [AdminPresupuestoController::class, 'show']);
$router->post('/admin/presupuestos/estado', [AdminPresupuestoController::class, 'estado']);
$router->post('/admin/presupuestos/delete', [AdminPresupuestoController::class, 'delete']);
$router->get('/admin/presupuestos/buscar-productos', [AdminPresupuestoController::class, 'searchProducts']);
$router->get('/admin/presupuestos/buscar-clientes', [AdminPresupuestoController::class, 'searchClientes']);
$router->get('/admin/remitos', [AdminRemitoController::class, 'index']);
$router->get('/admin/remitos/nuevo', [AdminRemitoController::class, 'create']);
$router->post('/admin/remitos/guardar', [AdminRemitoController::class, 'store']);
$router->get('/admin/remitos/(?P<id>\d+)', [AdminRemitoController::class, 'show']);
$router->post('/admin/remitos/estado', [AdminRemitoController::class, 'estado']);
$router->post('/admin/remitos/delete', [AdminRemitoController::class, 'delete']);
$router->get('/admin/remitos/buscar-productos', [AdminRemitoController::class, 'searchProducts']);
$router->get('/admin/remitos/buscar-clientes', [AdminRemitoController::class, 'searchClientes']);
$router->get('/admin/remitos/buscar-presupuestos', [AdminRemitoController::class, 'searchPresupuestos']);
$router->get('/admin/remitos/buscar-proveedores', [AdminRemitoController::class, 'searchProveedores']);
$router->get('/admin/facturas', [AdminFacturaController::class, 'index']);
$router->get('/admin/facturas/nueva', [AdminFacturaController::class, 'pos']);
$router->post('/admin/facturas/guardar', [AdminFacturaController::class, 'store']);
$router->get('/admin/facturas/(?P<id>\d+)', [AdminFacturaController::class, 'show']);
$router->post('/admin/facturas/estado', [AdminFacturaController::class, 'estado']);
$router->post('/admin/facturas/delete', [AdminFacturaController::class, 'delete']);
$router->get('/admin/facturas/buscar-productos', [AdminFacturaController::class, 'searchProducts']);
$router->get('/admin/facturas/buscar-clientes', [AdminFacturaController::class, 'searchClientes']);
$router->get('/admin/facturas/buscar-remitos', [AdminFacturaController::class, 'searchRemitos']);
$router->get('/admin/facturas/imprimir/(?P<id>\d+)', [AdminFacturaController::class, 'print']);
$router->post('/admin/facturas/(?P<id>\d+)/enviar-email', [AdminFacturaController::class, 'sendEmail']);
$router->get('/admin/recibos', [AdminReciboController::class, 'index']);
$router->get('/admin/recibos/nuevo', [AdminReciboController::class, 'create']);
$router->post('/admin/recibos/guardar', [AdminReciboController::class, 'store']);
$router->get('/admin/recibos/(?P<id>\d+)', [AdminReciboController::class, 'show']);
$router->post('/admin/recibos/estado', [AdminReciboController::class, 'estado']);
$router->post('/admin/recibos/delete', [AdminReciboController::class, 'delete']);
$router->get('/admin/recibos/buscar-clientes', [AdminReciboController::class, 'searchClientes']);
$router->get('/admin/recibos/buscar-facturas', [AdminReciboController::class, 'searchFacturas']);
$router->get('/admin/recibos/imprimir/(?P<id>\d+)', [AdminReciboController::class, 'print']);
$router->get('/admin/ctacte', [AdminCtaCteController::class, 'index']);
$router->get('/admin/ctacte/(?P<id>\d+)', [AdminCtaCteController::class, 'show']);
$router->get('/admin/ctacte/ajuste/(?P<id>\d+)', [AdminCtaCteController::class, 'ajuste']);
$router->post('/admin/ctacte/ajuste/guardar', [AdminCtaCteController::class, 'storeAjuste']);
$router->get('/admin/ctacte/buscar-clientes', [AdminCtaCteController::class, 'searchClientes']);
$router->get('/admin/sesion/iniciar', [AdminSesionController::class, 'iniciar']);
$router->post('/admin/sesion/guardar', [AdminSesionController::class, 'guardar']);
$router->post('/admin/sesion/cerrar', [AdminSesionController::class, 'cerrar']);
$router->get('/admin/stock', [AdminStockController::class, 'index']);
$router->get('/admin/stock/(?P<id>\d+)', [AdminStockController::class, 'show']);
$router->get('/admin/stock/ajuste', [AdminStockController::class, 'ajuste']);
$router->get('/admin/stock/ajuste/(?P<id>\d+)', [AdminStockController::class, 'ajuste']);
$router->post('/admin/stock/ajuste/guardar', [AdminStockController::class, 'storeAjuste']);
$router->get('/admin/stock/ajuste/buscar-productos', [AdminStockController::class, 'searchAjusteProductos']);
$router->get('/admin/stock/ajuste/variantes', [AdminStockController::class, 'ajusteVariantes']);
$router->get('/admin/ordenes-compra', [AdminOrdenCompraController::class, 'index']);
$router->get('/admin/ordenes-compra/nueva', [AdminOrdenCompraController::class, 'create']);
$router->post('/admin/ordenes-compra/guardar', [AdminOrdenCompraController::class, 'store']);
$router->get('/admin/ordenes-compra/(?P<id>\d+)', [AdminOrdenCompraController::class, 'show']);
$router->post('/admin/ordenes-compra/estado', [AdminOrdenCompraController::class, 'estado']);
$router->post('/admin/ordenes-compra/delete', [AdminOrdenCompraController::class, 'delete']);
$router->get('/admin/ordenes-compra/buscar-productos', [AdminOrdenCompraController::class, 'searchProducts']);
$router->get('/admin/ordenes-compra/buscar-proveedores', [AdminOrdenCompraController::class, 'searchProveedores']);
$router->get('/admin/caja', [AdminCajaController::class, 'index']);
$router->get('/admin/caja/abrir', [AdminCajaController::class, 'abrirForm']);
$router->post('/admin/caja/abrir/guardar', [AdminCajaController::class, 'abrirStore']);
$router->get('/admin/caja/movimientos', [AdminCajaController::class, 'movimientos']);
$router->post('/admin/caja/movimientos/guardar', [AdminCajaController::class, 'storeMovimiento']);
$router->get('/admin/caja/arqueo', [AdminCajaController::class, 'arqueoForm']);
$router->post('/admin/caja/arqueo/guardar', [AdminCajaController::class, 'storeArqueo']);
$router->get('/admin/caja/cierre', [AdminCajaController::class, 'cierreForm']);
$router->post('/admin/caja/cierre/guardar', [AdminCajaController::class, 'cierreStore']);
$router->get('/admin/arca', [AdminArcaController::class, 'index']);
$router->get('/admin/arca/config', [AdminArcaController::class, 'config']);
$router->post('/admin/arca/config/guardar', [AdminArcaController::class, 'configSave']);
$router->post('/admin/arca/test', [AdminArcaController::class, 'testConnection']);
$router->post('/admin/arca/reenviar', [AdminArcaController::class, 'reenviar']);
$router->get('/admin/reportes', [AdminReporteController::class, 'index']);
$router->get('/admin/reportes/data', [AdminReporteController::class, 'data']);

// Admin - Empleados / Sueldos
$router->get('/admin/empleados', [AdminEmpleadoController::class, 'index']);
$router->get('/admin/empleados/nuevo', [AdminEmpleadoController::class, 'edit']);
$router->get('/admin/empleados/(?P<id>\d+)', [AdminEmpleadoController::class, 'edit']);
$router->post('/admin/empleados/guardar', [AdminEmpleadoController::class, 'save']);
$router->post('/admin/empleados/comisiones/guardar', [AdminEmpleadoController::class, 'saveComision']);
$router->post('/admin/empleados/comisiones/eliminar', [AdminEmpleadoController::class, 'deleteComision']);
$router->get('/admin/empleados/horas', [AdminEmpleadoController::class, 'horas']);
$router->post('/admin/empleados/horas/guardar', [AdminEmpleadoController::class, 'horasStore']);
$router->get('/admin/empleados/liquidar', [AdminEmpleadoController::class, 'liquidar']);
$router->post('/admin/empleados/liquidar/guardar', [AdminEmpleadoController::class, 'liquidarStore']);
$router->get('/admin/empleados/liquidaciones', [AdminEmpleadoController::class, 'liquidaciones']);
$router->post('/admin/empleados/liquidaciones/pagar', [AdminEmpleadoController::class, 'liquidacionPagada']);
$router->post('/admin/empleados/liquidaciones/anular', [AdminEmpleadoController::class, 'liquidacionAnular']);
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
