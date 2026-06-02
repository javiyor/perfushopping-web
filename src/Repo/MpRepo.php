<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class MpRepo
{
    public function upsertPreference(int $orderId, string $preferenceId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT id FROM mp_payments WHERE order_id=:o LIMIT 1');
        $st->execute([':o' => $orderId]);
        $id = $st->fetchColumn();
        if ($id === false) {
            $ins = $pdo->prepare('INSERT INTO mp_payments (order_id, preference_id, created_at, updated_at) VALUES (:o,:p,NOW(),NOW())');
            $ins->execute([':o' => $orderId, ':p' => $preferenceId]);
            return;
        }
        $upd = $pdo->prepare('UPDATE mp_payments SET preference_id=:p, updated_at=NOW() WHERE order_id=:o');
        $upd->execute([':p' => $preferenceId, ':o' => $orderId]);
    }

    public function upsertPayment(int $orderId, int $paymentId, string $status, string $detail, string $raw): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT id FROM mp_payments WHERE order_id=:o LIMIT 1');
        $st->execute([':o' => $orderId]);
        $id = $st->fetchColumn();
        if ($id === false) {
            $ins = $pdo->prepare('INSERT INTO mp_payments (order_id, payment_id, status, status_detail, raw_json, created_at, updated_at) VALUES (:o,:pid,:s,:d,:r,NOW(),NOW())');
            $ins->execute([':o' => $orderId, ':pid' => $paymentId, ':s' => $status, ':d' => $detail, ':r' => $raw]);
            return;
        }
        $upd = $pdo->prepare('UPDATE mp_payments SET payment_id=:pid, status=:s, status_detail=:d, raw_json=:r, updated_at=NOW() WHERE order_id=:o');
        $upd->execute([':o' => $orderId, ':pid' => $paymentId, ':s' => $status, ':d' => $detail, ':r' => $raw]);
    }

    public function storeWebhook(string $eventKey, ?string $topic, string $payload): bool
    {
        $pdo = Db::pdo();
        try {
            $st = $pdo->prepare('INSERT INTO mp_webhook_events (event_key, topic, payload, received_at) VALUES (:k,:t,:p,NOW())');
            $st->execute([':k' => $eventKey, ':t' => $topic, ':p' => $payload]);
            return true;
        } catch (\PDOException $e) {
            // Duplicate key => already processed
            return false;
        }
    }
}
