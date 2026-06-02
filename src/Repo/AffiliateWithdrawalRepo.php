<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AffiliateWithdrawalRepo
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function listForUser(int $userId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM affiliate_withdrawals WHERE affiliate_user_id=:u ORDER BY created_at DESC LIMIT 50');
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function listRequested(): array
    {
        $st = Db::pdo()->query("SELECT w.*, u.email, u.name FROM affiliate_withdrawals w INNER JOIN web_users u ON u.id=w.affiliate_user_id WHERE w.status IN ('requested','approved') ORDER BY w.created_at ASC LIMIT 200");
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM affiliate_withdrawals WHERE id=:i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /**
     * Creates a withdrawal request: debits full credit and sets payout to 50%.
     */
    public function createRequest(int $userId, int $creditAmountCents, string $destination): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Check balance
            $bal = (new AffiliateLedgerRepo())->balanceAvailableCents($userId);
            if ($creditAmountCents <= 0 || $creditAmountCents > $bal) {
                throw new \RuntimeException('Saldo insuficiente.');
            }
            if ($creditAmountCents < 2000000) {
                throw new \RuntimeException('El minimo de retiro es $20.000.');
            }
            $payout = (int)floor($creditAmountCents / 2);

            $st = $pdo->prepare(
                "INSERT INTO affiliate_withdrawals (affiliate_user_id, credit_amount_cents, payout_amount_cents, destination, status, created_at, updated_at)
                 VALUES (:u,:c,:p,:d,'requested',NOW(),NOW())"
            );
            $st->execute([':u' => $userId, ':c' => $creditAmountCents, ':p' => $payout, ':d' => $destination]);
            $withdrawalId = (int)$pdo->lastInsertId();

            // Debit credit now
            $st2 = $pdo->prepare(
                "INSERT INTO affiliate_ledger (affiliate_user_id, type, amount_cents, order_id, status, available_at, note, created_at)
                 VALUES (:u,'withdraw_request',:a,NULL,'available',NULL,:n,NOW())"
            );
            $st2->execute([
                ':u' => $userId,
                ':a' => -abs($creditAmountCents),
                ':n' => 'Solicitud de retiro #' . $withdrawalId,
            ]);

            $pdo->commit();
            return $withdrawalId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $id, string $status): void
    {
        $st = Db::pdo()->prepare('UPDATE affiliate_withdrawals SET status=:s, updated_at=NOW() WHERE id=:i');
        $st->execute([':s' => $status, ':i' => $id]);
    }

    public function rejectAndRefund(int $id, int $adminId, string $reason = ''): void
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $w = $this->find($id);
            if (!$w) {
                throw new \RuntimeException('Retiro no encontrado.');
            }
            if (!in_array((string)$w['status'], ['requested','approved'], true)) {
                throw new \RuntimeException('Estado invalido para rechazar.');
            }
            $this->setStatus($id, 'rejected');
            $userId = (int)$w['affiliate_user_id'];
            $credit = (int)$w['credit_amount_cents'];

            $st = $pdo->prepare(
                "INSERT INTO affiliate_ledger (affiliate_user_id, type, amount_cents, order_id, status, available_at, note, created_at)
                 VALUES (:u,'adjustment',:a,NULL,'available',NULL,:n,NOW())"
            );
            $note = 'Rechazo retiro #' . $id;
            if ($reason !== '') {
                $note .= ' - ' . $reason;
            }
            $st->execute([':u' => $userId, ':a' => abs($credit), ':n' => $note]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
