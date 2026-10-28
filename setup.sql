-- setup.sql — creates the simple_chat database and tables
-- Import: mysql -u root < setup.sql   (or paste into phpMyAdmin)

CREATE DATABASE IF NOT EXISTS `simple_chat`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `simple_chat`;

-- -------------------------------------------------------
-- Users table
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)     NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Messages table — stores only ciphertext, never plaintext
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `sender_id`   INT UNSIGNED    NOT NULL,
  `receiver_id` INT UNSIGNED    NOT NULL,
  `ciphertext`  TEXT            NOT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sender_receiver` (`sender_id`, `receiver_id`, `id`),
  INDEX `idx_receiver_sender` (`receiver_id`, `sender_id`, `id`),
  CONSTRAINT `fk_messages_sender`
    FOREIGN KEY (`sender_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver`
    FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
