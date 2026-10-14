<?php
/**
 * Objectifs vs réalisé.
 *
 * - Définit les indicateurs (KPIs) suivis par période.
 * - Stocke les objectifs par conseiller et par période (table objectifs).
 * - Stocke le réalisé saisi manuellement (table objectifs_valeurs).
 * - Calcule le réalisé automatiquement depuis les modules source (phoning,
 *   production) sur une période quelconque.
 *
 * Les clés de production (eai_cle) sont identiques à celles du module EAI et
 * de la saisie de production, afin que les chiffres restent cohérents.
 */
require_once __DIR__ . '/functions.php';

function ensureObjectifsSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS objectifs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        periode_debut DATE NOT NULL,
        periode_fin DATE NOT NULL,
        cle VARCHAR(50) NOT NULL,
        libelle VARCHAR(255) NOT NULL,
        section VARCHAR(50) NOT NULL,
        unite VARCHAR(20) DEFAULT '',
        valeur_attendue DECIMAL(15,2) DEFAULT 0,
        ordre INT DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_obj (user_id, periode_debut, periode_fin, cle),
        INDEX idx_obj_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS objectifs_valeurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        periode_debut DATE NOT NULL,
        periode_fin DATE NOT NULL,
        cle VARCHAR(50) NOT NULL,
        valeur DECIMAL(15,2) DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_objval (user_id, periode_debut, periode_fin, cle)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Libellés des sections, dans l'ordre d'affichage. */
function getObjectifsSections() {
    return [
        'ventes'       => 'Ventes',
        'activite'     => 'Activité',
        'rdv'          => 'Rendez-vous',
        'portefeuille' => 'Portefeuille',
    ];
}

/**
 * Définition des indicateurs.
 *  - source : 'production_count' | 'production_sum' (via eai_cle)
 *             | 'appels_sum' | 'decroche' | 'rdv' | 'rdv_anv' | 'manuel'
 */
function getKpiDefs() {
    return [
        // Ventes (calculé depuis suivi_production)
        ['cle' => 'ventes_brut_anv',       'libelle' => 'Ventes brut ANV',            'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'ventes_brut_anv',       'ordre' => 1],
        ['cle' => 'volume_pret_perso',      'libelle' => 'Volume Prêt Personnel',      'section' => 'ventes',       'unite' => '€', 'source' => 'production_sum',   'eai_cle' => 'volume_pret_perso',     'ordre' => 2],
        ['cle' => 'cartes_hdg_dd',          'libelle' => 'Cartes HDG / DD',            'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'cartes_hdg_dd',         'ordre' => 3],
        ['cle' => 'izicartes',              'libelle' => 'Izicartes',                  'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'izicartes',             'ordre' => 4],
        ['cle' => 'forfaits',               'libelle' => 'Forfaits',                   'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'forfaits',              'ordre' => 5],
        ['cle' => 'volume_collecte',        'libelle' => 'Volume Collecte',            'section' => 'ventes',       'unite' => '€', 'source' => 'production_sum',   'eai_cle' => 'volume_collecte',       'ordre' => 6],
        ['cle' => 'livrets',                'libelle' => 'Livrets',                    'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'livrets',               'ordre' => 7],
        ['cle' => 'pel_quadreto',           'libelle' => 'PEL / Quadreto',             'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'pel_quadreto',          'ordre' => 8],
        ['cle' => 'assvie_peri',            'libelle' => 'AssVie / PERi',              'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'assvie_peri',           'ordre' => 9],
        ['cle' => 'volume_parts_sociales',  'libelle' => 'Volume Parts Sociales',      'section' => 'ventes',       'unite' => '€', 'source' => 'production_sum',   'eai_cle' => 'volume_parts_sociales', 'ordre' => 10],
        ['cle' => 'nouveau_societaire',     'libelle' => 'Nouveau Sociétaire',         'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'nouveau_societaire',    'ordre' => 11],
        ['cle' => 'bp_jbp',                 'libelle' => 'BP / jBP',                   'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'bp_jbp',                'ordre' => 12],
        ['cle' => 'equip_jequip',           'libelle' => 'Equip / jEquip',             'section' => 'ventes',       'unite' => '',  'source' => 'production_count', 'eai_cle' => 'equip_jequip',          'ordre' => 13],
        // Activité
        ['cle' => 'volume_appels_sortants', 'libelle' => 'Volume appels sortants',     'section' => 'activite',     'unite' => '',  'source' => 'appels_sum',       'eai_cle' => '',                      'ordre' => 14],
        ['cle' => 'taux_decroche',          'libelle' => 'Taux décroché',              'section' => 'activite',     'unite' => '%', 'source' => 'decroche',         'eai_cle' => '',                      'ordre' => 15],
        ['cle' => 'taux_reponse_mails',     'libelle' => 'Taux réponse aux mails',     'section' => 'activite',     'unite' => '%', 'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 16],
        // Rendez-vous
        ['cle' => 'rdv',                    'libelle' => 'Nombre de RDV',              'section' => 'rdv',          'unite' => '',  'source' => 'rdv',              'eai_cle' => '',                      'ordre' => 17],
        ['cle' => 'rdv_anv',                'libelle' => 'RDV ANV',                    'section' => 'rdv',          'unite' => '',  'source' => 'rdv_anv',          'eai_cle' => '',                      'ordre' => 19],
        // Portefeuille (saisie manuelle)
        ['cle' => 'myflow_en_cours',        'libelle' => 'MyFlow en cours',            'section' => 'portefeuille', 'unite' => '',  'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 20],
        ['cle' => 'vigiclients_en_cours',   'libelle' => 'Vigiclients en cours',       'section' => 'portefeuille', 'unite' => '',  'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 21],
        ['cle' => 'rpm_taux_traitement',    'libelle' => 'RPM - Taux de traitement',   'section' => 'portefeuille', 'unite' => '%', 'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 22],
        ['cle' => 'rpm_clients_15j',        'libelle' => 'RPM - Clients > 15j',        'section' => 'portefeuille', 'unite' => '',  'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 23],
        ['cle' => 'rpm_montant_total',      'libelle' => 'RPM - Montant total',        'section' => 'portefeuille', 'unite' => '€', 'source' => 'manuel',           'eai_cle' => '',                      'ordre' => 24],
    ];
}

function getKpiDefsIndexed() {
    $out = [];
    foreach (getKpiDefs() as $d) $out[$d['cle']] = $d;
    return $out;
}

/**
 * Réalisé sur une période [from, to] (dates Y-m-d), calculé depuis les sources.
 * Les indicateurs 'manuel' restent à 0 sauf si une valeur est enregistrée.
 */
function getKpiRealise($db, $userId, $from, $to) {
    $data = [];
    foreach (getKpiDefs() as $def) $data[$def['cle']] = 0.0;

    // Volume d'appels sortants (séances de phoning)
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(nombre_appels), 0) FROM seances_phoning WHERE user_id = ? AND date_ajout BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        $data['volume_appels_sortants'] = (float)$stmt->fetchColumn();
    } catch (Exception $e) {}

    // Taux de décroché (appels dont l'appel a abouti)
    try {
        $stmt = $db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(resultat IN ('repondu','rdv')), 0) AS decroche FROM appels_phoning WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        $r = $stmt->fetch();
        $data['taux_decroche'] = ((int)$r['total'] > 0) ? round((float)$r['decroche'] / (int)$r['total'] * 100, 1) : 0.0;
    } catch (Exception $e) {}

    // Rendez-vous
    try {
        $stmt = $db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(is_anv = 1), 0) AS anv FROM appels_phoning WHERE user_id = ? AND resultat = 'rdv' AND date_rdv BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        $r = $stmt->fetch();
        $data['rdv'] = (float)$r['total'];
        $data['rdv_anv'] = (float)$r['anv'];
    } catch (Exception $e) {}

    // Production (ventes)
    try {
        $stmtCount = $db->prepare("SELECT COALESCE(COUNT(*), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
        $stmtSum   = $db->prepare("SELECT COALESCE(SUM(CAST(montant_nombre AS DECIMAL(15,2))), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
        foreach (getKpiDefs() as $def) {
            if ($def['source'] === 'production_count' && $def['eai_cle'] !== '') {
                $stmtCount->execute([$userId, $def['eai_cle'], $from, $to]);
                $data[$def['cle']] = (float)$stmtCount->fetchColumn();
            } elseif ($def['source'] === 'production_sum' && $def['eai_cle'] !== '') {
                $stmtSum->execute([$userId, $def['eai_cle'], $from, $to]);
                $data[$def['cle']] = (float)$stmtSum->fetchColumn();
            }
        }
        // Repli : à défaut de production saisie, on retombe sur les RDV ANV
        if ((float)$data['ventes_brut_anv'] == 0.0) $data['ventes_brut_anv'] = (float)$data['rdv_anv'];
    } catch (Exception $e) {}

    // Réalisé saisi manuellement
    try {
        ensureObjectifsSchema();
        $stmt = $db->prepare("SELECT cle, valeur FROM objectifs_valeurs WHERE user_id = ? AND periode_debut = ? AND periode_fin = ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $r) {
            if (isset($data[$r['cle']])) $data[$r['cle']] = (float)$r['valeur'];
        }
    } catch (Exception $e) {}

    return $data;
}

/**
 * Objectifs (attendus) d'un conseiller pour une période. Complète avec les
 * indicateurs manquants (valeur 0) pour que l'affichage soit toujours complet.
 */
function getObjectifs($db, $userId, $from, $to) {
    ensureObjectifsSchema();
    $rows = [];
    try {
        $stmt = $db->prepare("SELECT * FROM objectifs WHERE user_id = ? AND periode_debut = ? AND periode_fin = ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $r) $rows[$r['cle']] = $r;
    } catch (Exception $e) {}
    foreach (getKpiDefs() as $def) {
        if (!isset($rows[$def['cle']])) {
            $rows[$def['cle']] = [
                'cle' => $def['cle'], 'libelle' => $def['libelle'], 'section' => $def['section'],
                'unite' => $def['unite'], 'valeur_attendue' => 0, 'ordre' => $def['ordre'], 'source' => $def['source'],
            ];
        } else {
            $rows[$def['cle']]['source'] = $def['source'];
        }
    }
    return $rows;
}

/**
 * Enregistre les objectifs (attendus) et, éventuellement, le réalisé manuel.
 */
function saveObjectifs($db, $userId, $from, $to, array $attendus, array $manuels = []) {
    ensureObjectifsSchema();
    $defs = getKpiDefsIndexed();

    $ins = $db->prepare("INSERT INTO objectifs (user_id, periode_debut, periode_fin, cle, libelle, section, unite, valeur_attendue, ordre)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valeur_attendue = VALUES(valeur_attendue), libelle = VALUES(libelle), section = VALUES(section), unite = VALUES(unite)");
    foreach ($attendus as $cle => $val) {
        if (!isset($defs[$cle])) continue;
        $d = $defs[$cle];
        $ins->execute([$userId, $from, $to, $cle, $d['libelle'], $d['section'], $d['unite'], (float)$val, (int)$d['ordre']]);
    }

    if ($manuels) {
        $insm = $db->prepare("INSERT INTO objectifs_valeurs (user_id, periode_debut, periode_fin, cle, valeur) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)");
        foreach ($manuels as $cle => $val) {
            if (!isset($defs[$cle])) continue;
            $insm->execute([$userId, $from, $to, $cle, (float)$val]);
        }
    }
}

/** Bornes par défaut d'une période : mois courant. */
function objectifsDefaultPeriod() {
    return [date('Y-m-01'), date('Y-m-t')];
}

/** Liste des utilisateurs (pour le sélecteur administrateur). */
function getObjectifsConseillers($db) {
    try {
        return $db->query("SELECT id, prenom, nom FROM users ORDER BY nom, prenom")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/** Un bouton barré + progression, sans dépendance externe. */
function objectifsPercent($attendu, $realise) {
    $attendu = (float)$attendu;
    if ($attendu <= 0) return null;
    return round((float)$realise / $attendu * 100, 0);
}

/** Formatage d'un montant/nombre selon l'unité. */
function formatKpi($value, $unite) {
    $value = (float)$value;
    $txt = abs($value - round($value)) < 0.01 ? number_format($value, 0, ',', ' ') : number_format($value, 1, ',', ' ');
    if ($unite === '€') return $txt . ' €';
    if ($unite === '%') return $txt . ' %';
    return $txt;
}
