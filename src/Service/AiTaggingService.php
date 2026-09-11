<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\Marketing\ProductContentRepo;
use Perfushopping\Web\Repo\Marketing\TaxonomyRepo;

final class AiTaggingService
{
    private AiCommercialContentService $ai;
    private ProductContentRepo $repo;

    public function __construct()
    {
        $this->ai = new AiCommercialContentService();
        $this->repo = new ProductContentRepo();
    }

    /**
     * @param array<string,mixed> $product
     * @param array<int,array<string,mixed>> $variants
     * @return array<int,array{taxonomy_key:string,term_value:string,source:string,ai_suggested:int,verified:int}>
     */
    public function suggestAndApply(int $productId, array $product, array $variants): array
    {
        $result = $this->ai->suggestForProduct($product, $variants);
        $validTaxonomies = array_keys((new TaxonomyRepo())->termsGrouped());
        $tags = [];
        foreach ($result['tags'] ?? [] as $t) {
            $key = trim((string)($t['taxonomy_key'] ?? ''));
            $val = trim((string)($t['term_value'] ?? ''));
            if (!in_array($key, $validTaxonomies, true)) continue;
            $tags[] = [
                'taxonomy_key' => $key,
                'term_value' => $val,
                'source' => 'ai',
                'ai_suggested' => 1,
                'verified' => 0,
            ];
        }

        $existing = $this->repo->findTags($productId);
        $merged = [];
        foreach ($existing as $key => $vals) {
            foreach ($vals as $val) {
                $merged[] = ['taxonomy_key' => $key, 'term_value' => $val, 'source' => 'manual', 'ai_suggested' => 0, 'verified' => 1];
            }
        }
        foreach ($tags as $t) {
            foreach ($merged as $m) {
                if ($m['taxonomy_key'] === $t['taxonomy_key'] && $m['term_value'] === $t['term_value']) {
                    continue 2;
                }
            }
            $merged[] = $t;
        }

        $this->repo->saveTags($productId, $merged);
        return $tags;
    }
}
