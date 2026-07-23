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
use Perfushopping\Web\Controller\MercadoPagoController;
use Perfushopping\Web\Controller\LegalController;
use Perfushopping\Web\Controller\AffiliateController;
use Perfushopping\Web\Controller\ApiSyncController;
use Perfushopping\Web\Controller\ApiUploadController;
use Perfushopping\Web\Controller\ApiSyncTablesController;
use Perfushopping\Web\Controller\DemoTechController;
use Perfushopping\Web\Controller\PromoTarjetasController as PromoTarjetasController;
use Perfushopping\Web\Controller\AdminProductController;
use Perfushopping\Web\Admin\AuthController as AdminAuthController;
use Perfushopping\Web\Admin\DashboardController as AdminDashboardController;
use Perfushopping\Web\Admin\UserController as AdminUserController;
use Perfushopping\Web\Admin\ProductController as AdminProductControllerNew;
use Perfushopping\Web\Admin\ImportController as AdminImportController;
use Perfushopping\Web\Admin\PriceUpdateController as AdminPriceUpdateController;
use Perfushopping\Web\Admin\PromoTarjetaController as AdminPromoTarjetaController;
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
use Perfushopping\Web\Admin\StockGrillaController as AdminStockGrillaController;
use Perfushopping\Web\Admin\PrintConfigController as AdminPrintConfigController;
use Perfushopping\Web\Admin\ReporteController as AdminReporteController;
use Perfushopping\Web\Admin\OrdenCompraController as AdminOrdenCompraController;
use Perfushopping\Web\Admin\CajaController as AdminCajaController;
use Perfushopping\Web\Admin\ArcaController as AdminArcaController;
use Perfushopping\Web\Admin\EmpleadoController as AdminEmpleadoController;
use Perfushopping\Web\Admin\ProveedorCtaCteController as AdminProveedorCtaCteController;
use Perfushopping\Web\Admin\WebOrderController as AdminWebOrderController;
use Perfushopping\Web\Admin\WebUserController as AdminWebUserController;
use Perfushopping\Web\Admin\WholesaleController as AdminWholesaleController;
use Perfushopping\Web\Admin\AffiliateController as AdminAffiliateController;
use Perfushopping\Web\Admin\WithdrawalController as AdminWithdrawalController;
use Perfushopping\Web\Admin\CapacitacionController as AdminCapacitacionController;
use Perfushopping\Web\Admin\CorreoController as AdminCorreoController;
use Perfushopping\Web\Admin\ChequeController as AdminChequeController;
use Perfushopping\Web\Admin\OrdenPagoController as AdminOrdenPagoController;
use Perfushopping\Web\Admin\EmailController as AdminEmailController;
use Perfushopping\Web\Admin\BadgeController as AdminBadgeController;
use Perfushopping\Web\Admin\SucursalController as AdminSucursalController;

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

// Eventos (capacitaciones)
$router->get('/eventos/capacitaciones', [DemoTechController::class, 'index']);
$router->get('/eventos/capacitaciones/profesionales', [DemoTechController::class, 'professionalsForm']);
$router->post('/eventos/capacitaciones/profesionales', [DemoTechController::class, 'professionalsSubmit']);
$router->get('/eventos/capacitaciones/clientes', [DemoTechController::class, 'clientsForm']);
$router->post('/eventos/capacitaciones/clientes', [DemoTechController::class, 'clientsSubmit']);
// Mantener rutas viejas por compatibilidad
$router->get('/eventos/demo-tecnica', [DemoTechController::class, 'index']);
$router->get('/promociones', [PromoTarjetasController::class, 'index']);
$router->get('/eventos/demo-tecnica/profesionales', [DemoTechController::class, 'professionalsForm']);
$router->post('/eventos/demo-tecnica/profesionales', [DemoTechController::class, 'professionalsSubmit']);
$router->get('/eventos/demo-tecnica/clientes', [DemoTechController::class, 'clientsForm']);
$router->post('/eventos/demo-tecnica/clientes', [DemoTechController::class, 'clientsSubmit']);

// API (VFP sync)
$router->post('/api/v1/sync', [ApiSyncController::class, 'sync']);
$router->post('/api/v1/upload', [ApiUploadController::class, 'uploadBase64']);
$router->post('/api/v1/sync-tables', [ApiSyncTablesController::class, 'push']);
$router->post('/api/v1/recalcular-stock', [ApiSyncTablesController::class, 'recalcular']);

// Admin - Nuevo sistema
$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/usuarios', [AdminUserController::class, 'index']);
$router->post('/admin/usuarios/save', [AdminUserController::class, 'save']);
$router->post('/admin/usuarios/delete', [AdminUserController::class, 'delete']);

// Admin - Correo Argentino
$router->get('/admin/correo', [AdminCorreoController::class, 'index']);
$router->post('/admin/correo/auth', [AdminCorreoController::class, 'auth']);
$router->post('/admin/correo/agencies', [AdminCorreoController::class, 'agencies']);
$router->get('/admin/correo/saved', [AdminCorreoController::class, 'savedAgencies']);
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
$router->get('/admin/productos/actualizar-precios', [AdminPriceUpdateController::class, 'index']);
$router->post('/admin/productos/actualizar-precios', [AdminPriceUpdateController::class, 'apply']);
$router->get('/admin/promo-tarjetas', [AdminPromoTarjetaController::class, 'index']);
$router->post('/admin/promo-tarjetas/save', [AdminPromoTarjetaController::class, 'save']);
$router->post('/admin/promo-tarjetas/delete', [AdminPromoTarjetaController::class, 'delete']);
$router->get('/admin/departamentos', [AdminDepartamentoController::class, 'index']);
$router->post('/admin/departamentos/save', [AdminDepartamentoController::class, 'save']);
$router->post('/admin/departamentos/delete', [AdminDepartamentoController::class, 'delete']);
$router->get('/admin/clientes', [AdminCustomerController::class, 'index']);
$router->get('/admin/clientes/(?P<id>\d+)', [AdminCustomerController::class, 'detail']);
$router->post('/admin/clientes/nota', [AdminCustomerController::class, 'addNota']);
$router->get('/admin/clientes/buscar-arca', [AdminCustomerController::class, 'buscarArca']);
$router->post('/admin/clientes/crear-desde-arca', [AdminCustomerController::class, 'crearDesdeArca']);
$router->get('/admin/proveedores', [AdminProveedorController::class, 'index']);
$router->post('/admin/proveedores/save', [AdminProveedorController::class, 'save']);
$router->post('/admin/proveedores/delete', [AdminProveedorController::class, 'delete']);
$router->get('/admin/proveedores/ctacte', [AdminProveedorCtaCteController::class, 'index']);
$router->get('/admin/proveedores/ctacte/(?P<id>\d+)', [AdminProveedorCtaCteController::class, 'movimientos']);
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
$router->get('/admin/facturas', [AdminFacturaController::class, 'pos']);
$router->get('/admin/facturas/comprobantes', [AdminFacturaController::class, 'index']);
$router->get('/admin/facturas/nueva', [AdminFacturaController::class, 'pos']);
$router->post('/admin/facturas/guardar', [AdminFacturaController::class, 'store']);
$router->get('/admin/facturas/(?P<id>\d+)', [AdminFacturaController::class, 'show']);
$router->post('/admin/facturas/estado', [AdminFacturaController::class, 'estado']);
$router->post('/admin/facturas/delete', [AdminFacturaController::class, 'delete']);
$router->get('/admin/facturas/buscar-productos', [AdminFacturaController::class, 'searchProducts']);
$router->get('/admin/facturas/buscar-clientes', [AdminFacturaController::class, 'searchClientes']);
$router->get('/admin/facturas/buscar-remitos', [AdminFacturaController::class, 'searchRemitos']);
$router->get('/admin/facturas/buscar-presupuestos', [AdminFacturaController::class, 'searchPresupuestos']);
$router->get('/admin/facturas/imprimir/(?P<id>\d+)', [AdminFacturaController::class, 'print']);
$router->post('/admin/facturas/(?P<id>\d+)/enviar-email', [AdminFacturaController::class, 'sendEmail']);
$router->get('/admin/recibos', [AdminReciboController::class, 'create']);
$router->get('/admin/recibos/comprobantes', [AdminReciboController::class, 'index']);
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
$router->get('/admin/badges', [AdminBadgeController::class, 'badges']);
$router->get('/admin/stock', [AdminStockController::class, 'index']);
$router->post('/admin/stock/recalcular', [AdminStockController::class, 'recalcular']);
$router->get('/admin/stock/(?P<id>\d+)', [AdminStockController::class, 'show']);
$router->get('/admin/stock/ajuste', [AdminStockController::class, 'ajuste']);
$router->get('/admin/stock/ajuste/(?P<id>\d+)', [AdminStockController::class, 'ajuste']);
$router->post('/admin/stock/ajuste/guardar', [AdminStockController::class, 'storeAjuste']);
$router->get('/admin/stock/ajuste/buscar-productos', [AdminStockController::class, 'searchAjusteProductos']);
$router->get('/admin/stock/ajuste/variantes', [AdminStockController::class, 'ajusteVariantes']);
$router->get('/admin/stock/grilla', [AdminStockGrillaController::class, 'index']);
$router->post('/admin/stock/grilla/generar-oc', [AdminStockGrillaController::class, 'generarOC']);
$router->get('/admin/stock/grilla/oc-pdf/(?P<id>\d+)', [AdminStockGrillaController::class, 'exportarPDF']);
$router->get('/admin/stock/grilla/oc-excel/(?P<id>\d+)', [AdminStockGrillaController::class, 'exportarExcel']);

// Admin - Configuración de impresión
$router->get('/admin/impresion/config', [AdminPrintConfigController::class, 'index']);
$router->get('/admin/ordenes-compra', [AdminOrdenCompraController::class, 'index']);
$router->get('/admin/ordenes-compra/nueva', [AdminOrdenCompraController::class, 'create']);
$router->post('/admin/ordenes-compra/guardar', [AdminOrdenCompraController::class, 'store']);
$router->get('/admin/ordenes-compra/(?P<id>\d+)', [AdminOrdenCompraController::class, 'show']);
$router->post('/admin/ordenes-compra/estado', [AdminOrdenCompraController::class, 'estado']);
$router->post('/admin/ordenes-compra/delete', [AdminOrdenCompraController::class, 'delete']);
$router->get('/admin/ordenes-compra/buscar-productos', [AdminOrdenCompraController::class, 'searchProducts']);
$router->get('/admin/ordenes-compra/buscar-proveedores', [AdminOrdenCompraController::class, 'searchProveedores']);
$router->post('/admin/ordenes-compra/guardar-recepcion', [AdminOrdenCompraController::class, 'guardarRecepcion']);
$router->get('/admin/ordenes-compra/fletes', [AdminOrdenCompraController::class, 'fletes']);
$router->get('/admin/ordenes-compra/descargar-comprobante/(?P<id>\d+)', [AdminOrdenCompraController::class, 'descargarComprobante']);
$router->get('/admin/caja', [AdminCajaController::class, 'index']);
$router->get('/admin/caja/abrir', [AdminCajaController::class, 'abrirForm']);
$router->post('/admin/caja/abrir/guardar', [AdminCajaController::class, 'abrirStore']);
$router->get('/admin/caja/movimientos', [AdminCajaController::class, 'movimientos']);
$router->post('/admin/caja/movimientos/guardar', [AdminCajaController::class, 'storeMovimiento']);
$router->get('/admin/caja/arqueo', [AdminCajaController::class, 'arqueoForm']);
$router->post('/admin/caja/arqueo/guardar', [AdminCajaController::class, 'storeArqueo']);
$router->get('/admin/caja/cierre', [AdminCajaController::class, 'cierreForm']);
$router->post('/admin/caja/cierre/guardar', [AdminCajaController::class, 'cierreStore']);
$router->get('/admin/caja/general', [AdminCajaController::class, 'general']);
$router->post('/admin/caja/general/guardar', [AdminCajaController::class, 'storeGeneralMovimiento']);
$router->post('/admin/caja/general/controlar', [AdminCajaController::class, 'controlarMovimiento']);
$router->get('/admin/arca', [AdminArcaController::class, 'index']);
$router->get('/admin/arca/config', [AdminArcaController::class, 'config']);
$router->post('/admin/arca/config/guardar', [AdminArcaController::class, 'configSave']);
$router->post('/admin/arca/test', [AdminArcaController::class, 'testConnection']);
$router->post('/admin/arca/reenviar', [AdminArcaController::class, 'reenviar']);
$router->post('/admin/arca/generar-csr', [AdminArcaController::class, 'generarCsr']);
$router->post('/admin/arca/cargar-certificado', [AdminArcaController::class, 'cargarCertificado']);
$router->get('/admin/reportes', [AdminReporteController::class, 'index']);
$router->get('/admin/reportes/data', [AdminReporteController::class, 'data']);

// Admin - Cheques
$router->get('/admin/cheques', [AdminChequeController::class, 'index']);
$router->get('/admin/cheques/emitir', [AdminChequeController::class, 'emitirForm']);
$router->post('/admin/cheques/emitir/guardar', [AdminChequeController::class, 'emitirStore']);
$router->get('/admin/cheques/(?P<id>\d+)', [AdminChequeController::class, 'show']);
$router->post('/admin/cheques/estado', [AdminChequeController::class, 'estado']);

// Admin - Sucursales
$router->get('/admin/sucursales', [AdminSucursalController::class, 'index']);
$router->post('/admin/sucursales/save', [AdminSucursalController::class, 'save']);

// Admin - Email (IMAP)
$router->get('/admin/email', [AdminEmailController::class, 'inbox']);
$router->get('/admin/email/(?P<uid>\d+)', [AdminEmailController::class, 'view']);

// Admin - Órdenes de pago a proveedores
$router->get('/admin/ordenes-pago', [AdminOrdenPagoController::class, 'index']);
$router->get('/admin/ordenes-pago/nueva', [AdminOrdenPagoController::class, 'create']);
$router->post('/admin/ordenes-pago/guardar', [AdminOrdenPagoController::class, 'store']);
$router->get('/admin/ordenes-pago/(?P<id>\d+)', [AdminOrdenPagoController::class, 'show']);
$router->post('/admin/ordenes-pago/estado', [AdminOrdenPagoController::class, 'estado']);
$router->post('/admin/ordenes-pago/delete', [AdminOrdenPagoController::class, 'delete']);
$router->get('/admin/ordenes-pago/buscar-proveedores', [AdminOrdenPagoController::class, 'searchProveedores']);
$router->get('/admin/ordenes-pago/deuda-proveedor', [AdminOrdenPagoController::class, 'deudaProveedor']);

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
$router->get('/admin/orders', [AdminWebOrderController::class, 'index']);
$router->get('/admin/prepare', [AdminWebOrderController::class, 'prepare']);
$router->post('/admin/order/status', [AdminWebOrderController::class, 'status']);
$router->post('/admin/orders/archive-abandoned', [AdminWebOrderController::class, 'archiveAbandoned']);
$router->post('/admin/orders/recover-abandoned', [AdminWebOrderController::class, 'recoverAbandoned']);
$router->get('/admin/users', [AdminWebUserController::class, 'index']);
$router->post('/admin/users/save', [AdminWebUserController::class, 'save']);
$router->post('/admin/users/role', [AdminWebUserController::class, 'roleSave']);
$router->post('/admin/users/password', [AdminWebUserController::class, 'passwordReset']);
$router->post('/admin/users/block', [AdminWebUserController::class, 'toggleBlock']);
$router->post('/admin/users/delete', [AdminWebUserController::class, 'delete']);
$router->get('/admin/wholesale', [AdminWholesaleController::class, 'index']);
$router->post('/admin/wholesale/approve', [AdminWholesaleController::class, 'approve']);
$router->post('/admin/wholesale/reject', [AdminWholesaleController::class, 'reject']);
$router->post('/admin/affiliate/release', [AdminAffiliateController::class, 'release']);
$router->get('/admin/withdrawals', [AdminWithdrawalController::class, 'index']);
$router->post('/admin/withdrawals/approve', [AdminWithdrawalController::class, 'approve']);
$router->post('/admin/withdrawals/paid', [AdminWithdrawalController::class, 'paid']);
$router->post('/admin/withdrawals/reject', [AdminWithdrawalController::class, 'reject']);

// Admin: capacitaciones
$router->get('/admin/capacitaciones', [AdminCapacitacionController::class, 'index']);
$router->post('/admin/capacitaciones/status', [AdminCapacitacionController::class, 'status']);
$router->get('/admin/capacitaciones/horarios', [AdminCapacitacionController::class, 'horarios']);
$router->post('/admin/capacitaciones/horarios/save', [AdminCapacitacionController::class, 'horariosSave']);

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
