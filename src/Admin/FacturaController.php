<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\CobroCuentaRepo;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipPadronService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class FacturaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        // Por defecto, el primer ingreso muestra solo las facturas del día de hoy.
        if (empty($_GET)) {
            $desde = $hasta = date('Y-m-d');
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 60;

        $repo = new FacturaRepo();
        $total = $repo->countSearch($q, $estado, $desde, $hasta);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $list = $repo->search($q, $estado, $perPage, ($page - 1) * $perPage, $desde, $hasta);

        echo View::adminPage('admin/facturas/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'desde' => $desde,
            'hasta' => $hasta,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Facturación',
        ]);
    }

    public function pos(array $params): void
    {
        $auth = new AdminAuthService();

        $repo = new FacturaRepo();
        $remitoId = (int)($_GET['remito_id'] ?? 0);
        $remitoItems = [];
        $presupuestoId = (int)($_GET['presupuesto_id'] ?? 0);
        $presupuestoItems = [];

        if ($remitoId > 0) {
            $remito = (new \Perfushopping\Web\Repo\RemitoRepo())->findById($remitoId);
            if ($remito && $remito['estado'] === 'completado') {
                $remitoItems = $repo->itemsByRemito($remitoId);
            }
        }

        if ($presupuestoId > 0) {
            $presupuesto = (new \Perfushopping\Web\Repo\PresupuestoRepo())->findById($presupuestoId);
            if ($presupuesto && $presupuesto['estado'] === 'aprobado') {
                $presupuestoItems = $repo->itemsByPresupuesto($presupuestoId);
            }
        }

        $pedidoId = (int)($_GET['pedido_id'] ?? 0);
        $pedido = null;
        $pedidoItems = [];
        $pedidoCliente = null;
        $pedidoEnvio = null;
        $pedidoPago = null;
        $pedidoDescPct = 0;
        if ($pedidoId > 0) {
            $prefill = $this->pedidoPrefill($pedidoId);
            if ($prefill) {
                $pedido = $prefill['pedido'];
                $pedidoItems = $prefill['items'];
                $pedidoCliente = $prefill['cliente'];
                $pedidoEnvio = $prefill['envio'];
                $pedidoPago = $prefill['pago'];
                $pedidoDescPct = $prefill['descuento_pct'];
            } else {
                $pedidoId = 0;
            }
        }

        echo View::adminPage('admin/facturas/pos.php', $this->posCommon($auth) + [
            'remitoId' => $remitoId,
            'remitoItems' => $remitoItems,
            'presupuestoId' => $presupuestoId,
            'presupuestoItems' => $presupuestoItems,
            'pedidoId' => $pedidoId,
            'pedidoCodigo' => $pedido ? (string)($pedido['codigo'] ?? '') : '',
            'pedidoItems' => $pedidoItems,
            'pedidoCliente' => $pedidoCliente,
            'pedidoEnvio' => $pedidoEnvio,
            'pedidoPago' => $pedidoPago,
            'pedidoDescPct' => $pedidoDescPct,
            'pageTitle' => 'Nueva factura',
        ]);
    }

    /** Datos comunes del POS (también usado al editar comprobantes). */
    private function posCommon(AdminAuthService $auth): array
    {
        $adminUser = $auth->requirePermiso('facturacion');

        $vendedoresSesion = $auth->getVendedores();
        $vendedores = [];
        if ($vendedoresSesion) {
            $st = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT id, nombre, username, rol FROM admin_users WHERE id IN (' . implode(',', array_fill(0, count($vendedoresSesion), '?')) . ') AND activo = 1');
            $st->execute(array_values($vendedoresSesion));
            $vendedores = $st->fetchAll();
        }

        $pedidosPendientes = [];
        try {
            if ((new FacturaRepo())->ensureOrderColumn()) {
                $pedidosPendientes = (new \Perfushopping\Web\Repo\OrderRepo())->paidNotInvoiced(100);
            }
        } catch (\Throwable $e) {
            $pedidosPendientes = [];
        }

        $cobroRepo = new CobroCuentaRepo();
        $transferCuentaId = $cobroRepo->getTransferenciaCuentaId();
        $tarjetaCobros = [];
        try { $tarjetaCobros = $cobroRepo->all(); } catch (\Throwable $e) {}
        $tarjetaBancoMap = [];
        foreach ($tarjetaCobros as $tc) {
            if (($tc['tipo'] ?? '') === 'tarjeta' && !empty($tc['idtarje'])) {
                $tarjetaBancoMap[(int)$tc['idtarje']] = (int)$tc['banco_cuenta_id'];
            }
        }

        return [
            'adminUser' => $adminUser,
            'pedidosPendientes' => $pedidosPendientes,
            'vendedores' => $vendedores,
            'bancos' => $this->bancos(),
            'bancosCuentas' => $this->bancosCuentas(),
            'tarjetas' => $this->tarjetas(),
            'equipos' => $this->equipos((int)$auth->getSucursalId()),
            'plazos' => $this->plazos(),
            'formasPago' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->findActivas(),
            'transferCuentaId' => $transferCuentaId,
            'tarjetaBancoMap' => $tarjetaBancoMap,
            'csrf' => Csrf::token(),
        ];
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $input = json_decode(file_get_contents('php://input'), true);
        Csrf::check($input['_csrf'] ?? null);
        if (!$input || !isset($input['items']) || !is_array($input['items'])) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        if (count($input['items']) < 1) {
            Response::json(['ok' => false, 'error' => 'Agregá al menos un producto.']);
            return;
        }

        $tipo = (string)($input['tipo_comprobante'] ?? 'FACT-B');
        $cliente = $input['cliente'] ?? [];
        $clienteNombre = trim((string)($cliente['nombre'] ?? ''));
        $clienteCuit = trim((string)($cliente['cuit'] ?? ''));
        $clienteId = (int)($cliente['id'] ?? 0) ?: null;
        $clienteCondIva = trim((string)($cliente['condicion_iva'] ?? 'consumidor_final'));

        $repo = new FacturaRepo();
        // Si el cliente está identificado pero el nombre llegó vacío o como CF, usar su razón real.
        $clienteNombre = $this->resolverNombreCliente(
            $repo,
            $clienteNombre,
            $clienteId,
            (int)($cliente['idclien'] ?? 0) ?: null
        );

        $itemsData = $this->normalizarItems($input['items']);
        $items = $itemsData['items'];
        $subtotal = $itemsData['subtotal'];
        $ivaTotal = $itemsData['iva'];

        $pagos = $this->normalizarPagos($input['pagos'] ?? [], $input, (int)$adminUser['id'], $clienteNombre, $subtotal + $ivaTotal);

        $formaPago = $pagos[0]['forma_pago'] ?? 'efectivo';

        $entrega = $input['entrega'] ?? [];
        $entregaTipo = in_array($entrega['tipo'] ?? 'local', ['local','envio'], true) ? $entrega['tipo'] : 'local';
        $transporte = null;
        $envioEstado = null;
        $envioDireccion = null;
        $envioObs = null;
        if ($entregaTipo === 'envio') {
            $transporte = in_array($entrega['transporte'] ?? '', ['propio','delivery','correo_argentino'], true) ? $entrega['transporte'] : 'propio';
            $envioEstado = 'pendiente';
            $envioDireccion = trim((string)($entrega['direccion'] ?? ''));
            $envioObs = trim((string)($entrega['observacion'] ?? ''));
        }

        if (\Perfushopping\Web\Support\Env::isDemo() && $repo->countActivas() >= 20) {
            Response::json(['ok' => false, 'error' => 'La demo permite un máximo de 20 facturas.']);
            return;
        }

        $codigo = $repo->nextCodigo($tipo);

        $clienteDirec = trim((string)($cliente['direc'] ?? ''));
        $clienteTele = trim((string)($cliente['tele'] ?? ''));
        $clienteMail = trim((string)($cliente['mail'] ?? ''));
        $clienteErpId = null;

        $clienteErpId = (int)($cliente['idclien'] ?? 0) ?: null;
        if (!$clienteErpId && $clienteId) {
            $erp = $repo->findClienteErpByWebId($clienteId);
            $clienteErpId = $erp ? (int)$erp['idclien'] : null;
        }
        if ($clienteId || $clienteErpId) {
            $erp = $clienteErpId ? $repo->findClienteByIdclien($clienteErpId) : $repo->findClienteErpByWebId($clienteId);
            if ($erp) {
                $clienteErpId = (int)$erp['idclien'];
                if (!$clienteDirec) $clienteDirec = trim((string)($erp['direc'] ?? ''));
                if (!$clienteTele) $clienteTele = trim((string)($erp['tele'] ?? ''));
                if (!$clienteMail) $clienteMail = trim((string)($erp['mail'] ?? ''));
            }
        }

        $vendedorId = (int)($input['vendedor_id'] ?? 0) ?: null;

        $notas = (string)($input['notas'] ?? '');
        $remitoId = (int)($input['remito_id'] ?? 0) ?: null;
        if ($remitoId) {
            $r = (new \Perfushopping\Web\Repo\RemitoRepo())->findById($remitoId);
            if ($r && $r['estado'] === 'completado') {
                $notas = ($notas ? $notas . "\n" : '') . 'Remito: ' . $r['codigo'];
            } else {
                $remitoId = null;
            }
        }
        $presupuestoId = (int)($input['presupuesto_id'] ?? 0) ?: null;
        if ($presupuestoId) {
            $p = (new \Perfushopping\Web\Repo\PresupuestoRepo())->findById($presupuestoId);
            if ($p && $p['estado'] === 'aprobado') {
                $notas = ($notas ? $notas . "\n" : '') . 'Presupuesto: ' . $p['codigo'];
            } else {
                $presupuestoId = null;
            }
        }
        $pedidoId = (int)($input['pedido_id'] ?? 0) ?: null;
        if ($pedidoId) {
            $order = (new \Perfushopping\Web\Repo\OrderRepo())->find($pedidoId);
            if (!$order || !in_array((string)($order['status'] ?? ''), $this->pedidosImportables(), true)) {
                Response::json(['ok' => false, 'error' => 'El pedido no está apto para facturar.'], 422);
                return;
            }
            if ($repo->facturaIdByOrder($pedidoId)) {
                Response::json(['ok' => false, 'error' => 'El pedido ya fue facturado.'], 422);
                return;
            }
            $notas = ($notas ? $notas . "\n" : '') . 'Pedido web: ' . (string)($order['order_code'] ?? $pedidoId);
        }

        $fecha = (string)($input['fecha'] ?? date('Y-m-d'));
        $descuento = max(0, (int)($input['descuento_cents'] ?? 0));

        // Loyalty points redemption (1 punto = $1): reduce the payable total.
        $puntosUsados = max(0, (int)($input['puntos_usados'] ?? 0));
        $puntosUsadosCents = $puntosUsados * 100;
        if ($puntosUsados > 0) {
            $saldoPuntos = (new \Perfushopping\Web\Repo\PuntosRepo())->saldo($clienteErpId ?: 0);
            if ($puntosUsados > $saldoPuntos) {
                Response::json(['ok' => false, 'error' => 'El cliente no tiene suficientes puntos para canjear.'], 422);
                return;
            }
            if ($puntosUsadosCents > ($subtotal + $ivaTotal - $descuento)) {
                $puntosUsados = (int)floor(($subtotal + $ivaTotal - $descuento) / 100);
                $puntosUsadosCents = $puntosUsados * 100;
            }
        }

        $id = $repo->create([
            'codigo' => $codigo,
            'tipo_comprobante' => $tipo,
            'remito_id' => $remitoId,
            'presupuesto_id' => $presupuestoId,
            'order_id' => $pedidoId,
            'cliente_id' => $clienteId,
            'idclien' => $clienteErpId,
            'cliente_nombre' => $clienteNombre,
            'cliente_cuit' => $clienteCuit,
            'cliente_direc' => $clienteDirec,
            'cliente_tele' => $clienteTele,
            'cliente_mail' => $clienteMail,
            'cliente_condicion_iva' => $clienteCondIva,
            'punto_venta' => $auth->getPuntoVenta(),
            'sucursal_id' => $auth->getSucursalId(),
            'fecha' => $fecha,
            'subtotal_cents' => $subtotal,
            'iva_cents' => $ivaTotal,
            'descuento_cents' => $descuento,
            'puntos_cents' => $puntosUsadosCents,
            'total_cents' => $subtotal + $ivaTotal - $descuento - $puntosUsadosCents,
            'estado' => 'emitida',
            'forma_pago' => $formaPago,
            'entrega_tipo' => $entregaTipo,
            'transporte' => $transporte,
            'envio_estado' => $envioEstado,
            'envio_direccion' => $envioDireccion,
            'envio_observacion' => $envioObs,
            'notas' => $notas,
            'created_by' => (int)$adminUser['id'],
            'vendedor_id' => $vendedorId,
        ], $items, $pagos);

        // Loyalty points: register redemption and accrue the purchase.
        $puntosService = new \Perfushopping\Web\Service\PuntosService();
        if ($puntosUsados > 0 && $clienteErpId) {
            $puntosService->usarEnFactura($clienteErpId, $puntosUsados, $id, (int)$adminUser['id']);
        }
        $factura = $repo->findById($id);
        if ($factura) {
            $puntosService->acreditarFactura($factura, $items);
        }

        // Deduct stock from session deposit
        $depoId = $auth->getDepositoId();
        if ($depoId > 0) {
            $stockRepo = new StockRepo();
            foreach ($items as $it) {
                $idprodu = $it['idprodu'];
                $idcodgusto = $it['idcodgusto'];
                $qty = $it['qty'];
                if ($idprodu) {
                    $stockRepo->registrarAjuste($idprodu, $idcodgusto, $depoId, 0, $qty, 'Factura ' . $codigo, (int)$adminUser['id'], 'venta');
                }
            }
        }

        // Registrar movimientos bancarios para transferencias y tarjetas
        $this->registrarBancoMov($pagos, $id, $codigo, $fecha, (int)$adminUser['id']);

        // Encolar impresión en tickets para la impresora del punto de venta.
        try {
            (new \Perfushopping\Web\Repo\PrintJobRepo())->encolar(
                $id,
                (int)$auth->getPuntoVenta(),
                $auth->getSucursalId() > 0 ? $auth->getSucursalId() : null
            );
        } catch (\Throwable $e) { error_log('PrintJob encolar: '.$e->getMessage()); }

        // Auto-post to current account if forma_pago = cuenta_corriente
        if ($clienteId && $formaPago === 'cuenta_corriente') {
            $ctaCte = new \Perfushopping\Web\Repo\CtaCteRepo();
            $ctaCte->agregarMovimiento(
                'debito',
                'factura',
                $id,
                $clienteId,
                $clienteErpId,
                $subtotal + $ivaTotal - $descuento,
                'Factura ' . $codigo . ' — ' . $clienteNombre,
                (int)$adminUser['id']
            );
        }

        // Auto-send to ARCA if enabled
        $arca = $this->autoEnviarArca($id, $repo);
        if (isset($arca['codigo'])) {
            $codigo = $arca['codigo'];
        }

        Response::json([
            'ok' => true,
            'id' => $id,
            'codigo' => $codigo,
            'arca' => !empty($arca['cae']) ? ['cae' => $arca['cae']] : (!empty($arca['error']) ? ['error' => $arca['error']] : null),
        ]);
    }

    /**
     * El POS puede traer el cliente identificado pero sin nombre (id de web_users = 0
     * cuando no tiene cuenta web). Si hay id, resuelve la razón social real.
     */
    private function resolverNombreCliente(FacturaRepo $repo, string $nombre, ?int $clienteWebId, ?int $clienteErpId): string
    {
        $nombre = trim($nombre);
        if ($nombre !== '' && strcasecmp($nombre, 'Consumidor Final') !== 0) {
            return $nombre;
        }

        $idclien = $clienteErpId ?: 0;
        if ($idclien <= 0 && ($clienteWebId ?: 0) > 0) {
            $erp = $repo->findClienteErpByWebId((int)$clienteWebId);
            $idclien = $erp ? (int)$erp['idclien'] : 0;
        }
        if ($idclien > 0) {
            $erp = $repo->findClienteByIdclien($idclien);
            $razon = $erp ? trim((string)($erp['razon'] ?? '')) : '';
            if ($razon !== '') {
                return $razon;
            }
        }

        return $nombre !== '' ? $nombre : 'Consumidor Final';
    }

    /** @return array{items:array,subtotal:int,iva:int} */
    private function normalizarItems(array $rawItems): array
    {
        $items = [];
        $subtotal = 0;
        $ivaTotal = 0;
        foreach ($rawItems as $it) {
            $qty = max(1, (int)($it['qty'] ?? 1));
            $unitPrice = max(0, (int)($it['unit_price_cents'] ?? 0));
            $ivaRate = (float)($it['iva_rate'] ?? 21);
            $dtoPct = min(100.0, max(0.0, (float)($it['descuento_pct'] ?? 0)));
            $lineNet = (int)round($qty * $unitPrice * (1 - $dtoPct / 100));
            $lineIva = $ivaRate > 0 ? (int)round($lineNet * $ivaRate / 100) : 0;
            $items[] = [
                'idprodu' => (int)($it['idprodu'] ?? 0) ?: null,
                'idcodgusto' => (int)($it['idcodgusto'] ?? 0) ?: null,
                'producto' => trim((string)($it['producto'] ?? '')),
                'variedad' => trim((string)($it['variedad'] ?? '')),
                'qty' => $qty,
                'unit_price_cents' => $unitPrice,
                'iva_rate' => $ivaRate,
                'descuento_pct' => $dtoPct,
                'iva_cents' => $lineIva,
                'total_cents' => $lineNet + $lineIva,
            ];
            $subtotal += $lineNet;
            $ivaTotal += $lineIva;
        }
        return ['items' => $items, 'subtotal' => $subtotal, 'iva' => $ivaTotal];
    }

    /**
     * Normaliza el array de pagos del POS. Si un pago de cheque trae cheque_id (edición),
     * reutiliza ese cheque en cartera en lugar de crear uno nuevo.
     * @throws \InvalidArgumentException cuando un pago en moneda extranjera está incompleto.
     */
    private function normalizarPagos(array $pagosRaw, array $input, int $adminUserId, string $clienteNombre, int $fallbackMonto): array
    {
        $pagos = [];
        $bancoNombres = [];
        if ($pagosRaw) {
            $st = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT idban, nombanc FROM bancos');
            foreach ($st->fetchAll() as $b) {
                $bancoNombres[(int)$b['idban']] = (string)$b['nombanc'];
            }
        }
        foreach ($pagosRaw as $pg) {
            $monto = (int)($pg['monto_cents'] ?? 0);
            if ($monto <= 0) continue;
            $formaPagoP = trim((string)($pg['forma_pago'] ?? 'efectivo'));
            // Compatibilidad: el código viejo 'tarjetas' equivale a 'tarjeta'
            if ($formaPagoP === 'tarjetas') $formaPagoP = 'tarjeta';
            $tipoPago = \Perfushopping\Web\Repo\FormaPagoRepo::tipoDe($formaPagoP);
            $bancoId = null;
            $chequeId = null;
            $moneda = null;
            $montoMoneda = null;
            $cotiz = null;
            if ($tipoPago === 'moneda') {
                $moneda = strtoupper(trim((string)($pg['moneda'] ?? '')));
                $montoMoneda = (int)($pg['monto_moneda_cents'] ?? 0);
                $cotiz = (float)($pg['cotizacion'] ?? 0);
                if (!preg_match('/^[A-Z]{3}$/', (string)$moneda) || $montoMoneda <= 0 || $cotiz <= 0) {
                    throw new \InvalidArgumentException('Pago en moneda extranjera incompleto (monto y cotización).');
                }
                $monto = (int)round($montoMoneda * $cotiz / 100);
                if ($monto <= 0) continue;
            }
            if ($tipoPago === 'cheque' && !empty($pg['cheque'])) {
                $chq = $pg['cheque'];
                $bancoId = (int)($chq['banco_id'] ?? 0) ?: null;
                $bancoEmisor = trim((string)($chq['banco'] ?? ''));
                if (!$bancoEmisor && $bancoId) {
                    $bancoEmisor = $bancoNombres[$bancoId] ?? '';
                }
                $chequeRepo = new ChequeRepo();
                $chequeIdReusar = (int)($pg['cheque_id'] ?? 0) ?: null;
                if ($chequeIdReusar) {
                    $chequeRow = $chequeRepo->findById($chequeIdReusar);
                    if ($chequeRow && ($chequeRow['estado'] ?? '') === 'en_cartera') {
                        $upd = ['monto_cents = :m'];
                        $updPrm = [':m' => $monto, ':i' => $chequeIdReusar];
                        $campos = [
                            'numero_cheque' => trim((string)($chq['numero'] ?? '')),
                            'titular' => trim((string)($chq['titular'] ?? '')),
                            'cuit_titular' => trim((string)($chq['cuit'] ?? '')),
                            'fecha_vencimiento' => trim((string)($chq['vencimiento'] ?? '')),
                            'banco_emisor' => $bancoEmisor,
                        ];
                        foreach ($campos as $col => $val) {
                            if ($val !== '') {
                                $upd[] = $col . ' = :' . $col;
                                $updPrm[':' . $col] = $val;
                            }
                        }
                        $upd[] = 'updated_at = NOW()';
                        \Perfushopping\Web\Infra\Db::pdo()
                            ->prepare('UPDATE cheques SET ' . implode(', ', $upd) . ' WHERE id = :i LIMIT 1')
                            ->execute($updPrm);
                        $chequeId = $chequeIdReusar;
                        $bancoId = (int)($chequeRow['banco_id'] ?? $bancoId ?? 0) ?: $bancoId;
                    } else {
                        $chequeIdReusar = null;
                    }
                }
                if (!$chequeIdReusar) {
                    $chequeId = $chequeRepo->create([
                        'tipo' => 'tercero',
                        'estado' => 'en_cartera',
                        'banco_emisor' => $bancoEmisor,
                        'numero_cheque' => trim((string)($chq['numero'] ?? '')),
                        'titular' => trim((string)($chq['titular'] ?? '')),
                        'cuit_titular' => trim((string)($chq['cuit'] ?? '')),
                        'monto_cents' => $monto,
                        'fecha_emision' => (string)($input['fecha'] ?? date('Y-m-d')),
                        'fecha_vencimiento' => trim((string)($chq['vencimiento'] ?? '')) ?: null,
                        'concepto' => 'Factura — ' . $clienteNombre,
                    ], $adminUserId);
                    $chequeRepo->agregarMovimiento($chequeId, 'recibido', 'factura', 0, '', $adminUserId);
                }
            }
            $pagos[] = [
                'forma_pago' => $formaPagoP,
                'monto_cents' => $monto,
                'cheque_id' => $chequeId,
                'cupon_numero' => $tipoPago === 'tarjeta' ? trim((string)($pg['cupon_numero'] ?? '')) : null,
                'cupon_monto_cents' => $tipoPago === 'tarjeta' ? (int)($pg['cupon_monto_cents'] ?? 0) : null,
                'idplazo' => $tipoPago === 'ctacte' ? ((int)($pg['idplazo'] ?? 0) ?: null) : null,
                'banco_id' => $bancoId,
                'banco_cuenta_id' => $tipoPago === 'banco' ? ((int)($pg['banco_cuenta_id'] ?? $pg['banco_id'] ?? 0) ?: null) : null,
                'tarjeta_id' => $tipoPago === 'tarjeta' ? ((int)($pg['tarjeta_id'] ?? $pg['idtarje'] ?? 0) ?: null) : null,
                'equipo_id' => $tipoPago === 'tarjeta' ? ((int)($pg['equipo_id'] ?? $pg['idequipo'] ?? 0) ?: null) : null,
                'moneda' => $moneda,
                'monto_moneda_cents' => $montoMoneda,
                'cotizacion' => $cotiz,
            ];
        }

        if (!$pagos) {
            $pagos[] = [
                'forma_pago' => trim((string)($input['forma_pago'] ?? 'efectivo')),
                'monto_cents' => $fallbackMonto,
            ];
        }
        return $pagos;
    }

    /** Registra movimientos bancarios por cobros de transferencia/tarjeta. */
    private function registrarBancoMov(array $pagos, int $id, string $codigo, string $fecha, int $adminId): void
    {
        try {
            $bancoMovRepo = new \Perfushopping\Web\Repo\BancoMovimientoRepo();
            $cobroRepo = new CobroCuentaRepo();
            foreach ($pagos as $pg) {
                $fp = $pg['forma_pago'];
                $fpTipo = \Perfushopping\Web\Repo\FormaPagoRepo::tipoDe((string)$fp);
                $esBancoNuevo = $fpTipo === 'banco' && !in_array($fp, ['transferencia', 'mercadopago', 'debito', 'credito'], true);
                if ($fp === 'transferencia' || $esBancoNuevo) {
                    $bancoCuentaId = $pg['banco_cuenta_id'] ?? null;
                    if (!$bancoCuentaId) $bancoCuentaId = $cobroRepo->getTransferenciaCuentaId();
                    if ($bancoCuentaId) {
                        $bancoMovRepo->create((int)$bancoCuentaId, 'credito', 'factura', $id, 'Cobro factura ' . $codigo . ' (transferencia)', (int)$pg['monto_cents'], $fecha, $adminId);
                    }
                } elseif ($fp === 'tarjeta') {
                    $tarjetaId = $pg['tarjeta_id'] ?? null;
                    $bancoCuentaId = null;
                    if ($tarjetaId) $bancoCuentaId = $cobroRepo->getTarjetaCuentaId((int)$tarjetaId);
                    // fallback a cuenta seleccionada explícitamente
                    if (!$bancoCuentaId) $bancoCuentaId = $pg['banco_cuenta_id'] ?? null;
                    if ($bancoCuentaId) {
                        $bancoMovRepo->create((int)$bancoCuentaId, 'credito', 'factura', $id, 'Cobro factura ' . $codigo . ' (tarjeta ' . ($pg['tarjeta_id'] ?? '') . ')', (int)$pg['monto_cents'], $fecha, $adminId);
                    }
                }
            }
        } catch (\Throwable $e) { error_log('BancoMov factura: '.$e->getMessage()); }
    }

    /**
     * Envía la factura a ARCA (si está habilitado). Devuelve cae/codigo o error.
     * @return array{cae?:string,codigo?:string,error?:string}
     */
    private function autoEnviarArca(int $id, FacturaRepo $repo): array
    {
        $out = [];
        $arcaRepo = new ArcaRepo();
        if (!$arcaRepo->isHabilitado()) {
            return $out;
        }
        $wsfe = new \Perfushopping\Web\Service\AfipWsfe();
        $wsfe->setDebugTag('factura-' . $id);
        try {
            $facturaData = $repo->findById($id);
            $facturaItems = $repo->items($id);
            $wsfe->autenticar();
            $resultado = $wsfe->solicitarCAE($facturaData, $facturaItems);
            $arcaRepo->guardarComprobante($id, $resultado);
            $out['cae'] = (string)($resultado['cae'] ?? '');
            if (!empty($resultado['cae']) && !empty($resultado['codigo_emision']) && !empty($resultado['punto_venta_arca'])) {
                $pv = (int)$resultado['punto_venta_arca'];
                $nro = (int)$resultado['codigo_emision'];
                $nuevoCodigo = sprintf('%05d-%08d', $pv, $nro);
                $repo->actualizarCodigo($id, $nuevoCodigo);
                $out['codigo'] = $nuevoCodigo;
            }
        } catch (\Throwable $e) {
            $out['error'] = $e->getMessage();
            $arcaRepo->guardarComprobante($id, [
                'resultado' => 'R',
                'observaciones' => $e->getMessage(),
                'cae' => null,
                'cae_vto' => null,
                'codigo_emision' => null,
                'request_xml' => $wsfe->lastRequest(),
                'response_xml' => $wsfe->lastResponse(),
            ]);
        }
        return $out;
    }

    /** Motivo por el que la factura no se puede editar, o null si es editable. */
    private function motivoNoEditable(?array $f, int $id): ?string
    {
        if (!$f) {
            return 'Factura no encontrada.';
        }
        if (($f['estado'] ?? '') === 'anulada') {
            return 'No se puede editar una factura anulada.';
        }
        $cae = trim((string)($f['cae'] ?? ''));
        if ($cae !== '' && $cae !== 'NULL') {
            return 'La factura ya tiene CAE: no se puede editar.';
        }
        $arca = (new ArcaRepo())->getComprobante($id);
        if (($arca['resultado'] ?? '') === 'A') {
            return 'El comprobante ya fue autorizado por ARCA: no se puede editar.';
        }
        return null;
    }

    public function editar(array $params): void
    {
        $auth = new AdminAuthService();

        $id = (int)($params['id'] ?? 0);
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        $motivo = $this->motivoNoEditable($factura, $id);
        if ($motivo) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $motivo];
            Response::redirect($factura ? '/admin/facturas/' . $id : '/admin/facturas');
            return;
        }

        echo View::adminPage('admin/facturas/pos.php', $this->posCommon($auth) + [
            'editarId' => $id,
            'editarFactura' => $factura,
            'editarItems' => $repo->items($id),
            'editarPagos' => $repo->pagos($id),
            'pageTitle' => 'Editar factura ' . ($factura['codigo'] ?? ''),
        ]);
    }

    public function actualizar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $input = json_decode(file_get_contents('php://input'), true);
        Csrf::check($input['_csrf'] ?? null);

        $editarId = (int)($input['editar_id'] ?? 0);
        if ($editarId <= 0 || !$input || !isset($input['items']) || !is_array($input['items']) || count($input['items']) < 1) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.'], 422);
            return;
        }

        $repo = new FacturaRepo();
        $old = $repo->findById($editarId);
        $motivo = $this->motivoNoEditable($old, $editarId);
        if ($motivo) {
            Response::json(['ok' => false, 'error' => $motivo], 422);
            return;
        }

        $oldItems = $repo->items($editarId);
        $oldPagos = $repo->pagos($editarId);

        $tipo = (string)($input['tipo_comprobante'] ?? $old['tipo_comprobante'] ?? 'FACT-B');
        $cliente = $input['cliente'] ?? [];
        $clienteCuit = trim((string)($cliente['cuit'] ?? $old['cliente_cuit'] ?? ''));
        $clienteId = (int)($cliente['id'] ?? 0) ?: null;
        $clienteCondIva = trim((string)($cliente['condicion_iva'] ?? $old['cliente_condicion_iva'] ?? 'consumidor_final'));
        // Si el cliente está identificado pero el nombre llegó vacío o como CF, usar su razón real.
        $clienteNombre = $this->resolverNombreCliente(
            $repo,
            trim((string)($cliente['nombre'] ?? $old['cliente_nombre'] ?? '')),
            $clienteId,
            (int)($cliente['idclien'] ?? 0) ?: null
        );

        try {
            $itemsData = $this->normalizarItems($input['items']);
            $items = $itemsData['items'];
            $subtotal = $itemsData['subtotal'];
            $ivaTotal = $itemsData['iva'];
            $pagos = $this->normalizarPagos($input['pagos'] ?? [], $input, (int)$adminUser['id'], $clienteNombre, $subtotal + $ivaTotal);
        } catch (\InvalidArgumentException $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
            return;
        }

        $formaPago = $pagos[0]['forma_pago'] ?? 'efectivo';

        $entrega = $input['entrega'] ?? [];
        $entregaTipo = in_array($entrega['tipo'] ?? 'local', ['local','envio'], true) ? $entrega['tipo'] : 'local';
        $transporte = null;
        $envioEstado = null;
        $envioDireccion = null;
        $envioObs = null;
        if ($entregaTipo === 'envio') {
            $transporte = in_array($entrega['transporte'] ?? '', ['propio','delivery','correo_argentino'], true) ? $entrega['transporte'] : 'propio';
            $envioEstado = trim((string)($old['envio_estado'] ?? '')) ?: 'pendiente';
            $envioDireccion = trim((string)($entrega['direccion'] ?? ''));
            $envioObs = trim((string)($entrega['observacion'] ?? ''));
        }

        $clienteDirec = trim((string)($cliente['direc'] ?? ''));
        $clienteTele = trim((string)($cliente['tele'] ?? ''));
        $clienteMail = trim((string)($cliente['mail'] ?? ''));
        $clienteErpId = (int)($cliente['idclien'] ?? 0) ?: null;
        if (!$clienteErpId && $clienteId) {
            $erp = $repo->findClienteErpByWebId($clienteId);
            $clienteErpId = $erp ? (int)$erp['idclien'] : null;
        }
        if ($clienteId || $clienteErpId) {
            if ($clienteDirec === '') $clienteDirec = trim((string)($old['cliente_direc'] ?? ''));
            if ($clienteTele === '') $clienteTele = trim((string)($old['cliente_tele'] ?? ''));
            if ($clienteMail === '') $clienteMail = trim((string)($old['cliente_mail'] ?? ''));
            $erp = $clienteErpId ? $repo->findClienteByIdclien($clienteErpId) : $repo->findClienteErpByWebId($clienteId);
            if ($erp) {
                $clienteErpId = (int)$erp['idclien'];
                if (!$clienteDirec) $clienteDirec = trim((string)($erp['direc'] ?? ''));
                if (!$clienteTele) $clienteTele = trim((string)($erp['tele'] ?? ''));
                if (!$clienteMail) $clienteMail = trim((string)($erp['mail'] ?? ''));
            }
        }

        $vendedorId = (int)($input['vendedor_id'] ?? 0) ?: null;
        $notas = (string)($input['notas'] ?? '');

        $fecha = (string)($input['fecha'] ?? $old['fecha'] ?? date('Y-m-d'));
        $descuento = max(0, (int)($input['descuento_cents'] ?? 0));
$puntosRepo = new \Perfushopping\Web\Repo\PuntosRepo();
        $puntosUsados = max(0, (int)($input['puntos_usados'] ?? 0));
        $puntosUsadosCents = $puntosUsados * 100;
        $oldUse = $puntosRepo->usoEnFactura($editarId);
        if ($puntosUsados > 0) {
            $saldoPuntos = $puntosRepo->saldo($clienteErpId ?: 0);
            if ($oldUse && (int)($oldUse['idclien'] ?? 0) === (int)($clienteErpId ?: 0)) {
                $saldoPuntos += (int)($oldUse['puntos'] ?? 0);
            }

            if ($puntosUsados > $saldoPuntos) {
                Response::json(['ok' => false, 'error' => 'El cliente no tiene suficientes puntos para canjear.'], 422);
                return;
            }
            if ($puntosUsadosCents > ($subtotal + $ivaTotal - $descuento)) {
                $puntosUsados = (int)floor(($subtotal + $ivaTotal - $descuento) / 100);
                $puntosUsadosCents = $puntosUsados * 100;
            }
        }

        // Validación: no permitir guardar si el total pagado es menor al facturado
        $totalPagado = array_sum(array_column($pagos, 'monto_cents'));
        $totalFacturado = $subtotal + $ivaTotal - $descuento - $puntosUsadosCents;
        if ($totalPagado < $totalFacturado) {
            Response::json(['ok' => false, 'error' => 'El total abonado es menor al total facturado.'], 422);
            return;
        }

        $repo->actualizar($editarId, [
            'tipo_comprobante' => $tipo,
            'cliente_id' => $clienteId,
            'idclien' => $clienteErpId,
            'cliente_nombre' => $clienteNombre,
            'cliente_cuit' => $clienteCuit,
            'cliente_direc' => $clienteDirec,
            'cliente_tele' => $clienteTele,
            'cliente_mail' => $clienteMail,
            'cliente_condicion_iva' => $clienteCondIva,
            'fecha' => $fecha,
            'subtotal_cents' => $subtotal,
            'iva_cents' => $ivaTotal,
            'descuento_cents' => $descuento,
            'puntos_cents' => $puntosUsadosCents,
            'total_cents' => $subtotal + $ivaTotal - $descuento - $puntosUsadosCents,
            'forma_pago' => $formaPago,
            'entrega_tipo' => $entregaTipo,
            'transporte' => $transporte,
            'envio_estado' => $envioEstado,
            'envio_direccion' => $envioDireccion,
            'envio_observacion' => $envioObs,
            'notas' => $notas,
            'vendedor_id' => $vendedorId,
        ], $items, $pagos);

        $codigo = (string)($old['codigo'] ?? '');
        $pdo = \Perfushopping\Web\Infra\Db::pdo();
        $puntosService = new \Perfushopping\Web\Service\PuntosService();

        // Puntos de uso: ajustar la única fila de 'uso' de la factura a lo nuevo.
        if ($oldUse) {
            $oldUseCli = (int)($oldUse['idclien'] ?? 0);
            $oldUsePts = (int)($oldUse['puntos'] ?? 0);
            $mismoCli = $oldUseCli > 0 && $oldUseCli === (int)($clienteErpId ?: 0);
            if ($puntosUsados > 0 && $mismoCli && $oldUsePts > 0) {
                $pdo->prepare('UPDATE puntos_movimientos SET puntos = :p WHERE id = :i LIMIT 1')
                    ->execute([':p' => $puntosUsados, ':i' => (int)$oldUse['id']]);
                $delta = $puntosUsados - $oldUsePts;
                if ($delta !== 0) {
                    $pdo->prepare('UPDATE puntos_cuentas SET saldo_puntos = saldo_puntos - :d, total_usados = total_usados + :d, updated_at = NOW() WHERE idclien = :c LIMIT 1')
                        ->execute([':d' => $delta, ':c' => $oldUseCli]);
                }
            } else {
                $pdo->prepare('DELETE FROM puntos_movimientos WHERE id = :i LIMIT 1')
                    ->execute([':i' => (int)$oldUse['id']]);
                if ($oldUseCli > 0 && $oldUsePts > 0) {
                    $pdo->prepare('UPDATE puntos_cuentas SET saldo_puntos = saldo_puntos + :p, total_usados = total_usados - :p, updated_at = NOW() WHERE idclien = :c LIMIT 1')
                        ->execute([':p' => $oldUsePts, ':c' => $oldUseCli]);
                }
                $oldUse = null;
            }
        }
        if (!$oldUse && $puntosUsados > 0 && $clienteErpId) {
            $puntosService->usarEnFactura($clienteErpId, $puntosUsados, $editarId, (int)$adminUser['id']);
        }

        // Puntos de acumulación: borrar la vieja y acreditar con los nuevos datos.
        $stAcc = $pdo->prepare("SELECT idclien, puntos FROM puntos_movimientos WHERE factura_id = :f AND tipo = 'acumulacion' LIMIT 1");
        $stAcc->execute([':f' => $editarId]);
        $oldAcc = $stAcc->fetch();
        if ($oldAcc) {
            $pdo->prepare("DELETE FROM puntos_movimientos WHERE factura_id = :f AND tipo = 'acumulacion'")
                ->execute([':f' => $editarId]);
            $accCli = (int)($oldAcc['idclien'] ?? 0);
            $accPts = (int)($oldAcc['puntos'] ?? 0);
            if ($accCli > 0 && $accPts > 0) {
                $pdo->prepare('UPDATE puntos_cuentas SET saldo_puntos = saldo_puntos - :p, total_acumulado = total_acumulado - :p, updated_at = NOW() WHERE idclien = :c LIMIT 1')
                    ->execute([':p' => $accPts, ':c' => $accCli]);
            }
        }
        $facturaNow = $repo->findById($editarId);
        if ($facturaNow) {
            $puntosService->acreditarFactura($facturaNow, $items);
        }

        // Cuenta corriente: rearmar el débito con los nuevos importes.
        $ctaCte = new \Perfushopping\Web\Repo\CtaCteRepo();
        $ctaCte->anularMovimientosPorOrigen('factura', $editarId);
        if ($clienteId && $formaPago === 'cuenta_corriente') {
            $ctaCte->agregarMovimiento(
                'debito',
                'factura',
                $editarId,
                $clienteId,
                $clienteErpId,
                $subtotal + $ivaTotal - $descuento,
                'Factura ' . $codigo . ' — ' . $clienteNombre,
                (int)$adminUser['id']
            );
        }

        // Movimientos bancarios: borrar los viejos y registrar los nuevos.
        $pdo->prepare("DELETE FROM banco_movimientos WHERE origen = 'factura' AND origen_id = :i")
            ->execute([':i' => $editarId]);
        $this->registrarBancoMov($pagos, $editarId, $codigo, $fecha, (int)$adminUser['id']);

        // Stock: aplicar solo el delta viejo -> nuevo.
        $depoId = $auth->getDepositoId();
        if ($depoId > 0) {
            $stockRepo = new StockRepo();
            $qtyViejo = [];
            $mapProd = [];
            foreach ($oldItems as $it) {
                $pid = (int)($it['idprodu'] ?? 0);
                if ($pid <= 0) continue;
                $gid = (int)($it['idcodgusto'] ?? 0);
                $k = $pid . '-' . $gid;
                $qtyViejo[$k] = ($qtyViejo[$k] ?? 0) + (int)($it['qty'] ?? 0);
                $mapProd[$k] = [$pid, $gid ?: null];
            }
            $qtyNuevo = [];
            foreach ($items as $it) {
                $pid = (int)($it['idprodu'] ?? 0);
                if ($pid <= 0) continue;
                $gid = (int)($it['idcodgusto'] ?? 0);
                $k = $pid . '-' . $gid;
                $qtyNuevo[$k] = ($qtyNuevo[$k] ?? 0) + (int)($it['qty'] ?? 0);
                $mapProd[$k] = [$pid, $gid ?: null];
            }
            foreach (array_unique(array_merge(array_keys($qtyViejo), array_keys($qtyNuevo))) as $k) {
                $delta = ($qtyNuevo[$k] ?? 0) - ($qtyViejo[$k] ?? 0);
                if ($delta === 0 || !isset($mapProd[$k])) {
                    continue;
                }
                [$pid, $gid] = $mapProd[$k];
                if ($delta > 0) {
                    $stockRepo->registrarAjuste($pid, $gid, $depoId, 0, $delta, 'Edición Factura ' . $codigo, (int)$adminUser['id'], 'venta');
                } else {
                    $stockRepo->registrarAjuste($pid, $gid, 0, $depoId, -$delta, 'Edición Factura ' . $codigo, (int)$adminUser['id'], 'devolucion_venta');
                }
            }
        }

        // Cheques: eliminar los huérfanos que quedaron en cartera.
        $usados = [];
        foreach ($pagos as $pg) {
            if (!empty($pg['cheque_id'])) {
                $usados[(int)$pg['cheque_id']] = true;
            }
        }
        foreach ($oldPagos as $op) {
            $chqId = (int)($op['cheque_id'] ?? 0);
            if ($chqId <= 0 || isset($usados[$chqId])) {
                continue;
            }
            try {
                $chq = (new ChequeRepo())->findById($chqId);
                if (!$chq || ($chq['estado'] ?? '') !== 'en_cartera' || $this->chequeReferenciado($chqId)) {
                    continue;
                }
                $pdo->prepare('DELETE FROM cheque_movimientos WHERE cheque_id = :c')->execute([':c' => $chqId]);
                $pdo->prepare('DELETE FROM cheques WHERE id = :c LIMIT 1')->execute([':c' => $chqId]);
            } catch (\Throwable $e) {
                error_log('Cheque orphan factura: ' . $e->getMessage());
            }
        }

        // Auto-send to ARCA if enabled
        $arca = $this->autoEnviarArca($editarId, $repo);
        if (isset($arca['codigo'])) {
            $codigo = $arca['codigo'];
        }

        Response::json([
            'ok' => true,
            'id' => $editarId,
            'codigo' => $codigo,
            'arca' => !empty($arca['cae']) ? ['cae' => $arca['cae']] : (!empty($arca['error']) ? ['error' => $arca['error']] : null),
        ]);
    }

    private function chequeReferenciado(int $chequeId): bool
    {
        $pdo = \Perfushopping\Web\Infra\Db::pdo();
        foreach (['factura_pagos', 'orden_pago_pagos', 'recibo_pagos'] as $tabla) {
            try {
                $st = $pdo->prepare("SELECT COUNT(*) FROM {$tabla} WHERE cheque_id = :c");
                $st->execute([':c' => $chequeId]);
                if ((int)$st->fetchColumn() > 0) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }
        return false;
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $id = (int)($params['id'] ?? 0);
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/facturas');
        }
        $items = $repo->items($id);
        $pagos = $repo->pagos($id);

        $arcaComprobante = (new \Perfushopping\Web\Repo\ArcaRepo())->getComprobante($id);
        $qrUrl = null;
        if (($factura['cae'] ?? null) && $arcaComprobante && ($arcaComprobante['codigo_emision'] ?? null)) {
            try {
                $wsfe = new \Perfushopping\Web\Service\AfipWsfe();
                $qrUrl = $wsfe->getUrlQr($factura, (int)$arcaComprobante['codigo_emision'], $factura['cae']);
            } catch (\Throwable $e) {
                error_log('QR error: ' . $e->getMessage());
            }
        }

        echo View::adminPage('admin/facturas/detail.php', [
            'adminUser' => $adminUser,
            'factura' => $factura,
            'items' => $items,
            'pagos' => $pagos,
            'arcaComprobante' => $arcaComprobante,
            'qrUrl' => $qrUrl,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Factura ' . ($factura['codigo'] ?? ''),
        ]);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');

        if (!in_array($estado, ['pendiente', 'emitida', 'anulada'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/facturas');
        }

        $repo = new FacturaRepo();
        $f = $repo->findById($id);
        if (!$f) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/facturas');
        }

        $oldEstado = $f['estado'] ?? '';

        $repo->updateEstado($id, $estado);

        // Restore stock if factura is anulated
        if ($estado === 'anulada' && $oldEstado !== 'anulada') {
            $depoId = $auth->getDepositoId();
            if ($depoId > 0) {
                $stockRepo = new StockRepo();
                $facturaItems = $repo->items($id);
                foreach ($facturaItems as $it) {
                    $idprodu = (int)($it['idprodu'] ?? 0);
                    $idcodgusto = (int)($it['idcodgusto'] ?? 0) ?: null;
                    $qty = (int)($it['qty'] ?? 0);
                    if ($idprodu) {
                        $stockRepo->registrarAjuste($idprodu, $idcodgusto, 0, $depoId, $qty, 'Anulación Factura ' . ($f['codigo'] ?? ''), (int)$adminUser['id'], 'devolucion_venta');
                    }
                }
            }
        }

        // Reverse ctacte movement if factura is anulated and was cta.cte.
        if ($estado === 'anulada' && $oldEstado !== 'anulada' && ($f['forma_pago'] ?? '') === 'cuenta_corriente' && $f['cliente_id']) {
            $ctaCte = new \Perfushopping\Web\Repo\CtaCteRepo();
            $ctaCte->agregarMovimiento(
                'credito',
                'factura',
                $id,
                (int)$f['cliente_id'],
                (int)($f['idclien'] ?? 0) ?: null,
                (int)($f['total_cents'] ?? 0),
                'Anulación Factura ' . ($f['codigo'] ?? ''),
                (int)$adminUser['id']
            );
        }

        // Reverse loyalty points if factura is anulated (accrual removed + redeemed points returned).
        if ($estado === 'anulada' && $oldEstado !== 'anulada') {
            $puntosService = new \Perfushopping\Web\Service\PuntosService();
            $puntosService->revertirFactura($id);
            $puntosService->revertirUsoFactura($id);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/facturas/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/facturas');

        (new FacturaRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Factura eliminada.'];
        Response::redirect('/admin/facturas');
    }

    private function bancos(): array
    {
        $st = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT idban, nombanc, numbanc FROM bancos ORDER BY nombanc ASC');
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    private function bancosCuentas(): array
    {
        try {
            $st = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT id, banco, numero_cuenta, cbu FROM banco_cuentas WHERE activo=1 ORDER BY banco ASC');
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    private function tarjetas(): array
    {
        try {
            $st = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT idtarje, nomtar FROM tarjeta ORDER BY nomtar ASC');
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    private function equipos(int $sucursalId = 0): array
    {
        try {
            $pdo = \Perfushopping\Web\Infra\Db::pdo();
            if ($sucursalId > 0) {
                $suc = (new \Perfushopping\Web\Repo\SucursalRepo())->findById($sucursalId);
                $idsucemp = $suc ? (int)($suc['idsucemp'] ?? 0) : 0;
                if ($idsucemp > 0) {
                    $st = $pdo->prepare('SELECT idequipo, empresa, idsucemp FROM equipotar WHERE idsucemp=:s ORDER BY empresa ASC, idequipo ASC');
                    $st->execute([':s' => $idsucemp]);
                    $rows = $st->fetchAll();
                    if ($rows) return $rows;
                }
            }
            $st = $pdo->query('SELECT idequipo, empresa, idsucemp FROM equipotar ORDER BY empresa ASC, idequipo ASC LIMIT 100');
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    private function plazos(): array
    {
        $st = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT idplazo, dias, cuotas, descripcion, pricuo, tipo FROM plazopago ORDER BY cuotas ASC, dias ASC, idplazo ASC');
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function diagnosticoStock(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');

        header('Content-Type: text/plain; charset=utf-8');

        $sucId = $auth->getSucursalId();
        $depoId = $auth->getDepositoId();
        echo "sucursal sesion: {$sucId} | deposito sesion: {$depoId} | turno: " . $auth->getTurno() . " | PV: " . $auth->getPuntoVenta() . "\n";
        echo ($depoId > 0 ? "OK: al facturar SE descuenta stock del deposito {$depoId}\n" : "PROBLEMA: deposito 0, al facturar NO se descuenta stock\n");
        echo "\n";

        $pdo = \Perfushopping\Web\Infra\Db::pdo();
        try {
            $s = (new \Perfushopping\Web\Repo\SucursalRepo())->findById($sucId);
            echo "sucursal iddepo: " . var_export($s['iddepo'] ?? 'FALTA COLUMNA', true) . "\n\n";
        } catch (\Throwable $e) {
            echo "sucursal: ERROR " . $e->getMessage() . "\n\n";
        }

        foreach (['stock', 'stockcab', 'stockdet'] as $t) {
            try {
                $n = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
                echo "tabla {$t}: existe, filas = {$n}\n";
            } catch (\Throwable $e) {
                echo "tabla {$t}: NO EXISTE\n";
            }
        }
        echo "\n";

        try {
            $facts = $pdo->query('SELECT id, codigo, fecha, created_at, punto_venta FROM facturas ORDER BY id DESC LIMIT 5')->fetchAll();
            foreach ($facts as $f) {
                echo "factura id={$f['id']} cod={$f['codigo']} pv={$f['punto_venta']}\n";
                $sti = $pdo->prepare('SELECT idprodu, idcodgusto, producto, qty FROM factura_items WHERE factura_id = :f');
                $sti->execute([':f' => $f['id']]);
                foreach ($sti->fetchAll() as $it) {
                    echo "  item prod={$it['idprodu']} gusto=" . ($it['idcodgusto'] ?? 'NULL') . " qty={$it['qty']} " . mb_substr((string)$it['producto'], 0, 30) . "\n";
                }
            }
        } catch (\Throwable $e) {
            echo "facturas: ERROR " . $e->getMessage() . "\n";
        }
        echo "\n";

        try {
            $cabs = $pdo->query("SELECT id, fecha, notas, tipo_movimiento FROM stockcab ORDER BY id DESC LIMIT 5")->fetchAll();
            echo "--- ultimos stockcab ---\n";
            foreach ($cabs as $c) {
                echo "  id={$c['id']} fecha={$c['fecha']} tipo={$c['tipo_movimiento']} notas=" . mb_substr((string)$c['notas'], 0, 60) . "\n";
            }
        } catch (\Throwable $e) {
            echo "stockcab: ERROR " . $e->getMessage() . "\n";
        }
        exit;
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        $iddepo = null;
        $sucId = $auth->getSucursalId();
        if ($sucId > 0) {
            $suc = (new \Perfushopping\Web\Repo\SucursalRepo())->findById($sucId);
            $iddepo = $suc ? (int)($suc['iddepo'] ?? 0) : null;
        }
        $results = (new FacturaRepo())->searchProducts($q, 50, $iddepo ?: null);

        Response::json($results);
    }

    /** Estados de pedidos web que se pueden importar a factura. @return array<int,string> */
    private function pedidosImportables(): array
    {
        return ['paid', 'pending_transfer', 'transfer_reported', 'preparing', 'prepared', 'shipped'];
    }

    /**
     * Arma los datos para precargar el POS desde un pedido web.
     * @return array{pedido:array,items:array,cliente:?array,envio:array,pago:array,descuento_pct:float}|null
     */
    private function pedidoPrefill(int $pedidoId): ?array
    {
        $orderRepo = new \Perfushopping\Web\Repo\OrderRepo();
        $order = $orderRepo->find($pedidoId);
        if (!$order || !in_array((string)($order['status'] ?? ''), $this->pedidosImportables(), true)) {
            return null;
        }

        $items = [];
        foreach ($orderRepo->itemsByOrderIds([$pedidoId]) as $oi) {
            $items[] = [
                'idprodu' => (int)($oi['idprodu'] ?? 0),
                'idcodgusto' => (int)($oi['idcodgusto'] ?? 0),
                'producto' => (string)($oi['product_name'] ?? ''),
                'variedad' => (string)($oi['variant_name'] ?? ''),
                'qty' => max(1, (int)($oi['qty'] ?? 1)),
                'unit_price_cents' => max(0, (int)($oi['unit_net_cents'] ?? 0)),
                'iva_rate' => (float)($oi['iva_rate'] ?? 21),
            ];
        }

        $method = (string)($order['shipping_method'] ?? '');
        $methodLabels = ['correo_argentino' => 'Correo Argentino', 'local_delivery' => 'Delivery', 'local' => 'Retiro en local'];
        $shipCost = max(0, (int)($order['shipping_cost_cents'] ?? 0));
        if ($shipCost > 0) {
            $items[] = [
                'idprodu' => 0,
                'idcodgusto' => 0,
                'producto' => 'Envío (' . ($methodLabels[$method] ?? $method ?: 'web') . ')',
                'variedad' => '',
                'qty' => 1,
                'unit_price_cents' => $shipCost,
                'iva_rate' => 21,
            ];
        }

        $cliente = $this->clientePorEmail((string)($order['email'] ?? ''));
        if (!$cliente) {
            $cliente = [
                'id' => 0,
                'idclien' => 0,
                'name' => (string)($order['ship_name'] ?? ''),
                'cuit' => '',
                'condicion_iva' => 'consumidor_final',
            ];
        }

        if ($method === 'correo_argentino') {
            $envio = ['tipo' => 'envio', 'transporte' => 'correo_argentino'];
        } elseif ($method === 'local_delivery') {
            $envio = ['tipo' => 'envio', 'transporte' => 'delivery'];
        } else {
            $envio = ['tipo' => 'local', 'transporte' => 'propio'];
        }
        $direccion = trim(trim((string)($order['ship_address'] ?? '')) . ', ' . trim((string)($order['ship_city'] ?? '')) . ' (' . trim((string)($order['ship_postal_code'] ?? '')) . ')', ', ()');
        $envio['direccion'] = $direccion;
        $envio['obs'] = trim((string)($order['shipping_detail'] ?? ''));

        $status = (string)($order['status'] ?? '');
        $forma = ($status === 'pending_transfer' || $status === 'transfer_reported') ? 'transferencia' : 'mercadopago';
        try {
            $st = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT status FROM mp_payments WHERE order_id = :o LIMIT 1');
            $st->execute([':o' => $pedidoId]);
            if (strtolower((string)$st->fetchColumn()) === 'approved') {
                $forma = 'mercadopago';
            }
        } catch (\Throwable $e) {
        }

        return [
            'pedido' => ['id' => $pedidoId, 'codigo' => (string)($order['order_code'] ?? '')],
            'items' => $items,
            'cliente' => $cliente,
            'envio' => $envio,
            'pago' => ['forma' => $forma, 'monto_cents' => max(0, (int)($order['total_cents'] ?? 0))],
            'descuento_pct' => (float)($order['discount_percent'] ?? 0),
        ];
    }

    /** @return array{id:int,idclien:int,name:string,cuit:string,condicion_iva:string}|null */
    private function clientePorEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        try {
            $st = \Perfushopping\Web\Infra\Db::pdo()->prepare("SELECT idclien, razon AS name, cuit, COALESCE(condicion_iva, 'consumidor_final') AS condicion_iva FROM clientes WHERE mail = :m LIMIT 1");
            $st->execute([':m' => $email]);
            $r = $st->fetch();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$r) {
            return null;
        }
        return [
            'id' => 0,
            'idclien' => (int)($r['idclien'] ?? 0),
            'name' => (string)($r['name'] ?? ''),
            'cuit' => (string)($r['cuit'] ?? ''),
            'condicion_iva' => (string)($r['condicion_iva'] ?? 'consumidor_final'),
        ];
    }

    public function searchPedidos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        (new FacturaRepo())->ensureOrderColumn();
        $rows = (new \Perfushopping\Web\Repo\OrderRepo())->searchImportables($q, 20);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'codigo' => (string)($r['order_code'] ?? ''),
                'cliente' => (string)($r['ship_name'] ?? ''),
                'email' => (string)($r['email'] ?? ''),
                'total' => (int)($r['total_cents'] ?? 0),
                'estado' => (string)($r['status'] ?? ''),
                'fecha' => (string)($r['created_at'] ?? ''),
                'facturado' => (int)($r['items_facturados'] ?? 0) > 0,
            ];
        }
        Response::json($out);
    }

    public function crearCliente(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $razon = trim((string)($_POST['razon'] ?? ''));
        if ($razon === '') {
            Response::json(['ok' => false, 'error' => 'El nombre del cliente es obligatorio.'], 422);
            return;
        }

        $cuit = trim((string)($_POST['cuit'] ?? ''));
        $force = (string)($_POST['force'] ?? '') === '1';
        if (!$force) {
            $duplicados = (new FacturaRepo())->findDuplicadosCliente($razon, $cuit);
            if ($duplicados) {
                Response::json(['ok' => false, 'confirm' => true, 'duplicados' => $duplicados]);
                return;
            }
        }

        $cliente = (new FacturaRepo())->crearClientePos([
            'razon' => $razon,
            'cuit' => $cuit,
            'direc' => trim((string)($_POST['direc'] ?? '')),
            'localidad' => trim((string)($_POST['localidad'] ?? '')),
            'tele' => trim((string)($_POST['tele'] ?? '')),
            'mail' => trim((string)($_POST['mail'] ?? '')),
            'condicion_iva' => trim((string)($_POST['condicion_iva'] ?? 'consumidor_final')),
            'categoria' => trim((string)($_POST['categoria'] ?? 'minorista')),
            'precio_mayorista' => trim((string)($_POST['precio_mayorista'] ?? '')),
            'especialidad' => trim((string)($_POST['especialidad'] ?? '')),
        ]);

        if (!$cliente) {
            Response::json(['ok' => false, 'error' => 'Error al crear el cliente.'], 500);
            return;
        }

        Response::json(['ok' => true, 'cliente' => $cliente]);
    }

    public function searchClientes(array $params): void
    {        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new FacturaRepo();
        $results = $repo->findClienteWeb($q);

        if (empty($results)) {
            $cuitsToTry = [];
            if (preg_match('/^\d{11}$/', $q)) {
                $cuitsToTry[] = $q;
            } elseif (preg_match('/^\d{7,8}$/', $q)) {
                $cuitsToTry = AfipPadronService::dniToCuits($q);
            }
            foreach ($cuitsToTry as $cuit) {
                try {
                    $arcaRepo = new ArcaRepo();
                    if ($arcaRepo->isHabilitado()) {
                        $padron = new AfipPadronService();
                        $persona = $padron->consultar($cuit);
                        if ($persona) {
                            $cliente = $repo->upsertClienteArca($persona);
                            if ($cliente) {
                                $results = [$cliente];
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('ARCA padron error: ' . $e->getMessage());
                }
            }
        }

        Response::json($results);
    }

    public function searchRemitos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new FacturaRepo())->findRemitosDisponibles($q);

        Response::json($results);
    }

    public function searchPresupuestos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new FacturaRepo())->findPresupuestosDisponibles($q);

        Response::json($results);
    }

    public function print(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        $id = (int)($params['id'] ?? 0);
        $formato = (string)($_GET['formato'] ?? '80mm');
        if (!in_array($formato, ['a4', '80mm', '58mm'], true)) $formato = '80mm';

        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/facturas');
        }
        $items = $repo->items($id);
        $pagos = $repo->pagos($id);

        $qrUrl = null;
        if ($factura['cae'] ?? null) {
            $arcaComprobante = (new \Perfushopping\Web\Repo\ArcaRepo())->getComprobante($id);
            if ($arcaComprobante && ($arcaComprobante['codigo_emision'] ?? null)) {
                try {
                    $wsfe = new \Perfushopping\Web\Service\AfipWsfe();
                    $qrUrl = $wsfe->getUrlQr($factura, (int)$arcaComprobante['codigo_emision'], $factura['cae']);
                } catch (\Throwable $e) {
                    error_log('QR error: ' . $e->getMessage());
                }
            }
        }

        $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();

        $sucursalRepo = new \Perfushopping\Web\Repo\SucursalRepo();
        $sucursal = null;
        if (!empty($factura['sucursal_id'])) {
            $sucursal = $sucursalRepo->findById((int)$factura['sucursal_id']);
        }
        if (!$sucursal && !empty($factura['punto_venta'])) {
            $sucursal = $sucursalRepo->findByPuntoVenta((int)$factura['punto_venta']);
        }

        echo View::render('admin/facturas/print.php', [
            'factura' => $factura,
            'items' => $items,
            'pagos' => $pagos,
            'formato' => $formato,
            'qrUrl' => $qrUrl,
            'empresa' => $empresa,
            'sucursal' => $sucursal,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
            'puntosObtenidos' => (new \Perfushopping\Web\Repo\PuntosRepo())->acumulacionFactura($id),
            'puntosTotales' => (new \Perfushopping\Web\Repo\PuntosRepo())->saldo((int)($factura['idclien'] ?? 0)),
        ]);
    }

    public function puntos(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');

        $id = (int)($params['id'] ?? 0);
        $factura = (new FacturaRepo())->findById($id);
        if (!$factura) {
            Response::json(['obtenidos' => 0, 'totales' => 0]);
            return;
        }

        $p = (new \Perfushopping\Web\Repo\PuntosRepo())->puntosDeFactura($id, (int)($factura['idclien'] ?? 0));
        Response::json($p);
    }

    public function pdf(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');

        $id = (int)($params['id'] ?? 0);
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/facturas');
        }

        $items = $repo->items($id);
        $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
        $money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');

        $lines = [];
        $lines[] = ['text' => (string)($empresa['razon_emp'] ?? 'PERFUSHOPPING S.R.L.'), 'bold' => true, 'size' => 14];
        $lines[] = ['text' => 'Comprobante ' . (string)($factura['codigo'] ?? '') . ' - ' . (string)($factura['tipo_comprobante'] ?? ''), 'bold' => true, 'size' => 12];
        $lines[] = ['text' => 'Fecha: ' . date('d/m/Y', strtotime((string)($factura['fecha'] ?? '')))];
        $lines[] = ['text' => 'Cliente: ' . (string)($factura['cliente_nombre'] ?? '')];
        if (!empty($factura['cliente_cuit'])) {
            $lines[] = ['text' => 'CUIT/CUIL: ' . (string)$factura['cliente_cuit']];
        }
        $lines[] = ['text' => 'Condicion IVA: ' . (string)($factura['cliente_condicion_iva'] ?? '')];
        $lines[] = ['text' => ''];
        $lines[] = ['text' => 'Productos', 'bold' => true, 'size' => 11];
        foreach ($items as $it) {
            $lines[] = ['text' => sprintf('%s x%s  $%s', (string)($it['producto'] ?? ''), (float)($it['qty'] ?? 0), $money((int)($it['total_cents'] ?? 0)))];
        }
        $lines[] = ['text' => ''];
        $lines[] = ['text' => 'Subtotal: $' . $money((int)($factura['subtotal_cents'] ?? 0))];
        $lines[] = ['text' => 'IVA: $' . $money((int)($factura['iva_cents'] ?? 0))];
        if ((int)($factura['descuento_cents'] ?? 0) > 0) {
            $lines[] = ['text' => 'Descuento: $' . $money((int)$factura['descuento_cents'])];
        }
        $lines[] = ['text' => 'TOTAL: $' . $money((int)($factura['total_cents'] ?? 0)), 'bold' => true, 'size' => 13];
        $lines[] = ['text' => 'Estado: ' . (string)($factura['estado'] ?? '')];
        if (!empty($factura['cae'])) {
            $lines[] = ['text' => 'CAE: ' . (string)$factura['cae']];
        }
        $lines[] = ['text' => ''];
        $lines[] = ['text' => 'www.perfushopping.com.ar'];

        ob_start();
        ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="comprobante-' . preg_replace('/[^A-Za-z0-9-]/', '', (string)($factura['codigo'] ?? (string)$id)) . '.pdf"');
        echo \Perfushopping\Web\Support\Pdf::render($lines);
    }

    public function sendEmail(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($params['id'] ?? 0);
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura) {
            Response::json(['ok' => false, 'error' => 'Factura no encontrada.']);
            return;
        }

        $to = trim((string)($factura['cliente_mail'] ?? ''));
        if ($to === '') {
            Response::json(['ok' => false, 'error' => 'El cliente no tiene email registrado.']);
            return;
        }

        $items = $repo->items($id);

        $empresaRepo = new \Perfushopping\Web\Repo\EmpresaRepo();
        $empresa = $empresaRepo->getDefault();
        $empresaNombre = htmlspecialchars($empresa['nomemp'] ?? 'Perfushopping');
        $empresaWeb = htmlspecialchars($empresa['web'] ?? 'www.perfushopping.com');
        $empresaLogoUrl = !empty($empresa['logo']) ? \Perfushopping\Web\Support\Format::uploadUrl((string)$empresa['logo']) : '/assets/brand/logo-header.png';
        $baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $tipoLabels = ['FACT-A'=>'Factura A','FACT-B'=>'Factura B','FACT-C'=>'Factura C','NC'=>'Nota de Crédito','ND'=>'Nota de Débito'];
        $tipo = $tipoLabels[$factura['tipo_comprobante'] ?? 'FACT-B'] ?? $factura['tipo_comprobante'] ?? '';

        $rows = '';
        foreach ($items as $it) {
            $rows .= '<tr>';
            $rows .= '<td>' . htmlspecialchars((string)($it['producto'] ?? '')) . ($it['variedad'] ? ' (' . htmlspecialchars($it['variedad']) . ')' : '') . '</td>';
            $rows .= '<td style="text-align:center">' . (int)($it['qty'] ?? 0) . '</td>';
            $rows .= '<td style="text-align:right">' . Format::moneyFromCents((int)($it['unit_price_cents'] ?? 0)) . '</td>';
            $rows .= '<td style="text-align:right">' . Format::moneyFromCents((int)($it['total_cents'] ?? 0)) . '</td>';
            $rows .= '</tr>';
        }

        $attachmentHtml = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($factura['codigo'] ?? '') . '</title>';
        $attachmentHtml .= '<style>body{font-family:Arial,sans-serif;font-size:14px;color:#333;max-width:600px;margin:0 auto;padding:20px}';
        $attachmentHtml .= 'table{width:100%;border-collapse:collapse;margin:12px 0}th,td{padding:6px 8px;text-align:left;border-bottom:1px solid #ddd}';
        $attachmentHtml .= 'th{background:#f5f5f5}.total{font-size:18px;font-weight:bold;border-top:2px solid #333;padding-top:8px;margin-top:4px}';
        $attachmentHtml .= '.footer{margin-top:20px;font-size:12px;color:#888;text-align:center}';
        $attachmentHtml .= 'h1{font-size:22px;margin:0 0 4px}.logo{max-width:140px;margin-bottom:8px}</style></head><body>';
        $attachmentHtml .= '<div style="text-align:center"><img class="logo" src="' . $baseUrl . $empresaLogoUrl . '" alt="' . $empresaNombre . '" /></div>';
        $attachmentHtml .= '<h1 style="text-align:center">' . htmlspecialchars($tipo) . '</h1>';
        $attachmentHtml .= '<p style="text-align:center;color:#666">Código: <strong>' . htmlspecialchars($factura['codigo'] ?? '') . '</strong> — Fecha: ' . htmlspecialchars($factura['fecha'] ?? '') . '</p>';
        if ($factura['cae'] ?? '') {
            $attachmentHtml .= '<p style="text-align:center;color:#666">CAE: <strong>' . htmlspecialchars($factura['cae']) . '</strong> — Vto: ' . htmlspecialchars($factura['cae_vto'] ?? '') . '</p>';
        }
        $attachmentHtml .= '<hr style="border:none;border-top:1px solid #ddd" />';
        $attachmentHtml .= '<p><strong>Cliente:</strong> ' . htmlspecialchars($factura['cliente_nombre'] ?? 'Consumidor Final') . '<br/>';
        if ($factura['cliente_cuit'] ?? '') $attachmentHtml .= '<strong>CUIT:</strong> ' . htmlspecialchars($factura['cliente_cuit']) . '<br/>';
        $attachmentHtml .= '<strong>Cond. IVA:</strong> ' . htmlspecialchars($factura['cliente_condicion_iva'] ?? 'Consumidor Final') . '</p>';
        $attachmentHtml .= '<hr style="border:none;border-top:1px solid #ddd" />';
        $attachmentHtml .= '<table><thead><tr><th>Producto</th><th>Cant</th><th style="text-align:right">Precio</th><th style="text-align:right">Total</th></tr></thead><tbody>';
        $attachmentHtml .= $rows;
        $attachmentHtml .= '</tbody></table>';
        $attachmentHtml .= '<div style="text-align:right"><p>Subtotal: ' . Format::moneyRoundedFromCents((int)($factura['subtotal_cents'] ?? 0)) . '</p>';
        $attachmentHtml .= '<p>IVA: ' . Format::moneyRoundedFromCents((int)($factura['iva_cents'] ?? 0)) . '</p>';
        $desc = (int)($factura['descuento_cents'] ?? 0);
        if ($desc > 0) $attachmentHtml .= '<p style="color:#dc3545">Descuento: -' . Format::moneyRoundedFromCents($desc) . '</p>';
        $attachmentHtml .= '<p class="total">TOTAL: ' . Format::moneyRoundedFromCents((int)($factura['total_cents'] ?? 0)) . '</p></div>';
        $puntosObtenidos = (new \Perfushopping\Web\Repo\PuntosRepo())->acumulacionFactura($id);
        $puntosTotales = (new \Perfushopping\Web\Repo\PuntosRepo())->saldo((int)($factura['idclien'] ?? 0));
        if ($puntosObtenidos > 0) {
            $attachmentHtml .= '<p style="color:#b8860b"><strong>Puntos sumados en esta compra:</strong> ' . $puntosObtenidos . ' pts<br/>';
            if ($puntosTotales > 0) {
                $attachmentHtml .= '<strong>Total de puntos acumulados:</strong> ' . $puntosTotales . ' pts<br/>';
            }
            $attachmentHtml .= '</p><hr style="border:none;border-top:1px solid #ddd" />';
        }
        $attachmentHtml .= '<hr style="border:none;border-top:1px solid #ddd" />';
        $attachmentHtml .= '<div class="footer"><p>Gracias por su compra</p><p>' . $empresaNombre . ' — ' . $empresaWeb . '</p></div>';
        $attachmentHtml .= '<p style="text-align:center;font-size:11px;color:#999">Versión imprimible: <a href="' . $baseUrl . '/admin/facturas/imprimir/' . $id . '">' . $baseUrl . '/admin/facturas/imprimir/' . $id . '</a></p>';
        $attachmentHtml .= '</body></html>';

        $attachments = [];
        $attachments[] = [
            'name' => 'factura_' . ($factura['codigo'] ?? $id) . '.html',
            'content' => $attachmentHtml,
            'mime' => 'text/html',
        ];

        $emailBody = '<p>Estimado/a,</p><p>Adjuntamos la factura <strong>' . htmlspecialchars($factura['codigo'] ?? '') . '</strong>.</p>';
        $emailBody .= '<p>Monto total: <strong>' . Format::moneyFromCents((int)($factura['total_cents'] ?? 0)) . '</strong></p>';
        if ($factura['cae'] ?? '') {
            $emailBody .= '<p>CAE: <strong>' . htmlspecialchars($factura['cae']) . '</strong> — Vto: ' . htmlspecialchars($factura['cae_vto'] ?? '') . '</p>';
        }
        $emailBody .= '<p>Podés descargar la factura desde el adjunto o ver la versión imprimible aquí: <a href="' . $baseUrl . '/admin/facturas/imprimir/' . $id . '">' . $baseUrl . '/admin/facturas/imprimir/' . $id . '</a></p>';
        $emailBody .= '<p>Gracias por su compra.<br/>' . $empresaNombre . '</p>';

        try {
            (new SmtpMailer())->send($to, 'Factura ' . ($factura['codigo'] ?? ''), $emailBody, '', $attachments);
            Response::json(['ok' => true, 'message' => 'Factura enviada a ' . $to]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => 'Error al enviar: ' . $e->getMessage()]);
        }
    }
}
