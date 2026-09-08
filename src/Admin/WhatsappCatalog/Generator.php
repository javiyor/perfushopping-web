<?php

declare(strict_types=1);

namespace Perfushopping\Web\Admin\WhatsappCatalog;

use Perfushopping\Web\Infra\Db;
use PDO;

final class Generator
{
    /** @var string Ruta donde se guardará el archivo CSV - en public para https://perfushopping.ar/catalog_products.csv */
    private const CSV_PATH = __DIR__ . '/../../../../catalog_products.csv';
    public static function csvPath(): string
    {
        // En server Hostinger el docroot es public_html al lado de repo
        $cands = [
            __DIR__ . '/../../../../public_html/catalog_products.csv',
            __DIR__ . '/../../../public/catalog_products.csv',
            __DIR__ . '/../../../../catalog_products.csv',
        ];
        foreach ($cands as $p) if (is_dir(dirname($p))) return $p;
        return $cands[0];
    }

    /** @var string[] Encabezados del catálogo Facebook/WhApp en orden exacto */
    private const HEADERS = [
        'id',
        'title',
        'description',
        'availability',
        'condition',
        'price',
        'link',
        'image_link',
        'brand',
        'google_product_category',
        'fb_product_category',
        'quantity_to_sell_on_facebook',
        'sale_price',
        'sale_price_effective_date',
        'item_group_id',
        'gender',
        'color',
        'size',
        'age_group',
        'material',
        'pattern',
        'shipping',
        'shipping_weight',
        'offer_disclaimer',
        'offer_disclaimer_url',
        'video[0].url',
        'video[0].tag[0]',
        'gtin',
        'product_tags[0]',
        'product_tags[1]',
        'style',
    ];

    /** @return array<string,mixed> */
    private static function getCsvHeaders(): array
    {
        return self::HEADERS;
    }

    /**
     * Genera el catálogo completo y guarda en catalog_products.csv
     *
     * @return string|false Ruta del archivo generado o false en error
     */
    public static function generate()
    {
        try {
            $pdo = Db::pdo();

            // Query productos visibles en web (enweb=1, sin rubros excluidos, fecompra válida)
            // Usamos placeholders para image_link y link - los completaremos en mapRow
            $sql = "
                SELECT
                    p.idprodu AS id,
                    p.produ AS title,
                    IFNULL(p.observ, '') AS description,
                    CASE WHEN p.enweb = 1 THEN 'in stock' ELSE 'out of stock' END AS availability,
                    'new' AS `condition`,
                    FORMAT(p.precio / 100, 2) AS price_raw,
                    CONCAT('/producto/', p.idprodu) AS link_base,
                    p.imagen AS image_filename,
                    'Perfushopping' AS brand,
                    '' AS google_product_category,
                    '' AS fb_product_category,
                    '1' AS quantity_to_sell_on_facebook,
                    IF(p.precio1 > 0 AND p.precio1 < p.precio, FORMAT(p.precio1 / 100, 2), '') AS sale_price,
                    '' AS sale_price_effective_date,
                    '' AS item_group_id,
                    'unisex' AS gender,
                    '' AS color,
                    '' AS size,
                    'adult' AS age_group,
                    '' AS material,
                    '' AS pattern,
                    'US:CA:Ground:0.00 USD;US:NY:Air:0.00 USD;' AS shipping,
                    '0 kg' AS shipping_weight,
                    '' AS offer_disclaimer,
                    '' AS offer_disclaimer_url,
                    '' AS 'video[0].url',
                    '' AS 'video[0].tag[0]',
                    IF(LENGTH(TRIM(p.codscan)) IN (8, 12, 13, 14), p.codscan, '') AS gtin,
                    '' AS 'product_tags[0]',
                    '' AS 'product_tags[1]',
                    '' AS style
                FROM producto p
                WHERE p.enweb = 1
                  AND p.codrub NOT IN (228, 192, 193, 198, 146)
                  AND LOWER(p.produ) NOT LIKE '%tester%'
                  AND p.fecompra IS NOT NULL
                  AND p.fecompra <> '0000-00-00'
                  AND p.fecompra > DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                ORDER BY p.idprodu ASC
            ";

            $st = $pdo->prepare($sql);
            $st->execute();

            $rows = $st->fetchAll(PDO::FETCH_ASSOC);

            if ($rows === false) {
                return false;
            }

            // Construir CSV completando los URLs después de obtener los datos
            $csvLines = [];

            // Encabezado
            $csvLines[] = implode(',', self::getCsvHeaders());

            // Filas de datos - completar URLs en cada fila
            $validRows = 0;
            foreach ($rows as $row) {
                $mapped = self::mapRow($row);
                // Validación rápida: asegurar que id no esté vacío
                if (trim($mapped['id']) === '') {
                    continue; // Saltar filas sin ID
                }
                $csvLines[] = self::csvEncodeRow($mapped);
                $validRows++;
            }

            $content = implode("\n", $csvLines);

            // Guardar en public para URL https://perfushopping.ar/catalog_products.csv
            $csvPath = self::csvPath();
            $dir = dirname($csvPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $result = file_put_contents($csvPath, $content);
            if ($result === false) {
                error_log('WhatsApp Catalog Generator: Cannot write to ' . $csvPath . ' (tried to write ' . strlen($content) . ' bytes)');
                return false;
            }
            if ($csvPath !== self::CSV_PATH) @copy($csvPath, self::CSV_PATH);

            if ($validRows > 0) {
                error_log('WhatsApp Catalog Generator: Successfully generated catalog with ' . $validRows . ' products at ' . $csvPath);
            }
            return $csvPath;
        } catch (\Throwable $e) {
            error_log('WhatsApp Catalog Generator Exception: ' . $e->getMessage());
            error_log('WhatsApp Catalog Generator Stack: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Mapea los datos de la BD a los campos del catálogo, asegurando formato correcto
     * y completando los URLs con el host actual
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function mapRow(array $row): array
    {
        $mapped = [];

        // ID - asegurar que sea string y único
        $mapped['id'] = (string)($row['id'] ?? '');

        // Título - limitar a 200 chars, escapar comas
        $title = (string)($row['title'] ?? '');
        $mapped['title'] = strlen($title) > 200 ? substr($title, 0, 197) . '...' : $title;

        // Descripción - texto plano, sin comas sin escapar
        $desc = (string)($row['description'] ?? '');
        $mapped['description'] = addcslashes($desc, "\n\r\"\\");

        // Disponibilidad - valores exactos requeridos
        $avail = (string)($row['availability'] ?? 'out of stock');
        $mapped['availability'] = in_array($avail, ['in stock', 'out of stock']) ? $avail : 'out of stock';

        // Condición - valores exactos requeridos
        $cond = (string)($row['condition'] ?? 'new');
        $mapped['condition'] = in_array($cond, ['new', 'used']) ? $cond : 'new';

        // Precio - formato number_currency con punto decimal, espacio, código ISO
        $priceRaw = (string)($row['price_raw'] ?? '0.00');
        // Quitar posibles comas y formatear con punto
        $priceClean = str_replace(',', '.', $priceRaw);
        $parts = explode('.', $priceClean, 2);
        $intPart = $parts[0];
        $decPart = isset($parts[1]) ? substr($parts[1], 0, 2) : '00';
        // Asegurar que tenga exactamente 2 decimales
        $decPart = strlen($decPart) === 1 ? $decPart . '0' : $decPart;
        $mapped['price'] = $intPart . '.' . $decPart . ' USD';

        // Link - URL absoluta del producto (fallback perfushopping.ar si no hay HTTP_HOST, ej cron)
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'perfushopping.ar';
        $id = (string)($row['id'] ?? '1');
        $linkBase = (string)($row['link_base'] ?? '/producto/1');
        $mapped['link'] = 'https://' . $host . $linkBase . '/' . $id;

        // Image link - construir URL con host + extensión verificada
        $img = (string)($row['image_filename'] ?? '');
        $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $mapped['image_link'] = in_array($ext, $allowedExts)
            ? 'https://' . $host . '/imagenes/' . $img
            : 'https://' . $host . '/images/default-product.jpg';

        // Marca - por defecto
        $mapped['brand'] = 'Perfushopping';

        // Google product category - vacío por defecto
        $mapped['google_product_category'] = '';

        // FB product category - vacío
        $mapped['fb_product_category'] = '';

        // Cantidad Facebook - siempre 1 como mínimo
        $mapped['quantity_to_sell_on_facebook'] = '1';

        // Precio oferta - si hay precio1 (descuento)
        $salePrice = (string)($row['sale_price'] ?? '');
        $mapped['sale_price'] = $salePrice ? $salePrice . ' USD' : '';

        // Fecha oferta - vacío por defecto
        $mapped['sale_price_effective_date'] = '';

        // Grupo variantes - vacío
        $mapped['item_group_id'] = '';

        // Género - default unisex
        $mapped['gender'] = 'unisex';

        // Color - vacío
        $mapped['color'] = '';

        // Talla - vacío
        $mapped['size'] = '';

        // Grupo edad - default adult
        $mapped['age_group'] = 'adult';

        // Material - vacío
        $mapped['material'] = '';

        // Patrón - vacío
        $mapped['pattern'] = '';

        // Envío - formato País:Región:Servicio:Precio;
        $mapped['shipping'] = 'US:CA:Ground:0.00 USD;';

        // Peso envío
        $mapped['shipping_weight'] = '0 kg';

        // Aviso legal
        $mapped['offer_disclaimer'] = '';

        // URL aviso legal
        $mapped['offer_disclaimer_url'] = '';

        // Video URL
        $mapped['video[0].url'] = '';

        // Video tag
        $mapped['video[0].tag[0]'] = '';

        // GTIN - solo si tiene longitud válida (8,12,13,14 dígitos)
        $gtin = (string)($row['gtin'] ?? '');
        if (ctype_digit($gtin) && strlen($gtin) >= 8 && strlen($gtin) <= 14) {
            $mapped['gtin'] = $gtin;
        } else {
            $mapped['gtin'] = '';
        }

        // Etiquetas - vacías por defecto
        $mapped['product_tags[0]'] = '';
        $mapped['product_tags[1]'] = '';

        // Estilo - vacío
        $mapped['style'] = '';

        return $mapped;
    }

    /**
     * Codifica una fila CSV correctamente:
     - Campos con comas, comillas o saltos de línea son Entrecomillados
     - Comillas internas se duplican
     - Codificación UTF-8
     *
     * @param array<string,mixed> $row
     * @return string
     */
    private static function csvEncodeRow(array $row): string
    {
        $fields = [];

        foreach (self::getCsvHeaders() as $header) {
            $value = $row[$header] ?? '';

            // Convertir a string y codificar en UTF-8
            $value = (string)$value;

            // Si el campo contiene comas, comillas o saltos de línea, Entrecomillarlo
            if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n") || str_contains($value, "\r")) {
                // Duplicar comillas internas
                $value = '"' . str_replace('"', '""', $value) . '"';
            }

            $fields[] = $value;
        }

        return implode(',', $fields);
    }
}