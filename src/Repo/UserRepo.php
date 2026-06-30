<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class UserRepo
{
    /** @return array<int, string> */
    public static function customerCategoryOptions(): array
    {
        return [
            'none' => 'Sin categoria',
            'peluquero' => 'Peluquero/a',
            'cosmetologa' => 'Cosmetologa',
            'esteticista' => 'Esteticista',
            'manicura' => 'Manicura/o',
            'masajista' => 'Masajista',
            'barbero' => 'Barbero/a',
            'maquillador' => 'Maquillador/a',
            'spa' => 'Spa / centro estetico',
            'revendedor' => 'Revendedor/a',
            'otro' => 'Otro profesional',
        ];
    }

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

    /** @return array<int, array<string,mixed>> */
    public function adminList(string $q = '', int $limit = 120): array
    {
        $limit = max(1, min(300, $limit));
        $q = trim($q);
        $params = [];
        $sql = 'SELECT id, email, name, phone, role, wholesale_status, customer_category, created_at, last_login_at, disabled_at FROM web_users';
        if ($q !== '') {
            $sql .= ' WHERE email LIKE :like OR name LIKE :like OR phone LIKE :like';
            $params[':like'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit;
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (\PDOException $e) {
            $fallback = 'SELECT id, email, name, phone, role, wholesale_status, created_at, last_login_at, disabled_at FROM web_users';
            if ($q !== '') {
                $fallback .= ' WHERE email LIKE :like OR name LIKE :like OR phone LIKE :like';
            }
            $fallback .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit;
            $st = Db::pdo()->prepare($fallback);
            $st->execute($params);
            return $st->fetchAll();
        }
    }

    public function setRole(int $userId, string $role): void
    {
        $st = Db::pdo()->prepare('UPDATE web_users SET role=:r WHERE id=:i LIMIT 1');
        $st->execute([':r' => $role, ':i' => $userId]);
    }

    public function adminUpdate(int $userId, string $email, string $name, string $phone, string $role, string $wholesaleStatus, string $customerCategory): void
    {
        $phoneKey = preg_replace('/[^0-9]/', '', $phone) ?? '';
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET email=:e, name=:n, phone=:p, phone_key=:pk, role=:r, wholesale_status=:w, customer_category=:cc WHERE id=:i LIMIT 1');
            $st->execute([':e' => $email, ':n' => $name, ':p' => $phone, ':pk' => $phoneKey, ':r' => $role, ':w' => $wholesaleStatus, ':cc' => $customerCategory, ':i' => $userId]);
        } catch (\PDOException $e) {
            $st = Db::pdo()->prepare('UPDATE web_users SET email=:e, name=:n, phone=:p, phone_key=:pk, role=:r, wholesale_status=:w WHERE id=:i LIMIT 1');
            $st->execute([':e' => $email, ':n' => $name, ':p' => $phone, ':pk' => $phoneKey, ':r' => $role, ':w' => $wholesaleStatus, ':i' => $userId]);
        }
    }

    public function setDisabled(int $userId, bool $disabled): void
    {
        $st = Db::pdo()->prepare('UPDATE web_users SET disabled_at=' . ($disabled ? 'NOW()' : 'NULL') . ' WHERE id=:i LIMIT 1');
        $st->execute([':i' => $userId]);
    }

    public function deleteUser(int $userId): void
    {
        $st = Db::pdo()->prepare('DELETE FROM web_users WHERE id=:i LIMIT 1');
        $st->execute([':i' => $userId]);
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
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET password_hash=:h, email_verified_at=COALESCE(email_verified_at,NOW()), force_password_change=0 WHERE id=:i');
            $st->execute([':h' => $hash, ':i' => $userId]);
        } catch (\PDOException $e) {
            $st = Db::pdo()->prepare('UPDATE web_users SET password_hash=:h, email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=:i');
            $st->execute([':h' => $hash, ':i' => $userId]);
        }
    }

    public function adminResetPassword(int $userId, string $hash): void
    {
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET password_hash=:h, email_verified_at=COALESCE(email_verified_at,NOW()), force_password_change=1 WHERE id=:i LIMIT 1');
            $st->execute([':h' => $hash, ':i' => $userId]);
        } catch (\PDOException $e) {
            $st = Db::pdo()->prepare('UPDATE web_users SET password_hash=:h, email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=:i LIMIT 1');
            $st->execute([':h' => $hash, ':i' => $userId]);
        }
    }

    public function forcePasswordChange(int $userId, bool $force): void
    {
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET force_password_change=:f WHERE id=:i LIMIT 1');
            $st->execute([':f' => $force ? 1 : 0, ':i' => $userId]);
        } catch (\PDOException $e) {
            // Column might not exist yet.
        }
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

    public function setCustomerCategory(int $userId, string $customerCategory): void
    {
        try {
            $st = Db::pdo()->prepare('UPDATE web_users SET customer_category=:cc WHERE id=:i');
            $st->execute([':cc' => $customerCategory, ':i' => $userId]);
        } catch (\PDOException $e) {
            // Column might not exist yet.
        }
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
