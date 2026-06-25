-- AI Article Toolkit - MySQL schema
-- Run with: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS article_toolkit
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE article_toolkit;

CREATE TABLE IF NOT EXISTS analyses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_excerpt VARCHAR(500) NOT NULL,
    word_count INT UNSIGNED NOT NULL,
    headlines JSON NOT NULL,
    summary TEXT NOT NULL,
    meta_description VARCHAR(500) NOT NULL,
    flesch_kincaid_grade DECIMAL(5,2) NOT NULL,
    readability_band VARCHAR(64) NOT NULL,
    reading_time_minutes INT UNSIGNED NOT NULL,
    demo_mode TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
