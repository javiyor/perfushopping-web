<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\PresupuestoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PresupuestoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $list = (new PresupuestoRepo())->search($q, $estado);

        echo View::adminPage('admin/presupuestos/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Presupuestos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new PresupuestoRepo();
        $codigo = $repo->nextCodigo();

        echo View::adminPage('admin/presupuestos/form.php', [
            'adminUser' => $adminUser,
            'presupuesto' => null,
            'items' => [],
            'codigo' => $codigo,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo presupuesto',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new PresupuestoRepo();

        $clienteNombre = trim((string)($_POST['cliente_nombre'] ?? ''));
        if ($clienteNombre === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre del cliente es obligatorio.'];
            Response::redirect('/admin/presupuestos/nuevo');
        }

        $productos = $_POST['producto'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio'] ?? [];
        $variedades = $_POST['variedad'] ?? [];
        $idpros = $_POST['idprodu'] ?? [];
        $idgustos = $_POST['idcodgusto'] ?? [];
        $ivas = $_POST['iva_rate'] ?? [];

        if (!is_array($productos) || count($productos) < 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un producto.'];
            Response::redirect('/admin/presupuestos/nuevo');
        }

        $items = [];
        $subtotal = 0;
        $ivaTotal = 0;
        foreach ($productos as $idx => $prodName) {
            if (trim((string)$prodName) === '') continue;
            $qty = max(1, (int)($cantidades[$idx] ?? 1));
            $unitPrice = max(0, (int)($precios[$idx] ?? 0));
            $ivaRate = (float)($ivas[$idx] ?? 0);
            $lineTotal = $qty * $unitPrice;
            $lineIva = (int)round($lineTotal * ($ivaRate / (100 + $ivaRate)));
            $items[] = [
                'idprodu' => (int)($idpros[$idx] ?? 0) ?: null,
                'idcodgusto' => (int)($idgustos[$idx] ?? 0) ?: null,
                'producto' => trim((string)$prodName),
                'variedad' => trim((string)($variedades[$idx] ?? '')),
                'qty' => $qty,
                'unit_price_cents' => $unitPrice,
                'iva_rate' => $ivaRate,
                'total_cents' => $lineTotal,
            ];
            $subtotal += $lineTotal - $lineIva;
            $ivaTotal += $lineIva;
        }

        $codigo = $repo->nextCodigo();
        $id = $repo->create([
            'codigo' => $codigo,
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0) ?: null,
            'idclien' => null,
            'cliente_nombre' => $clienteNombre,
            'cliente_cuit' => trim((string)($_POST['cliente_cuit'] ?? '')),
            'cliente_direc' => trim((string)($_POST['cliente_direc'] ?? '')),
            'cliente_tele' => trim((string)($_POST['cliente_tele'] ?? '')),
            'cliente_mail' => trim((string)($_POST['cliente_mail'] ?? '')),
            'fecha' => (string)($_POST['fecha'] ?? date('Y-m-d')),
            'valido_hasta' => (string)($_POST['valido_hasta'] ?? ''),
            'subtotal_cents' => $subtotal,
            'iva_cents' => $ivaTotal,
            'total_cents' => $subtotal + $ivaTotal,
            'estado' => 'pendiente',
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)$adminUser['id'],
        ], $items);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Presupuesto {$codigo} creado."];
        Response::redirect('/admin/presupuestos/' . $id);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new PresupuestoRepo();
        $presupuesto = $repo->findById($id);
        if (!$presupuesto) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Presupuesto no encontrado.'];
            Response::redirect('/admin/presupuestos');
        }
        $items = $repo->items($id);

        echo View::adminPage('admin/presupuestos/detail.php', [
            'adminUser' => $adminUser,
            'presupuesto' => $presupuesto,
            'items' => $items,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Presupuesto ' . ($presupuesto['codigo'] ?? ''),
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');

        if (!in_array($estado, ['pendiente', 'aprobado', 'rechazado', 'vencido'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/presupuestos');
        }

        $repo = new PresupuestoRepo();
        $p = $repo->findById($id);
        if (!$p) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Presupuesto no encontrado.'];
            Response::redirect('/admin/presupuestos');
        }

        $repo->updateEstado($id, $estado);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/presupuestos/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::redirect('/admin/presupuestos');
        }

        (new PresupuestoRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Presupuesto eliminado.'];
        Response::redirect('/admin/presupuestos');
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new PresupuestoRepo())->searchProducts($q);

        Response::json($results);
    }

    public function searchClientes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new PresupuestoRepo())->findClienteWeb($q);

        Response::json($results);
    }
}
