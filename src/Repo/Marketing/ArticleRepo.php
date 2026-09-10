<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class ArticleRepo
{
    public function findAll(bool $publishedOnly = false): array
    {
        $sql = 'SELECT * FROM cms_articles';
        if ($publishedOnly) {
            $sql .= " WHERE status = 'published' AND active = 1 AND (published_at IS NULL OR published_at <= NOW())";
        }
        $sql .= ' ORDER BY featured DESC, published_at DESC, id DESC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_articles WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare("SELECT * FROM cms_articles WHERE slug = :s AND status = 'published' AND active = 1 LIMIT 1");
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function findByProductId(int $productId): array
    {
        $st = Db::pdo()->prepare('
            SELECT a.* FROM cms_articles a
            INNER JOIN cms_article_product ap ON ap.article_id = a.id
            WHERE ap.product_id = :p AND a.status = \'published\' AND a.active = 1
            ORDER BY ap.sort_order ASC, a.id ASC
        ');
        $st->execute([':p' => $productId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int,int> */
    public function findProductIdsByArticleId(int $articleId): array
    {
        $st = Db::pdo()->prepare('SELECT product_id FROM cms_article_product WHERE article_id = :a ORDER BY sort_order ASC');
        $st->execute([':a' => $articleId]);
        return array_map(static fn($r) => (int)$r['product_id'], $st->fetchAll() ?: []);
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $slug = $this->uniqueSlug(trim((string)($data['slug'] ?? '')), $id);
        $status = in_array($data['status'] ?? '', ['draft', 'published', 'scheduled'], true) ? $data['status'] : 'draft';
        $publishedAt = trim((string)($data['published_at'] ?? ''));
        $publishedAt = $publishedAt === '' ? null : $publishedAt;
        $fields = [
            ':t' => trim((string)($data['title'] ?? '')),
            ':s' => $slug,
            ':e' => trim((string)($data['excerpt'] ?? '')),
            ':c' => trim((string)($data['content'] ?? '')),
            ':st' => $status,
            ':pa' => $publishedAt,
            ':a' => (int)($data['active'] ?? 1),
            ':stt' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_articles SET title=:t, slug=:s, excerpt=:e, content=:c, status=:st, published_at=:pa, active=:a, seo_title=:stt, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_articles (title, slug, excerpt, content, status, published_at, active, seo_title, seo_description, og_image) VALUES (:t, :s, :e, :c, :st, :pa, :a, :stt, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_articles WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function search(string $q, int $limit = 20): array
    {
        $st = Db::pdo()->prepare('SELECT id, title FROM cms_articles WHERE title LIKE :q AND active = 1 ORDER BY title ASC LIMIT ' . max(1, min(100, $limit)));
        $st->execute([':q' => '%' . $q . '%']);
        return $st->fetchAll() ?: [];
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = $base === '' ? 'articulo' : $base;
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'articulo';
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_articles WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) break;
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
