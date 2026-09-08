<?php
declare(strict_types=1);
namespace Perfushopping\Web\Controller;

final class CatalogFeedController
{
    public function csv(array $params): void
    {
        $cands = [
            __DIR__ . '/../../public/catalog_products.csv',
            __DIR__ . '/../../catalog_products.csv',
            __DIR__ . '/../../../public_html/catalog_products.csv',
        ];
        if (class_exists(\Perfushopping\Web\Admin\WhatsappCatalog\Generator::class)) {
            $cands[] = \Perfushopping\Web\Admin\WhatsappCatalog\Generator::csvPath();
        }
        foreach ($cands as $p) {
            if ($p && file_exists($p)) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Cache-Control: no-cache');
                readfile($p);
                return;
            }
        }
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'not generated - ejecute /admin/whatsApp-catalog/generate';
    }
}
