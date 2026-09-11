-- Perfushopping - Fase C: Page Builder / Home administrable
-- Ejecutar en MySQL/MariaDB después de las Fases A y B

CREATE TABLE IF NOT EXISTS cms_page_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_type VARCHAR(40) NOT NULL DEFAULT 'home',
    page_id INT DEFAULT 0,
    block_type VARCHAR(60) NOT NULL,
    position INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    settings JSON,
    content TEXT,
    target_entity_type VARCHAR(40) DEFAULT NULL,
    target_entity_id INT DEFAULT NULL,
    segment VARCHAR(60) DEFAULT NULL,
    tracking_data VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (page_type, page_id, active, position),
    INDEX (block_type, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
