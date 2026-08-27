<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class SocialInboxRepo
{
    private static bool $tablesReady = false;

    private function ensureTables(): void
    {
        if (self::$tablesReady) {
            return;
        }

        $pdo = Db::pdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS social_contacts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel VARCHAR(20) NOT NULL,
            external_id VARCHAR(120) NOT NULL,
            display_name VARCHAR(191) DEFAULT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            web_user_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_social_contacts_channel_external (channel, external_id),
            KEY idx_social_contacts_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS social_conversations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel VARCHAR(20) NOT NULL,
            contact_id INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'nuevo',
            assigned_admin_id INT UNSIGNED DEFAULT NULL,
            priority TINYINT UNSIGNED NOT NULL DEFAULT 0,
            unread_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_message_at DATETIME DEFAULT NULL,
            last_message_preview VARCHAR(255) DEFAULT NULL,
            internal_note TEXT DEFAULT NULL,
            claimed_at DATETIME DEFAULT NULL,
            first_response_at DATETIME DEFAULT NULL,
            closed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_social_conversations_status (status),
            KEY idx_social_conversations_assigned (assigned_admin_id),
            KEY idx_social_conversations_last_message_at (last_message_at),
            KEY idx_social_conversations_contact (contact_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS social_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            direction VARCHAR(10) NOT NULL,
            message_type VARCHAR(32) DEFAULT NULL,
            meta_message_id VARCHAR(191) DEFAULT NULL,
            body TEXT DEFAULT NULL,
            attachments_json MEDIUMTEXT DEFAULT NULL,
            raw_json MEDIUMTEXT DEFAULT NULL,
            provider_created_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_social_messages_meta (meta_message_id),
            KEY idx_social_messages_conversation (conversation_id),
            KEY idx_social_messages_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS social_conversation_notes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT UNSIGNED NOT NULL,
            admin_user_id INT UNSIGNED NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_social_notes_conversation (conversation_id),
            KEY idx_social_notes_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS social_webhook_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_key CHAR(64) NOT NULL,
            payload MEDIUMTEXT NOT NULL,
            received_at DATETIME NOT NULL,
            UNIQUE KEY uq_social_webhook_events_key (event_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::$tablesReady = true;
    }

    public function storeWebhookEvent(string $eventKey, string $payload): bool
    {
        $this->ensureTables();
        try {
            $st = Db::pdo()->prepare('INSERT INTO social_webhook_events (event_key, payload, received_at) VALUES (:k, :p, NOW())');
            $st->execute([':k' => $eventKey, ':p' => $payload]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function ingestInbound(array $m): void
    {
        $this->ensureTables();
        $pdo = Db::pdo();

        $channel = trim((string)($m['channel'] ?? ''));
        $externalId = trim((string)($m['external_id'] ?? ''));
        if ($channel === '' || $externalId === '') {
            return;
        }

        $displayName = trim((string)($m['display_name'] ?? ''));
        $phone = trim((string)($m['phone'] ?? ''));
        $messageId = trim((string)($m['message_id'] ?? ''));
        $body = trim((string)($m['body'] ?? ''));
        $type = trim((string)($m['message_type'] ?? 'text'));
        $attachmentsJson = $m['attachments_json'] ?? null;
        $rawJson = $m['raw_json'] ?? null;
        $providerAt = $m['provider_created_at'] ?? null;

        $contactId = $this->upsertContact($channel, $externalId, $displayName, $phone);

        $conversation = $this->findLastConversationByContact($channel, $contactId);
        if (!$conversation) {
            $conversationId = $this->createConversation($channel, $contactId, $body);
        } else {
            $conversationId = (int)$conversation['id'];
            if (($conversation['status'] ?? '') === 'cerrado') {
                $st = $pdo->prepare('UPDATE social_conversations SET status = :s, assigned_admin_id = NULL, closed_at = NULL, updated_at = NOW() WHERE id = :id LIMIT 1');
                $st->execute([':s' => 'nuevo', ':id' => $conversationId]);
            }
        }

        if ($messageId !== '') {
            $check = $pdo->prepare('SELECT id FROM social_messages WHERE meta_message_id = :mid LIMIT 1');
            $check->execute([':mid' => $messageId]);
            if ($check->fetchColumn() !== false) {
                return;
            }
        }

        $ins = $pdo->prepare('INSERT INTO social_messages (conversation_id, direction, message_type, meta_message_id, body, attachments_json, raw_json, provider_created_at, created_at)
            VALUES (:c, :d, :t, :mid, :b, :a, :r, :pa, NOW())');
        $ins->execute([
            ':c' => $conversationId,
            ':d' => 'in',
            ':t' => $type !== '' ? $type : 'text',
            ':mid' => $messageId !== '' ? $messageId : null,
            ':b' => $body !== '' ? $body : null,
            ':a' => is_string($attachmentsJson) && $attachmentsJson !== '' ? $attachmentsJson : null,
            ':r' => is_string($rawJson) && $rawJson !== '' ? $rawJson : null,
            ':pa' => is_string($providerAt) && $providerAt !== '' ? $providerAt : null,
        ]);

        $preview = mb_substr($body !== '' ? $body : ('[' . ($type !== '' ? $type : 'mensaje') . ']'), 0, 255);
        $upd = $pdo->prepare('UPDATE social_conversations
            SET unread_count = unread_count + 1,
                last_message_at = NOW(),
                last_message_preview = :p,
                updated_at = NOW()
            WHERE id = :id LIMIT 1');
        $upd->execute([':p' => $preview, ':id' => $conversationId]);
    }

    private function upsertContact(string $channel, string $externalId, string $displayName, string $phone): int
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT id FROM social_contacts WHERE channel = :c AND external_id = :e LIMIT 1');
        $st->execute([':c' => $channel, ':e' => $externalId]);
        $id = $st->fetchColumn();
        if ($id !== false) {
            $upd = $pdo->prepare("UPDATE social_contacts SET display_name = COALESCE(NULLIF(:n, ''), display_name), phone = COALESCE(NULLIF(:p, ''), phone), updated_at = NOW() WHERE id = :id LIMIT 1");
            $upd->execute([':n' => $displayName, ':p' => $phone, ':id' => (int)$id]);
            return (int)$id;
        }

        $ins = $pdo->prepare('INSERT INTO social_contacts (channel, external_id, display_name, phone, created_at, updated_at)
            VALUES (:c, :e, :n, :p, NOW(), NOW())');
        $ins->execute([
            ':c' => $channel,
            ':e' => $externalId,
            ':n' => $displayName !== '' ? $displayName : null,
            ':p' => $phone !== '' ? $phone : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    private function findLastConversationByContact(string $channel, int $contactId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM social_conversations WHERE channel = :c AND contact_id = :cid ORDER BY id DESC LIMIT 1');
        $st->execute([':c' => $channel, ':cid' => $contactId]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    private function createConversation(string $channel, int $contactId, string $firstBody): int
    {
        $preview = mb_substr($firstBody, 0, 255);
        $st = Db::pdo()->prepare('INSERT INTO social_conversations
            (channel, contact_id, status, unread_count, last_message_at, last_message_preview, created_at, updated_at)
            VALUES (:c, :cid, :s, 0, NOW(), :p, NOW(), NOW())');
        $st->execute([
            ':c' => $channel,
            ':cid' => $contactId,
            ':s' => 'nuevo',
            ':p' => $preview !== '' ? $preview : null,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function listConversations(string $tab, int $adminId, string $q = ''): array
    {
        $this->ensureTables();
        $where = [];
        $params = [];

        if ($tab === 'mine') {
            $where[] = 'c.assigned_admin_id = :me AND c.status <> :cerrado';
            $params[':me'] = $adminId;
            $params[':cerrado'] = 'cerrado';
        } elseif ($tab === 'closed') {
            $where[] = 'c.status = :cerrado';
            $params[':cerrado'] = 'cerrado';
        } else {
            $where[] = 'c.assigned_admin_id IS NULL AND c.status <> :cerrado';
            $params[':cerrado'] = 'cerrado';
        }

        if ($q !== '') {
            $where[] = '(ct.display_name LIKE :q OR ct.phone LIKE :q OR ct.external_id LIKE :q OR c.last_message_preview LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = 'SELECT c.*, ct.display_name, ct.phone, ct.external_id, au.nombre AS assigned_admin_nombre
            FROM social_conversations c
            INNER JOIN social_contacts ct ON ct.id = c.contact_id
            LEFT JOIN admin_users au ON au.id = c.assigned_admin_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.last_message_at IS NULL ASC, c.last_message_at ASC, c.id ASC LIMIT 200';

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getConversation(int $id): ?array
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('SELECT c.*, ct.display_name, ct.phone, ct.external_id, au.nombre AS assigned_admin_nombre
            FROM social_conversations c
            INNER JOIN social_contacts ct ON ct.id = c.contact_id
            LEFT JOIN admin_users au ON au.id = c.assigned_admin_id
            WHERE c.id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function listMessages(int $conversationId): array
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('SELECT * FROM social_messages WHERE conversation_id = :id ORDER BY id ASC LIMIT 500');
        $st->execute([':id' => $conversationId]);
        return $st->fetchAll();
    }

    public function listNotes(int $conversationId): array
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('SELECT n.*, au.nombre AS admin_nombre
            FROM social_conversation_notes n
            LEFT JOIN admin_users au ON au.id = n.admin_user_id
            WHERE n.conversation_id = :id
            ORDER BY n.id DESC LIMIT 100');
        $st->execute([':id' => $conversationId]);
        return $st->fetchAll();
    }

    public function markRead(int $conversationId): void
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('UPDATE social_conversations SET unread_count = 0, updated_at = NOW() WHERE id = :id LIMIT 1');
        $st->execute([':id' => $conversationId]);
    }

    public function takeConversation(int $conversationId, int $adminId): bool
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('UPDATE social_conversations
            SET assigned_admin_id = :a,
                status = :s,
                claimed_at = COALESCE(claimed_at, NOW()),
                updated_at = NOW()
            WHERE id = :id AND assigned_admin_id IS NULL AND status <> :cerrado
            LIMIT 1');
        $st->execute([
            ':a' => $adminId,
            ':s' => 'en_gestion',
            ':id' => $conversationId,
            ':cerrado' => 'cerrado',
        ]);
        return $st->rowCount() > 0;
    }

    public function releaseConversation(int $conversationId): void
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('UPDATE social_conversations
            SET assigned_admin_id = NULL,
                status = :s,
                updated_at = NOW()
            WHERE id = :id AND status <> :cerrado
            LIMIT 1');
        $st->execute([':s' => 'nuevo', ':id' => $conversationId, ':cerrado' => 'cerrado']);
    }

    public function closeConversation(int $conversationId): void
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('UPDATE social_conversations
            SET status = :s,
                closed_at = NOW(),
                unread_count = 0,
                updated_at = NOW()
            WHERE id = :id LIMIT 1');
        $st->execute([':s' => 'cerrado', ':id' => $conversationId]);
    }

    public function reopenConversation(int $conversationId): void
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('UPDATE social_conversations
            SET status = :s,
                assigned_admin_id = NULL,
                closed_at = NULL,
                updated_at = NOW()
            WHERE id = :id LIMIT 1');
        $st->execute([':s' => 'nuevo', ':id' => $conversationId]);
    }

    public function addNote(int $conversationId, int $adminId, string $note): void
    {
        $this->ensureTables();
        $st = Db::pdo()->prepare('INSERT INTO social_conversation_notes (conversation_id, admin_user_id, note, created_at)
            VALUES (:c, :a, :n, NOW())');
        $st->execute([':c' => $conversationId, ':a' => $adminId, ':n' => $note]);

        $upd = Db::pdo()->prepare('UPDATE social_conversations SET internal_note = :n, updated_at = NOW() WHERE id = :id LIMIT 1');
        $upd->execute([':n' => mb_substr($note, 0, 5000), ':id' => $conversationId]);
    }
}
