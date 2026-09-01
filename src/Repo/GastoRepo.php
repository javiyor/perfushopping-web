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
        $hasFecha = $this->hasGastosColumn('fecha');
        $hasCreatedBy = $this->hasGastosColumn('created_by');
        $hasIdcta1 = $this->hasGastosColumn('idcta1');
        $hasBanco = $this->hasGastosColumn('banco_cuenta_id');
        $hasCheque = $this->hasGastosColumn('cheque_id');
        $descCol = $this->descColumn();
        $hasDesc = $this->hasGastosColumn($descCol);

        $params = [];
        $where = [];
        $q = trim((string)($f['q'] ?? ''));
        if ($q !== '' && $hasDesc) {
            if ($hasIdcta1) {
                $where[] = "(g.`$descCol` LIKE :q OR c1.nomcta1 LIKE :q)";
            } else {
                $where[] = "g.`$descCol` LIKE :q";
            }
            $params[':q'] = '%' . $q . '%';
        }
        $desde = trim((string)($f['desde'] ?? ''));
        if ($desde !== '' && $hasFecha) { $where[] = 'g.fecha >= :desde'; $params[':desde'] = $desde; }
        $hasta = trim((string)($f['hasta'] ?? ''));
        if ($hasta !== '' && $hasFecha) { $where[] = 'g.fecha <= :hasta'; $params[':hasta'] = $hasta; }

        $selectCuenta = $hasIdcta1 ? 'c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo,' : 'NULL AS cuenta_nombre, NULL AS cuenta_grupo,';
        $joinCuenta = $hasIdcta1 ? ' LEFT JOIN contable1 c1 ON c1.idcta1 = g.idcta1 LEFT JOIN contable c ON c.idcta = c1.idcta' : '';
        $selectBanco = $hasBanco ? 'bc.banco AS banco_nombre,' : 'NULL AS banco_nombre,';
        $joinBanco = $hasBanco ? ' LEFT JOIN banco_cuentas bc ON bc.id = g.banco_cuenta_id' : '';
        $selectCheque = $hasCheque ? 'ch.numero_cheque, ch.banco_emisor,' : 'NULL AS numero_cheque, NULL AS banco_emisor,';
        $joinCheque = $hasCheque ? ' LEFT JOIN cheques ch ON ch.id = g.cheque_id' : '';
        $selectCreated = $hasCreatedBy ? 'au.nombre AS created_by_nombre' : 'NULL AS created_by_nombre';
        $joinCreated = $hasCreatedBy ? ' LEFT JOIN admin_users au ON au.id = g.created_by' : '';
        $orderBy = $hasFecha ? 'g.fecha DESC, g.id DESC' : 'g.id DESC';

        $sql = "SELECT g.*, $selectCuenta $selectBanco $selectCheque $selectCreated FROM gastos g$joinCuenta$joinBanco$joinCheque$joinCreated";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY $orderBy LIMIT 500";
        try {
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('GastoRepo::findAll fallback: '.$e->getMessage());
            try {
                $st2 = Db::pdo()->query('SELECT * FROM gastos ORDER BY id DESC LIMIT 500');
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
        $hasCreatedBy = $this->hasGastosColumn('created_by');
        $selectCuenta = $hasIdcta1 ? 'c1.nomcta1 AS cuenta_nombre, c.nomcta AS cuenta_grupo,' : 'NULL AS cuenta_nombre, NULL AS cuenta_grupo,';
        $joinCuenta = $hasIdcta1 ? ' LEFT JOIN contable1 c1 ON c1.idcta1=g.idcta1 LEFT JOIN contable c ON c.idcta=c1.idcta' : '';
        $selectBanco = $hasBanco ? 'bc.banco AS banco_nombre,' : 'NULL AS banco_nombre,';
        $joinBanco = $hasBanco ? ' LEFT JOIN banco_cuentas bc ON bc.id=g.banco_cuenta_id' : '';
        $selectCheque = $hasCheque ? 'ch.numero_cheque, ch.banco_emisor,' : 'NULL AS numero_cheque, NULL AS banco_emisor,';
        $joinCheque = $hasCheque ? ' LEFT JOIN cheques ch ON ch.id=g.cheque_id' : '';
        $selectCreated = $hasCreatedBy ? 'au.nombre AS created_by_nombre' : 'NULL AS created_by_nombre';
        $joinCreated = $hasCreatedBy ? ' LEFT JOIN admin_users au ON au.id=g.created_by' : '';
        $sql = "SELECT g.*, $selectCuenta $selectBanco $selectCheque $selectCreated FROM gastos g$joinCuenta$joinBanco$joinCheque$joinCreated WHERE g.id=:id LIMIT 1";
        $st = Db::pdo()->prepare($sql);
        $st->execute([':id'=>$id]);
        return $st->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $descCol = $this->descColumn();
        $importeCol = $this->importeColumn();
        $cols = [];
        $placeholders = [];
        $params = [];
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
            if (!$this->hasGastosColumn($col)) continue;
            $cols[] = "`$col`";
            $placeholders[] = ':'.$key;
            $params[':'.$key] = $values[$key];
        }
        if ($this->hasGastosColumn('created_at')) { $cols[] = 'created_at'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('created')) { $cols[] = 'created'; $placeholders[] = 'NOW()'; }
        if ($this->hasGastosColumn('updated_at')) { $cols[] = 'updated_at'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('updated')) { $cols[] = 'updated'; $placeholders[] = 'NOW()'; }
        elseif ($this->hasGastosColumn('fecha_alta')) { $cols[] = 'fecha_alta'; $placeholders[] = 'NOW()'; }
        if (!$cols) {
            throw new \RuntimeException('Tabla gastos sin columnas compatibles');
        }
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
