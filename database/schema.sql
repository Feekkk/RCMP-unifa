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

-- Staff table (admin=1, committee=2, ceo=3)
CREATE TABLE IF NOT EXISTS staff (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id      VARCHAR(255) NOT NULL UNIQUE,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(50)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=admin, 2=committee, 3=ceo',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Status table
-- Flow: pending -> under_review (admin recommend)
--       -> committee_approved (committee approve) -> approved (CEO approve)
--       -> disbursed (admin upload receipt)
-- Reject: under_review or committee_approved -> rejected
CREATE TABLE IF NOT EXISTS status (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50)  NOT NULL UNIQUE,
    display_order   TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO status (name, display_order) VALUES
('pending', 1),
('under_review', 2),
('approved', 4),
('rejected', 5),
('disbursed', 6),
('committee_approved', 3)
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
    receipt_path    VARCHAR(500) NULL,
    receipt_uploaded_at DATETIME NULL,
    receipt_uploaded_by INT UNSIGNED NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES status(id),
    FOREIGN KEY (receipt_uploaded_by) REFERENCES staff(id) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS notification (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    application_id  INT UNSIGNED NULL,
    type            VARCHAR(50)  NOT NULL COMMENT 'status_change, receipt_uploaded',
    title           VARCHAR(255) NOT NULL,
    message         TEXT         NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TRIGGER IF EXISTS tr_application_history_notify;
DELIMITER //
CREATE TRIGGER tr_application_history_notify
AFTER INSERT ON application_history
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT UNSIGNED;
    DECLARE v_title VARCHAR(255);
    DECLARE v_message TEXT;

    SELECT user_id INTO v_user_id FROM applications WHERE id = NEW.application_id LIMIT 1;

    SET v_title = CASE NEW.action
        WHEN 'submit' THEN 'Application submitted'
        WHEN 'recommend' THEN 'Application under review'
        WHEN 'approve' THEN 'Committee approved'
        WHEN 'ceo_approve' THEN 'Application approved'
        WHEN 'reject' THEN 'Application rejected'
        WHEN 'disburse' THEN 'Receipt available'
        ELSE CONCAT('Application #', NEW.application_id, ' updated')
    END;

    SET v_message = CASE NEW.action
        WHEN 'submit' THEN CONCAT('Your application #', NEW.application_id, ' has been submitted successfully.')
        WHEN 'recommend' THEN CONCAT('Application #', NEW.application_id, ' is now under committee review.')
        WHEN 'approve' THEN CONCAT('Application #', NEW.application_id, ' was approved by committee. Awaiting CEO approval.')
        WHEN 'ceo_approve' THEN CONCAT('Application #', NEW.application_id, ' has been approved. You will receive the fund after disbursement.')
        WHEN 'reject' THEN CONCAT('Application #', NEW.application_id, ' was rejected.', IFNULL(CONCAT(' Note: ', NEW.notes), ''))
        WHEN 'disburse' THEN CONCAT('Application #', NEW.application_id, ' has been disbursed. You can view the receipt in View history.')
        ELSE CONCAT('Application #', NEW.application_id, ' status was updated.')
    END;

    INSERT INTO notification (user_id, application_id, type, title, message, is_read)
    VALUES (v_user_id, NEW.application_id, 'status_change', v_title, v_message, 0);
END//
DELIMITER ;

-- Announcements table (admin-posted notices visible to all students)
CREATE TABLE IF NOT EXISTS announcements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255)                          NOT NULL,
    body        TEXT                                  NULL,
    is_active   TINYINT(1)                            NOT NULL DEFAULT 1,
    pinned      TINYINT(1)                            NOT NULL DEFAULT 0
                COMMENT '1 = pinned to top of list',
    created_by  INT UNSIGNED                          NULL
                COMMENT 'FK to staff.id — NULL for seeded/system rows',
    created_at  DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME                              NULL
                COMMENT 'NULL = never expires; set a date to auto-hide after that time',
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_active_expires (is_active, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Admin-posted announcements shown on the student dashboard';