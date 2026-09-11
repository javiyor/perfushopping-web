-- Perfushopping - Fase E: Recomendador / Quiz
-- Ejecutar en MySQL/MariaDB después de las fases anteriores

CREATE TABLE IF NOT EXISTS cms_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    INDEX (quiz_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_quiz_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    tags JSON,
    sort_order INT DEFAULT 0,
    INDEX (question_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_recommendation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    priority INT DEFAULT 0,
    conditions JSON,
    excluded_conditions JSON,
    target_type VARCHAR(40) NOT NULL,
    target_id INT NOT NULL,
    score INT DEFAULT 0,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    active TINYINT DEFAULT 1,
    INDEX (quiz_id, active, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
