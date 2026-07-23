<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PromoTarjetaRepo
{
    public function findAll(): array
    {
        $st = Db::pdo()->query('SELECT * FROM promo_tarjetas ORDER BY updated_at DESC');
        return $st->fetchAll();
    }

    public function findActivos(): array
    {
        $st = Db::pdo()->prepare("
            SELECT * FROM promo_tarjetas
            WHERE publicado = 1
              AND (fecha_desde IS NULL OR fecha_desde <= CURDATE())
              AND (fecha_hasta IS NULL OR fecha_hasta >= CURDATE())
            ORDER BY fecha_desde DESC, banco ASC
        ");
        $st->execute();
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM promo_tarjetas WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO promo_tarjetas (tipo_tarjeta, banco, descripcion, detalle_promo, fecha_desde, fecha_hasta, publicado, created_by, created_at, updated_at)
            VALUES (:tt, :ban, :des, :det, :fd, :fh, :pub, :cb, NOW(), NOW())
        ');
        $st->execute([
            ':tt' => $data['tipo_tarjeta'],
            ':ban' => $data['banco'],
            ':des' => $data['descripcion'] ?? null,
            ':det' => $data['detalle_promo'] ?? null,
            ':fd' => $data['fecha_desde'] ?? null,
            ':fh' => $data['fecha_hasta'] ?? null,
            ':pub' => !empty($data['publicado']) ? 1 : 0,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $st = Db::pdo()->prepare('
            UPDATE promo_tarjetas SET
                tipo_tarjeta = :tt,
                banco = :ban,
                descripcion = :des,
                detalle_promo = :det,
                fecha_desde = :fd,
                fecha_hasta = :fh,
                publicado = :pub,
                updated_at = NOW()
            WHERE id = :id LIMIT 1
        ');
        $st->execute([
            ':tt' => $data['tipo_tarjeta'],
            ':ban' => $data['banco'],
            ':des' => $data['descripcion'] ?? null,
            ':det' => $data['detalle_promo'] ?? null,
            ':fd' => $data['fecha_desde'] ?? null,
            ':fh' => $data['fecha_hasta'] ?? null,
            ':pub' => !empty($data['publicado']) ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        Db::pdo()->prepare('DELETE FROM promo_tarjetas WHERE id = :id LIMIT 1')
            ->execute([':id' => $id]);
    }
}