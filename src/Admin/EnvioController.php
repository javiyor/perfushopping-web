<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class EnvioController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        $repo = new FacturaRepo();
        $estado = trim((string)($_GET['estado'] ?? ''));
        if (!in_array($estado, ['pendiente','en_transito','entregado','cancelado'], true)) $estado = '';
        $list = $estado ? $repo->listarEnvios($estado) : $repo->listarEnvios();
        // filter pendientes default
        if (!$estado) {
            $list = array_values(array_filter($list, fn($r)=> in_array($r['envio_estado'] ?? '', ['pendiente','en_transito'], true)));
        }
        echo View::adminPage('admin/envios/index.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Envíos',
        ]);
    }

    public function entregar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { Response::redirect('/admin/envios'); }
        $repo = new FacturaRepo();
        $factura = $repo->findById($id);
        if (!$factura || ($factura['entrega_tipo'] ?? '') !== 'envio') {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Envío no encontrado.'];
            Response::redirect('/admin/envios');
        }
        $repo->marcarEnvioEntregado($id, (int)$adminUser['id']);

        // Si era efectivo contra entrega, ahora impacta en caja: registrar movimiento
        $forma = $factura['forma_pago'] ?? '';
        $pagos = $repo->pagos($id);
        $efectivo = 0;
        foreach ($pagos as $p) if (($p['forma_pago'] ?? '') === 'efectivo') $efectivo += (int)($p['monto_cents'] ?? 0);
        if ($efectivo <= 0) $efectivo = (int)($factura['total_cents'] ?? 0);

        if ($forma === 'efectivo' || $efectivo > 0) {
            // intentar agregar a caja chica si hay apertura, sino a caja general
            $cajaRepo = new CajaRepo();
            $sucursalId = $auth->getSucursalId();
            $turno = $auth->getTurno();
            $fecha = date('Y-m-d');
            $apertura = $cajaRepo->aperturaActiva($sucursalId, $turno, $fecha);
            if ($apertura) {
                $cajaRepo->agregarMovimiento((int)$apertura['id'], 'ingreso', 'Cobro envío #' . ($factura['codigo'] ?? $id), $efectivo, (int)$adminUser['id']);
            } else {
                $cajaRepo->agregarMovimientoGeneral('ingreso', 'envio', $id, 'Cobro envío #' . ($factura['codigo'] ?? $id), $efectivo, (int)$adminUser['id']);
            }
        }

        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Envío marcado como entregado y cobrado.'];
        Response::redirect('/admin/envios');
    }

    public function cancelar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        (new FacturaRepo())->marcarEnvioEstado($id, 'cancelado');
        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Envío cancelado.'];
        Response::redirect('/admin/envios');
    }
}
