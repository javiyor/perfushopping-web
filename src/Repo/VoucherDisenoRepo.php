<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class VoucherDisenoRepo
{
    private static bool $ready = false;

    public static function ensureTable(): void
    {
        if (self::$ready) return;
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS voucher_regalo_diseno (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            modelo_imagen VARCHAR(255) DEFAULT NULL,
            titulo VARCHAR(80) NOT NULL,
            cinta VARCHAR(80) NOT NULL,
            mensaje TEXT NOT NULL,
            frase_inferior VARCHAR(160) NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Db::pdo()->exec("INSERT IGNORE INTO voucher_regalo_diseno (id, modelo_imagen, titulo, cinta, mensaje, frase_inferior, updated_at)
            VALUES (1, '/assets/brand/voucher-model.png', '¡FELICITACIONES!', 'GIFT VOUCHER', 'Qué alegría compartir este regalo con vos.\\n\\nGracias por ser parte de este momento.\\n\\nDeseamos que este regalo te acompañe en recuerdos hermosos.', '¡Te esperamos con mucho cariño!', NOW())");
        Db::pdo()->exec("UPDATE voucher_regalo_diseno SET modelo_imagen='/assets/brand/voucher-model.png' WHERE id=1 AND (modelo_imagen IS NULL OR modelo_imagen='')");
        self::$ready = true;
    }

    public function get(): array
    {
        self::ensureTable();
        return Db::pdo()->query('SELECT * FROM voucher_regalo_diseno WHERE id=1')->fetch() ?: [];
    }

    public function save(array $data): void
    {
        self::ensureTable();
        Db::pdo()->prepare('UPDATE voucher_regalo_diseno SET modelo_imagen=:imagen, titulo=:titulo, cinta=:cinta, mensaje=:mensaje, frase_inferior=:frase, updated_at=NOW() WHERE id=1')->execute([
            ':imagen'=>trim((string)($data['modelo_imagen'] ?? '')) ?: null,
            ':titulo'=>trim((string)($data['titulo'] ?? '')),
            ':cinta'=>trim((string)($data['cinta'] ?? '')),
            ':mensaje'=>trim((string)($data['mensaje'] ?? '')),
            ':frase'=>trim((string)($data['frase_inferior'] ?? '')),
        ]);
    }
}
