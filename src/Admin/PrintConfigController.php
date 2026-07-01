<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\View;

final class PrintConfigController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        echo View::adminPage('admin/impresion/config.php', [
            'adminUser' => $adminUser,
            'pageTitle' => 'Configuración de impresión',
        ]);
    }
}
