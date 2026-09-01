<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class BancoMovimientoRepo
{
    public function create(int $bancoCuentaId, string $tipo, ?string $origen, ?int $origenId, string $concepto, int $montoCents, string $fecha, int $createdBy): int
    {
        $st = Db::pdo()->prepare('INSERT INTO banco_movimientos (banco_cuenta_id, tipo, origen, origen_id, concepto, monto_cents, fecha, created_by, created_at) VALUES (:banco, :tipo, :origen, :oid, :concepto, :monto, :fecha, :cb, NOW())');
        $st->execute([
            ':banco'=>$bancoCuentaId,
            ':tipo'=>$tipo,
            ':origen'=>$origen,
            ':oid'=>$origenId,
            ':concepto'=>$concepto,
            ':monto'=>$montoCents,
            ':fecha'=>$fecha,
            ':cb'=>$createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function findByCuenta(int $bancoCuentaId, int $limit = 100): array
    {
        $st = Db::pdo()->prepare('SELECT bm.*, au.nombre AS created_by_nombre FROM banco_movimientos bm LEFT JOIN admin_users au ON au.id=bm.created_by WHERE bm.banco_cuenta_id=:b ORDER BY bm.fecha DESC, bm.id DESC LIMIT '.max(1,min(500,$limit)));
        $st->execute([':b'=>$bancoCuentaId]);
        return $st->fetchAll();
    }

    public function saldo(int $bancoCuentaId): int
    {
        $st = Db::pdo()->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='credito' THEN monto_cents ELSE 0 END),0) - COALESCE(SUM(CASE WHEN tipo='debito' THEN monto_cents ELSE 0 END),0) FROM banco_movimientos WHERE banco_cuenta_id=:b");
        $st->execute([':b'=>$bancoCuentaId]);
        $mov = (int)$st->fetchColumn();
        $st2 = Db::pdo()->prepare('SELECT saldo_inicial_cents FROM banco_cuentas WHERE id=:b LIMIT 1');
        $st2->execute([':b'=>$bancoCuentaId]);
        $ini = (int)($st2->fetchColumn() ?: 0);
        return $ini + $mov;
    }

    public function allMovimientos(?int $bancoCuentaId = null, ?string $desde = null, ?string $hasta = null): array
    {
        $params = [];
        $where = [];
        if ($bancoCuentaId) { $where[]='bm.banco_cuenta_id=:b'; $params[':b']=$bancoCuentaId; }
        if ($desde) { $where[]='bm.fecha >= :desde'; $params[':desde']=$desde; }
        if ($hasta) { $where[]='bm.fecha <= :hasta'; $params[':hasta']=$hasta; }
        $sql = 'SELECT bm.*, bc.banco AS banco_nombre, au.nombre AS created_by_nombre FROM banco_movimientos bm LEFT JOIN banco_cuentas bc ON bc.id=bm.banco_cuenta_id LEFT JOIN admin_users au ON au.id=bm.created_by';
        if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
        $sql .= ' ORDER BY bm.fecha DESC, bm.id DESC LIMIT 500';
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
