<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class GastoRepo
{
    private function hasIdcta1(): bool
    {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM gastos')->fetchAll();
            $fields = array_column($cols, 'Field');
            $has = in_array('idcta1', $fields, true);
        } catch (\Throwable $e) { $has = false; }
        return $has;
    }

    public function findAll(array $f = []): array
    {
        $params = [];
        $where = [];
        $q = trim((string)($f['q'] ?? ''));
        $hasIdcta1 = $this->hasIdcta1();
        if ($q !== '') {
            if ($hasIdcta1) {
                $where[] = '(g.descripcion LIKE :q OR c1.nomcta1 LIKE :q)';
            } else {
                $where[] = 'g.descripcion LIKE :q';
            }
            $params[':q'] = '%' . $q . '%';
        }
        $desde = trim((string)($f['desde'] ?? ''));
        if ($desde !== '') { $where[] = 'g.fecha >= :desde'; $params[':desde'] = $desde; }
        $hasta = trim((string)($f['hasta'] ?? ''));
        if ($hasta !== '') { $where[] = 'g.fecha <= :hasta'; $params[':hasta'] = $hasta; }

        if ($hasIdcta1) {
            $sql = 'SELECT g.*, c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo,
                           bc.banco AS banco_nombre, ch.numero_cheque, ch.banco_emisor,
                           au.nombre AS created_by_nombre
                    FROM gastos g
                    LEFT JOIN contable1 c1 ON c1.idcta1 = g.idcta1
                    LEFT JOIN contable c ON c.idcta = c1.idcta
                    LEFT JOIN banco_cuentas bc ON bc.id = g.banco_cuenta_id
                    LEFT JOIN cheques ch ON ch.id = g.cheque_id
                    LEFT JOIN admin_users au ON au.id = g.created_by';
        } else {
            $sql = 'SELECT g.*, NULL AS cuenta_nombre, NULL AS cuenta_grupo,
                           bc.banco AS banco_nombre, ch.numero_cheque, ch.banco_emisor,
                           au.nombre AS created_by_nombre
                    FROM gastos g
                    LEFT JOIN banco_cuentas bc ON bc.id = g.banco_cuenta_id
                    LEFT JOIN cheques ch ON ch.id = g.cheque_id
                    LEFT JOIN admin_users au ON au.id = g.created_by';
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY g.fecha DESC, g.id DESC LIMIT 500';
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('GastoRepo::findAll fallback: '.$e->getMessage());
            $sql2 = 'SELECT g.*, bc.banco AS banco_nombre, ch.numero_cheque, ch.banco_emisor, au.nombre AS created_by_nombre FROM gastos g LEFT JOIN banco_cuentas bc ON bc.id=g.banco_cuenta_id LEFT JOIN cheques ch ON ch.id=g.cheque_id LEFT JOIN admin_users au ON au.id=g.created_by ORDER BY g.fecha DESC, g.id DESC LIMIT 500';
            $st2 = Db::pdo()->prepare($sql2);
            $st2->execute([]);
            return $st2->fetchAll();
        }
    }

    public function findById(int $id): ?array
    {
        if ($this->hasIdcta1()) {
            $st = Db::pdo()->prepare('SELECT g.*, c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo FROM gastos g LEFT JOIN contable1 c1 ON c1.idcta1=g.idcta1 LEFT JOIN contable c ON c.idcta=c1.idcta WHERE g.id=:id LIMIT 1');
        } else {
            $st = Db::pdo()->prepare('SELECT g.*, NULL AS cuenta_nombre, NULL AS cuenta_grupo FROM gastos g WHERE g.id=:id LIMIT 1');
        }
        $st->execute([':id'=>$id]);
        return $st->fetch() ?: null;
    }

    public function create(array $data): int
    {
        if ($this->hasIdcta1()) {
            $st = Db::pdo()->prepare('INSERT INTO gastos (fecha, idcta1, descripcion, importe_cents, forma_pago, caja_destino, banco_cuenta_id, cheque_id, sucursal_id, punto_venta, created_by, created_at, updated_at) VALUES (:fecha, :idcta1, :desc, :imp, :fp, :caja, :banco, :cheque, :suc, :pv, :cb, NOW(), NOW())');
            $st->execute([
                ':fecha'=>$data['fecha'],
                ':idcta1'=>$data['idcta1'] ?: null,
                ':desc'=>trim((string)$data['descripcion']),
                ':imp'=>(int)($data['importe_cents'] ?? 0),
                ':fp'=>$data['forma_pago'],
                ':caja'=>$data['caja_destino'] ?? 'general',
                ':banco'=>$data['banco_cuenta_id'] ?: null,
                ':cheque'=>$data['cheque_id'] ?: null,
                ':suc'=>$data['sucursal_id'] ?: null,
                ':pv'=>$data['punto_venta'] ?: null,
                ':cb'=>$data['created_by'] ?: null,
            ]);
        } else {
            $st = Db::pdo()->prepare('INSERT INTO gastos (fecha, descripcion, importe_cents, forma_pago, caja_destino, banco_cuenta_id, cheque_id, sucursal_id, punto_venta, created_by, created_at, updated_at) VALUES (:fecha, :desc, :imp, :fp, :caja, :banco, :cheque, :suc, :pv, :cb, NOW(), NOW())');
            $st->execute([
                ':fecha'=>$data['fecha'],
                ':desc'=>trim((string)$data['descripcion']),
                ':imp'=>(int)($data['importe_cents'] ?? 0),
                ':fp'=>$data['forma_pago'],
                ':caja'=>$data['caja_destino'] ?? 'general',
                ':banco'=>$data['banco_cuenta_id'] ?: null,
                ':cheque'=>$data['cheque_id'] ?: null,
                ':suc'=>$data['sucursal_id'] ?: null,
                ':pv'=>$data['punto_venta'] ?: null,
                ':cb'=>$data['created_by'] ?: null,
            ]);
        }
        return (int)Db::pdo()->lastInsertId();
    }

    public function cuentas(): array
    {
        $st = Db::pdo()->query('SELECT c.idcta, c.nomcta, c1.idcta1, c1.nomcta1 FROM contable1 c1 INNER JOIN contable c ON c.idcta=c1.idcta ORDER BY c.nomcta ASC, c1.nomcta1 ASC');
        return $st->fetchAll();
    }
}
