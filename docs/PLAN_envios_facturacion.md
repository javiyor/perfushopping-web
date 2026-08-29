# Plan — Facturación con Local / Envío + Caja

## Objetivo
Diferenciar en POS si la venta es **en local** o **con envío**, indicando transporte, y reflejar correctamente en **Caja** y en un apartado de **Envíos pendientes**.

## Reglas de negocio acordadas
- Facturación POS agrega selector:
  - `entrega_tipo`: `local` | `envio`
  - Si `envio`: `transporte` = `propio` | `delivery` | `correo_argentino` + datos de domicilio (dirección/teléfono opcional).
- Caja:
  - `local` → impacta inmediatamente en caja chica del turno (efectivo y transferencias).
  - `envio` + pago `transferencia` / `mercadopago` / `tarjeta_*` / `link` → impacta inmediatamente en caja (o caja general si es transferencia, según flujo actual).
  - `envio` + pago `efectivo` (contra entrega) → **NO** impacta caja hasta que se marque **Entregado / Cobrado** desde apartado Envíos. Queda pendiente.
- Envíos:
  - Listado `/admin/envios` muestra facturas `entrega_tipo=envio` con `envio_estado` pendiente.
  - Acción `Marcar entregado` → cambia estado a `entregado` y si el pago es efectivo, genera movimiento en caja (ingreso).
  - También permite `Cancelar envío`.

## Cambios DB (aditivo, sin romper existente)
`db/patches_facturas_envios.sql`:
- `facturas` add columns: `entrega_tipo ENUM('local','envio') DEFAULT 'local'`, `transporte ENUM('propio','delivery','correo_argentino')`, `envio_estado ENUM('pendiente','en_transito','entregado','cancelado') DEFAULT NULL`, `envio_direccion VARCHAR(255)`, `envio_observacion TEXT`.
- Para compatibilidad, repo hace `ALTER` defensivo con try/catch + `SHOW COLUMNS`.

Alternativa considerada (tabla `factura_envios` separada) descartada para MVP por simplicidad; se usa misma tabla `facturas`.

## Backend
- `FacturaRepo`:
  - `create()` acepta entrega_*.
  - `search()`/`findById()` devuelven entrega_*.
  - `listarEnviosPendientes()`, `marcarEnvioEntregado()`.
- `CajaRepo`:
  - `totalVentasEfectivo()`, `totalVentasTransferencia()`, `ventasPorPuntoVenta()` filtran: solo cuentan facturas donde `entrega_tipo='local'` OR `transporte != efectivo-contra-entrega` OR `envio_estado='entregado'`.
  - Definición: efectivo contra entrega = `entrega_tipo='envio' AND forma_pago='efectivo' AND envio_estado != 'entregado'`.
- `FacturaController::store()`:
  - Lee `entrega` del payload JSON.
  - Valida transporte si es envío.
  - Pasa a `FacturaRepo::create`.
  - No genera movimiento caja extra aquí; caja se calcula por agregación. Para envíos efectivo pendiente, no contará hasta entrega.
- `EnvioController` (nuevo `src/Admin/EnvioController.php`):
  - `index()`, `entregar()`, `cancelar()`.

## Frontend POS
- `templates/admin/facturas/pos.php`:
  - Radio/Select `Retiro en local` / `Envío a domicilio` antes de pagar.
  - Si `envío`: muestra select `Transporte` + inputs dirección/observación.
  - Valida antes de `submitFactura()`.
  - Envía `entrega: {tipo, transporte, direccion}` en payload.

## Detalle y Listados
- `templates/admin/facturas/detail.php`: badge entrega + transporte + estado + acción si es envío pendiente.
- `templates/admin/facturas/list.php`: columna entrega.
- Nuevo `templates/admin/envios/index.php`: tabla pendientes con acciones.

## Rutas
- `GET /admin/envios` → EnvioController@index
- `POST /admin/envios/entregar` → EnvioController@entregar
- `POST /admin/envios/cancelar` → EnvioController@cancelar
- `GET /admin/envios/data` opcional JSON.

## Caja
- Sin cambio de tabla; solo filtros.
- Al entregar, `EnvioController` registra ingreso en `caja_movimientos` o `caja_general_movimientos` si corresponde, usando `CajaRepo::agregarMovimiento`.

## Fases
1. Migración + Repo + Controller store (esta entrega).
2. UI POS + detalle + listado.
3. Apartado Envíos + caja al entregar + pruebas.

## Riesgos
- Filtrar caja no debe romper reporte histórico: facturas antiguas sin entrega_tipo se tratan como 'local'.
- Validar punto_venta aún viene de sucursal; entrega no cambia PV.
