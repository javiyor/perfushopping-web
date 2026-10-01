-- Asignación de ordenes de pago a facturas de compra (para saber cuáles se pagaron)
CREATE TABLE IF NOT EXISTS orden_pago_compras (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_pago_id INT UNSIGNED NOT NULL,
  factura_compra_id INT UNSIGNED NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_opc_op (orden_pago_id),
  KEY idx_opc_compra (factura_compra_id),
  CONSTRAINT fk_opc_op FOREIGN KEY (orden_pago_id) REFERENCES ordenes_pago(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
