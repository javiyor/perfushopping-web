<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class NeedRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_needs';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_needs WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_needs WHERE slug = :s AND active = 1 LIMIT 1');
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
            ':ic' => trim((string)($data['icon'] ?? '')),
            ':im' => trim((string)($data['image'] ?? '')),
            ':ds' => trim((string)($data['description_short'] ?? '')),
            ':dl' => trim((string)($data['description_long'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':so' => (int)($data['sort_order'] ?? 0),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_needs SET name=:n, slug=:s, icon=:ic, image=:im, description_short=:ds, description_long=:dl, active=:a, sort_order=:so, seo_title=:st, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_needs (name, slug, icon, image, description_short, description_long, active, sort_order, seo_title, seo_description, og_image) VALUES (:n, :s, :ic, :im, :ds, :dl, :a, :so, :st, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_needs WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findProducts(int $needId): array
    {
        return $this->findRelated('cms_need_product', 'producto', 'product_id', $needId, 'p.produ ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findVideos(int $needId): array
    {
        return $this->findRelated('cms_need_video', 'cms_videos', 'video_id', $needId, 'v.title ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findArticles(int $needId): array
    {
        return $this->findRelated('cms_need_article', 'cms_articles', 'article_id', $needId, 'a.title ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findFaqs(int $needId): array
    {
        return $this->findRelated('cms_need_faq', 'cms_faqs', 'faq_id', $needId, 'f.question ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findTopics(int $needId): array
    {
        $st = Db::pdo()->prepare('
            SELECT t.* FROM cms_topics t
            INNER JOIN cms_need_topic nt ON nt.topic_id = t.id
            WHERE nt.need_id = :id AND t.active = 1
            ORDER BY nt.sort_order ASC, t.name ASC
        ');
        $st->execute([':id' => $needId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $ids */
    public function setProducts(int $needId, array $ids): void
    {
        $this->setRelated('cms_need_product', 'product_id', $needId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setVideos(int $needId, array $ids): void
    {
        $this->setRelated('cms_need_video', 'video_id', $needId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setArticles(int $needId, array $ids): void
    {
        $this->setRelated('cms_need_article', 'article_id', $needId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setFaqs(int $needId, array $ids): void
    {
        $this->setRelated('cms_need_faq', 'faq_id', $needId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setTopics(int $needId, array $ids): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_need_topic WHERE need_id = :id');
        $st->execute([':id' => $needId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_need_topic (need_id, topic_id, sort_order) VALUES (:nid, :tid, :so)');
        foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $tid) {
            if ($tid <= 0) continue;
            $ins->execute([':nid' => $needId, ':tid' => $tid, ':so' => $i]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function findRelated(string $pivot, string $table, string $fk, int $needId, string $order): array
    {
        $active = $table === 'producto' ? '' : ' AND t.active = 1';
        $sql = "SELECT t.* FROM {$table} t INNER JOIN {$pivot} p ON p.{$fk} = t.id WHERE p.need_id = :id{$active} ORDER BY p.sort_order ASC, {$order}";
        $st = Db::pdo()->prepare($sql);
        $st->execute([':id' => $needId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $ids */
    private function setRelated(string $pivot, string $fk, int $needId, array $ids): void
    {
        $st = Db::pdo()->prepare("DELETE FROM {$pivot} WHERE need_id = :id");
        $st->execute([':id' => $needId]);
        $ins = Db::pdo()->prepare("INSERT INTO {$pivot} (need_id, {$fk}, sort_order) VALUES (:nid, :fid, :so)");
        foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $fid) {
            if ($fid <= 0) continue;
            $ins->execute([':nid' => $needId, ':fid' => $fid, ':so' => $i]);
        }
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'necesidad';
        }
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'necesidad';
        }
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_needs WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) {
                break;
            }
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
