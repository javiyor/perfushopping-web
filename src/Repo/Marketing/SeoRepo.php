<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class SeoRepo
{
    public function find(string $entityType, int $entityId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_seo_data WHERE entity_type = :t AND entity_id = :i LIMIT 1');
        $st->execute([':t' => $entityType, ':i' => $entityId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_seo_data WHERE slug = :s LIMIT 1');
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function save(string $entityType, int $entityId, array $data): void
    {
        $slug = trim((string)($data['slug'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $meta = trim((string)($data['meta_description'] ?? ''));
        $og = trim((string)($data['og_image'] ?? ''));
        $canonical = trim((string)($data['canonical'] ?? ''));
        $indexable = (int)($data['indexable'] ?? 1);

        $existing = $this->find($entityType, $entityId);
        if ($existing) {
            $st = Db::pdo()->prepare('UPDATE cms_seo_data SET slug=:s, title=:t, meta_description=:m, og_image=:o, canonical=:c, indexable=:i WHERE entity_type=:et AND entity_id=:ei');
        } else {
            $st = Db::pdo()->prepare('INSERT INTO cms_seo_data (entity_type, entity_id, slug, title, meta_description, og_image, canonical, indexable) VALUES (:et, :ei, :s, :t, :m, :o, :c, :i)');
        }
        $st->execute([
            ':et' => $entityType, ':ei' => $entityId, ':s' => $slug,
            ':t' => $title, ':m' => $meta, ':o' => $og, ':c' => $canonical, ':i' => $indexable,
        ]);
    }

    public function delete(string $entityType, int $entityId): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_seo_data WHERE entity_type = :t AND entity_id = :i');
        $st->execute([':t' => $entityType, ':i' => $entityId]);
    }
}
