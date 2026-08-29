ALTER TABLE facturas
  ADD COLUMN entrega_tipo ENUM('local','envio') NOT NULL DEFAULT 'local' AFTER forma_pago,
  ADD COLUMN transporte ENUM('propio','delivery','correo_argentino') DEFAULT NULL AFTER entrega_tipo,
  ADD COLUMN envio_estado ENUM('pendiente','en_transito','entregado','cancelado') DEFAULT NULL AFTER transporte,
  ADD COLUMN envio_direccion VARCHAR(255) DEFAULT NULL AFTER envio_estado,
  ADD COLUMN envio_observacion TEXT DEFAULT NULL AFTER envio_direccion;

ALTER TABLE facturas
  ADD KEY idx_facturas_entrega (entrega_tipo),
  ADD KEY idx_facturas_envio_estado (envio_estado);
