<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\QuizRepo;
use Perfushopping\Web\Repo\Marketing\RoutineRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Service\RecommendationService;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class QuizController
{
    public function index(array $params): void
    {
        $quizzes = (new QuizRepo())->findAll(true);
        echo View::page('quizzes/index.php', [
            'quizzes' => $quizzes,
            'title' => 'Encontrá tu rutina',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $quiz = (new QuizRepo())->findBySlug($slug);
        if (!$quiz) {
            Response::notFound();
            return;
        }
        $questions = (new QuizRepo())->findQuestions((int)$quiz['id']);
        echo View::page('quizzes/show.php', [
            'quiz' => $quiz,
            'questions' => $questions,
            'title' => $quiz['title'],
        ]);
    }

    public function result(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $quiz = (new QuizRepo())->findBySlug($slug);
        if (!$quiz) {
            Response::notFound();
            return;
        }
        $answers = [];
        foreach ((array)($_POST['answers'] ?? []) as $qid => $oid) {
            $answers[(int)$qid] = (int)$oid;
        }
        $result = (new RecommendationService())->recommend((int)$quiz['id'], $answers);

        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);

        $firstRoutine = $result['routines'][0] ?? null;
        $routineItems = [];
        if ($firstRoutine) {
            $routineItems = (new RoutineRepo())->findItems((int)$firstRoutine['id']);
        }

        echo View::page('quizzes/result.php', [
            'quiz' => $quiz,
            'answers' => $answers,
            'result' => $result,
            'firstRoutine' => $firstRoutine,
            'routineItems' => $routineItems,
            'isWholesale' => $isWholesale,
            'title' => 'Tu recomendación',
        ]);
    }
}
