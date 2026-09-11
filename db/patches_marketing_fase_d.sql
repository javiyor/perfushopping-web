-- Perfushopping - Fase D: Rutinas
-- Ejecutar en MySQL/MariaDB después de las fases anteriores

CREATE TABLE IF NOT EXISTS cms_routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    problem TEXT,
    expected_result TEXT,
    active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_routine_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    product_id INT NOT NULL,
    step_order INT DEFAULT 0,
    instructions TEXT,
    optional TINYINT DEFAULT 0,
    UNIQUE KEY (routine_id, product_id),
    INDEX (routine_id, step_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_routine_need (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    need_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (routine_id, need_id),
    INDEX (need_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_routine_topic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    topic_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (routine_id, topic_id),
    INDEX (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_routine_video (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    video_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (routine_id, video_id),
    INDEX (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_routine_article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    article_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (routine_id, article_id),
    INDEX (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
