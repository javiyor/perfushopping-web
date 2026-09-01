<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class GastoRepo
{
    private function gastosColumns(): array
    {
        static $cols = null;
        if ($cols !== null) return $cols;
        try {
            $rows = Db::pdo()->query('SHOW COLUMNS FROM gastos')->fetchAll();
            $cols = array_column($rows, 'Field');
        } catch (\Throwable $e) { $cols = []; }
        return $cols;
    }
    private function hasGastosColumn(string $col): bool
    {
        return in_array($col, $this->gastosColumns(), true);
    }
    private function descColumn(): string
    {
        foreach (['descripcion','concepto','detalle','observacion','descripcion_gasto','nombre'] as $c) {
            if ($this->hasGastosColumn($c)) return $c;
        }
        return 'descripcion';
    }
    private function importeColumn(): string
    {
        foreach (['importe_cents','monto_cents','importe','monto','total_cents'] as $c) {
            if ($this->hasGastosColumn($c)) return $c;
        }
        return 'importe_cents';
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
        $descCol = $this->descColumn();
        if ($q !== '') {
            if ($hasIdcta1) {
                $where[] = "(g.`$descCol` LIKE :q OR c1.nomcta1 LIKE :q)";
            } else {
                $where[] = "g.`$descCol` LIKE :q";
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
        $descCol = $this->descColumn();
        $importeCol = $this->importeColumn();
        $map = [
            'fecha' => 'fecha',
            'idcta1' => 'idcta1',
            'desc' => $descCol,
            'importe' => $importeCol,
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
            'desc' => trim((string)$data['descripcion']),
            'importe' => (int)($data['importe_cents'] ?? 0),
            'forma_pago' => $data['forma_pago'],
            'caja_destino' => $data['caja_destino'] ?? 'general',
            'banco_cuenta_id' => $data['banco_cuenta_id'] ?: null,
            'cheque_id' => $data['cheque_id'] ?: null,
            'sucursal_id' => $data['sucursal_id'] ?: null,
            'punto_venta' => $data['punto_venta'] ?: null,
            'created_by' => $data['created_by'] ?: null,
        ];
        foreach ($map as $key => $col) {
            if (!$this->hasGastosColumn($col)) {
                continue;
            }
            $cols[] = "`$col`";
            $placeholders[] = ':'.$key;
            $params[':'.$key] = $values[$key];
        }
        // Timestamps solo si existen
        if ($this->hasGastosColumn('created_at')) { $cols[] = 'created_at'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('created')) { $cols[] = 'created'; $placeholders[] = 'NOW()'; }
        if ($this->hasGastosColumn('updated_at')) { $cols[] = 'updated_at'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('updated')) { $cols[] = 'updated'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('fecha_alta')) { $cols[] = 'fecha_alta'; $placeholders[] = 'NOW()'; }
        $sql = 'INSERT INTO gastos ('.implode(', ', $cols).') VALUES ('.implode(', ', $placeholders).')';
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
        } catch (\Throwable $e) {
            // Fallback sin timestamps si falló por columna
            if (str_contains($e->getMessage(), 'created_at') || str_contains($e->getMessage(), 'updated_at')) {
                $cols2 = array_filter($cols, fn($c) => !in_array(trim($c, '`'), ['created_at','updated_at','created','updated','fecha_alta'], true));
                $ph2 = [];
                $params2 = [];
                foreach ($cols2 as $i => $c) {
                    $ph2[] = $placeholders[$i];
                    $k = array_keys($params)[$i] ?? null;
                    if ($k && isset($params[$k])) $params2[$k] = $params[$k];
                }
                // reconstruir sin timestamps
                $sql2 = 'INSERT INTO gastos ('.implode(', ', $cols2).') VALUES ('.implode(', ', $ph2).')';
                $st2 = Db::pdo()->prepare($sql2);
                $st2->execute($params2);
                return (int)Db::pdo()->lastInsertId();
            }
            throw $e;
        }
        return (int)Db::pdo()->lastInsertId();
    }

    public function cuentas(): array
    {
        $st = Db::pdo()->query('SELECT c.idcta, c.nomcta, c1.idcta1, c1.nomcta1 FROM contable1 c1 INNER JOIN contable c ON c.idcta=c1.idcta ORDER BY c.nomcta ASC, c1.nomcta1 ASC');
        return $st->fetchAll();
    }
}
