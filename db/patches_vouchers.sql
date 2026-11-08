-- Vouchers de regalo. FormaPagoRepo también crea esta tabla y la forma de pago al primer uso.
CREATE TABLE IF NOT EXISTS voucher_regalos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(24) NOT NULL,
  importe_cents INT UNSIGNED NOT NULL,
  destinatario_nombre VARCHAR(160) NOT NULL,
  destinatario_dni VARCHAR(30) NOT NULL,
  destinatario_telefono VARCHAR(40) NOT NULL,
  remitente_nombre VARCHAR(160) NOT NULL,
  remitente_telefono VARCHAR(40) NOT NULL,
  forma_pago VARCHAR(40) NOT NULL,
  banco_cuenta_id INT UNSIGNED DEFAULT NULL,
  sucursal_id INT UNSIGNED DEFAULT NULL,
  estado ENUM('activo','canjeado','anulado') NOT NULL DEFAULT 'activo',
  factura_id INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  cobrado_at DATETIME DEFAULT NULL,
  canjeado_at DATETIME DEFAULT NULL,
  UNIQUE KEY uq_voucher_regalos_codigo (codigo),
  KEY idx_voucher_regalos_estado (estado),
  KEY idx_voucher_regalos_factura (factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO formas_pago (codigo, nombre, tipo, activo, orden, created_at, updated_at)
VALUES ('voucher_regalo', 'Voucher regalo', 'voucher', 1, 9, NOW(), NOW());

CREATE TABLE IF NOT EXISTS voucher_regalo_diseno (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  modelo_imagen VARCHAR(255) DEFAULT NULL,
  titulo VARCHAR(80) NOT NULL,
  cinta VARCHAR(80) NOT NULL,
  mensaje TEXT NOT NULL,
  frase_inferior VARCHAR(160) NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO voucher_regalo_diseno (id, modelo_imagen, titulo, cinta, mensaje, frase_inferior, updated_at)
VALUES (1, '/assets/brand/voucher-model.png', '¡FELICITACIONES!', 'GIFT VOUCHER', 'Qué alegría compartir este regalo con vos.\n\nGracias por ser parte de este momento.\n\nDeseamos que este regalo te acompañe en recuerdos hermosos.', '¡Te esperamos con mucho cariño!', NOW());
