<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

/** Promotional prices offered only when an invoice is paid in cash or by transfer. */
final class PosProductPromoRepo
{
    private static bool $tablesReady = false;

    public function ensureTables(): void
    {
        if (self::$tablesReady) return;
        $pdo = Db::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS producto_promos_pos (
            idprodu INT UNSIGNED NOT NULL,
            idcodgusto INT UNSIGNED NOT NULL DEFAULT 0,
            precio_final_cents INT UNSIGNED NOT NULL,
            fecha_hasta DATE DEFAULT NULL,
            unidades_limite INT UNSIGNED DEFAULT NULL,
            unidades_vendidas INT UNSIGNED NOT NULL DEFAULT 0,
            offer_version CHAR(32) NOT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (idprodu, idcodgusto),
            KEY idx_producto_promos_pos_active_expiry (activo, fecha_hasta)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS producto_pos_promo_usos (
            factura_id INT UNSIGNED NOT NULL,
            idprodu INT UNSIGNED NOT NULL,
            idcodgusto INT UNSIGNED NOT NULL DEFAULT 0,
            offer_version CHAR(32) NOT NULL,
            unidades INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (factura_id, idprodu, idcodgusto),
            KEY idx_pos_promo_usos_producto_version (idprodu, offer_version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->migrarColumnaVariante($pdo, 'producto_promos_pos');
        $this->migrarColumnaVariante($pdo, 'producto_pos_promo_usos');
        self::$tablesReady = true;
    }

    private function migrarColumnaVariante(\PDO $pdo, string $table): void
    {
        $columns = array_column($pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(), 'Field');
        if (!in_array('idcodgusto', $columns, true)) {
            $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN idcodgusto INT UNSIGNED NOT NULL DEFAULT 0 AFTER idprodu');
        }
        $expectedPrimary = $table === 'producto_promos_pos'
            ? ['idprodu', 'idcodgusto']
            : ['factura_id', 'idprodu', 'idcodgusto'];
        $primaryRows = $pdo->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = \'PRIMARY\'')->fetchAll();
        usort($primaryRows, static fn(array $a, array $b): int => (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']);
        $primaryColumns = array_column($primaryRows, 'Column_name');
        if ($primaryColumns !== $expectedPrimary) {
            $pdo->exec('ALTER TABLE `' . $table . '` DROP PRIMARY KEY, ADD PRIMARY KEY (' . implode(', ', $expectedPrimary) . ')');
        }
    }

    public function getForProduct(int $idprodu): ?array
    {
        if ($idprodu <= 0) return null;
        $this->ensureTables();
        $st = Db::pdo()->prepare('SELECT * FROM producto_promos_pos WHERE idprodu = :id AND idcodgusto = 0 LIMIT 1');
        $st->execute([':id' => $idprodu]);
        return $st->fetch() ?: null;
    }

    public function saveOffer(int $idprodu, ?int $priceCents, ?string $expiresOn, ?int $unitsLimit): void
    {
        $this->ensureTables();
        if ($idprodu <= 0) throw new \InvalidArgumentException('Producto inválido para configurar la oferta.');
        $priceCents = max(0, (int)($priceCents ?? 0));
        if ($priceCents === 0) {
            Db::pdo()->prepare('UPDATE producto_promos_pos SET activo = 0, updated_at = NOW() WHERE idprodu = :id AND idcodgusto = 0 LIMIT 1')
                ->execute([':id' => $idprodu]);
            return;
        }
        if ($expiresOn === null && $unitsLimit === null) {
            throw new \InvalidArgumentException('La oferta debe tener fecha de vencimiento, límite de unidades o ambos.');
        }

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM producto_promos_pos WHERE idprodu = :id AND idcodgusto = 0 LIMIT 1 FOR UPDATE');
            $st->execute([':id' => $idprodu]);
            $old = $st->fetch() ?: null;
            $sameOffer = $old
                && (int)($old['activo'] ?? 0) === 1
                && (int)($old['precio_final_cents'] ?? 0) === $priceCents
                && (string)($old['fecha_hasta'] ?? '') === (string)($expiresOn ?? '')
                && (($old['unidades_limite'] ?? null) === null ? $unitsLimit === null : (int)$old['unidades_limite'] === $unitsLimit);
            $version = $sameOffer ? (string)$old['offer_version'] : bin2hex(random_bytes(16));
            $unitsSold = $sameOffer ? (int)($old['unidades_vendidas'] ?? 0) : 0;
            $upsert = $pdo->prepare('INSERT INTO producto_promos_pos
                (idprodu, idcodgusto, precio_final_cents, fecha_hasta, unidades_limite, unidades_vendidas, offer_version, activo, updated_at)
                VALUES (:id, 0, :price, :expires, :units, :sold, :version, 1, NOW())
                ON DUPLICATE KEY UPDATE precio_final_cents = VALUES(precio_final_cents), fecha_hasta = VALUES(fecha_hasta),
                    unidades_limite = VALUES(unidades_limite), unidades_vendidas = VALUES(unidades_vendidas),
                    offer_version = VALUES(offer_version), activo = 1, updated_at = NOW()');
            $upsert->execute([
                ':id' => $idprodu,
                ':price' => $priceCents,
                ':expires' => $expiresOn,
                ':units' => $unitsLimit,
                ':sold' => $unitsSold,
                ':version' => $version,
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<int,int> $productIds @return array<int,array<string,mixed>> keyed by product id */
    public function ofertasActivas(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) return [];
        try {
            $st = Db::pdo()->prepare('SELECT idprodu, precio_final_cents, fecha_hasta, unidades_limite,
                    unidades_vendidas, offer_version,
                    CASE WHEN unidades_limite IS NULL THEN NULL ELSE GREATEST(0, unidades_limite - unidades_vendidas) END AS unidades_disponibles
                FROM producto_promos_pos
                WHERE activo = 1
                  AND (fecha_hasta IS NULL OR fecha_hasta >= CURDATE())
                  AND (unidades_limite IS NULL OR unidades_vendidas < unidades_limite)
                  AND idcodgusto = 0
                  AND idprodu IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
            $st->execute($ids);
            $out = [];
            foreach ($st->fetchAll() as $row) $out[(int)$row['idprodu']] = $row;
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Must be called inside FacturaRepo::create transaction after creating the invoice. */
    public function reservarUnidades(int $facturaId, array $usos): void
    {
        $pdo = Db::pdo();
        $update = $pdo->prepare("UPDATE producto_promos_pos
            SET unidades_vendidas = unidades_vendidas + :add_units, updated_at = NOW()
            WHERE idprodu = :id AND idcodgusto = 0 AND activo = 1 AND offer_version = :version
              AND (fecha_hasta IS NULL OR fecha_hasta >= CURDATE())
              AND (unidades_limite IS NULL OR unidades_vendidas + :check_units <= unidades_limite)");
        $insert = $pdo->prepare('INSERT INTO producto_pos_promo_usos (factura_id, idprodu, idcodgusto, offer_version, unidades, created_at)
            VALUES (:factura, :id, 0, :version, :units, NOW())');
        foreach ($usos as $idprodu => $usage) {
            $idprodu = (int)$idprodu;
            $units = max(0, (int)($usage['unidades'] ?? 0));
            $version = trim((string)($usage['offer_version'] ?? ''));
            if ($idprodu <= 0 || $units <= 0 || $version === '') continue;
            $update->execute([':add_units' => $units, ':check_units' => $units, ':id' => $idprodu, ':version' => $version]);
            if ($update->rowCount() !== 1) {
                throw new \RuntimeException('La oferta de un producto venció o se agotó mientras se facturaba. Volvé a consultar el producto.');
            }
            $insert->execute([':factura' => $facturaId, ':id' => $idprodu, ':version' => $version, ':units' => $units]);
        }
    }

    public function tieneUsosFactura(int $facturaId): bool
    {
        if ($facturaId <= 0) return false;
        try {
            $st = Db::pdo()->prepare('SELECT 1 FROM producto_pos_promo_usos WHERE factura_id = :id LIMIT 1');
            $st->execute([':id' => $facturaId]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Libera el cupo vendido cuando una factura promocionada se anula o elimina. */
    public function liberarFactura(int $facturaId): void
    {
        if ($facturaId <= 0 || !$this->tieneUsosFactura($facturaId)) return;
        $pdo = Db::pdo();
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT idprodu, idcodgusto, offer_version, unidades FROM producto_pos_promo_usos WHERE factura_id = :id FOR UPDATE');
            $st->execute([':id' => $facturaId]);
            $usos = $st->fetchAll();
            $update = $pdo->prepare('UPDATE producto_promos_pos SET unidades_vendidas = GREATEST(0, unidades_vendidas - :units), updated_at = NOW() WHERE idprodu = :id AND idcodgusto = :gusto AND offer_version = :version');
            foreach ($usos as $uso) {
                $update->execute([':units' => (int)$uso['unidades'], ':id' => (int)$uso['idprodu'], ':gusto' => (int)$uso['idcodgusto'], ':version' => (string)$uso['offer_version']]);
            }
            $pdo->prepare('DELETE FROM producto_pos_promo_usos WHERE factura_id = :id')->execute([':id' => $facturaId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('PosProductPromoRepo::liberarFactura: ' . $e->getMessage());
        }
    }
}
