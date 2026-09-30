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

    private static ?bool $tokensTableReady = null;

    public static function ensureTokensTable(): void
    {
        if (self::$tokensTableReady !== null) {
            return;
        }
        self::$tokensTableReady = true;
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS admin_ubicacion_tokens (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    admin_user_id INT UNSIGNED NOT NULL,
                    dispositivo VARCHAR(80) NOT NULL DEFAULT '',
                    token VARCHAR(64) NOT NULL,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_token (token),
                    KEY idx_user (admin_user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('UbicacionRepo::ensureTokensTable error: ' . $e->getMessage());
        }
    }

    public function crearToken(int $userId, string $dispositivo): array
    {
        self::ensureTokensTable();
        $token = bin2hex(random_bytes(16));
        Db::pdo()->prepare('
            INSERT INTO admin_ubicacion_tokens (admin_user_id, dispositivo, token, activo, created_at)
            VALUES (:u, :d, :t, 1, NOW())
        ')->execute([':u' => $userId, ':d' => mb_substr($dispositivo, 0, 80), ':t' => $token]);
        return ['id' => (int)Db::pdo()->lastInsertId(), 'token' => $token];
    }

    /** @return array<int,array<string,mixed>> */
    public function listarTokens(): array
    {
        self::ensureTokensTable();
        try {
            return Db::pdo()->query("
                SELECT t.*, a.nombre AS usuario_nombre
                FROM admin_ubicacion_tokens t
                LEFT JOIN admin_users a ON a.id = t.admin_user_id
                ORDER BY t.id DESC
            ")->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function revocarToken(int $id): void
    {
        self::ensureTokensTable();
        Db::pdo()->prepare('UPDATE admin_ubicacion_tokens SET activo = 0 WHERE id = :id LIMIT 1')
            ->execute([':id' => $id]);
    }

    public function usuarioPorToken(string $token): ?array
    {
        self::ensureTokensTable();
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        $st = Db::pdo()->prepare('
            SELECT t.admin_user_id
            FROM admin_ubicacion_tokens t
            WHERE t.token = :t AND t.activo = 1
            LIMIT 1
        ');
        $st->execute([':t' => $token]);
        $row = $st->fetch() ?: null;
        return $row ? ['admin_user_id' => (int)$row['admin_user_id']] : null;
    }

    public function guardarConFecha(int $userId, float $lat, float $lng, ?int $accuracy, ?string $fecha = null): int
    {
        self::ensureTable();
        $pdo = Db::pdo();
        if ($fecha !== null && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fecha)) {
            $st = $pdo->prepare('
                INSERT INTO admin_ubicaciones (admin_user_id, lat, lng, accuracy, created_at)
                VALUES (:u, :lat, :lng, :acc, :f)
            ');
            $st->execute([':u' => $userId, ':lat' => $lat, ':lng' => $lng, ':acc' => $accuracy, ':f' => $fecha]);
        } else {
            return $this->guardar($userId, $lat, $lng, $accuracy);
        }
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
