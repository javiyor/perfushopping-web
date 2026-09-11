<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class AnalyticsRepo
{
    public function track(string $eventType, array $data): void
    {
        $st = Db::pdo()->prepare('INSERT INTO cms_analytics_events (event_type, session_id, url, path, product_id, value, payload, created_at) VALUES (:t, :s, :u, :p, :pid, :v, :pl, NOW())');
        $st->execute([
            ':t' => $eventType,
            ':s' => substr((string)($data['session_id'] ?? ''), 0, 64),
            ':u' => substr((string)($data['url'] ?? ''), 0, 500),
            ':p' => substr((string)($data['path'] ?? ''), 0, 255),
            ':pid' => (int)($data['product_id'] ?? 0) ?: null,
            ':v' => is_numeric($data['value'] ?? null) ? (float)$data['value'] : null,
            ':pl' => !empty($data['payload']) ? json_encode($data['payload']) : null,
        ]);
    }

    public function summaryByType(string $since): array
    {
        $st = Db::pdo()->prepare('SELECT event_type, COUNT(*) AS c FROM cms_analytics_events WHERE created_at >= :s GROUP BY event_type ORDER BY c DESC');
        $st->execute([':s' => $since]);
        return $st->fetchAll() ?: [];
    }

    public function topPaths(string $since, int $limit = 20): array
    {
        $st = Db::pdo()->prepare('SELECT path, COUNT(*) AS c FROM cms_analytics_events WHERE created_at >= :s AND path IS NOT NULL GROUP BY path ORDER BY c DESC LIMIT ' . max(1, min(100, $limit)));
        $st->execute([':s' => $since]);
        return $st->fetchAll() ?: [];
    }

    public function topProducts(string $since, int $limit = 20): array
    {
        $st = Db::pdo()->prepare('SELECT product_id, COUNT(*) AS c FROM cms_analytics_events WHERE created_at >= :s AND product_id IS NOT NULL GROUP BY product_id ORDER BY c DESC LIMIT ' . max(1, min(100, $limit)));
        $st->execute([':s' => $since]);
        return $st->fetchAll() ?: [];
    }

    public function dailyCounts(string $since, string $eventType): array
    {
        $st = Db::pdo()->prepare('SELECT DATE(created_at) AS d, COUNT(*) AS c FROM cms_analytics_events WHERE created_at >= :s AND event_type = :t GROUP BY d ORDER BY d ASC');
        $st->execute([':s' => $since, ':t' => $eventType]);
        return $st->fetchAll() ?: [];
    }

    public function funnelSteps(): array
    {
        $since = date('Y-m-d', strtotime('-30 days'));
        $out = [];
        foreach (['pageview', 'product_view', 'add_to_cart', 'add_routine', 'checkout_start', 'checkout_complete'] as $t) {
            $st = Db::pdo()->prepare('SELECT COUNT(*) FROM cms_analytics_events WHERE event_type = :t AND created_at >= :s');
            $st->execute([':t' => $t, ':s' => $since]);
            $out[$t] = (int)$st->fetchColumn();
        }
        return $out;
    }
}
