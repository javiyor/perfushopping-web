<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class WholesaleRepo
{
    public function submit(int $userId, array $data): int
    {
        $st = Db::pdo()->prepare(
            'INSERT INTO wholesale_requests (user_id, razon_social, cuit, address, city, postal_code, province_codprov, customer_category, notes, submitted_at, decision) VALUES (:u,:rs,:c,:a,:ci,:cp,:pr,:cc,:n,NOW(),\'pending\')'
        );
        try {
            $st->execute([
                ':u' => $userId,
                ':rs' => $data['razon_social'],
                ':c' => $data['cuit'],
                ':a' => $data['address'],
                ':ci' => $data['city'],
                ':cp' => $data['postal_code'],
                ':pr' => (int)$data['province_codprov'],
                ':cc' => $data['customer_category'] ?? 'none',
                ':n' => $data['notes'] ?? null,
            ]);
        } catch (\PDOException $e) {
            $st = Db::pdo()->prepare(
                'INSERT INTO wholesale_requests (user_id, razon_social, cuit, address, city, postal_code, province_codprov, notes, submitted_at, decision) VALUES (:u,:rs,:c,:a,:ci,:cp,:pr,:n,NOW(),\'pending\')'
            );
            $st->execute([
                ':u' => $userId,
                ':rs' => $data['razon_social'],
                ':c' => $data['cuit'],
                ':a' => $data['address'],
                ':ci' => $data['city'],
                ':cp' => $data['postal_code'],
                ':pr' => (int)$data['province_codprov'],
                ':n' => $data['notes'] ?? null,
            ]);
        }
        return (int)Db::pdo()->lastInsertId();
    }

    /** @return array<int, array<string,mixed>> */
    public function pendingList(): array
    {
        try {
            $st = Db::pdo()->query('SELECT wr.*, u.email, u.name, u.phone, u.customer_category AS user_customer_category FROM wholesale_requests wr INNER JOIN web_users u ON u.id=wr.user_id WHERE wr.decision=\'pending\' ORDER BY wr.submitted_at ASC');
            return $st->fetchAll();
        } catch (\PDOException $e) {
            $st = Db::pdo()->query('SELECT wr.*, u.email, u.name, u.phone FROM wholesale_requests wr INNER JOIN web_users u ON u.id=wr.user_id WHERE wr.decision=\'pending\' ORDER BY wr.submitted_at ASC');
            return $st->fetchAll();
        }
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        try {
            $st = Db::pdo()->prepare('SELECT wr.*, u.email, u.name, u.phone, u.customer_category AS user_customer_category FROM wholesale_requests wr INNER JOIN web_users u ON u.id=wr.user_id WHERE wr.id=:i LIMIT 1');
            $st->execute([':i' => $id]);
        } catch (\PDOException $e) {
            $st = Db::pdo()->prepare('SELECT wr.*, u.email, u.name, u.phone FROM wholesale_requests wr INNER JOIN web_users u ON u.id=wr.user_id WHERE wr.id=:i LIMIT 1');
            $st->execute([':i' => $id]);
        }
        $r = $st->fetch();
        return $r ?: null;
    }

    public function decide(int $id, int $reviewedBy, string $decision, ?string $notes): void
    {
        $st = Db::pdo()->prepare('UPDATE wholesale_requests SET decision=:d, reviewed_by=:b, reviewed_at=NOW(), decision_notes=:n WHERE id=:i');
        $st->execute([':d' => $decision, ':b' => $reviewedBy, ':n' => $notes, ':i' => $id]);
    }
}
