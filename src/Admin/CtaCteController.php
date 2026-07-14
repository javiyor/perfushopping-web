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
        $adminUser = $auth->requireSesion();

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
        $adminUser = $auth->requireSesion();

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

    public function ajuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

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
        $adminUser = $auth->requireSesion();
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
        $adminUser = $auth->requireSesion();

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
