<?php
$pageTitle = 'Crédit immobilier';
require_once __DIR__ . '/../../templates/header.php';
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

// ── POST ADD ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $p = $_POST;
    try {
        $s = $db->prepare("INSERT INTO credit_immobilier (
            user_id,numero_personne,type_client,
            banque_de_france,drc,topcc,primo_accedant,statut_occupation,
            nb_personnes_foyer,nb_enfants,revenus_json,charges_json,epargne_json,
            usage_bien,usage_rl_type,mode_occupation,
            taux_emprunt,duree_emprunt,montant_acquisition,dont_mobilier_financable,
            frais_notaire,frais_dossier,frais_midi_epargne,montant_midi_epargne,
            frais_negociation,frais_divers,frais_agence,tva_financee,apport,
            garantie_type,garantie_montant,ade_json,
            ptz_actif,ptz_montant,ptz_duree,
            ecoptz_actif,ecoptz_montant,ecoptz_duree,ecoptz_bouquets,ecoptz_nb_bouquets,ecoptz_performance_globale,
            ade_envoyee_le,ade_retour_le,ade_reponse,
            date_prelevement,notaire_nom,notaire_adresse,date_signature_notaire_prev,
            doc_ji,doc_ji_date,doc_jd,doc_jd_date,doc_ir,doc_ir_date,
            doc_contrat_travail,doc_contrat_travail_date,doc_bulletins_salaire,doc_bulletins_salaire_date,
            doc_justif_propriete,doc_justif_propriete_date,
            doc_releves_externes,doc_releves_externes_date,
            doc_epargnes_externes,doc_epargnes_externes_date,
            doc_devis,doc_devis_date,
            eco_formulaire_emprunteur,eco_formulaire_emprunteur_date,
            eco_formulaire_entreprises,eco_formulaire_entreprises_date,
            eco_ademe_emprunteur,eco_ademe_emprunteur_date,
            eco_ademe_entreprises,eco_ademe_entreprises_date,
            eco_dpe,eco_dpe_date,eco_audit,eco_audit_date,
            eco_devis_travaux,eco_devis_travaux_date,
            suivi_date_edition_liasse,
            suivi_date_envoi_conformite,suivi_date_retour_conformite,
            suivi_conformite_reponse,suivi_conformite_motif,
            suivi_date_edition_offres_dt,suivi_date_accuse_reception,suivi_date_j11,
            suivi_date_signature_definitive,suivi_date_versement_notaire,
            workflow_status,date_ajout
        ) VALUES (
            ?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,
            ?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?,
            ?,?,?, ?,?,?,?,?,?,
            ?,?,?, ?,?,?,?,
            ?,?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,
            ?,?,?,?, ?,?,?,?, ?,?,?,?,
            ?, ?,?,?,?, ?,?,?, ?,?,
            ?,CURDATE()
        )");
        $nb = fn($f) => ($p[$f]??''!=='')?((int)$p[$f]):null;
        $s->execute([
            $userId,$p['numero_personne']??'',$p['type_client']??'Particulier',
            nullIfEmpty($p['banque_de_france']??null),nullIfEmpty($p['drc']??null),nullIfEmpty($p['topcc']??null),
            ($p['primo_accedant']??''!=='')?((int)$p['primo_accedant']):null,
            nullIfEmpty($p['statut_occupation']??null),
            $nb('nb_personnes_foyer'),$nb('nb_enfants'),
            $p['revenus_json']??'[]',$p['charges_json']??'[]',$p['epargne_json']??'[]',
            nullIfEmpty($p['usage_bien']??null),nullIfEmpty($p['usage_rl_type']??null),nullIfEmpty($p['mode_occupation']??null),
            d2n($p['taux_emprunt']??0),(int)($p['duree_emprunt']??0),
            d2n($p['montant_acquisition']??0),d2n($p['dont_mobilier_financable']??0),
            d2n($p['frais_notaire']??0),d2n($p['frais_dossier']??0),
            isset($p['frais_midi_epargne'])?1:0,d2n($p['montant_midi_epargne']??0),
            d2n($p['frais_negociation']??0),d2n($p['frais_divers']??0),
            d2n($p['frais_agence']??0),d2n($p['tva_financee']??0),d2n($p['apport']??0),
            nullIfEmpty($p['garantie_type']??null),d2n($p['garantie_montant']??0),$p['ade_json']??'[]',
            isset($p['ptz_actif'])?1:0,d2n($p['ptz_montant']??0),(int)($p['ptz_duree']??0),
            isset($p['ecoptz_actif'])?1:0,d2n($p['ecoptz_montant']??0),(int)($p['ecoptz_duree']??0),
            isset($p['ecoptz_bouquets'])?1:0,(int)($p['ecoptz_nb_bouquets']??0),isset($p['ecoptz_performance_globale'])?1:0,
            nullIfEmpty($p['ade_envoyee_le']??null),nullIfEmpty($p['ade_retour_le']??null),nullIfEmpty($p['ade_reponse']??null),
            nullIfEmpty($p['date_prelevement']??null),$p['notaire_nom']??'',$p['notaire_adresse']??'',
            nullIfEmpty($p['date_signature_notaire_prev']??null),
            isset($p['doc_ji'])?1:0,nullIfEmpty($p['doc_ji_date']??null),
            isset($p['doc_jd'])?1:0,nullIfEmpty($p['doc_jd_date']??null),
            isset($p['doc_ir'])?1:0,nullIfEmpty($p['doc_ir_date']??null),
            isset($p['doc_contrat_travail'])?1:0,nullIfEmpty($p['doc_contrat_travail_date']??null),
            isset($p['doc_bulletins_salaire'])?1:0,nullIfEmpty($p['doc_bulletins_salaire_date']??null),
            isset($p['doc_justif_propriete'])?1:0,nullIfEmpty($p['doc_justif_propriete_date']??null),
            isset($p['doc_releves_externes'])?1:0,nullIfEmpty($p['doc_releves_externes_date']??null),
            isset($p['doc_epargnes_externes'])?1:0,nullIfEmpty($p['doc_epargnes_externes_date']??null),
            isset($p['doc_devis'])?1:0,nullIfEmpty($p['doc_devis_date']??null),
            isset($p['eco_formulaire_emprunteur'])?1:0,nullIfEmpty($p['eco_formulaire_emprunteur_date']??null),
            isset($p['eco_formulaire_entreprises'])?1:0,nullIfEmpty($p['eco_formulaire_entreprises_date']??null),
            isset($p['eco_ademe_emprunteur'])?1:0,nullIfEmpty($p['eco_ademe_emprunteur_date']??null),
            isset($p['eco_ademe_entreprises'])?1:0,nullIfEmpty($p['eco_ademe_entreprises_date']??null),
            isset($p['eco_dpe'])?1:0,nullIfEmpty($p['eco_dpe_date']??null),
            isset($p['eco_audit'])?1:0,nullIfEmpty($p['eco_audit_date']??null),
            isset($p['eco_devis_travaux'])?1:0,nullIfEmpty($p['eco_devis_travaux_date']??null),
            nullIfEmpty($p['suivi_date_edition_liasse']??null),
            nullIfEmpty($p['suivi_date_envoi_conformite']??null),nullIfEmpty($p['suivi_date_retour_conformite']??null),
            nullIfEmpty($p['suivi_conformite_reponse']??null),$p['suivi_conformite_motif']??'',
            nullIfEmpty($p['suivi_date_edition_offres_dt']??null),nullIfEmpty($p['suivi_date_accuse_reception']??null),
            nullIfEmpty($p['suivi_date_j11']??null),
            nullIfEmpty($p['suivi_date_signature_definitive']??null),nullIfEmpty($p['suivi_date_versement_notaire']??null),
            $p['workflow_status']??'etude',
        ]);
        $newId = $db->lastInsertId();
    } catch (Exception $e) { error_log('[ci] INSERT:'.$e->getMessage()); $newId=0; }
    header('Location: index.php'.($newId?'?open='.$newId:''));
    exit;
}

// ── POST EDIT ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
    $id = (int)$_POST['id'];
    $p  = $_POST;
    try {
        $s = $db->prepare("UPDATE credit_immobilier SET
            numero_personne=?,type_client=?,
            banque_de_france=?,drc=?,topcc=?,primo_accedant=?,statut_occupation=?,
            nb_personnes_foyer=?,nb_enfants=?,revenus_json=?,charges_json=?,epargne_json=?,
            usage_bien=?,usage_rl_type=?,mode_occupation=?,
            taux_emprunt=?,duree_emprunt=?,montant_acquisition=?,dont_mobilier_financable=?,
            frais_notaire=?,frais_dossier=?,frais_midi_epargne=?,montant_midi_epargne=?,
            frais_negociation=?,frais_divers=?,frais_agence=?,tva_financee=?,apport=?,
            garantie_type=?,garantie_montant=?,ade_json=?,
            ptz_actif=?,ptz_montant=?,ptz_duree=?,
            ecoptz_actif=?,ecoptz_montant=?,ecoptz_duree=?,ecoptz_bouquets=?,ecoptz_nb_bouquets=?,ecoptz_performance_globale=?,
            ade_envoyee_le=?,ade_retour_le=?,ade_reponse=?,
            date_prelevement=?,notaire_nom=?,notaire_adresse=?,date_signature_notaire_prev=?,
            doc_ji=?,doc_ji_date=?,doc_jd=?,doc_jd_date=?,doc_ir=?,doc_ir_date=?,
            doc_contrat_travail=?,doc_contrat_travail_date=?,doc_bulletins_salaire=?,doc_bulletins_salaire_date=?,
            doc_justif_propriete=?,doc_justif_propriete_date=?,
            doc_releves_externes=?,doc_releves_externes_date=?,
            doc_epargnes_externes=?,doc_epargnes_externes_date=?,
            doc_devis=?,doc_devis_date=?,
            eco_formulaire_emprunteur=?,eco_formulaire_emprunteur_date=?,
            eco_formulaire_entreprises=?,eco_formulaire_entreprises_date=?,
            eco_ademe_emprunteur=?,eco_ademe_emprunteur_date=?,
            eco_ademe_entreprises=?,eco_ademe_entreprises_date=?,
            eco_dpe=?,eco_dpe_date=?,eco_audit=?,eco_audit_date=?,
            eco_devis_travaux=?,eco_devis_travaux_date=?,
            suivi_date_edition_liasse=?,
            suivi_date_envoi_conformite=?,suivi_date_retour_conformite=?,
            suivi_conformite_reponse=?,suivi_conformite_motif=?,
            suivi_date_edition_offres_dt=?,suivi_date_accuse_reception=?,suivi_date_j11=?,
            suivi_date_signature_definitive=?,suivi_date_versement_notaire=?,
            workflow_status=?,updated_at=NOW()
            WHERE id=? AND user_id=?");
        $nb = fn($f) => ($p[$f]??''!=='')?((int)$p[$f]):null;
        $s->execute([
            $p['numero_personne']??'',$p['type_client']??'Particulier',
            nullIfEmpty($p['banque_de_france']??null),nullIfEmpty($p['drc']??null),nullIfEmpty($p['topcc']??null),
            ($p['primo_accedant']??''!=='')?((int)$p['primo_accedant']):null,
            nullIfEmpty($p['statut_occupation']??null),
            $nb('nb_personnes_foyer'),$nb('nb_enfants'),
            $p['revenus_json']??'[]',$p['charges_json']??'[]',$p['epargne_json']??'[]',
            nullIfEmpty($p['usage_bien']??null),nullIfEmpty($p['usage_rl_type']??null),nullIfEmpty($p['mode_occupation']??null),
            d2n($p['taux_emprunt']??0),(int)($p['duree_emprunt']??0),
            d2n($p['montant_acquisition']??0),d2n($p['dont_mobilier_financable']??0),
            d2n($p['frais_notaire']??0),d2n($p['frais_dossier']??0),
            isset($p['frais_midi_epargne'])?1:0,d2n($p['montant_midi_epargne']??0),
            d2n($p['frais_negociation']??0),d2n($p['frais_divers']??0),
            d2n($p['frais_agence']??0),d2n($p['tva_financee']??0),d2n($p['apport']??0),
            nullIfEmpty($p['garantie_type']??null),d2n($p['garantie_montant']??0),$p['ade_json']??'[]',
            isset($p['ptz_actif'])?1:0,d2n($p['ptz_montant']??0),(int)($p['ptz_duree']??0),
            isset($p['ecoptz_actif'])?1:0,d2n($p['ecoptz_montant']??0),(int)($p['ecoptz_duree']??0),
            isset($p['ecoptz_bouquets'])?1:0,(int)($p['ecoptz_nb_bouquets']??0),isset($p['ecoptz_performance_globale'])?1:0,
            nullIfEmpty($p['ade_envoyee_le']??null),nullIfEmpty($p['ade_retour_le']??null),nullIfEmpty($p['ade_reponse']??null),
            nullIfEmpty($p['date_prelevement']??null),$p['notaire_nom']??'',$p['notaire_adresse']??'',
            nullIfEmpty($p['date_signature_notaire_prev']??null),
            isset($p['doc_ji'])?1:0,nullIfEmpty($p['doc_ji_date']??null),
            isset($p['doc_jd'])?1:0,nullIfEmpty($p['doc_jd_date']??null),
            isset($p['doc_ir'])?1:0,nullIfEmpty($p['doc_ir_date']??null),
            isset($p['doc_contrat_travail'])?1:0,nullIfEmpty($p['doc_contrat_travail_date']??null),
            isset($p['doc_bulletins_salaire'])?1:0,nullIfEmpty($p['doc_bulletins_salaire_date']??null),
            isset($p['doc_justif_propriete'])?1:0,nullIfEmpty($p['doc_justif_propriete_date']??null),
            isset($p['doc_releves_externes'])?1:0,nullIfEmpty($p['doc_releves_externes_date']??null),
            isset($p['doc_epargnes_externes'])?1:0,nullIfEmpty($p['doc_epargnes_externes_date']??null),
            isset($p['doc_devis'])?1:0,nullIfEmpty($p['doc_devis_date']??null),
            isset($p['eco_formulaire_emprunteur'])?1:0,nullIfEmpty($p['eco_formulaire_emprunteur_date']??null),
            isset($p['eco_formulaire_entreprises'])?1:0,nullIfEmpty($p['eco_formulaire_entreprises_date']??null),
            isset($p['eco_ademe_emprunteur'])?1:0,nullIfEmpty($p['eco_ademe_emprunteur_date']??null),
            isset($p['eco_ademe_entreprises'])?1:0,nullIfEmpty($p['eco_ademe_entreprises_date']??null),
            isset($p['eco_dpe'])?1:0,nullIfEmpty($p['eco_dpe_date']??null),
            isset($p['eco_audit'])?1:0,nullIfEmpty($p['eco_audit_date']??null),
            isset($p['eco_devis_travaux'])?1:0,nullIfEmpty($p['eco_devis_travaux_date']??null),
            nullIfEmpty($p['suivi_date_edition_liasse']??null),
            nullIfEmpty($p['suivi_date_envoi_conformite']??null),nullIfEmpty($p['suivi_date_retour_conformite']??null),
            nullIfEmpty($p['suivi_conformite_reponse']??null),$p['suivi_conformite_motif']??'',
            nullIfEmpty($p['suivi_date_edition_offres_dt']??null),nullIfEmpty($p['suivi_date_accuse_reception']??null),
            nullIfEmpty($p['suivi_date_j11']??null),
            nullIfEmpty($p['suivi_date_signature_definitive']??null),nullIfEmpty($p['suivi_date_versement_notaire']??null),
            $p['workflow_status']??'etude',
            $id,$userId,
        ]);
    } catch (Exception $e) { error_log('[ci] UPDATE:'.$e->getMessage()); }
    header('Location: index.php?open='.$id);
    exit;
}

// ── POST DELETE ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    $s = $db->prepare("DELETE FROM credit_immobilier WHERE id=? AND user_id=?");
    $s->execute([(int)$_POST['id'],$userId]);
    header('Location: index.php'); exit;
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
    <div class="col-md-4 d-flex align-items-center gap-2">
        <button class="btn btn-ce" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau dossier</button>
        <button class="btn btn-ce-outline" onclick="showCompareSelect()"><i class="fas fa-balance-scale"></i> Comparer</button>
    </div>
</div>

<!-- ── TABLEAU ───────────────────────────────────────────────────────────── -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Dossiers crédit immobilier</h3>
        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchDossiers" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table" id="tableDossiers">
        <thead><tr>
            <th>Date</th><th>N° personne</th><th>Usage</th><th>Taux</th><th>Durée</th>
            <th>Mensualité glob.</th><th>Endettement</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($dossiers as $d):
            $wf=$d['workflow_status']??'etude'; $wfI=$workflowLabels[$wf]??['Inconnu','secondary']; ?>
            <tr>
                <td><?= formatDate($d['date_ajout']) ?></td>
                <td><?= e($d['numero_personne']) ?></td>
                <td><?= e($d['usage_bien']?:($d['type_residence']?:'—')) ?></td>
                <td><?= $d['taux_emprunt'] ?> %</td>
                <td><?= $d['duree_emprunt'] ?> mois</td>
                <td id="mens_<?= $d['id'] ?>">—</td>
                <td id="tend_<?= $d['id'] ?>">—</td>
                <td><span class="badge bg-<?= $wfI[1] ?>"><?= $wfI[0] ?></span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDossier(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showAmortissement(<?= $d['id'] ?>)" title="Amortissement"><i class="fas fa-table"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showSimulation(<?= $d['id'] ?>)" title="Simulation"><i class="fas fa-calculator"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="printDossier(<?= $d['id'] ?>)" title="Imprimer"><i class="fas fa-print"></i></button>
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
const workflowSteps  = ['etude','dossier_complet','synthese_envoyee','controle','edition_offres','envoi_signature','offre_signee','deblocage','termine'];
</script>
<script src="ci.js"></script>
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
.pr-wrap{font-family:'Segoe UI',Arial,sans-serif;font-size:7.8pt;color:#1a1a1a}
.pr-header{display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #1B6234;padding-bottom:5px;margin-bottom:7px}
.pr-logo{height:38px;width:auto;object-fit:contain}
.pr-header-center{text-align:center;flex:1;padding:0 10px}
.pr-title{font-size:13pt;font-weight:700;color:#1B6234;letter-spacing:1px}
.pr-subtitle{font-size:8.5pt;color:#444;margin-top:2px}
.pr-header-right{text-align:right;min-width:90px}
.pr-date{font-size:7pt;color:#666}
.pr-statut{font-size:7.5pt;font-weight:600;color:#1B6234;margin-top:2px;background:#e8f5ee;padding:2px 5px;border-radius:3px;display:inline-block}
.pr-cols{display:flex;gap:6px;margin-bottom:6px}
.pr-col{flex:1;min-width:0}
.pr-section{border:1px solid #d0e8d8;border-radius:4px;padding:5px 6px;margin-bottom:6px;background:#fff}
.pr-section-title{font-size:7.5pt;font-weight:700;color:#fff;background:#1B6234;padding:2px 6px;border-radius:2px;margin:-5px -6px 5px -6px;letter-spacing:.5px}
.pr-sub-title{font-size:7pt;font-weight:700;color:#1B6234;background:#edf7f1;padding:1px 4px}
.pr-wrap table{width:100%;border-collapse:collapse}
.pr-wrap table td{padding:2px 5px;font-size:7.5pt;border-bottom:1px solid #eee;vertical-align:top}
.pr-wrap table td.lbl{color:#555;width:48%}
.pr-wrap table td.val{font-weight:600;color:#111}
.pr-kpi-row{display:flex;gap:5px;margin-top:5px}
.pr-kpi{flex:1;text-align:center;border-radius:4px;padding:4px 3px}
.pr-kpi-val{font-size:10pt;font-weight:700}
.pr-kpi-lbl{font-size:6.5pt;margin-top:1px;opacity:.85}
.pr-kpi-green{background:#e8f5ee;color:#1B6234;border:1px solid #b2dfc2}
.pr-kpi-primary{background:#1B6234;color:#fff}
.pr-kpi-ok{background:#e8f5ee;color:#1B6234;border:1px solid #b2dfc2}
.pr-kpi-warn{background:#fff3cd;color:#856404;border:1px solid #ffe08a}
.pr-kpi-danger{background:#f8d7da;color:#842029;border:1px solid #f5c2c7}
.ck-ok{color:#1B6234;font-weight:700}
.ck-no{color:#bbb}
.pr-footer{margin-top:5px;border-top:1px solid #ccc;padding-top:4px;text-align:center;font-size:6.5pt;color:#888}
.note-card{background:#fffde7;border:1px solid #ffe082;border-radius:6px;padding:10px 12px;margin-bottom:8px}
.note-card .note-meta{font-size:.75em;color:#888;margin-bottom:4px}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
