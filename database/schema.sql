CREATE DATABASE IF NOT EXISTS unifa_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE unifa_db;

-- Users Table
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

-- Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id      VARCHAR(255) NOT NULL UNIQUE,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(50)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Approve table
CREATE TABLE IF NOT EXISTS approve (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id       INT UNSIGNED NOT NULL,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(50)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applications Table
-- CREATE TABLE IF NOT EXISTS applications (
--     id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id         INT UNSIGNED NOT NULL,
--     category        VARCHAR(50)  NOT NULL,
--     subtype         VARCHAR(50)  NOT NULL,
--     amount_applied  DECIMAL(10,2) NULL,
--     bank_name       VARCHAR(255) NOT NULL,
--     bank_account    VARCHAR(255) NOT NULL,
--     status          VARCHAR(30)  NOT NULL DEFAULT 'pending',
--     created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;