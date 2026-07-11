<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class WebUserController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new UserRepo())->adminList($q, $q === '' ? 120 : 200);
        echo View::adminPage('admin/users.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'customerCategories' => UserRepo::customerCategoryOptions(),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function roleSave(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = trim((string)($_POST['role'] ?? ''));
        $q = trim((string)($_POST['q'] ?? ''));
        $allowed = ['customer', 'admin'];
        if ($userId <= 0 || !in_array($role, $allowed, true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para cambiar el rol.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        if ((int)($adminUser['id'] ?? 0) === $userId && $role !== 'admin') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes quitarte el rol admin a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->setRole($userId, $role);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Rol actualizado para ' . (string)($target['email'] ?? ('#' . $userId)) . '.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function save(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $wholesaleStatus = trim((string)($_POST['wholesale_status'] ?? 'none'));
        $customerCategory = trim((string)($_POST['customer_category'] ?? 'none'));
        $q = trim((string)($_POST['q'] ?? ''));
        $allowed = ['customer', 'admin'];
        $allowedWholesale = ['none', 'pending', 'approved', 'rejected'];
        if ($userId <= 0 || $email === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowed, true) || !in_array($wholesaleStatus, $allowedWholesale, true) || !array_key_exists($customerCategory, UserRepo::customerCategoryOptions())) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para guardar el usuario.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        if ((int)($adminUser['id'] ?? 0) === $userId && $role !== 'admin') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes quitarte el rol admin a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        $existing = $repo->findByEmail($email);
        if ($existing && (int)($existing['id'] ?? 0) !== $userId) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ese email ya esta registrado en otra cuenta.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->adminUpdate($userId, $email, $name, $phone, $role, $wholesaleStatus, $customerCategory);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Usuario actualizado.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function toggleBlock(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $q = trim((string)($_POST['q'] ?? ''));
        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        if ((int)($adminUser['id'] ?? 0) === $userId) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes bloquearte a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $disabled = empty($target['disabled_at']);
        $repo->setDisabled($userId, $disabled);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $disabled ? 'Usuario bloqueado.' : 'Usuario desbloqueado.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function delete(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $q = trim((string)($_POST['q'] ?? ''));
        if ((int)($adminUser['id'] ?? 0) === $userId) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes eliminarte a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        try {
            $repo->deleteUser($userId);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Usuario eliminado.'];
        } catch (\PDOException $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo eliminar el usuario porque tiene registros relacionados. Podes bloquearlo en su lugar.'];
        }
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function passwordReset(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $password = (string)($_POST['new_password'] ?? '');
        $q = trim((string)($_POST['q'] ?? ''));
        if ($userId <= 0 || strlen($password) < 8) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'La nueva clave debe tener al menos 8 caracteres.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->adminResetPassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Clave actualizada para ' . (string)($target['email'] ?? ('#' . $userId)) . '.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }
}
