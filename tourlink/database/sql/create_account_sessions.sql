-- Select the production database (havenedg_tourlink) in phpMyAdmin before importing.
-- This creates the empty account-session registry required by the application.
-- It does not create, alter, or delete user accounts or existing session data.

CREATE TABLE IF NOT EXISTS `account_sessions` (
    `id` CHAR(26) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `session_fingerprint` VARCHAR(64) NOT NULL,
    `session_type` VARCHAR(16) NOT NULL,
    `personal_access_token_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `last_activity_at` TIMESTAMP NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `account_sessions_user_id_unique` (`user_id`),
    UNIQUE KEY `account_sessions_personal_access_token_id_unique` (`personal_access_token_id`),
    KEY `account_sessions_expires_at_index` (`expires_at`)
) ENGINE=InnoDB;
