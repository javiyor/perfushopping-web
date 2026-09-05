-- Factura pagos: agregar soporte para tarjetas y equipotar, y banco para transferencias
ALTER TABLE factura_pagos ADD COLUMN IF NOT EXISTS tarjeta_id INT UNSIGNED NULL AFTER banco_id;
ALTER TABLE factura_pagos ADD COLUMN IF NOT EXISTS equipo_id INT UNSIGNED NULL AFTER tarjeta_id;
ALTER TABLE factura_pagos ADD COLUMN IF NOT EXISTS banco_cuenta_id INT UNSIGNED NULL AFTER equipo_id;
-- Para compatibilidad, si la tabla factura_pagos no tenía id (algunas instalaciones), asegurar id
-- No tocamos forma_pago enum, se maneja a nivel aplicación (tarjeta unificada)
