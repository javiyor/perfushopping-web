<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class TaxonomyRepo
{
    /** @return array<int,array<string,mixed>> */
    public function findAllActive(): array
    {
        $st = Db::pdo()->query('SELECT * FROM cms_taxonomies WHERE active = 1 ORDER BY sort_order ASC, id ASC');
        return $st->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function findTermsByTaxonomyKey(string $key): array
    {
        $st = Db::pdo()->prepare('
            SELECT t.* FROM cms_taxonomy_terms t
            INNER JOIN cms_taxonomies tx ON tx.id = t.taxonomy_id
            WHERE tx.key = :k AND t.active = 1
            ORDER BY t.sort_order ASC, t.label ASC
        ');
        $st->execute([':k' => $key]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<string,array{taxonomy_label:string,terms:array<int,array<string,mixed>}>} */
    public function termsGrouped(): array
    {
        $st = Db::pdo()->query('
            SELECT tx.key, tx.label AS taxonomy_label, t.value, t.label FROM cms_taxonomy_terms t
            INNER JOIN cms_taxonomies tx ON tx.id = t.taxonomy_id
            WHERE tx.active = 1 AND t.active = 1
            ORDER BY tx.sort_order, t.sort_order, t.label
        ');
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $key = (string)$row['key'];
            if (!isset($out[$key])) $out[$key] = ['taxonomy_label' => $row['taxonomy_label'], 'terms' => []];
            $out[$key]['terms'][] = ['value' => $row['value'], 'label' => $row['label']];
        }
        return $out;
    }
}
