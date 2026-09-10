-- Perfushopping - Fase A: Producto enriquecido + contenido base
-- Ejecutar en MySQL/MariaDB

CREATE TABLE IF NOT EXISTS cms_taxonomies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(60) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_taxonomy_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taxonomy_id INT NOT NULL,
    value VARCHAR(100) NOT NULL,
    label VARCHAR(120) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (taxonomy_id, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_product_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    benefit TEXT,
    ideal_for TEXT,
    problem TEXT,
    results TEXT,
    `usage` TEXT,
    advice TEXT,
    cta VARCHAR(255),
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_product_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    related_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (product_id, related_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_product_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    taxonomy_key VARCHAR(60) NOT NULL,
    term_value VARCHAR(100) NOT NULL,
    source VARCHAR(30) DEFAULT 'manual',
    ai_suggested TINYINT DEFAULT 0,
    verified TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (product_id, taxonomy_key, term_value),
    INDEX (product_id, taxonomy_key),
    INDEX (term_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_short TEXT,
    description_long TEXT,
    thumbnail VARCHAR(255),
    source VARCHAR(30) NOT NULL DEFAULT 'youtube',
    url VARCHAR(500) NOT NULL,
    duration INT DEFAULT NULL,
    instructor VARCHAR(120),
    active TINYINT DEFAULT 1,
    featured TINYINT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_video_product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    product_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (video_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    active TINYINT DEFAULT 1,
    featured TINYINT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE cms_articles ADD COLUMN IF NOT EXISTS featured TINYINT DEFAULT 0;

CREATE TABLE IF NOT EXISTS cms_article_product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    product_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (article_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_product_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    faq_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (product_id, faq_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_seo_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(40) NOT NULL,
    entity_id INT NOT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    meta_description VARCHAR(500),
    og_image VARCHAR(255),
    canonical VARCHAR(500),
    indexable TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (entity_type, entity_id),
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed taxonomías iniciales
INSERT INTO cms_taxonomies (`key`, label, sort_order, active) VALUES
('need', 'Necesidad', 1, 1),
('hair_type', 'Tipo de cabello', 2, 1),
('goal', 'Objetivo', 3, 1),
('routine_step', 'Paso de rutina', 4, 1),
('usage', 'Uso', 5, 1)
ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Necesidad
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'cabello_danado', 'Cabello dañado', 1, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'sequedad', 'Sequedad', 2, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'frizz', 'Frizz', 3, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'color', 'Color', 4, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'caida', 'Caída', 5, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='need'), 'brillo', 'Brillo', 6, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Tipo de cabello
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'liso', 'Liso', 1, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'ondulado', 'Ondulado', 2, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'rizado', 'Rizado', 3, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'afro', 'Afro', 4, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'fino', 'Fino', 5, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='hair_type'), 'grueso', 'Grueso', 6, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Objetivo
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='goal'), 'reparar', 'Reparar', 1, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='goal'), 'hidratar', 'Hidratar', 2, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='goal'), 'proteger', 'Proteger', 3, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='goal'), 'definir_rizos', 'Definir rizos', 4, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='goal'), 'volumen', 'Volumen', 5, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Paso de rutina
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'limpiar', 'Limpiar', 1, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'acondicionar', 'Acondicionar', 2, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'tratar', 'Tratar', 3, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'mascarilla', 'Mascarilla', 4, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'proteger', 'Proteger', 5, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='routine_step'), 'finalizar', 'Finalizar', 6, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Uso
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='usage'), 'profesional', 'Profesional', 1, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='usage'), 'hogar', 'Hogar', 2, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='usage'), 'frecuente', 'Frecuente', 3, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
INSERT INTO cms_taxonomy_terms (taxonomy_id, value, label, sort_order, active) VALUES ((SELECT id FROM cms_taxonomies WHERE `key`='usage'), 'ocasional', 'Ocasional', 4, 1) ON DUPLICATE KEY UPDATE label=VALUES(label);
