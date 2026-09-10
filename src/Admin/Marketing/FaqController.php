<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\FaqRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class FaqController
{
    private AdminAuthService $auth;
    private FaqRepo $repo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new FaqRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_faqs');
        $faqs = $this->repo->findAll();
        echo View::adminPage('admin/marketing/faqs/list.php', [
            'adminUser' => $adminUser,
            'faqs' => $faqs,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'FAQs',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_faqs');
        Csrf::check($_POST['_csrf'] ?? null);

        $question = trim((string)($_POST['question'] ?? ''));
        $answer = trim((string)($_POST['answer'] ?? ''));
        if ($question === '' || $answer === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Pregunta y respuesta son obligatorias.'];
            Response::redirect('/admin/marketing/faqs');
        }

        $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'question' => $question,
            'answer' => $answer,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'FAQ guardada correctamente.'];
        Response::redirect('/admin/marketing/faqs');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_faqs');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'FAQ eliminada.'];
        Response::redirect('/admin/marketing/faqs');
    }
}
