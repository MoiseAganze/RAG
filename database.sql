-- ============================================================
--  RAG — Structure de la base de données
--  3 tables : users · conversations · messages
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ─── Table : users ───────────────────────────────────────────
-- role 'admin_full'  → chat + indexation + gestion des admins
-- role 'admin_chat'  → chat uniquement
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `matricule`     VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nom`           VARCHAR(100) NOT NULL,
  `prenom`        VARCHAR(100) NOT NULL,
  `role`          ENUM('admin_full','admin_chat') NOT NULL DEFAULT 'admin_chat',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matricule` (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : conversations ────────────────────────────────────
-- Une conversation appartient à un utilisateur.
-- Le titre est mis à jour automatiquement lors du premier message.
CREATE TABLE IF NOT EXISTS `conversations` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,
  `title`      VARCHAR(255) NOT NULL DEFAULT 'Nouvelle conversation',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_updated` (`user_id`, `updated_at`),
  CONSTRAINT `fk_conv_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : messages ─────────────────────────────────────────
-- Chaque message est lié à une conversation.
-- role 'user'      → message de l'utilisateur
-- role 'assistant' → réponse du RAG
CREATE TABLE IF NOT EXISTS `messages` (
  `id`              INT       NOT NULL AUTO_INCREMENT,
  `conversation_id` INT       NOT NULL,
  `role`            ENUM('user','assistant') NOT NULL,
  `content`         TEXT      NOT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_created` (`conversation_id`, `created_at`),
  CONSTRAINT `fk_msg_conv`
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ─── Note ─────────────────────────────────────────────────────
-- L'administrateur initial est créé via setup.php (à exécuter
-- une seule fois puis supprimer).
-- Matricule par défaut : ADMIN001  /  Mot de passe : Admin@2024
