<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PortadaRepo
{
    private static bool $schemaAttempted = false;
    private static ?array $configCache = null;
    private const MODOS = ['auto', 'rubro', 'marca', 'ultimos', 'manual'];
    private const PLANTILLAS = [
        'elegante' => ['label' => 'Elegante oscura', 'mode' => 'dark', 'color_fondo' => '#050505', 'color_fondo_secundario' => '#0C0B0A', 'color_tarjeta' => '#171411', 'color_acento' => '#D8B25A', 'color_texto' => '#F6F4EF'],
        'minimal' => ['label' => 'Minimalista clara', 'mode' => 'light', 'color_fondo' => '#F6F5F2', 'color_fondo_secundario' => '#ECEAE5', 'color_tarjeta' => '#FFFFFF', 'color_acento' => '#444444', 'color_texto' => '#242424'],
        'boutique' => ['label' => 'Boutique rosa', 'mode' => 'light', 'color_fondo' => '#F8EEF1', 'color_fondo_secundario' => '#F1E1E7', 'color_tarjeta' => '#FFF9FB', 'color_acento' => '#B13D68', 'color_texto' => '#38212A'],
        'natural' => ['label' => 'Natural', 'mode' => 'light', 'color_fondo' => '#EEF2E9', 'color_fondo_secundario' => '#DDE6D5', 'color_tarjeta' => '#FAFCF7', 'color_acento' => '#52734D', 'color_texto' => '#263326'],
    ];
    private const COLORES_COMPONENTES = [
        'header_bg' => ['Encabezado: fondo', '#171411'],
        'header_border' => ['Encabezado: borde', '#D8B25A'],
        'logo_bg' => ['Logo: fondo', '#26221B'],
        'brand_text' => ['Nombre de tienda', '#F6F4EF'],
        'brand_subtext' => ['Subtítulo de tienda', '#B9B3A8'],
        'nav_bg' => ['Navegación: fondo', '#26221B'],
        'nav_text' => ['Navegación: texto', '#F6F4EF'],
        'nav_border' => ['Navegación: borde', '#D8B25A'],
        'hero_bg' => ['Portada: fondo destacado', '#26221B'],
        'hero_text' => ['Portada: texto', '#F6F4EF'],
        'hero_accent' => ['Portada: títulos y acentos', '#D8B25A'],
        'contact_text' => ['Contacto: texto', '#D4D0C8'],
        'section_bg' => ['Secciones: fondo', '#171411'],
        'section_border' => ['Secciones: borde', '#55462B'],
        'catalog_title' => ['Catálogo: título', '#D8B25A'],
        'filter_bg' => ['Filtros: fondo', '#26221B'],
        'filter_text' => ['Filtros: texto', '#F6F4EF'],
        'filter_border' => ['Filtros: borde', '#55462B'],
        'product_bg' => ['Productos: tarjeta', '#171411'],
        'product_border' => ['Productos: borde', '#55462B'],
        'product_hover_bg' => ['Productos: fondo al pasar', '#26221B'],
        'product_hover_border' => ['Productos: borde al pasar', '#D8B25A'],
        'product_media_bg' => ['Productos: fondo de imagen', '#26221B'],
        'product_category' => ['Productos: categoría y marca', '#B9B3A8'],
        'promo_title' => ['Ofertas: título', '#FF6B6B'],
        'promo_bg' => ['Ofertas: tarjeta', '#26221B'],
        'promo_border' => ['Ofertas: borde', '#D8B25A'],
        'promo_old_price' => ['Ofertas: precio anterior', '#B9B3A8'],
        'promo_price' => ['Ofertas: precio promocional', '#7CFC9A'],
        'promo_discount_bg' => ['Ofertas: fondo de descuento', '#C0392B'],
        'promo_discount_text' => ['Ofertas: texto de descuento', '#FFFFFF'],
        'footer_bg' => ['Pie: fondo', '#171411'],
        'footer_text' => ['Pie: texto', '#D4D0C8'],
        'footer_link' => ['Pie: enlaces', '#D8B25A'],
        'footer_border' => ['Pie: borde', '#55462B'],
        'button_bg' => ['Botones: fondo', '#D8B25A'],
        'button_text' => ['Botones: texto', '#17140F'],
    ];

    private function ensureTables(): void
    {
        if (self::$schemaAttempted) return;
        self::$schemaAttempted = true;
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
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS portada_confianza (
                  slot_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                  foto VARCHAR(255) DEFAULT NULL,
                  foto_titulo VARCHAR(120) DEFAULT NULL,
                  video_url VARCHAR(500) DEFAULT NULL,
                  video_titulo VARCHAR(120) DEFAULT NULL,
                  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $st = Db::pdo()->query("SELECT COUNT(*) FROM portada_config WHERE id=1");
            if ((int)$st->fetchColumn() === 0) {
                Db::pdo()->exec("INSERT IGNORE INTO portada_config (id, modo) VALUES (1, 'auto')");
            }
            $this->ensureColumns('portada_config', [
                'banner_text' => 'VARCHAR(120) DEFAULT NULL',
                'banner_bold' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'banner_italic' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'banner_size' => "VARCHAR(10) NOT NULL DEFAULT 'grande'",
                'banner_color' => "VARCHAR(7) NOT NULL DEFAULT '#C9A227'",
                'banner_bg' => 'VARCHAR(7) DEFAULT NULL',
                'banner_sub' => 'VARCHAR(160) DEFAULT NULL',
                'banner_sub_bold' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'banner_sub_italic' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'banner_sub_size' => "VARCHAR(10) NOT NULL DEFAULT 'normal'",
                'banner_sub_color' => "VARCHAR(7) NOT NULL DEFAULT '#FFFFFF'",
                'plantilla' => "VARCHAR(24) NOT NULL DEFAULT 'elegante'",
                'color_fondo' => "VARCHAR(7) NOT NULL DEFAULT '#050505'",
                'color_fondo_secundario' => "VARCHAR(7) NOT NULL DEFAULT '#0C0B0A'",
                'color_tarjeta' => "VARCHAR(7) NOT NULL DEFAULT '#171411'",
                'color_acento' => "VARCHAR(7) NOT NULL DEFAULT '#D8B25A'",
                'color_texto' => "VARCHAR(7) NOT NULL DEFAULT '#F6F4EF'",
                'fuente_tarjeta' => "VARCHAR(16) NOT NULL DEFAULT 'system'",
                'color_nombre_producto' => "VARCHAR(7) NOT NULL DEFAULT '#F6F4EF'",
                'tamano_nombre_producto' => 'TINYINT UNSIGNED NOT NULL DEFAULT 14',
                'color_precio_producto' => "VARCHAR(7) NOT NULL DEFAULT '#D8B25A'",
                'tamano_precio_producto' => 'TINYINT UNSIGNED NOT NULL DEFAULT 18',
                'footer_text' => 'TEXT DEFAULT NULL',
                'colores_componentes' => 'TEXT DEFAULT NULL',
            ]);
        } catch (\Throwable $e) {
            error_log('PortadaRepo::ensureTables error: ' . $e->getMessage());
        }
    }

    private function ensureColumns(string $table, array $definitions): void
    {
        $columns = array_fill_keys(array_column(Db::pdo()->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(), 'Field'), true);
        foreach ($definitions as $column => $definition) {
            if (isset($columns[$column])) continue;
            Db::pdo()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
            $columns[$column] = true;
        }
    }

    /** Ensures homepage configuration tables during admin setup, never in public read paths. */
    public function ensureSchema(): void
    {
        $this->ensureTables();
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        if (self::$configCache !== null) return self::$configCache;
        try {
            $st = Db::pdo()->query("SELECT * FROM portada_config WHERE id=1 LIMIT 1");
            $row = $st->fetch();
            if ($row) {
                return self::$configCache = $row;
            }
        } catch (\Throwable $e) {
        }
        return self::$configCache = ['id' => 1, 'modo' => 'auto', 'codrub' => null, 'codsub' => null,
            'banner_text' => null, 'banner_bold' => 0, 'banner_italic' => 0,
            'banner_size' => 'grande', 'banner_color' => '#C9A227', 'banner_bg' => null,
            'banner_sub' => null, 'banner_sub_bold' => 0, 'banner_sub_italic' => 0,
            'banner_sub_size' => 'normal', 'banner_sub_color' => '#FFFFFF',
            'plantilla' => 'elegante', 'color_fondo' => '#050505', 'color_fondo_secundario' => '#0C0B0A',
            'color_tarjeta' => '#171411', 'color_acento' => '#D8B25A', 'color_texto' => '#F6F4EF',
            'fuente_tarjeta' => 'system', 'color_nombre_producto' => '#F6F4EF', 'tamano_nombre_producto' => 14,
            'color_precio_producto' => '#D8B25A', 'tamano_precio_producto' => 18, 'footer_text' => ''];
    }

    public static function plantillas(): array
    {
        return self::PLANTILLAS;
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function coloresComponentes(): array
    {
        return self::COLORES_COMPONENTES;
    }

    /** @param array<string,string> $colors @return array<string,string> */
    private static function defaultsComponentes(array $colors, bool $light): array
    {
        $accent = $colors['color_acento'];
        $card = $colors['color_tarjeta'];
        $text = $colors['color_texto'];
        $background = $colors['color_fondo_secundario'];
        return [
            'header_bg' => $card, 'header_border' => $accent, 'logo_bg' => $background, 'brand_text' => $text,
            'brand_subtext' => $text, 'nav_bg' => $background, 'nav_text' => $text,
            'nav_border' => $accent, 'hero_bg' => $card, 'hero_text' => $text,
            'hero_accent' => $accent, 'contact_text' => $text, 'section_bg' => $card,
            'section_border' => $accent, 'catalog_title' => $accent, 'filter_bg' => $background,
            'filter_text' => $text, 'filter_border' => $accent, 'product_bg' => $card,
            'product_border' => $accent, 'product_hover_bg' => $background, 'product_hover_border' => $accent,
            'product_media_bg' => $background, 'product_category' => $text, 'promo_title' => $accent,
            'promo_bg' => $card, 'promo_border' => $accent, 'promo_old_price' => $text,
            'promo_price' => $accent, 'promo_discount_bg' => '#C0392B', 'promo_discount_text' => '#FFFFFF',
            'footer_bg' => $background, 'footer_text' => $text,
            'footer_link' => $accent, 'footer_border' => $accent, 'button_bg' => $accent,
            'button_text' => $light ? '#FFFFFF' : '#17140F',
        ];
    }

    private static function luminancia(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $linear = array_map(static function (int $value): float {
            $channel = $value / 255;
            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, $channels);
        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    private static function contraste(string $foreground, string $background): float
    {
        $a = self::luminancia($foreground);
        $b = self::luminancia($background);
        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    private static function colorTextoLegible(string $foreground, string $background, string $fallback): string
    {
        if (self::contraste($foreground, $background) >= 4.5) return $foreground;
        if (self::contraste($fallback, $background) >= 4.5) return $fallback;
        $dark = '#17140F';
        $light = '#FFFFFF';
        return self::contraste($dark, $background) >= self::contraste($light, $background) ? $dark : $light;
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
        $tema['color_texto'] = self::colorTextoLegible($tema['color_texto'], $tema['color_fondo'], self::PLANTILLAS[$key]['color_texto']);
        $fuente = (string)($config['fuente_tarjeta'] ?? 'system');
        $tema['fuente_tarjeta'] = in_array($fuente, ['system', 'serif', 'rounded'], true) ? $fuente : 'system';
        foreach (['color_nombre_producto', 'color_precio_producto'] as $color) {
            $valor = (string)($config[$color] ?? '');
            $fallback = $color === 'color_nombre_producto' ? $tema['color_texto'] : $tema['color_acento'];
            $tema[$color] = preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? strtoupper($valor) : $fallback;
        }
        $tema['tamano_nombre_producto'] = max(12, min(20, (int)($config['tamano_nombre_producto'] ?? 14)));
        $tema['tamano_precio_producto'] = max(14, min(26, (int)($config['tamano_precio_producto'] ?? 18)));
        $baseColors = [
            'color_fondo' => $tema['color_fondo'], 'color_fondo_secundario' => $tema['color_fondo_secundario'],
            'color_tarjeta' => $tema['color_tarjeta'], 'color_acento' => $tema['color_acento'],
            'color_texto' => $tema['color_texto'],
        ];
        $defaultComponentColors = self::defaultsComponentes($baseColors, $tema['mode'] === 'light');
        $storedComponentColors = json_decode((string)($config['colores_componentes'] ?? ''), true);
        if (!is_array($storedComponentColors)) $storedComponentColors = [];
        $componentColors = [];
        foreach (self::COLORES_COMPONENTES as $key => [, $default]) {
            $default = $defaultComponentColors[$key] ?? $default;
            $value = (string)($storedComponentColors[$key] ?? '');
            $componentColors[$key] = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtoupper($value) : $default;
        }
        $textBackgrounds = [
            'brand_text' => 'header_bg',
            'brand_subtext' => 'header_bg',
            'nav_text' => 'nav_bg',
            'hero_text' => 'hero_bg',
            'hero_accent' => 'hero_bg',
            'contact_text' => 'section_bg',
            'catalog_title' => 'section_bg',
            'filter_text' => 'filter_bg',
            'product_category' => 'product_bg',
            'promo_title' => 'promo_bg',
            'promo_old_price' => 'promo_bg',
            'promo_price' => 'promo_bg',
            'footer_text' => 'footer_bg',
            'footer_link' => 'footer_bg',
            'button_text' => 'button_bg',
            'promo_discount_text' => 'promo_discount_bg',
        ];
        foreach ($textBackgrounds as $textKey => $backgroundKey) {
            $componentColors[$textKey] = self::colorTextoLegible(
                $componentColors[$textKey],
                $componentColors[$backgroundKey],
                $defaultComponentColors[$textKey] ?? $tema['color_texto']
            );
        }
        $tema['color_nombre_producto'] = self::colorTextoLegible($tema['color_nombre_producto'], $componentColors['product_bg'], $tema['color_texto']);
        $tema['color_precio_producto'] = self::colorTextoLegible($tema['color_precio_producto'], $componentColors['product_bg'], $tema['color_acento']);
        $tema['colores_componentes'] = $componentColors;
        return $tema;
    }

    /** @param array<string,mixed> $banner */
    public function saveConfig(string $modo, ?int $codrub, ?int $codsub, array $banner = [], array $theme = [], string $footerText = '', array $componentColors = []): void
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
        $fuenteTarjeta = (string)($theme['fuente_tarjeta'] ?? 'system');
        if (!in_array($fuenteTarjeta, ['system', 'serif', 'rounded'], true)) $fuenteTarjeta = 'system';
        $colorNombre = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($theme['color_nombre_producto'] ?? ''))
            ? strtoupper((string)$theme['color_nombre_producto']) : $coloresTema['color_texto'];
        $colorPrecio = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($theme['color_precio_producto'] ?? ''))
            ? strtoupper((string)$theme['color_precio_producto']) : $coloresTema['color_acento'];
        $tamanoNombre = max(12, min(20, (int)($theme['tamano_nombre_producto'] ?? 14)));
        $tamanoPrecio = max(14, min(26, (int)($theme['tamano_precio_producto'] ?? 18)));
        $footerText = mb_substr(trim($footerText), 0, 600);
        $defaultComponentColors = self::defaultsComponentes($coloresTema, $paleta['mode'] === 'light');
        $validatedComponentColors = [];
        foreach (self::COLORES_COMPONENTES as $key => [, $default]) {
            $default = $defaultComponentColors[$key] ?? $default;
            $value = (string)($componentColors[$key] ?? $default);
            $validatedComponentColors[$key] = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtoupper($value) : $default;
        }
        $componentColorsJson = json_encode($validatedComponentColors, JSON_UNESCAPED_SLASHES);
        $pdo = Db::pdo();
        $st = $pdo->prepare("
            INSERT INTO portada_config (id, modo, codrub, codsub, banner_text, banner_bold, banner_italic, banner_size, banner_color, banner_bg,
                banner_sub, banner_sub_bold, banner_sub_italic, banner_sub_size, banner_sub_color,
                plantilla, color_fondo, color_fondo_secundario, color_tarjeta, color_acento, color_texto,
                fuente_tarjeta, color_nombre_producto, tamano_nombre_producto, color_precio_producto, tamano_precio_producto, footer_text, colores_componentes)
            VALUES (1, :modo, :codrub, :codsub, :btext, :bbold, :bitalic, :bsize, :bcolor, :bbg,
                :bsub, :bsubbold, :bsubitalic, :bsubsize, :bsubcolor,
                :plantilla, :color_fondo, :color_fondo_secundario, :color_tarjeta, :color_acento, :color_texto,
                :fuente_tarjeta, :color_nombre_producto, :tamano_nombre_producto, :color_precio_producto, :tamano_precio_producto, :footer_text, :colores_componentes)
            ON DUPLICATE KEY UPDATE modo=VALUES(modo), codrub=VALUES(codrub), codsub=VALUES(codsub),
                banner_text=VALUES(banner_text), banner_bold=VALUES(banner_bold), banner_italic=VALUES(banner_italic),
                banner_size=VALUES(banner_size), banner_color=VALUES(banner_color), banner_bg=VALUES(banner_bg),
                banner_sub=VALUES(banner_sub), banner_sub_bold=VALUES(banner_sub_bold), banner_sub_italic=VALUES(banner_sub_italic),
                banner_sub_size=VALUES(banner_sub_size), banner_sub_color=VALUES(banner_sub_color),
                plantilla=VALUES(plantilla), color_fondo=VALUES(color_fondo), color_fondo_secundario=VALUES(color_fondo_secundario),
                color_tarjeta=VALUES(color_tarjeta), color_acento=VALUES(color_acento), color_texto=VALUES(color_texto),
                fuente_tarjeta=VALUES(fuente_tarjeta), color_nombre_producto=VALUES(color_nombre_producto),
                tamano_nombre_producto=VALUES(tamano_nombre_producto), color_precio_producto=VALUES(color_precio_producto),
                tamano_precio_producto=VALUES(tamano_precio_producto), footer_text=VALUES(footer_text),
                colores_componentes=VALUES(colores_componentes)
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
            ':fuente_tarjeta' => $fuenteTarjeta,
            ':color_nombre_producto' => $colorNombre,
            ':tamano_nombre_producto' => $tamanoNombre,
            ':color_precio_producto' => $colorPrecio,
            ':tamano_precio_producto' => $tamanoPrecio,
            ':footer_text' => $footerText !== '' ? $footerText : null,
            ':colores_componentes' => $componentColorsJson,
        ]);
        self::$configCache = null;
    }

    /** @return array<int, array<string,mixed>> */
    public function getManualProducts(): array
    {
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

    /** @return array<int,array{slot_id:int,foto:string,foto_titulo:string,video_url:string,video_titulo:string}> */
    public function getTrustMedia(): array
    {
        $slots = [];
        for ($i = 1; $i <= 3; $i++) {
            $slots[$i] = ['slot_id' => $i, 'foto' => '', 'foto_titulo' => '', 'video_url' => '', 'video_titulo' => ''];
        }
        try {
            foreach (Db::pdo()->query('SELECT * FROM portada_confianza ORDER BY slot_id ASC')->fetchAll() as $row) {
                $id = (int)($row['slot_id'] ?? 0);
                if ($id < 1 || $id > 3) continue;
                $slots[$id] = [
                    'slot_id' => $id,
                    'foto' => trim((string)($row['foto'] ?? '')),
                    'foto_titulo' => trim((string)($row['foto_titulo'] ?? '')),
                    'video_url' => trim((string)($row['video_url'] ?? '')),
                    'video_titulo' => trim((string)($row['video_titulo'] ?? '')),
                ];
            }
        } catch (\Throwable $e) {
            error_log('PortadaRepo::getTrustMedia error: ' . $e->getMessage());
        }
        return array_values($slots);
    }

    /** @param array<int,array{foto?:string,foto_titulo?:string,video_url?:string,video_titulo?:string}> $slots */
    public function saveTrustMedia(array $slots): void
    {
        $this->ensureTables();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO portada_confianza (slot_id, foto, foto_titulo, video_url, video_titulo, updated_at)
                VALUES (:slot, :foto, :foto_titulo, :video_url, :video_titulo, NOW())
                ON DUPLICATE KEY UPDATE foto=VALUES(foto), foto_titulo=VALUES(foto_titulo), video_url=VALUES(video_url), video_titulo=VALUES(video_titulo), updated_at=NOW()');
            for ($i = 1; $i <= 3; $i++) {
                $slot = $slots[$i] ?? [];
                $st->execute([
                    ':slot' => $i,
                    ':foto' => trim((string)($slot['foto'] ?? '')) ?: null,
                    ':foto_titulo' => mb_substr(trim((string)($slot['foto_titulo'] ?? '')), 0, 120) ?: null,
                    ':video_url' => trim((string)($slot['video_url'] ?? '')) ?: null,
                    ':video_titulo' => mb_substr(trim((string)($slot['video_titulo'] ?? '')), 0, 120) ?: null,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
