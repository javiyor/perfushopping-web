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
        echo View::page('legal/privacy-policy.php', [
            'pageTitle' => 'Política de privacidad',
            'head' => '<meta name="description" content="Información sobre los datos personales tratados por Perfushopping y cómo ejercer tus derechos." /><link rel="canonical" href="https://perfushopping.ar/politica-de-privacidad" />',
        ]);
    }

    public function dataDeletion(array $params): void
    {
        echo View::page('legal/data-deletion.php', [
            'pageTitle' => 'Eliminación de datos personales',
            'head' => '<meta name="description" content="Cómo solicitar acceso, rectificación o eliminación de datos personales en Perfushopping." /><link rel="canonical" href="https://perfushopping.ar/eliminacion-de-datos" />',
        ]);
    }

    public function affiliateTerms(array $params): void
    {
        echo View::page('legal/affiliate_terms.php', []);
    }
}
