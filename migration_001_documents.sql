-- ============================================================
--  Migration 001 — Table documents
--  Historique des fichiers indexés via la page Indexation.
--  À exécuter une seule fois sur la base existante.
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `documents` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `user_id`       INT           NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `stored_name`   VARCHAR(255)  NOT NULL,
  `file_size`     INT           NOT NULL DEFAULT 0,
  `mime_type`     VARCHAR(100)  NOT NULL DEFAULT '',
  `status`        ENUM('success','error') NOT NULL DEFAULT 'success',
  `error_msg`     VARCHAR(255)  NULL DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_doc_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
