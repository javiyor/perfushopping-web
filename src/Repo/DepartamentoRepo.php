<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class DepartamentoRepo
{
    public function findAll(): array
    {
        $st = Db::pdo()->query('
            SELECT d.*, COUNT(p.idprodu) AS product_count
            FROM departa d
            LEFT JOIN producto p ON p.codepar = d.codepar
            GROUP BY d.codepar
            ORDER BY d.nomdepar ASC
        ');
        return $st->fetchAll();
    }

    public function findById(int $codepar): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM departa WHERE codepar = :i LIMIT 1');
        $st->execute([':i' => $codepar]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function create(string $nomdepar): int
    {
        $max = Db::pdo()->query('SELECT COALESCE(MAX(codepar), 0) + 1 FROM departa')->fetchColumn();
        $st = Db::pdo()->prepare('INSERT INTO departa (codepar, nomdepar) VALUES (:id, :n)');
        $st->execute([':id' => (int)$max, ':n' => $nomdepar]);
        return (int)$max;
    }

    public function update(int $codepar, string $nomdepar): void
    {
        $st = Db::pdo()->prepare('UPDATE departa SET nomdepar = :n WHERE codepar = :i LIMIT 1');
        $st->execute([':n' => $nomdepar, ':i' => $codepar]);
    }

    public function delete(int $codepar): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET codepar = NULL WHERE codepar = :i');
        $st->execute([':i' => $codepar]);
        $st = Db::pdo()->prepare('DELETE FROM departa WHERE codepar = :i LIMIT 1');
        $st->execute([':i' => $codepar]);
    }

    public function productCount(int $codepar): int
    {
        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM producto WHERE codepar = :i');
        $st->execute([':i' => $codepar]);
        return (int)$st->fetchColumn();
    }
}
