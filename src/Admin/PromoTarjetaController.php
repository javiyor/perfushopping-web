<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\PromoTarjetaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PromoTarjetaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $list = (new PromoTarjetaRepo())->findAll();

        echo View::adminPage('admin/promo-tarjetas/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Promo Tarjetas',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $tipo = trim((string)($_POST['tipo_tarjeta'] ?? ''));
        $banco = trim((string)($_POST['banco'] ?? ''));

        if (!in_array($tipo, ['credito', 'debito'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Tipo de tarjeta inválido.'];
            Response::redirect('/admin/promo-tarjetas');
        }
        if ($banco === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El banco es obligatorio.'];
            Response::redirect('/admin/promo-tarjetas');
        }

        $data = [
            'tipo_tarjeta' => $tipo,
            'banco' => $banco,
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'detalle_promo' => trim((string)($_POST['detalle_promo'] ?? '')),
            'fecha_desde' => trim((string)($_POST['fecha_desde'] ?? '')) ?: null,
            'fecha_hasta' => trim((string)($_POST['fecha_hasta'] ?? '')) ?: null,
            'publicado' => isset($_POST['publicado']) ? 1 : 0,
        ];

        $repo = new PromoTarjetaRepo();

        // Keep existing image for update if no new one uploaded
        if ($id > 0) {
            $existing = $repo->findById($id);
            $data['imagen'] = $existing['imagen'] ?? null;
        } else {
            $data['imagen'] = null;
        }

        // Handle image upload
        $uploaded = $_FILES['imagen'] ?? null;
        if ($uploaded && is_array($uploaded) && $uploaded['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($uploaded['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Formato de imagen no válido (jpg, png, webp).'];
                Response::redirect('/admin/promo-tarjetas');
            }
            $uploadDir = APP_BASE_DIR . '/public/upload/promo';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($uploaded['tmp_name'], $destPath)) {
                // Delete old image if updating
                if ($id > 0 && $data['imagen']) {
                    $oldPath = APP_BASE_DIR . '/public/upload/' . ltrim($data['imagen'], '/');
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $data['imagen'] = 'promo/' . $filename;
            }
        }

        if ($id > 0) {
            $repo->update($id, $data);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Promo actualizada.'];
        } else {
            $newId = $repo->create($data, (int)$adminUser['id']);
            $id = $newId;
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Promo creada.'];
        }

        Response::redirect('/admin/promo-tarjetas');
    }

    public function deleteImage(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::redirect('/admin/promo-tarjetas');
        }

        $repo = new PromoTarjetaRepo();
        $promo = $repo->findById($id);
        if ($promo && ($promo['imagen'] ?? '') !== '') {
            $path = APP_BASE_DIR . '/public/upload/' . ltrim((string)$promo['imagen'], '/');
            if (is_file($path)) {
                unlink($path);
            }
            $repo->update($id, array_merge($promo, ['imagen' => null]));
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Imagen eliminada.'];
        Response::redirect('/admin/promo-tarjetas');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new PromoTarjetaRepo())->delete($id);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Promo eliminada.'];
        Response::redirect('/admin/promo-tarjetas');
    }
}