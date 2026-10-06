<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoMovimientoRepo;
use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\CompraRepo;
use Perfushopping\Web\Repo\CobroCuentaRepo;
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
        $this->registrarMovimientosGasto([
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'importe_cents' => $importeCents,
            'forma_pago' => $formaPago,
            'caja_destino' => $cajaDestino,
            'banco_cuenta_id' => $bancoCuentaId,
        ], $gastoId, $auth, (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Gasto registrado.'];
        Response::redirect('/admin/gastos');
    }

    public function update(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $repo = new GastoRepo();
        $actual = $id > 0 ? $repo->findById($id) : null;
        if (!$actual) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Gasto no encontrado.'];
            Response::redirect('/admin/gastos');
        }

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

        $conceptoNuevo = 'Gasto: '.$descripcion;
        $conceptoViejo = 'Gasto: '.trim((string)($actual['descripcion'] ?? ''));
        $importeViejo = (int)($actual['importe_cents'] ?? 0);
        $esChicaVieja = ($actual['forma_pago'] ?? '') === 'efectivo' && ($actual['caja_destino'] ?? 'general') === 'chica';
        $esChicaNueva = $formaPago === 'efectivo' && $cajaDestino === 'chica';

        // Cheque propio vinculado
        $chequeRepo = new ChequeRepo();
        $chequeId = (int)($actual['cheque_id'] ?? 0) ?: null;
        if ($formaPago === 'cheque') {
            $datosCheque = [
                'banco_emisor' => trim((string)($_POST['banco_emisor'] ?? '')),
                'numero_cheque' => trim((string)($_POST['numero_cheque'] ?? '')),
                'titular' => trim((string)($_POST['titular'] ?? '')) ?: trim((string)($actual['titular'] ?? '')),
                'monto_cents' => $importeCents,
                'fecha_emision' => $fecha,
                'fecha_vencimiento' => trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null,
                'banco_cuenta_id' => $bancoCuentaId ?: null,
                'concepto' => 'Gasto: '.$descripcion,
            ];
            $chequeActual = $chequeId > 0 ? $chequeRepo->findById($chequeId) : null;
            if ($chequeActual && ($chequeActual['tipo'] ?? '') === 'propio' && ($chequeActual['estado'] ?? '') === 'emitido') {
                $chequeRepo->updateDatos($chequeId, $datosCheque);
            } else {
                $chequeId = $chequeRepo->create($datosCheque + ['tipo'=>'propio','estado'=>'emitido'], (int)$adminUser['id']);
                $chequeRepo->agregarMovimiento($chequeId, 'emitido', 'gasto', $id, 'Gasto varios (edición)', (int)$adminUser['id']);
            }
        } else {
            if ($chequeId > 0) {
                $chequeActual = $chequeRepo->findById($chequeId);
                if ($chequeActual && ($chequeActual['tipo'] ?? '') === 'propio' && ($chequeActual['estado'] ?? '') === 'emitido') {
                    $chequeRepo->delete($chequeId);
                }
            }
            $chequeId = null;
        }

        // Movimientos: caja chica se actualiza/elimina; caja general y banco se recrean.
        $cajaRepo = new CajaRepo();
        $chicaExistente = $esChicaVieja
            ? $cajaRepo->buscarMovimientoGastoChica($conceptoViejo, $importeViejo)
            : null;
        if ($chicaExistente) {
            if ($esChicaNueva) {
                $cajaRepo->actualizarMovimientoGastoChica($conceptoViejo, $importeViejo, $conceptoNuevo, $importeCents);
            } else {
                $cajaRepo->eliminarMovimientoGastoChica($conceptoViejo, $importeViejo);
            }
        }
        $cajaRepo->eliminarMovimientosGasto($id);
        (new BancoMovimientoRepo())->eliminarMovimientosGasto($id);
        $this->registrarMovimientosGasto([
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'importe_cents' => $importeCents,
            'forma_pago' => $formaPago,
            'caja_destino' => $cajaDestino,
            'banco_cuenta_id' => $bancoCuentaId,
        ], $id, $auth, (int)$adminUser['id'], $esChicaNueva && !$chicaExistente);

        $repo->update($id, [
            'fecha' => $fecha,
            'idcta1' => $idcta1,
            'descripcion' => $descripcion,
            'importe_cents' => $importeCents,
            'forma_pago' => $formaPago,
            'caja_destino' => ($formaPago === 'efectivo' ? $cajaDestino : 'general'),
            'banco_cuenta_id' => ($formaPago !== 'efectivo' ? ($bancoCuentaId ?: null) : null),
            'cheque_id' => $chequeId,
        ]);

        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Gasto actualizado.'];
        Response::redirect('/admin/gastos');
    }

    public function eliminar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $repo = new GastoRepo();
        $gasto = $id > 0 ? $repo->findById($id) : null;
        if (!$gasto) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Gasto no encontrado.'];
            Response::redirect('/admin/gastos');
        }

        // Autorización: credenciales de un admin activo con permiso.
        $autoriza = $auth->verificarCredenciales(
            (string)($_POST['auth_user'] ?? ''),
            (string)($_POST['auth_pass'] ?? '')
        );
        if (!$autoriza) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Autorización rechazada: usuario o contraseña de admin incorrectos.'];
            Response::redirect('/admin/gastos');
        }
        if (!AdminAuthService::adminPuedeAutorizar($autoriza)) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Autorización rechazada: ese admin no tiene permiso para eliminar gastos.'];
            Response::redirect('/admin/gastos');
        }

        $concepto = 'Gasto: '.trim((string)($gasto['descripcion'] ?? ''));
        $importe = (int)($gasto['importe_cents'] ?? 0);

        $cajaRepo = new CajaRepo();
        if (($gasto['forma_pago'] ?? '') === 'efectivo' && ($gasto['caja_destino'] ?? 'general') === 'chica') {
            $cajaRepo->eliminarMovimientoGastoChica($concepto, $importe);
        }
        $cajaRepo->eliminarMovimientosGasto($id);
        (new BancoMovimientoRepo())->eliminarMovimientosGasto($id);

        $chequeId = (int)($gasto['cheque_id'] ?? 0);
        if ($chequeId > 0) {
            $chequeRepo = new ChequeRepo();
            $cheque = $chequeRepo->findById($chequeId);
            if ($cheque && ($cheque['tipo'] ?? '') === 'propio' && ($cheque['estado'] ?? '') === 'emitido') {
                $chequeRepo->delete($chequeId);
            }
        }

        $repo->delete($id);

        $autorizaNombre = (string)($autoriza['nombre'] ?? $autoriza['username'] ?? 'admin');
        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Gasto eliminado. Autorizado por: '.$autorizaNombre.'.'];
        Response::redirect('/admin/gastos');
    }

    /** Registra los movimientos de caja general / banco de un gasto (mismos criterios que al crear). */
    private function registrarMovimientosGasto(array $g, int $gastoId, AdminAuthService $auth, int $userId, bool $incluirChica = true): void
    {
        $cajaRepo = new CajaRepo();
        $bancoRepo = new BancoMovimientoRepo();
        $formaPago = (string)$g['forma_pago'];
        $importeCents = (int)$g['importe_cents'];
        $cajaDestino = (string)$g['caja_destino'];
        $bancoCuentaId = (int)($g['banco_cuenta_id'] ?? 0);
        $fecha = (string)$g['fecha'];
        $fh = ($fecha !== '') ? $fecha . ' ' . date('H:i:s') : null;
        $concepto = 'Gasto: '.(string)$g['descripcion'];

        if ($formaPago === 'efectivo') {
            if ($cajaDestino === 'chica') {
                if (!$incluirChica) {
                    // El movimiento de caja chica ya fue actualizado en update().
                    return;
                }
                $apertura = $cajaRepo->aperturaActiva($auth->getSucursalId(), $auth->getTurno(), date('Y-m-d'));
                if ($apertura) {
                    $cajaRepo->agregarMovimiento((int)$apertura['id'], 'egreso', $concepto, $importeCents, $userId);
                    return;
                }
            }
            $cajaRepo->agregarMovimientoGeneral('egreso', 'gasto', $gastoId, $concepto, $importeCents, $userId, $fh);
        } elseif ($formaPago === 'transferencia') {
            // Caja general es solo efectivo: la transferencia impacta solo en movimientos bancarios.
            if (!$bancoCuentaId) {
                $bancoCuentaId = (int)((new CobroCuentaRepo())->getTransferenciaCuentaId() ?: 0);
            }
            if ($bancoCuentaId) {
                $bancoRepo->create($bancoCuentaId, 'debito', 'gasto', $gastoId, $concepto, $importeCents, $fecha, $userId);
            } else {
                error_log('GastoController::registrarMovimientosGasto: gasto ' . $gastoId . ' sin cuenta bancaria para transferencia');
            }
        }
        // cheque: sin egreso en caja general (el flujo queda en el módulo de cheques).
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
        Response::redirect('/admin/caja');
    }
}
