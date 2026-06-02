<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class UserRepo
{
    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM web_users WHERE email=:e LIMIT 1');
        $st->execute([':e' => $email]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM web_users WHERE id=:i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function create(string $email, string $name, string $phone, string $role, string $whStatus): int
    {
        $phoneKey = preg_replace('/[^0-9]/', '', $phone) ?? '';
        $pdo = Db::pdo();
        try {
            $st = $pdo->prepare('INSERT INTO web_users (email, name, phone, phone_key, role, wholesale_status, created_at) VALUES (:e,:n,:p,:pk,:r,:w,NOW())');
            $st->execute([':e' => $email, ':n' => $name, ':p' => $phone, ':pk' => $phoneKey, ':r' => $role, ':w' => $whStatus]);
        } catch (\PDOException $e) {
            // Backwards compatibility if new columns weren't applied yet.
            $st = $pdo->prepare('INSERT INTO web_users (email, name, phone, role, wholesale_status, created_at) VALUES (:e,:n,:p,:r,:w,NOW())');
            $st->execute([':e' => $email, ':n' => $name, ':p' => $phone, ':r' => $role, ':w' => $whStatus]);
        }
        return (int)Db::pdo()->lastInsertId();
    }

    public function setPassword(int $userId, string $hash): void
    {
        $st = Db::pdo()->prepare('UPDATE web_users SET password_hash=:h, email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=:i');
        $st->execute([':h' => $hash, ':i' => $userId]);
    }

    public function touchLogin(int $userId): void
    {
        $st = Db::pdo()->prepare('UPDATE web_users SET last_login_at=NOW() WHERE id=:i');
        $st->execute([':i' => $userId]);
    }

    public function setWholesaleStatus(int $userId, string $status, ?int $clienteId): void
    {
        $st = Db::pdo()->prepare('UPDATE web_users SET wholesale_status=:s, cliente_id=:c WHERE id=:i');
        $st->execute([':s' => $status, ':c' => $clienteId, ':i' => $userId]);
    }

    public function setAffiliateReferrerIfEmpty(int $userId, int $referrerUserId): void
    {
        if ($userId <= 0 || $referrerUserId <= 0 || $userId === $referrerUserId) {
            return;
        }
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET affiliate_referrer_user_id=:r, affiliate_assigned_at=NOW() WHERE id=:i AND affiliate_referrer_user_id IS NULL');
            $st->execute([':r' => $referrerUserId, ':i' => $userId]);
        } catch (\PDOException $e) {
            // Column might not exist yet.
        }
    }

    public function setProfileAddressIfEmpty(int $userId, string $address, string $city, string $postalCode, int $provinceCodprov): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT address, city FROM web_users WHERE id=:i LIMIT 1');
        $st->execute([':i' => $userId]);
        $r = $st->fetch();
        if (!$r) {
            return;
        }
        $curAddr = trim((string)($r['address'] ?? ''));
        $curCity = trim((string)($r['city'] ?? ''));
        if ($curAddr !== '' || $curCity !== '') {
            return;
        }

        $addrKey = $this->normKey($address);
        $cityKey = $this->normKey($city);

        try {
            $up = $pdo->prepare('UPDATE web_users SET address=:a, city=:c, postal_code=:p, province_codprov=:pr, addr_key=:ak, city_key=:ck WHERE id=:i');
            $up->execute([':a' => $address, ':c' => $city, ':p' => $postalCode, ':pr' => $provinceCodprov, ':ak' => $addrKey, ':ck' => $cityKey, ':i' => $userId]);
        } catch (\PDOException $e) {
            // Columns might not exist yet.
        }
    }

    private function normKey(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? '';
        $s = preg_replace('/\s+/', ' ', $s) ?? '';
        return trim($s);
    }
}
