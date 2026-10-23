<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class VideoRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_videos' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY featured DESC, sort_order IS NULL, sort_order ASC, id DESC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findActive(): array
    {
        return $this->findAll(true);
    }

    public function countRecentActive(int $days = 7): int
    {
        $days = max(1, min(30, $days));
        try {
            $st = Db::pdo()->query("SELECT COUNT(*) FROM cms_videos WHERE active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_videos WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_videos WHERE slug = :s AND active = 1 LIMIT 1');
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function findByProductId(int $productId): array
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

    /** @return array<int,int> */
    public function findProductIdsByVideoId(int $videoId): array
    {
        $st = Db::pdo()->prepare('SELECT product_id FROM cms_video_product WHERE video_id = :v ORDER BY sort_order ASC');
        $st->execute([':v' => $videoId]);
        return array_map(static fn($r) => (int)$r['product_id'], $st->fetchAll() ?: []);
    }

    /** @param array<int,int|string> $productIds */
    public function saveProductIds(int $videoId, array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn(int $id): bool => $id > 0)));
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM cms_video_product WHERE video_id = :v')->execute([':v' => $videoId]);
            if ($ids) {
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $valid = $pdo->prepare("SELECT idprodu FROM producto WHERE idprodu IN ({$marks})");
                $valid->execute($ids);
                $validIds = array_map('intval', $valid->fetchAll(\PDO::FETCH_COLUMN) ?: []);
                $insert = $pdo->prepare('INSERT INTO cms_video_product (video_id, product_id, sort_order) VALUES (:v, :p, :s)');
                foreach ($validIds as $order => $productId) {
                    $insert->execute([':v' => $videoId, ':p' => $productId, ':s' => $order]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $slug = $this->uniqueSlug(trim((string)($data['slug'] ?? '')), $id);
        $fields = [
            ':t' => trim((string)($data['title'] ?? '')),
            ':s' => $slug,
            ':ds' => trim((string)($data['description_short'] ?? '')),
            ':dl' => trim((string)($data['description_long'] ?? '')),
            ':th' => trim((string)($data['thumbnail'] ?? '')),
            ':so' => (string)($data['source'] ?? 'youtube'),
            ':u' => trim((string)($data['url'] ?? '')),
            ':du' => (int)($data['duration'] ?? 0) ?: null,
            ':in' => trim((string)($data['instructor'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':f' => (int)($data['featured'] ?? 0),
            ':st' => trim((string)($data['seo_title'] ?? '')),
            ':sd' => trim((string)($data['seo_description'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_videos SET title=:t, slug=:s, description_short=:ds, description_long=:dl, thumbnail=:th, source=:so, url=:u, duration=:du, instructor=:in, active=:a, featured=:f, seo_title=:st, seo_description=:sd WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_videos (title, slug, description_short, description_long, thumbnail, source, url, duration, instructor, active, featured, seo_title, seo_description) VALUES (:t, :s, :ds, :dl, :th, :so, :u, :du, :in, :a, :f, :st, :sd)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_videos WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function search(string $q, int $limit = 20): array
    {
        $st = Db::pdo()->prepare('SELECT id, title, thumbnail FROM cms_videos WHERE title LIKE :q AND active = 1 ORDER BY title ASC LIMIT ' . max(1, min(100, $limit)));
        $st->execute([':q' => '%' . $q . '%']);
        return $st->fetchAll() ?: [];
    }

    public static function youtubeEmbedUrl(string $url): string
    {
        $id = self::youtubeId($url);
        return $id ? 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0' : '';
    }

    public static function youtubeWatchUrl(string $url): string
    {
        $id = self::youtubeId($url);
        return $id ? 'https://www.youtube.com/watch?v=' . $id : '';
    }

    public static function youtubeId(string $url): string
    {
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})~i', $url, $m)) {
            return $m[1];
        }
        return '';
    }

    public static function youtubeThumb(string $url): string
    {
        $id = self::youtubeId($url);
        return $id ? 'https://img.youtube.com/vi/' . $id . '/mqdefault.jpg' : '';
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = $base === '' ? 'video' : $base;
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'video';
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_videos WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) break;
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
