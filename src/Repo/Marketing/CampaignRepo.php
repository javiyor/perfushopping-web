<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class CampaignRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_campaigns';
        if ($activeOnly) {
            $now = date('Y-m-d H:i:s');
            $sql .= " WHERE active = 1 AND (start_at IS NULL OR start_at <= '{$now}') AND (end_at IS NULL OR end_at >= '{$now}')";
        }
        $sql .= ' ORDER BY start_at DESC, id DESC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_campaigns WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_campaigns WHERE slug = :s AND active = 1 LIMIT 1');
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $slug = $this->uniqueSlug(trim((string)($data['slug'] ?? '')), $id);
        if ($slug === '') {
            $slug = $this->uniqueSlug(trim((string)($data['name'] ?? '')), $id);
        }
        $fields = [
            ':n' => trim((string)($data['name'] ?? '')),
            ':s' => $slug,
            ':t' => trim((string)($data['title'] ?? '')),
            ':d' => trim((string)($data['description'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':sat' => $this->nullableDate($data['start_at'] ?? null),
            ':eat' => $this->nullableDate($data['end_at'] ?? null),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_campaigns SET name=:n, slug=:s, title=:t, description=:d, active=:a, start_at=:sat, end_at=:eat, seo_title=:st, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_campaigns (name, slug, title, description, active, start_at, end_at, seo_title, seo_description, og_image) VALUES (:n, :s, :t, :d, :a, :sat, :eat, :st, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_campaigns WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    private function nullableDate(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);
        return $value === '' ? null : $value;
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') $base = 'campaña';
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'campaña';
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_campaigns WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) break;
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
