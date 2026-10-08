<?php
/**
 * Barèmes réglementaires utilisés par les simulateurs (PTZ, frais de notaire, plafonds HCSF).
 * Gérés par l'administrateur (onglet « Barèmes ») ; chaque barème porte une date de référence et un indicateur « contrôlé ».
 * Les valeurs par défaut sont indicatives : tant qu'un barème n'est pas contrôlé, les simulateurs affichent un avertissement.
 */
function baremeCatalog() {
    require_once __DIR__ . '/../modules/ptz/bareme.php';
    return [
        'ptz' => [
            'label' => 'Prêt à taux zéro (PTZ)', 'icon' => 'fa-percent',
            'help' => 'Plafonds de ressources et de prix par zone, quotités par tranche et type de bien, durées et différés.',
            'default' => ptzDefaultBareme(),
        ],
        'notaire' => [
            'label' => 'Frais de notaire', 'icon' => 'fa-scale-balanced',
            'help' => 'Émoluments (tranches), droits de mutation, taxes. « departements » (facultatif) : {"31": {"nom": "Haute-Garonne", "taux": 4.5}} ajoute un choix par département dans le simulateur.',
            'default' => [
                'emoluments' => [[6500, 3.870], [17000, 1.596], [60000, 1.064], [null, 0.799]], // [borne haute, taux %] (null = au-delà)
                'droits_neuf' => 0.715, 'taxe_communale' => 1.2, 'frais_assiette' => 2.37, 'tva' => 20, 'csi' => 0.1,
                'taux_departemental_defaut' => 4.5, 'departements' => new stdClass(),
            ],
        ],
        'hcsf' => [
            'label' => 'Plafonds HCSF (endettement et durée)', 'icon' => 'fa-gauge-high',
            'help' => 'Taux d\'endettement maximal et durées maximales recommandées pour les crédits immobiliers.',
            'default' => ['taux_endettement_max' => 35, 'duree_max_annees' => 25, 'duree_max_annees_neuf' => 27],
        ],
    ];
}

function baremeEnsureSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS baremes (
        cle VARCHAR(30) PRIMARY KEY,
        data MEDIUMTEXT NOT NULL,
        valide TINYINT(1) NOT NULL DEFAULT 0,
        date_reference DATE DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Contrôle de structure d'un barème saisi ; renvoie un message d'erreur ou null. */
function baremeCheck($cle, $d) {
    if (!is_array($d)) return 'JSON invalide.';
    if ($cle === 'ptz') return ptzCheckBareme($d);
    if ($cle === 'notaire') {
        if (!isset($d['emoluments']) || !is_array($d['emoluments']) || !$d['emoluments']) return 'Émoluments : au moins une tranche attendue.';
        foreach ($d['emoluments'] as $t) if (!is_array($t) || count($t) !== 2 || !is_numeric($t[1])) return 'Émoluments : chaque tranche est [borne haute ou null, taux].';
        foreach (['droits_neuf', 'taxe_communale', 'frais_assiette', 'tva', 'csi', 'taux_departemental_defaut'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k])) return "Valeur numérique manquante : $k";
        foreach ((array)($d['departements'] ?? []) as $dep) if (!is_array($dep) || !isset($dep['taux']) || !is_numeric($dep['taux'])) return 'Départements : {"code": {"nom": "…", "taux": 4.5}} attendu.';
        return null;
    }
    if ($cle === 'hcsf') {
        foreach (['taux_endettement_max', 'duree_max_annees', 'duree_max_annees_neuf'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur numérique positive manquante : $k";
        return null;
    }
    return 'Barème inconnu.';
}

/** Barème : ['data' => tableau, 'valide' => bool, 'date' => 'Y-m-d'|null, 'defaut' => bool (jamais enregistré)] */
function baremeGet($cle) {
    $cat = baremeCatalog();
    $out = ['data' => json_decode(json_encode($cat[$cle]['default'] ?? []), true), 'valide' => false, 'date' => null, 'defaut' => true];
    try {
        baremeEnsureSchema();
        $st = getDB()->prepare("SELECT data, valide, date_reference FROM baremes WHERE cle = ?");
        $st->execute([$cle]);
        $row = $st->fetch();
        if (!$row && $cle === 'ptz') { // reprise de l'ancien emplacement du barème PTZ
            try {
                $old = getDB()->query("SELECT data FROM ptz_bareme WHERE id = 1")->fetchColumn();
                $b = $old ? json_decode($old, true) : null;
                if (is_array($b) && !baremeCheck('ptz', $b)) return ['data' => $b, 'valide' => !empty($b['valide']), 'date' => null, 'defaut' => false];
            } catch (Exception $e) {}
        }
        if ($row) {
            $d = json_decode($row['data'], true);
            if (is_array($d) && !baremeCheck($cle, $d)) $out = ['data' => $d, 'valide' => (bool)$row['valide'], 'date' => $row['date_reference'], 'defaut' => false];
        }
    } catch (Exception $e) {}
    return $out;
}

function baremeSave($cle, array $data, $valide, $date, $userId) {
    baremeEnsureSchema();
    getDB()->prepare("REPLACE INTO baremes (cle, data, valide, date_reference, updated_by) VALUES (?, ?, ?, ?, ?)")
        ->execute([$cle, json_encode($data, JSON_UNESCAPED_UNICODE), $valide ? 1 : 0, $date ?: null, $userId]);
}

/** 'ok' | 'provisoire' (non contrôlé) | 'ancien' (date de référence de plus de 12 mois ou absente) */
function baremeStatut(array $b) {
    if (!$b['valide']) return 'provisoire';
    if (!$b['date'] || strtotime($b['date']) < strtotime('-12 months')) return 'ancien';
    return 'ok';
}
function baremesAReviser() {
    $n = 0;
    foreach (array_keys(baremeCatalog()) as $k) if (baremeStatut(baremeGet($k)) !== 'ok') $n++;
    return $n;
}
