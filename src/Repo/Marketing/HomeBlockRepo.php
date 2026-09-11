<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class HomeBlockRepo
{
    /** @return array<int,array<string,mixed>> */
    public function findForHome(): array
    {
        return $this->findForPage('home', 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function findForPage(string $pageType, int $pageId): array
    {
        $now = date('Y-m-d H:i:s');
        $st = Db::pdo()->prepare("
            SELECT * FROM cms_page_blocks
            WHERE page_type = :pt AND page_id = :pi AND active = 1
              AND (start_at IS NULL OR start_at <= :now)
              AND (end_at IS NULL OR end_at >= :now)
            ORDER BY position ASC, id ASC
        ");
        $st->execute([':pt' => $pageType, ':pi' => $pageId, ':now' => $now]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as $i => $row) {
            $rows[$i]['settings'] = $this->decodeSettings($row['settings'] ?? '');
        }
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_page_blocks WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        if ($r) {
            $r['settings'] = $this->decodeSettings($r['settings'] ?? '');
        }
        return $r ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function findAllAdmin(): array
    {
        return $this->findAllAdminForPage('home', 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function findAllAdminForPage(string $pageType, int $pageId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_page_blocks WHERE page_type = :pt AND page_id = :pi ORDER BY position ASC, id ASC');
        $st->execute([':pt' => $pageType, ':pi' => $pageId]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as $i => $row) {
            $rows[$i]['settings'] = $this->decodeSettings($row['settings'] ?? '');
        }
        return $rows;
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $pageType = trim((string)($data['page_type'] ?? 'home'));
        $pageId = (int)($data['page_id'] ?? 0);
        $settings = $data['settings'] ?? [];
        if (is_string($settings)) {
            $settings = $this->decodeSettings($settings);
        }
        $fields = [
            ':bt' => trim((string)($data['block_type'] ?? 'custom')),
            ':pos' => (int)($data['position'] ?? 0),
            ':a' => (int)($data['active'] ?? 1),
            ':sat' => $this->nullableDate($data['start_at'] ?? null),
            ':eat' => $this->nullableDate($data['end_at'] ?? null),
            ':t' => trim((string)($data['title'] ?? '')),
            ':set' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':c' => trim((string)($data['content'] ?? '')),
            ':tet' => trim((string)($data['target_entity_type'] ?? '')),
            ':tei' => (int)($data['target_entity_id'] ?? 0),
            ':seg' => trim((string)($data['segment'] ?? '')),
            ':td' => trim((string)($data['tracking_data'] ?? '')),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_page_blocks SET block_type=:bt, position=:pos, active=:a, start_at=:sat, end_at=:eat, title=:t, settings=:set, content=:c, target_entity_type=:tet, target_entity_id=:tei, segment=:seg, tracking_data=:td WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_page_blocks (page_type, page_id, block_type, position, active, start_at, end_at, title, settings, content, target_entity_type, target_entity_id, segment, tracking_data) VALUES (:pt, :pi, :bt, :pos, :a, :sat, :eat, :t, :set, :c, :tet, :tei, :seg, :td)');
        $st->execute([
            ':pt' => $pageType,
            ':pi' => $pageId,
        ] + $fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_page_blocks WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $st = Db::pdo()->prepare('UPDATE cms_page_blocks SET active = NOT active WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @param array<int,int> $ids */
    public function reorder(array $ids): void
    {
        $st = Db::pdo()->prepare('UPDATE cms_page_blocks SET position = :pos WHERE id = :id');
        foreach (array_values($ids) as $pos => $id) {
            $st->execute([':pos' => $pos, ':id' => (int)$id]);
        }
    }

    private function decodeSettings(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }

    private function nullableDate(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);
        if ($value === '') return null;
        return $value;
    }
}
