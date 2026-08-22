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
        } catch (\Throwable $e) {
        }
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
        return ['id' => 1, 'modo' => 'auto', 'codrub' => null, 'codsub' => null];
    }

    public function saveConfig(string $modo, ?int $codrub, ?int $codsub): void
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
        $pdo = Db::pdo();
        $st = $pdo->prepare("
            INSERT INTO portada_config (id, modo, codrub, codsub)
            VALUES (1, :modo, :codrub, :codsub)
            ON DUPLICATE KEY UPDATE modo=VALUES(modo), codrub=VALUES(codrub), codsub=VALUES(codsub)
        ");
        $st->execute([
            ':modo' => $modo,
            ':codrub' => $codrub,
            ':codsub' => $codsub,
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
