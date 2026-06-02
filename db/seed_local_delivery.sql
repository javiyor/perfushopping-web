-- Seed local delivery whitelist and default prices

-- Santa Fe codprov = 3

INSERT INTO local_delivery_localities (locality_key, display_name, province_codprov, active) VALUES
('reconquista','Reconquista',3,1),
('avellaneda','Avellaneda',3,1),
('guadalupe-norte','Guadalupe Norte',3,1),
('fortin-olmos','Fortin Olmos',3,1),
('malabrigo','Malabrigo',3,1),
('vera','Vera',3,1),
('romang','Romang',3,1),
('villa-ocampo','Villa Ocampo',3,1),
('florencia','Florencia',3,1),
('calchaqui','Calchaqui',3,1),
('margarita','Margarita',3,1),
('alejandra','Alejandra',3,1),
('lanteri','Lanteri',3,1),
('villa-ana','Villa Ana',3,1),
('tartagal','Tartagal',3,1),
('arroyo-ceibal','Arroyo Ceibal',3,1),
('el-sombrerito','El Sombrerito',3,1),
('las-toscas','Las Toscas',3,1),
('san-javier','San Javier',3,1),
('los-laureles','Los Laureles',3,1),
('intiyaco','Intiyaco',3,1),
('golondrina','Golondrina',3,1)
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), active=VALUES(active);

-- Prices: Reconquista base $1500, everyone else $3000. Reconquista "zona alejada" handled as checkout option.
INSERT INTO local_delivery_prices (locality_key, price_cents) VALUES
('reconquista',150000),
('avellaneda',300000),
('guadalupe-norte',300000),
('fortin-olmos',300000),
('malabrigo',300000),
('vera',300000),
('romang',300000),
('villa-ocampo',300000),
('florencia',300000),
('calchaqui',300000),
('margarita',300000),
('alejandra',300000),
('lanteri',300000),
('villa-ana',300000),
('tartagal',300000),
('arroyo-ceibal',300000),
('el-sombrerito',300000),
('las-toscas',300000),
('san-javier',300000),
('los-laureles',300000),
('intiyaco',300000),
('golondrina',300000)
ON DUPLICATE KEY UPDATE price_cents=VALUES(price_cents);
