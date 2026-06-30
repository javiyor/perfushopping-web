CREATE TABLE IF NOT EXISTS cliente_notas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  admin_user_id INT UNSIGNED DEFAULT NULL,
  nota TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_cliente_notas_user (user_id),
  CONSTRAINT fk_cliente_notas_user FOREIGN KEY (user_id) REFERENCES web_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_notas_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
