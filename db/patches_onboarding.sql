-- Contador de vistas del manual de carga de productos (primeros 2 accesos). Idempotente.
ALTER TABLE admin_users
  ADD COLUMN IF NOT EXISTS onboarding_vistas INT UNSIGNED NOT NULL DEFAULT 0 AFTER activo;