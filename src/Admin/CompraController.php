<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\CompraRepo;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\ArcaQrParser;
use Perfushopping\Web\Service\ExcelReader;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CompraController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $list = (new CompraRepo())->findAll([
            'q' => trim((string)($_GET['q'] ?? '')),
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'desde' => trim((string)($_GET['desde'] ?? '')),
            'hasta' => trim((string)($_GET['hasta'] ?? '')),
        ]);

        echo View::adminPage('admin/compras/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => (string)($_GET['q'] ?? ''),
            'estado' => (string)($_GET['estado'] ?? ''),
            'desde' => (string)($_GET['desde'] ?? ''),
            'hasta' => (string)($_GET['hasta'] ?? ''),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Facturas de compra',
        ]);
        unset($_SESSION['admin_flash']);
    }

    // ── Importación desde Excel/CSV de ARCA ──

    public function importar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $rows = $_SESSION['compra_import']['rows'] ?? null;
        if (is_array($rows)) {
            unset($_SESSION['compra_import']);
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al subir el archivo.'];
            Response::redirect('/admin/compras/importar');
        }

        $tmp = $_FILES['archivo']['tmp_name'];
        try {
            $parsed = (new ExcelReader())->readRows((string)$tmp);
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo leer el archivo: ' . $e->getMessage()];
            Response::redirect('/admin/compras/importar');
        }

        if (!$parsed) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El archivo no contiene filas de datos.'];
            Response::redirect('/admin/compras/importar');
        }

        $repo = new CompraRepo();
        $mapped = [];
        foreach ($parsed as $i => $r) {
            $row = $this->mapRow($r);
            if ($row['cuit'] === '' && $row['tipo'] === '' && $row['numero'] === '') {
                continue;
            }
            $row['dup'] = $row['numero'] !== '' && $repo->existsComprobante($row['cuit'], $row['tipo'], $row['punto_venta'], $row['numero']);
            $row['proveedor_match'] = $repo->proveedorByCuit($row['cuit']) ? true : false;
            $mapped[] = $row;
            if (count($mapped) >= 2000) {
                break;
            }
        }

        if (!$mapped) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se reconocieron comprobantes en el archivo. Verificá las columnas.'];
            Response::redirect('/admin/compras/importar');
        }

        $_SESSION['compra_import']['rows'] = $mapped;
        $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Se leyeron ' . count($mapped) . ' comprobantes. Marcá los que querés importar y confirmá.'];

        echo View::adminPage('admin/compras/import.php', [
            'adminUser' => $adminUser,
            'rows' => $mapped,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Importar comprobantes de compra',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function importConfirm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $rows = $_SESSION['compra_import']['rows'] ?? null;
        unset($_SESSION['compra_import']);
        if (!is_array($rows)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay datos de importación. Volvé a subir el archivo.'];
            Response::redirect('/admin/compras/importar');
        }

        $selected = $_POST['sel'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = array_map('intval', array_values($selected));

        $repo = new CompraRepo();
        $inserted = 0;
        $skipped = 0;
        foreach ($rows as $i => $row) {
            if (!in_array($i, $selected, true)) {
                continue;
            }
            if ($row['numero'] !== '' && $repo->existsComprobante($row['cuit'], $row['tipo'], $row['punto_venta'], $row['numero'])) {
                $skipped++;
                continue;
            }
            $idprovee = $repo->proveedorEnsure($row['cuit'], $row['razon']);
            $repo->insert([
                'origen' => 'excel',
                'estado' => 'pendiente',
                'fecha' => $row['fecha'],
                'tipo' => $row['tipo'],
                'punto_venta' => $row['punto_venta'],
                'numero_desde' => $row['numero'],
                'numero_hasta' => $row['numero'],
                'cod_autorizacion' => $row['cae'],
                'cuit_proveedor' => $row['cuit'],
                'razon_proveedor' => $row['razon'],
                'idprovee' => $idprovee,
                'moneda' => $row['moneda'] !== '' ? $row['moneda'] : 'PES',
                'tipo_cambio' => $row['tipo_cambio'] > 0 ? $row['tipo_cambio'] : 1,
                'imp_neto_gravado' => $row['neto'],
                'imp_no_gravado' => $row['no_gravado'],
                'imp_exento' => $row['exento'],
                'otros_tributos' => $row['tributos'],
                'imp_iva' => $row['iva'],
                'imp_total' => $row['total'],
                'created_by' => (int)$adminUser['id'],
            ]);
            $inserted++;
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Importadas $inserted facturas de compra." . ($skipped > 0 ? " Se omitieron $skipped duplicadas." : '')];
        Response::redirect('/admin/compras');
    }

    // ── QR ARCA ──

    public function qr(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $text = trim((string)($_POST['qr'] ?? ''));
        try {
            $qr = (new ArcaQrParser())->parse($text);
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'QR inválido: ' . $e->getMessage()];
            Response::redirect('/admin/compras/nueva');
        }

        $repo = new CompraRepo();
        $prov = $repo->proveedorByCuit($qr['cuit']);
        $_SESSION['compra_prefill'] = [
            'fecha' => $qr['fecha'] ?? date('Y-m-d'),
            'tipo' => $qr['tipo'],
            'punto_venta' => $qr['punto_venta'],
            'numero_desde' => $qr['numero'],
            'numero_hasta' => $qr['numero'],
            'cuit_proveedor' => $qr['cuit'],
            'razon_proveedor' => $prov ? (string)$prov['razon'] : $qr['razon'],
            'imp_total' => (string)$qr['imp_total'],
            'origen' => 'qr',
        ];
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Datos del QR cargados. Completá el resto y guardá.'];
        Response::redirect('/admin/compras/nueva');
    }

    // ── Alta / edición (manual, QR o completar importada) ──

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $prefill = $_SESSION['compra_prefill'] ?? [];
        unset($_SESSION['compra_prefill']);

        $compra = $prefill + [
            'fecha' => date('Y-m-d'),
            'origen' => 'manual',
            'moneda' => 'PES',
            'tipo_cambio' => 1,
        ];

        echo View::adminPage('admin/compras/form.php', $this->formData($adminUser, $compra, []));
    }

    public function edit(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new CompraRepo();
        $compra = $repo->findById($id);
        if (!$compra) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura de compra no encontrada.'];
            Response::redirect('/admin/compras');
        }

        echo View::adminPage('admin/compras/form.php', $this->formData($adminUser, $compra, $repo->items($id)));
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new CompraRepo();
        $compra = $repo->findById($id);
        if (!$compra) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura de compra no encontrada.'];
            Response::redirect('/admin/compras');
        }

        echo View::adminPage('admin/compras/detail.php', [
            'adminUser' => $adminUser,
            'compra' => $compra,
            'items' => $repo->items($id),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Factura de compra #' . $id,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new CompraRepo();
        $id = (int)($_POST['id'] ?? 0);

        $cuit = preg_replace('/\D/', '', (string)($_POST['cuit_proveedor'] ?? ''));
        $razon = trim((string)($_POST['razon_proveedor'] ?? ''));
        $idprovee = $cuit !== '' ? $repo->proveedorEnsure($cuit, $razon) : null;

        // Cuenta contable: existente o crear subcuenta
        $idcta1 = (int)($_POST['idcta1'] ?? 0);
        $nuevaSub = trim((string)($_POST['nueva_subcuenta'] ?? ''));
        $idcta = (int)($_POST['idcta'] ?? 0);
        if ($idcta1 <= 0 && $nuevaSub !== '' && $idcta > 0) {
            $idcta1 = $repo->crearSubcuenta($nuevaSub, $idcta);
        }

        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $data = [
            'origen' => (string)($_POST['origen'] ?? 'manual'),
            'fecha' => $fecha !== '' ? $fecha : date('Y-m-d'),
            'tipo' => trim((string)($_POST['tipo'] ?? '')),
            'punto_venta' => trim((string)($_POST['punto_venta'] ?? '')),
            'numero_desde' => trim((string)($_POST['numero_desde'] ?? '')),
            'numero_hasta' => trim((string)($_POST['numero_hasta'] ?? '')) ?: trim((string)($_POST['numero_desde'] ?? '')),
            'cod_autorizacion' => trim((string)($_POST['cod_autorizacion'] ?? '')),
            'cuit_proveedor' => $cuit,
            'razon_proveedor' => $razon !== '' ? $razon : (string)($_POST['razon_proveedor'] ?? ''),
            'idprovee' => $idprovee,
            'moneda' => trim((string)($_POST['moneda'] ?? 'PES')) ?: 'PES',
            'tipo_cambio' => (float)($_POST['tipo_cambio'] ?? 1),
            'imp_neto_gravado' => (float)($_POST['imp_neto_gravado'] ?? 0),
            'imp_no_gravado' => (float)($_POST['imp_no_gravado'] ?? 0),
            'imp_exento' => (float)($_POST['imp_exento'] ?? 0),
            'otros_tributos' => (float)($_POST['otros_tributos'] ?? 0),
            'imp_iva' => (float)($_POST['imp_iva'] ?? 0),
            'imp_total' => (float)($_POST['imp_total'] ?? 0),
            'idcta1' => $idcta1,
            'iddepo' => (int)($_POST['iddepo'] ?? 0),
            'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
        ];

        $items = $this->parseItems();

        if ($id > 0) {
            $wasCompleta = ((string)($repo->findById($id)['estado'] ?? '') === 'completa');
            $data['estado'] = $items ? 'completa' : ($wasCompleta ? 'completa' : 'pendiente');
            $repo->update($id, $data);
            $compraId = $id;
        } else {
            $data['estado'] = $items ? 'completa' : 'pendiente';
            $data['created_by'] = (int)$adminUser['id'];
            $compraId = $repo->insert($data);
        }

        if ($items) {
            $wasCompleta = $wasCompleta ?? false;
            if ($id > 0 && $wasCompleta) {
                // Ya aplicada: solo persistimos los ítems sin volver a sumar stock.
                $repo->reemplazarItems($compraId, $items);
            } else {
                $iddepo = (int)$data['iddepo'] > 0 ? (int)$data['iddepo'] : $repo->depositoPrincipal();
                $repo->aplicarItems($compraId, $items, $iddepo, (string)$data['fecha'], 'Compra ' . (string)$data['tipo'] . ' ' . (string)$data['punto_venta'] . '-' . (string)$data['numero_desde']);
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Factura de compra guardada.'];
        Response::redirect('/admin/compras/' . $compraId);
    }

    public function setCuenta(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $idcta1 = (int)($_POST['idcta1'] ?? 0);
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $i): bool => $i > 0));

        if (!$ids || $idcta1 <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná comprobantes y una cuenta contable.'];
            Response::redirect('/admin/compras');
        }

        $repo = new CompraRepo();
        $in = implode(',', $ids);
        Db::pdo()->prepare("UPDATE factura_compra SET idcta1 = :c WHERE id IN ($in)")->execute([':c' => $idcta1]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cuenta contable asignada a ' . count($ids) . ' comprobantes.'];
        Response::redirect('/admin/compras');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new CompraRepo())->delete($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Factura de compra eliminada.'];
        }
        Response::redirect('/admin/compras');
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $prod = (new StockRepo())->searchProducts($q, 15);
        $out = [];
        foreach ($prod as $p) {
            $variants = (new StockRepo())->variantesPorProducto((int)$p['idprodu']);
            $out[] = [
                'idprodu' => (int)$p['idprodu'],
                'codprodu' => (string)($p['codprodu'] ?? ''),
                'produ' => (string)($p['produ'] ?? ''),
                'precomp' => (float)($p['precomp'] ?? 0),
                'precio' => (float)($p['precio'] ?? 0),
                'variants' => $variants,
            ];
        }
        Response::json($out);
    }

    /** @param array<string,mixed> $compra
     *  @return array<string,mixed>
     */
    private function formData(array $adminUser, array $compra, array $items): array
    {
        $repo = new CompraRepo();
        return [
            'adminUser' => $adminUser,
            'compra' => $compra,
            'items' => $items,
            'cuentas' => $repo->cuentas(),
            'cuentasGrupo' => $repo->cuentasGrupo(),
            'depositos' => $repo->depositosVenta(),
            'depositoDefault' => $repo->depositoPrincipal(),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => (int)($compra['id'] ?? 0) > 0 ? 'Editar factura de compra' : 'Nueva factura de compra',
        ];
    }

    /** @param array<string,string> $r
     *  @return array<string,mixed>
     */
    private function mapRow(array $r): array
    {
        $v = static fn (string $key): string => (string)($r[$key] ?? '');

        $fecha = ExcelReader::toDate($v('fecha') !== '' ? $v('fecha') : $v('fechacomprobante'));
        $tipo = $v('tipo');
        $pv = $v('puntodeventa');
        $numero = $v('numerodesde') !== '' ? $v('numerodesde') : $v('numero');
        $cuit = ExcelReader::digits($v('nrodocemisor') !== '' ? $v('nrodocemisor') : $v('cuit'));
        $razon = $v('denominacionemisor') !== '' ? $v('denominacionemisor') : $v('denominacion');

        return [
            'fecha' => $fecha,
            'tipo' => $tipo,
            'punto_venta' => $pv,
            'numero' => $numero,
            'cae' => $v('codautorizacion') !== '' ? $v('codautorizacion') : $v('cae'),
            'cuit' => $cuit,
            'razon' => $razon,
            'moneda' => $v('moneda'),
            'tipo_cambio' => ExcelReader::toFloat($v('tipocambio')),
            'neto' => ExcelReader::toFloat($v('impnetogravado') !== '' ? $v('impnetogravado') : $v('netogravado')),
            'no_gravado' => ExcelReader::toFloat($v('impnetonogravado') !== '' ? $v('impnetonogravado') : $v('netonogravado')),
            'exento' => ExcelReader::toFloat($v('impopexentas') !== '' ? $v('impopexentas') : $v('exento')),
            'tributos' => ExcelReader::toFloat($v('otrostributos') !== '' ? $v('otrostributos') : $v('otrostributos')),
            'iva' => ExcelReader::toFloat($v('iva') !== '' ? $v('iva') : $v('importeiva')),
            'total' => ExcelReader::toFloat($v('imptotal') !== '' ? $v('imptotal') : $v('importetotal')),
            'dup' => false,
            'proveedor_match' => false,
        ];
    }

    /** @return array<int, array{idprodu:int, idcodgusto:?int, product_name:string, qty:float, unit_cost:float}> */
    private function parseItems(): array
    {
        $ids = $_POST['item_idprodu'] ?? [];
        if (!is_array($ids)) {
            return [];
        }
        $gustos = $_POST['item_idcodgusto'] ?? [];
        $names = $_POST['item_name'] ?? [];
        $qtys = $_POST['item_qty'] ?? [];
        $costs = $_POST['item_cost'] ?? [];

        $out = [];
        foreach ($ids as $i => $idprodu) {
            $idprodu = (int)$idprodu;
            if ($idprodu <= 0) {
                continue;
            }
            $out[] = [
                'idprodu' => $idprodu,
                'idcodgusto' => isset($gustos[$i]) && (int)$gustos[$i] > 0 ? (int)$gustos[$i] : null,
                'product_name' => (string)($names[$i] ?? ''),
                'qty' => (float)str_replace(',', '.', (string)($qtys[$i] ?? '1')),
                'unit_cost' => ExcelReader::toFloat((string)($costs[$i] ?? '0')),
            ];
        }
        return $out;
    }
}
