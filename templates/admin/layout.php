<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Perfushopping</title>
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
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 10px 0; }
        .sidebar-nav .nav-section { padding: 8px 20px 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.3); }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #c8ccd4;
            text-decoration: none;
            font-size: 14px;
            transition: background .12s;
        }
        .sidebar-nav a:hover { background: var(--sidebar-hover); color: #f6f4ef; }
        .sidebar-nav a.active { background: var(--sidebar-active); color: var(--accent); border-right: 3px solid var(--accent); }
        .sidebar-nav a i { font-size: 18px; width: 22px; text-align: center; }
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

    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="document.getElementById('adminSidebar').classList.remove('open')"></div>
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <img src="/assets/brand/logo-header.png" alt="PF" />
            <span>Perfushopping</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">General</div>
            <a href="/admin" class="<?= $currentPath === '/admin' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i>Dashboard</a>

            <div class="nav-section">Productos</div>
            <a href="/admin/productos" class="<?= ($currentPath === '/admin/productos' || preg_match('#^/admin/productos/\d+#', $currentPath)) ? 'active' : '' ?>"><i class="bi bi-box-seam"></i>Productos</a>
            <a href="/admin/productos/importar" class="<?= str_starts_with($currentPath, '/admin/productos/importar') ? 'active' : '' ?>"><i class="bi bi-upload"></i>Importar</a>
            <a href="/admin/departamentos" class="<?= $currentPath === '/admin/departamentos' ? 'active' : '' ?>"><i class="bi bi-tags"></i>Departamentos</a>
            <a href="/admin/proveedores" class="<?= $currentPath === '/admin/proveedores' ? 'active' : '' ?>"><i class="bi bi-truck"></i>Proveedores</a>
            <a href="/admin/proveedores/ctacte" class="<?= str_starts_with($currentPath, '/admin/proveedores/ctacte') ? 'active' : '' ?>"><i class="bi bi-currency-dollar"></i>Cta Cte Proveedores</a>
            <a href="/admin/ordenes-compra" class="<?= str_starts_with($currentPath, '/admin/ordenes-compra') ? 'active' : '' ?>"><i class="bi bi-cart-plus"></i>Órdenes compra</a>
            <a href="/admin/ordenes-pago" class="<?= str_starts_with($currentPath, '/admin/ordenes-pago') ? 'active' : '' ?>"><i class="bi bi-credit-card"></i>Órdenes pago</a>
            <a href="/admin/ordenes-compra/fletes" class="<?= $currentPath === '/admin/ordenes-compra/fletes' ? 'active' : '' ?>"><i class="bi bi-truck"></i>Fletes</a>
            <a href="/admin/stock" class="<?= str_starts_with($currentPath, '/admin/stock') ? 'active' : '' ?>"><i class="bi bi-boxes"></i>Stock</a>
            <a href="/admin/stock/ajuste" class="<?= str_starts_with($currentPath, '/admin/stock/ajuste') ? 'active' : '' ?>"><i class="bi bi-pencil-square"></i>Ajuste stock</a>
            <a href="/admin/stock/grilla" class="<?= str_starts_with($currentPath, '/admin/stock/grilla') ? 'active' : '' ?>"><i class="bi bi-grid-3x3-gap"></i>Grilla reposición</a>

            <div class="nav-section">Ventas</div>
            <a href="/admin/orders" class="<?= str_starts_with($currentPath, '/admin/orders') ? 'active' : '' ?>"><i class="bi bi-cart"></i>Pedidos</a>
            <a href="/admin/prepare" class="<?= str_starts_with($currentPath, '/admin/prepare') ? 'active' : '' ?>"><i class="bi bi-box"></i>Preparar</a>
            <a href="/admin/presupuestos" class="<?= str_starts_with($currentPath, '/admin/presupuestos') ? 'active' : '' ?>"><i class="bi bi-file-text"></i>Presupuestos</a>
            <a href="/admin/remitos" class="<?= str_starts_with($currentPath, '/admin/remitos') ? 'active' : '' ?>"><i class="bi bi-receipt"></i>Remitos</a>
            <a href="/admin/facturas" class="<?= str_starts_with($currentPath, '/admin/facturas') ? 'active' : '' ?>"><i class="bi bi-receipt-cutoff"></i>Facturación</a>
            <a href="/admin/recibos" class="<?= str_starts_with($currentPath, '/admin/recibos') ? 'active' : '' ?>"><i class="bi bi-wallet2"></i>Recibos</a>
            <a href="/admin/ctacte" class="<?= str_starts_with($currentPath, '/admin/ctacte') ? 'active' : '' ?>"><i class="bi bi-currency-dollar"></i>Ctas. ctes.</a>
            <a href="/admin/caja" class="<?= str_starts_with($currentPath, '/admin/caja') && $currentPath !== '/admin/caja/general' ? 'active' : '' ?>"><i class="bi bi-cash-stack"></i>Caja</a>
            <a href="/admin/caja/general" class="<?= $currentPath === '/admin/caja/general' ? 'active' : '' ?>"><i class="bi bi-piggy-bank"></i>Caja General</a>
            <a href="/admin/arca" class="<?= str_starts_with($currentPath, '/admin/arca') ? 'active' : '' ?>"><i class="bi bi-cloud-check"></i>ARCA</a>
            <a href="/admin/reportes" class="<?= str_starts_with($currentPath, '/admin/reportes') ? 'active' : '' ?>"><i class="bi bi-graph-up"></i>Reportes</a>

            <div class="nav-section">Clientes</div>
            <a href="/admin/clientes" class="<?= str_starts_with($currentPath, '/admin/clientes') ? 'active' : '' ?>"><i class="bi bi-people"></i>Clientes</a>
            <a href="/admin/users" class="<?= str_starts_with($currentPath, '/admin/users') ? 'active' : '' ?>"><i class="bi bi-person-gear"></i>Usuarios web</a>

            <div class="nav-section">Administración</div>
            <a href="/admin/usuarios" class="<?= str_starts_with($currentPath, '/admin/usuarios') ? 'active' : '' ?>"><i class="bi bi-shield-lock"></i>Admins</a>
            <a href="/admin/empleados" class="<?= str_starts_with($currentPath, '/admin/empleados') ? 'active' : '' ?>"><i class="bi bi-person-badge"></i>Empleados</a>
            <a href="/admin/wholesale" class="<?= str_starts_with($currentPath, '/admin/wholesale') ? 'active' : '' ?>"><i class="bi bi-shop"></i>Mayoristas</a>
            <a href="/admin/withdrawals" class="<?= str_starts_with($currentPath, '/admin/withdrawals') ? 'active' : '' ?>"><i class="bi bi-cash"></i>Retiros</a>
            <a href="/admin/correo" class="<?= str_starts_with($currentPath, '/admin/correo') ? 'active' : '' ?>"><i class="bi bi-truck"></i>Correo Argentino</a>
            <a href="/admin/demo-tecnica" class="<?= str_starts_with($currentPath, '/admin/demo-tecnica') ? 'active' : '' ?>"><i class="bi bi-calendar-event"></i>Demo técnica</a>
            <a href="/admin/cheques" class="<?= str_starts_with($currentPath, '/admin/cheques') ? 'active' : '' ?>"><i class="bi bi-file-text"></i>Cheques</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
