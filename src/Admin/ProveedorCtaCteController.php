<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CtaCteProveedorRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProveedorCtaCteController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new CtaCteProveedorRepo();
        try {
            $repo->normalizarNotasCreditoCompra();
            $repo->sincronizarAsignaciones();
        } catch (\Throwable $e) {
            error_log('ctacte proveedor asignaciones: ' . $e->getMessage());
        }
        $list = $repo->listarConSaldo($q);
        $vencidos = $repo->resumenVencidoPorProveedor(array_map('intval', array_column($list, 'proveedor_id')));
        $saldoTotal = 0;
        foreach ($list as &$item) {
            $saldoTotal += (int)($item['saldo_cents'] ?? 0);
            $vencido = $vencidos[(int)($item['proveedor_id'] ?? 0)] ?? [];
            $item['vencido_cents'] = (int)($vencido['vencido_cents'] ?? 0);
            $item['credito_pendiente_cents'] = (int)($vencido['credito_pendiente_cents'] ?? 0);
            $item['vencido_neto_cents'] = (int)($vencido['vencido_neto_cents'] ?? 0);
        }
        unset($item);

        echo View::adminPage('admin/proveedores/ctacte/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'saldoTotal' => $saldoTotal,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta Cte — Proveedores',
        ]);
    }

    public function sincronizar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $res = (new CtaCteProveedorRepo())->sincronizarCompras((int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Sincronizadas ' . $res['insertadas'] . ' facturas de compra con cuenta corriente.' . ($res['omitidas'] > 0 ? ' Omitidas ' . $res['omitidas'] . ' sin importe.' : '')];
        Response::redirect('/admin/proveedores/ctacte');
    }

    public function guardarAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $proveedorId = (int)($_POST['proveedor_id'] ?? 0);
        $tipo = trim((string)($_POST['tipo'] ?? ''));
        $concepto = trim((string)($_POST['concepto'] ?? ''));
        $rawMonto = trim((string)($_POST['monto'] ?? '0'));
        if (str_contains($rawMonto, ',') && str_contains($rawMonto, '.')) {
            $rawMonto = strrpos($rawMonto, ',') > strrpos($rawMonto, '.')
                ? str_replace(',', '.', str_replace('.', '', $rawMonto))
                : str_replace(',', '', $rawMonto);
        } else {
            $rawMonto = str_replace(',', '.', $rawMonto);
        }
        $montoCents = is_numeric($rawMonto) ? (int)round((float)$rawMonto * 100) : 0;
        $proveedor = $proveedorId > 0 ? (new \Perfushopping\Web\Repo\ProveedorRepo())->findById($proveedorId) : null;

        if (!$proveedor || !in_array($tipo, ['debito', 'credito'], true) || $montoCents <= 0 || $concepto === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá tipo, importe y concepto del ajuste.'];
            Response::redirect('/admin/proveedores/ctacte/' . max(1, $proveedorId));
        }

        (new CtaCteProveedorRepo())->agregarMovimiento(
            $tipo,
            'ajuste',
            null,
            $proveedorId,
            (string)($proveedor['razon'] ?? ''),
            $montoCents,
            $concepto,
            (int)$adminUser['id']
        );
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Ajuste registrado en la cuenta corriente.'];
        Response::redirect('/admin/proveedores/ctacte/' . $proveedorId);
    }

    public function movimientos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');

        $proveedorId = isset($params['id']) ? (int)$params['id'] : null;
        $q = trim((string)($_GET['q'] ?? ''));
        $filtro = trim((string)($_GET['filtro'] ?? 'pendientes'));
        if (!in_array($filtro, ['pendientes', 'todos'], true)) {
            $filtro = 'pendientes';
        }

        $repo = new CtaCteProveedorRepo();
        try {
            $repo->normalizarNotasCreditoCompra();
            // Backfill idempotente: asigna OPs viejas (sin orden_pago_compras) a facturas impagas
            $repo->sincronizarAsignaciones($proveedorId);
        } catch (\Throwable $e) {
            error_log('sincronizarAsignaciones: ' . $e->getMessage());
        }
        $movimientos = $repo->movimientos($proveedorId, $q);
        $saldo = $repo->saldoActual($proveedorId);
        $comprobantes = $proveedorId !== null ? $repo->comprobantesConPlazo($proveedorId) : [];
        $vencidoBrutoCents = array_sum(array_column($comprobantes, 'vencido_cents'));
        $resumenVencido = $proveedorId !== null ? ($repo->resumenVencidoPorProveedor([$proveedorId])[$proveedorId] ?? []) : [];
        $creditoPendienteCents = (int)($resumenVencido['credito_pendiente_cents'] ?? 0);
        $vencidoNetoCents = max(0, $vencidoBrutoCents - $creditoPendienteCents);
        // Predeterminado: solo pendientes (oculta las pagadas/imputadas); ?filtro=todos las muestra.
        if ($filtro === 'pendientes') {
            $comprobantes = array_values(array_filter($comprobantes, static fn (array $c): bool => ($c['estado'] ?? '') !== 'Pagada'));
        }

        $proveedorNombre = '';
        if ($movimientos) {
            $proveedorNombre = $movimientos[0]['proveedor_nombre'] ?? '';
        }

        echo View::adminPage('admin/proveedores/ctacte/show.php', [
            'adminUser' => $adminUser,
            'movimientos' => $movimientos,
            'comprobantes' => $comprobantes,
            'vencidoBrutoCents' => $vencidoBrutoCents,
            'creditoPendienteCents' => $creditoPendienteCents,
            'vencidoNetoCents' => $vencidoNetoCents,
            'proveedorId' => $proveedorId,
            'proveedorNombre' => $proveedorNombre,
            'saldo' => $saldo,
            'q' => $q,
            'filtro' => $filtro,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta Cte — ' . ($proveedorNombre ?: 'Proveedores'),
        ]);
    }
}
