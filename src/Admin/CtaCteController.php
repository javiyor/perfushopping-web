<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Repo\CtaCteRepo;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipPadronService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CtaCteController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');

        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new CtaCteRepo())->listarConSaldo($q);

        echo View::adminPage('admin/ctacte/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cuentas corrientes',
        ]);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');

        $clienteId = (int)($params['id'] ?? 0);
        if ($clienteId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente inválido.'];
            Response::redirect('/admin/ctacte');
        }

        $repo = new CtaCteRepo();

        // Get client info
        $st = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT id, name, email, phone FROM web_users WHERE id = :id LIMIT 1');
        $st->execute([':id' => $clienteId]);
        $cliente = $st->fetch();
        if (!$cliente) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente no encontrado.'];
            Response::redirect('/admin/ctacte');
        }

        $saldo = $repo->saldoActual($clienteId);
        $movimientos = $repo->movimientos($clienteId);

        echo View::adminPage('admin/ctacte/show.php', [
            'adminUser' => $adminUser,
            'cliente' => $cliente,
            'saldo' => $saldo,
            'movimientos' => $movimientos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta. cte. — ' . ($cliente['name'] ?? ''),
        ]);
    }

    public function showErp(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');
        $idclien = (int)($params['id'] ?? 0);
        if ($idclien <= 0) {
            Response::redirect('/admin/ctacte');
        }

        $st = Db::pdo()->prepare('SELECT idclien, razon AS name, cuit, mail AS email, tele AS phone FROM clientes WHERE idclien = :id LIMIT 1');
        $st->execute([':id' => $idclien]);
        $cliente = $st->fetch();
        if (!$cliente) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente ERP no encontrado.'];
            Response::redirect('/admin/ctacte');
        }
        $cliente['id'] = 0;
        $repo = new CtaCteRepo();
        $repo->sincronizarFacturasErp($idclien, (int)$adminUser['id']);
        echo View::adminPage('admin/ctacte/show.php', [
            'adminUser' => $adminUser,
            'cliente' => $cliente,
            'saldo' => $repo->saldoActualErp($idclien),
            'movimientos' => $repo->movimientosErp($idclien),
            'facturasPendientes' => (new \Perfushopping\Web\Repo\ReciboRepo())->findFacturasPendientesErp($idclien),
            'formasPago' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->findActivas(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta. cte. — ' . ($cliente['name'] ?? ''),
        ]);
    }

    public function pagarErp(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');
        Csrf::check($_POST['_csrf'] ?? null);

        $idclien = (int)($_POST['idclien'] ?? 0);
        $repo = new CtaCteRepo();
        $back = '/admin/ctacte/erp/' . $idclien;
        $facturaIdsRaw = $_POST['factura_id'] ?? [];
        if ($idclien <= 0 || !is_array($facturaIdsRaw) || !$facturaIdsRaw) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná al menos una factura pendiente.'];
            Response::redirect($back);
        }

        $clienteSt = Db::pdo()->prepare('SELECT idclien, razon, cuit, direc, COALESCE(condicion_iva, \'consumidor_final\') AS condicion_iva FROM clientes WHERE idclien = :id LIMIT 1');
        $clienteSt->execute([':id' => $idclien]);
        $cliente = $clienteSt->fetch();
        if (!$cliente) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente ERP no encontrado.'];
            Response::redirect('/admin/ctacte');
        }

        $formaPago = trim((string)($_POST['forma_pago'] ?? ''));
        $formaRepo = new \Perfushopping\Web\Repo\FormaPagoRepo();
        $forma = $formaRepo->findActivoByCodigo($formaPago);
        $tipoForma = $forma ? (string)$forma['tipo'] : '';
        if (!$forma || in_array($tipoForma, ['ctacte', 'moneda'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná una forma de cobro válida.'];
            Response::redirect($back);
        }

        $pendientes = (new \Perfushopping\Web\Repo\ReciboRepo())->findFacturasPendientesErp($idclien);
        $pendientePorId = [];
        foreach ($pendientes as $f) {
            $pendientePorId[(int)$f['id']] = (int)$f['pendiente_cents'];
        }
        $montos = is_array($_POST['monto'] ?? null) ? $_POST['monto'] : [];
        $pagos = [];
        $totalCents = 0;
        $idsSeleccionados = array_values(array_unique(array_map('intval', $facturaIdsRaw)));
        foreach ($idsSeleccionados as $facturaId) {
            if ($facturaId <= 0 || !isset($pendientePorId[$facturaId])) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Una de las facturas seleccionadas ya no está pendiente para este cliente.'];
                Response::redirect($back);
            }
            $montoCents = (int)round(max(0, (float)str_replace(',', '.', (string)($montos[$facturaId] ?? '0'))) * 100);
            if ($montoCents <= 0 || $montoCents > $pendientePorId[$facturaId]) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El importe de cada factura debe ser mayor a cero y no superar su saldo pendiente.'];
                Response::redirect($back);
            }
            $pagos[] = ['factura_id' => $facturaId, 'monto_cents' => $montoCents, 'forma_pago' => $formaPago];
            $totalCents += $montoCents;
        }
        if (!$pagos || $totalCents <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ingresá importes de cobro válidos.'];
            Response::redirect($back);
        }

        $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            $fecha = date('Y-m-d');
        }
        $notas = trim((string)($_POST['notas'] ?? ''));
        $chequeId = null;
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Revalidar saldos bajo la misma transacción antes de imputar el recibo.
            $pendientesActuales = (new \Perfushopping\Web\Repo\ReciboRepo())->findFacturasPendientesErp($idclien);
            $pendienteActualPorId = [];
            foreach ($pendientesActuales as $f) {
                $pendienteActualPorId[(int)$f['id']] = (int)$f['pendiente_cents'];
            }
            foreach ($pagos as $pago) {
                if (!isset($pendienteActualPorId[(int)$pago['factura_id']]) || (int)$pago['monto_cents'] > $pendienteActualPorId[(int)$pago['factura_id']]) {
                    throw new \RuntimeException('El saldo de una factura cambió; actualizá la página y volvé a seleccionar.');
                }
            }

            if ($tipoForma === 'cheque') {
                $chequeMonto = (int)($_POST['cheque_monto_cents'] ?? $totalCents);
                if ($chequeMonto !== $totalCents) {
                    throw new \RuntimeException('El monto del cheque debe coincidir con el total asignado a facturas.');
                }
                $chequeRepo = new \Perfushopping\Web\Repo\ChequeRepo();
                $chequeId = $chequeRepo->create([
                    'tipo' => 'tercero',
                    'estado' => 'en_cartera',
                    'banco_emisor' => trim((string)($_POST['cheque_banco'] ?? '')),
                    'numero_cheque' => trim((string)($_POST['cheque_numero'] ?? '')),
                    'titular' => trim((string)($_POST['cheque_titular'] ?? '')),
                    'cuit_titular' => trim((string)($_POST['cheque_cuit'] ?? '')),
                    'monto_cents' => $totalCents,
                    'fecha_emision' => $fecha,
                    'fecha_vencimiento' => trim((string)($_POST['cheque_vencimiento'] ?? '')) ?: null,
                    'concepto' => 'Cobro Cta. Cte. — ' . (string)$cliente['razon'],
                ], (int)$adminUser['id']);
                foreach ($pagos as &$pago) {
                    $pago['cheque_id'] = $chequeId;
                }
                unset($pago);
            }

            $reciboRepo = new \Perfushopping\Web\Repo\ReciboRepo();
            $codigo = $reciboRepo->nextCodigo();
            $reciboId = $reciboRepo->create([
                'codigo' => $codigo,
                'tipo_comprobante' => 'REC-B',
                'cliente_id' => null,
                'idclien' => $idclien,
                'cliente_nombre' => (string)$cliente['razon'],
                'cliente_cuit' => (string)($cliente['cuit'] ?? ''),
                'cliente_direc' => (string)($cliente['direc'] ?? ''),
                'cliente_condicion_iva' => (string)($cliente['condicion_iva'] ?? 'consumidor_final'),
                'fecha' => $fecha,
                'monto_cents' => $totalCents,
                'forma_pago' => $formaPago,
                'concepto' => 'Cobro cuenta corriente — ' . implode(', ', array_map(static fn(array $p): string => 'Factura #' . (int)$p['factura_id'], $pagos)),
                'estado' => 'emitido',
                'notas' => $notas,
                'created_by' => (int)$adminUser['id'],
            ], $pagos);

            if ($chequeId) {
                (new \Perfushopping\Web\Repo\ChequeRepo())->agregarMovimiento($chequeId, 'recibido', 'recibo', $reciboId, 'Cobro cuenta corriente ' . $codigo, (int)$adminUser['id']);
            }
            foreach ($pagos as $pago) {
                $facturaCodigoSt = $pdo->prepare('SELECT codigo FROM facturas WHERE id = :id LIMIT 1');
                $facturaCodigoSt->execute([':id' => (int)$pago['factura_id']]);
                $facturaCodigo = (string)($facturaCodigoSt->fetchColumn() ?: ('#' . (int)$pago['factura_id']));
                $repo->agregarMovimiento(
                    'credito',
                    'recibo',
                    $reciboId,
                    null,
                    $idclien,
                    (int)$pago['monto_cents'],
                    'Recibo ' . $codigo . ' — pago ' . $facturaCodigo,
                    (int)$adminUser['id']
                );
            }
            $pdo->commit();
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cobro registrado en el recibo ' . $codigo . '. Las facturas totalmente pagadas ya no figuran pendientes.'];
            Response::redirect('/admin/recibos/' . $reciboId);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo registrar el cobro: ' . $e->getMessage()];
            Response::redirect($back);
        }
    }

    public function ajuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');

        $clienteId = (int)($params['id'] ?? 0);
        if ($clienteId <= 0) {
            Response::redirect('/admin/ctacte');
        }

        echo View::adminPage('admin/ctacte/ajuste.php', [
            'adminUser' => $adminUser,
            'clienteId' => $clienteId,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Ajuste manual',
        ]);
    }

    public function storeAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');
        Csrf::check($_POST['_csrf'] ?? null);

        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $tipo = (string)($_POST['tipo'] ?? '');
        $montoCents = (int)($_POST['monto_cents'] ?? 0);
        $concepto = trim((string)($_POST['concepto'] ?? ''));

        if ($clienteId <= 0 || !in_array($tipo, ['debito', 'credito'], true) || $montoCents <= 0 || $concepto === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/ctacte/ajuste/' . $clienteId);
        }

        $repo = new CtaCteRepo();
        $repo->agregarMovimiento(
            $tipo,
            'ajuste',
            null,
            $clienteId,
            null,
            $montoCents,
            $concepto,
            (int)$adminUser['id']
        );

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movimiento registrado.'];
        Response::redirect('/admin/ctacte/' . $clienteId);
    }

    public function searchClientes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cta_cte');

        $q = trim((string)($_GET['q'] ?? ''));
        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   COALESCE(c.condicion_iva, \'consumidor_final\') AS condicion_iva
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.razon LIKE :like OR c.cuit LIKE :like2
            ORDER BY c.razon ASC LIMIT 10
        ');
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        $results = $st->fetchAll();

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
}
