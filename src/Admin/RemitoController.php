<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Repo\RemitoRepo;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipPadronService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class RemitoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $tipo = trim((string)($_GET['tipo'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $list = (new RemitoRepo())->search($q, $tipo, $estado);

        echo View::adminPage('admin/remitos/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'tipo' => $tipo,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Remitos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $tipoDefault = (string)($_GET['tipo'] ?? 'salida');
        if (!in_array($tipoDefault, ['salida', 'entrada'], true)) {
            $tipoDefault = 'salida';
        }

        $presupuesto = null;
        $presupuestoId = (int)($_GET['presupuesto_id'] ?? 0);
        if ($presupuestoId > 0) {
            $repo = new RemitoRepo();
            $p = (new \Perfushopping\Web\Repo\PresupuestoRepo())->findById($presupuestoId);
            if ($p && $p['estado'] === 'aprobado') {
                $presupuesto = $p;
                $presupuesto['items'] = (new \Perfushopping\Web\Repo\PresupuestoRepo())->items($presupuestoId);
            }
        }

        echo View::adminPage('admin/remitos/form.php', [
            'adminUser' => $adminUser,
            'remito' => null,
            'items' => [],
            'tipo' => $tipoDefault,
            'presupuesto' => $presupuesto,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => $tipoDefault === 'entrada' ? 'Nuevo remito de entrada' : 'Nuevo remito de salida',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $tipo = (string)($_POST['tipo'] ?? 'salida');
        if (!in_array($tipo, ['salida', 'entrada'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Tipo inválido.'];
            Response::redirect('/admin/remitos');
        }

        if ($tipo === 'salida') {
            $clienteNombre = trim((string)($_POST['cliente_nombre'] ?? ''));
            if ($clienteNombre === '') {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre del cliente es obligatorio.'];
                Response::redirect('/admin/remitos/nuevo?tipo=salida');
            }
        } else {
            $proveedorNombre = trim((string)($_POST['proveedor_nombre'] ?? ''));
            if ($proveedorNombre === '') {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre del proveedor es obligatorio.'];
                Response::redirect('/admin/remitos/nuevo?tipo=entrada');
            }
        }

        $productos = $_POST['producto'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $variedades = $_POST['variedad'] ?? [];
        $idpros = $_POST['idprodu'] ?? [];
        $idgustos = $_POST['idcodgusto'] ?? [];

        if (!is_array($productos) || count($productos) < 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un producto.'];
            Response::redirect('/admin/remitos/nuevo?tipo=' . $tipo);
        }

        $items = [];
        $total = 0;
        foreach ($productos as $idx => $prodName) {
            if (trim((string)$prodName) === '') continue;
            $qty = max(1, (int)($cantidades[$idx] ?? 1));
            $items[] = [
                'idprodu' => (int)($idpros[$idx] ?? 0) ?: null,
                'idcodgusto' => (int)($idgustos[$idx] ?? 0) ?: null,
                'producto' => trim((string)$prodName),
                'variedad' => trim((string)($variedades[$idx] ?? '')),
                'qty' => $qty,
            ];
            $total += $qty;
        }

        $repo = new RemitoRepo();
        $codigo = $repo->nextCodigo($tipo);
        $presupuestoId = (int)($_POST['presupuesto_id'] ?? 0) ?: null;

        $id = $repo->create([
            'codigo' => $codigo,
            'tipo' => $tipo,
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0) ?: null,
            'idclien' => null,
            'cliente_nombre' => trim((string)($_POST['cliente_nombre'] ?? '')),
            'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0) ?: null,
            'proveedor_nombre' => trim((string)($_POST['proveedor_nombre'] ?? '')),
            'presupuesto_id' => $presupuestoId,
            'fecha' => (string)($_POST['fecha'] ?? date('Y-m-d')),
            'total_cents' => $total,
            'estado' => 'pendiente',
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)$adminUser['id'],
        ], $items);

        // Deduct/add stock from session deposit
        $depoId = $auth->getDepositoId();
        if ($depoId > 0) {
            $stockRepo = new StockRepo();
            $sign = $tipo === 'entrada' ? 1 : -1;
            foreach ($items as $it) {
                $idprodu = $it['idprodu'];
                $idcodgusto = $it['idcodgusto'];
                $qty = $it['qty'];
                if ($idprodu) {
                    $stockRepo->registrarAjuste($idprodu, $idcodgusto, $depoId, $sign * $qty, 'Remito ' . $codigo, (int)$adminUser['id']);
                }
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Remito {$codigo} creado."];
        Response::redirect('/admin/remitos/' . $id);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new RemitoRepo();
        $remito = $repo->findById($id);
        if (!$remito) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Remito no encontrado.'];
            Response::redirect('/admin/remitos');
        }
        $items = $repo->items($id);

        echo View::adminPage('admin/remitos/detail.php', [
            'adminUser' => $adminUser,
            'remito' => $remito,
            'items' => $items,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Remito ' . ($remito['codigo'] ?? ''),
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

        if (!in_array($estado, ['pendiente', 'completado', 'anulado'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/remitos');
        }

        $repo = new RemitoRepo();
        $r = $repo->findById($id);
        if (!$r) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Remito no encontrado.'];
            Response::redirect('/admin/remitos');
        }

        $oldEstado = $r['estado'] ?? '';

        $repo->updateEstado($id, $estado);

        // Restore/reverse stock if remito is anulated
        if ($estado === 'anulado' && $oldEstado !== 'anulado') {
            $depoId = $auth->getDepositoId();
            if ($depoId > 0) {
                $stockRepo = new StockRepo();
                $remitoTipo = $r['tipo'] ?? 'salida';
                $sign = $remitoTipo === 'entrada' ? -1 : 1;
                $remitoItems = $repo->items($id);
                foreach ($remitoItems as $it) {
                    $idprodu = (int)($it['idprodu'] ?? 0);
                    $idcodgusto = (int)($it['idcodgusto'] ?? 0) ?: null;
                    $qty = (int)($it['qty'] ?? 0);
                    if ($idprodu) {
                        $stockRepo->registrarAjuste($idprodu, $idcodgusto, $depoId, $sign * $qty, 'Anulación Remito ' . ($r['codigo'] ?? ''), (int)$adminUser['id']);
                    }
                }
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/remitos/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::redirect('/admin/remitos');
        }

        (new RemitoRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Remito eliminado.'];
        Response::redirect('/admin/remitos');
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new RemitoRepo())->searchProducts($q);

        Response::json($results);
    }

    public function searchClientes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new RemitoRepo();
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

    public function searchPresupuestos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new RemitoRepo())->findPresupuestosDisponibles($q);

        Response::json($results);
    }

    public function searchProveedores(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new RemitoRepo())->findProveedores($q);

        Response::json($results);
    }
}
