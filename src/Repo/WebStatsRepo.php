<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class WebStatsRepo
{
    private ?bool $hasWebVisitasTable = null;
    private ?bool $hasProductoVisitasTable = null;

    private function hasWebVisitasTable(): bool
    {
        if ($this->hasWebVisitasTable !== null) {
            return $this->hasWebVisitasTable;
        }
        try {
            $pdo = Db::pdo();
            $st = $pdo->query("SHOW TABLES LIKE 'web_visitas'");
            return $this->hasWebVisitasTable = ($st->fetchColumn() !== false);
        } catch (\Throwable $e) {
            return $this->hasWebVisitasTable = false;
        }
    }

    private function hasProductoVisitasTable(): bool
    {
        if ($this->hasProductoVisitasTable !== null) {
            return $this->hasProductoVisitasTable;
        }
        try {
            $pdo = Db::pdo();
            $st = $pdo->query("SHOW TABLES LIKE 'producto_visitas'");
            return $this->hasProductoVisitasTable = ($st->fetchColumn() !== false);
        } catch (\Throwable $e) {
            return $this->hasProductoVisitasTable = false;
        }
    }

    /** Log a page visit. Returns false if the request should be ignored (bot/health/etc). */
    public function registrarVisita(string $url, ?int $idprodu, ?int $userId, string $sessionKey, string $ip): bool
    {
        if ($url === '' || $this->esBot()) {
            return false;
        }
        if (!$this->hasWebVisitasTable()) {
            return false;
        }
        $pdo = Db::pdo();
        $st = $pdo->prepare('
            INSERT INTO web_visitas (url, idprodu, user_id, session_key, ip, created_at)
            VALUES (:u, :p, :uid, :s, :ip, NOW())
        ');
        $st->execute([
            ':u' => mb_substr($url, 0, 255),
            ':p' => $idprodu > 0 ? $idprodu : null,
            ':uid' => $userId > 0 ? $userId : null,
            ':s' => mb_substr($sessionKey, 0, 64) !== '' ? mb_substr($sessionKey, 0, 64) : null,
            ':ip' => mb_substr($ip, 0, 45) !== '' ? mb_substr($ip, 0, 45) : null,
        ]);
        return true;
    }

    public function incrementarProducto(int $idprodu): void
    {
        if ($idprodu <= 0 || !$this->hasProductoVisitasTable()) {
            return;
        }
        $st = Db::pdo()->prepare('
            INSERT INTO producto_visitas (idprodu, fecha, vistas)
            VALUES (:p, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE vistas = vistas + 1
        ');
        $st->execute([':p' => $idprodu]);
    }

    public function visitasHoy(): int
    {
        if (!$this->hasWebVisitasTable()) {
            return 0;
        }
        $st = Db::pdo()->query("SELECT COUNT(*) FROM web_visitas WHERE DATE(created_at) = CURDATE()");
        return (int)$st->fetchColumn();
    }

    public function visitasEnDias(int $dias): int
    {
        if (!$this->hasWebVisitasTable()) {
            return 0;
        }
        $dias = max(1, (int)$dias);
        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM web_visitas WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)');
        $st->execute([':d' => $dias]);
        return (int)$st->fetchColumn();
    }

    public function visitantesUnicosHoy(): int
    {
        if (!$this->hasWebVisitasTable()) {
            return 0;
        }
        $st = Db::pdo()->query("SELECT COUNT(DISTINCT session_key) FROM web_visitas WHERE DATE(created_at) = CURDATE() AND session_key IS NOT NULL");
        return (int)$st->fetchColumn();
    }

    public function visitantesUnicosEnDias(int $dias): int
    {
        if (!$this->hasWebVisitasTable()) {
            return 0;
        }
        $dias = max(1, (int)$dias);
        $st = Db::pdo()->prepare('SELECT COUNT(DISTINCT session_key) FROM web_visitas WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY) AND session_key IS NOT NULL');
        $st->execute([':d' => $dias]);
        return (int)$st->fetchColumn();
    }

    /** @return array<int, array<string,mixed>> */
    public function topProductos(int $limit = 5, int $dias = 30): array
    {
        if (!$this->hasProductoVisitasTable()) {
            return [];
        }
        $limit = max(1, min(20, (int)$limit));
        $dias = max(1, (int)$dias);
        $st = Db::pdo()->prepare("
            SELECT pv.idprodu, p.produ, p.precio, p.precio1, p.imagen, SUM(pv.vistas) AS vistas
            FROM producto_visitas pv
            INNER JOIN producto p ON p.idprodu = pv.idprodu
            WHERE pv.fecha >= DATE_SUB(CURDATE(), INTERVAL :d DAY)
            GROUP BY pv.idprodu, p.produ, p.precio, p.precio1, p.imagen
            ORDER BY vistas DESC
            LIMIT :l
        ");
        $st->bindValue(':d', $dias, \PDO::PARAM_INT);
        $st->bindValue(':l', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    private function esBot(): bool
    {
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '') {
            return true;
        }
        return preg_match('/bot|crawl|spider|slurp|bingpreview|monitoring|uptime|curl|wget|python-requests|facebookexternalhit|whatsapp/i', $ua) === 1;
    }
}
