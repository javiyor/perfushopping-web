<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ProveedorRepo
{
    public function findAll(string $q = ''): array
    {
        $q = trim($q);
        if ($q === '') {
            $st = Db::pdo()->query('
                SELECT p.*, COUNT(pr.idprodu) AS product_count
                FROM proveedo p
                LEFT JOIN producto pr ON pr.codprove = p.idprovee
                GROUP BY p.idprovee
                ORDER BY p.razon ASC
            ');
            return $st->fetchAll();
        }
        $st = Db::pdo()->prepare('
            SELECT p.*, COUNT(pr.idprodu) AS product_count
            FROM proveedo p
            LEFT JOIN producto pr ON pr.codprove = p.idprovee
            WHERE p.razon LIKE :like OR p.codprove LIKE :like OR p.cuit LIKE :like
            GROUP BY p.idprovee
            ORDER BY p.razon ASC
        ');
        $like = '%' . $q . '%';
        $st->execute([':like' => $like]);
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

    public function create(string $razon, string $cuit, string $direc, string $tele, string $codpost, string $mail, string $localidad): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // A temporary numeric code satisfies legacy NOT NULL/unique schemas until the generated ID exists.
            do {
                $temporaryCode = (string)random_int(100000000, 2000000000);
            } while ($this->findByCodprove($temporaryCode));

            $st = $pdo->prepare('
                INSERT INTO proveedo (codprove, razon, cuit, direc, tele, codpost, mail, Localidad, activo, fealta)
                VALUES (:cp, :r, :c, :d, :t, :po, :m, :l, 1, CURDATE())
            ');
            $st->execute([
                ':cp' => $temporaryCode, ':r' => $razon, ':c' => $cuit, ':d' => $direc,
                ':t' => $tele, ':po' => $codpost, ':m' => $mail, ':l' => $localidad,
            ]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE proveedo SET codprove=:code WHERE idprovee=:id LIMIT 1')
                ->execute([':code'=>(string)$id, ':id'=>$id]);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $idprovee, string $codprove, string $razon, string $cuit, string $direc, string $tele, string $codpost, string $mail, string $localidad, int $activo): void
    {
        if ($codprove !== '') {
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
            return;
        }
        Db::pdo()->prepare('
            UPDATE proveedo SET razon=:r, cuit=:c, direc=:d, tele=:t,
                codpost=:po, mail=:m, Localidad=:l, activo=:a
            WHERE idprovee=:i LIMIT 1
        ')->execute([
            ':r' => $razon, ':c' => $cuit, ':d' => $direc, ':t' => $tele,
            ':po' => $codpost, ':m' => $mail, ':l' => $localidad,
            ':a' => $activo, ':i' => $idprovee,
        ]);
    }

    public function delete(int $idprovee): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET codprove = NULL WHERE codprove = (SELECT idprovee FROM proveedo WHERE idprovee = :i)');
        $st->execute([':i' => $idprovee]);
        $st = Db::pdo()->prepare('DELETE FROM proveedo WHERE idprovee = :i LIMIT 1');
        $st->execute([':i' => $idprovee]);
    }

    public function findProveedores(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $st = Db::pdo()->prepare('
            SELECT idprovee, codprove, razon, cuit FROM proveedo
            WHERE razon LIKE :like OR codprove LIKE :like OR cuit LIKE :like
            ORDER BY razon ASC LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function productCount(int $idprovee): int
    {
        $st = Db::pdo()->prepare('
            SELECT COUNT(*) FROM producto pr
            INNER JOIN proveedo p ON p.idprovee = pr.codprove
            WHERE p.idprovee = :i
        ');
        $st->execute([':i' => $idprovee]);
        return (int)$st->fetchColumn();
    }
}
