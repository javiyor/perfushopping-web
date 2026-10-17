<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Repo\ReciboRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipPadronService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ReciboController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $fecha = trim((string)($_GET['fecha'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            $fecha = date('Y-m-d');
        }
        $list = (new ReciboRepo())->search($q, $estado, 60, $fecha);

        echo View::adminPage('admin/recibos/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'fecha' => $fecha,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Recibos',
        ]);
    }

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        echo View::adminPage('admin/recibos/form.php', [
            'adminUser' => $adminUser,
            'formasPago' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->findActivas(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Nuevo recibo',
        ]);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');
        Csrf::check($_POST['_csrf'] ?? null);

        $clienteNombre = trim((string)($_POST['cliente_nombre'] ?? ''));
        if ($clienteNombre === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre del cliente es obligatorio.'];
            Response::redirect('/admin/recibos/nuevo');
        }

        $montoCents = (int)($_POST['monto_cents'] ?? 0);
        if ($montoCents <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El monto debe ser mayor a cero.'];
            Response::redirect('/admin/recibos/nuevo');
        }

        $formaPago = trim((string)($_POST['forma_pago'] ?? 'efectivo'));

        // Create cheque record if forma_pago = cheque
        $chequeId = null;
        if ($formaPago === 'cheque') {
            $chequeRepo = new ChequeRepo();
            $chequeMontoCents = (int)($_POST['cheque_monto_cents'] ?? 0);
            if ($chequeMontoCents <= 0) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá los datos del cheque.'];
                Response::redirect('/admin/recibos/nuevo');
            }
            $chequeId = $chequeRepo->create([
                'tipo' => 'tercero',
                'estado' => 'en_cartera',
                'banco_emisor' => trim((string)($_POST['cheque_banco'] ?? '')),
                'numero_cheque' => trim((string)($_POST['cheque_numero'] ?? '')),
                'titular' => trim((string)($_POST['cheque_titular'] ?? '')),
                'cuit_titular' => trim((string)($_POST['cheque_cuit'] ?? '')),
                'monto_cents' => $chequeMontoCents,
                'fecha_emision' => (string)($_POST['fecha'] ?? date('Y-m-d')),
                'fecha_vencimiento' => trim((string)($_POST['cheque_vencimiento'] ?? '')) ?: null,
                'concepto' => 'Recibo — ' . trim((string)($_POST['cliente_nombre'] ?? '')),
            ], (int)$adminUser['id']);
            $chequeRepo->agregarMovimiento($chequeId, 'recibido', 'recibo', 0, '', (int)$adminUser['id']);
        }

        $facturaIds = $_POST['factura_id'] ?? [];
        $montosPago = $_POST['pago_monto'] ?? [];
        $pagos = [];
        if (is_array($facturaIds)) {
            foreach ($facturaIds as $idx => $fid) {
                $fid = (int)$fid;
                if ($fid <= 0) continue;
                $pm = (int)($montosPago[$idx] ?? 0);
                if ($pm <= 0) continue;
                $pagoRow = ['factura_id' => $fid, 'monto_cents' => $pm];
                if ($chequeId) {
                    $pagoRow['forma_pago'] = 'cheque';
                    $pagoRow['cheque_id'] = $chequeId;
                }
                $pagos[] = $pagoRow;
            }
        }

        if (!$pagos) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná al menos una factura o ingresá un concepto.'];
            Response::redirect('/admin/recibos/nuevo');
        }

        $repo = new ReciboRepo();
        $clienteId = (int)($_POST['cliente_id'] ?? 0) ?: null;
        $clienteCuit = trim((string)($_POST['cliente_cuit'] ?? ''));
        $clienteDirec = trim((string)($_POST['cliente_direc'] ?? ''));
        $clienteCondIva = trim((string)($_POST['cliente_condicion_iva'] ?? 'consumidor_final'));
        $clienteErpId = null;

        if ($clienteId) {
            $erp = $repo->findClienteErpByWebId($clienteId);
            $clienteErpId = $erp ? (int)$erp['idclien'] : null;
        }

        $codigo = $repo->nextCodigo();
        $id = $repo->create([
            'codigo' => $codigo,
            'tipo_comprobante' => 'REC-B',
            'cliente_id' => $clienteId,
            'idclien' => $clienteErpId,
            'cliente_nombre' => $clienteNombre,
            'cliente_cuit' => $clienteCuit,
            'cliente_direc' => $clienteDirec,
            'cliente_condicion_iva' => $clienteCondIva,
            'fecha' => (string)($_POST['fecha'] ?? date('Y-m-d')),
            'monto_cents' => $montoCents,
            'forma_pago' => $formaPago,
            'concepto' => trim((string)($_POST['concepto'] ?? '')),
            'estado' => 'emitido',
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)$adminUser['id'],
        ], $pagos);

        // Auto-post credits to current account
        if ($clienteId) {
            $ctaCte = new \Perfushopping\Web\Repo\CtaCteRepo();
            foreach ($pagos as $pg) {
                $facturaCodigo = '';
                if ($pg['factura_id']) {
                    $stF = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT codigo FROM facturas WHERE id = :id LIMIT 1');
                    $stF->execute([':id' => $pg['factura_id']]);
                    $facturaCodigo = (string)($stF->fetchColumn() ?: '');
                }
                $concepto = 'Recibo ' . $codigo;
                if ($facturaCodigo) $concepto .= ' — Pago ' . $facturaCodigo;
                $ctaCte->agregarMovimiento(
                    'credito',
                    'recibo',
                    $id,
                    $clienteId,
                    $clienteErpId,
                    $pg['monto_cents'],
                    $concepto,
                    (int)$adminUser['id']
                );
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Recibo {$codigo} emitido."];
        Response::redirect('/admin/recibos/' . $id);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        $id = (int)($params['id'] ?? 0);
        $repo = new ReciboRepo();
        $recibo = $repo->findById($id);
        if (!$recibo) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Recibo no encontrado.'];
            Response::redirect('/admin/recibos');
        }
        $pagos = $repo->pagos($id);

        echo View::adminPage('admin/recibos/detail.php', [
            'adminUser' => $adminUser,
            'recibo' => $recibo,
            'pagos' => $pagos,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Recibo ' . ($recibo['codigo'] ?? ''),
        ]);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');

        if (!in_array($estado, ['emitido', 'anulado'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/recibos');
        }

        $repo = new ReciboRepo();
        $r = $repo->findById($id);
        if (!$r) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Recibo no encontrado.'];
            Response::redirect('/admin/recibos');
        }

        $adminAutoriza = $auth->verificarCredenciales(
            (string)($_POST['auth_user'] ?? ''),
            (string)($_POST['auth_pass'] ?? '')
        );
        if (!$adminAutoriza || !AdminAuthService::adminPuedeAutorizar($adminAutoriza)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Autorización rechazada: se requieren credenciales de un admin autorizado.'];
            Response::redirect('/admin/recibos/' . $id);
        }

        $oldEstado = $r['estado'] ?? '';
        if ($oldEstado === 'anulado' && $estado === 'emitido') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Un recibo anulado no se puede reactivar; emití uno nuevo.'];
            Response::redirect('/admin/recibos/' . $id);
        }
        $repo->updateEstado($id, $estado);

        // Reverse ctacte credits if recibo is anulated
        if ($estado === 'anulado' && $oldEstado !== 'anulado' && $r['cliente_id']) {
            $pagos = $repo->pagos($id);
            $ctaCte = new \Perfushopping\Web\Repo\CtaCteRepo();
            foreach ($pagos as $pg) {
                $ctaCte->agregarMovimiento(
                    'debito',
                    'recibo',
                    $id,
                    (int)$r['cliente_id'],
                    (int)($r['idclien'] ?? 0) ?: null,
                    (int)($pg['monto_cents'] ?? 0),
                    'Anulación Recibo ' . ($r['codigo'] ?? ''),
                    (int)$adminUser['id']
                );
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/recibos/' . $id);
    }

    public function update(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('recibos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $repo = new ReciboRepo();
        $recibo = $repo->findById($id);
        if (!$recibo || ($recibo['estado'] ?? '') !== 'emitido') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solo se pueden corregir recibos emitidos.'];
            Response::redirect('/admin/recibos/comprobantes');
        }

        $adminAutoriza = $auth->verificarCredenciales(
            (string)($_POST['auth_user'] ?? ''),
            (string)($_POST['auth_pass'] ?? '')
        );
        if (!$adminAutoriza || !AdminAuthService::adminPuedeAutorizar($adminAutoriza)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Autorización rechazada: se requieren credenciales de un admin autorizado.'];
            Response::redirect('/admin/recibos/' . $id);
        }

        $fecha = trim((string)($_POST['fecha'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'La fecha indicada no es válida.'];
            Response::redirect('/admin/recibos/' . $id);
        }
        $repo->updateDatos(
            $id,
            $fecha,
            trim((string)($_POST['concepto'] ?? '')),
            trim((string)($_POST['notas'] ?? ''))
        );
        $nombre = (string)($adminAutoriza['nombre'] ?? $adminAutoriza['username'] ?? 'admin');
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Datos del recibo corregidos con autorización de ' . $nombre . '.'];
        Response::redirect('/admin/recibos/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/recibos');

        $repo = new ReciboRepo();
        $recibo = $repo->findById($id);
        if (!$recibo || ($recibo['estado'] ?? '') !== 'anulado') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Para eliminar un recibo, primero debe estar anulado.'];
            Response::redirect('/admin/recibos/' . $id);
        }
        $adminAutoriza = $auth->verificarCredenciales(
            (string)($_POST['auth_user'] ?? ''),
            (string)($_POST['auth_pass'] ?? '')
        );
        if (!$adminAutoriza || !AdminAuthService::adminPuedeAutorizar($adminAutoriza)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Autorización rechazada: se requieren credenciales de un admin autorizado.'];
            Response::redirect('/admin/recibos/' . $id);
        }

        $repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Recibo eliminado.'];
        Response::redirect('/admin/recibos');
    }

    public function searchClientes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new ReciboRepo();
        $results = $repo->findClienteWeb($q);

        if (empty($results) && preg_match('/^\d{11}$/', $q)) {
            try {
                $arcaRepo = new ArcaRepo();
                if ($arcaRepo->isHabilitado()) {
                    $padron = new AfipPadronService();
                    $persona = $padron->consultar($q);
                    if ($persona) {
                        $cliente = (new FacturaRepo())->upsertClienteArca($persona);
                        if ($cliente) {
                            $results = [$cliente];
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('ARCA padron error: ' . $e->getMessage());
            }
        }

        Response::json($results);
    }

    public function searchFacturas(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            Response::json([]);
            return;
        }

        $results = (new ReciboRepo())->findFacturasPendientes($clienteId);
        Response::json($results);
    }

    public function print(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('recibos');

        $id = (int)($params['id'] ?? 0);
        $formato = (string)($_GET['formato'] ?? '80mm');
        if (!in_array($formato, ['a4', '80mm', '58mm'], true)) $formato = '80mm';

        $repo = new ReciboRepo();
        $recibo = $repo->findById($id);
        if (!$recibo) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Recibo no encontrado.'];
            Response::redirect('/admin/recibos');
        }
        $pagos = $repo->pagos($id);

        $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();

        echo View::render('admin/recibos/print.php', [
            'recibo' => $recibo,
            'pagos' => $pagos,
            'formato' => $formato,
            'empresa' => $empresa,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
        ]);
    }
}
