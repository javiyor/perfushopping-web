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

    // ── Banners de texto (sin producto, opcionalmente enlazados a una marca) ──

    private function ensureBanners(): void
    {
        $this->ensure();
        try {
            Db::pdo()->exec("CREATE TABLE IF NOT EXISTS promo_banners (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(120) NOT NULL,
                texto VARCHAR(255) DEFAULT NULL,
                codsub INT UNSIGNED DEFAULT NULL,
                link VARCHAR(255) DEFAULT NULL,
                color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF',
                bg VARCHAR(7) DEFAULT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                orden INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_promo_banners_activo_orden (activo, orden, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
        }
    }

    /** @return array<int, array<string,mixed>> */
    public function listBannersAdmin(): array
    {
        $this->ensureBanners();
        try {
            $st = Db::pdo()->query("
                SELECT b.*, s.nomsub
                FROM promo_banners b
                LEFT JOIN subrubro s ON s.codsub = b.codsub
                ORDER BY b.orden ASC, b.id ASC
            ");
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Banners activos para la portada, con nombre de marca y link resuelto. */
    public function listActiveBanners(): array
    {
        $this->ensureBanners();
        try {
            $st = Db::pdo()->query("
                SELECT b.*, s.nomsub
                FROM promo_banners b
                LEFT JOIN subrubro s ON s.codsub = b.codsub
                WHERE b.activo = 1
                ORDER BY b.orden ASC, b.id ASC
            ");
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $r['href'] = $this->bannerHref($r);
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @param array<string,mixed> $b */
    public function bannerHref(array $b): string
    {
        $codsub = (int)($b['codsub'] ?? 0);
        if ($codsub > 0) {
            return '/?codsub=' . $codsub;
        }
        $link = trim((string)($b['link'] ?? ''));
        if ($link === '') {
            return '';
        }
        if (str_starts_with($link, '/')) {
            return $link;
        }
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }
        return '';
    }

    /**
     * Alta o edición de banner. $data: titulo, texto, codsub, link, color, bg.
     * @param array<string,mixed> $data
     */
    public function saveBanner(array $data): bool
    {
        $this->ensureBanners();
        $id = (int)($data['id'] ?? 0);
        $titulo = mb_substr(trim((string)($data['titulo'] ?? '')), 0, 120);
        if ($titulo === '') {
            return false;
        }
        $texto = mb_substr(trim((string)($data['texto'] ?? '')), 0, 255);
        $codsub = (int)($data['codsub'] ?? 0);
        if ($codsub > 0) {
            try {
                $chk = Db::pdo()->prepare("SELECT codsub FROM subrubro WHERE codsub=:c LIMIT 1");
                $chk->execute([':c' => $codsub]);
                if (!$chk->fetch()) {
                    $codsub = 0;
                }
            } catch (\Throwable $e) {
                $codsub = 0;
            }
        }
        $link = trim((string)($data['link'] ?? ''));
        if ($link !== '' && !str_starts_with($link, '/') && !preg_match('#^https?://#i', $link)) {
            $link = '';
        }
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($data['color'] ?? '')) ? (string)$data['color'] : '#FFFFFF';
        $bgRaw = trim((string)($data['bg'] ?? ''));
        $bg = preg_match('/^#[0-9a-fA-F]{6}$/', $bgRaw) ? $bgRaw : null;
        try {
            $pdo = Db::pdo();
            if ($id > 0) {
                $st = $pdo->prepare("UPDATE promo_banners
                    SET titulo=:t, texto=:x, codsub=:c, link=:l, color=:col, bg=:bg
                    WHERE id=:id LIMIT 1");
                $st->execute([
                    ':t' => $titulo, ':x' => $texto !== '' ? $texto : null,
                    ':c' => $codsub > 0 ? $codsub : null, ':l' => $link !== '' ? $link : null,
                    ':col' => $color, ':bg' => $bg, ':id' => $id,
                ]);
                return $st->rowCount() > 0;
            }
            $max = (int)$pdo->query("SELECT COALESCE(MAX(orden),0)+1 FROM promo_banners")->fetchColumn();
            $st = $pdo->prepare("INSERT INTO promo_banners (titulo, texto, codsub, link, color, bg, activo, orden)
                VALUES (:t, :x, :c, :l, :col, :bg, 1, :o)");
            $st->execute([
                ':t' => $titulo, ':x' => $texto !== '' ? $texto : null,
                ':c' => $codsub > 0 ? $codsub : null, ':l' => $link !== '' ? $link : null,
                ':col' => $color, ':bg' => $bg, ':o' => max(1, $max),
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function removeBanner(int $id): void
    {
        $this->ensureBanners();
        try {
            $st = Db::pdo()->prepare("DELETE FROM promo_banners WHERE id=:id LIMIT 1");
            $st->execute([':id' => $id]);
        } catch (\Throwable $e) {
        }
    }

    public function toggleBanner(int $id): void
    {
        $this->ensureBanners();
        try {
            $st = Db::pdo()->prepare("UPDATE promo_banners SET activo=1-activo WHERE id=:id LIMIT 1");
            $st->execute([':id' => $id]);
        } catch (\Throwable $e) {
        }
    }

    /** @param array<int,int> $map id => orden */
    public function saveBannerOrder(array $map): void
    {
        $this->ensureBanners();
        try {
            $pdo = Db::pdo();
            $st = $pdo->prepare("UPDATE promo_banners SET orden=:o WHERE id=:id LIMIT 1");
            foreach ($map as $id => $orden) {
                $id = (int)$id;
                if ($id <= 0) {
                    continue;
                }
                $st->execute([':o' => max(0, (int)$orden), ':id' => $id]);
            }
        } catch (\Throwable $e) {
        }
    }
}
