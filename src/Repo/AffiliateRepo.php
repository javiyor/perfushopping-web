<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AffiliateRepo
{
    public function ensureForUser(int $userId): string
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT ref_code FROM affiliates WHERE user_id=:u LIMIT 1');
        $st->execute([':u' => $userId]);
        $ref = $st->fetchColumn();
        if (is_string($ref) && $ref !== '') {
            return $ref;
        }

        for ($i = 0; $i < 10; $i++) {
            $code = $this->genCode();
            try {
                $ins = $pdo->prepare('INSERT INTO affiliates (user_id, ref_code, status, created_at) VALUES (:u,:c,\'active\',NOW())');
                $ins->execute([':u' => $userId, ':c' => $code]);
                return $code;
            } catch (\PDOException $e) {
                // likely duplicate ref_code, retry
            }
        }
        throw new \RuntimeException('No se pudo generar codigo de referido.');
    }

    /** @return array{user_id:int,ref_code:string}|null */
    public function findByCode(string $refCode): ?array
    {
        $refCode = trim($refCode);
        if ($refCode === '') {
            return null;
        }
        $st = Db::pdo()->prepare('SELECT user_id, ref_code FROM affiliates WHERE ref_code=:c AND status=\'active\' LIMIT 1');
        $st->execute([':c' => $refCode]);
        $r = $st->fetch();
        if (!$r) {
            return null;
        }
        return ['user_id' => (int)$r['user_id'], 'ref_code' => (string)$r['ref_code']];
    }

    private function genCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
