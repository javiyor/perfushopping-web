-- Agrupa los movimientos generados en una misma registración de ajuste
ALTER TABLE stockcab ADD COLUMN grupo_id INT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_grupo_id (grupo_id);
