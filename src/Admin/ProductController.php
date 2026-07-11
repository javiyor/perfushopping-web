<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Repo\DepartamentoRepo;
use Perfushopping\Web\Repo\ProveedorRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AiProductDescriptionService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProductController
{
    private AdminProductRepo $repo;
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->repo = new AdminProductRepo();
        $this->auth = new AdminAuthService();
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requireSesion();

        $rubros = $this->repo->allRubros();
        $subrubros = $this->repo->allSubrubros();
        $departamentos = (new DepartamentoRepo())->findAll();
        $proveedores = (new ProveedorRepo())->findAll();
        $ivaOptions = $this->repo->ivaOptions();

        echo View::adminPage('admin/productos/create.php', [
            'adminUser' => $adminUser,
            'rubros' => $rubros,
            'subrubros' => $subrubros,
            'departamentos' => $departamentos,
            'proveedores' => $proveedores,
            'ivaOptions' => $ivaOptions,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo producto',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function store(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $produ = trim((string)($_POST['produ'] ?? ''));
        if ($produ === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre del producto es obligatorio.'];
            Response::redirect('/admin/productos/nuevo');
        }

        $codrub = (int)($_POST['codrub'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);
        $codepar = (int)($_POST['codepar'] ?? 0);
        $codprove = trim((string)($_POST['codprove'] ?? ''));
        $iva = (int)($_POST['iva'] ?? 0);
        $ganan1 = (float)str_replace(',', '.', trim((string)($_POST['ganan1'] ?? '0')));
        $ganan2 = (float)str_replace(',', '.', trim((string)($_POST['ganan2'] ?? '0')));
        $precomp = $this->parseMoney((string)($_POST['precomp'] ?? '')) ?? 0;

        $ivaRate = 0;
        $ivaOpts = $this->repo->ivaOptions();
        foreach ($ivaOpts as $opt) {
            if ((int)($opt['codivaprodu'] ?? 0) === $iva) {
                $ivaRate = (float)($opt['tiva'] ?? 0);
                break;
            }
        }

        $precioGross = $this->parseMoney((string)($_POST['precio_gross'] ?? ''));
        $precio1Gross = $this->parseMoney((string)($_POST['precio1_gross'] ?? ''));
        if ($precioGross === null && $precomp > 0 && $ganan1 > 0) {
            $precioNeto = round($precomp * (1 + $ganan1 / 100), 2);
            $precioGross = round($precioNeto * (1 + $ivaRate / 100), 2);
        }
        if ($precio1Gross === null && $precomp > 0 && $ganan2 > 0) {
            $precio1Neto = round($precomp * (1 + $ganan2 / 100), 2);
            $precio1Gross = round($precio1Neto * (1 + $ivaRate / 100), 2);
        }
        if ($precioGross === null || $precio1Gross === null) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Carga precios o costo + margen.'];
            Response::redirect('/admin/productos/nuevo');
        }

        $precioNeto = $this->grossToNet((float)$precioGross, $ivaRate);
        $precio1Neto = $this->grossToNet((float)$precio1Gross, $ivaRate);

        $idprodu = $this->repo->createProduct($produ, $precioNeto, $precio1Neto, $iva, $precomp, $ganan1, $ganan2);

        $enweb = isset($_POST['enweb']);
        $this->repo->updateProduct($idprodu, '', $precioNeto, $precio1Neto, $enweb, $produ, $codrub, $codsub, $codepar, $codprove, $ganan1, $ganan2, $precomp);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Producto creado correctamente.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireSesion();
        $q = trim((string)($_GET['q'] ?? ''));
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codrub = (int)($_GET['codrub'] ?? 0);
        $sort = (string)($_GET['sort'] ?? 'id');
        $order = (string)($_GET['order'] ?? 'desc');
        $view = (string)($_GET['view'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 60)));

        if (!in_array($sort, ['id','codprodu','produ','marca','categoria','precio','precio1','fecompra'], true)) $sort = 'id';
        if (!in_array($order, ['asc','desc'], true)) $order = 'desc';
        $validViews = ['cards','table'];
        $view = in_array($view, $validViews, true) ? $view : ($_SESSION['product_view'] ?? 'cards');
        if (in_array((string)($_GET['view'] ?? ''), $validViews, true)) $_SESSION['product_view'] = $view;

        $brands = $this->repo->brandOptions();
        $categories = $this->repo->categoryOptions();
        $result = $this->repo->search($q, $codsub, $codrub, $perPage, $sort, $order, $page, $perPage);

        echo View::adminPage('admin/productos/list.php', [
            'adminUser' => $adminUser,
            'q' => $q,
            'codsub' => $codsub,
            'codrub' => $codrub,
            'sort' => $sort,
            'order' => $order,
            'view' => $view,
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'total' => $result['total'],
            'brands' => $brands,
            'categories' => $categories,
            'products' => $result['items'],
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Productos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function show(array $params): void
    {
        $adminUser = $this->auth->requireSesion();
        $id = (int)($params['id'] ?? 0);

        $product = $this->repo->find($id);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $variants = $this->repo->variants($id);
        $imagesMap = $this->repo->variantImagesMap(array_map(static fn (array $v): int => (int)($v['idcodgusto'] ?? 0), $variants));
        foreach ($variants as $idx => $variant) {
            $variants[$idx]['images'] = $imagesMap[(int)($variant['idcodgusto'] ?? 0)] ?? [];
        }

        $rubros = $this->repo->allRubros();
        $subrubros = $this->repo->allSubrubros();
        $departamentos = (new DepartamentoRepo())->findAll();
        $proveedores = (new ProveedorRepo())->findAll();

        echo View::adminPage('admin/productos/edit.php', [
            'adminUser' => $adminUser,
            'product' => $product,
            'variants' => $variants,
            'rubros' => $rubros,
            'subrubros' => $subrubros,
            'departamentos' => $departamentos,
            'proveedores' => $proveedores,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Producto: ' . htmlspecialchars(mb_substr((string)($product['produ'] ?? ''), 0, 40)),
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $product = $this->repo->find($idprodu);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $observ = trim((string)($_POST['observ'] ?? ''));
        $precioBruto = $this->parseMoney((string)($_POST['precio_gross'] ?? ''));
        $precio1Bruto = $this->parseMoney((string)($_POST['precio1_gross'] ?? ''));
        $enweb = isset($_POST['enweb']);
        if ($precioBruto === null || $precio1Bruto === null) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Carga precios validos.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $produ = trim((string)($_POST['produ'] ?? ''));
        $codrub = (int)($_POST['codrub'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);
        $codepar = (int)($_POST['codepar'] ?? 0);
        $codprove = trim((string)($_POST['codprove'] ?? ''));
        $ganan1 = (float)str_replace(',', '.', trim((string)($_POST['ganan1'] ?? '0')));
        $ganan2 = (float)str_replace(',', '.', trim((string)($_POST['ganan2'] ?? '0')));
        $precomp = $this->parseMoney((string)($_POST['precomp'] ?? '')) ?? 0;

        $ivaRate = (float)($product['tiva'] ?? 0);
        $this->repo->updateProduct($idprodu, $observ, $this->grossToNet($precioBruto, $ivaRate), $this->grossToNet($precio1Bruto, $ivaRate), $enweb, $produ, $codrub, $codsub, $codepar, $codprove, $ganan1, $ganan2, $precomp);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Producto actualizado.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function uploadMainImage(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);

        $product = $this->repo->find($idprodu);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $files = $this->normalizeFilesArray($_FILES['images'] ?? null);
        if (!$files) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Selecciona al menos una imagen.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        try {
            $dir = $this->resolveUploadDir();
            $first = $files[0];
            $filename = $this->storeUploadedImage($first, $dir, 'p' . $idprodu . '-main');
            $this->repo->updateMainImage($idprodu, $filename);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Imagen principal actualizada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function clearMainImage(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);

        $product = $this->repo->find($idprodu);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }
        $this->repo->updateMainImage($idprodu, '');
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Imagen principal quitada.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function saveVariantLogistics(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $weightG = max(0, (int)($_POST['weight_g'] ?? 0));
        $heightCm = max(0, (int)($_POST['height_cm'] ?? 0));
        $widthCm = max(0, (int)($_POST['width_cm'] ?? 0));
        $depthCm = max(0, (int)($_POST['depth_cm'] ?? 0));
        $productCategory = trim((string)($_POST['product_category'] ?? ''));

        $product = $this->repo->find($idprodu);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $this->repo->updateVariantLogistics($idcodgusto, $weightG, $heightCm, $widthCm, $depthCm, $productCategory);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Datos logisticos actualizados.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function uploadVariantImages(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);

        $product = $this->repo->find($idprodu);
        if (!$product) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $files = $this->normalizeFilesArray($_FILES['images'] ?? null);
        if (!$files) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Selecciona al menos una imagen.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $saved = 0;
        $current = $this->repo->countVariantImages($idcodgusto);
        try {
            $dir = $this->resolveUploadDir();
            foreach ($files as $idx => $file) {
                if (($current + $saved) >= 6) break;
                $filename = $this->storeUploadedImage($file, $dir, 'p' . $idprodu . '-g' . $idcodgusto . '-' . ($idx + 1));
                $this->repo->insertVariantImage($idprodu, $idcodgusto, $filename);
                $saved++;
            }
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            Response::redirect('/admin/productos/' . $idprodu);
        }
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Imagenes cargadas: ' . $saved . '.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function deleteVariantImage(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $idimagen = (int)($_POST['idimagen'] ?? 0);
        if ($idprodu <= 0 || $idcodgusto <= 0 || $idimagen <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Parametros invalidos.'];
            Response::redirect('/admin/productos/' . max(0, $idprodu));
        }
        $this->repo->deleteVariantImage($idimagen, $idprodu, $idcodgusto);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Imagen quitada.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function describe(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);

        $product = $this->repo->find($idprodu);
        if (!$product) {
            Response::json(['ok' => false, 'error' => 'Producto no encontrado.'], 404);
            return;
        }
        $variants = $this->repo->variants($idprodu);
        try {
            $text = (new AiProductDescriptionService())->generate($product, $variants);
            Response::json(['ok' => true, 'description' => $text]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);
        if ($idprodu <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'ID invalido.'];
            Response::redirect('/admin/productos');
        }
        $this->repo->deleteProduct($idprodu);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Producto eliminado permanentemente.'];
        Response::redirect('/admin/productos');
    }

    public function deleteVariant(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        if ($idprodu <= 0 || $idcodgusto <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Parametros invalidos.'];
            Response::redirect('/admin/productos/' . max(0, $idprodu));
        }
        $this->repo->deleteVariant($idcodgusto);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Variedad eliminada.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    private function grossToNet(float $gross, float $ivaRate): float
    {
        $factor = 1.0 + ($ivaRate / 100.0);
        return $factor > 0 ? round($gross / $factor, 2) : round($gross, 2);
    }

    private function parseMoney(string $value): ?float
    {
        $value = trim(str_replace(['$', ' '], '', $value));
        if ($value === '') return null;
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value) || (float)$value < 0) return null;
        return round((float)$value, 2);
    }

    private function normalizeFilesArray(mixed $raw): array
    {
        if (!is_array($raw) || !isset($raw['name'])) return [];
        $out = [];
        if (is_array($raw['name'])) {
            foreach ($raw['name'] as $i => $name) {
                $out[] = [
                    'name' => is_string($name) ? $name : '',
                    'tmp_name' => is_array($raw['tmp_name'] ?? null) && isset($raw['tmp_name'][$i]) && is_string($raw['tmp_name'][$i]) ? $raw['tmp_name'][$i] : '',
                    'size' => is_array($raw['size'] ?? null) && isset($raw['size'][$i]) ? (int)$raw['size'][$i] : 0,
                    'error' => is_array($raw['error'] ?? null) && isset($raw['error'][$i]) ? (int)$raw['error'][$i] : UPLOAD_ERR_NO_FILE,
                ];
            }
        } else {
            $out[] = [
                'name' => is_string($raw['name']) ? $raw['name'] : '',
                'tmp_name' => is_string($raw['tmp_name'] ?? '') ? $raw['tmp_name'] : '',
                'size' => (int)($raw['size'] ?? 0),
                'error' => (int)($raw['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
        }
        return array_values(array_filter($out, static fn(array $f): bool => $f['error'] === UPLOAD_ERR_OK && $f['tmp_name'] !== ''));
    }

    private function resolveUploadDir(): string
    {
        $base = defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : (string)realpath(__DIR__ . '/../..');
        $candidates = [
            rtrim($base, '/\\') . '/public_html/upload',
            rtrim($base, '/\\') . '/public/upload',
            rtrim($base, '/\\') . '/upload',
        ];
        foreach ($candidates as $candidate) {
            if (is_dir($candidate) || @mkdir($candidate, 0775, true)) {
                return $candidate;
            }
        }
        throw new \RuntimeException('No se encontro directorio de uploads.');
    }

    private function storeUploadedImage(array $file, string $dir, string $prefix): string
    {
        if ($file['size'] > 8 * 1024 * 1024) throw new \RuntimeException('Maximo 8 MB por archivo.');
        if (!is_uploaded_file($file['tmp_name'])) throw new \RuntimeException('Archivo subido invalido.');

        $original = $this->normalizeFilename($file['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) throw new \RuntimeException('Solo JPG, PNG o WEBP.');

        $prefix = trim($prefix, '-');
        $filename = $this->uniqueFilename($dir, $prefix . '-' . $original);
        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $tmpDest = $dest . '.tmp_' . bin2hex(random_bytes(6));
        if (!move_uploaded_file($file['tmp_name'], $tmpDest)) throw new \RuntimeException('No se pudo mover la imagen.');
        @chmod($tmpDest, 0644);
        if (!@rename($tmpDest, $dest)) { @unlink($tmpDest); throw new \RuntimeException('No se pudo guardar.'); }
        return $filename;
    }

    private function normalizeFilename(string $value): string
    {
        $value = trim($value);
        $value = str_replace('\\', '/', $value);
        $value = basename($value);
        $value = trim($value, "\"' ");
        $value = preg_replace('/\s+/', '-', $value) ?? $value;
        $value = mb_strtolower($value);
        $value = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $value);
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? $value;
        $value = trim($value, '-.');
        return $value ?: 'imagen';
    }

    private function uniqueFilename(string $dir, string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $candidate = $filename;
        $i = 1;
        while (is_file(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $name . '-' . $i . ($ext ? '.' . $ext : '');
            $i++;
        }
        return $candidate;
    }
}
