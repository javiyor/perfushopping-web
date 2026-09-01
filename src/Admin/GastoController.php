<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoMovimientoRepo;
use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\CompraRepo;
use Perfushopping\Web\Repo\GastoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class GastoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        $repo = new GastoRepo();
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $hasFiltro = isset($_GET['desde']) || isset($_GET['hasta']) || isset($_GET['q']);
        if (!$hasFiltro) {
            $desde = date('Y-m-d');
            $hasta = date('Y-m-d');
        } elseif ($desde === '' && $hasta !== '') {
            $desde = $hasta;
        } elseif ($hasta === '' && $desde !== '') {
            $hasta = $desde;
        }
        $list = $repo->findAll(['q'=>trim((string)($_GET['q'] ?? '')), 'desde'=>$desde, 'hasta'=>$hasta]);
        $today = date('Y-m-d');
        echo View::adminPage('admin/gastos/list.php', [
            'adminUser'=>$adminUser,
            'list'=>$list,
            'cuentas'=>$repo->cuentas(),
            'bancos'=>(new BancoCuentaRepo())->findAll(),
            'desde'=>$desde,
            'hasta'=>$hasta,
            'today'=>$today,
            'csrf'=>Csrf::token(),
            'pageTitle'=>'Gastos varios',
        ]);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        $idcta1 = (int)($_POST['idcta1'] ?? 0);
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $importeCents = (int)($_POST['importe_cents'] ?? 0);
        if ($importeCents <= 0) {
            $importe = (float)str_replace(',', '.', (string)($_POST['importe'] ?? '0'));
            $importeCents = (int)round($importe * 100);
        }
        $formaPago = trim((string)($_POST['forma_pago'] ?? 'efectivo'));
        $cajaDestino = trim((string)($_POST['caja_destino'] ?? 'general'));
        $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);

        if ($descripcion === '' || $importeCents <= 0 || $idcta1 <= 0) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Completá cuenta, descripción e importe.'];
            Response::redirect('/admin/gastos');
        }
        if (!in_array($formaPago, ['efectivo','transferencia','cheque'], true)) $formaPago='efectivo';
        if (!in_array($cajaDestino, ['chica','general'], true)) $cajaDestino='general';

        $chequeId = null;
        if ($formaPago === 'cheque') {
            $bancoEmisor = trim((string)($_POST['banco_emisor'] ?? ''));
            $numero = trim((string)($_POST['numero_cheque'] ?? ''));
            $titular = trim((string)($_POST['titular'] ?? ''));
            $venc = trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null;
            $repoCh = new ChequeRepo();
            // Para gastos es cheque propio
            $chequeId = $repoCh->create([
                'tipo'=>'propio',
                'estado'=>'emitido',
                'banco_emisor'=>$bancoEmisor,
                'numero_cheque'=>$numero,
                'titular'=>$titular,
                'cuit_titular'=>trim((string)($_POST['cuit'] ?? '')),
                'monto_cents'=>$importeCents,
                'fecha_emision'=>$fecha,
                'fecha_vencimiento'=>$venc,
                'banco_cuenta_id'=>$bancoCuentaId ?: null,
                'concepto'=>'Gasto: '.$descripcion,
            ], (int)$adminUser['id']);
            $repoCh->agregarMovimiento($chequeId, 'emitido', 'gasto', null, 'Gasto varios', (int)$adminUser['id']);
        }

        $repo = new GastoRepo();
        $gastoId = $repo->create([
            'fecha'=>$fecha,
            'idcta1'=>$idcta1,
            'descripcion'=>$descripcion,
            'importe_cents'=>$importeCents,
            'forma_pago'=>$formaPago,
            'caja_destino'=>($formaPago==='efectivo' ? $cajaDestino : 'general'),
            'banco_cuenta_id'=>($formaPago!=='efectivo' ? ($bancoCuentaId ?: null) : null),
            'cheque_id'=>$chequeId,
            'sucursal_id'=>$auth->getSucursalId(),
            'punto_venta'=>$auth->getPuntoVenta(),
            'created_by'=>(int)$adminUser['id'],
        ]);

        // Movimientos caja / banco
        $cajaRepo = new CajaRepo();
        $bancoRepo = new BancoMovimientoRepo();
        $concepto = 'Gasto: '.$descripcion;

        if ($formaPago === 'efectivo') {
            if ($cajaDestino === 'chica') {
                $sucursalId = $auth->getSucursalId();
                $turno = $auth->getTurno();
                $apertura = $cajaRepo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));
                if ($apertura) {
                    $cajaRepo->agregarMovimiento((int)$apertura['id'], 'egreso', $concepto, $importeCents, (int)$adminUser['id']);
                } else {
                    // Si no hay caja chica abierta, va a general
                    $cajaRepo->agregarMovimientoGeneral('egreso', 'gasto', $gastoId, $concepto, $importeCents, (int)$adminUser['id']);
                }
            } else {
                $cajaRepo->agregarMovimientoGeneral('egreso', 'gasto', $gastoId, $concepto, $importeCents, (int)$adminUser['id']);
            }
        } elseif ($formaPago === 'transferencia') {
            // Caja general egreso + banco debito
            $cajaRepo->agregarMovimientoGeneral('egreso', 'gasto', $gastoId, $concepto.' (transferencia)', $importeCents, (int)$adminUser['id']);
            if ($bancoCuentaId) {
                $bancoRepo->create($bancoCuentaId, 'debito', 'gasto', $gastoId, $concepto, $importeCents, $fecha, (int)$adminUser['id']);
            }
        } elseif ($formaPago === 'cheque') {
            // Caja general egreso + banco debito (cuando se debite, pero lo registramos como pendiente; el débito real será al cambiar estado a debitado, aquí solo dejamos trazabilidad en caja general)
            $cajaRepo->agregarMovimientoGeneral('egreso', 'gasto', $gastoId, $concepto.' (cheque)', $importeCents, (int)$adminUser['id']);
            if ($bancoCuentaId) {
                // No debitamos aún hasta que el cheque se acredite; pero si querés, descomentar:
                // $bancoRepo->create($bancoCuentaId, 'debito', 'gasto', $gastoId, $concepto.' (cheque)', $importeCents, $fecha, (int)$adminUser['id']);
            }
        }

        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Gasto registrado.'];
        Response::redirect('/admin/gastos');
    }

    public function depositarForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        echo View::adminPage('admin/caja/depositar.php', [
            'adminUser'=>$adminUser,
            'bancos'=>(new BancoCuentaRepo())->findAll(),
            'csrf'=>Csrf::token(),
            'pageTitle'=>'Depositar efectivo en banco',
        ]);
    }

    public function depositarStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);
        $montoCents = (int)($_POST['monto_cents'] ?? 0);
        if ($montoCents <=0) {
            $monto = (float)str_replace(',', '.', (string)($_POST['monto'] ?? '0'));
            $montoCents = (int)round($monto*100);
        }
        $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);
        $origen = trim((string)($_POST['origen'] ?? 'chica')); // chica o general
        $concepto = trim((string)($_POST['concepto'] ?? 'Depósito en banco'));

        if ($montoCents <=0 || $bancoCuentaId <=0) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Completá monto y banco.'];
            Response::redirect('/admin/caja/depositar');
        }

        $cajaRepo = new CajaRepo();
        $bancoRepo = new BancoMovimientoRepo();

        // Egreso de caja
        if ($origen === 'chica') {
            $sucursalId = $auth->getSucursalId();
            $turno = $auth->getTurno();
            $apertura = $cajaRepo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));
            if ($apertura) {
                $cajaRepo->agregarMovimiento((int)$apertura['id'], 'egreso', $concepto.' (a banco)', $montoCents, (int)$adminUser['id']);
            } else {
                $cajaRepo->agregarMovimientoGeneral('egreso', 'deposito_banco', null, $concepto.' (desde caja chica s/apertura)', $montoCents, (int)$adminUser['id']);
            }
        } else {
            $cajaRepo->agregarMovimientoGeneral('egreso', 'deposito_banco', null, $concepto, $montoCents, (int)$adminUser['id']);
        }
        // Crédito en banco
        $bancoRepo->create($bancoCuentaId, 'credito', 'deposito', null, $concepto, $montoCents, date('Y-m-d'), (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Depósito registrado.'];
        Response::redirect('/admin/caja/general');
    }
}
