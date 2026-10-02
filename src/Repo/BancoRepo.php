<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class BancoRepo
{
    public function findAll(): array
    {
        $st = Db::pdo()->query('SELECT idban, nombanc, numbanc FROM bancos ORDER BY nombanc ASC');
        return $st->fetchAll();
    }

    public function findById(int $idban): ?array
    {
        $st = Db::pdo()->prepare('SELECT idban, nombanc, numbanc FROM bancos WHERE idban = :id LIMIT 1');
        $st->execute([':id' => $idban]);
        return $st->fetch() ?: null;
    }

    public function create(string $nombanc, ?int $numbanc): int
    {
        $st = Db::pdo()->prepare('INSERT INTO bancos (nombanc, numbanc) VALUES (:n, :num)');
        $st->execute([':n' => trim($nombanc), ':num' => $numbanc]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function update(int $idban, string $nombanc, ?int $numbanc): void
    {
        $st = Db::pdo()->prepare('UPDATE bancos SET nombanc = :n, numbanc = :num WHERE idban = :id LIMIT 1');
        $st->execute([':n' => trim($nombanc), ':num' => $numbanc, ':id' => $idban]);
    }

    public function delete(int $idban): void
    {
        Db::pdo()->prepare('DELETE FROM bancos WHERE idban = :id LIMIT 1')->execute([':id' => $idban]);
    }

    /** Normaliza un nombre de banco para comparar/guardar (espacios, NBSP, BOM, caracteres de control). */
    public static function nombreNormalizado($v): string
    {
        $v = (string)$v;
        $v = str_replace(["\xEF\xBB\xBF", "\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xE2\x80\x8E", "\xE2\x80\x8F"], '', $v);
        $v = str_replace("\xC2\xA0", ' ', $v);
        $v = preg_replace('/[\x00-\x1F\x7F]/', '', $v) ?? '';
        $v = preg_replace('/ {2,}/', ' ', $v) ?? '';
        return trim($v);
    }
}
