-- Migration 003 : Événements personnels du calendrier unifié
CREATE TABLE IF NOT EXISTS `evenements_perso` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT 'Utilisateur propriétaire de l''événement',
  `created_by` INT NOT NULL COMMENT 'Créateur (peut être un admin)',
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `date_debut` DATE NOT NULL,
  `date_fin` DATE DEFAULT NULL,
  `heure_debut` TIME DEFAULT NULL,
  `heure_fin` TIME DEFAULT NULL,
  `couleur` VARCHAR(7) DEFAULT '#27ae60',
  `is_admin_event` TINYINT(1) DEFAULT 0 COMMENT 'Si 1, l''utilisateur ne peut pas modifier',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
