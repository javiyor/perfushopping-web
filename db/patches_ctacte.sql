CREATE TABLE IF NOT EXISTS ctacte_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED DEFAULT NULL,
  idclien INT UNSIGNED DEFAULT NULL,
  tipo ENUM('debito','credito') NOT NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'ajuste',
  origen_id INT UNSIGNED DEFAULT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  saldo_after_cents INT NOT NULL DEFAULT 0,
  concepto VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ctacte_cliente (cliente_id),
  KEY idx_ctacte_fecha (created_at),
  KEY idx_ctacte_origen (origen, origen_id),
  CONSTRAINT fk_ctacte_cliente FOREIGN KEY (cliente_id) REFERENCES web_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ctacte_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
