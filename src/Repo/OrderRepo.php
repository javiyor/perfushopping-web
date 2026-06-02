<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class OrderRepo
{
    public function create(array $order, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
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
}
