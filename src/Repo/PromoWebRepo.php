<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

/** Productos en promoción para la web (precio neto promocionado por producto). */
final class PromoWebRepo
{
    private function ensure(): void
    {
        try {
            Db::pdo()->exec("CREATE TABLE IF NOT EXISTS promo_web (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                idprodu INT UNSIGNED NOT NULL,
                promo_precio DECIMAL(12,2) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                orden INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_promo_web_idprodu (idprodu),
                KEY idx_promo_web_activo_orden (activo, orden, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
        }
    }

    /** @return array<int, array<string,mixed>> */
    public function listAdmin(): array
    {
        $this->ensure();
        try {
            $st = Db::pdo()->query("
                SELECT w.*, p.produ, p.precio, p.precio1, p.imagen, p.enweb, i.tiva
                FROM promo_web w
                INNER JOIN producto p ON p.idprodu = w.idprodu
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                ORDER BY w.orden ASC, w.id ASC
            ");
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function addOrUpdate(int $idprodu, float $promoNet): bool
    {
        $this->ensure();
        if ($idprodu <= 0 || $promoNet <= 0) {
            return false;
        }
        try {
            $pdo = Db::pdo();
            $chk = $pdo->prepare("SELECT idprodu FROM producto WHERE idprodu=:id LIMIT 1");
            $chk->execute([':id' => $idprodu]);
            if (!$chk->fetch()) {
                return false;
            }
            $max = (int)$pdo->query("SELECT COALESCE(MAX(orden),0)+1 FROM promo_web")->fetchColumn();
            $st = $pdo->prepare("INSERT INTO promo_web (idprodu, promo_precio, activo, orden)
                VALUES (:id, :p, 1, :o)
                ON DUPLICATE KEY UPDATE promo_precio=VALUES(promo_precio), activo=1");
            $st->execute([':id' => $idprodu, ':p' => round($promoNet, 2), ':o' => max(1, $max)]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function remove(int $idprodu): void
    {
        $this->ensure();
        try {
            $st = Db::pdo()->prepare("DELETE FROM promo_web WHERE idprodu=:id LIMIT 1");
            $st->execute([':id' => $idprodu]);
        } catch (\Throwable $e) {
        }
    }

    public function toggle(int $idprodu): void
    {
        $this->ensure();
        try {
            $st = Db::pdo()->prepare("UPDATE promo_web SET activo=1-activo WHERE idprodu=:id LIMIT 1");
            $st->execute([':id' => $idprodu]);
        } catch (\Throwable $e) {
        }
    }

    /** @param array<int,int> $map idprodu => orden */
    public function saveOrder(array $map): void
    {
        $this->ensure();
        try {
            $pdo = Db::pdo();
            $st = $pdo->prepare("UPDATE promo_web SET orden=:o WHERE idprodu=:id LIMIT 1");
            foreach ($map as $idprodu => $orden) {
                $idprodu = (int)$idprodu;
                if ($idprodu <= 0) {
                    continue;
                }
                $st->execute([':o' => max(0, (int)$orden), ':id' => $idprodu]);
            }
        } catch (\Throwable $e) {
        }
    }

    /** Promo activa de un producto o null. */
    public function findActiveByProduct(int $idprodu): ?array
    {
        $this->ensure();
        if ($idprodu <= 0) {
            return null;
        }
        try {
            $st = Db::pdo()->prepare("SELECT w.*, p.precio, p.precio1, i.tiva
                FROM promo_web w
                INNER JOIN producto p ON p.idprodu = w.idprodu AND p.enweb = 1
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                WHERE w.idprodu = :id AND w.activo = 1 LIMIT 1");
            $st->execute([':id' => $idprodu]);
            $row = $st->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Promos activas para la portada (sólo con promo menor al precio minorista neto). */
    public function listActive(): array
    {
        $this->ensure();
        try {
            $st = Db::pdo()->query("
                SELECT w.idprodu, w.promo_precio, w.orden,
                       p.produ, p.precio, p.precio1, p.imagen, i.tiva,
                       (SELECT g2.idcodgusto FROM gustos g2
                         LEFT JOIN (
                             SELECT st.idcodgusto AS gid, SUM(st.stock) AS ssum
                             FROM stock st
                             INNER JOIN deposito d ON d.iddepo = st.iddepo AND d.marca = 2
                             GROUP BY st.idcodgusto
                         ) ss ON ss.gid = g2.idcodgusto
                         WHERE g2.idprodu = p.idprodu AND g2.discont = 0
                         ORDER BY (COALESCE(ss.ssum, 0) > 0) DESC, g2.idcodgusto ASC
                         LIMIT 1) AS first_variant_id
                FROM promo_web w
                INNER JOIN producto p ON p.idprodu = w.idprodu AND p.enweb = 1
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                WHERE w.activo = 1 AND w.promo_precio > 0 AND w.promo_precio < p.precio
                ORDER BY w.orden ASC, w.id ASC
            ");
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
