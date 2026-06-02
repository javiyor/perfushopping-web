<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\UserRepo;

final class AuthService
{
    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }
        return (new UserRepo())->findById($id);
    }

    public function requireLogin(): array
    {
        $u = $this->user();
        if (!$u) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Inicia sesion para continuar.'];
            \Perfushopping\Web\Support\Response::redirect('/login');
        }
        return $u;
    }

    public function requireAdmin(): array
    {
        $u = $this->requireLogin();
        if (($u['role'] ?? '') !== 'admin') {
            \Perfushopping\Web\Support\Response::html(\Perfushopping\Web\Support\View::page('errors/403.php', ['message' => 'Acceso denegado.']), 403);
            exit;
        }
        return $u;
    }

    public function isWholesaleApproved(?array $u): bool
    {
        return $u && ($u['wholesale_status'] ?? '') === 'approved';
    }
}
