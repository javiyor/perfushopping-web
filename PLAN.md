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

### 16. Caja / Arqueo
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

---

## Estructura de archivos

```
public/
  index.php                     ← todas las rutas del sistema

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
  Admin/
    AuthController.php
    DashboardController.php
    SesionController.php
    UserController.php
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
