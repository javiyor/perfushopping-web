-- Extensión de órdenes de compra: recepción, fletes y cta cte proveedores

ALTER TABLE ordenes_compra
  ADD COLUMN fecha_recepcion DATE DEFAULT NULL AFTER fecha_estimada,
  ADD COLUMN bultos_recibidos INT DEFAULT NULL AFTER fecha_recepcion,
  ADD COLUMN controlado_por INT UNSIGNED DEFAULT NULL AFTER bultos_recibidos,
  ADD COLUMN valor_declarado_cents INT NOT NULL DEFAULT 0 AFTER total_cents,
  ADD COLUMN flete_cents INT NOT NULL DEFAULT 0 AFTER valor_declarado_cents,
  ADD COLUMN flete_pagado TINYINT(1) NOT NULL DEFAULT 0 AFTER flete_cents,
  ADD COLUMN flete_comprobante VARCHAR(255) DEFAULT NULL AFTER flete_pagado,
  ADD KEY idx_oc_controlado (controlado_por);

-- Tabla de cuenta corriente de proveedores
CREATE TABLE IF NOT EXISTS ctacte_proveedor_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proveedor_id INT UNSIGNED DEFAULT NULL,
  proveedor_nombre VARCHAR(200) DEFAULT NULL,
  tipo ENUM('debito','credito') NOT NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'ajuste',
  origen_id INT UNSIGNED DEFAULT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  saldo_after_cents INT NOT NULL DEFAULT 0,
  concepto VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ctacte_prov_proveedor (proveedor_id),
  KEY idx_ctacte_prov_origen (origen, origen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
