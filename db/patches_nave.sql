-- Registro de intenciones/pagos de Nave (checkout web).
CREATE TABLE IF NOT EXISTS nave_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  payment_request_id VARCHAR(190) DEFAULT NULL,
  return_token VARCHAR(64) DEFAULT NULL,
  webhook_secret VARCHAR(64) DEFAULT NULL,
  payment_id VARCHAR(190) DEFAULT NULL,
  payment_code VARCHAR(190) DEFAULT NULL,
  status VARCHAR(40) DEFAULT NULL,
  raw_json MEDIUMTEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_order (order_id),
  KEY idx_payment_request (payment_request_id),
  KEY idx_payment (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhooks recibidos de Nave (dedup por hash del body).
CREATE TABLE IF NOT EXISTS nave_webhook_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_key VARCHAR(64) NOT NULL,
  payment_request_id VARCHAR(190) DEFAULT NULL,
  payload MEDIUMTEXT,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event_key (event_key),
  KEY idx_pr (payment_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;