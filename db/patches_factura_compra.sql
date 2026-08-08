-- Facturas de compra (importadas de ARCA, manuales o por QR).
CREATE TABLE IF NOT EXISTS factura_compra (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  origen VARCHAR(10) NOT NULL DEFAULT 'manual',
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  fecha DATE DEFAULT NULL,
  tipo VARCHAR(30) DEFAULT NULL,
  punto_venta VARCHAR(10) DEFAULT NULL,
  numero_desde VARCHAR(20) DEFAULT NULL,
  numero_hasta VARCHAR(20) DEFAULT NULL,
  cod_autorizacion VARCHAR(40) DEFAULT NULL,
  cuit_proveedor VARCHAR(13) DEFAULT NULL,
  razon_proveedor VARCHAR(200) DEFAULT NULL,
  idprovee INT UNSIGNED DEFAULT NULL,
  moneda VARCHAR(10) DEFAULT 'PES',
  tipo_cambio DECIMAL(14,4) NOT NULL DEFAULT 1.0000,
  imp_neto_gravado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  imp_no_gravado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  imp_exento DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  otros_tributos DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  imp_iva DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  imp_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  idcta1 INT UNSIGNED DEFAULT NULL,
  iddepo INT UNSIGNED DEFAULT NULL,
  observaciones MEDIUMTEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_fecha (fecha),
  KEY idx_provee (idprovee),
  KEY idx_estado (estado),
  UNIQUE KEY uq_compra (cuit_proveedor, punto_venta, numero_desde, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle (ítems) de facturas de compra. Al confirmar actualiza stock y precios.
CREATE TABLE IF NOT EXISTS factura_compra_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  factura_compra_id BIGINT UNSIGNED NOT NULL,
  idprodu INT UNSIGNED NOT NULL,
  idcodgusto INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(200) DEFAULT NULL,
  qty DECIMAL(12,2) NOT NULL DEFAULT 1.00,
  unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_fc (factura_compra_id),
  CONSTRAINT fk_fc_items FOREIGN KEY (factura_compra_id) REFERENCES factura_compra(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
