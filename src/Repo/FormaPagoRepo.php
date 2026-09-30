<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class FormaPagoRepo
{
    public const TIPOS = [
        'efectivo' => 'Efectivo',
        'banco' => 'Banco / transferencia',
        'tarjeta' => 'Tarjeta',
        'cheque' => 'Cheque',
        'ctacte' => 'Cuenta corriente',
        'moneda' => 'Moneda extranjera',
    ];

    private static ?bool $tableReady = null;

    public static function ensureTable(): void
    {
        if (self::$tableReady !== null) {
            return;
        }
        self::$tableReady = true;
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS formas_pago (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    codigo VARCHAR(40) NOT NULL,
                    nombre VARCHAR(80) NOT NULL,
                    tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
                    moneda CHAR(3) DEFAULT NULL,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    orden INT NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_codigo (codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $coll = Db::pdo()->query("
                SELECT TABLE_COLLATION FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'formas_pago'
            ")->fetchColumn();
            if ($coll !== false && $coll !== null && (string)$coll !== 'utf8mb4_unicode_ci') {
                Db::pdo()->exec('ALTER TABLE formas_pago CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
            $count = (int)Db::pdo()->query('SELECT COUNT(*) FROM formas_pago')->fetchColumn();
            if ($count === 0) {
                $seed = [
                    ['efectivo', 'Efectivo', 'efectivo', null, 1],
                    ['transferencia', 'Transferencia bancaria', 'banco', null, 2],
                    ['tarjeta', 'Tarjetas', 'tarjeta', null, 3],
                    ['mercadopago', 'Mercado Pago', 'banco', null, 4],
                    ['cuenta_corriente', 'Cuenta corriente', 'ctacte', null, 5],
                    ['cheque', 'Cheque de terceros', 'cheque', null, 6],
                    ['billetera', 'Billetera / QR', 'banco', null, 7],
                    ['dolares', 'Dólares', 'moneda', 'USD', 8],
                ];
                $ins = Db::pdo()->prepare("
                    INSERT INTO formas_pago (codigo, nombre, tipo, moneda, activo, orden, created_at, updated_at)
                    VALUES (:cod, :nom, :tipo, :mon, 1, :ord, NOW(), NOW())
                ");
                foreach ($seed as [$cod, $nom, $tipo, $mon, $ord]) {
                    $ins->execute([':cod' => $cod, ':nom' => $nom, ':tipo' => $tipo, ':mon' => $mon, ':ord' => $ord]);
                }
            }
        } catch (\Throwable $e) {
            error_log('FormaPagoRepo::ensureTable error: ' . $e->getMessage());
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findActivas(): array
    {
        self::ensureTable();
        try {
            return Db::pdo()->query("
                SELECT * FROM formas_pago WHERE activo = 1 ORDER BY orden ASC, nombre ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findAll(): array
    {
        self::ensureTable();
        return Db::pdo()->query('SELECT * FROM formas_pago ORDER BY orden ASC, nombre ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        self::ensureTable();
        $st = Db::pdo()->prepare('SELECT * FROM formas_pago WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function findActivoByCodigo(string $codigo): ?array
    {
        self::ensureTable();
        $st = Db::pdo()->prepare('SELECT * FROM formas_pago WHERE codigo = :c AND activo = 1 LIMIT 1');
        $st->execute([':c' => $codigo]);
        return $st->fetch() ?: null;
    }

    /** codigo => nombre para etiquetas. */
    public function labels(): array
    {
        $out = [];
        foreach ($this->findAll() as $f) {
            $out[(string)$f['codigo']] = (string)$f['nombre'];
        }
        return $out;
    }

    public function save(?int $id, array $data): int
    {
        self::ensureTable();
        $codigo = strtolower(trim((string)($data['codigo'] ?? '')));
        $codigo = (string)preg_replace('/[^a-z0-9_]/', '', $codigo);
        if ($codigo === '') {
            throw new \RuntimeException('El código es obligatorio (solo minúsculas, números y guion bajo).');
        }
        $tipo = (string)($data['tipo'] ?? 'otro');
        if (!isset(self::TIPOS[$tipo])) {
            throw new \RuntimeException('Tipo de comportamiento inválido.');
        }
        $nombre = trim((string)($data['nombre'] ?? ''));
        if ($nombre === '') {
            throw new \RuntimeException('El nombre es obligatorio.');
        }
        $moneda = $tipo === 'moneda' ? strtoupper(trim((string)($data['moneda'] ?? ''))) : null;
        if ($tipo === 'moneda' && !preg_match('/^[A-Z]{3}$/', (string)$moneda)) {
            throw new \RuntimeException('La moneda debe ser un código de 3 letras (ej: USD).');
        }
        $activo = !empty($data['activo']) ? 1 : 0;
        $orden = (int)($data['orden'] ?? 0);

        $dup = Db::pdo()->prepare('SELECT id FROM formas_pago WHERE codigo = :c' . ($id ? ' AND id <> :id' : '') . ' LIMIT 1');
        $dupParams = [':c' => $codigo];
        if ($id) {
            $dupParams[':id'] = $id;
        }
        $dup->execute($dupParams);
        if ($dup->fetch()) {
            throw new \RuntimeException('Ya existe una forma de pago con ese código.');
        }

        if ($id) {
            Db::pdo()->prepare('
                UPDATE formas_pago SET codigo = :cod, nombre = :nom, tipo = :tipo, moneda = :mon,
                    activo = :act, orden = :ord, updated_at = NOW() WHERE id = :id LIMIT 1
            ')->execute([
                ':cod' => $codigo, ':nom' => $nombre, ':tipo' => $tipo, ':mon' => $moneda,
                ':act' => $activo, ':ord' => $orden, ':id' => $id,
            ]);
            return $id;
        }

        Db::pdo()->prepare('
            INSERT INTO formas_pago (codigo, nombre, tipo, moneda, activo, orden, created_at, updated_at)
            VALUES (:cod, :nom, :tipo, :mon, :act, :ord, NOW(), NOW())
        ')->execute([
            ':cod' => $codigo, ':nom' => $nombre, ':tipo' => $tipo, ':mon' => $moneda,
            ':act' => $activo, ':ord' => $orden,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function toggle(int $id): void
    {
        self::ensureTable();
        Db::pdo()->prepare('UPDATE formas_pago SET activo = 1 - activo, updated_at = NOW() WHERE id = :id LIMIT 1')
            ->execute([':id' => $id]);
    }

    public function enUso(int $id): bool
    {
        self::ensureTable();
        $st = Db::pdo()->prepare('SELECT codigo FROM formas_pago WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $codigo = $st->fetchColumn();
        if ($codigo === false || $codigo === null) {
            return false;
        }
        foreach (['factura_pagos', 'recibo_pagos'] as $tabla) {
            try {
                $n = Db::pdo()->prepare("SELECT COUNT(*) FROM {$tabla} WHERE forma_pago = :c");
                $n->execute([':c' => $codigo]);
                if ((int)$n->fetchColumn() > 0) {
                    return true;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return false;
    }

    public function delete(int $id): void
    {
        self::ensureTable();
        if ($this->enUso($id)) {
            throw new \RuntimeException('No se puede eliminar: ya tiene comprobantes asociados. Desactivala en su lugar.');
        }
        Db::pdo()->prepare('DELETE FROM formas_pago WHERE id = :id LIMIT 1')->execute([':id' => $id]);
    }

    /**
     * Tipo de comportamiento para un código de forma de pago.
     * Usa la tabla y cae a los códigos históricos si no está configurado.
     */
    public static function tipoDe(string $codigo): string
    {
        $f = (new self())->findActivoByCodigo($codigo);
        if ($f && isset(self::TIPOS[(string)$f['tipo']])) {
            return (string)$f['tipo'];
        }
        return match ($codigo) {
            'efectivo' => 'efectivo',
            'transferencia', 'mercadopago', 'debito', 'credito' => 'banco',
            'tarjeta', 'tarjeta_credito', 'tarjeta_debito', 'tarjetas' => 'tarjeta',
            'cheque' => 'cheque',
            'cuenta_corriente' => 'ctacte',
            default => 'otro',
        };
    }
}
