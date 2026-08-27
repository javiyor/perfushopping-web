-- Bandeja social (WhatsApp / Instagram / Facebook)

CREATE TABLE IF NOT EXISTS social_contacts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_conversations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_messages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_conversation_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  admin_user_id INT UNSIGNED NOT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_social_notes_conversation (conversation_id),
  KEY idx_social_notes_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_webhook_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_key CHAR(64) NOT NULL,
  payload MEDIUMTEXT NOT NULL,
  received_at DATETIME NOT NULL,
  UNIQUE KEY uq_social_webhook_events_key (event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
