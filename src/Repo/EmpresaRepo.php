<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class EmpresaRepo
{
    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT e.*, t.tipiva
            FROM empre e
            LEFT JOIN tipoiva t ON t.codtipiva = e.codtip
            WHERE e.idempre = :id LIMIT 1
        ');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function getDefault(): ?array
    {
        return $this->findById(3);
    }

    public function update(int $id, array $data): void
    {
        $existing = $this->columns();
        $fields = [];
        $params = [':id' => $id];
        foreach (['nomemp', 'razon_emp', 'dire_emp', 'telefono', 'cuit', 'ing_brutos', 'mail', 'codtip', 'logo', 'web', 'benef1', 'benef2', 'benef3'] as $col) {
            if (!in_array($col, $existing, true)) {
                continue;
            }
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[$col] = $data[$col];
            }
        }
        if (empty($fields)) return;
        $sql = 'UPDATE empre SET ' . implode(', ', $fields) . ' WHERE idempre = :id LIMIT 1';
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
    }

    private static ?array $empreColumns = null;

    private function columns(): array
    {
        if (self::$empreColumns !== null) {
            return self::$empreColumns;
        }
        $st = Db::pdo()->query('SHOW COLUMNS FROM empre');
        self::$empreColumns = array_column($st->fetchAll(), 'Field');
        return self::$empreColumns;
    }

    public function tiposIva(): array
    {
        $st = Db::pdo()->query('SELECT codtipiva, tipiva FROM tipoiva ORDER BY codtipiva ASC');
        return $st->fetchAll();
    }
}
