# Plan de desarrollo — Panel Admin Perfushopping

## Objetivo

Construir un sistema de gestión comercial completo (panel admin) para Perfushopping, separado del frontend web, con login propio para administradores por roles, y módulos de productos, clientes, facturación tipo POS, remitos, presupuestos, recibos, cuentas corrientes, stock y reportes.

---

## Convenciones

- Panel admin usa URL `/admin/*`
- Bootstrap 5 vía CDN
- Roles: superadmin, ventas, administracion, compras, caja
- NO se crean tablas para datos que ya existen en el ERP. Las tablas ERP (`producto`, `gustos`, `clientes`, `proveedo`, `departa`, `sucur`, `deposito`, `stock`, `stockcab`, `stockdet`, `ivaprodu`) ya existen en Hostinger
- `producto.precio` y `producto.precomp` son SIN IVA (netos). IVA = multiplicar por `(1 + tiva/100)`
- Precios en cents en tablas propias (presupuestos, facturas, etc.), decimales en ERP
- `$auth->requireSesion()` reemplazó a `requireLogin()` — requiere auth + sucursal + turno
- `AdminController` completamente migrado a controladores dedicados en `src/Admin/`. Eliminado.
- Archivos legacy standalone en root y `upload/` eliminados (17 archivos). Se mantienen `public/upload/gustos_sync.php` y `public/upload/subir_imagen.php` para compatibilidad VFP.
- Login incluye selección de sucursal; después del login se redirige a `/admin/sesion/iniciar` para turno
- `View::adminPage()` renderiza en `templates/admin/layout.php`

---

## Arquitectura

- PHP nativo sin framework
- Autoloader PSR-4-like (`namespace Perfushopping\Web\*` → `src/*`)
- Router propio en `src\Support\Router.php` con match por regex exacta (`#^pattern$#`)
- Sesión admin independiente de la sesión web (usa `admin_user_id`)
- `AdminAuthService` maneja auth con tabla `admin_users` + sesión de sucursal/turno
- `requireLogin()` → solo verifica auth. `requireSesion()` → auth + sucursal + turno
- Layout topbar muestra: sucursal, turno (☀️ Mañana / 🌤️ Tarde), botón cerrar turno, nombre admin, rol, botón logout

---

## Módulos completados

### 1. Base admin
- Login/logout con selección de sucursal
- Roles con permisos
- Layout BS5 con sidebar responsivo + topbar
- Dashboard con stats (pedidos, usuarios, admins)
- Selección de turno (mañana/tarde) y vendedores

### 2. Productos
- Listado con filtros
- Edición de producto y variantes
- Imágenes principal y de variantes
- Vista sobre tablas ERP `producto`/`gustos`

### 3. Importación CSV
- Preview con diff de precios y stock
- Confirmación de importación

### 4. Departamentos
- CRUD sobre tabla ERP `departa`

### 5. Clientes
- Búsqueda en `web_users` + ERP `clientes`
- Historial de pedidos
- Notas internas

### 6. Proveedores
- CRUD sobre tabla ERP `proveedo`

### 7. Presupuestos
- Creación con búsqueda AJAX de productos y clientes
- Cambio de estado (pendiente → aprobado/rechazado/vencido)
- Detalle con items y totales en cents

### 8. Remitos
- Entrada (proveedor) y Salida (cliente)
- Vínculo con presupuestos aprobados
- Tablas: `remitos`, `remito_items`

### 9. Facturación POS
- Interfaz tipo supermercado
- Búsqueda rápida de productos con código de barras
- Carrito en vivo con cantidades y precios
- Selección de cliente con auto-detección de tipo (RI → Factura A)
- Formas de pago: efectivo, transferencia, débito, crédito, cuenta corriente
- Cálculo de vuelto
- Store vía JSON
- Impresión ticket 80mm
- Auto-posteo a cuenta corriente si corresponde
- Reverse de ctacte al anular
- Tablas: `facturas`, `factura_items`, `factura_pagos`

### 10. Recibos
- Seleccionar cliente
- Ver facturas pendientes
- Elegir cuáles cancela
- Emitir recibo
- Auto-posteo a cuenta corriente
- Tablas: `recibos`, `recibo_items`, `recibo_facturas_canceladas`

### 11. Cuentas corrientes
- Movimientos (débito/crédito) con saldo corrido
- Auto-posteo desde facturas (cta.cte.) y recibos
- Reversión al anular
- Ajuste manual
- Tabla: `ctacte_movimientos`

### 12. Sucursal + Turno
- Login con sucursal
- Selección de turno mañana/tarde + vendedores
- Display en topbar
- `punto_venta` por sucursal conectado a facturación
- Sesión manejada en `$_SESSION` (sin tabla DB)

### 13. Stock
- Listado con filtros (texto, departamento, estado stock)
- Detalle con variantes
- Stock por depósito
- Movimientos históricos desde `stockcab`/`stockdet`
- Consultas sobre tablas ERP: `producto`, `gustos`, `stock`, `stockcab`, `stockdet`, `deposito`

### 14. Reportes
- Filtro por rango de fechas
- KPIs: facturas emitidas, total vendido, IVA total, cobrado (recibos)
- Gráfico de ventas diarias (Chart.js)
- Top productos vendidos
- Ventas por departamento (con barra de progreso)
- Ventas por forma de pago
- Facturas por tipo de comprobante
- Exportación a CSV
- Datos vía endpoint JSON (`/admin/reportes/data`)

### 15. Órdenes de compra
- Pedidos a proveedores
- Búsqueda AJAX de proveedores y productos (con variantes + código de barras)
- Precio de costo en cents
- Cálculo de total en vivo
- Estados: pendiente → aprobada → recibida → anulada
- Sin transición desde recibida o anulada
- Tablas: `ordenes_compra`, `orden_compra_items`

### 16. Sync VFP → Hostinger
- Endpoint `/api/v1/sync-tables` para push HTTP desde local a Hostinger
- Script `src/push_tables.php` para push desde la PC local
- Script `src/pull_tables.php` para pull desde Hostinger
- Script `src/local_api.php` expone MySQL local vía HTTP en puerto 8080
- `push_daily.bat` para Windows Task Scheduler
- Dinamismo: columnas detectadas vía INFORMATION_SCHEMA
- Tablas sincronizadas: `producto`, `gustos`, `stockcab`, `stockdet`

### 17. Migración de AdminController (legacy → nuevos controladores)
Se migraron todas las rutas del `AdminController` original a controladores dedicados en `src/Admin/`:

| Controlador nuevo | Métodos | Reemplaza de AdminController |
|---|---|---|
| `WebOrderController` | index, prepare, status, recoverAbandoned, archiveAbandoned | orders, prepare, orderStatus, recoverAbandoned, archiveAbandoned |
| `WebUserController` | index, save, roleSave, toggleBlock, delete, passwordReset | users, userSave, userRoleSave, userToggleBlock, userDelete, userPasswordReset |
| `WholesaleController` | index, approve, reject (+ upsertCliente) | wholesaleList, wholesaleApprove, wholesaleReject |
| `AffiliateController` | release | affiliateRelease |
| `WithdrawalController` | index, approve, paid, reject | withdrawals, withdrawalsApprove, withdrawalsPaid, withdrawalsReject |
| `CapacitacionController` | index, horarios, horariosSave, status | demoTech, demoTechEvents, demoTechEventSave, demoTechStatus |
| `CorreoController` | index, auth, agencies, savedAgencies | correo, correoAuth, correoAgencies, correoSavedAgencies |

Rutas públicas de `/eventos/demo-tecnica/*` (frontend) se mantienen en `DemoTechController` (sin cambios).

### 18. Renombre: Demo Técnica → Capacitaciones
- URLs admin: `/admin/demo-tecnica/*` → `/admin/capacitaciones/*`
- Templates: `demo_tech.php` → `capacitaciones/registros.php`, `demo_tech_events.php` → `capacitaciones/horarios.php`
- Sidebar y dashboard actualizados

### 19. Bugfixes
- Subrubro no se limpiaba al subir imagen (nested forms corregido en `edit.php`)
- Paginación agregada a listado de productos

### 20. Caja / Arqueo
- Apertura de caja por turno con monto inicial
- Movimientos extra (ingresos/egresos no facturables)
- Arqueo con conteo físico y cálculo de diferencia vs saldo esperado
- Cierre de caja con resumen del turno
- Integración con SesionController: no permite cerrar turno si la caja está abierta
- Dashboard con KPIs: apertura, ventas efectivo, transferencias, recibos, movimientos, saldo esperado
- Historial de cierres anteriores
- Tablas: `caja_aperturas`, `caja_movimientos`, `caja_arqueos`

---

## Pendientes

1. **AFIP/ARCA** — Factura electrónica (integración con servicios AFIP)
2. ~~Órdenes de compra~~ ✅
3. ~~Caja / Arqueo~~ ✅
4. ~~Ajustes de stock manuales~~ ✅
5. ~~Reportes~~ ✅
6. ~~Migración AdminController → controladores nuevos~~ ✅
7. ~~Eliminar AdminController legacy~~ ✅
8. ~~Evaluar y eliminar archivos legacy standalone~~ ✅ (17 archivos)
9. ~~Compactar formulario de producto~~ ✅
10. ~~Sistema de permisos por rol~~ ✅ — columna `permisos` (JSON) en `admin_users`, checkboxes en modal, fallback a defaults del rol
11. ~~Renombrar Demo Técnica → Capacitaciones~~ ✅

---

## Estructura de archivos

```
public/
  index.php                     ← todas las rutas del sistema

sync/                             ← scripts de sincronización VFP → Hostinger
  push_tables.php               ← push desde PC local a Hostinger vía HTTP API
  pull_tables.php               ← pull desde Hostinger hacia local vía HTTP
  local_api.php                 ← expone MySQL local vía HTTP en puerto 8080
  sync_config.php               ← credenciales DB local
push_daily.bat                  ← Windows Task Scheduler

src/
  bootstrap.php                 ← autoloader
  Infra/
    Db.php                      ← conexión MySQL
  Support/
    Router.php                  ← enrutador
    View.php                    ← renderizado (adminPage)
    Response.php                ← respuestas HTTP
    Format.php                  ← formateo de dinero
    Csrf.php                    ← tokens CSRF
  Service/
    AdminAuthService.php        ← auth + sesión sucursal/turno
  Repo/
    AdminUserRepo.php
    AdminProductRepo.php
    CustomerRepo.php
    ProveedorRepo.php
    DepartamentoRepo.php
    PresupuestoRepo.php
    RemitoRepo.php
    FacturaRepo.php
    ReciboRepo.php
    CtaCteRepo.php
    SucursalRepo.php
    StockRepo.php
    ReporteRepo.php
    OrdenCompraRepo.php
    CajaRepo.php
    OrderRepo.php                 ← pedidos web
    UserRepo.php                  ← usuarios web
    WholesaleRepo.php             ← mayoristas
    AffiliateLedgerRepo.php       ← afiliados
    AffiliateWithdrawalRepo.php   ← retiros
    DemoTechRepo.php              ← capacitaciones (demo tech)
    CorreoRepo.php                ← Correo Argentino
  Admin/
    AuthController.php
    DashboardController.php
    SesionController.php
    UserController.php            ← admins del panel
    ProductController.php
    ImportController.php
    DepartamentoController.php
    CustomerController.php
    ProveedorController.php
    PresupuestoController.php
    RemitoController.php
    FacturaController.php
    ReciboController.php
    CtaCteController.php
    StockController.php
    ReporteController.php
    OrdenCompraController.php
    CajaController.php
    WebOrderController.php        ← pedidos web (migrado)
    WebUserController.php         ← usuarios web (migrado)
    WholesaleController.php       ← mayoristas (migrado)
    AffiliateController.php       ← afiliados (migrado)
    WithdrawalController.php      ← retiros (migrado)
    CapacitacionController.php    ← capacitaciones (ex demo técnica, migrado)
    CorreoController.php          ← Correo Argentino (migrado)
  Controller/

templates/admin/
  layout.php
  auth/login.php
  sesion/iniciar.php
  dashboard.php
  usuarios/list.php
  productos/{list,edit,import}.php
  departamentos/list.php
  clientes/{list,detail}.php
  proveedores/list.php
  presupuestos/{list,form,detail}.php
  remitos/{list,form,detail}.php
  facturas/{list,pos,detail,print}.php
  recibos/{list,form,detail,print}.php
  ctacte/{list,show,ajuste}.php
  stock/{list,show,ajuste}.php
  reportes/index.php
  ordenes-compra/{list,form,detail}.php
  caja/{index,abrir,movimientos,arqueo,cierre}.php
  orders.php                      ← pedidos web (migrado)
  prepare.php                    ← preparación pedidos (migrado)
  users.php                      ← usuarios web (migrado)
  wholesale_list.php             ← mayoristas (migrado)
  withdrawals.php                ← retiros (migrado)
  correo.php                     ← Correo Argentino (migrado)
  capacitaciones/
    registros.php                ← capacitaciones (migrado)
    horarios.php                 ← horarios capacitaciones (migrado)

db/
  patches_admin.sql
  patches_cliente_notas.sql
  patches_presupuestos.sql
  patches_remitos.sql
  patches_facturas.sql
  patches_recibos.sql
  patches_ctacte.sql
  patches_sucursales.sql
  patches_ordenes_compra.sql
  patches_caja.sql
```

---

## Tablas creadas (propias del sistema)

| Tabla | Módulo | Propósito |
|---|---|---|
| `admin_users` | Auth | Usuarios admin con roles |
| `admin_sucursales` | Sucursal | punto_venta por sucursal |
| `presupuestos` | Presupuestos | Cabecera de presupuesto |
| `presupuesto_items` | Presupuestos | Items del presupuesto |
| `remitos` | Remitos | Cabecera de remito |
| `remito_items` | Remitos | Items del remito |
| `facturas` | Facturación | Cabecera de factura |
| `factura_items` | Facturación | Items de factura |
| `factura_pagos` | Facturación | Pagos de factura |
| `recibos` | Recibos | Cabecera de recibo |
| `recibo_items` | Recibos | Items/lineas del recibo |
| `recibo_facturas_canceladas` | Recibos | Facturas canceladas en un recibo |
| `ctacte_movimientos` | Cta. Cte. | Movimientos de cuenta corriente |
| `cliente_notas` | Clientes | Notas internas por cliente |
| `ordenes_compra` | Órdenes compra | Cabecera de orden |
| `orden_compra_items` | Órdenes compra | Items de orden |
| `caja_aperturas` | Caja | Apertura/cierre de caja por turno |
| `caja_movimientos` | Caja | Movimientos extra de caja |
| `caja_arqueos` | Caja | Arqueos/conteo físico |

## Tablas ERP consultadas (NO se modifican)

| Tabla | Uso |
|---|---|
| `producto` | Productos, stock, precios |
| `gustos` | Variantes de producto |
| `clientes` | Clientes ERP |
| `proveedo` | Proveedores |
| `departa` | Departamentos |
| `sucur` | Sucursales |
| `deposito` | Depósitos |
| `stock` | Stock por depósito |
| `stockcab` | Cabecera de movimientos de stock |
| `stockdet` | Detalle de movimientos de stock |
| `ivaprodu` | Alicuotas de IVA |
| `web_users` | Usuarios web (clientes frontend) |
