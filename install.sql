-- Portail de gestion d'activités - Caisse d'Épargne
-- Script d'installation de la base de données

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email_pro` VARCHAR(150) DEFAULT NULL,
  `tel_pro` VARCHAR(20) DEFAULT NULL,
  `ligne_interne` VARCHAR(20) DEFAULT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Utilisateur admin par défaut
INSERT INTO `users` (`username`, `password`, `nom`, `prenom`, `is_admin`) VALUES
('adrien', '$2y$10$placeholder', 'Admin', 'Adrien', 1);

-- Table instances
CREATE TABLE IF NOT EXISTS `instances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `numero_personne` VARCHAR(100) NOT NULL,
  `date_echeance` DATE DEFAULT NULL,
  `categories` VARCHAR(500) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `statut` ENUM('a_faire','fait') DEFAULT 'a_faire',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table codes utiles
CREATE TABLE IF NOT EXISTS `codes_utiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `code` VARCHAR(255) NOT NULL,
  `fonction` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table contacts utiles
CREATE TABLE IF NOT EXISTS `contacts_utiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `mail` VARCHAR(150) DEFAULT NULL,
  `service` VARCHAR(255) DEFAULT NULL,
  `a_contacter_pour` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table demandes de rappel
CREATE TABLE IF NOT EXISTS `demandes_rappel` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `motif` TEXT DEFAULT NULL,
  `traitee` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table tentatives d'appel
CREATE TABLE IF NOT EXISTS `tentatives_appel` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `demande_rappel_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `date_tentative` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `commentaire` VARCHAR(500) DEFAULT NULL,
  FOREIGN KEY (`demande_rappel_id`) REFERENCES `demandes_rappel`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table offres en cours
CREATE TABLE IF NOT EXISTS `offres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  `date_debut` DATE DEFAULT NULL,
  `date_fin` DATE DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table suivi production
CREATE TABLE IF NOT EXISTS `suivi_production` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_rdv` DATETIME NOT NULL,
  `categorie` ENUM('Banca','Epargne','Placement','Credit','Assurance') NOT NULL,
  `produit_vendu` VARCHAR(255) DEFAULT NULL,
  `montant_nombre` VARCHAR(100) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table séances phoning (dossiers)
CREATE TABLE IF NOT EXISTS `seances_phoning` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `titre` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `nombre_appels` INT DEFAULT 0,
  `nombre_rdv` INT DEFAULT 0,
  `dont_s` INT DEFAULT 0,
  `dont_s1` INT DEFAULT 0,
  `dont_anv` INT DEFAULT 0,
  `nombre_repondeur` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table appels phoning (appels individuels dans un dossier)
CREATE TABLE IF NOT EXISTS `appels_phoning` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seance_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) DEFAULT NULL,
  `resultat` ENUM('repondu','repondeur','indisponible','rdv') NOT NULL DEFAULT 'repondu',
  `date_rdv` DATE DEFAULT NULL,
  `motif_rdv` ENUM('Banca','Epargne','Placement','Crédit','Assurances') DEFAULT NULL,
  `is_anv` TINYINT(1) DEFAULT 0,
  `commentaire` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seance_id`) REFERENCES `seances_phoning`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table suivi demande clients
CREATE TABLE IF NOT EXISTS `demandes_clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `numero_personne` VARCHAR(100) NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `details_demande` TEXT DEFAULT NULL,
  `date_envoi` DATE DEFAULT NULL,
  `service` VARCHAR(255) DEFAULT NULL,
  `traitee` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table bloc-notes
CREATE TABLE IF NOT EXISTS `blocnotes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom_note` VARCHAR(255) NOT NULL,
  `contenu` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table procédures
CREATE TABLE IF NOT EXISTS `procedures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  `texte` LONGTEXT DEFAULT NULL,
  `mise_en_avant` TINYINT(1) DEFAULT 0,
  `lien_partage` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table modèles de courriers
CREATE TABLE IF NOT EXISTS `modeles_courriers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom_modele` VARCHAR(255) NOT NULL,
  `objet` VARCHAR(500) DEFAULT NULL,
  `corps` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table courriers
CREATE TABLE IF NOT EXISTS `courriers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nom_prenom_dest` VARCHAR(255) DEFAULT NULL,
  `complement_dest` VARCHAR(255) DEFAULT NULL,
  `adresse_dest` VARCHAR(500) DEFAULT NULL,
  `complement_adresse_dest` VARCHAR(255) DEFAULT NULL,
  `cp_ville_dest` VARCHAR(255) DEFAULT NULL,
  `lieu` VARCHAR(100) DEFAULT 'Capdenac-Gare',
  `date_courrier` DATE DEFAULT (CURRENT_DATE),
  `objet` VARCHAR(500) DEFAULT NULL,
  `corps` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table crédit immobilier
CREATE TABLE IF NOT EXISTS `credit_immobilier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  -- Onglet Client
  `numero_personne` VARCHAR(100) DEFAULT NULL,
  `type_client` ENUM('Particulier','Pro','Asso') DEFAULT 'Particulier',
  `type_occupation` ENUM('Proprietaire','Locatif') DEFAULT NULL,
  `type_residence` ENUM('RP','RS') DEFAULT NULL,
  `type_bien` ENUM('Appartement','Maison','Copro') DEFAULT NULL,
  `proprietaire_logement` TINYINT(1) DEFAULT 0,
  `adresse_bien` TEXT DEFAULT NULL,
  -- Onglet Crédit
  `type_credit` VARCHAR(255) DEFAULT NULL,
  `avec_travaux` TINYINT(1) DEFAULT 0,
  `montant_acquisition` DECIMAL(15,2) DEFAULT 0,
  `frais_notaire` DECIMAL(15,2) DEFAULT 0,
  `frais_agence` DECIMAL(15,2) DEFAULT 0,
  `frais_courtage` DECIMAL(15,2) DEFAULT 0,
  `frais_dossier` DECIMAL(15,2) DEFAULT 0,
  `cegc` DECIMAL(15,2) DEFAULT 0,
  `ade` DECIMAL(15,2) DEFAULT 0,
  `travaux` DECIMAL(15,2) DEFAULT 0,
  `dont_ecoptz_ptz` DECIMAL(15,2) DEFAULT 0,
  `taux_emprunt` DECIMAL(5,3) DEFAULT 0,
  `duree_emprunt` INT DEFAULT 0,
  `apport` DECIMAL(15,2) DEFAULT 0,
  -- Onglet PTZ/EcoPTZ
  `ptz_demande` ENUM('Initiale','Complementaire') DEFAULT NULL,
  `ptz_type` ENUM('Perf globale','Bouquets') DEFAULT NULL,
  `ptz_nombre_bouquets` VARCHAR(255) DEFAULT NULL,
  -- Onglet Situation financière
  `revenus_mensuels` DECIMAL(15,2) DEFAULT 0,
  `charges_fixes` DECIMAL(15,2) DEFAULT 0,
  `loyer` DECIMAL(15,2) DEFAULT 0,
  `credits_en_cours` DECIMAL(15,2) DEFAULT 0,
  `epargne` DECIMAL(15,2) DEFAULT 0,
  -- Onglet Suivi - Documents
  `doc_ji` TINYINT(1) DEFAULT 0,
  `doc_jd` TINYINT(1) DEFAULT 0,
  `doc_ir` TINYINT(1) DEFAULT 0,
  `doc_contrat_travail` TINYINT(1) DEFAULT 0,
  `doc_bulletins_salaire` TINYINT(1) DEFAULT 0,
  `doc_justif_propriete` TINYINT(1) DEFAULT 0,
  `doc_releves_externes` TINYINT(1) DEFAULT 0,
  `doc_epargnes_externes` TINYINT(1) DEFAULT 0,
  -- Onglet Suivi - EcoPTZ
  `eco_ademe_emprunteur` TINYINT(1) DEFAULT 0,
  `eco_ademe_entreprises` TINYINT(1) DEFAULT 0,
  `eco_dpe` TINYINT(1) DEFAULT 0,
  `eco_audit` TINYINT(1) DEFAULT 0,
  `eco_devis_travaux` TINYINT(1) DEFAULT 0,
  -- Onglet Suivi - Suivi
  `suivi_synthese_envoyee` TINYINT(1) DEFAULT 0,
  `suivi_controle_conformite` TINYINT(1) DEFAULT 0,
  `suivi_edition_offres` TINYINT(1) DEFAULT 0,
  `suivi_envoi_signature` TINYINT(1) DEFAULT 0,
  `suivi_offre_signee` TINYINT(1) DEFAULT 0,
  `suivi_offre_signee_date` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table calculateur de budget
CREATE TABLE IF NOT EXISTS `calculateur_budget` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `avec_conjoint` TINYINT(1) DEFAULT 0,
  -- Revenus
  `salaire` DECIMAL(10,2) DEFAULT 0,
  `salaire_conjoint` DECIMAL(10,2) DEFAULT 0,
  `autres_revenus` DECIMAL(10,2) DEFAULT 0,
  `autres_revenus_conjoint` DECIMAL(10,2) DEFAULT 0,
  `allocations` DECIMAL(10,2) DEFAULT 0,
  `pensions` DECIMAL(10,2) DEFAULT 0,
  `pensions_conjoint` DECIMAL(10,2) DEFAULT 0,
  `revenus_fonciers` DECIMAL(10,2) DEFAULT 0,
  `revenus_fonciers_conjoint` DECIMAL(10,2) DEFAULT 0,
  -- Charges fixes
  `loyer_charges` DECIMAL(10,2) DEFAULT 0,
  `credit_immo` DECIMAL(10,2) DEFAULT 0,
  `credits_conso` DECIMAL(10,2) DEFAULT 0,
  `assurance_habitation` DECIMAL(10,2) DEFAULT 0,
  `assurance_auto` DECIMAL(10,2) DEFAULT 0,
  `assurance_sante` DECIMAL(10,2) DEFAULT 0,
  `impots` DECIMAL(10,2) DEFAULT 0,
  `taxe_fonciere` DECIMAL(10,2) DEFAULT 0,
  `taxe_habitation` DECIMAL(10,2) DEFAULT 0,
  -- Charges courantes
  `electricite_gaz` DECIMAL(10,2) DEFAULT 0,
  `eau` DECIMAL(10,2) DEFAULT 0,
  `telephone_internet` DECIMAL(10,2) DEFAULT 0,
  `transport` DECIMAL(10,2) DEFAULT 0,
  `alimentation` DECIMAL(10,2) DEFAULT 0,
  `habillement` DECIMAL(10,2) DEFAULT 0,
  `sante` DECIMAL(10,2) DEFAULT 0,
  `loisirs` DECIMAL(10,2) DEFAULT 0,
  `epargne_mensuelle` DECIMAL(10,2) DEFAULT 0,
  `divers` DECIMAL(10,2) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table formations
CREATE TABLE IF NOT EXISTS `formations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `date_debut` DATE DEFAULT NULL,
  `date_fin` DATE DEFAULT NULL,
  `lieu` ENUM('presentiel','distanciel') DEFAULT 'distanciel',
  `adresse_hotel` VARCHAR(500) DEFAULT NULL,
  `reservation_faite` TINYINT(1) DEFAULT 0,
  `peage_ar` DECIMAL(10,2) DEFAULT 0,
  `repas` DECIMAL(10,2) DEFAULT 0,
  `indemnites_km` DECIMAL(10,2) DEFAULT 0,
  `montant_total` DECIMAL(10,2) DEFAULT 0,
  `envoyee_expansya` TINYINT(1) DEFAULT 0,
  `remboursee` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table notifications envoyées (éviter les doublons)
CREATE TABLE IF NOT EXISTS `notifications_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `date_envoi` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `nb_instances` INT DEFAULT 0,
  `nb_demandes` INT DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table notes (pour instances, demandes rappel, demandes clients)
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table liens externes (gérés par l'admin)
CREATE TABLE IF NOT EXISTS `liens_externes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(255) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `categorie_id` INT DEFAULT NULL,
  `ordre` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table catégories de liens
CREATE TABLE IF NOT EXISTS `categories_liens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `ordre` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table configuration du menu
CREATE TABLE IF NOT EXISTS `menu_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_key` VARCHAR(50) NOT NULL UNIQUE,
  `parent_key` VARCHAR(50) DEFAULT NULL,
  `label` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `uri_patterns` VARCHAR(500) DEFAULT NULL,
  `ordre` INT DEFAULT 0,
  `visible` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table mobilités entrantes
CREATE TABLE IF NOT EXISTS `mobilites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_ajout` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `numero_personne` VARCHAR(100) DEFAULT NULL,
  `nom_client` VARCHAR(255) DEFAULT NULL,
  `banque_depart` VARCHAR(255) DEFAULT NULL,
  `is_ce_hors_mp` TINYINT(1) DEFAULT 0,
  `etape` ENUM('rdv','synthese','ouverture','mobilite','termine') DEFAULT 'rdv',
  -- Étape 1 : RDV - Documents
  `doc_carte_identite` TINYINT(1) DEFAULT 0,
  `doc_justif_domicile` TINYINT(1) DEFAULT 0,
  `doc_avis_imposition` TINYINT(1) DEFAULT 0,
  `doc_releves_externes` TINYINT(1) DEFAULT 0,
  `doc_rib` TINYINT(1) DEFAULT 0,
  -- Étape 2 : Synthèse client
  `synthese_faite` TINYINT(1) DEFAULT 0,
  -- Étape 3 : Ouverture du compte
  `type_compte` ENUM('CDD','OCF','Initial','Confort','Optimal') DEFAULT NULL,
  `compte_joint` TINYINT(1) DEFAULT 0,
  `montant_decouvert` DECIMAL(10,2) DEFAULT 0,
  `izicarte` TINYINT(1) DEFAULT 0,
  -- Étape 4 : Demande de mobilité
  `mandat_signe` TINYINT(1) DEFAULT 0,
  `date_fin_mobilite` DATE DEFAULT NULL,
  `cloture_demandee` TINYINT(1) DEFAULT 0,
  `date_cloture_depart` DATE DEFAULT NULL,
  -- Spécificités Mobiliz (CE hors MP)
  `mobiliz_mail_envoye` TINYINT(1) DEFAULT 0,
  `mobiliz_synthese_recue` TINYINT(1) DEFAULT 0,
  `mobiliz_04_ouvert` TINYINT(1) DEFAULT 0,
  `mobiliz_tel_fait` TINYINT(1) DEFAULT 0,
  `mobiliz_epargnes_a_transferer` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table cartes bancaires liées aux mobilités
CREATE TABLE IF NOT EXISTS `mobilites_cartes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mobilite_id` INT NOT NULL,
  `titulaire` VARCHAR(255) DEFAULT NULL,
  `type_carte` ENUM('VCTRL_Syst','VCTRL_casiSyst','VClassic','V1er','VPlatinium') DEFAULT NULL,
  `type_debit` ENUM('immediat','differe') DEFAULT 'immediat',
  `commandee` TINYINT(1) DEFAULT 0,
  `date_commande` DATE DEFAULT NULL,
  `recue` TINYINT(1) DEFAULT 0,
  `date_reception` DATE DEFAULT NULL,
  `remise_client` TINYINT(1) DEFAULT 0,
  `date_remise` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`mobilite_id`) REFERENCES `mobilites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table chéquiers liés aux mobilités
CREATE TABLE IF NOT EXISTS `mobilites_chequiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mobilite_id` INT NOT NULL,
  `titulaire` VARCHAR(255) DEFAULT NULL,
  `commande` TINYINT(1) DEFAULT 0,
  `date_commande` DATE DEFAULT NULL,
  `recu` TINYINT(1) DEFAULT 0,
  `date_reception` DATE DEFAULT NULL,
  `remis_client` TINYINT(1) DEFAULT 0,
  `date_remise` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`mobilite_id`) REFERENCES `mobilites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table EAI - Attendus (objectifs fixés par l'admin)
CREATE TABLE IF NOT EXISTS `eai_attendus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cle` VARCHAR(50) NOT NULL UNIQUE,
  `libelle` VARCHAR(255) NOT NULL,
  `section` VARCHAR(50) NOT NULL,
  `valeur_attendue` DECIMAL(15,2) DEFAULT 0,
  `unite` VARCHAR(20) DEFAULT '',
  `ordre` INT DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table EAI - Rapports hebdomadaires
CREATE TABLE IF NOT EXISTS `eai_rapports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `semaine_date` DATE NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_semaine` (`user_id`, `semaine_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table EAI - Valeurs par indicateur par rapport
CREATE TABLE IF NOT EXISTS `eai_valeurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rapport_id` INT NOT NULL,
  `cle` VARCHAR(50) NOT NULL,
  `valeur` DECIMAL(15,2) DEFAULT 0,
  `auto_filled` TINYINT(1) DEFAULT 0,
  UNIQUE KEY `unique_rapport_cle` (`rapport_id`, `cle`),
  FOREIGN KEY (`rapport_id`) REFERENCES `eai_rapports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table configuration SMTP
CREATE TABLE IF NOT EXISTS `smtp_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `smtp_host` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_port` INT DEFAULT 587,
  `smtp_user` VARCHAR(255) DEFAULT '',
  `smtp_pass` VARCHAR(255) DEFAULT '',
  `smtp_secure` ENUM('tls','ssl','none') DEFAULT 'tls',
  `mail_from` VARCHAR(255) DEFAULT '',
  `mail_from_name` VARCHAR(255) DEFAULT 'Portail CE',
  `rappel_enabled` TINYINT(1) DEFAULT 1,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
