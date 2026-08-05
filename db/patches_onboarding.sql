ALTER TABLE admin_users
  ADD COLUMN onboarding_vistas INT UNSIGNED NOT NULL DEFAULT 0 AFTER activo;