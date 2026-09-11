-- Perfushopping - Fase F: Campañas / Landings
-- Ejecutar en MySQL/MariaDB después de las fases anteriores

CREATE TABLE IF NOT EXISTS cms_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    active TINYINT DEFAULT 1,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
