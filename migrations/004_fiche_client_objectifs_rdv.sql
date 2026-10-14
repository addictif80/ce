-- Migration 004 : Fiche client 360°, Objectifs vs réalisé, Trames de RDV
-- Ces tables sont également créées automatiquement à la volée par les modules
-- (ensure*Schema). Ce fichier documente le schéma pour les environnements gérés en SQL.

-- =========================================================
-- Fiche client 360° : notes internes et actions retenues
-- =========================================================
CREATE TABLE IF NOT EXISTS `fiche_client_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fcn` (`user_id`, `numero_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fiche_client_actions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `produit` VARCHAR(255) DEFAULT '',
  `source` VARCHAR(10) DEFAULT 'regle',
  `priorite` VARCHAR(10) DEFAULT 'normale',
  `statut` VARCHAR(10) DEFAULT 'a_faire',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fca` (`user_id`, `numero_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Objectifs vs réalisé : objectifs par conseiller et par période
-- =========================================================
CREATE TABLE IF NOT EXISTS `objectifs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `periode_debut` DATE NOT NULL,
  `periode_fin` DATE NOT NULL,
  `cle` VARCHAR(50) NOT NULL,
  `libelle` VARCHAR(255) NOT NULL,
  `section` VARCHAR(50) NOT NULL,
  `unite` VARCHAR(20) DEFAULT '',
  `valeur_attendue` DECIMAL(15,2) DEFAULT 0,
  `ordre` INT DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_obj` (`user_id`, `periode_debut`, `periode_fin`, `cle`),
  INDEX `idx_obj_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Réalisé saisi manuellement (indicateurs non calculables automatiquement)
CREATE TABLE IF NOT EXISTS `objectifs_valeurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `periode_debut` DATE NOT NULL,
  `periode_fin` DATE NOT NULL,
  `cle` VARCHAR(50) NOT NULL,
  `valeur` DECIMAL(15,2) DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_objval` (`user_id`, `periode_debut`, `periode_fin`, `cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Trames de RDV / modèles d'entretien
-- =========================================================
CREATE TABLE IF NOT EXISTS `modeles_rdv` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom_modele` VARCHAR(255) NOT NULL,
  `categorie` VARCHAR(100) DEFAULT '',
  `objectif` VARCHAR(500) DEFAULT '',
  `duree_min` INT DEFAULT 30,
  `trame` LONGTEXT,
  `checklist` TEXT,
  `usage_public` TINYINT(1) DEFAULT 0,
  `approved` TINYINT(1) DEFAULT 0,
  `approved_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_mrdv_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
