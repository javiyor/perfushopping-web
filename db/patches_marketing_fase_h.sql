-- Perfushopping - Fase H: Páginas de marca
-- Ejecutar en MySQL/MariaDB después de las fases anteriores

CREATE TABLE IF NOT EXISTS cms_brand_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    active TINYINT DEFAULT 1,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
