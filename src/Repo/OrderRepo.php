<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class OrderRepo
{
    /** @return array<int, array<string,mixed>> */
    public function adminList(string $q = '', string $status = '', int $limit = 120): array
    {
        $limit = max(1, min(300, $limit));
        $q = trim($q);
        $status = trim($status);
        $params = [];
        $where = [];
        if ($q !== '') {
            $where[] = '(o.order_code LIKE :like OR o.email LIKE :like OR o.phone LIKE :like OR o.ship_name LIKE :like OR o.ship_city LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($status !== '') {
            $where[] = 'o.status = :status';
            $params[':status'] = $status;
        }

        $sql = '
            SELECT
              o.*, u.name AS user_name, u.email AS user_email,
              COUNT(oi.id) AS items_count,
              COALESCE(SUM(oi.qty), 0) AS units_count
            FROM orders o
            LEFT JOIN web_users u ON u.id = o.user_id
            LEFT JOIN order_items oi ON oi.order_id = o.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC, o.id DESC LIMIT ' . $limit;
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function itemsByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if (!$orderIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $st = Db::pdo()->prepare('SELECT * FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY order_id DESC, id ASC');
        $st->execute($orderIds);
        return $st->fetchAll();
    }

    public function create(array $order, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'INSERT INTO orders (order_code,user_id,customer_type,status,email,phone,ship_name,ship_address,ship_city,ship_postal_code,ship_province_codprov,ship_cod_lugar,ship_province_name,shipping_method,shipping_detail,shipping_cost_cents,subtotal_net_cents,discount_percent,discount_cents,iva_cents,total_cents,currency,created_at,updated_at) VALUES (:code,:uid,:ctype,:status,:email,:phone,:sname,:addr,:city,:cp,:prov,:lugar,:provn,:sm,:sd,:sc,:subnet,:dp,:dc,:iva,:tot,:cur,NOW(),NOW())'
            );
            try {
                $st->execute([
                    ':code' => $order['order_code'],
                    ':uid' => $order['user_id'],
                    ':ctype' => $order['customer_type'],
                    ':status' => $order['status'],
                    ':email' => $order['email'],
                    ':phone' => $order['phone'],
                    ':sname' => $order['ship_name'],
                    ':addr' => $order['ship_address'],
                    ':city' => $order['ship_city'],
                    ':cp' => $order['ship_postal_code'],
                    ':prov' => $order['ship_province_codprov'],
                    ':lugar' => $order['ship_cod_lugar'] ?? null,
                    ':provn' => $order['ship_province_name'],
                    ':sm' => $order['shipping_method'],
                    ':sd' => $order['shipping_detail'],
                    ':sc' => $order['shipping_cost_cents'],
                    ':subnet' => $order['subtotal_net_cents'],
                    ':dp' => $order['discount_percent'],
                    ':dc' => $order['discount_cents'],
                    ':iva' => $order['iva_cents'],
                    ':tot' => $order['total_cents'],
                    ':cur' => 'ARS',
                ]);
            } catch (\PDOException $e) {
                $st = $pdo->prepare(
                    'INSERT INTO orders (order_code,user_id,customer_type,status,email,phone,ship_name,ship_address,ship_city,ship_postal_code,ship_province_codprov,ship_province_name,shipping_method,shipping_detail,shipping_cost_cents,subtotal_net_cents,discount_percent,discount_cents,iva_cents,total_cents,currency,created_at,updated_at) VALUES (:code,:uid,:ctype,:status,:email,:phone,:sname,:addr,:city,:cp,:prov,:provn,:sm,:sd,:sc,:subnet,:dp,:dc,:iva,:tot,:cur,NOW(),NOW())'
                );
                $st->execute([
                    ':code' => $order['order_code'],
                    ':uid' => $order['user_id'],
                    ':ctype' => $order['customer_type'],
                    ':status' => $order['status'],
                    ':email' => $order['email'],
                    ':phone' => $order['phone'],
                    ':sname' => $order['ship_name'],
                    ':addr' => $order['ship_address'],
                    ':city' => $order['ship_city'],
                    ':cp' => $order['ship_postal_code'],
                    ':prov' => $order['ship_province_codprov'],
                    ':provn' => $order['ship_province_name'],
                    ':sm' => $order['shipping_method'],
                    ':sd' => $order['shipping_detail'],
                    ':sc' => $order['shipping_cost_cents'],
                    ':subnet' => $order['subtotal_net_cents'],
                    ':dp' => $order['discount_percent'],
                    ':dc' => $order['discount_cents'],
                    ':iva' => $order['iva_cents'],
                    ':tot' => $order['total_cents'],
                    ':cur' => 'ARS',
                ]);
            }
            $orderId = (int)$pdo->lastInsertId();

            $sti = $pdo->prepare(
                'INSERT INTO order_items (order_id,idprodu,idcodgusto,product_name,variant_name,qty,unit_net_cents,iva_rate,line_net_cents,line_iva_cents,line_total_cents) VALUES (:oid,:pid,:gid,:pn,:vn,:q,:un,:ir,:ln,:li,:lt)'
            );
            foreach ($items as $it) {
                $sti->execute([
                    ':oid' => $orderId,
                    ':pid' => $it['idprodu'],
                    ':gid' => $it['idcodgusto'],
                    ':pn' => $it['product_name'],
                    ':vn' => $it['variant_name'],
                    ':q' => $it['qty'],
                    ':un' => $it['unit_net_cents'],
                    ':ir' => $it['iva_rate'],
                    ':ln' => $it['line_net_cents'],
                    ':li' => $it['line_iva_cents'],
                    ':lt' => $it['line_total_cents'],
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    public function findByCode(string $code): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM orders WHERE order_code=:c LIMIT 1');
        $st->execute([':c' => $code]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM orders WHERE id=:i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $st = Db::pdo()->prepare('UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:i');
        $st->execute([':s' => $status, ':i' => $id]);
    }

    public function updateCorreoData(int $id, string $operation, string $tracking): void
    {
        $st = Db::pdo()->prepare('UPDATE orders SET correo_operation=:op, correo_tracking=:tr, updated_at=NOW() WHERE id=:i');
        $st->execute([':op' => $operation, ':tr' => $tracking, ':i' => $id]);
    }

    /** @return array<int, array<string,mixed>> */
    public function findAbandonedCarts(int $olderThanHours = 6, int $newerThanHours = 48): array
    {
        $st = Db::pdo()->prepare("
            SELECT o.*, u.name AS user_name
            FROM orders o
            LEFT JOIN web_users u ON u.id = o.user_id
            WHERE o.status = 'pending_payment'
              AND o.created_at < DATE_SUB(NOW(), INTERVAL :older HOUR)
              AND o.created_at > DATE_SUB(NOW(), INTERVAL :newer HOUR)
            ORDER BY o.created_at ASC
        ");
        $st->execute([':older' => $olderThanHours, ':newer' => $newerThanHours]);
        return $st->fetchAll();
    }

    /** Mark pending_payment older than 48h as archived. Returns count. */
    public function archiveAbandonedCarts(): int
    {
        $st = Db::pdo()->prepare("UPDATE orders SET status='archived', updated_at=NOW() WHERE status='pending_payment' AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        $st->execute();
        return $st->rowCount();
    }
}
