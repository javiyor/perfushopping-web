-- Beneficios de portada configurables desde /admin/empresa (se muestran en el home).
ALTER TABLE empre ADD COLUMN benef1 VARCHAR(255) DEFAULT NULL AFTER web;
ALTER TABLE empre ADD COLUMN benef2 VARCHAR(255) DEFAULT NULL AFTER benef1;
ALTER TABLE empre ADD COLUMN benef3 VARCHAR(255) DEFAULT NULL AFTER benef2;