<?php
$pageTitle = 'Crédit immobilier';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/baremes.php';
$db = getDB();
$userId = getCurrentUserId();

// ── MIGRATIONS ───────────────────────────────────────────────────────────────
$migrations = [
    "ALTER TABLE credit_immobilier ADD COLUMN notes TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN workflow_status VARCHAR(50) DEFAULT 'etude'",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_demande_cegc DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_retour_cegc DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_cegc_accord TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_cegc_refus TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_creation_cnp DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_retour_cnp DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_edition_liasse DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_signature_liasse DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_envoi_conformite DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_retour_conformite DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_conformite_conforme TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_conformite_non_conforme TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_conformite_motif TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_edition_offres_dt DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_accuse_reception DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_j11 DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_offre_signee_date DATE DEFAULT NULL",
    // nouvelles — Client
    "ALTER TABLE credit_immobilier ADD COLUMN banque_de_france VARCHAR(2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN drc VARCHAR(2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN topcc VARCHAR(2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN primo_accedant TINYINT(1) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN statut_occupation VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN nb_personnes_foyer INT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN nb_enfants INT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN revenus_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN charges_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN epargne_json TEXT DEFAULT NULL",
    // nouvelles — Projet
    "ALTER TABLE credit_immobilier ADD COLUMN usage_bien VARCHAR(5) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN usage_rl_type VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN mode_occupation VARCHAR(20) DEFAULT NULL",
    // nouvelles — Financement
    "ALTER TABLE credit_immobilier ADD COLUMN dont_mobilier_financable DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN frais_midi_epargne TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN montant_midi_epargne DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN frais_negociation DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN frais_divers DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN tva_financee DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN garantie_type VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN garantie_montant DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ade_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN ptz_actif TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ptz_montant DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ptz_duree INT DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_actif TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_montant DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_duree INT DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_bouquets TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_nb_bouquets INT DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN ecoptz_performance_globale TINYINT(1) DEFAULT 0",
    // nouvelles — Gestion admin
    "ALTER TABLE credit_immobilier ADD COLUMN ade_envoyee_le DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN ade_retour_le DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN ade_reponse VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN date_prelevement DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN notaire_nom VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN notaire_adresse TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN date_signature_notaire_prev DATE DEFAULT NULL",
    // nouvelles — Pièces (dates)
    "ALTER TABLE credit_immobilier ADD COLUMN doc_ji_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_jd_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_ir_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_contrat_travail_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_bulletins_salaire_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_justif_propriete_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_releves_externes_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_epargnes_externes_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_devis TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN doc_devis_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_formulaire_emprunteur TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_formulaire_emprunteur_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_formulaire_entreprises TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_formulaire_entreprises_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_ademe_emprunteur_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_ademe_entreprises_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_dpe_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_audit_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN eco_devis_travaux_date DATE DEFAULT NULL",
    // nouvelles — Suivi & Signature
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_conformite_reponse VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_signature_definitive DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN suivi_date_versement_notaire DATE DEFAULT NULL",
    // refonte : emprunteurs, projet, lignes de crédit, MRH
    "ALTER TABLE credit_immobilier ADD COLUMN adresse_bien TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN emprunteurs_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN primo_accedant_statut VARCHAR(30) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN nb_personnes_charge_supp INT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN enfants_ages_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN type_projet VARCHAR(30) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN type_acquisition VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN type_propriete VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN type_logement VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN nb_logements INT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN date_fin_construction DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN surface_habitable DECIMAL(8,2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN bien_lat DECIMAL(10,7) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN bien_lon DECIMAL(10,7) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN dpe_etiquette VARCHAR(1) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN dpe_ges VARCHAR(1) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN dpe_numero VARCHAR(30) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN dpe_date DATE DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN dpe_conso DECIMAL(8,2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN lignes_credit_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN doublissimo TINYINT(1) DEFAULT 0",
    "ALTER TABLE credit_immobilier ADD COLUMN mrh_montant_devis DECIMAL(10,2) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN mrh_formule VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN mrh_options_json TEXT DEFAULT NULL",
    "ALTER TABLE credit_immobilier ADD COLUMN taeg_assurance VARCHAR(5) DEFAULT 'MIN'",
];
foreach ($migrations as $sql) { try { $db->exec($sql); } catch (Exception $e) {} }

try {
    $db->exec("CREATE TABLE IF NOT EXISTS credit_immo_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        credit_immo_id INT NOT NULL,
        user_id INT NOT NULL,
        note TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ci (credit_immo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ── HELPERS ──────────────────────────────────────────────────────────────────
function nullIfEmpty($v) { return ($v !== null && $v !== '') ? $v : null; }
function d2n($v) { return ($v !== null && $v !== '') ? (float)$v : 0; }
function owns($db, $id, $userId) {
    $s = $db->prepare("SELECT id FROM credit_immobilier WHERE id=? AND user_id=?");
    $s->execute([$id, $userId]);
    return (bool)$s->fetch();
}

// ── AJAX ─────────────────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'budget') {
        $s = $db->prepare("SELECT * FROM calculateur_budget WHERE user_id=?");
        $s->execute([$userId]);
        echo json_encode($s->fetch() ?: ['error'=>'no_budget']);
        exit;
    }
    if ($_GET['ajax'] === 'notes' && isset($_GET['dossier_id'])) {
        $did = (int)$_GET['dossier_id'];
        if (!owns($db, $did, $userId)) { echo json_encode([]); exit; }
        $s = $db->prepare("SELECT n.id, n.note, n.created_at, n.updated_at,
            CONCAT(u.prenom,' ',u.nom) AS conseiller
            FROM credit_immo_notes n JOIN users u ON u.id=n.user_id
            WHERE n.credit_immo_id=? ORDER BY n.created_at DESC");
        $s->execute([$did]);
        echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    echo json_encode(['error'=>'unknown']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $act = $_POST['ajax_action'];
    if ($act === 'save_note') {
        $did   = (int)($_POST['dossier_id']??0);
        $nid   = (int)($_POST['note_id']??0);
        $txt   = trim($_POST['note']??'');
        if (!$did || !$txt || !owns($db,$did,$userId)) { echo json_encode(['success'=>false]); exit; }
        if ($nid > 0) {
            $s = $db->prepare("UPDATE credit_immo_notes SET note=?,updated_at=NOW() WHERE id=? AND user_id=?");
            $s->execute([$txt,$nid,$userId]);
            echo json_encode(['success'=>true,'id'=>$nid]);
        } else {
            $s = $db->prepare("INSERT INTO credit_immo_notes (credit_immo_id,user_id,note) VALUES (?,?,?)");
            $s->execute([$did,$userId,$txt]);
            echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
        }
        exit;
    }
    if ($act === 'delete_note') {
        $nid = (int)($_POST['note_id']??0);
        $s = $db->prepare("DELETE FROM credit_immo_notes WHERE id=? AND user_id=?");
        $s->execute([$nid,$userId]);
        echo json_encode(['success'=>true]);
        exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// ── ENREGISTREMENT (ajout / modification) ───────────────────────────────────
// Liste JSON saisie côté navigateur : on la revalide (tableau, taille bornée) avant de la stocker
function ciJsonList($raw, $max = 50) {
    $a = json_decode((string)$raw, true);
    return json_encode(is_array($a) ? array_slice(array_values($a), 0, $max) : [], JSON_UNESCAPED_UNICODE);
}

// Toutes les colonnes écrites à partir du formulaire : [colonne => valeur]
function ciCollect($p) {
    $flag  = fn($k) => isset($p[$k]) ? 1 : 0;
    $date  = fn($k) => nullIfEmpty($p[$k] ?? null);
    $intn  = fn($k) => (($p[$k] ?? '') !== '') ? (int)$p[$k] : null;
    $decn  = fn($k) => (($p[$k] ?? '') !== '') ? (float)$p[$k] : null;
    $enum  = fn($k, $allowed) => in_array($p[$k] ?? '', $allowed, true) ? $p[$k] : null;
    $text  = fn($k, $max = 255) => mb_substr(trim((string)($p[$k] ?? '')), 0, $max);

    $emps = json_decode($p['emprunteurs_json'] ?? '[]', true);
    $emps = is_array($emps) ? array_slice(array_values($emps), 0, 2) : [];
    foreach ($emps as &$em) { // champs texte bornés
        $em = is_array($em) ? $em : [];
        $em['num_personne'] = mb_substr(trim((string)($em['num_personne'] ?? '')), 0, 50);
        $em['nom'] = mb_substr(trim((string)($em['nom'] ?? '')), 0, 100);
    }
    unset($em);
    $e1 = $emps[0] ?? [];
    $okko = fn($v) => in_array($v ?? '', ['OK', 'KO'], true) ? $v : null;

    $lignes = json_decode($p['lignes_credit_json'] ?? '[]', true);
    $lignes = is_array($lignes) ? array_slice(array_values($lignes), 0, 20) : [];
    $l1 = $lignes[0] ?? [];
    $fraisDossier = array_sum(array_map(fn($l) => (float)($l['frais_dossier'] ?? 0), $lignes));

    // Usage : principal / secondaire / locatif principal / locatif secondaire
    $usage = $p['usage_choice'] ?? '';
    $usageBien = in_array($usage, ['RP', 'RS'], true) ? $usage : (in_array($usage, ['RL_PRINCIPALE', 'RL_SECONDAIRE'], true) ? 'RL' : null);
    $usageRl = $usageBien === 'RL' ? $usage : null;

    $primo = $enum('primo_accedant_statut', ['NON', 'OUI', 'OUI_AAH', 'OUI_CARTE_INVALIDITE', 'OUI_CATASTROPHE']);

    $c = [
        'numero_personne' => $text('numero_personne', 100),
        'type_client' => $enum('type_client', ['Particulier', 'Pro', 'Asso']) ?? 'Particulier',
        // Client
        'emprunteurs_json' => json_encode($emps, JSON_UNESCAPED_UNICODE),
        'banque_de_france' => $okko($e1['bdf'] ?? null), 'drc' => $okko($e1['drc'] ?? null), 'topcc' => $okko($e1['topcc'] ?? null), // colonnes historiques = emprunteur 1
        'revenus_json' => ciJsonList($p['revenus_json'] ?? '[]'), 'charges_json' => ciJsonList($p['charges_json'] ?? '[]'), 'epargne_json' => ciJsonList($p['epargne_json'] ?? '[]'),
        'primo_accedant_statut' => $primo, 'primo_accedant' => $primo === null ? null : ($primo === 'NON' ? 0 : 1),
        'statut_occupation' => $enum('statut_occupation', ['LOCATAIRE_HLM', 'AUTRE_LOCATAIRE', 'LOGE_GRATUIT', 'AUTRE']),
        'nb_personnes_foyer' => $intn('nb_personnes_foyer'), 'nb_enfants' => $intn('nb_enfants'),
        'enfants_ages_json' => ciJsonList($p['enfants_ages_json'] ?? '[]', 20), 'nb_personnes_charge_supp' => $intn('nb_personnes_charge_supp'),
        // Projet
        'type_projet' => $enum('type_projet', ['ANCIEN_SANS_TRAVAUX', 'ANCIEN_AVEC_TRAVAUX', 'CONSTRUCTION_CCMI', 'CONSTRUCTION_SANS_CCMI', 'NEUF_VEFA']),
        'usage_bien' => $usageBien, 'usage_rl_type' => $usageRl,
        'mode_occupation' => $enum('mode_occupation', ['EMPRUNTEUR', 'ASCENDANT', 'DESCENDANT']),
        'montant_acquisition' => d2n($p['montant_acquisition'] ?? 0), 'dont_mobilier_financable' => d2n($p['dont_mobilier_financable'] ?? 0),
        'frais_notaire' => d2n($p['frais_notaire'] ?? 0), 'frais_negociation' => d2n($p['frais_negociation'] ?? 0),
        'frais_agence' => 0, // regroupés avec les frais de négociation
        'frais_divers' => d2n($p['frais_divers'] ?? 0),
        'adresse_bien' => $text('adresse_bien', 500), 'bien_lat' => $decn('bien_lat'), 'bien_lon' => $decn('bien_lon'),
        'type_acquisition' => $enum('type_acquisition', ['MAISON', 'APPARTEMENT']),
        'type_propriete' => $enum('type_propriete', ['NU_PROPRIETAIRE', 'USUFRUITIER', 'PLEINE_PROPRIETE', 'NON_PROPRIETAIRE']),
        'type_logement' => $enum('type_logement', ['T1', 'T1 bis', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']),
        'nb_logements' => $intn('nb_logements'), 'date_fin_construction' => $date('date_fin_construction'), 'surface_habitable' => $decn('surface_habitable'),
        'dpe_etiquette' => $enum('dpe_etiquette', ['A', 'B', 'C', 'D', 'E', 'F', 'G']), 'dpe_ges' => $enum('dpe_ges', ['A', 'B', 'C', 'D', 'E', 'F', 'G']),
        'dpe_numero' => preg_replace('/[^0-9A-Za-z]/', '', $text('dpe_numero', 30)) ?: null, 'dpe_date' => $date('dpe_date'), 'dpe_conso' => $decn('dpe_conso'),
        // Financement
        'apport' => d2n($p['apport'] ?? 0),
        'garantie_type' => $enum('garantie_type', ['CEGC', 'SACCEF', 'HYPOTHEQUE']), 'garantie_montant' => d2n($p['garantie_montant'] ?? 0),
        'tva_financee' => d2n($p['tva_financee'] ?? 0),
        'frais_midi_epargne' => $flag('frais_midi_epargne'), 'montant_midi_epargne' => d2n($p['montant_midi_epargne'] ?? 0),
        'lignes_credit_json' => json_encode($lignes, JSON_UNESCAPED_UNICODE), 'doublissimo' => $flag('doublissimo'),
        // colonnes historiques = première ligne de crédit et total des frais de dossier
        'taux_emprunt' => min(99.999, (float)($l1['taux'] ?? 0)), 'duree_emprunt' => (int)($l1['duree'] ?? 0), 'frais_dossier' => $fraisDossier,
        'ade_json' => ciJsonList($p['ade_json'] ?? '[]'),
        'taeg_assurance' => $enum('taeg_assurance', ['MIN', 'ALL', 'EMP1', 'EMP2']) ?? 'MIN',
        'ptz_actif' => $flag('ptz_actif'), 'ptz_montant' => d2n($p['ptz_montant'] ?? 0), 'ptz_duree' => (int)($p['ptz_duree'] ?? 0),
        'ecoptz_actif' => $flag('ecoptz_actif'), 'ecoptz_montant' => d2n($p['ecoptz_montant'] ?? 0), 'ecoptz_duree' => (int)($p['ecoptz_duree'] ?? 0),
        'ecoptz_bouquets' => $flag('ecoptz_bouquets'), 'ecoptz_nb_bouquets' => (int)($p['ecoptz_nb_bouquets'] ?? 0),
        'ecoptz_performance_globale' => $flag('ecoptz_performance_globale'),
        // Gestion admin
        'ade_envoyee_le' => $date('ade_envoyee_le'), 'ade_retour_le' => $date('ade_retour_le'), 'ade_reponse' => $enum('ade_reponse', ['ACCORD', 'REFUS']),
        'suivi_date_demande_cegc' => $date('suivi_date_demande_cegc'), 'suivi_date_retour_cegc' => $date('suivi_date_retour_cegc'),
        'suivi_cegc_accord' => $flag('suivi_cegc_accord') && ($p['suivi_cegc_accord'] ?? 0) == 1 ? 1 : 0,
        'suivi_cegc_refus' => $flag('suivi_cegc_refus') && ($p['suivi_cegc_refus'] ?? 0) == 1 ? 1 : 0,
        'date_prelevement' => $date('date_prelevement'), 'notaire_nom' => $text('notaire_nom'), 'notaire_adresse' => $text('notaire_adresse', 1000),
        'date_signature_notaire_prev' => $date('date_signature_notaire_prev'),
        // Suivi
        'suivi_date_edition_liasse' => $date('suivi_date_edition_liasse'),
        'suivi_date_envoi_conformite' => $date('suivi_date_envoi_conformite'), 'suivi_date_retour_conformite' => $date('suivi_date_retour_conformite'),
        'suivi_conformite_reponse' => $enum('suivi_conformite_reponse', ['CONFORME', 'NON_CONFORME']), 'suivi_conformite_motif' => $text('suivi_conformite_motif', 1000),
        'suivi_date_edition_offres_dt' => $date('suivi_date_edition_offres_dt'), 'suivi_date_accuse_reception' => $date('suivi_date_accuse_reception'),
        'suivi_date_j11' => $date('suivi_date_j11'),
        'suivi_date_signature_definitive' => $date('suivi_date_signature_definitive'), 'suivi_date_versement_notaire' => $date('suivi_date_versement_notaire'),
        'workflow_status' => $enum('workflow_status', ['etude', 'dossier_complet', 'synthese_envoyee', 'controle', 'edition_offres', 'envoi_signature', 'offre_signee', 'deblocage', 'termine', 'refuse']) ?? 'etude',
        // MRH
        'mrh_montant_devis' => $decn('mrh_montant_devis'), 'mrh_formule' => $text('mrh_formule', 100), 'mrh_options_json' => ciJsonList($p['mrh_options_json'] ?? '[]', 30),
    ];
    // Pièces reçues (case + date)
    foreach (['doc_ji', 'doc_jd', 'doc_ir', 'doc_contrat_travail', 'doc_bulletins_salaire', 'doc_justif_propriete', 'doc_releves_externes', 'doc_epargnes_externes', 'doc_devis',
              'eco_formulaire_emprunteur', 'eco_formulaire_entreprises', 'eco_ademe_emprunteur', 'eco_ademe_entreprises', 'eco_dpe', 'eco_audit', 'eco_devis_travaux'] as $k) {
        $c[$k] = $flag($k);
        $c[$k . '_date'] = $date($k . '_date');
    }
    return $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $newId = 0;
    try {
        $cols = ciCollect($_POST);
        $sql = 'INSERT INTO credit_immobilier (user_id, date_ajout, ' . implode(', ', array_keys($cols)) . ') VALUES (?, CURDATE(), ' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $db->prepare($sql)->execute(array_merge([$userId], array_values($cols)));
        $newId = $db->lastInsertId();
    } catch (Exception $e) { error_log('[ci] INSERT:' . $e->getMessage()); }
    header('Location: index.php' . ($newId ? '?open=' . $newId : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id = (int)$_POST['id'];
    try {
        $cols = ciCollect($_POST);
        $sql = 'UPDATE credit_immobilier SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($cols))) . ', updated_at = NOW() WHERE id = ? AND user_id = ?';
        $db->prepare($sql)->execute(array_merge(array_values($cols), [$id, $userId]));
    } catch (Exception $e) { error_log('[ci] UPDATE:' . $e->getMessage()); }
    header('Location: index.php?open=' . $id);
    exit;
}

// ── POST DELETE ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    $s = $db->prepare("DELETE FROM credit_immobilier WHERE id=? AND user_id=?");
    $s->execute([(int)$_POST['id'],$userId]);
    header('Location: index.php'); exit;
}

// ── POST DUPLICATION (copie d'un dossier pour tester un autre scénario) ──────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='duplicate') {
    $id = (int)$_POST['id'];
    $s = $db->prepare("SELECT * FROM credit_immobilier WHERE id=? AND user_id=?");
    $s->execute([$id, $userId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    $newId = 0;
    if ($row) {
        // Suivi administratif, pièces et dates repartent à zéro (valeur par défaut de la colonne)
        $defaults = [];
        foreach ($db->query("SHOW COLUMNS FROM credit_immobilier")->fetchAll(PDO::FETCH_ASSOC) as $col) $defaults[$col['Field']] = $col['Default'];
        $reset = fn($k) => preg_match('/^(suivi_|doc_|eco_)/', $k) || in_array($k, ['ade_envoyee_le','ade_retour_le','ade_reponse','date_prelevement','date_signature_notaire_prev']);
        foreach (['id','created_at','updated_at','date_ajout','user_id'] as $k) unset($row[$k]);
        foreach ($row as $k => $v) if ($reset($k)) $row[$k] = $defaults[$k] ?? null;
        $row['workflow_status'] = 'etude';
        $row['numero_personne'] = mb_substr((string)$row['numero_personne'], 0, 88) . ' (copie)';
        try {
            $db->prepare('INSERT INTO credit_immobilier (user_id, date_ajout, ' . implode(', ', array_keys($row)) . ') VALUES (?, CURDATE(), ' . implode(', ', array_fill(0, count($row), '?')) . ')')
               ->execute(array_merge([$userId], array_values($row)));
            $newId = $db->lastInsertId();
        } catch (Exception $e) { error_log('[ci] DUPLICATE:' . $e->getMessage()); }
    }
    header('Location: index.php' . ($newId ? '?open=' . $newId : ''));
    exit;
}

// ── LISTE ────────────────────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM credit_immobilier WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$workflowLabels = [
    'etude'            => ['Étude en cours',     'secondary'],
    'dossier_complet'  => ['Dossier complet',     'info'],
    'synthese_envoyee' => ['Synthèse envoyée',    'primary'],
    'controle'         => ['Contrôle conformité', 'primary'],
    'edition_offres'   => ['Édition offres',      'warning'],
    'envoi_signature'  => ['Envoi signature',     'warning'],
    'offre_signee'     => ['Offre signée',        'success'],
    'deblocage'        => ['Déblocage fonds',     'success'],
    'termine'          => ['Terminé',             'dark'],
    'refuse'           => ['Refusé',              'danger'],
];

$cEnCours  = count(array_filter($dossiers, fn($d) => !in_array($d['workflow_status']??'etude', ['termine','refuse','offre_signee','deblocage'])));
$cSignees  = count(array_filter($dossiers, fn($d) => in_array($d['workflow_status']??'', ['offre_signee','deblocage','termine'])));
$cRefusees = count(array_filter($dossiers, fn($d) => ($d['workflow_status']??'')==='refuse'));
?>

<!-- ── STATS ─────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="stat-card"><div class="stat-number"><?= count($dossiers) ?></div><div class="stat-label">Total dossiers</div></div></div>
    <div class="col-md-2"><div class="stat-card" style="border-left-color:#0d6efd;"><div class="stat-number"><?= $cEnCours ?></div><div class="stat-label">En cours</div></div></div>
    <div class="col-md-2"><div class="stat-card stat-success"><div class="stat-number"><?= $cSignees ?></div><div class="stat-label">Signées/Terminées</div></div></div>
    <div class="col-md-2"><div class="stat-card" style="border-left-color:#dc3545;"><div class="stat-number"><?= $cRefusees ?></div><div class="stat-label">Refusées</div></div></div>
    <div class="col-md-2"><div class="stat-card" style="border-left-color:#fd7e14;"><div class="stat-number" id="kpiRelances">–</div><div class="stat-label">À relancer</div></div></div>
    <div class="col-12 d-flex align-items-center gap-2">
        <button class="btn btn-ce" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau dossier</button>
        <button class="btn btn-ce-outline" onclick="showCompareSelect()"><i class="fas fa-balance-scale"></i> Comparer</button>
        <button class="btn btn-ce-outline" onclick="toggleDash()"><i class="fas fa-chart-column"></i> Tableau de bord</button>
    </div>
</div>
<div id="ciDash" class="card mb-4" style="display:none"><div class="card-header d-flex justify-content-between align-items-center">
    <strong><i class="fas fa-chart-column"></i> Tableau de bord crédit</strong>
    <select id="ciDashPeriode" class="form-select form-select-sm w-auto" onchange="ciDashboard()"><option value="all">Toute la période</option><option value="30">30 derniers jours</option><option value="90">90 derniers jours</option><option value="365">12 derniers mois</option></select>
</div><div class="card-body" id="ciDashBody"></div></div>

<!-- ── TABLEAU ───────────────────────────────────────────────────────────── -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Dossiers crédit immobilier</h3>
        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchDossiers" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table" id="tableDossiers">
        <thead><tr>
            <th>Date</th><th>N° dossier</th><th>N° personne emprunteur(s)</th><th>Usage</th><th>Capital emprunté</th><th>Lignes</th>
            <th>Mensualité tout inclus</th><th>Endettement</th><th>Alertes</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($dossiers as $d):
            $wf=$d['workflow_status']??'etude'; $wfI=$workflowLabels[$wf]??['Inconnu','secondary']; ?>
            <tr>
                <td><?= formatDate($d['date_ajout']) ?></td>
                <td><strong><?= e($d['numero_personne']) ?></strong></td>
                <td id="pers_<?= $d['id'] ?>">—</td>
                <td id="usg_<?= $d['id'] ?>">—</td>
                <td id="cap_<?= $d['id'] ?>">—</td>
                <td id="nl_<?= $d['id'] ?>">—</td>
                <td id="mens_<?= $d['id'] ?>">—</td>
                <td id="tend_<?= $d['id'] ?>">—</td>
                <td id="al_<?= $d['id'] ?>">—</td>
                <td><span class="badge bg-<?= $wfI[1] ?>"><?= $wfI[0] ?></span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDossier(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showAmortissement(<?= $d['id'] ?>)" title="Amortissement"><i class="fas fa-table"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showSimulation(<?= $d['id'] ?>)" title="Simulation"><i class="fas fa-calculator"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="printSynthese(<?= $d['id'] ?>)" title="Fiche synthèse (1 page, à agrafer sur la sous-chemise)"><i class="fas fa-file-alt"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="printDossier(<?= $d['id'] ?>)" title="Dossier complet (impression détaillée)"><i class="fas fa-print"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Dupliquer ce dossier pour tester un autre scénario ? (le suivi et les pièces repartent à zéro)')">
                        <input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-ce-outline" title="Dupliquer (autre scénario)"><i class="fas fa-copy"></i></button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce dossier ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ── MODALS ────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau dossier crédit immobilier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="addContent"></div>
    </div></div>
</div>
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du dossier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="detailContent"></div>
    </div></div>
</div>
<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le dossier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="editContent"></div>
    </div></div>
</div>
<div class="modal fade" id="amortModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-table"></i> Tableau d'amortissement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="amortContent"></div>
    </div></div>
</div>
<div class="modal fade" id="simulModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-calculator"></i> Simulation "Et si..."</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="simulContent"></div>
    </div></div>
</div>
<div class="modal fade" id="compareModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-balance-scale"></i> Comparaison de scénarios</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="compareContent"></div>
    </div></div>
</div>
<div id="printArea" class="print-dossier" style="display:none;"></div>

<script>
const dossiersData   = <?= json_encode(array_values($dossiers)) ?>;
const workflowLabels = <?= json_encode($workflowLabels) ?>;
const conseillerData = <?= json_encode(['nom' => $currentUser['nom'] ?? '', 'prenom' => $currentUser['prenom'] ?? '', 'email' => $currentUser['email_pro'] ?? '', 'tel' => $currentUser['tel_pro'] ?? ''], JSON_UNESCAPED_UNICODE) ?>;
const ciBaremes = <?= json_encode(['tauxEndettementMax' => (float)baremeGet('hcsf')['data']['taux_endettement_max']]) ?>;
const workflowSteps  = ['etude','dossier_complet','synthese_envoyee','controle','edition_offres','envoi_signature','offre_signee','deblocage','termine'];
</script>
<script src="ci.js?v=<?= (int)@filemtime(__DIR__ . '/ci.js') ?>"></script>
<script src="ci_extra.js?v=<?= (int)@filemtime(__DIR__ . '/ci_extra.js') ?>"></script>
<script>
// Auto-ouverture du dossier après enregistrement ou depuis la recherche globale
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<style>
.bg-orange{background-color:#fd7e14!important}
.ci-json-list{border:1px solid #dee2e6;border-radius:6px;padding:8px;background:#fafafa}
.ci-json-row{background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:6px 8px;margin-bottom:6px;position:relative}
.ci-json-row .btn-rm{position:absolute;top:4px;right:4px}
.taux-endett-live{font-weight:700;font-size:1.1em}
@media print{
    @page{size:A4 portrait;margin:7mm 8mm}
    body *{visibility:hidden!important}
    #printArea,#printArea *{visibility:visible!important}
    #printArea{display:block!important;position:absolute;left:0;top:0;width:100%;font-family:'Segoe UI',Arial,sans-serif;font-size:7.8pt;color:#1a1a1a;line-height:1.3}
}

/* Fiche synthèse : 1 page A4, lisible en noir et blanc */
.sy-wrap{font-family:Arial,Helvetica,sans-serif;font-size:8pt;color:#000;line-height:1.25}
.sy-head{display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:2px solid #000;padding-bottom:4px;margin-bottom:5px}
.sy-logo{height:34px;width:auto}
.sy-title{flex:1;text-align:center;font-size:13pt;font-weight:700;letter-spacing:.5px}
.sy-meta{text-align:right;font-size:7.5pt;min-width:150px}
.sy-sec{border:1px solid #000;margin-bottom:5px;page-break-inside:avoid}
.sy-sec-t{background:#ddd;border-bottom:1px solid #000;font-weight:700;font-size:8pt;padding:1px 5px}
.sy-body{padding:3px 5px}
.sy-cols{display:flex;gap:6px}
.sy-col{flex:1;min-width:0}
.sy-card{border:1px solid #888;padding:2px 5px}
.sy-card-t{font-weight:700;font-size:8.5pt}
.sy-small{font-size:7.5pt}
.sy-normal{font-weight:400;font-size:7.5pt}
.sy-k{color:#333;width:44%}
.sy-sub2{font-weight:700;border-bottom:1px solid #000;margin-bottom:1px;font-size:6.6pt}
/* Dossier complet : mêmes blocs que la synthèse, caractères réduits pour tout tenir sur 1 page */
.sy-wrap.fc{font-size:6.8pt;line-height:1.15}
.fc .sy-head{padding-bottom:2px;margin-bottom:3px}
.fc .sy-logo{height:26px}
.fc .sy-title{font-size:10.5pt}
.fc .sy-meta{font-size:6.2pt;min-width:150px}
.fc .sy-conseiller{font-size:5.8pt}
.fc .sy-sec{margin-bottom:3px}
.fc .sy-sec-t{font-size:6.5pt;padding:0 4px}
.fc .sy-body{padding:2px 4px}
.fc .sy-cols{gap:4px}
.fc .sy-card{padding:1px 3px}
.fc .sy-card-t{font-size:7pt}
.fc .sy-small{font-size:6.7pt}
.fc table.sy-t td,.fc table.sy-t th{font-size:6.6pt;padding:0 2px}
.fc .sy-kpi{gap:3px;margin-top:2px}
.fc .sy-kpi div{font-size:5.5pt;padding:1px}
.fc .sy-kpi b{font-size:7.6pt}
.fc .sy-dpe{font-size:10pt;padding:0 4px}
.fc .sy-foot{font-size:5.8pt;margin-top:2px;padding-top:2px}
.sy-conseiller{margin-top:2px;padding-top:2px;border-top:1px solid #888;font-size:7pt;line-height:1.25}
table.sy-t{width:100%;border-collapse:collapse}
table.sy-t td,table.sy-t th{padding:1px 3px;font-size:7.6pt;vertical-align:top}
table.sy-t th{text-align:left;border-bottom:1px solid #000;font-weight:700}
table.sy-t .r{text-align:right;white-space:nowrap}
table.sy-cmp td,table.sy-cmp th{font-size:8.5pt;padding:2px 5px}
table.sy-cmp td.sy-best{font-weight:700;background:#e9e9e9}
table.sy-t td.sy-sub{font-weight:700;border-bottom:1px solid #bbb;padding-top:3px}
table.sy-t tr.tot td{border-top:1px solid #000;font-weight:700}
.sy-grey{color:#555;font-style:italic}
.sy-kpi{display:flex;gap:4px;margin-top:4px}
.sy-kpi div{flex:1;border:1px solid #000;text-align:center;padding:2px;font-size:7pt}
.sy-kpi b{display:block;font-size:10pt}
.sy-kpi span{display:block;font-size:7pt}
.sy-dpe{display:inline-block;border:2px solid #000;font-size:15pt;font-weight:700;line-height:1.1;padding:0 8px;margin-right:4px;vertical-align:middle}
.sy-foot{border-top:1px solid #000;margin-top:4px;padding-top:3px;font-size:7pt;text-align:center}
.note-card{background:#fffde7;border:1px solid #ffe082;border-radius:6px;padding:10px 12px;margin-bottom:8px}
.note-card .note-meta{font-size:.75em;color:#888;margin-bottom:4px}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
