<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class GastoRepo
{
    private function hasGastosColumn(string $col): bool
    {
        static $cache = [];
        if (array_key_exists($col, $cache)) return $cache[$col];
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM gastos')->fetchAll();
            $fields = array_column($cols, 'Field');
            $cache[$col] = in_array($col, $fields, true);
        } catch (\Throwable $e) { $cache[$col] = false; }
        return $cache[$col];
    }
    private function hasIdcta1(): bool { return $this->hasGastosColumn('idcta1'); }

    public function findAll(array $f = []): array
    {
        $params = [];
        $where = [];
        $q = trim((string)($f['q'] ?? ''));
        $hasIdcta1 = $this->hasGastosColumn('idcta1');
        $hasBanco = $this->hasGastosColumn('banco_cuenta_id');
        $hasCheque = $this->hasGastosColumn('cheque_id');
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

        // SELECT y JOIN dinámicos según columnas existentes
        $selectCuenta = $hasIdcta1 ? 'c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo,' : 'NULL AS cuenta_nombre, NULL AS cuenta_grupo,';
        $joinCuenta = $hasIdcta1 ? ' LEFT JOIN contable1 c1 ON c1.idcta1 = g.idcta1 LEFT JOIN contable c ON c.idcta = c1.idcta' : '';
        $selectBanco = $hasBanco ? 'bc.banco AS banco_nombre,' : 'NULL AS banco_nombre,';
        $joinBanco = $hasBanco ? ' LEFT JOIN banco_cuentas bc ON bc.id = g.banco_cuenta_id' : '';
        $selectCheque = $hasCheque ? 'ch.numero_cheque, ch.banco_emisor,' : 'NULL AS numero_cheque, NULL AS banco_emisor,';

        $sql = "SELECT g.*, $selectCuenta $selectBanco $selectCheque au.nombre AS created_by_nombre FROM gastos g$joinCuenta$joinBanco" . ($hasCheque ? ' LEFT JOIN cheques ch ON ch.id = g.cheque_id' : '') . " LEFT JOIN admin_users au ON au.id = g.created_by";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY g.fecha DESC, g.id DESC LIMIT 500';
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('GastoRepo::findAll fallback: '.$e->getMessage());
            try {
                $st2 = Db::pdo()->prepare('SELECT g.*, au.nombre AS created_by_nombre FROM gastos g LEFT JOIN admin_users au ON au.id=g.created_by ORDER BY g.fecha DESC, g.id DESC LIMIT 500');
                $st2->execute([]);
                return $st2->fetchAll();
            } catch (\Throwable $e2) {
                error_log('GastoRepo::findAll fallback2: '.$e2->getMessage());
                return [];
            }
        }
    }

    public function findById(int $id): ?array
    {
        $hasIdcta1 = $this->hasGastosColumn('idcta1');
        $hasBanco = $this->hasGastosColumn('banco_cuenta_id');
        $hasCheque = $this->hasGastosColumn('cheque_id');
        $selectCuenta = $hasIdcta1 ? 'c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo,' : 'NULL AS cuenta_nombre, NULL AS cuenta_grupo,';
        $joinCuenta = $hasIdcta1 ? ' LEFT JOIN contable1 c1 ON c1.idcta1=g.idcta1 LEFT JOIN contable c ON c.idcta=c1.idcta' : '';
        $selectBanco = $hasBanco ? 'bc.banco AS banco_nombre,' : 'NULL AS banco_nombre,';
        $joinBanco = $hasBanco ? ' LEFT JOIN banco_cuentas bc ON bc.id=g.banco_cuenta_id' : '';
        $selectCheque = $hasCheque ? 'ch.numero_cheque, ch.banco_emisor,' : 'NULL AS numero_cheque, NULL AS banco_emisor,';
        $joinCheque = $hasCheque ? ' LEFT JOIN cheques ch ON ch.id=g.cheque_id' : '';
        $sql = "SELECT g.*, $selectCuenta $selectBanco $selectCheque au.nombre AS created_by_nombre FROM gastos g$joinCuenta$joinBanco$joinCheque LEFT JOIN admin_users au ON au.id=g.created_by WHERE g.id=:id LIMIT 1";
        $st = Db::pdo()->prepare($sql);
        $st->execute([':id'=>$id]);
        return $st->fetch() ?: null;
    }

    public function create(array $data): int
    {
        // Construir INSERT dinámico según columnas que existen
        $cols = [];
        $placeholders = [];
        $params = [];
        $map = [
            'fecha' => 'fecha',
            'idcta1' => 'idcta1',
            'descripcion' => 'descripcion',
            'importe_cents' => 'importe_cents',
            'forma_pago' => 'forma_pago',
            'caja_destino' => 'caja_destino',
            'banco_cuenta_id' => 'banco_cuenta_id',
            'cheque_id' => 'cheque_id',
            'sucursal_id' => 'sucursal_id',
            'punto_venta' => 'punto_venta',
            'created_by' => 'created_by',
        ];
        $values = [
            'fecha' => $data['fecha'],
            'idcta1' => $data['idcta1'] ?? null,
            'descripcion' => trim((string)$data['descripcion']),
            'importe_cents' => (int)($data['importe_cents'] ?? 0),
            'forma_pago' => $data['forma_pago'],
            'caja_destino' => $data['caja_destino'] ?? 'general',
            'banco_cuenta_id' => $data['banco_cuenta_id'] ?: null,
            'cheque_id' => $data['cheque_id'] ?: null,
            'sucursal_id' => $data['sucursal_id'] ?: null,
            'punto_venta' => $data['punto_venta'] ?: null,
            'created_by' => $data['created_by'] ?: null,
        ];
        foreach ($map as $key => $col) {
            if (!$this->hasGastosColumn($col) && $col !== 'fecha' && $col !== 'descripcion' && $col !== 'importe_cents') {
                continue;
            }
            $cols[] = $col;
            $placeholders[] = ':'.$key;
            $params[':'.$key] = $values[$key];
        }
        // Siempre añadir timestamps
        $cols[] = 'created_at'; $placeholders[] = 'NOW()';
        $cols[] = 'updated_at'; $placeholders[] = 'NOW()';
        $sql = 'INSERT INTO gastos ('.implode(', ', $cols).') VALUES ('.implode(', ', $placeholders).')';
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return (int)Db::pdo()->lastInsertId();
    }

    public function cuentas(): array
    {
        $st = Db::pdo()->query('SELECT c.idcta, c.nomcta, c1.idcta1, c1.nomcta1 FROM contable1 c1 INNER JOIN contable c ON c.idcta=c1.idcta ORDER BY c.nomcta ASC, c1.nomcta1 ASC');
        return $st->fetchAll();
    }
}
