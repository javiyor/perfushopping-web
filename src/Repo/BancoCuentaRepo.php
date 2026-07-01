<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class BancoCuentaRepo
{
    public function findAll(bool $soloActivas = true): array
    {
        $where = $soloActivas ? ' WHERE activo = 1' : '';
        $st = Db::pdo()->query('SELECT * FROM banco_cuentas' . $where . ' ORDER BY banco ASC');
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM banco_cuentas WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO banco_cuentas (banco, tipo_cuenta, numero_cuenta, cbu, titular, saldo_inicial_cents, activo, created_at, updated_at)
            VALUES (:banco, :tipo, :nro, :cbu, :titular, :saldo, :activo, NOW(), NOW())
        ');
        $st->execute([
            ':banco' => $data['banco'],
            ':tipo' => $data['tipo_cuenta'] ?? 'corriente',
            ':nro' => $data['numero_cuenta'] ?? null,
            ':cbu' => $data['cbu'] ?? null,
            ':titular' => $data['titular'] ?? null,
            ':saldo' => (int)($data['saldo_inicial_cents'] ?? 0),
            ':activo' => (int)($data['activo'] ?? 1),
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $st = Db::pdo()->prepare('
            UPDATE banco_cuentas SET
                banco = :banco, tipo_cuenta = :tipo, numero_cuenta = :nro,
                cbu = :cbu, titular = :titular, saldo_inicial_cents = :saldo,
                activo = :activo, updated_at = NOW()
            WHERE id = :i LIMIT 1
        ');
        $st->execute([
            ':banco' => $data['banco'],
            ':tipo' => $data['tipo_cuenta'] ?? 'corriente',
            ':nro' => $data['numero_cuenta'] ?? null,
            ':cbu' => $data['cbu'] ?? null,
            ':titular' => $data['titular'] ?? null,
            ':saldo' => (int)($data['saldo_inicial_cents'] ?? 0),
            ':activo' => (int)($data['activo'] ?? 1),
            ':i' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        Db::pdo()->prepare('DELETE FROM banco_cuentas WHERE id = :i LIMIT 1')->execute([':i' => $id]);
    }
}
