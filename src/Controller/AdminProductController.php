<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Service\AiProductDescriptionService;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AdminProductController
{
    public function index(array $params): void
    {
        $user = (new AuthService())->requireAdmin();
        $repo = new AdminProductRepo();
        $q = trim((string)($_GET['q'] ?? ''));
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codrub = (int)($_GET['codrub'] ?? 0);
        $selectedId = (int)($params['id'] ?? 0);

        $brands = $repo->brandOptions();
        $categories = $repo->categoryOptions();
        $products = $repo->search($q, $codsub, $codrub, ($q === '' && $codsub <= 0 && $codrub <= 0) ? 24 : 60);
        $selected = null;
        $variants = [];
        if ($selectedId > 0) {
            $selected = $repo->find($selectedId);
            if ($selected) {
                $variants = $repo->variants($selectedId);
                $imagesMap = $repo->variantImagesMap(array_map(static fn (array $variant): int => (int)($variant['idcodgusto'] ?? 0), $variants));
                foreach ($variants as $idx => $variant) {
                    $variants[$idx]['images'] = $imagesMap[(int)($variant['idcodgusto'] ?? 0)] ?? [];
                }
                $alreadyListed = false;
                foreach ($products as $p) {
                    if ((int)$p['idprodu'] === $selectedId) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if (!$alreadyListed) {
                    array_unshift($products, $selected);
                }
            }
        }

        echo View::page('admin/products.php', [
            'user' => $user,
            'q' => $q,
            'codsub' => $codsub,
            'codrub' => $codrub,
            'brands' => $brands,
            'categories' => $categories,
            'products' => $products,
            'selected' => $selected,
            'variants' => $variants,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function save(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $observ = trim((string)($_POST['observ'] ?? ''));
        $precioBruto = $this->parseMoney((string)($_POST['precio_gross'] ?? ''));
        $precio1Bruto = $this->parseMoney((string)($_POST['precio1_gross'] ?? ''));
        $enweb = isset($_POST['enweb']);
        if ($precioBruto === null || $precio1Bruto === null) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Carga precios validos.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $ivaRate = (float)($product['tiva'] ?? 0);
        $repo->updateProduct(
            $idprodu,
            $observ,
            $this->grossToNet($precioBruto, $ivaRate),
            $this->grossToNet($precio1Bruto, $ivaRate),
            $enweb
        );

        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Producto actualizado.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function saveVariantLogistics(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $weightG = max(0, (int)($_POST['weight_g'] ?? 0));
        $heightCm = max(0, (int)($_POST['height_cm'] ?? 0));
        $widthCm = max(0, (int)($_POST['width_cm'] ?? 0));
        $depthCm = max(0, (int)($_POST['depth_cm'] ?? 0));
        $productCategory = trim((string)($_POST['product_category'] ?? ''));

        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $variantOk = false;
        foreach ($repo->variants($idprodu) as $variant) {
            if ((int)($variant['idcodgusto'] ?? 0) === $idcodgusto) {
                $variantOk = true;
                break;
            }
        }
        if (!$variantOk) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Variedad no encontrada para ese producto.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $repo->updateVariantLogistics($idcodgusto, $weightG, $heightCm, $widthCm, $depthCm, $productCategory);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Datos logisticos de la variedad actualizados.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function uploadMainImage(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $files = $this->normalizeFilesArray($_FILES['images'] ?? null);
        if (!$files) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Selecciona al menos una imagen.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $saved = 0;
        $usedFirstOnly = count($files) > 1;
        try {
            $dir = $this->resolveUploadDir();
            $first = $files[0];
            $filename = $this->storeUploadedImage($first, $dir, 'p' . $idprodu . '-main');
            $repo->updateMainImage($idprodu, $filename);
            $saved++;
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $text = 'Imagen principal actualizada.';
        if ($usedFirstOnly) {
            $text .= ' Se uso solo la primera imagen seleccionada.';
        }
        $_SESSION['flash'] = ['type' => 'ok', 'text' => $text];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function clearMainImage(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }
        $repo->updateMainImage($idprodu, '');
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Imagen principal quitada.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function uploadVariantImages(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/productos');
        }

        $variantOk = false;
        foreach ($repo->variants($idprodu) as $variant) {
            if ((int)$variant['idcodgusto'] === $idcodgusto) {
                $variantOk = true;
                break;
            }
        }
        if (!$variantOk) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Variedad no encontrada para ese producto.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $files = $this->normalizeFilesArray($_FILES['images'] ?? null);
        if (!$files) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Selecciona al menos una imagen para la variedad.'];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        $saved = 0;
        $current = $repo->countVariantImages($idcodgusto);
        try {
            $dir = $this->resolveUploadDir();
            foreach ($files as $idx => $file) {
                if (($current + $saved) >= 6) {
                    break;
                }
                $filename = $this->storeUploadedImage($file, $dir, 'p' . $idprodu . '-g' . $idcodgusto . '-' . ($idx + 1));
                $repo->insertVariantImage($idprodu, $idcodgusto, $filename);
                $saved++;
            }
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            Response::redirect('/admin/productos/' . $idprodu);
        }

        if ($saved <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'La variedad ya tiene 6 imagenes. Quita alguna para cargar nuevas.'];
        } else {
            $msg = 'Imagenes de la variedad cargadas: ' . $saved . '.';
            if (($current + $saved) >= 6 && count($files) > $saved) {
                $msg .= ' Se alcanzo el maximo de 6.';
            }
            $_SESSION['flash'] = ['type' => 'ok', 'text' => $msg];
        }

        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function deleteVariantImage(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $idimagen = (int)($_POST['idimagen'] ?? 0);
        if ($idprodu <= 0 || $idcodgusto <= 0 || $idimagen <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Parametros invalidos para borrar imagen.'];
            Response::redirect('/admin/productos/' . max(0, $idprodu));
        }

        (new AdminProductRepo())->deleteVariantImage($idimagen, $idprodu, $idcodgusto);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Imagen de variedad quitada.'];
        Response::redirect('/admin/productos/' . $idprodu);
    }

    public function describe(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $repo = new AdminProductRepo();
        $product = $repo->find($idprodu);
        if (!$product) {
            Response::json(['ok' => false, 'error' => 'Producto no encontrado.'], 404);
            return;
        }
        $variants = $repo->variants($idprodu);

        try {
            $text = (new AiProductDescriptionService())->generate($product, $variants);
            Response::json(['ok' => true, 'description' => $text], 200);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function grossToNet(float $gross, float $ivaRate): float
    {
        $factor = 1.0 + ($ivaRate / 100.0);
        if ($factor <= 0) {
            return round($gross, 2);
        }
        return round($gross / $factor, 2);
    }

    private function parseMoney(string $value): ?float
    {
        $value = trim(str_replace(['$', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value)) {
            return null;
        }
        $amount = (float)$value;
        if ($amount < 0) {
            return null;
        }
        return round($amount, 2);
    }

    /** @return array<int, array{name:string,tmp_name:string,size:int,error:int}> */
    private function normalizeFilesArray(mixed $raw): array
    {
        if (!is_array($raw) || !isset($raw['name'])) {
            return [];
        }

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

    /** @param array{name:string,tmp_name:string,size:int,error:int} $file */
    private function storeUploadedImage(array $file, string $dir, string $prefix): string
    {
        if ($file['size'] > 8 * 1024 * 1024) {
            throw new \RuntimeException('Cada archivo debe pesar hasta 8 MB.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Archivo subido invalido.');
        }

        $original = $this->normalizeFilename($file['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Solo se permiten imagenes JPG, PNG o WEBP.');
        }

        $prefix = trim($prefix, '-');
        $filename = $this->uniqueFilename($dir, $prefix . '-' . $original);
        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $tmpDest = $dest . '.tmp_' . bin2hex(random_bytes(6));
        if (!move_uploaded_file($file['tmp_name'], $tmpDest)) {
            throw new \RuntimeException('No se pudo mover la imagen subida.');
        }
        @chmod($tmpDest, 0644);
        if (!@rename($tmpDest, $dest)) {
            @unlink($tmpDest);
            throw new \RuntimeException('No se pudo guardar la imagen.');
        }
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
        $value = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'], ['a', 'e', 'i', 'o', 'u', 'n', 'u'], $value);
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? $value;
        $value = trim($value, '-.');
        if ($value === '') {
            $value = 'imagen';
        }
        return $value;
    }

    private function uniqueFilename(string $dir, string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $candidate = $filename;
        $i = 1;
        while (is_file(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $name . '-' . $i . ($ext !== '' ? '.' . $ext : '');
            $i++;
        }
        return $candidate;
    }
}
