<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class UbicacionRepo
{
    private static ?bool $tableReady = null;

    public static function ensureTable(): void
    {
        if (self::$tableReady !== null) {
            return;
        }
        self::$tableReady = true;
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS admin_ubicaciones (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    admin_user_id INT UNSIGNED NOT NULL,
                    lat DECIMAL(10, 7) NOT NULL,
                    lng DECIMAL(10, 7) NOT NULL,
                    accuracy INT DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_user_fecha (admin_user_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('UbicacionRepo::ensureTable error: ' . $e->getMessage());
        }
    }

    public function guardar(int $userId, float $lat, float $lng, ?int $accuracy): int
    {
        self::ensureTable();
        $pdo = Db::pdo();
        $st = $pdo->prepare('
            INSERT INTO admin_ubicaciones (admin_user_id, lat, lng, accuracy, created_at)
            VALUES (:u, :lat, :lng, :acc, NOW())
        ');
        $st->execute([':u' => $userId, ':lat' => $lat, ':lng' => $lng, ':acc' => $accuracy]);
        // Higiene: conservar 30 días por usuario.
        try {
            $pdo->prepare("DELETE FROM admin_ubicaciones WHERE admin_user_id = :u AND created_at < NOW() - INTERVAL 30 DAY")
                ->execute([':u' => $userId]);
        } catch (\Throwable $e) {
        }
        return (int)$pdo->lastInsertId();
    }

    /** Última posición conocida por usuario. */
    public function ultimas(): array
    {
        self::ensureTable();
        try {
            return Db::pdo()->query("
                SELECT u.*, a.nombre AS usuario_nombre, a.rol AS usuario_rol
                FROM admin_ubicaciones u
                INNER JOIN (
                    SELECT admin_user_id, MAX(id) AS max_id
                    FROM admin_ubicaciones
                    GROUP BY admin_user_id
                ) m ON m.max_id = u.id
                LEFT JOIN admin_users a ON a.id = u.admin_user_id
                ORDER BY u.created_at DESC
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('UbicacionRepo::ultimas error: ' . $e->getMessage());
            return [];
        }
    }
}
