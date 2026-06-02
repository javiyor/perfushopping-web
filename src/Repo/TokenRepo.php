<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class TokenRepo
{
    public function create(int $userId, string $type, string $tokenHash, int $ttlHours): void
    {
        $st = Db::pdo()->prepare('INSERT INTO web_user_tokens (user_id, token_hash, token_type, created_at, expires_at) VALUES (:u,:h,:t,NOW(),DATE_ADD(NOW(), INTERVAL :ttl HOUR))');
        $st->execute([':u' => $userId, ':h' => $tokenHash, ':t' => $type, ':ttl' => $ttlHours]);
    }

    /** @return array<string,mixed>|null */
    public function findUsable(string $tokenHash, string $type): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM web_user_tokens WHERE token_hash=:h AND token_type=:t AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $st->execute([':h' => $tokenHash, ':t' => $type]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function markUsed(int $id): void
    {
        $st = Db::pdo()->prepare('UPDATE web_user_tokens SET used_at=NOW() WHERE id=:i');
        $st->execute([':i' => $id]);
    }
}
