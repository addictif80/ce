-- Migration 002 : Module de suivi des signatures
-- Dossiers de demande de signature client

CREATE TABLE IF NOT EXISTS `dossiers_signature` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) NOT NULL,
  `nom_client` VARCHAR(255) NOT NULL,
  `email_client` VARCHAR(255) NOT NULL,
  `date_envoi` DATE NOT NULL,
  `statut` ENUM('en_cours','traite') DEFAULT 'en_cours',
  `date_traitement` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Documents à signer, liés à un dossier
CREATE TABLE IF NOT EXISTS `signature_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dossier_id` INT NOT NULL,
  `nom_document` VARCHAR(255) NOT NULL,
  `recu` TINYINT(1) DEFAULT 0,
  `date_reception` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_signature`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
