-- Perfushopping - Fase G: Analytics y CRO (básico)
-- Ejecutar en MySQL/MariaDB después de las fases anteriores

CREATE TABLE IF NOT EXISTS cms_analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(60) NOT NULL,
    session_id VARCHAR(64) DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    path VARCHAR(255) DEFAULT NULL,
    product_id INT DEFAULT NULL,
    value DECIMAL(10,2) DEFAULT NULL,
    payload JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (event_type, created_at),
    INDEX (path, created_at),
    INDEX (product_id, event_type),
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_analytics_daily_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day DATE NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    event_count INT DEFAULT 0,
    unique_sessions INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    UNIQUE KEY (day, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
