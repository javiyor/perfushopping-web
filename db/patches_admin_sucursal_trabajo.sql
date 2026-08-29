ALTER TABLE admin_users
  ADD COLUMN sucursal_trabajo_id INT UNSIGNED DEFAULT NULL AFTER email,
  ADD KEY idx_admin_users_sucursal_trabajo (sucursal_trabajo_id);
