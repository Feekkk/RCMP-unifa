CREATE DATABASE IF NOT EXISTS unifa_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE unifa_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    course        VARCHAR(255) NULL,
    year          VARCHAR(50)  NULL,
    phone         VARCHAR(50)  NULL,
    address       TEXT         NULL,
    bank_name     VARCHAR(255) NULL,
    bank_account  VARCHAR(255) NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff table (admin=1, committee=2)
CREATE TABLE IF NOT EXISTS staff (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id      VARCHAR(255) NOT NULL UNIQUE,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(50)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=admin, 2=committee',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Status table (application status flow: submit -> under_review -> pending -> approved; or reject)
CREATE TABLE IF NOT EXISTS status (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50)  NOT NULL UNIQUE,
    display_order   TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO status (name, display_order) VALUES
('pending', 1),
('under_review', 2),
('approved', 3),
('rejected', 4),
('disbursed', 5)
ON DUPLICATE KEY UPDATE display_order = VALUES(display_order);

-- Applications table (sparse columns for category-specific data)
CREATE TABLE IF NOT EXISTS applications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    category        VARCHAR(50)  NOT NULL,
    subtype         VARCHAR(50)  NOT NULL,
    amount_applied  DECIMAL(10,2) NULL,
    bank_name       VARCHAR(255) NOT NULL,
    bank_account    VARCHAR(255) NOT NULL,
    status_id       INT UNSIGNED NOT NULL DEFAULT 1,
    clinic_name     VARCHAR(255) NULL COMMENT 'outpatient',
    reason_visit    VARCHAR(255) NULL COMMENT 'outpatient, inpatient',
    visit_datetime  DATETIME     NULL COMMENT 'outpatient',
    checkin_date    DATE         NULL COMMENT 'inpatient',
    checkout_date   DATE         NULL COMMENT 'inpatient',
    case_description TEXT        NULL COMMENT 'emergency natural/others',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Document table (file paths for application uploads)
CREATE TABLE IF NOT EXISTS document (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    document_type   VARCHAR(100) NOT NULL COMMENT 'e.g. death_certificate, receipt_clinic, supporting_doc',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Application status history (audit trail: who changed status, when)
CREATE TABLE IF NOT EXISTS application_history (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    from_status_id  INT UNSIGNED NULL,
    to_status_id    INT UNSIGNED NOT NULL,
    staff_id        INT UNSIGNED NULL,
    action          VARCHAR(50)  NULL COMMENT 'submit,review,approve,reject,disburse',
    notes           TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (from_status_id) REFERENCES status(id) ON DELETE SET NULL,
    FOREIGN KEY (to_status_id) REFERENCES status(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;