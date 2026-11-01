<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ImportRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ImportController
{
    private ImportRepo $repo;
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->repo = new ImportRepo();
        $this->auth = new AdminAuthService();
    }

    public function form(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('productos');

        // El preview debe persistir en sesión hasta confirmar o cancelar,
        // porque el confirm lo lee en un request posterior.
        if (isset($_GET['cancelar'])) {
            unset($_SESSION['import_preview']);
            Response::redirect('/admin/productos/importar');
        }

        $preview = $_SESSION['import_preview'] ?? null;
        $stats = $_SESSION['import_stats'] ?? null;
        unset($_SESSION['import_stats']);

        $proveedores = [];
        $productosSinCosto = [];
        try {
            $proveedores = (new \Perfushopping\Web\Repo\ProveedorRepo())->findAll();
        } catch (\Throwable $e) {
        }
        try {
            $productosSinCosto = $this->repo->productosConStockSinCosto();
        } catch (\Throwable $e) {
        }

        echo View::adminPage('admin/productos/import.php', [
            'adminUser' => $adminUser,
            'preview' => $preview,
            'stats' => $stats,
            'proveedores' => $proveedores,
            'productosSinCosto' => $productosSinCosto,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Importar productos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function preview(array $params): void
    {
        $this->auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Selecciona un archivo CSV valido.'];
            Response::redirect('/admin/productos/importar');
        }

        $tmp = (string)($_FILES['csv_file']['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Archivo subido invalido.'];
            Response::redirect('/admin/productos/importar');
        }

        $rows = $this->parseCsv($tmp);
        $modo = (string)($_POST['modo_precio'] ?? (isset($_POST['precios_con_iva']) ? 'con_iva' : 'neto'));
        if (!in_array($modo, ['neto', 'con_iva', 'costo'], true)) {
            $modo = 'neto';
        }
        $conIva = $modo === 'con_iva';
        $soloCosto = $modo === 'costo';
        if (!$rows) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $soloCosto
                ? 'No se pudieron leer filas del CSV. Verifica que tenga codscan o codprodup y la columna costo_sin_iva. Para buscar por codprodup, selecciona el proveedor.'
                : ($conIva
                    ? 'No se pudieron leer filas del CSV. Verifica que tenga codscan o codprodup y la columna precio_con_iva (costo_con_iva es opcional). Si es un Excel (.xlsx), guardalo como CSV primero.'
                    : 'No se pudieron leer filas del CSV. Verifica que tenga codprodup o codscan y las columnas de precios correspondientes. Si es un Excel (.xlsx), guardalo como CSV primero.')];
            Response::redirect('/admin/productos/importar');
        }

        $idprovee = (int)($_POST['idprovee'] ?? 0);
        $proveedorNombre = '';
        if ($idprovee > 0) {
            try {
                $prov = (new \Perfushopping\Web\Repo\ProveedorRepo())->findById($idprovee);
                $proveedorNombre = $prov ? (string)($prov['razon'] ?? '') : '';
            } catch (\Throwable $e) {
            }
            if ($proveedorNombre === '') {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Proveedor inválido.'];
                Response::redirect('/admin/productos/importar');
            }
        }

        $results = [];
        $found = 0;
        $notFound = 0;

        foreach ($rows as $idx => $row) {
            $codprodup = trim((string)($row['codprodup'] ?? ''));
            $codscan = trim((string)($row['codscan'] ?? ''));
            if ($soloCosto) {
                // Prioriza codscan; codprodup solo se usa como respaldo con proveedor seleccionado.
                // Minorista/mayorista se calculan con los márgenes existentes.
                $precioConIvaNew = null;
                $costoConIvaNew = null;
                $precioNew = null;
                $costoNew = $this->parseFloat((string)($row['costo_sin_iva'] ?? $row['costo'] ?? ''));
                $ganan1New = null;
                $ganan2New = null;
                $precio1New = null;
                $match = null;
                if ($codscan !== '') {
                    $match = $this->repo->findByCodscan($codscan, $idprovee);
                }
                if ($match === null && $codprodup !== '' && $idprovee > 0) {
                    $match = $this->repo->findByCodprodup($codprodup, $idprovee);
                    if ($match) {
                        $match['_match_type'] = 'codprodup';
                    }
                }
            } elseif ($conIva) {
                // Modo precios con IVA: se busca por código de barra de la variedad y
                // los netos se calculan contra IVA y márgenes existentes dentro del bloque matched.
                $precioConIvaNew = $this->parseFloat((string)($row['precio_con_iva'] ?? $row['precio_iva'] ?? ''));
                $costoConIvaNew = $this->parseFloat((string)($row['costo_con_iva'] ?? ''));
                $precioNew = null;
                $costoNew = null;
                $ganan1New = null;
                $ganan2New = null;
                $precio1New = null;
                $match = null;
                if ($codscan !== '') {
                    $match = $this->repo->findByCodscan($codscan, $idprovee);
                }
                if ($match === null && $codprodup !== '') {
                    $match = $this->repo->findByCodprodup($codprodup, $idprovee);
                    if ($match) {
                        $match['_match_type'] = 'codprodup';
                    }
                }
            } else {
                $precioNew = $this->parseFloat((string)($row['precio_sin_iva'] ?? ''));
                $costoNew = $this->parseFloat((string)($row['costo_sin_iva'] ?? ''));
                $ganan1New = $this->parseFloat((string)($row['ganan1'] ?? ''));
                $ganan2New = $this->parseFloat((string)($row['ganan2'] ?? ''));
                $precio1New = $this->parseFloat((string)($row['precio1_sin_iva'] ?? ''));
                $precioConIvaNew = null;
                $costoConIvaNew = null;
                $match = $this->repo->findByCodprodupOrCodscan($codprodup, $codscan, $idprovee);
            }

            $item = [
                'row' => $idx + 2,
                'codprodup' => $codprodup,
                'codscan' => $codscan,
                'precio_new' => $precioNew,
                'costo_new' => $costoNew,
                'ganan1_new' => $ganan1New,
                'ganan2_new' => $ganan2New,
                'precio1_new' => $precio1New,
                'precio_con_iva_new' => $precioConIvaNew ?? null,
                'costo_con_iva_new' => $costoConIvaNew ?? null,
                'matched' => $match !== null,
            ];

            if ($match) {
                $found++;
                $item['idprodu'] = (int)$match['idprodu'];
                $item['producto'] = (string)($match['produ'] ?? '');
                $item['codprodu'] = (string)($match['codprodu'] ?? '');
                $item['marca'] = (string)($match['nomsub'] ?? '');
                $item['categoria'] = (string)($match['nomrub'] ?? '');
                $item['match_type'] = (string)($match['_match_type'] ?? '');
                $item['variedad'] = (string)($match['_nomgusto'] ?? '');
                $item['idcodgusto'] = (int)($match['_idcodgusto'] ?? 0);

                $ivaRate = (float)($match['tiva'] ?? 0);
                $precioOld = (float)($match['precio'] ?? 0);
                $precio1Old = (float)($match['precio1'] ?? 0);
                $costoOld = (float)($match['precomp'] ?? 0);
                $ganan1Old = (float)($match['ganan1'] ?? 0);
                $ganan2Old = (float)($match['ganan2'] ?? 0);

                $item['precio_old'] = $precioOld;
                $item['precio_old_gross'] = $precioOld * (1 + $ivaRate / 100);
                $item['precio_new_gross'] = $precioNew !== null ? $precioNew * (1 + $ivaRate / 100) : null;
                $item['precio1_old'] = $precio1Old;
                $item['precio1_old_gross'] = $precio1Old * (1 + $ivaRate / 100);
                $item['precio1_new_gross'] = $precio1New !== null ? $precio1New * (1 + $ivaRate / 100) : null;
                $item['costo_old'] = $costoOld;
                $item['ganan1_old'] = $ganan1Old;
                $item['ganan2_old'] = $ganan2Old;
                $item['iva_rate'] = $ivaRate;

                if ($conIva) {
                    // El Excel trae el precio minorista con IVA: se guarda el neto y se
                    // recalculan costo y mayorista con los márgenes ya cargados (ganan1/ganan2).
                    $divisor = 1 + $ivaRate / 100;
                    if ($precioConIvaNew !== null && $divisor > 0) {
                        $precioNew = round($precioConIvaNew / $divisor, 2);
                    }
                    if ($costoConIvaNew !== null && $divisor > 0) {
                        $costoNew = round($costoConIvaNew / $divisor, 2);
                    } elseif ($precioNew !== null && $ganan1Old > 0) {
                        $costoNew = round($precioNew / (1 + $ganan1Old / 100), 2);
                    }
                    $costoBase = $costoNew ?? $costoOld;
                    if ($costoBase > 0 && $ganan2Old > 0) {
                        $precio1New = round($costoBase * (1 + $ganan2Old / 100), 2);
                    }
                    $item['precio_new'] = $precioNew;
                    $item['costo_new'] = $costoNew;
                    $item['precio1_new'] = $precio1New;
                } elseif ($soloCosto) {
                    // El CSV trae el costo: minorista y mayorista se calculan
                    // con los márgenes ya cargados (ganan1/ganan2).
                    if ($costoNew !== null) {
                        if ($ganan1Old > 0) {
                            $precioNew = round($costoNew * (1 + $ganan1Old / 100), 2);
                        }
                        if ($ganan2Old > 0) {
                            $precio1New = round($costoNew * (1 + $ganan2Old / 100), 2);
                        }
                    }
                    $item['precio_new'] = $precioNew;
                    $item['costo_new'] = $costoNew;
                    $item['precio1_new'] = $precio1New;
                }

                $item['precio_diff'] = $precioNew !== null ? round($precioNew - $precioOld, 2) : null;
                $precioVariacionPct = $precioOld > 0 && $precioNew !== null
                    ? round((($precioNew - $precioOld) / $precioOld) * 100, 1)
                    : null;
                $revisionPrecioDetalles = [];
                $revisarVariacionPrecio = static function (string $etiqueta, float $anterior, ?float $nuevo) use (&$revisionPrecioDetalles): void {
                    if ($nuevo === null) return;
                    if ($anterior > 0) {
                        $porcentaje = (($nuevo - $anterior) / $anterior) * 100;
                        if (abs($porcentaje) > 15) {
                            $revisionPrecioDetalles[] = $etiqueta . ' ' . ($porcentaje > 0 ? '+' : '') . number_format($porcentaje, 1, ',', '.') . '%';
                        }
                    } elseif ($nuevo > 0) {
                        $revisionPrecioDetalles[] = $etiqueta . ' sin valor anterior';
                    }
                };
                $revisarVariacionPrecio('Minorista', $precioOld, $precioNew);
                $revisarVariacionPrecio('Mayorista', $precio1Old, $precio1New);
                $revisarVariacionPrecio('Costo', $costoOld, $costoNew);
                $requiereRevisionPrecio = !empty($revisionPrecioDetalles);
                $item['precio_variacion_pct'] = $precioVariacionPct;
                $item['requiere_revision_precio'] = $requiereRevisionPrecio;
                $item['revision_precio_detalle'] = implode(' · ', $revisionPrecioDetalles);
                $item['costo_diff'] = $costoNew !== null ? round($costoNew - $costoOld, 2) : null;
                $item['ganan1_diff'] = $ganan1New !== null ? round($ganan1New - $ganan1Old, 2) : null;
                $item['ganan2_diff'] = $ganan2New !== null ? round($ganan2New - $ganan2Old, 2) : null;

                $item['has_changes'] = false;
                if ($precioNew !== null && abs($precioNew - $precioOld) > 0.001) $item['has_changes'] = true;
                if ($costoNew !== null && abs($costoNew - $costoOld) > 0.001) $item['has_changes'] = true;
                if ($ganan1New !== null && abs($ganan1New - $ganan1Old) > 0.001) $item['has_changes'] = true;
                if ($ganan2New !== null && abs($ganan2New - $ganan2Old) > 0.001) $item['has_changes'] = true;
                if ($precio1New !== null && abs($precio1New - $precio1Old) > 0.001) $item['has_changes'] = true;
            } else {
                $notFound++;
            }

            $results[] = $item;
        }

        $_SESSION['import_preview'] = [
            'results' => $results,
            'total' => count($results),
            'found' => $found,
            'notFound' => $notFound,
            'idprovee' => $idprovee,
            'proveedor' => $proveedorNombre,
            'precios_con_iva' => $conIva,
            'modo_precio' => $modo,
            'version' => 2,
        ];

        Response::redirect('/admin/productos/importar');
    }

    public function confirm(array $params): void
    {
        $this->auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $preview = $_SESSION['import_preview'] ?? null;
        if (!$preview || !is_array($preview['results'] ?? null)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay datos de importacion. Subi el CSV primero.'];
            Response::redirect('/admin/productos/importar');
        }
        if ((int)($preview['version'] ?? 0) !== 2) {
            unset($_SESSION['import_preview']);
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'La vista previa fue generada con una versión anterior. Volvé a cargar el CSV para revisar los cambios antes de aplicarlos.'];
            Response::redirect('/admin/productos/importar');
        }

        $selected = $_POST['selected'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }
        if (!$selected) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No seleccionaste productos para actualizar.'];
            Response::redirect('/admin/productos/importar');
        }
        $updated = 0;
        $errors = 0;

        foreach ($preview['results'] as $item) {
            if (!$item['matched']) {
                continue;
            }
            $idx = 'row_' . $item['row'];
            if (!in_array($idx, $selected, true)) {
                continue;
            }

            try {
                $idprodu = (int)$item['idprodu'];
                $precioVal = $item['precio_new'] ?? $item['precio_old'] ?? null;
                $costoVal = $item['costo_new'] ?? $item['costo_old'] ?? null;
                $ganan1Val = $item['ganan1_new'] ?? null;
                $ganan2Val = $item['ganan2_new'] ?? null;
                $precio1Val = $item['precio1_new'] ?? $item['precio1_old'] ?? null;

                $hasPriceChange = $item['precio_diff'] !== null && abs((float)$item['precio_diff']) > 0.001;
                $hasCostChange = $item['costo_diff'] !== null && abs((float)$item['costo_diff']) > 0.001;
                $hasGanan1Change = $item['ganan1_diff'] !== null && abs((float)$item['ganan1_diff']) > 0.001;
                $hasGanan2Change = $item['ganan2_diff'] !== null && abs((float)$item['ganan2_diff']) > 0.001;
                $hasPrecio1Change = ($item['precio1_diff'] ?? null) !== null && abs((float)$item['precio1_diff']) > 0.001;

                if ($hasPriceChange || $hasCostChange || $hasGanan1Change || $hasGanan2Change || $hasPrecio1Change) {
                    $this->repo->updatePrecios($idprodu, (float)($precioVal ?? 0), (float)($costoVal ?? 0), $ganan1Val, $ganan2Val, $precio1Val);
                }
                $updated++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        unset($_SESSION['import_preview']);

        $msg = 'Productos actualizados: ' . $updated . '.';
        if ($errors > 0) {
            $msg .= ' Errores: ' . $errors . '.';
        }
        $_SESSION['admin_flash'] = ['type' => $errors > 0 ? 'info' : 'ok', 'text' => $msg];
        $_SESSION['import_stats'] = ['updated' => $updated, 'errors' => $errors];

        Response::redirect('/admin/productos/importar');
    }

    private function parseCsv(string $path): array
    {
        $f = fopen($path, 'r');
        if (!$f) {
            return [];
        }

        $raw = fgets($f);
        if (!$raw || !is_string($raw)) {
            fclose($f);
            return [];
        }
        // Quitar BOM UTF-8 (los CSV guardados desde Excel suelen traerlo y rompe el primer encabezado).
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw));

        if (str_contains($raw, ';')) {
            $delimiter = ';';
        } elseif (str_contains($raw, "\t")) {
            $delimiter = "\t";
        } else {
            $delimiter = ',';
        }

        $headers = str_getcsv($raw, $delimiter);
        if (!$headers || !is_array($headers)) {
            fclose($f);
            return [];
        }
        $headers = array_map([$this, 'normalizeImportHeader'], $headers);

        $required = ['codprodup', 'codscan'];
        $hasAny = false;
        foreach ($required as $r) {
            if (in_array($r, $headers, true)) {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            fclose($f);
            return [];
        }

        $rows = [];
        while (($line = fgetcsv($f, 0, $delimiter)) !== false) {
            if (count($line) < 2) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $line[$i] ?? '';
            }
            $codprodup = trim((string)($row['codprodup'] ?? ''));
            $codscan = trim((string)($row['codscan'] ?? ''));
            if ($codprodup === '' && $codscan === '') {
                continue;
            }
            $rows[] = $row;
        }

        fclose($f);
        return $rows;
    }

    /**
     * Normaliza un encabezado del CSV: minúsculas, sin acentos ni BOM, y con
     * alias para los nombres que suelen venir de planillas de Excel.
     */
    private function normalizeImportHeader(string $h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', trim($h));
        $h = mb_strtolower($h);
        $h = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û', 'ä', 'ë', 'ï', 'ö'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o'],
            $h
        );
        $h = str_replace([' ', '-', '.', '/'], '_', $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h) ?? '';

        static $aliases = [
            'codprodup' => ['codprodup', 'codigo_proveedor', 'cod_proveedor', 'codigo_prov', 'codprov'],
            'codscan' => ['codscan', 'codbarra', 'codigo_barra', 'codigo_barras', 'codigo_de_barra', 'codigo_de_barras', 'barra', 'barras', 'ean', 'ean13', 'ean_13', 'codigo', 'cod', 'codigo_producto'],
            'precio_con_iva' => ['precio_con_iva', 'precio_iva', 'precio_final', 'precio_publico', 'precio_minorista', 'precio_venta', 'precio', 'pvp', 'precio_lista'],
            'costo_con_iva' => ['costo_con_iva', 'costo_iva', 'costo', 'costo_final'],
            'precio_sin_iva' => ['precio_sin_iva', 'precio_neto', 'precio_siniva'],
            'costo_sin_iva' => ['costo_sin_iva', 'costo_neto', 'costo_siniva'],
            'precio1_sin_iva' => ['precio1_sin_iva', 'precio1', 'precio_mayorista', 'mayorista'],
            'ganan1' => ['ganan1', 'margen1', 'margen_minorista'],
            'ganan2' => ['ganan2', 'margen2', 'margen_mayorista'],
            'stock' => ['stock', 'cantidad', 'cant', 'existencia', 'existencias'],
        ];
        foreach ($aliases as $canonical => $names) {
            if (in_array($h, $names, true)) {
                return $canonical;
            }
        }
        return $h;
    }

    private function parseFloat(string $v): ?float
    {
        $v = trim(str_replace(['$', ' '], '', $v));
        if ($v === '') {
            return null;
        }
        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '.', $v);
        }
        if (!is_numeric($v) || (float)$v < 0) {
            return null;
        }
        return round((float)$v, 2);
    }

    private function parseInt(string $v): ?int
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (!ctype_digit($v) && !preg_match('/^-?\d+$/', $v)) {
            return null;
        }
        return (int)$v;
    }
}
