<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\FaqRepo;
use Perfushopping\Web\Repo\Marketing\ProductContentRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Response;

final class EntitySearchController
{
    public function search(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        $type = (string)($_GET['type'] ?? '');
        $q = trim((string)($_GET['q'] ?? ''));
        $exclude = (int)($_GET['exclude'] ?? 0);

        $results = match ($type) {
            'product' => (new ProductContentRepo())->searchProducts($q, $exclude),
            'video' => (new VideoRepo())->search($q),
            'article' => (new ArticleRepo())->search($q),
            'faq' => (new FaqRepo())->search($q),
            default => [],
        };

        Response::json(['ok' => true, 'results' => $results]);
    }
}
