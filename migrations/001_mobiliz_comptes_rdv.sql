-- Migration 001 : ajout des champs Mobiliz (comptes à transférer + rendez-vous)
-- À exécuter une seule fois sur les bases existantes créées avant cette migration.

ALTER TABLE `mobilites`
  ADD COLUMN `mobiliz_compte_cdd`            TINYINT(1)  DEFAULT 0    AFTER `mobiliz_tel_fait`,
  ADD COLUMN `mobiliz_compte_livret_a`       TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_cdd`,
  ADD COLUMN `mobiliz_compte_livret_b`       TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_livret_a`,
  ADD COLUMN `mobiliz_compte_lep`            TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_livret_b`,
  ADD COLUMN `mobiliz_compte_ldds`           TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_lep`,
  ADD COLUMN `mobiliz_compte_assurance_vie`  TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_ldds`,
  ADD COLUMN `mobiliz_compte_pea`            TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_assurance_vie`,
  ADD COLUMN `mobiliz_compte_parts_sociales` TINYINT(1)  DEFAULT 0    AFTER `mobiliz_compte_pea`,
  ADD COLUMN `mobiliz_rdv_date`              DATE        DEFAULT NULL  AFTER `mobiliz_compte_parts_sociales`,
  ADD COLUMN `mobiliz_rdv_heure`             VARCHAR(5)  DEFAULT NULL  AFTER `mobiliz_rdv_date`,
  ADD COLUMN `mobiliz_rdv_honore`            TINYINT(1)  DEFAULT 0    AFTER `mobiliz_rdv_heure`;
