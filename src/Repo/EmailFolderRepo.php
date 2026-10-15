<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

/** Carpetas de email por interés/categoría de cliente + etiquetas por Message-ID. */
final class EmailFolderRepo
{
    private static bool $ready = false;

    private function ensure(): void
    {
        if (self::$ready) {
            return;
        }
        $pdo = Db::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_folders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(80) NOT NULL,
            orden INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_email_folders_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_labels (
            message_id VARCHAR(255) NOT NULL,
            folder_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_email_labels_message (message_id),
            KEY idx_email_labels_folder (folder_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $count = (int)$pdo->query("SELECT COUNT(*) FROM email_folders")->fetchColumn();
        if ($count === 0) {
            $seed = ['Mayoristas', 'Peluqueros', 'Esteticistas', 'Perfumerías'];
            $ins = $pdo->prepare("INSERT INTO email_folders (nombre, orden, created_at) VALUES (:n, :o, NOW())");
            $o = 1;
            foreach ($seed as $n) {
                try {
                    $ins->execute([':n' => $n, ':o' => $o++]);
                } catch (\Throwable $e) {
                }
            }
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_seen (
            message_id VARCHAR(255) NOT NULL,
            first_seen_at DATETIME NOT NULL,
            PRIMARY KEY (message_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_user_state (
            admin_user_id INT UNSIGNED NOT NULL,
            last_seen_at DATETIME NOT NULL,
            PRIMARY KEY (admin_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$ready = true;
    }

    /** @return array<int, array<string,mixed>> */
    public function folders(): array
    {
        $this->ensure();
        return Db::pdo()->query("SELECT * FROM email_folders ORDER BY orden ASC, id ASC")->fetchAll();
    }

    /** @return array<int,int> folder_id => cantidad */
    public function counts(): array
    {
        $this->ensure();
        $out = [];
        foreach (Db::pdo()->query("SELECT folder_id, COUNT(*) AS c FROM email_labels GROUP BY folder_id")->fetchAll() as $r) {
            $out[(int)$r['folder_id']] = (int)$r['c'];
        }
        return $out;
    }

    public function createFolder(string $nombre): bool
    {
        $this->ensure();
        $nombre = mb_substr(trim($nombre), 0, 80);
        if ($nombre === '') {
            return false;
        }
        try {
            $max = (int)Db::pdo()->query("SELECT COALESCE(MAX(orden),0)+1 FROM email_folders")->fetchColumn();
            $st = Db::pdo()->prepare("INSERT INTO email_folders (nombre, orden, created_at) VALUES (:n, :o, NOW())");
            $st->execute([':n' => $nombre, ':o' => $max]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function assign(string $messageId, int $folderId): bool
    {
        $this->ensure();
        $messageId = mb_substr(trim($messageId), 0, 255);
        if ($messageId === '' || $folderId <= 0) {
            return false;
        }
        try {
            $chk = Db::pdo()->prepare("SELECT id FROM email_folders WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $folderId]);
            if (!$chk->fetch()) {
                return false;
            }
            $st = Db::pdo()->prepare("INSERT INTO email_labels (message_id, folder_id, created_at)
                VALUES (:m, :f, NOW())
                ON DUPLICATE KEY UPDATE folder_id = VALUES(folder_id)");
            $st->execute([':m' => $messageId, ':f' => $folderId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function unassign(string $messageId): void
    {
        $this->ensure();
        $messageId = trim($messageId);
        if ($messageId === '') {
            return;
        }
        try {
            Db::pdo()->prepare("DELETE FROM email_labels WHERE message_id = :m LIMIT 1")->execute([':m' => $messageId]);
        } catch (\Throwable $e) {
        }
    }

    /** @return array<string,int> message_id => folder_id */
    public function labelsFor(array $messageIds): array
    {
        $this->ensure();
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => mb_substr(trim((string)$v), 0, 255), $messageIds))));
        if (!$ids) {
            return [];
        }
        $out = [];
        foreach (array_chunk($ids, 200) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $st = Db::pdo()->prepare("SELECT message_id, folder_id FROM email_labels WHERE message_id IN ({$ph})");
            $st->execute($chunk);
            foreach ($st->fetchAll() as $r) {
                $out[(string)$r['message_id']] = (int)$r['folder_id'];
            }
        }
        return $out;
    }

    /** Cliente ERP que coincide con el email remitente (para sugerir carpeta). */
    public function matchCliente(string $email): ?array
    {
        $this->ensure();
        $email = mb_strtolower(trim($email));
        if ($email === '' || !str_contains($email, '@')) {
            return null;
        }
        try {
            $cols = array_column(Db::pdo()->query('SHOW COLUMNS FROM clientes')->fetchAll(), 'Field');
            $sel = ['idclien', 'razon', 'mail'];
            foreach (['categoria', 'especialidad'] as $c) {
                if (in_array($c, $cols, true)) {
                    $sel[] = $c;
                }
            }
            $st = Db::pdo()->prepare('SELECT ' . implode(',', $sel) . ' FROM clientes WHERE LOWER(mail) = :e LIMIT 1');
            $st->execute([':e' => $email]);
            $row = $st->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Carpeta cuyo nombre coincide con la categoría/especialidad del cliente. */
    public function suggestFolder(?array $cliente): ?array
    {
        if (!$cliente) {
            return null;
        }
        $keys = [];
        foreach (['categoria', 'especialidad'] as $c) {
            $v = mb_strtolower(trim((string)($cliente[$c] ?? '')));
            if ($v !== '') {
                $keys[] = $v;
            }
        }
        if (!$keys) {
            return null;
        }
        foreach ($this->folders() as $f) {
            $nombre = mb_strtolower((string)($f['nombre'] ?? ''));
            foreach ($keys as $k) {
                if ($nombre !== '' && (str_contains($nombre, $k) || str_contains($k, $nombre))) {
                    return $f;
                }
            }
        }
        return null;
    }

    /** Registra Message-IDs ya vistos (primera vez = first_seen_at). */
    public function markSeen(array $messageIds): void
    {
        $this->ensure();
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => mb_substr(trim((string)$v), 0, 255), $messageIds))));
        if (!$ids) {
            return;
        }
        try {
            $pdo = Db::pdo();
            $ins = $pdo->prepare("INSERT IGNORE INTO email_seen (message_id, first_seen_at) VALUES (:m, NOW())");
            foreach ($ids as $mid) {
                $ins->execute([':m' => $mid]);
            }
        } catch (\Throwable $e) {
        }
    }

    public function touchSeen(int $adminId): void
    {
        $this->ensure();
        if ($adminId <= 0) {
            return;
        }
        try {
            $st = Db::pdo()->prepare("INSERT INTO email_user_state (admin_user_id, last_seen_at) VALUES (:a, NOW())
                ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at)");
            $st->execute([':a' => $adminId]);
        } catch (\Throwable $e) {
        }
    }

    public function lastSeen(int $adminId): ?string
    {
        $this->ensure();
        if ($adminId <= 0) {
            return null;
        }
        try {
            $st = Db::pdo()->prepare("SELECT last_seen_at FROM email_user_state WHERE admin_user_id = :a LIMIT 1");
            $st->execute([':a' => $adminId]);
            $v = $st->fetchColumn();
            return is_string($v) && $v !== '' ? $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Cantidad de mails descubiertos después de $since (0 si nunca visitó). */
    public function countNew(?string $since, int $limit = 99): int
    {
        $this->ensure();
        if ($since === null || $since === '') {
            return 0;
        }
        try {
            $limit = max(1, min(500, $limit));
            $st = Db::pdo()->prepare("SELECT COUNT(*) FROM (SELECT 1 FROM email_seen WHERE first_seen_at > :s LIMIT {$limit}) t");
            $st->execute([':s' => $since]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
