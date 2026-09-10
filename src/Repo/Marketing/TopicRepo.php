<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class TopicRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_topics';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_topics WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_topics WHERE slug = :s AND active = 1 LIMIT 1');
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
            ':im' => trim((string)($data['image'] ?? '')),
            ':co' => trim((string)($data['cover'] ?? '')),
            ':ds' => trim((string)($data['description_short'] ?? '')),
            ':dl' => trim((string)($data['description_long'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':so' => (int)($data['sort_order'] ?? 0),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_topics SET name=:n, slug=:s, image=:im, cover=:co, description_short=:ds, description_long=:dl, active=:a, sort_order=:so, seo_title=:st, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_topics (name, slug, image, cover, description_short, description_long, active, sort_order, seo_title, seo_description, og_image) VALUES (:n, :s, :im, :co, :ds, :dl, :a, :so, :st, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_topics WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findProducts(int $topicId): array
    {
        return $this->findRelated('cms_topic_product', 'producto', 'product_id', $topicId, 'p.produ ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findVideos(int $topicId): array
    {
        return $this->findRelated('cms_topic_video', 'cms_videos', 'video_id', $topicId, 'v.title ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findArticles(int $topicId): array
    {
        return $this->findRelated('cms_topic_article', 'cms_articles', 'article_id', $topicId, 'a.title ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function findFaqs(int $topicId): array
    {
        return $this->findRelated('cms_topic_faq', 'cms_faqs', 'faq_id', $topicId, 'f.question ASC');
    }

    /** @param array<int,int> $ids */
    public function setProducts(int $topicId, array $ids): void
    {
        $this->setRelated('cms_topic_product', 'product_id', $topicId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setVideos(int $topicId, array $ids): void
    {
        $this->setRelated('cms_topic_video', 'video_id', $topicId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setArticles(int $topicId, array $ids): void
    {
        $this->setRelated('cms_topic_article', 'article_id', $topicId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setFaqs(int $topicId, array $ids): void
    {
        $this->setRelated('cms_topic_faq', 'faq_id', $topicId, $ids);
    }

    /** @return array<int,array<string,mixed>> */
    private function findRelated(string $pivot, string $table, string $fk, int $topicId, string $order): array
    {
        $active = $table === 'producto' ? '' : ' AND t.active = 1';
        $sql = "SELECT t.* FROM {$table} t INNER JOIN {$pivot} p ON p.{$fk} = t.id WHERE p.topic_id = :id{$active} ORDER BY p.sort_order ASC, {$order}";
        $st = Db::pdo()->prepare($sql);
        $st->execute([':id' => $topicId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $ids */
    private function setRelated(string $pivot, string $fk, int $topicId, array $ids): void
    {
        $st = Db::pdo()->prepare("DELETE FROM {$pivot} WHERE topic_id = :id");
        $st->execute([':id' => $topicId]);
        $ins = Db::pdo()->prepare("INSERT INTO {$pivot} (topic_id, {$fk}, sort_order) VALUES (:tid, :fid, :so)");
        foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $fid) {
            if ($fid <= 0) continue;
            $ins->execute([':tid' => $topicId, ':fid' => $fid, ':so' => $i]);
        }
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'tema';
        }
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'tema';
        }
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_topics WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) {
                break;
            }
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
