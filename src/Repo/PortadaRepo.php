<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PortadaRepo
{
    private const MODOS = ['auto', 'rubro', 'marca', 'ultimos', 'manual'];
    private const PLANTILLAS = [
        'elegante' => ['label' => 'Elegante oscura', 'mode' => 'dark', 'color_fondo' => '#050505', 'color_fondo_secundario' => '#0C0B0A', 'color_tarjeta' => '#171411', 'color_acento' => '#D8B25A', 'color_texto' => '#F6F4EF'],
        'minimal' => ['label' => 'Minimalista clara', 'mode' => 'light', 'color_fondo' => '#F6F5F2', 'color_fondo_secundario' => '#ECEAE5', 'color_tarjeta' => '#FFFFFF', 'color_acento' => '#444444', 'color_texto' => '#242424'],
        'boutique' => ['label' => 'Boutique rosa', 'mode' => 'light', 'color_fondo' => '#F8EEF1', 'color_fondo_secundario' => '#F1E1E7', 'color_tarjeta' => '#FFF9FB', 'color_acento' => '#B13D68', 'color_texto' => '#38212A'],
        'natural' => ['label' => 'Natural', 'mode' => 'light', 'color_fondo' => '#EEF2E9', 'color_fondo_secundario' => '#DDE6D5', 'color_tarjeta' => '#FAFCF7', 'color_acento' => '#52734D', 'color_texto' => '#263326'],
    ];

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
            $this->ensureColumn('portada_config', 'banner_sub', 'VARCHAR(160) DEFAULT NULL');
            $this->ensureColumn('portada_config', 'banner_sub_bold', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->ensureColumn('portada_config', 'banner_sub_italic', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->ensureColumn('portada_config', 'banner_sub_size', "VARCHAR(10) NOT NULL DEFAULT 'normal'");
            $this->ensureColumn('portada_config', 'banner_sub_color', "VARCHAR(7) NOT NULL DEFAULT '#FFFFFF'");
            $this->ensureColumn('portada_config', 'plantilla', "VARCHAR(24) NOT NULL DEFAULT 'elegante'");
            $this->ensureColumn('portada_config', 'color_fondo', "VARCHAR(7) NOT NULL DEFAULT '#050505'");
            $this->ensureColumn('portada_config', 'color_fondo_secundario', "VARCHAR(7) NOT NULL DEFAULT '#0C0B0A'");
            $this->ensureColumn('portada_config', 'color_tarjeta', "VARCHAR(7) NOT NULL DEFAULT '#171411'");
            $this->ensureColumn('portada_config', 'color_acento', "VARCHAR(7) NOT NULL DEFAULT '#D8B25A'");
            $this->ensureColumn('portada_config', 'color_texto', "VARCHAR(7) NOT NULL DEFAULT '#F6F4EF'");
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
            'banner_size' => 'grande', 'banner_color' => '#C9A227', 'banner_bg' => null,
            'banner_sub' => null, 'banner_sub_bold' => 0, 'banner_sub_italic' => 0,
            'banner_sub_size' => 'normal', 'banner_sub_color' => '#FFFFFF',
            'plantilla' => 'elegante', 'color_fondo' => '#050505', 'color_fondo_secundario' => '#0C0B0A',
            'color_tarjeta' => '#171411', 'color_acento' => '#D8B25A', 'color_texto' => '#F6F4EF'];
    }

    public static function plantillas(): array
    {
        return self::PLANTILLAS;
    }

    /** @return array{plantilla:string,mode:string,color_fondo:string,color_fondo_secundario:string,color_tarjeta:string,color_acento:string,color_texto:string} */
    public static function tema(array $config): array
    {
        $key = (string)($config['plantilla'] ?? 'elegante');
        if (!isset(self::PLANTILLAS[$key])) $key = 'elegante';
        $tema = self::PLANTILLAS[$key];
        $tema['plantilla'] = $key;
        foreach (['color_fondo','color_fondo_secundario','color_tarjeta','color_acento','color_texto'] as $color) {
            $valor = (string)($config[$color] ?? '');
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $valor)) $tema[$color] = strtoupper($valor);
        }
        return $tema;
    }

    /** @param array<string,mixed> $banner */
    public function saveConfig(string $modo, ?int $codrub, ?int $codsub, array $banner = [], array $theme = []): void
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
        $bannerSub = mb_substr(trim((string)($banner['sub'] ?? '')), 0, 160);
        $bannerSubSize = in_array($banner['sub_size'] ?? '', ['chico', 'normal', 'grande'], true) ? (string)$banner['sub_size'] : 'normal';
        $bannerSubColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($banner['sub_color'] ?? '')) ? (string)$banner['sub_color'] : '#FFFFFF';
        $plantilla = (string)($theme['plantilla'] ?? 'elegante');
        if (!isset(self::PLANTILLAS[$plantilla])) $plantilla = 'elegante';
        $paleta = self::PLANTILLAS[$plantilla];
        $coloresTema = [];
        foreach (['color_fondo','color_fondo_secundario','color_tarjeta','color_acento','color_texto'] as $color) {
            $valor = (string)($theme[$color] ?? $paleta[$color]);
            $coloresTema[$color] = preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? strtoupper($valor) : $paleta[$color];
        }
        $pdo = Db::pdo();
        $st = $pdo->prepare("
            INSERT INTO portada_config (id, modo, codrub, codsub, banner_text, banner_bold, banner_italic, banner_size, banner_color, banner_bg,
                banner_sub, banner_sub_bold, banner_sub_italic, banner_sub_size, banner_sub_color,
                plantilla, color_fondo, color_fondo_secundario, color_tarjeta, color_acento, color_texto)
            VALUES (1, :modo, :codrub, :codsub, :btext, :bbold, :bitalic, :bsize, :bcolor, :bbg,
                :bsub, :bsubbold, :bsubitalic, :bsubsize, :bsubcolor,
                :plantilla, :color_fondo, :color_fondo_secundario, :color_tarjeta, :color_acento, :color_texto)
            ON DUPLICATE KEY UPDATE modo=VALUES(modo), codrub=VALUES(codrub), codsub=VALUES(codsub),
                banner_text=VALUES(banner_text), banner_bold=VALUES(banner_bold), banner_italic=VALUES(banner_italic),
                banner_size=VALUES(banner_size), banner_color=VALUES(banner_color), banner_bg=VALUES(banner_bg),
                banner_sub=VALUES(banner_sub), banner_sub_bold=VALUES(banner_sub_bold), banner_sub_italic=VALUES(banner_sub_italic),
                banner_sub_size=VALUES(banner_sub_size), banner_sub_color=VALUES(banner_sub_color),
                plantilla=VALUES(plantilla), color_fondo=VALUES(color_fondo), color_fondo_secundario=VALUES(color_fondo_secundario),
                color_tarjeta=VALUES(color_tarjeta), color_acento=VALUES(color_acento), color_texto=VALUES(color_texto)
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
            ':bsub' => $bannerSub !== '' ? $bannerSub : null,
            ':bsubbold' => !empty($banner['sub_bold']) ? 1 : 0,
            ':bsubitalic' => !empty($banner['sub_italic']) ? 1 : 0,
            ':bsubsize' => $bannerSubSize,
            ':bsubcolor' => $bannerSubColor,
            ':plantilla' => $plantilla,
            ':color_fondo' => $coloresTema['color_fondo'],
            ':color_fondo_secundario' => $coloresTema['color_fondo_secundario'],
            ':color_tarjeta' => $coloresTema['color_tarjeta'],
            ':color_acento' => $coloresTema['color_acento'],
            ':color_texto' => $coloresTema['color_texto'],
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
