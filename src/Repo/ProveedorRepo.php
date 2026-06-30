<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ProveedorRepo
{
    public function findAll(): array
    {
        $st = Db::pdo()->query('
            SELECT p.*, COUNT(pr.idprodu) AS product_count
            FROM proveedo p
            LEFT JOIN producto pr ON pr.codprove = p.codprove
            GROUP BY p.idprovee
            ORDER BY p.razon ASC
        ');
        return $st->fetchAll();
    }

    public function findById(int $idprovee): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM proveedo WHERE idprovee = :i LIMIT 1');
        $st->execute([':i' => $idprovee]);
        return $st->fetch() ?: null;
    }

    public function findByCodprove(string $codprove): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM proveedo WHERE codprove = :c LIMIT 1');
        $st->execute([':c' => $codprove]);
        return $st->fetch() ?: null;
    }

    public function create(string $codprove, string $razon, string $cuit, string $direc, string $tele, string $codpost, string $mail, string $localidad): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO proveedo (codprove, razon, cuit, direc, tele, codpost, mail, Localidad, activo, fealta)
            VALUES (:cp, :r, :c, :d, :t, :po, :m, :l, 1, CURDATE())
        ');
        $st->execute([
            ':cp' => $codprove, ':r' => $razon, ':c' => $cuit, ':d' => $direc,
            ':t' => $tele, ':po' => $codpost, ':m' => $mail, ':l' => $localidad,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function update(int $idprovee, string $codprove, string $razon, string $cuit, string $direc, string $tele, string $codpost, string $mail, string $localidad, int $activo): void
    {
        $st = Db::pdo()->prepare('
            UPDATE proveedo SET codprove=:cp, razon=:r, cuit=:c, direc=:d, tele=:t,
                codpost=:po, mail=:m, Localidad=:l, activo=:a
            WHERE idprovee=:i LIMIT 1
        ');
        $st->execute([
            ':cp' => $codprove, ':r' => $razon, ':c' => $cuit, ':d' => $direc,
            ':t' => $tele, ':po' => $codpost, ':m' => $mail, ':l' => $localidad,
            ':a' => $activo, ':i' => $idprovee,
        ]);
    }

    public function delete(int $idprovee): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET codprove = NULL WHERE codprove = (SELECT codprove FROM proveedo WHERE idprovee = :i)');
        $st->execute([':i' => $idprovee]);
        $st = Db::pdo()->prepare('DELETE FROM proveedo WHERE idprovee = :i LIMIT 1');
        $st->execute([':i' => $idprovee]);
    }

    public function productCount(int $idprovee): int
    {
        $st = Db::pdo()->prepare('
            SELECT COUNT(*) FROM producto pr
            INNER JOIN proveedo p ON p.codprove = pr.codprove
            WHERE p.idprovee = :i
        ');
        $st->execute([':i' => $idprovee]);
        return (int)$st->fetchColumn();
    }
}
