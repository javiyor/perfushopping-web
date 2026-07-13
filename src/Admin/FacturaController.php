<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class FacturaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $list = (new FacturaRepo())->search($q, $estado);

        echo View::adminPage('admin/facturas/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Facturación',
        ]);
    }

    public function pos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new FacturaRepo();
        $remitoId = (int)($_GET['remito_id'] ?? 0);
        $remitoItems = [];

        $vendedoresSesion = $auth->getVendedores();
        $vendedores = [];
        if ($vendedoresSesion) {
            $st = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT id, nombre, username, rol FROM admin_users WHERE id IN (' . implode(',', array_fill(0, count($vendedoresSesion), '?')) . ') AND activo = 1');
            $st->execute(array_values($vendedoresSesion));
            $vendedores = $st->fetchAll();
        }

        if ($remitoId > 0) {
            $remito = (new \Perfushopping\Web\Repo\RemitoRepo())->findById($remitoId);
            if ($remito && $remito['estado'] === 'completado') {
                $remitoItems = $repo->itemsByRemito($remitoId);
            }
        }

        echo View::adminPage('admin/facturas/pos.php', [
            'adminUser' => $adminUser,
            'remitoId' => $remitoId,
            'remitoItems' => $remitoItems,
            'vendedores' => $vendedores,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Nueva factura',
        ]);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $input = json_decode(file_get_contents('php://input'), true);
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
        $clienteNombre = trim((string)($cliente['nombre'] ?? 'Consumidor Final'));
        $clienteCuit = trim((string)($cliente['cuit'] ?? ''));
        $clienteId = (int)($cliente['id'] ?? 0) ?: null;
        $clienteCondIva = trim((string)($cliente['condicion_iva'] ?? 'consumidor_final'));

        $items = [];
        $subtotal = 0;
        $ivaTotal = 0;
        foreach ($input['items'] as $it) {
            $qty = max(1, (int)($it['qty'] ?? 1));
            $unitPrice = max(0, (int)($it['unit_price_cents'] ?? 0));
            $ivaRate = (float)($it['iva_rate'] ?? 21);
            $lineTotal = $qty * $unitPrice;
            $lineIva = $ivaRate > 0 ? (int)round($lineTotal - ($lineTotal / (1 + $ivaRate / 100))) : 0;
            $items[] = [
                'idprodu' => (int)($it['idprodu'] ?? 0) ?: null,
                'idcodgusto' => (int)($it['idcodgusto'] ?? 0) ?: null,
                'producto' => trim((string)($it['producto'] ?? '')),
                'variedad' => trim((string)($it['variedad'] ?? '')),
                'qty' => $qty,
                'unit_price_cents' => $unitPrice,
                'iva_rate' => $ivaRate,
                'iva_cents' => $lineIva,
                'total_cents' => $lineTotal,
            ];
            $subtotal += $lineTotal - $lineIva;
            $ivaTotal += $lineIva;
        }

        $pagosRaw = $input['pagos'] ?? [];
        $pagos = [];
        foreach ($pagosRaw as $pg) {
            $monto = (int)($pg['monto_cents'] ?? 0);
            if ($monto <= 0) continue;
            $formaPagoP = trim((string)($pg['forma_pago'] ?? 'efectivo'));
            $chequeId = null;
            if ($formaPagoP === 'cheque' && !empty($pg['cheque'])) {
                $chq = $pg['cheque'];
                $chequeRepo = new ChequeRepo();
                $chequeId = $chequeRepo->create([
                    'tipo' => 'tercero',
                    'estado' => 'en_cartera',
                    'banco_emisor' => trim((string)($chq['banco'] ?? '')),
                    'numero_cheque' => trim((string)($chq['numero'] ?? '')),
                    'titular' => trim((string)($chq['titular'] ?? '')),
                    'cuit_titular' => trim((string)($chq['cuit'] ?? '')),
                    'monto_cents' => $monto,
                    'fecha_emision' => (string)($input['fecha'] ?? date('Y-m-d')),
                    'fecha_vencimiento' => trim((string)($chq['vencimiento'] ?? '')) ?: null,
                    'concepto' => 'Factura — ' . $clienteNombre,
                ], (int)$adminUser['id']);
                $chequeRepo->agregarMovimiento($chequeId, 'recibido', 'factura', 0, '', (int)$adminUser['id']);
            }
            $pagos[] = [
                'forma_pago' => $formaPagoP,
                'monto_cents' => $monto,
                'cheque_id' => $chequeId,
            ];
        }

        if (!$pagos) {
            $pagos[] = [
                'forma_pago' => trim((string)($input['forma_pago'] ?? 'efectivo')),
                'monto_cents' => $subtotal + $ivaTotal,
            ];
        }

        $formaPago = $pagos[0]['forma_pago'] ?? 'efectivo';

        $repo = new FacturaRepo();
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

        $fecha = (string)($input['fecha'] ?? date('Y-m-d'));
        $descuento = max(0, (int)($input['descuento_cents'] ?? 0));

        $id = $repo->create([
            'codigo' => $codigo,
            'tipo_comprobante' => $tipo,
            'remito_id' => $remitoId,
            'presupuesto_id' => null,
            'cliente_id' => $clienteId,
            'idclien' => $clienteErpId,
            'cliente_nombre' => $clienteNombre,
            'cliente_cuit' => $clienteCuit,
            'cliente_direc' => $clienteDirec,
            'cliente_tele' => $clienteTele,
            'cliente_mail' => $clienteMail,
            'cliente_condicion_iva' => $clienteCondIva,
            'punto_venta' => $auth->getPuntoVenta(),
            'fecha' => $fecha,
            'subtotal_cents' => $subtotal,
            'iva_cents' => $ivaTotal,
            'descuento_cents' => $descuento,
            'total_cents' => $subtotal + $ivaTotal - $descuento,
            'estado' => 'emitida',
            'forma_pago' => $formaPago,
            'notas' => $notas,
            'created_by' => (int)$adminUser['id'],
            'vendedor_id' => $vendedorId,
        ], $items, $pagos);

        // Deduct stock from session deposit
        $depoId = $auth->getDepositoId();
        if ($depoId > 0) {
            $stockRepo = new StockRepo();
            foreach ($items as $it) {
                $idprodu = $it['idprodu'];
                $idcodgusto = $it['idcodgusto'];
                $qty = $it['qty'];
                if ($idprodu) {
                    $stockRepo->registrarAjuste($idprodu, $idcodgusto, $depoId, -$qty, 'Factura ' . $codigo, (int)$adminUser['id']);
                }
            }
        }

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
        $arcaResult = null;
        $arcaError = null;
        $arcaRepo = new \Perfushopping\Web\Repo\ArcaRepo();
        if ($arcaRepo->isHabilitado()) {
            try {
                $facturaData = $repo->findById($id);
                $facturaItems = $repo->items($id);
                $wsfe = new \Perfushopping\Web\Service\AfipWsfe();
                $wsfe->autenticar();
                $resultado = $wsfe->solicitarCAE($facturaData, $facturaItems);
                $arcaRepo->guardarComprobante($id, $resultado);
                $arcaResult = $resultado['cae'] ?? null;
            } catch (\Throwable $e) {
                $arcaError = $e->getMessage();
                $arcaRepo->guardarComprobante($id, [
                    'resultado' => 'R',
                    'observaciones' => $arcaError,
                    'cae' => null,
                    'cae_vto' => null,
                    'codigo_emision' => null,
                    'request_xml' => null,
                    'response_xml' => null,
                ]);
            }
        }

        Response::json([
            'ok' => true,
            'id' => $id,
            'codigo' => $codigo,
            'arca' => $arcaResult ? ['cae' => $arcaResult] : ($arcaError ? ['error' => $arcaError] : null),
        ]);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/facturas');
        }
        $items = $repo->items($id);
        $pagos = $repo->pagos($id);

        $arcaComprobante = null;
        if ($factura['cae'] ?? null) {
            $arcaComprobante = (new \Perfushopping\Web\Repo\ArcaRepo())->getComprobante($id);
        }

        echo View::adminPage('admin/facturas/detail.php', [
            'adminUser' => $adminUser,
            'factura' => $factura,
            'items' => $items,
            'pagos' => $pagos,
            'arcaComprobante' => $arcaComprobante,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Factura ' . ($factura['codigo'] ?? ''),
        ]);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
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
                        $stockRepo->registrarAjuste($idprodu, $idcodgusto, $depoId, $qty, 'Anulación Factura ' . ($f['codigo'] ?? ''), (int)$adminUser['id']);
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

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/facturas/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/facturas');

        (new FacturaRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Factura eliminada.'];
        Response::redirect('/admin/facturas');
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new FacturaRepo())->searchProducts($q);

        Response::json($results);
    }

    public function searchClientes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new FacturaRepo())->findClienteWeb($q);

        Response::json($results);
    }

    public function searchRemitos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new FacturaRepo())->findRemitosDisponibles($q);

        Response::json($results);
    }

    public function print(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

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

        echo View::render('admin/facturas/print.php', [
            'factura' => $factura,
            'items' => $items,
            'pagos' => $pagos,
            'formato' => $formato,
        ]);
    }

    public function sendEmail(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
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

        $baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $attachmentHtml = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($factura['codigo'] ?? '') . '</title>';
        $attachmentHtml .= '<style>body{font-family:Arial,sans-serif;font-size:14px;color:#333;max-width:600px;margin:0 auto;padding:20px}';
        $attachmentHtml .= 'table{width:100%;border-collapse:collapse;margin:12px 0}th,td{padding:6px 8px;text-align:left;border-bottom:1px solid #ddd}';
        $attachmentHtml .= 'th{background:#f5f5f5}.total{font-size:18px;font-weight:bold;border-top:2px solid #333;padding-top:8px;margin-top:4px}';
        $attachmentHtml .= '.footer{margin-top:20px;font-size:12px;color:#888;text-align:center}';
        $attachmentHtml .= 'h1{font-size:22px;margin:0 0 4px}.logo{max-width:140px;margin-bottom:8px}</style></head><body>';
        $attachmentHtml .= '<div style="text-align:center"><img class="logo" src="' . $baseUrl . '/assets/brand/logo-header.png" alt="Perfushopping" /></div>';
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
        $attachmentHtml .= '<hr style="border:none;border-top:1px solid #ddd" />';
        $attachmentHtml .= '<div class="footer"><p>Gracias por su compra</p><p>Perfushopping — www.perfushopping.com</p></div>';
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
        $emailBody .= '<p>Gracias por su compra.<br/>Perfushopping</p>';

        try {
            (new SmtpMailer())->send($to, 'Factura ' . ($factura['codigo'] ?? ''), $emailBody, '', $attachments);
            Response::json(['ok' => true, 'message' => 'Factura enviada a ' . $to]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => 'Error al enviar: ' . $e->getMessage()]);
        }
    }
}
