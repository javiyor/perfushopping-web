<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class ProductContentRepo
{
    public const RELATION_TYPES = [
        'complement' => 'Complemento',
        'alternative' => 'Alternativa',
        'kit' => 'Kit / combo',
        'upgrades' => 'Upgrade',
        'similar' => 'Similar',
    ];
    public function findContent(int $productId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_product_content WHERE product_id = :p LIMIT 1');
        $st->execute([':p' => $productId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function saveContent(int $productId, array $data): void
    {
        $exists = $this->findContent($productId);
        $fields = [
            'benefit' => trim((string)($data['benefit'] ?? '')),
            'ideal_for' => trim((string)($data['ideal_for'] ?? '')),
            'problem' => trim((string)($data['problem'] ?? '')),
            'results' => trim((string)($data['results'] ?? '')),
            'usage' => trim((string)($data['usage'] ?? '')),
            'advice' => trim((string)($data['advice'] ?? '')),
            'cta' => trim((string)($data['cta'] ?? '')),
        ];
        if ($exists) {
            $st = Db::pdo()->prepare('UPDATE cms_product_content SET benefit=:b, ideal_for=:i, problem=:pr, results=:r, `usage`=:u, advice=:a, cta=:c WHERE product_id=:p');
        } else {
            $st = Db::pdo()->prepare('INSERT INTO cms_product_content (product_id, benefit, ideal_for, problem, results, `usage`, advice, cta) VALUES (:p, :b, :i, :pr, :r, :u, :a, :c)');
        }
        $st->execute([
            ':p' => $productId,
            ':b' => $fields['benefit'],
            ':i' => $fields['ideal_for'],
            ':pr' => $fields['problem'],
            ':r' => $fields['results'],
            ':u' => $fields['usage'],
            ':a' => $fields['advice'],
            ':c' => $fields['cta'],
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findRelations(int $productId): array
    {
        $st = Db::pdo()->prepare('
            SELECT r.*, p.produ AS related_name, p.imagen AS related_image
            FROM cms_product_relations r
            INNER JOIN producto p ON p.idprodu = r.related_id
            WHERE r.product_id = :p AND r.active = 1
            ORDER BY r.sort_order ASC, r.id ASC
        ');
        $st->execute([':p' => $productId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,array{related_id:int,type:string,sort_order:int}> $relations */
    public function saveRelations(int $productId, array $relations): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_product_relations WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_product_relations (product_id, related_id, type, sort_order) VALUES (:p, :r, :t, :s)');
        foreach ($relations as $rel) {
            $rid = (int)($rel['related_id'] ?? 0);
            if ($rid <= 0 || $rid === $productId) continue;
            $ins->execute([
                ':p' => $productId,
                ':r' => $rid,
                ':t' => substr(trim((string)($rel['type'] ?? 'complement')), 0, 40),
                ':s' => (int)($rel['sort_order'] ?? 0),
            ]);
        }
    }

    /** @return array<string,array<int,string>> */
    public function findTags(int $productId): array
    {
        $st = Db::pdo()->prepare('SELECT taxonomy_key, term_value FROM cms_product_tags WHERE product_id = :p ORDER BY id ASC');
        $st->execute([':p' => $productId]);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(string)$row['taxonomy_key']][] = (string)$row['term_value'];
        }
        return $out;
    }

    /** @param array<int,array{taxonomy_key:string,term_value:string}> $tags */
    public function saveTags(int $productId, array $tags): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_product_tags WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_product_tags (product_id, taxonomy_key, term_value, source, ai_suggested, verified) VALUES (:p, :tk, :tv, :s, :ai, :v)');
        foreach ($tags as $t) {
            $tk = trim((string)($t['taxonomy_key'] ?? ''));
            $tv = trim((string)($t['term_value'] ?? ''));
            if ($tk === '' || $tv === '') continue;
            $ins->execute([
                ':p' => $productId,
                ':tk' => $tk,
                ':tv' => $tv,
                ':s' => (string)($t['source'] ?? 'manual'),
                ':ai' => (int)($t['ai_suggested'] ?? 0),
                ':v' => (int)($t['verified'] ?? 1),
            ]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findVideos(int $productId): array
    {
        $st = Db::pdo()->prepare('
            SELECT v.* FROM cms_videos v
            INNER JOIN cms_video_product vp ON vp.video_id = v.id
            WHERE vp.product_id = :p AND v.active = 1
            ORDER BY vp.sort_order ASC, v.id ASC
        ');
        $st->execute([':p' => $productId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $videoIds */
    public function saveVideos(int $productId, array $videoIds): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_video_product WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_video_product (video_id, product_id, sort_order) VALUES (:v, :p, :s)');
        foreach (array_values($videoIds) as $i => $vid) {
            $vid = (int)$vid;
            if ($vid <= 0) continue;
            $ins->execute([':v' => $vid, ':p' => $productId, ':s' => $i]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findFaqs(int $productId): array
    {
        $st = Db::pdo()->prepare('
            SELECT f.* FROM cms_faqs f
            INNER JOIN cms_product_faq pf ON pf.faq_id = f.id
            WHERE pf.product_id = :p AND f.active = 1
            ORDER BY pf.sort_order ASC, f.id ASC
        ');
        $st->execute([':p' => $productId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $faqIds */
    public function saveFaqs(int $productId, array $faqIds): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_product_faq WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_product_faq (faq_id, product_id, sort_order) VALUES (:f, :p, :s)');
        foreach (array_values($faqIds) as $i => $fid) {
            $fid = (int)$fid;
            if ($fid <= 0) continue;
            $ins->execute([':f' => $fid, ':p' => $productId, ':s' => $i]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function searchProducts(string $q, int $excludeId = 0, int $limit = 20): array
    {
        $sql = 'SELECT idprodu, produ, imagen FROM producto WHERE (produ LIKE :q OR codprodu LIKE :q)';
        $params = [':q' => '%' . $q . '%'];
        if ($excludeId > 0) {
            $sql .= ' AND idprodu != :e';
            $params[':e'] = $excludeId;
        }
        $sql .= ' ORDER BY produ ASC LIMIT ' . max(1, min(100, $limit));
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll() ?: [];
    }

    public function computeScore(int $productId, bool $hasImage, bool $hasDescription): int
    {
        $score = 0;
        $content = $this->findContent($productId);
        $fields = ['benefit', 'ideal_for', 'problem', 'results', 'usage', 'advice', 'cta'];
        $filled = 0;
        if ($content) {
            foreach ($fields as $f) {
                if (trim((string)($content[$f] ?? '')) !== '') $filled++;
            }
        }
        $score += min(35, (int)round($filled * 5));

        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM cms_product_tags WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $tags = (int)$st->fetchColumn();
        $score += $tags >= 3 ? 15 : ($tags > 0 ? 5 : 0);

        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM cms_product_relations WHERE product_id = :p AND active = 1');
        $st->execute([':p' => $productId]);
        $score += (int)$st->fetchColumn() > 0 ? 10 : 0;

        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM cms_video_product WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $score += (int)$st->fetchColumn() > 0 ? 10 : 0;

        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM cms_product_faq WHERE product_id = :p');
        $st->execute([':p' => $productId]);
        $score += (int)$st->fetchColumn() > 0 ? 10 : 0;

        if ($hasImage) $score += 10;
        if ($hasDescription) $score += 10;

        return min(100, $score);
    }
}
