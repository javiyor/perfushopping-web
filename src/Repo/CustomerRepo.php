<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CustomerRepo
{
    /** @return array<int, array<string,mixed>> */
    public function search(string $q = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);

        $select = '
            SELECT u.id, u.email, u.name, u.phone, u.role, u.wholesale_status,
                   u.customer_category, u.disabled_at, u.created_at, u.last_login_at,
                   u.cliente_id,
                   COALESCE(o_sum.order_count, 0) AS order_count,
                   COALESCE(o_sum.total_spent, 0) AS total_spent_cents,
                   o_sum.last_order_at
        ';
        $from = '
            FROM web_users u
            LEFT JOIN (
                SELECT user_id,
                       COUNT(*) AS order_count,
                       MAX(created_at) AS last_order_at,
                       SUM(total_cents) AS total_spent
                FROM orders
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) o_sum ON o_sum.user_id = u.id
        ';
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(u.name LIKE :like OR u.email LIKE :like OR u.phone LIKE :like OR u.phone_key LIKE :pk)';
            $params[':like'] = '%' . $q . '%';
            $params[':pk'] = preg_replace('/[^0-9]/', '', $q) ?? '';
        }

        $sql = $select . $from;
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o_sum.last_order_at DESC, u.created_at DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT u.*,
                   COALESCE(o_sum.order_count, 0) AS order_count,
                   COALESCE(o_sum.total_spent, 0) AS total_spent_cents,
                   o_sum.last_order_at
            FROM web_users u
            LEFT JOIN (
                SELECT user_id,
                       COUNT(*) AS order_count,
                       MAX(created_at) AS last_order_at,
                       SUM(total_cents) AS total_spent
                FROM orders
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) o_sum ON o_sum.user_id = u.id
            WHERE u.id = :i
            LIMIT 1
        ');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function orders(int $userId): array
    {
        $st = Db::pdo()->prepare('
            SELECT o.*, COUNT(oi.id) AS items_count
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = :u
            GROUP BY o.id
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT 100
        ');
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function clienteErp(int $clienteId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE idclien = :i LIMIT 1');
        $st->execute([':i' => $clienteId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function orderItems(int $orderId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM order_items WHERE order_id = :o ORDER BY id ASC');
        $st->execute([':o' => $orderId]);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function notas(int $userId): array
    {
        $st = Db::pdo()->prepare('
            SELECT n.*, a.nombre AS admin_nombre
            FROM cliente_notas n
            LEFT JOIN admin_users a ON a.id = n.admin_user_id
            WHERE n.user_id = :u
            ORDER BY n.created_at DESC
            LIMIT 200
        ');
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }

    public function addNota(int $userId, int $adminUserId, string $texto): int
    {
        $st = Db::pdo()->prepare('INSERT INTO cliente_notas (user_id, admin_user_id, nota, created_at) VALUES (:u, :a, :n, NOW())');
        $st->execute([':u' => $userId, ':a' => $adminUserId, ':n' => $texto]);
        return (int)Db::pdo()->lastInsertId();
    }
}
