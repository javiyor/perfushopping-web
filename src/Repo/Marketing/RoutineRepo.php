<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class RoutineRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_routines';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_routines WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_routines WHERE slug = :s AND active = 1 LIMIT 1');
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
            ':d' => trim((string)($data['description'] ?? '')),
            ':p' => trim((string)($data['problem'] ?? '')),
            ':er' => trim((string)($data['expected_result'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':so' => (int)($data['sort_order'] ?? 0),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
            ':og' => trim((string)($data['og_image'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_routines SET name=:n, slug=:s, image=:im, description=:d, problem=:p, expected_result=:er, active=:a, sort_order=:so, seo_title=:st, seo_description=:sd, og_image=:og WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_routines (name, slug, image, description, problem, expected_result, active, sort_order, seo_title, seo_description, og_image) VALUES (:n, :s, :im, :d, :p, :er, :a, :so, :st, :sd, :og)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_routines WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findItems(int $routineId): array
    {
        $st = Db::pdo()->prepare('
            SELECT i.*, p.produ, p.imagen, p.precio, p.precio1, p.iva, iv.tiva
            FROM cms_routine_items i
            INNER JOIN producto p ON p.idprodu = i.product_id
            LEFT JOIN ivaprodu iv ON iv.codivaprodu = p.iva
            WHERE i.routine_id = :id
            ORDER BY i.step_order ASC, i.id ASC
        ');
        $st->execute([':id' => $routineId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,array{product_id:int,step_order:int,instructions:string,optional:int}> $items */
    public function setItems(int $routineId, array $items): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_routine_items WHERE routine_id = :id');
        $st->execute([':id' => $routineId]);
        $ins = Db::pdo()->prepare('INSERT INTO cms_routine_items (routine_id, product_id, step_order, instructions, optional) VALUES (:rid, :pid, :so, :ins, :opt)');
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            if ($pid <= 0) continue;
            $ins->execute([
                ':rid' => $routineId,
                ':pid' => $pid,
                ':so' => (int)($it['step_order'] ?? 0),
                ':ins' => trim((string)($it['instructions'] ?? '')),
                ':opt' => (int)($it['optional'] ?? 0),
            ]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findNeeds(int $routineId): array
    {
        $st = Db::pdo()->prepare('
            SELECT n.* FROM cms_needs n
            INNER JOIN cms_routine_need rn ON rn.need_id = n.id
            WHERE rn.routine_id = :id AND n.active = 1
            ORDER BY rn.sort_order ASC, n.name ASC
        ');
        $st->execute([':id' => $routineId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function findTopics(int $routineId): array
    {
        $st = Db::pdo()->prepare('
            SELECT t.* FROM cms_topics t
            INNER JOIN cms_routine_topic rt ON rt.topic_id = t.id
            WHERE rt.routine_id = :id AND t.active = 1
            ORDER BY rt.sort_order ASC, t.name ASC
        ');
        $st->execute([':id' => $routineId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function findVideos(int $routineId): array
    {
        $st = Db::pdo()->prepare('
            SELECT v.* FROM cms_videos v
            INNER JOIN cms_routine_video rv ON rv.video_id = v.id
            WHERE rv.routine_id = :id AND v.active = 1
            ORDER BY rv.sort_order ASC, v.title ASC
        ');
        $st->execute([':id' => $routineId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function findArticles(int $routineId): array
    {
        $st = Db::pdo()->prepare('
            SELECT a.* FROM cms_articles a
            INNER JOIN cms_routine_article ra ON ra.article_id = a.id
            WHERE ra.routine_id = :id AND a.active = 1 AND a.status = \'published\'
            ORDER BY ra.sort_order ASC, a.title ASC
        ');
        $st->execute([':id' => $routineId]);
        return $st->fetchAll() ?: [];
    }

    /** @param array<int,int> $ids */
    public function setNeeds(int $routineId, array $ids): void
    {
        $this->setLink('cms_routine_need', 'need_id', $routineId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setTopics(int $routineId, array $ids): void
    {
        $this->setLink('cms_routine_topic', 'topic_id', $routineId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setVideos(int $routineId, array $ids): void
    {
        $this->setLink('cms_routine_video', 'video_id', $routineId, $ids);
    }

    /** @param array<int,int> $ids */
    public function setArticles(int $routineId, array $ids): void
    {
        $this->setLink('cms_routine_article', 'article_id', $routineId, $ids);
    }

    /** @param array<int,int> $ids */
    private function setLink(string $table, string $fk, int $routineId, array $ids): void
    {
        $st = Db::pdo()->prepare("DELETE FROM {$table} WHERE routine_id = :id");
        $st->execute([':id' => $routineId]);
        $ins = Db::pdo()->prepare("INSERT INTO {$table} (routine_id, {$fk}, sort_order) VALUES (:rid, :fid, :so)");
        foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $fid) {
            if ($fid <= 0) continue;
            $ins->execute([':rid' => $routineId, ':fid' => $fid, ':so' => $i]);
        }
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'rutina';
        }
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'rutina';
        }
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_routines WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) {
                break;
            }
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
