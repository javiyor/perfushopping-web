<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class EmailMarketingRepo
{
    private static bool $ready = false;

    private function ensure(): void
    {
        if (self::$ready) return;
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS email_marketing_unsubscribes (
            email VARCHAR(190) NOT NULL PRIMARY KEY,
            token CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            unsubscribed_at DATETIME DEFAULT NULL,
            UNIQUE KEY uq_email_marketing_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$ready = true;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** @param array<int,string> $emails @return array<string,bool> */
    public function unsubscribeMap(array $emails): array
    {
        $this->ensure();
        $normalized = array_values(array_unique(array_filter(array_map(fn($email) => $this->normalizeEmail((string)$email), $emails))));
        $out = [];
        foreach (array_chunk($normalized, 300) as $chunk) {
            $st = Db::pdo()->prepare('SELECT email FROM email_marketing_unsubscribes WHERE unsubscribed_at IS NOT NULL AND email IN ('
                . implode(',', array_fill(0, count($chunk), '?')) . ')');
            $st->execute($chunk);
            foreach ($st->fetchAll() as $row) $out[(string)$row['email']] = true;
        }
        return $out;
    }

    public function tokenForEmail(string $email): ?string
    {
        $this->ensure();
        $email = $this->normalizeEmail($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
        $st = Db::pdo()->prepare('SELECT token, unsubscribed_at FROM email_marketing_unsubscribes WHERE email = :email LIMIT 1');
        $st->execute([':email' => $email]);
        $row = $st->fetch();
        if ($row) return empty($row['unsubscribed_at']) ? (string)$row['token'] : null;

        for ($i = 0; $i < 3; $i++) {
            $token = bin2hex(random_bytes(32));
            try {
                Db::pdo()->prepare('INSERT IGNORE INTO email_marketing_unsubscribes (email, token, created_at) VALUES (:email, :token, NOW())')
                    ->execute([':email' => $email, ':token' => $token]);
            } catch (\Throwable $e) {
                continue;
            }
            $st->execute([':email' => $email]);
            $row = $st->fetch();
            if ($row) return empty($row['unsubscribed_at']) ? (string)$row['token'] : null;
        }
        throw new \RuntimeException('No se pudo crear el enlace de desuscripción.');
    }

    public function emailByToken(string $token): ?string
    {
        $this->ensure();
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        $st = Db::pdo()->prepare('SELECT email FROM email_marketing_unsubscribes WHERE token = :token LIMIT 1');
        $st->execute([':token' => $token]);
        $email = $st->fetchColumn();
        return $email ? (string)$email : null;
    }

    public function unsubscribe(string $token): ?string
    {
        $email = $this->emailByToken($token);
        if ($email === null) return null;
        Db::pdo()->prepare('UPDATE email_marketing_unsubscribes SET unsubscribed_at = COALESCE(unsubscribed_at, NOW()) WHERE token = :token LIMIT 1')
            ->execute([':token' => $token]);
        return $email;
    }
}
