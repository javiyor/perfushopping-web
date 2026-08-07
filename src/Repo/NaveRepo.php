<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class NaveRepo
{
    public function store(int $orderId, string $paymentRequestId, string $returnToken, string $webhookSecret): void
    {
        $st = Db::pdo()->prepare('
            INSERT INTO nave_payments (order_id, payment_request_id, return_token, webhook_secret, created_at, updated_at)
            VALUES (:o, :pr, :rt, :ws, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              payment_request_id = VALUES(payment_request_id),
              return_token = VALUES(return_token),
              webhook_secret = VALUES(webhook_secret),
              updated_at = NOW()
        ');
        $st->execute([
            ':o' => $orderId,
            ':pr' => $paymentRequestId,
            ':rt' => $returnToken,
            ':ws' => $webhookSecret,
        ]);
    }

    public function findByOrder(int $orderId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM nave_payments WHERE order_id = :o ORDER BY id DESC LIMIT 1');
        $st->execute([':o' => $orderId]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByPaymentRequest(string $paymentRequestId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM nave_payments WHERE payment_request_id = :pr ORDER BY id DESC LIMIT 1');
        $st->execute([':pr' => $paymentRequestId]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function storeWebhook(string $eventKey, ?string $paymentRequestId, string $payload): bool
    {
        $pdo = Db::pdo();
        try {
            $st = $pdo->prepare('INSERT INTO nave_webhook_events (event_key, payment_request_id, payload, received_at) VALUES (:k,:pr,:p,NOW())');
            $st->execute([':k' => $eventKey, ':pr' => $paymentRequestId, ':p' => $payload]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function updatePayment(int $orderId, string $paymentId, string $status, string $code, string $raw): void
    {
        $st = Db::pdo()->prepare('
            UPDATE nave_payments
            SET payment_id = :pid, status = :s, payment_code = :code, raw_json = :raw, updated_at = NOW()
            WHERE order_id = :o
        ');
        $st->execute([
            ':o' => $orderId,
            ':pid' => $paymentId,
            ':s' => $status,
            ':code' => $code,
            ':raw' => $raw,
        ]);
    }
}