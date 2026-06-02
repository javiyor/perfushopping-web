<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Support\View;

final class LegalController
{
    public function terms(array $params): void
    {
        echo View::page('legal/terms.php', []);
    }

    public function privacy(array $params): void
    {
        echo View::page('legal/privacy.php', []);
    }

    public function affiliateTerms(array $params): void
    {
        echo View::page('legal/affiliate_terms.php', []);
    }
}
