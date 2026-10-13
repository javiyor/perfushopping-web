<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Service\PricingService;

/** Snapshots del carrito web para medir abandono real (aunque no llegue al checkout). */
final class CartSnapshotRepo
{
    private static bool $ready = false;

    private function ensure(): void
    {
        if (self::$ready) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS cart_snapshots (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            email VARCHAR(191) DEFAULT NULL,
            items_json MEDIUMTEXT NOT NULL,
            items_count INT UNSIGNED NOT NULL DEFAULT 0,
            total_cents INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'abierto',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_cart_snapshots_session (session_id),
            KEY idx_cart_snapshots_status_updated (status, updated_at),
            KEY idx_cart_snapshots_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$ready = true;
    }

    /**
     * Guarda/actualiza el snapshot abierto de la sesión. Con carrito vacío lo marca vaciado.
     * @param array<int,int> $items idcodgusto => qty
     */
    public function capture(array $items, ?int $userId, ?string $email, bool $isWholesale): void
    {
        $this->ensure();
        $sid = (string)session_id();
        if ($sid === '') {
            return;
        }
        $pdo = Db::pdo();

        if ($userId) {
            $st = $pdo->prepare("SELECT id FROM cart_snapshots WHERE status='abierto' AND (session_id=:s OR user_id=:u) ORDER BY updated_at DESC LIMIT 1");
            $st->execute([':s' => $sid, ':u' => $userId]);
        } else {
            $st = $pdo->prepare("SELECT id FROM cart_snapshots WHERE status='abierto' AND session_id=:s ORDER BY updated_at DESC LIMIT 1");
            $st->execute([':s' => $sid]);
        }
        $id = $st->fetchColumn();

        $lines = [];
        $total = 0;
        $count = 0;
        if ($items) {
            $repo = new ProductRepo();
            $pricing = new PricingService();
            foreach ($items as $idcodgusto => $qty) {
                $idcodgusto = (int)$idcodgusto;
                $qty = (int)$qty;
                if ($idcodgusto <= 0 || $qty <= 0) {
                    continue;
                }
                $v = $repo->findVariant($idcodgusto);
                if (!$v) {
                    continue;
                }
                $baseNet = (float)($isWholesale ? $v['precio1'] : $v['precio']);
                $unitNet = $pricing->cents((new PromoWebRepo())->effectiveNet((int)($v['idprodu'] ?? 0), $baseNet));
                $lineNet = $unitNet * $qty;
                $lineTotal = $lineNet + $pricing->ivaCents($lineNet, (float)($v['tiva'] ?? 0));
                $total += $lineTotal;
                $count += $qty;
                $lines[] = [
                    'idcodgusto' => $idcodgusto,
                    'qty' => $qty,
                    'produ' => (string)($v['produ'] ?? ''),
                    'nomgusto' => (string)($v['nomgusto'] ?? ''),
                    'line_total_cents' => $lineTotal,
                ];
            }
        }

        if (!$lines) {
            if ($id !== false) {
                $pdo->prepare("UPDATE cart_snapshots SET status='vaciado', updated_at=NOW() WHERE id=:id LIMIT 1")
                    ->execute([':id' => (int)$id]);
            }
            return;
        }

        $payload = json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $emailClean = $email !== null && trim($email) !== '' ? trim($email) : null;
        if ($id !== false) {
            $upd = $pdo->prepare("UPDATE cart_snapshots
                SET session_id=:s, user_id=COALESCE(user_id,:u), email=COALESCE(email,:e),
                    items_json=:j, items_count=:c, total_cents=:t, updated_at=NOW()
                WHERE id=:id LIMIT 1");
            $upd->execute([
                ':s' => $sid, ':u' => $userId, ':e' => $emailClean,
                ':j' => $payload, ':c' => $count, ':t' => $total, ':id' => (int)$id,
            ]);
            return;
        }
        $ins = $pdo->prepare("INSERT INTO cart_snapshots
            (session_id, user_id, email, items_json, items_count, total_cents, status, created_at, updated_at)
            VALUES (:s, :u, :e, :j, :c, :t, 'abierto', NOW(), NOW())");
        $ins->execute([
            ':s' => $sid, ':u' => $userId, ':e' => $emailClean,
            ':j' => $payload, ':c' => $count, ':t' => $total,
        ]);
    }

    /** Marca convertido el snapshot abierto de la sesión (se generó el pedido). */
    public function markConverted(): void
    {
        $this->ensure();
        $sid = (string)session_id();
        if ($sid === '') {
            return;
        }
        Db::pdo()->prepare("UPDATE cart_snapshots SET status='convertido', updated_at=NOW() WHERE session_id=:s AND status='abierto'")
            ->execute([':s' => $sid]);
    }

    /** Carritos abiertos sin tocar hace $hours horas (abandono real, sin checkout). */
    public function countAbandoned(int $hours = 2): int
    {
        $this->ensure();
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM cart_snapshots
            WHERE status='abierto' AND items_count>0 AND updated_at < DATE_SUB(NOW(), INTERVAL :h HOUR)");
        $st->execute([':h' => max(1, $hours)]);
        return (int)$st->fetchColumn();
    }

    /** @return array<int, array<string,mixed>> */
    public function listAbandoned(int $hours = 2, int $limit = 100): array
    {
        $this->ensure();
        $limit = max(1, min(500, $limit));
        $st = Db::pdo()->prepare("SELECT * FROM cart_snapshots
            WHERE status='abierto' AND items_count>0 AND updated_at < DATE_SUB(NOW(), INTERVAL :h HOUR)
            ORDER BY updated_at DESC LIMIT " . $limit);
        $st->execute([':h' => max(1, $hours)]);
        return $st->fetchAll();
    }
}
