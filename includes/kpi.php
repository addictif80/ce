<?php
/**
 * Catalogue unique des indicateurs (KPIs) du portail.
 *
 * Source de vérité partagée par :
 *   - le module EAI (pilotage hebdomadaire collectif) ;
 *   - le module Objectifs vs réalisé (pilotage par conseiller et par période).
 *
 * Chaque indicateur porte :
 *   cle       identifiant stable (utilisé comme clé EAI et clé d'objectif)
 *   libelle   libellé affiché
 *   section   ventes | activite | rdv | portefeuille
 *   unite     '' | '€' | '%'
 *   ordre     ordre d'affichage dans la section
 *   source    mode de calcul du réalisé (voir includes/objectifs.php) :
 *             '' (non calculé) | production_count | production_sum
 *             | appels_sum | decroche | rdv | rdv_anv | manuel
 *   eai_cle   clé utilisée dans suivi_production.eai_cle (pour production_*)
 *   eai       true si l'indicateur figure dans le rapport EAI
 *   obj       true si l'indicateur figure dans les objectifs par période
 *
 * Les RDV EAI sont ventilés par semaine (rdv_s / rdv_s1 / rdv_s2) ; le module
 * Objectifs les agrège sur la période (cle 'rdv' / 'rdv_anv').
 */
require_once __DIR__ . '/functions.php';

function getKpiCatalogue() {
    return [
        // ---------------------------------------------------------------- Ventes
        ['cle' => 'ventes_brut_anv',       'libelle' => 'Ventes brut ANV',            'section' => 'ventes',       'unite' => '',  'ordre' => 1,  'source' => 'production_count', 'eai_cle' => 'ventes_brut_anv',       'eai' => true,  'obj' => true],
        ['cle' => 'volume_pret_perso',      'libelle' => 'Volume Prêt Personnel',      'section' => 'ventes',       'unite' => '€', 'ordre' => 2,  'source' => 'production_sum',   'eai_cle' => 'volume_pret_perso',     'eai' => true,  'obj' => true],
        ['cle' => 'cartes_hdg_dd',          'libelle' => 'Cartes HDG DD',              'section' => 'ventes',       'unite' => '',  'ordre' => 3,  'source' => 'production_count', 'eai_cle' => 'cartes_hdg_dd',         'eai' => true,  'obj' => true],
        ['cle' => 'izicartes',              'libelle' => 'Izicartes',                  'section' => 'ventes',       'unite' => '',  'ordre' => 4,  'source' => 'production_count', 'eai_cle' => 'izicartes',             'eai' => true,  'obj' => true],
        ['cle' => 'forfaits',               'libelle' => 'Forfaits',                   'section' => 'ventes',       'unite' => '',  'ordre' => 5,  'source' => 'production_count', 'eai_cle' => 'forfaits',              'eai' => true,  'obj' => true],
        ['cle' => 'volume_collecte',        'libelle' => 'Volume Collecte',            'section' => 'ventes',       'unite' => '€', 'ordre' => 6,  'source' => 'production_sum',   'eai_cle' => 'volume_collecte',       'eai' => true,  'obj' => true],
        ['cle' => 'livrets',                'libelle' => 'Livrets',                    'section' => 'ventes',       'unite' => '',  'ordre' => 7,  'source' => 'production_count', 'eai_cle' => 'livrets',               'eai' => true,  'obj' => true],
        ['cle' => 'pel_quadreto',           'libelle' => 'PEL / Quadreto',             'section' => 'ventes',       'unite' => '',  'ordre' => 8,  'source' => 'production_count', 'eai_cle' => 'pel_quadreto',          'eai' => true,  'obj' => true],
        ['cle' => 'assvie_peri',            'libelle' => 'AssVie / PERi',              'section' => 'ventes',       'unite' => '',  'ordre' => 9,  'source' => 'production_count', 'eai_cle' => 'assvie_peri',           'eai' => true,  'obj' => true],
        ['cle' => 'volume_parts_sociales',  'libelle' => 'Volume Parts Sociales',      'section' => 'ventes',       'unite' => '€', 'ordre' => 10, 'source' => 'production_sum',   'eai_cle' => 'volume_parts_sociales', 'eai' => true,  'obj' => true],
        ['cle' => 'nouveau_societaire',     'libelle' => 'Nouveau Sociétaire',         'section' => 'ventes',       'unite' => '',  'ordre' => 11, 'source' => 'production_count', 'eai_cle' => 'nouveau_societaire',    'eai' => true,  'obj' => true],
        ['cle' => 'bp_jbp',                 'libelle' => 'BP / jBP',                   'section' => 'ventes',       'unite' => '',  'ordre' => 12, 'source' => 'production_count', 'eai_cle' => 'bp_jbp',                'eai' => true,  'obj' => true],
        ['cle' => 'equip_jequip',           'libelle' => 'Equip / jEquip',             'section' => 'ventes',       'unite' => '',  'ordre' => 13, 'source' => 'production_count', 'eai_cle' => 'equip_jequip',          'eai' => true,  'obj' => true],
        // -------------------------------------------------------------- Activité
        ['cle' => 'taux_decroche',          'libelle' => 'Taux décroché',              'section' => 'activite',     'unite' => '%', 'ordre' => 14, 'source' => 'decroche',         'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'taux_reponse_mails',     'libelle' => 'Taux réponse aux mails',     'section' => 'activite',     'unite' => '%', 'ordre' => 15, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'volume_appels_sortants', 'libelle' => 'Volume appels sortants',     'section' => 'activite',     'unite' => '',  'ordre' => 16, 'source' => 'appels_sum',       'eai_cle' => '',                      'eai' => true,  'obj' => true],
        // ------------------------------------------- Rendez-vous (ventilation EAI)
        ['cle' => 'rdv_s',                  'libelle' => 'Nombre de RDV - Sem. passée',   'section' => 'rdv', 'unite' => '', 'ordre' => 17, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_s1',                 'libelle' => 'Nombre de RDV - Sem. en cours', 'section' => 'rdv', 'unite' => '', 'ordre' => 18, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_s2',                 'libelle' => 'Nombre de RDV - Sem. prochaine', 'section' => 'rdv', 'unite' => '', 'ordre' => 19, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_proactifs_s',        'libelle' => 'RDV Proactifs - Sem. passée',   'section' => 'rdv', 'unite' => '', 'ordre' => 20, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_proactifs_s1',       'libelle' => 'RDV Proactifs - Sem. en cours', 'section' => 'rdv', 'unite' => '', 'ordre' => 21, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_proactifs_s2',       'libelle' => 'RDV Proactifs - Sem. prochaine', 'section' => 'rdv', 'unite' => '', 'ordre' => 22, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_anv_s',              'libelle' => 'RDV ANV - Sem. passée',         'section' => 'rdv', 'unite' => '', 'ordre' => 23, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_anv_s1',             'libelle' => 'RDV ANV - Sem. en cours',       'section' => 'rdv', 'unite' => '', 'ordre' => 24, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        ['cle' => 'rdv_anv_s2',             'libelle' => 'RDV ANV - Sem. prochaine',      'section' => 'rdv', 'unite' => '', 'ordre' => 25, 'source' => '', 'eai_cle' => '', 'eai' => true,  'obj' => false],
        // ------------------------------------- Rendez-vous (agrégés, module Objectifs)
        ['cle' => 'rdv',                    'libelle' => 'Nombre de RDV',              'section' => 'rdv',          'unite' => '',  'ordre' => 26, 'source' => 'rdv',              'eai_cle' => '',                      'eai' => false, 'obj' => true],
        ['cle' => 'rdv_anv',                'libelle' => 'RDV ANV',                    'section' => 'rdv',          'unite' => '',  'ordre' => 27, 'source' => 'rdv_anv',          'eai_cle' => '',                      'eai' => false, 'obj' => true],
        // ----------------------------------------------------------- Portefeuille
        ['cle' => 'myflow_en_cours',        'libelle' => 'MyFlow en cours',            'section' => 'portefeuille', 'unite' => '',  'ordre' => 28, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'vigiclients_en_cours',   'libelle' => 'Vigiclients en cours',       'section' => 'portefeuille', 'unite' => '',  'ordre' => 29, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'rpm_taux_traitement',    'libelle' => 'RPM - Taux de traitement',   'section' => 'portefeuille', 'unite' => '%', 'ordre' => 30, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'rpm_clients_15j',        'libelle' => 'RPM - Clients > 15j',        'section' => 'portefeuille', 'unite' => '',  'ordre' => 31, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
        ['cle' => 'rpm_montant_total',      'libelle' => 'RPM - Montant total',        'section' => 'portefeuille', 'unite' => '€', 'ordre' => 32, 'source' => 'manuel',           'eai_cle' => '',                      'eai' => true,  'obj' => true],
    ];
}

/** Indicateurs utilisés par le rapport EAI (pilotage hebdomadaire). */
function getKpiCatalogueEai() {
    return array_values(array_filter(getKpiCatalogue(), fn($d) => !empty($d['eai'])));
}

/** Indicateurs utilisés par le module Objectifs (par conseiller et par période). */
function getKpiCatalogueObjectifs() {
    return array_values(array_filter(getKpiCatalogue(), fn($d) => !empty($d['obj'])));
}

/**
 * Attendus globaux définis dans EAI (onglet Attendus), indexés par clé.
 * Ces attendus sont des cibles HEBDOMADAIRES (un seul jeu pour tout le monde).
 */
function getEaiAttendus($db) {
    $out = [];
    try {
        $stmt = $db->query("SELECT cle, valeur_attendue FROM eai_attendus");
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['cle']] = (float)$r['valeur_attendue'];
        }
    } catch (Exception $e) {}
    return $out;
}
