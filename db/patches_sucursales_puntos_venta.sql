CREATE TABLE IF NOT EXISTS admin_sucursal_puntos_venta (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sucursal_id INT UNSIGNED NOT NULL,
  punto_venta INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_admin_sucursal_pv (punto_venta),
  KEY idx_admin_sucursal_pv_sucursal (sucursal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admin_sucursal_puntos_venta (sucursal_id, punto_venta, created_at)
SELECT s.id, s.punto_venta, NOW()
FROM admin_sucursales s
WHERE s.punto_venta IS NOT NULL AND s.punto_venta > 0;
