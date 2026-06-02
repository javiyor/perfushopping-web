<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AffiliateLedgerRepo
{
    public function balanceAvailableCents(int $userId): int
    {
        $st = Db::pdo()->prepare('SELECT COALESCE(SUM(amount_cents),0) AS s FROM affiliate_ledger WHERE affiliate_user_id=:u AND status=\'available\'');
        $st->execute([':u' => $userId]);
        $v = $st->fetchColumn();
        return (int)$v;
    }

    public function addPendingCommission(int $affiliateUserId, int $orderId, int $amountCents, string $availableAt, string $note = ''): bool
    {
        if ($amountCents <= 0) {
            return false;
        }
        // Unique key prevents duplicates.
        $sql = 'INSERT INTO affiliate_ledger (affiliate_user_id, type, amount_cents, order_id, status, available_at, note, created_at) '
            . 'VALUES (:u,\'commission_earn\',:a,:o,\'pending\',:av,:n,NOW())';
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute([':u' => $affiliateUserId, ':a' => $amountCents, ':o' => $orderId, ':av' => $availableAt, ':n' => $note]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function addBlocked(int $affiliateUserId, int $orderId, string $note): void
    {
        $sql = 'INSERT INTO affiliate_ledger (affiliate_user_id, type, amount_cents, order_id, status, available_at, note, created_at) '
            . 'VALUES (:u,\'commission_blocked\',0,:o,\'available\',NULL,:n,NOW())';
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute([':u' => $affiliateUserId, ':o' => $orderId, ':n' => $note]);
        } catch (\PDOException $e) {
            // ignore duplicates
        }
    }

    public function addSpendOnOrder(int $affiliateUserId, int $orderId, int $amountCents, string $note = ''): void
    {
        if ($amountCents <= 0) {
            return;
        }
        $st = Db::pdo()->prepare(
            'INSERT INTO affiliate_ledger (affiliate_user_id, type, amount_cents, order_id, status, available_at, note, created_at) '
            . 'VALUES (:u,\'spend_on_order\',:a,:o,\'available\',NULL,:n,NOW())'
        );
        $st->execute([':u' => $affiliateUserId, ':a' => -abs($amountCents), ':o' => $orderId, ':n' => $note]);
    }

    public function releaseDueCommissions(): int
    {
        $st = Db::pdo()->prepare("UPDATE affiliate_ledger SET status='available' WHERE status='pending' AND available_at IS NOT NULL AND available_at <= NOW()");
        $st->execute();
        return $st->rowCount();
    }

    /** @return array<int, array<string,mixed>> */
    public function listRecent(int $userId, int $limit = 80): array
    {
        $limit = max(1, min(200, $limit));
        $st = Db::pdo()->prepare('SELECT type, amount_cents, status, available_at, note, order_id, created_at FROM affiliate_ledger WHERE affiliate_user_id=:u ORDER BY created_at DESC LIMIT ' . $limit);
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }
}
