<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmpresaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class EmpresaController
{
    public function edit(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');

        $repo = new EmpresaRepo();
        $empresa = $repo->getDefault();
        $tiposIva = $repo->tiposIva();
        $sucursales = (new \Perfushopping\Web\Repo\SucursalRepo())->findAll();
        $depositos = (new \Perfushopping\Web\Repo\SucursalRepo())->listarDepositos();

        echo View::adminPage('admin/empresa/edit.php', [
            'adminUser' => $adminUser,
            'empresa' => $empresa,
            'tiposIva' => $tiposIva,
            'sucursales' => $sucursales,
            'depositos' => $depositos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Datos de la empresa',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Response::redirect('/admin/empresa');
        }

        $data = [
            'nomemp' => trim((string)($_POST['nomemp'] ?? '')),
            'razon_emp' => trim((string)($_POST['razon_emp'] ?? '')),
            'dire_emp' => trim((string)($_POST['dire_emp'] ?? '')),
            'telefono' => trim((string)($_POST['telefono'] ?? '')),
            'cuit' => trim((string)($_POST['cuit'] ?? '')),
            'ing_brutos' => trim((string)($_POST['ing_brutos'] ?? '')),
            'mail' => trim((string)($_POST['mail'] ?? '')),
            'web' => trim((string)($_POST['web'] ?? '')),
            'codtip' => (int)($_POST['codtip'] ?? 0) ?: null,
        ];

        // Logo upload
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                $uploadDir = APP_BASE_DIR . '/upload/empresa';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'logo.' . $ext;
                $destPath = $uploadDir . '/' . $filename;
                move_uploaded_file($_FILES['logo']['tmp_name'], $destPath);
                $data['logo'] = '/upload/empresa/' . $filename;
            }
        }

        (new EmpresaRepo())->update($id, $data);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Datos de empresa actualizados.'];
        Response::redirect('/admin/empresa');
    }

    public function removeLogo(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new EmpresaRepo();
        $empresa = $repo->getDefault();
        if ($empresa && ($empresa['logo'] ?? '')) {
            $filePath = APP_BASE_DIR . '/' . ltrim($empresa['logo'], '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $repo->update((int)$empresa['idempre'], ['logo' => null]);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Logo eliminado.'];
        Response::redirect('/admin/empresa');
    }
}
