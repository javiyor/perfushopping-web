<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - <?= $empresaNombre ?></title>
    <link rel="icon" href="/assets/brand/favicon.ico" sizes="any" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <meta name="theme-color" content="#121418" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-title" content="PF Admin" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/brand/apple-touch-icon-180.png" />
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/brand/pwa-icon-192.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #1a1d23;
            --sidebar-hover: #262a33;
            --sidebar-active: #2d323e;
            --accent: #d8b25a;
            --topbar-bg: #121418;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f4f5f7;
            color: #1a1d23;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: #c8ccd4;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1030;
            transition: transform .2s;
        }
        .sidebar-brand {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            font-weight: 800;
            font-size: 18px;
            color: #f6f4ef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand img { height: 32px; }
.sidebar-nav { flex: 1; overflow-y: auto; padding: 4px 0; }
.sidebar-nav .nav-section { padding: 3px 20px 1px; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.25); }
.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 3px 20px;
    color: #c8ccd4;
    text-decoration: none;
    font-size: 13px;
    transition: background .12s;
}
.sidebar-nav a:hover { background: var(--sidebar-hover); color: #f6f4ef; }
.sidebar-nav a.active { background: var(--sidebar-active); color: var(--accent); border-right: 3px solid var(--accent); }
.sidebar-nav a i { font-size: 16px; width: 20px; text-align: center; }
.sidebar-nav .badge-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-left: auto; flex-shrink: 0; }
.badge-dot.green { background: #198754; }
.badge-dot.red { background: #dc3545; }
.badge-count { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; border-radius: 9px; font-size: 10px; font-weight: 700; padding: 0 5px; margin-left: auto; flex-shrink: 0; }
.badge-count.green { background: #198754; color: #fff; }
.badge-count.red { background: #dc3545; color: #fff; }
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            background: var(--topbar-bg);
            color: #f6f4ef;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar-left .toggle-sidebar { background: none; border: none; color: #f6f4ef; font-size: 22px; cursor: pointer; padding: 4px; display: none; }
        .topbar-right { display: flex; align-items: center; gap: 16px; font-size: 14px; }
        .topbar-right .admin-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-superadmin { background: var(--accent); color: #1a1d23; }
        .badge-ventas { background: #0d6efd; color: #fff; }
        .badge-administracion { background: #6f42c1; color: #fff; }
        .badge-compras { background: #198754; color: #fff; }
        .badge-caja { background: #fd7e14; color: #fff; }
        .content-wrap { padding: 24px; flex: 1; }
        .page-title { margin-bottom: 20px; }
        .page-title h2 { margin: 0; font-weight: 700; font-size: 22px; }
        .page-title p { margin: 4px 0 0; color: #6c757d; font-size: 14px; }
        .card-dashboard {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            transition: box-shadow .15s;
        }
        .card-dashboard:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .flash-msg {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .flash-msg.ok { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .flash-msg.danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .flash-msg.info { background: #cff4fc; color: #055160; border: 1px solid #b6effb; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .topbar-left .toggle-sidebar { display: block; }
        }
        .table-admin { font-size: 14px; }
        .table-admin th { background: #f8f9fa; font-weight: 600; white-space: nowrap; }
        .btn-accent { background: var(--accent); border-color: var(--accent); color: #1a1d23; font-weight: 600; }
        .btn-accent:hover { background: #c9a44a; border-color: #c9a44a; color: #1a1d23; }
        .sidebar-backdrop { display:none; }
        @media (max-width: 768px) {
            .content-wrap { padding: 16px; }
            .topbar { padding: 10px 14px; }
            .topbar-right { gap: 8px; font-size: 13px; }
            .topbar-right .admin-badge { font-size: 10px; padding: 1px 6px; }
            .topbar-right .btn { font-size: 12px; padding: 2px 8px; }
            .card-dashboard { padding: 14px; }
            .sidebar-backdrop {
                display:none; position:fixed; inset:0; background:rgba(0,0,0,.4);
                z-index:1025;
            }
            .sidebar-backdrop.show { display:block; }
            .sidebar.open { box-shadow: 4px 0 20px rgba(0,0,0,.3); }
        }
        @media (max-width: 480px) {
            .content-wrap { padding: 12px; }
            .table-admin { font-size: 12px; }
            .table-admin th, .table-admin td { padding: 4px 6px; }
            .table-admin .btn-sm { font-size: 11px; padding: 1px 6px; }
        }
    </style>
</head>
<body>
    <?php
    $adminUser = $adminUser ?? null;
    $adminRol = $adminUser['rol'] ?? '';
    $adminRolLabel = ['superadmin'=>'Super Admin','ventas'=>'Ventas','administracion'=>'Admin.','compras'=>'Compras','caja'=>'Caja'][$adminRol] ?? '';
    $adminRolBadge = 'badge-' . $adminRol;

    // Sucursal / turno info
    $authSvc = new \Perfushopping\Web\Service\AdminAuthService();
    $hasSesion = $authSvc->hasSesion();
    $sucursalId = $authSvc->getSucursalId();
    $turno = $authSvc->getTurno();
    $turnoLabel = ['manana'=>'☀️ Mañana','tarde'=>'🌤️ Tarde'];
    $sucursalNombre = '';
    if ($sucursalId > 0) {
        $suc = (new \Perfushopping\Web\Repo\SucursalRepo())->findById($sucursalId);
        $sucursalNombre = $suc['nomsuc'] ?? '';
    }

    $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
    $empresaLogo = !empty($empresa['logo']) ? '/uploads/' . ltrim($empresa['logo'], '/') : '/assets/brand/logo-header.png';
    $empresaNombre = htmlspecialchars($empresa['nomemp'] ?? 'Perfushopping');

    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="document.getElementById('adminSidebar').classList.remove('open')"></div>
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <img src="<?= $empresaLogo ?>" alt="PF" onerror="this.style.display='none'" />
            <span><?= $empresaNombre ?></span>
        </div>
        <nav class="sidebar-nav">
            <?php $isDemo = \Perfushopping\Web\Support\Env::isDemo(); $can = function(string $p) use ($authSvc): bool { return ($authSvc->user()['rol'] ?? '') === 'superadmin' || $authSvc->checkPermiso($p); }; ?>
            <div class="nav-section">General</div>
            <a href="/admin" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="<?= $isDemo ? 'Resumen general: pedidos, caja y accesos rápidos' : '' ?>"><i class="bi bi-speedometer2"></i>Panel Principal</a>

            <div class="nav-section">Ventas</div>
            <?php if ($can('facturacion') || $can('cta_cte') || $can('productos')): ?><a href="/admin/orders" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Pedidos ingresados en la web (minorista/mayorista)"><i class="bi bi-cart"></i>Pedidos<span class="badge-count green" id="badgePedidosNuevos" style="display:none">0</span><span class="badge-count red" id="badgePedidosAbandonados" style="display:none">0</span></a><?php endif; ?>
            <?php if ($can('clientes')): ?><a href="/admin/mensajes" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Bandeja unificada WhatsApp, Instagram y Facebook"><i class="bi bi-chat-dots"></i>Mensajes</a><?php endif; ?>
            <?php if ($can('facturacion')): ?><a href="/admin/prepare" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Pedidos listos para preparar y despachar"><i class="bi bi-box"></i>Preparar</a><?php endif; ?>
            <?php if ($can('presupuestos')): ?><a href="/admin/presupuestos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Presupuestos a clientes (antes de facturar)"><i class="bi bi-file-text"></i>Presupuestos</a><?php endif; ?>
            <?php if ($can('remitos')): ?><a href="/admin/remitos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Remitos de entrega y movimiento de stock"><i class="bi bi-receipt"></i>Remitos</a><?php endif; ?>
            <?php if ($can('facturacion')): ?><a href="/admin/facturas" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Facturación POS: emitir facturas y tickets"><i class="bi bi-receipt-cutoff"></i>Facturación</a><?php endif; ?>
            <?php if ($can('facturacion')): ?><a href="/admin/envios" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Envíos pendientes: entregas a domicilio y cobros"><i class="bi bi-truck"></i>Envíos<span class="badge-count red" id="badgeEnviosPendientes" style="display:none">0</span></a><?php endif; ?>
            <?php if ($can('recibos')): ?><a href="/admin/recibos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Recibos de cobranza y cancelación de facturas"><i class="bi bi-wallet2"></i>Recibos</a><?php endif; ?>
            <?php if ($can('cta_cte')): ?><a href="/admin/ctacte" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Cuentas corrientes de clientes"><i class="bi bi-currency-dollar"></i>Ctas. ctes.</a><?php endif; ?>
            <?php if ($can('caja_movimientos')): ?><a href="/admin/caja" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Caja del turno: apertura, movimientos y cierre"><i class="bi bi-cash-stack"></i>Caja</a><?php endif; ?>
            <?php if ($can('caja_movimientos')): ?><a href="/admin/caja/general" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Caja general: ingresos/egresos centralizados"><i class="bi bi-piggy-bank"></i>Caja General</a><?php endif; ?>
            <?php if ($can('caja_movimientos')): ?><a href="/admin/impresion/config" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Configuración de impresión de tickets"><i class="bi bi-printer"></i>Impresión</a><?php endif; ?>
            <?php if ($can('arca')): ?><a href="/admin/arca" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Factura electrónica ARCA / AFIP"><i class="bi bi-cloud-check"></i>ARCA</a><?php endif; ?>
            <?php if ($can('estadisticas')): ?><a href="/admin/reportes" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Reportes de ventas y estadísticas"><i class="bi bi-graph-up"></i>Reportes</a><?php endif; ?>

            <?php if ($can('productos')): ?><div class="nav-section">Productos</div><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/portada" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Productos destacados en la portada web"><i class="bi bi-easel"></i>Portada</a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/productos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Catálogo de productos y variantes"><i class="bi bi-box-seam"></i>Productos</a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/productos/importar" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Importar productos y precios desde Excel/CSV"><i class="bi bi-upload"></i>Importar</a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/departamentos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Departamentos / categorías internas"><i class="bi bi-tags"></i>Departamentos</a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/stock" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Consulta de stock por depósito y variante"><i class="bi bi-boxes"></i>Stock</a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/stock/ajuste" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Ajustes manuales de stock (con autorización)"><i class="bi bi-sliders"></i>Ajustes stock<span class="badge-count red" id="badgeStockAjustes" style="display:none">0</span></a><?php endif; ?>
            <?php if ($can('productos')): ?><a href="/admin/stock/grilla" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Grilla de reposición sugerida por ventas"><i class="bi bi-grid-3x3-gap"></i>Grilla reposición</a><?php endif; ?>

            <div class="nav-section">Clientes</div>
            <?php if ($can('clientes')): ?><a href="/admin/clientes" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Base de clientes y fichas"><i class="bi bi-people"></i>Clientes</a><?php endif; ?>
            <?php if ($can('clientes')): ?><a href="/admin/users" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Usuarios web registrados"><i class="bi bi-person-gear"></i>Usuarios web<span class="badge-count green" id="badgeUsuariosNuevos" style="display:none">0</span></a><?php endif; ?>
            <?php if ($can('clientes')): ?><a href="/admin/puntos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Programa de puntos y fidelización"><i class="bi bi-stars"></i>Puntos</a><?php endif; ?>

            <?php $hasCompras = $can('compras') || $can('pagos_proveedores'); if ($hasCompras): ?><div class="nav-section">Compras</div><?php endif; ?>
            <?php if ($can('compras')): ?><a href="/admin/compras" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Facturas de compra a proveedores"><i class="bi bi-receipt"></i>Facturas compra</a><?php endif; ?>
            <?php if ($can('compras')): ?><a href="/admin/proveedores" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Proveedores y datos comerciales"><i class="bi bi-truck"></i>Proveedores</a><?php endif; ?>
            <?php if ($can('pagos_proveedores')): ?><a href="/admin/proveedores/ctacte" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Cuenta corriente con proveedores"><i class="bi bi-currency-dollar"></i>Cta Cte Proveedores</a><?php endif; ?>
            <?php if ($can('compras')): ?><a href="/admin/ordenes-compra" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Órdenes de compra a proveedores"><i class="bi bi-cart-plus"></i>Órdenes compra</a><?php endif; ?>
            <?php if ($can('compras')): ?><a href="/admin/nota-pedido/nueva" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Crear notas de pedido internas"><i class="bi bi-file-text"></i>Nota pedido</a><?php endif; ?>
            <?php if ($can('pagos_proveedores')): ?><a href="/admin/ordenes-pago" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Órdenes de pago a proveedores"><i class="bi bi-credit-card"></i>Órdenes pago</a><?php endif; ?>
            <?php if ($can('compras')): ?><a href="/admin/ordenes-compra/fletes" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Gestión de fletes de compras"><i class="bi bi-truck"></i>Fletes</a><?php endif; ?>
            <?php if ($can('compras') || $can('caja_movimientos')): ?><a href="/admin/gastos" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Gastos varios con cuenta contable"><i class="bi bi-cash-coin"></i>Gastos</a><?php endif; ?>
            <?php if ($can('caja_movimientos')): ?><a href="/admin/caja/depositar" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Depositar efectivo en banco"><i class="bi bi-bank"></i>Depósito banco</a><?php endif; ?>

            <?php $hasAdmin = $can('usuarios_admin') || $can('pagos') || $can('cheques') || $can('estadisticas') || $can('arca'); if ($hasAdmin): ?><div class="nav-section">Administración</div><?php endif; ?>
            <?php if ($can('usuarios_admin')): ?><a href="/admin/usuarios" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Usuarios del panel admin y permisos"><i class="bi bi-shield-lock"></i>Admins</a><?php endif; ?>
            <?php if ($can('usuarios_admin')): ?><a href="/admin/empleados" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Legajos, sueldos y liquidaciones"><i class="bi bi-person-badge"></i>Empleados</a><?php endif; ?>
            <?php if ($can('pagos')): ?><a href="/admin/wholesale" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Solicitudes mayoristas pendientes"><i class="bi bi-shop"></i>Mayoristas</a><?php endif; ?>
            <?php if ($can('pagos')): ?><a href="/admin/withdrawals" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Retiros de comisiones de afiliados"><i class="bi bi-cash"></i>Retiros</a><?php endif; ?>
            <?php if ($can('estadisticas')): ?><a href="/admin/correo" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Integración con Correo Argentino"><i class="bi bi-truck"></i>Correo Argentino</a><?php endif; ?>
            <?php if ($can('estadisticas')): ?><a href="/admin/capacitaciones" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Capacitaciones y demos técnicas"><i class="bi bi-calendar-event"></i>Capacitaciones</a><?php endif; ?>
            <?php if ($can('cheques')): ?><a href="/admin/cheques" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Cheques propios y de terceros"><i class="bi bi-file-text"></i>Cheques</a><?php endif; ?>
            <?php if ($can('usuarios_admin')): ?><a href="/admin/empresa" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Datos de la empresa y logo"><i class="bi bi-building"></i>Empresa</a><?php endif; ?>
            <?php if ($can('usuarios_admin')): ?><a href="/admin/sucursales" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Sucursales y puntos de venta ARCA"><i class="bi bi-building"></i>Sucursales</a><?php endif; ?>
            <?php if ($can('estadisticas')): ?><a href="/admin/promo-tarjetas" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Promociones con tarjetas bancarias"><i class="bi bi-credit-card-2-front"></i>Promo Tarjetas</a><?php endif; ?>
            <?php if ($can('estadisticas')): ?><a href="/admin/email" data-bs-toggle="<?= $isDemo ? 'tooltip' : '' ?>" data-bs-placement="right" title="Bandeja de email integrada"><i class="bi bi-envelope"></i>Email</a><?php endif; ?>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="toggle-sidebar" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <span style="font-weight:600"><?= $pageTitle ?? 'Panel de gestión' ?></span>
            </div>
            <div class="topbar-right">
                <?php if ($adminUser): ?>
                    <?php if ($hasSesion && $sucursalNombre): ?>
                        <span class="text-muted small" style="opacity:.7"><i class="bi bi-shop"></i> <?= htmlspecialchars($sucursalNombre) ?></span>
                        <span class="text-muted small" style="opacity:.7"><?= htmlspecialchars($turnoLabel[$turno] ?? $turno) ?></span>
                        <form method="post" action="/admin/sesion/cerrar" style="margin:0">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Perfushopping\Web\Support\Csrf::token()) ?>" />
                            <button class="btn btn-sm btn-outline-warning" type="submit" title="Cerrar turno"><i class="bi bi-stop-fill"></i></button>
                        </form>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($adminUser['nombre'] ?? '') ?></span>
                    <span class="admin-badge <?= $adminRolBadge ?>"><?= htmlspecialchars($adminRolLabel) ?></span>
                    <button class="btn btn-sm btn-outline-info" id="btnInstallApp" type="button" style="display:none" title="Instalar app">
                        <i class="bi bi-phone"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" id="btnUpdateApp" type="button" style="display:none" title="Actualizar app">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    <form method="post" action="/admin/logout" style="margin:0">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Perfushopping\Web\Support\Csrf::token()) ?>" />
                        <button class="btn btn-sm btn-outline-light" type="submit"><i class="bi bi-box-arrow-right"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-wrap">
            <?php if (is_array($flash) && ($flash['text'] ?? '') !== ''): ?>
                <div class="flash-msg <?= htmlspecialchars((string)($flash['type'] ?? '')) ?>"><?= htmlspecialchars((string)$flash['text']) ?></div>
            <?php endif; ?>

            <?= $body ?>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        const s = document.getElementById('adminSidebar');
        const b = document.getElementById('sidebarBackdrop');
        s.classList.toggle('open');
        if (window.innerWidth <= 768) {
            b.classList.toggle('show', s.classList.contains('open'));
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const s = document.getElementById('adminSidebar');
        const b = document.getElementById('sidebarBackdrop');
        s.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    s.classList.remove('open');
                    b.classList.remove('show');
                }
            });
        });
    });
    </script>
    <script>
    var isSuperadmin = <?= json_encode(($adminRol ?? '') === 'superadmin') ?>;
    var prevStockAjustesPendientes = null;

    function playStockAlertTone() {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            var ctx = new Ctx();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 980;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.28);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.30);
            setTimeout(function() { ctx.close(); }, 350);
        } catch (e) {}
    }

    async function fetchBadges() {
        try {
            var r = await fetch('/admin/badges');
            if (!r.ok) return;
            var d = await r.json();

            var pn = document.getElementById('badgePedidosNuevos');
            if (d.pedidos_nuevos > 0) { pn.textContent = d.pedidos_nuevos; pn.style.display = ''; }
            else { pn.style.display = 'none'; }

            var pa = document.getElementById('badgePedidosAbandonados');
            if (d.pedidos_abandonados > 0) { pa.textContent = d.pedidos_abandonados; pa.style.display = ''; }
            else { pa.style.display = 'none'; }

            var un = document.getElementById('badgeUsuariosNuevos');
            if (d.usuarios_nuevos > 0) { un.textContent = d.usuarios_nuevos; un.style.display = ''; }
            else { un.style.display = 'none'; }

            var sa = document.getElementById('badgeStockAjustes');
            if (d.stock_ajustes_pendientes > 0) { sa.textContent = d.stock_ajustes_pendientes; sa.style.display = ''; }
            else { sa.style.display = 'none'; }

            var ev = document.getElementById('badgeEnviosPendientes');
            if (ev) {
                if (d.envios_pendientes > 0) { ev.textContent = d.envios_pendientes; ev.style.display = ''; }
                else { ev.style.display = 'none'; }
            }

            if (isSuperadmin) {
                var current = Number(d.stock_ajustes_pendientes || 0);
                if (prevStockAjustesPendientes !== null && current > prevStockAjustesPendientes) {
                    playStockAlertTone();
                }
                prevStockAjustesPendientes = current;
            }
        } catch(e) {}
    }
    document.addEventListener('DOMContentLoaded', function() { fetchBadges(); setInterval(fetchBadges, 30000); });
    // Demo tooltips (solo organizing-web)
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                if (el.getAttribute('title')) new bootstrap.Tooltip(el, {trigger: 'hover', delay: {show: 200, hide: 100}});
            });
        }
    });
    </script>
    <script>
    (function() {
        var installBtn = document.getElementById('btnInstallApp');
        var updateBtn = document.getElementById('btnUpdateApp');
        var deferredPrompt = null;
        var waitingWorker = null;
        var isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
        var isStandaloneIos = window.navigator.standalone === true;

        if (installBtn && isIos && !isStandaloneIos) {
            installBtn.style.display = '';
            installBtn.title = 'Instalar app';
        }

        function showUpdate(reg) {
            if (reg && reg.waiting) {
                waitingWorker = reg.waiting;
            }
            if (updateBtn && waitingWorker) {
                updateBtn.style.display = '';
            }
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin/', updateViaCache: 'none' }).then(function(reg) {
                if (reg.waiting) {
                    showUpdate(reg);
                }

                reg.addEventListener('updatefound', function() {
                    var newWorker = reg.installing;
                    if (!newWorker) return;
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            waitingWorker = newWorker;
                            if (updateBtn) updateBtn.style.display = '';
                        }
                    });
                });

                setInterval(function() {
                    reg.update();
                }, 60000);
            }).catch(function() {});

            navigator.serviceWorker.addEventListener('controllerchange', function() {
                window.location.reload();
            });
        }

        if (updateBtn) {
            updateBtn.addEventListener('click', function() {
                if (waitingWorker) {
                    waitingWorker.postMessage({ type: 'SKIP_WAITING' });
                }
            });
        }

        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            if (installBtn) installBtn.style.display = '';
        });

        if (installBtn) {
            installBtn.addEventListener('click', async function() {
                if (!deferredPrompt) {
                    if (isIos && !isStandaloneIos) {
                        alert('Para instalar en iPhone/iPad: toca Compartir y luego "Agregar a pantalla de inicio".');
                    } else {
                        alert('Si no aparece la instalacion automatica, usa el menu del navegador y toca "Instalar app" o "Agregar a pantalla de inicio".');
                    }
                    return;
                }
                deferredPrompt.prompt();
                try {
                    await deferredPrompt.userChoice;
                } catch (e) {}
                deferredPrompt = null;
                installBtn.style.display = 'none';
            });
        }
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
