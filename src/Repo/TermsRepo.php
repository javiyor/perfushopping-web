<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class TermsRepo
{
    public function accept(int $userId, string $termsKey, ?string $ip, ?string $ua): void
    {
        $st = Db::pdo()->prepare(
            'INSERT INTO terms_acceptances (user_id, terms_key, accepted_at, ip, user_agent) VALUES (:u,:k,NOW(),:ip,:ua) '
            . 'ON DUPLICATE KEY UPDATE accepted_at=VALUES(accepted_at), ip=VALUES(ip), user_agent=VALUES(user_agent)'
        );
        $st->execute([':u' => $userId, ':k' => $termsKey, ':ip' => $ip, ':ua' => $ua]);
    }
}
