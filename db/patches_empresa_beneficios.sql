-- Beneficios de portada configurables desde /admin/empresa (se muestran en el home). Idempotente.
ALTER TABLE empre ADD COLUMN IF NOT EXISTS benef1 VARCHAR(255) DEFAULT NULL AFTER web;
ALTER TABLE empre ADD COLUMN IF NOT EXISTS benef2 VARCHAR(255) DEFAULT NULL AFTER benef1;
ALTER TABLE empre ADD COLUMN IF NOT EXISTS benef3 VARCHAR(255) DEFAULT NULL AFTER benef2;