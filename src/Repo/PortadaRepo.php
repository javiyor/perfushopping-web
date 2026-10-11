<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PortadaRepo
{
    private const MODOS = ['auto', 'rubro', 'marca', 'ultimos', 'manual'];

    private function ensureTables(): void
    {
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS portada_config (
                  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                  modo ENUM('auto','rubro','marca','ultimos','manual') NOT NULL DEFAULT 'auto',
                  codrub INT UNSIGNED DEFAULT NULL,
                  codsub INT UNSIGNED DEFAULT NULL,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS portada_productos (
                  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  idprodu INT UNSIGNED NOT NULL,
                  orden INT UNSIGNED NOT NULL DEFAULT 0,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE KEY uq_portada_productos_idprodu (idprodu),
                  KEY idx_portada_productos_orden (orden, id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $st = Db::pdo()->query("SELECT COUNT(*) FROM portada_config WHERE id=1");
            if ((int)$st->fetchColumn() === 0) {
                Db::pdo()->exec("INSERT IGNORE INTO portada_config (id, modo) VALUES (1, 'auto')");
            }
            $this->ensureColumn('portada_config', 'banner_text', 'VARCHAR(120) DEFAULT NULL');
            $this->ensureColumn('portada_config', 'banner_bold', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->ensureColumn('portada_config', 'banner_italic', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->ensureColumn('portada_config', 'banner_size', "VARCHAR(10) NOT NULL DEFAULT 'grande'");
            $this->ensureColumn('portada_config', 'banner_color', "VARCHAR(7) NOT NULL DEFAULT '#C9A227'");
            $this->ensureColumn('portada_config', 'banner_bg', 'VARCHAR(7) DEFAULT NULL');
        } catch (\Throwable $e) {
        }
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $like = Db::pdo()->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :c');
        $like->execute([':c' => $column]);
        if ($like->fetch() !== false) {
            return;
        }
        Db::pdo()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        $this->ensureTables();
        try {
            $st = Db::pdo()->query("SELECT * FROM portada_config WHERE id=1 LIMIT 1");
            $row = $st->fetch();
            if ($row) {
                return $row;
            }
        } catch (\Throwable $e) {
        }
        return ['id' => 1, 'modo' => 'auto', 'codrub' => null, 'codsub' => null,
            'banner_text' => null, 'banner_bold' => 0, 'banner_italic' => 0,
            'banner_size' => 'grande', 'banner_color' => '#C9A227', 'banner_bg' => null];
    }

    /** @param array<string,mixed> $banner */
    public function saveConfig(string $modo, ?int $codrub, ?int $codsub, array $banner = []): void
    {
        $this->ensureTables();
        if (!in_array($modo, self::MODOS, true)) {
            $modo = 'auto';
        }
        $codrub = $codrub !== null && $codrub > 0 ? $codrub : null;
        $codsub = $codsub !== null && $codsub > 0 ? $codsub : null;
        if ($modo === 'rubro' && $codrub === null) {
            $modo = 'auto';
        }
        if ($modo === 'marca' && $codsub === null) {
            $modo = 'auto';
        }
        $bannerText = mb_substr(trim((string)($banner['text'] ?? '')), 0, 120);
        $bannerSize = in_array($banner['size'] ?? '', ['chico', 'normal', 'grande'], true) ? (string)$banner['size'] : 'grande';
        $bannerColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($banner['color'] ?? '')) ? (string)$banner['color'] : '#C9A227';
        $bannerBg = ($banner['bg'] ?? null) === null || $banner['bg'] === ''
            ? null
            : (preg_match('/^#[0-9a-fA-F]{6}$/', (string)$banner['bg']) ? (string)$banner['bg'] : null);
        $pdo = Db::pdo();
        $st = $pdo->prepare("
            INSERT INTO portada_config (id, modo, codrub, codsub, banner_text, banner_bold, banner_italic, banner_size, banner_color, banner_bg)
            VALUES (1, :modo, :codrub, :codsub, :btext, :bbold, :bitalic, :bsize, :bcolor, :bbg)
            ON DUPLICATE KEY UPDATE modo=VALUES(modo), codrub=VALUES(codrub), codsub=VALUES(codsub),
                banner_text=VALUES(banner_text), banner_bold=VALUES(banner_bold), banner_italic=VALUES(banner_italic),
                banner_size=VALUES(banner_size), banner_color=VALUES(banner_color), banner_bg=VALUES(banner_bg)
        ");
        $st->execute([
            ':modo' => $modo,
            ':codrub' => $codrub,
            ':codsub' => $codsub,
            ':btext' => $bannerText !== '' ? $bannerText : null,
            ':bbold' => !empty($banner['bold']) ? 1 : 0,
            ':bitalic' => !empty($banner['italic']) ? 1 : 0,
            ':bsize' => $bannerSize,
            ':bcolor' => $bannerColor,
            ':bbg' => $bannerBg,
        ]);
    }

    /** @return array<int, array<string,mixed>> */
    public function getManualProducts(): array
    {
        $this->ensureTables();
        try {
            $sql = "
                SELECT p.idprodu, p.produ, p.precio, p.precio1, p.imagen, p.codrub, p.codsub, p.enweb,
                       r.nomrub, s.nomsub, pp.orden
                FROM portada_productos pp
                INNER JOIN producto p ON p.idprodu = pp.idprodu
                LEFT JOIN rubros r ON r.codrub = p.codrub
                LEFT JOIN subrubro s ON s.codsub = p.codsub
                ORDER BY pp.orden ASC, pp.id ASC
            ";
            $st = Db::pdo()->query($sql);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function addManual(int $idprodu, int $orden = 0): bool
    {
        $this->ensureTables();
        if ($idprodu <= 0) {
            return false;
        }
        try {
            $pdo = Db::pdo();
            $st = $pdo->prepare("SELECT idprodu FROM producto WHERE idprodu=:id LIMIT 1");
            $st->execute([':id' => $idprodu]);
            if (!$st->fetch()) {
                return false;
            }
            if ($orden <= 0) {
                $st = $pdo->query("SELECT COALESCE(MAX(orden),0)+1 FROM portada_productos");
                $orden = (int)$st->fetchColumn();
                if ($orden <= 0) $orden = 1;
            }
            $st = $pdo->prepare("INSERT IGNORE INTO portada_productos (idprodu, orden) VALUES (:id, :o)");
            $st->execute([':id' => $idprodu, ':o' => $orden]);
            return $st->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function removeManual(int $idprodu): void
    {
        $this->ensureTables();
        try {
            $st = Db::pdo()->prepare("DELETE FROM portada_productos WHERE idprodu=:id LIMIT 1");
            $st->execute([':id' => $idprodu]);
        } catch (\Throwable $e) {
        }
    }

    public function reorderManual(array $orderedIds): void
    {
        $this->ensureTables();
        try {
            $pdo = Db::pdo();
            $pdo->beginTransaction();
            $st = $pdo->prepare("UPDATE portada_productos SET orden=:o WHERE idprodu=:id LIMIT 1");
            $orden = 1;
            foreach ($orderedIds as $idprodu) {
                $idprodu = (int)$idprodu;
                if ($idprodu <= 0) continue;
                $st->execute([':o' => $orden++, ':id' => $idprodu]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            try { Db::pdo()->rollBack(); } catch (\Throwable $e2) {}
        }
    }

    public function clearManual(): void
    {
        $this->ensureTables();
        try {
            Db::pdo()->exec("DELETE FROM portada_productos");
        } catch (\Throwable $e) {
        }
    }
}
