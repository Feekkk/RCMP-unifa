CREATE DATABASE IF NOT EXISTS unifa_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE unifa_db;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(50)  NULL,
    address       TEXT         NULL,
    bank_name     VARCHAR(255) NULL,
    bank_account  VARCHAR(255) NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;