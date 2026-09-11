<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\QuizRepo;
use Perfushopping\Web\Repo\Marketing\RoutineRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class QuizController
{
    private AdminAuthService $auth;
    private QuizRepo $repo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new QuizRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_quizzes');
        $quizzes = $this->repo->findAll();
        echo View::adminPage('admin/marketing/quizzes/list.php', [
            'adminUser' => $adminUser,
            'quizzes' => $quizzes,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Recomendadores',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_quizzes');
        echo View::adminPage('admin/marketing/quizzes/form.php', [
            'adminUser' => $adminUser,
            'quiz' => null,
            'questions' => [],
            'rules' => [],
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo recomendador',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_quizzes');
        $id = (int)($params['id'] ?? 0);
        $quiz = $this->repo->findById($id);
        if (!$quiz) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Recomendador no encontrado.'];
            Response::redirect('/admin/marketing/recomendadores');
        }
        echo View::adminPage('admin/marketing/quizzes/form.php', [
            'adminUser' => $adminUser,
            'quiz' => $quiz,
            'questions' => $this->repo->findQuestions($id),
            'rules' => $this->repo->findRules($id),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar recomendador',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);

        $name = trim((string)($_POST['name'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        if ($name === '' || $title === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Nombre y título son obligatorios.'];
            Response::redirect('/admin/marketing/recomendadores');
        }

        $quizId = $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'name' => $name,
            'slug' => trim((string)($_POST['slug'] ?? '')),
            'title' => $title,
            'description' => $_POST['description'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Recomendador guardado.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Recomendador eliminado.'];
        Response::redirect('/admin/marketing/recomendadores');
    }

    public function saveQuestion(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $this->repo->saveQuestion([
            'id' => (int)($_POST['id'] ?? 0),
            'quiz_id' => $quizId,
            'question' => trim((string)($_POST['question'] ?? '')),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Pregunta guardada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function deleteQuestion(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->deleteQuestion($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Pregunta eliminada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function saveOption(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $tags = [];
        foreach ((array)($_POST['tags']['taxonomy_key'] ?? []) as $i => $key) {
            $val = trim((string)($_POST['tags']['term_value'][$i] ?? ''));
            if ($key !== '' && $val !== '') {
                $tags[] = ['taxonomy_key' => $key, 'term_value' => $val];
            }
        }
        $this->repo->saveOption([
            'id' => (int)($_POST['id'] ?? 0),
            'question_id' => $questionId,
            'label' => trim((string)($_POST['label'] ?? '')),
            'tags' => $tags,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Opción guardada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function deleteOption(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->deleteOption($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Opción eliminada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function saveRule(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $conditions = [];
        foreach ((array)($_POST['conditions']['taxonomy_key'] ?? []) as $i => $key) {
            $val = trim((string)($_POST['conditions']['term_value'][$i] ?? ''));
            if ($key !== '' && $val !== '') {
                $conditions[] = ['taxonomy_key' => $key, 'term_value' => $val];
            }
        }
        $this->repo->saveRule([
            'id' => (int)($_POST['id'] ?? 0),
            'quiz_id' => $quizId,
            'name' => trim((string)($_POST['name'] ?? '')),
            'priority' => (int)($_POST['priority'] ?? 0),
            'conditions' => $conditions,
            'target_type' => trim((string)($_POST['target_type'] ?? 'product')),
            'target_id' => (int)($_POST['target_id'] ?? 0),
            'score' => (int)($_POST['score'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Regla guardada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }

    public function deleteRule(array $params): void
    {
        $this->auth->requirePermiso('marketing_quizzes');
        Csrf::check($_POST['_csrf'] ?? null);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->deleteRule($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Regla eliminada.'];
        Response::redirect('/admin/marketing/recomendadores/' . $quizId);
    }
}
