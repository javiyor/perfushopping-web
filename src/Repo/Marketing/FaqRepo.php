<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class FaqRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_faqs' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order ASC, id ASC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_faqs WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findByProductId(int $productId): array
    {
        $st = Db::pdo()->prepare('
            SELECT f.* FROM cms_faqs f
            INNER JOIN cms_product_faq pf ON pf.faq_id = f.id
            WHERE pf.product_id = :p AND f.active = 1
            ORDER BY pf.sort_order ASC, f.id ASC
        ');
        $st->execute([':p' => $productId]);
        return $st->fetchAll() ?: [];
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $fields = [
            ':q' => trim((string)($data['question'] ?? '')),
            ':a' => trim((string)($data['answer'] ?? '')),
            ':so' => (int)($data['sort_order'] ?? 0),
            ':ac' => (int)($data['active'] ?? 1),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_faqs SET question=:q, answer=:a, sort_order=:so, active=:ac WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_faqs (question, answer, sort_order, active) VALUES (:q, :a, :so, :ac)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_faqs WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function search(string $q, int $limit = 20): array
    {
        $st = Db::pdo()->prepare('SELECT id, question FROM cms_faqs WHERE question LIKE :q AND active = 1 ORDER BY question ASC LIMIT ' . max(1, min(100, $limit)));
        $st->execute([':q' => '%' . $q . '%']);
        return $st->fetchAll() ?: [];
    }
}
