<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Service\SearchService;
use Perfushopping\Web\Support\View;

final class SearchController
{
    public function index(array $params): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new SearchService())->search($q);
        $auth = new AuthService();
        $user = $auth->user();
        echo View::page('search/index.php', [
            'q' => $q,
            'results' => $results,
            'user' => $user,
            'isWholesale' => $auth->isWholesaleApproved($user),
            'title' => $q !== '' ? 'Resultados para "' . $q . '"' : 'Buscador',
        ]);
    }
}
