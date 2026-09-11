<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class BrandPageRepo
{
    public function findAll(): array
    {
        return Db::pdo()->query('SELECT b.*, s.nomsub AS brand_name FROM cms_brand_pages b INNER JOIN subrubro s ON s.codsub = b.brand_id ORDER BY s.nomsub ASC')->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT b.*, s.nomsub AS brand_name FROM cms_brand_pages b INNER JOIN subrubro s ON s.codsub = b.brand_id WHERE b.id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT b.*, s.nomsub AS brand_name FROM cms_brand_pages b INNER JOIN subrubro s ON s.codsub = b.brand_id WHERE b.slug = :s AND b.active = 1 LIMIT 1');
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findByBrandId(int $brandId): ?array
    {
        $st = Db::pdo()->prepare('SELECT b.*, s.nomsub AS brand_name FROM cms_brand_pages b INNER JOIN subrubro s ON s.codsub = b.brand_id WHERE b.brand_id = :b LIMIT 1');
        $st->execute([':b' => $brandId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $brandId = (int)($data['brand_id'] ?? 0);
        $slugBase = trim((string)($data['slug'] ?? ''));
        if ($slugBase === '') {
            $st = Db::pdo()->prepare('SELECT nomsub FROM subrubro WHERE codsub = :b LIMIT 1');
            $st->execute([':b' => $brandId]);
            $name = (string)$st->fetchColumn();
            $slugBase = $name;
        }
        $slug = $this->uniqueSlug($slugBase, $id);
        $fields = [
            ':b' => $brandId,
            ':s' => $slug,
            ':t' => trim((string)($data['title'] ?? '')),
            ':d' => trim((string)($data['description'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_brand_pages SET brand_id=:b, slug=:s, title=:t, description=:d, active=:a, seo_title=:st, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_brand_pages (brand_id, slug, title, description, active, seo_title, seo_description, og_image) VALUES (:b, :s, :t, :d, :a, :st, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_brand_pages WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') $base = 'marca';
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'marca';
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_brand_pages WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) break;
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
