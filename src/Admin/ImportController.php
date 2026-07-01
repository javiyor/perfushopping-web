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
        $adminUser = $this->auth->requireSesion();

        $preview = $_SESSION['import_preview'] ?? null;
        $stats = $_SESSION['import_stats'] ?? null;
        unset($_SESSION['import_preview'], $_SESSION['import_stats']);

        echo View::adminPage('admin/productos/import.php', [
            'adminUser' => $adminUser,
            'preview' => $preview,
            'stats' => $stats,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Importar productos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function preview(array $params): void
    {
        $this->auth->requireSesion();
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
        if (!$rows) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudieron leer filas del CSV. Verifica que tenga las columnas: codprodup, codscan, precio_sin_iva, costo_sin_iva, stock'];
            Response::redirect('/admin/productos/importar');
        }

        $results = [];
        $found = 0;
        $notFound = 0;

        foreach ($rows as $idx => $row) {
            $codprodup = trim((string)($row['codprodup'] ?? ''));
            $codscan = trim((string)($row['codscan'] ?? ''));
            $precioNew = $this->parseFloat((string)($row['precio_sin_iva'] ?? ''));
            $costoNew = $this->parseFloat((string)($row['costo_sin_iva'] ?? ''));
            $stockNew = $this->parseInt((string)($row['stock'] ?? ''));
            $ganan1New = $this->parseFloat((string)($row['ganan1'] ?? ''));
            $ganan2New = $this->parseFloat((string)($row['ganan2'] ?? ''));
            $precio1New = $this->parseFloat((string)($row['precio1_sin_iva'] ?? ''));

            $match = $this->repo->findByCodprodupOrCodscan($codprodup, $codscan);

            $item = [
                'row' => $idx + 2,
                'codprodup' => $codprodup,
                'codscan' => $codscan,
                'precio_new' => $precioNew,
                'costo_new' => $costoNew,
                'stock_new' => $stockNew,
                'ganan1_new' => $ganan1New,
                'ganan2_new' => $ganan2New,
                'precio1_new' => $precio1New,
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
                $stockOld = (int)($match['_stockact'] ?? 0);
                $ganan1Old = (float)($match['ganan1'] ?? 0);
                $ganan2Old = (float)($match['ganan2'] ?? 0);

                $item['precio_old'] = $precioOld;
                $item['precio_old_gross'] = $precioOld * (1 + $ivaRate / 100);
                $item['precio_new_gross'] = $precioNew !== null ? $precioNew * (1 + $ivaRate / 100) : null;
                $item['precio1_old'] = $precio1Old;
                $item['precio1_old_gross'] = $precio1Old * (1 + $ivaRate / 100);
                $item['precio1_new_gross'] = $precio1New !== null ? $precio1New * (1 + $ivaRate / 100) : null;
                $item['costo_old'] = $costoOld;
                $item['stock_old'] = $stockOld;
                $item['ganan1_old'] = $ganan1Old;
                $item['ganan2_old'] = $ganan2Old;
                $item['iva_rate'] = $ivaRate;

                $item['precio_diff'] = $precioNew !== null ? round($precioNew - $precioOld, 2) : null;
                $item['costo_diff'] = $costoNew !== null ? round($costoNew - $costoOld, 2) : null;
                $item['stock_diff'] = $stockNew !== null ? $stockNew - $stockOld : null;
                $item['ganan1_diff'] = $ganan1New !== null ? round($ganan1New - $ganan1Old, 2) : null;
                $item['ganan2_diff'] = $ganan2New !== null ? round($ganan2New - $ganan2Old, 2) : null;

                $item['has_changes'] = false;
                if ($precioNew !== null && abs($precioNew - $precioOld) > 0.001) $item['has_changes'] = true;
                if ($costoNew !== null && abs($costoNew - $costoOld) > 0.001) $item['has_changes'] = true;
                if ($stockNew !== null && $stockNew !== $stockOld) $item['has_changes'] = true;
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
        ];

        Response::redirect('/admin/productos/importar');
    }

    public function confirm(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $preview = $_SESSION['import_preview'] ?? null;
        if (!$preview || !is_array($preview['results'] ?? null)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay datos de importacion. Subi el CSV primero.'];
            Response::redirect('/admin/productos/importar');
        }

        $selected = $_POST['selected'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }

        $updated = 0;
        $errors = 0;

        foreach ($preview['results'] as $item) {
            if (!$item['matched']) {
                continue;
            }
            $idx = 'row_' . $item['row'];
            if (!in_array($idx, $selected, true) && !empty($selected)) {
                continue;
            }

            try {
                $idprodu = (int)$item['idprodu'];
                $precioVal = $item['precio_new'];
                $costoVal = $item['costo_new'];
                $stockVal = $item['stock_new'];
                $ganan1Val = $item['ganan1_new'] ?? null;
                $ganan2Val = $item['ganan2_new'] ?? null;
                $precio1Val = $item['precio1_new'] ?? null;
                $idcodgusto = (int)$item['idcodgusto'];

                $hasPriceChange = $item['precio_diff'] !== null && abs((float)$item['precio_diff']) > 0.001;
                $hasCostChange = $item['costo_diff'] !== null && abs((float)$item['costo_diff']) > 0.001;
                $hasStockChange = $item['stock_diff'] !== null && (int)$item['stock_diff'] !== 0;
                $hasGanan1Change = $item['ganan1_diff'] !== null && abs((float)$item['ganan1_diff']) > 0.001;
                $hasGanan2Change = $item['ganan2_diff'] !== null && abs((float)$item['ganan2_diff']) > 0.001;

                if ($hasPriceChange || $hasCostChange || $hasGanan1Change || $hasGanan2Change || $precio1Val !== null) {
                    $this->repo->updatePrecios($idprodu, (float)$precioVal, (float)$costoVal, $ganan1Val, $ganan2Val, $precio1Val);
                }
                if ($hasStockChange && $idcodgusto > 0) {
                    $this->repo->updateStock($idcodgusto, (int)$stockVal);
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
        $raw = trim($raw);

        $delimiter = str_contains($raw, ';') ? ';' : ',';

        $headers = str_getcsv($raw, $delimiter);
        if (!$headers || !is_array($headers)) {
            fclose($f);
            return [];
        }
        $headers = array_map(static fn (string $h): string => trim(mb_strtolower(str_replace([' ', '-'], '_', $h))), $headers);

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
