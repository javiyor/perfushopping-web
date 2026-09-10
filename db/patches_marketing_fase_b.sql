-- Perfushopping - Fase B: Temas y Necesidades
-- Ejecutar en MySQL/MariaDB después de la Fase A

CREATE TABLE IF NOT EXISTS cms_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    image VARCHAR(255) DEFAULT NULL,
    cover VARCHAR(255) DEFAULT NULL,
    description_short TEXT,
    description_long TEXT,
    active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_topic_product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    product_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (topic_id, product_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_topic_video (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    video_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (topic_id, video_id),
    INDEX (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_topic_article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    article_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (topic_id, article_id),
    INDEX (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_topic_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    faq_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (topic_id, faq_id),
    INDEX (faq_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_needs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    icon VARCHAR(120) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    description_short TEXT,
    description_long TEXT,
    active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_description VARCHAR(500),
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_need_product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    product_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (need_id, product_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_need_video (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    video_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (need_id, video_id),
    INDEX (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_need_article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    article_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (need_id, article_id),
    INDEX (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_need_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    faq_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (need_id, faq_id),
    INDEX (faq_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_need_topic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    topic_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    UNIQUE KEY (need_id, topic_id),
    INDEX (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
