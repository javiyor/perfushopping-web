<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ArcaRepo
{
    // ── Config ──

    public function getConfig(string $key): string
    {
        $st = Db::pdo()->prepare('SELECT cfg_value FROM arca_config WHERE cfg_key = :k LIMIT 1');
        $st->execute([':k' => $key]);
        $r = $st->fetchColumn();
        return $r !== false ? (string)$r : '';
    }

    public function setConfig(string $key, string $value): void
    {
        $st = Db::pdo()->prepare('
            INSERT INTO arca_config (cfg_key, cfg_value, created_at, updated_at)
            VALUES (:k, :v, NOW(), NOW())
            ON DUPLICATE KEY UPDATE cfg_value = :v2, updated_at = NOW()
        ');
        $st->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
    }

    public function getAllConfig(): array
    {
        $st = Db::pdo()->query('SELECT cfg_key, cfg_value FROM arca_config');
        $result = [];
        foreach ($st->fetchAll() as $row) {
            $result[$row['cfg_key']] = $row['cfg_value'];
        }
        return $result;
    }

    // ── TA ──

    public function getTicketAccesoValido(string $service = 'wsfe'): ?array
    {
        $this->ensureServiceColumn();
        if ($this->hasServiceColumn) {
            $st = Db::pdo()->prepare('
                SELECT * FROM arca_tickets_acceso
                WHERE expiration > NOW() AND service = :s
                ORDER BY id DESC LIMIT 1
            ');
            $st->execute([':s' => $service]);
        } else {
            $st = Db::pdo()->query('
                SELECT * FROM arca_tickets_acceso
                WHERE expiration > NOW()
                ORDER BY id DESC LIMIT 1
            ');
        }
        return $st->fetch() ?: null;
    }

    public function guardarTicketAcceso(string $token, string $sign, string $expiration, string $service = 'wsfe'): int
    {
        $this->ensureServiceColumn();
        if ($this->hasServiceColumn) {
            $st = Db::pdo()->prepare('
                INSERT INTO arca_tickets_acceso (token, sign, expiration, service, created_at)
                VALUES (:t, :s, :e, :sv, NOW())
            ');
            $st->execute([':t' => $token, ':s' => $sign, ':e' => $expiration, ':sv' => $service]);
        } else {
            $st = Db::pdo()->prepare('
                INSERT INTO arca_tickets_acceso (token, sign, expiration, created_at)
                VALUES (:t, :s, :e, NOW())
            ');
            $st->execute([':t' => $token, ':s' => $sign, ':e' => $expiration]);
        }
        return (int)Db::pdo()->lastInsertId();
    }

    private bool $hasServiceColumn = false;

    private function ensureServiceColumn(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            Db::pdo()->exec("ALTER TABLE arca_tickets_acceso ADD COLUMN service VARCHAR(32) NOT NULL DEFAULT 'wsfe' AFTER expiration");
            $this->hasServiceColumn = true;
        } catch (\Throwable $e) {
            // Check if column already exists
            try {
                $st = Db::pdo()->query("SHOW COLUMNS FROM arca_tickets_acceso LIKE 'service'");
                $this->hasServiceColumn = (bool)$st->fetch();
            } catch (\Throwable $e2) {
                $this->hasServiceColumn = false;
            }
        }
    }

    // ── Comprobantes ──

    public function guardarComprobante(int $facturaId, array $resultado): int
    {
        $pdo = Db::pdo();

        $st = $pdo->prepare('
            INSERT INTO arca_comprobantes (factura_id, cae, cae_vto, resultado, codigo_emision, observaciones, request_xml, response_xml, created_at)
            VALUES (:fi, :cae, :vto, :res, :cod, :obs, :req, :rsp, NOW())
            ON DUPLICATE KEY UPDATE
                cae = VALUES(cae), cae_vto = VALUES(cae_vto), resultado = VALUES(resultado),
                codigo_emision = VALUES(codigo_emision), observaciones = VALUES(observaciones),
                request_xml = VALUES(request_xml), response_xml = VALUES(response_xml)
        ');
        $st->execute([
            ':fi' => $facturaId,
            ':cae' => $resultado['cae'] ?? null,
            ':vto' => $resultado['cae_vto'] ?? null,
            ':res' => $resultado['resultado'] ?? null,
            ':cod' => isset($resultado['codigo_emision']) ? (int)$resultado['codigo_emision'] : null,
            ':obs' => $resultado['observaciones'] ?? null,
            ':req' => $resultado['request_xml'] ?? null,
            ':rsp' => $resultado['response_xml'] ?? null,
        ]);

        $id = (int)$pdo->lastInsertId();

        // Update factura with CAE info
        if (!empty($resultado['cae'])) {
            $pdo->prepare('UPDATE facturas SET cae = :cae, cae_vto = :vto, resultado_arca = :res, arca_obs = :obs, updated_at = NOW() WHERE id = :fi LIMIT 1')
                ->execute([
                    ':cae' => $resultado['cae'],
                    ':vto' => $resultado['cae_vto'] ?? null,
                    ':res' => $resultado['resultado'] ?? 'A',
                    ':obs' => $resultado['observaciones'] ?? null,
                    ':fi' => $facturaId,
                ]);
        } else {
            $pdo->prepare('UPDATE facturas SET resultado_arca = :res, arca_obs = :obs, updated_at = NOW() WHERE id = :fi LIMIT 1')
                ->execute([
                    ':res' => $resultado['resultado'] ?? 'R',
                    ':obs' => $resultado['observaciones'] ?? null,
                    ':fi' => $facturaId,
                ]);
        }

        return $id;
    }

    public function getComprobante(int $facturaId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM arca_comprobantes WHERE factura_id = :fi LIMIT 1');
        $st->execute([':fi' => $facturaId]);
        return $st->fetch() ?: null;
    }

    public function listarComprobantes(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $st = Db::pdo()->query("
            SELECT ac.*, f.codigo AS factura_codigo, f.cliente_nombre, f.total_cents, f.fecha
            FROM arca_comprobantes ac
            INNER JOIN facturas f ON f.id = ac.factura_id
            ORDER BY ac.created_at DESC
            LIMIT {$limit}
        ");
        return $st->fetchAll();
    }

    // ── Helper ──

    public function isHabilitado(): bool
    {
        return $this->getConfig('habilitado') === '1';
    }

    public function esHomologacion(): bool
    {
        return $this->getConfig('ambiente') === 'homologacion';
    }
}
