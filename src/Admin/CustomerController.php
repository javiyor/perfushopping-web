<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Repo\CustomerRepo;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipPadronService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CustomerController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new CustomerRepo())->search($q);

        echo View::adminPage('admin/clientes/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Clientes',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function detail(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new CustomerRepo();

        $customer = $repo->findById($id);
        if (!$customer) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente no encontrado.'];
            Response::redirect('/admin/clientes');
        }

        $orders = $repo->orders($id);

        $itemsByOrder = [];
        foreach ($orders as $o) {
            $oid = (int)($o['id'] ?? 0);
            if ($oid > 0) {
                $itemsByOrder[$oid] = $repo->orderItems($oid);
            }
        }

        $clienteErp = null;
        if (!empty($customer['cliente_id'])) {
            $clienteErp = $repo->clienteErp((int)$customer['cliente_id']);
        }

        $notas = $repo->notas($id);

        echo View::adminPage('admin/clientes/detail.php', [
            'adminUser' => $adminUser,
            'customer' => $customer,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'clienteErp' => $clienteErp,
            'notas' => $notas,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Cliente: ' . htmlspecialchars(mb_substr((string)($customer['name'] ?? $customer['email'] ?? ''), 0, 40)),
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function addNota(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $userId = (int)($_POST['user_id'] ?? 0);
        $texto = trim((string)($_POST['nota'] ?? ''));

        if ($userId <= 0 || $texto === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para la nota.'];
            Response::redirect('/admin/clientes/' . $userId);
        }

        (new CustomerRepo())->addNota($userId, (int)$adminUser['id'], $texto);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Nota agregada.'];
        Response::redirect('/admin/clientes/' . $userId);
    }

    public function buscarArca(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            Response::json(['ok' => false, 'error' => 'Ingresá un CUIT o DNI.']);
            return;
        }

        $cuitsToTry = [];
        if (preg_match('/^\d{11}$/', $q)) {
            $cuitsToTry[] = $q;
        } elseif (preg_match('/^\d{7,8}$/', $q)) {
            $cuitsToTry = AfipPadronService::dniToCuits($q);
        } else {
            Response::json(['ok' => false, 'error' => 'Formato inválido. Ingresá un CUIT (11 dígitos) o DNI (7-8 dígitos).']);
            return;
        }

        $arcaRepo = new ArcaRepo();
        if (!$arcaRepo->isHabilitado()) {
            Response::json(['ok' => false, 'error' => 'ARCA no está habilitado. Configuralo en Admin → ARCA.']);
            return;
        }

        $padron = new AfipPadronService();
        foreach ($cuitsToTry as $cuit) {
            try {
                $persona = $padron->consultar($cuit);
                if ($persona) {
                    Response::json(['ok' => true, 'persona' => $persona]);
                    return;
                }
            } catch (\Throwable $e) {
                error_log('ARCA padron error: ' . $e->getMessage());
            }
        }

        Response::json(['ok' => false, 'error' => 'No se encontraron datos en ARCA para el CUIT/DNI ingresado.']);
    }

    public function crearDesdeArca(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $cuit = trim((string)($_POST['cuit'] ?? ''));
        $razon = trim((string)($_POST['razon'] ?? ''));
        $direc = trim((string)($_POST['direc'] ?? ''));
        $localidad = trim((string)($_POST['localidad'] ?? ''));
        $provincia = trim((string)($_POST['provincia'] ?? ''));
        $condicionIva = trim((string)($_POST['condicion_iva'] ?? 'consumidor_final'));

        if ($cuit === '' || $razon === '') {
            Response::json(['ok' => false, 'error' => 'Faltan datos requeridos (CUIT y Razón Social).']);
            return;
        }

        // Create/update clientes table
        $facturaRepo = new FacturaRepo();
        $cliente = $facturaRepo->upsertClienteArca([
            'cuit' => $cuit,
            'razon' => $razon,
            'direc' => $direc,
            'localidad' => $localidad,
            'provincia' => $provincia,
            'condicion_iva' => $condicionIva,
        ]);

        if (!$cliente) {
            Response::json(['ok' => false, 'error' => 'Error al crear el registro en clientes.']);
            return;
        }

        // Check if web_user already exists for this clientes id
        $idclien = (int)$cliente['idclien'];
        $st = Db::pdo()->prepare('SELECT id FROM web_users WHERE cliente_id = :c LIMIT 1');
        $st->execute([':c' => $idclien]);
        $existing = $st->fetch();

        if ($existing) {
            Response::json(['ok' => true, 'user_id' => (int)$existing['id'], 'cliente_id' => $idclien, 'message' => 'Cliente ya existente, datos actualizados.']);
            return;
        }

        // Create web_user
        $email = strtolower(preg_replace('/\D/', '', $cuit)) . '@sinemail.com';
        $st = Db::pdo()->prepare('
            INSERT INTO web_users (name, email, phone, cliente_id, role, created_at)
            VALUES (:n, :e, :p, :c, \'customer\', NOW())
        ');
        $st->execute([
            ':n' => $razon,
            ':e' => $email,
            ':p' => '',
            ':c' => $idclien,
        ]);
        $userId = (int)Db::pdo()->lastInsertId();

        Response::json(['ok' => true, 'user_id' => $userId, 'cliente_id' => $idclien, 'message' => 'Cliente creado correctamente.']);
    }
}
