<?php
$pageTitle = 'Crédit immobilier';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Auto-add columns if missing (notes, workflow_status)
try {
    $db->exec("ALTER TABLE credit_immobilier ADD COLUMN notes TEXT DEFAULT NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE credit_immobilier ADD COLUMN workflow_status VARCHAR(50) DEFAULT 'etude'");
} catch (Exception $e) {}

// AJAX: fetch budget data for pre-fill
if (isset($_GET['ajax']) && $_GET['ajax'] === 'budget') {
    $stmt = $db->prepare("SELECT * FROM calculateur_budget WHERE user_id = ?");
    $stmt->execute([$userId]);
    $budget = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode($budget ?: ['error' => 'no_budget']);
    exit;
}

// AJAX toggle checkbox
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field']) && isset($_POST['id']) && isset($_POST['value'])) {
    $allowed = ['doc_ji','doc_jd','doc_ir','doc_contrat_travail','doc_bulletins_salaire','doc_justif_propriete',
        'doc_releves_externes','doc_epargnes_externes','eco_ademe_emprunteur','eco_ademe_entreprises','eco_dpe',
        'eco_audit','eco_devis_travaux','suivi_synthese_envoyee','suivi_controle_conformite','suivi_edition_offres',
        'suivi_envoi_signature','suivi_offre_signee'];
    if (in_array($_POST['field'], $allowed)) {
        $stmt = $db->prepare("UPDATE credit_immobilier SET `{$_POST['field']}` = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$_POST['value'], (int)$_POST['id'], $userId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Helper : chaîne vide → null (évite l'erreur ENUM en mode strict MySQL)
function nullIfEmpty($v) { return ($v !== null && $v !== '') ? $v : null; }

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $stmt = $db->prepare("INSERT INTO credit_immobilier (user_id, numero_personne, type_client, type_occupation, type_residence, type_bien, proprietaire_logement, adresse_bien,
            type_credit, avec_travaux, montant_acquisition, frais_notaire, frais_agence, frais_courtage, frais_dossier, cegc, ade, travaux, dont_ecoptz_ptz, taux_emprunt, duree_emprunt, apport,
            ptz_demande, ptz_type, ptz_nombre_bouquets,
            revenus_mensuels, charges_fixes, loyer, credits_en_cours, epargne, notes, workflow_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $_POST['numero_personne'] ?? '',
            $_POST['type_client'] ?? 'Particulier',
            nullIfEmpty($_POST['type_occupation'] ?? null),
            nullIfEmpty($_POST['type_residence'] ?? null),
            nullIfEmpty($_POST['type_bien'] ?? null),
            isset($_POST['proprietaire_logement']) ? 1 : 0,
            $_POST['adresse_bien'] ?? '',
            is_array($_POST['type_credit'] ?? '') ? implode(',', $_POST['type_credit']) : ($_POST['type_credit'] ?? ''),
            isset($_POST['avec_travaux']) ? 1 : 0,
            (float)($_POST['montant_acquisition'] ?? 0),
            (float)($_POST['frais_notaire'] ?? 0),
            (float)($_POST['frais_agence'] ?? 0),
            (float)($_POST['frais_courtage'] ?? 0),
            (float)($_POST['frais_dossier'] ?? 0),
            (float)($_POST['cegc'] ?? 0),
            (float)($_POST['ade'] ?? 0),
            (float)($_POST['travaux'] ?? 0),
            (float)($_POST['dont_ecoptz_ptz'] ?? 0),
            (float)($_POST['taux_emprunt'] ?? 0),
            (int)($_POST['duree_emprunt'] ?? 0),
            (float)($_POST['apport'] ?? 0),
            nullIfEmpty($_POST['ptz_demande'] ?? null),
            nullIfEmpty($_POST['ptz_type'] ?? null),
            $_POST['ptz_nombre_bouquets'] ?? '',
            (float)($_POST['revenus_mensuels'] ?? 0),
            (float)($_POST['charges_fixes'] ?? 0),
            (float)($_POST['loyer'] ?? 0),
            (float)($_POST['credits_en_cours'] ?? 0),
            (float)($_POST['epargne'] ?? 0),
            $_POST['notes'] ?? '',
            $_POST['workflow_status'] ?? 'etude'
        ]);
    } catch (Exception $e) {
        // Erreur SQL silencieuse — retour à la liste sans planter la page
        error_log('[credit_immo] Erreur INSERT : ' . $e->getMessage());
    }
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    try {
        $stmt = $db->prepare("UPDATE credit_immobilier SET
            numero_personne = ?, type_client = ?, type_occupation = ?, type_residence = ?, type_bien = ?, proprietaire_logement = ?, adresse_bien = ?,
            type_credit = ?, avec_travaux = ?, montant_acquisition = ?, frais_notaire = ?, frais_agence = ?, frais_courtage = ?, frais_dossier = ?, cegc = ?, ade = ?, travaux = ?, dont_ecoptz_ptz = ?, taux_emprunt = ?, duree_emprunt = ?, apport = ?,
            ptz_demande = ?, ptz_type = ?, ptz_nombre_bouquets = ?,
            revenus_mensuels = ?, charges_fixes = ?, loyer = ?, credits_en_cours = ?, epargne = ?,
            doc_ji = ?, doc_jd = ?, doc_ir = ?, doc_contrat_travail = ?, doc_bulletins_salaire = ?, doc_justif_propriete = ?, doc_releves_externes = ?, doc_epargnes_externes = ?,
            eco_ademe_emprunteur = ?, eco_ademe_entreprises = ?, eco_dpe = ?, eco_audit = ?, eco_devis_travaux = ?,
            suivi_synthese_envoyee = ?, suivi_controle_conformite = ?, suivi_edition_offres = ?, suivi_envoi_signature = ?, suivi_offre_signee = ?,
            notes = ?, workflow_status = ?,
            suivi_offre_signee_date = ?,
            updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $_POST['numero_personne'] ?? '',
            $_POST['type_client'] ?? 'Particulier',
            nullIfEmpty($_POST['type_occupation'] ?? null),
            nullIfEmpty($_POST['type_residence'] ?? null),
            nullIfEmpty($_POST['type_bien'] ?? null),
            isset($_POST['proprietaire_logement']) ? 1 : 0,
            $_POST['adresse_bien'] ?? '',
            is_array($_POST['type_credit'] ?? '') ? implode(',', $_POST['type_credit']) : ($_POST['type_credit'] ?? ''),
            isset($_POST['avec_travaux']) ? 1 : 0,
            (float)($_POST['montant_acquisition'] ?? 0),
            (float)($_POST['frais_notaire'] ?? 0),
            (float)($_POST['frais_agence'] ?? 0),
            (float)($_POST['frais_courtage'] ?? 0),
            (float)($_POST['frais_dossier'] ?? 0),
            (float)($_POST['cegc'] ?? 0),
            (float)($_POST['ade'] ?? 0),
            (float)($_POST['travaux'] ?? 0),
            (float)($_POST['dont_ecoptz_ptz'] ?? 0),
            (float)($_POST['taux_emprunt'] ?? 0),
            (int)($_POST['duree_emprunt'] ?? 0),
            (float)($_POST['apport'] ?? 0),
            nullIfEmpty($_POST['ptz_demande'] ?? null),
            nullIfEmpty($_POST['ptz_type'] ?? null),
            $_POST['ptz_nombre_bouquets'] ?? '',
            (float)($_POST['revenus_mensuels'] ?? 0),
            (float)($_POST['charges_fixes'] ?? 0),
            (float)($_POST['loyer'] ?? 0),
            (float)($_POST['credits_en_cours'] ?? 0),
            (float)($_POST['epargne'] ?? 0),
            isset($_POST['doc_ji']) ? 1 : 0,
            isset($_POST['doc_jd']) ? 1 : 0,
            isset($_POST['doc_ir']) ? 1 : 0,
            isset($_POST['doc_contrat_travail']) ? 1 : 0,
            isset($_POST['doc_bulletins_salaire']) ? 1 : 0,
            isset($_POST['doc_justif_propriete']) ? 1 : 0,
            isset($_POST['doc_releves_externes']) ? 1 : 0,
            isset($_POST['doc_epargnes_externes']) ? 1 : 0,
            isset($_POST['eco_ademe_emprunteur']) ? 1 : 0,
            isset($_POST['eco_ademe_entreprises']) ? 1 : 0,
            isset($_POST['eco_dpe']) ? 1 : 0,
            isset($_POST['eco_audit']) ? 1 : 0,
            isset($_POST['eco_devis_travaux']) ? 1 : 0,
            isset($_POST['suivi_synthese_envoyee']) ? 1 : 0,
            isset($_POST['suivi_controle_conformite']) ? 1 : 0,
            isset($_POST['suivi_edition_offres']) ? 1 : 0,
            isset($_POST['suivi_envoi_signature']) ? 1 : 0,
            isset($_POST['suivi_offre_signee']) ? 1 : 0,
            $_POST['notes'] ?? '',
            $_POST['workflow_status'] ?? 'etude',
            !empty($_POST['suivi_offre_signee_date']) ? $_POST['suivi_offre_signee_date'] : null,
            $id, $userId
        ]);
    } catch (Exception $e) {
        error_log('[credit_immo] Erreur UPDATE : ' . $e->getMessage());
    }
    header('Location: index.php?open=' . $id);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM credit_immobilier WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Liste
$stmt = $db->prepare("SELECT * FROM credit_immobilier WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$dossiers = $stmt->fetchAll();
?>

<?php
$workflowLabels = [
    'etude' => ['Étude en cours', 'secondary'],
    'dossier_complet' => ['Dossier complet', 'info'],
    'synthese_envoyee' => ['Synthèse envoyée', 'primary'],
    'controle' => ['Contrôle conformité', 'primary'],
    'edition_offres' => ['Édition offres', 'warning'],
    'envoi_signature' => ['Envoi signature', 'warning'],
    'offre_signee' => ['Offre signée', 'success'],
    'deblocage' => ['Déblocage fonds', 'success'],
    'termine' => ['Terminé', 'dark'],
    'refuse' => ['Refusé', 'danger'],
];
$countEnCours = count(array_filter($dossiers, fn($d) => !in_array($d['workflow_status'] ?? 'etude', ['termine', 'refuse', 'offre_signee', 'deblocage'])));
$countSignees = count(array_filter($dossiers, fn($d) => in_array($d['workflow_status'] ?? '', ['offre_signee', 'deblocage', 'termine'])));
$countRefusees = count(array_filter($dossiers, fn($d) => ($d['workflow_status'] ?? '') === 'refuse'));
?>
<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-number"><?= count($dossiers) ?></div>
            <div class="stat-label">Total dossiers</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color:#0d6efd;">
            <div class="stat-number"><?= $countEnCours ?></div>
            <div class="stat-label">En cours</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card stat-success">
            <div class="stat-number"><?= $countSignees ?></div>
            <div class="stat-label">Signées/Terminées</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card" style="border-left-color:#dc3545;">
            <div class="stat-number"><?= $countRefusees ?></div>
            <div class="stat-label">Refusées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center gap-2">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau dossier</button>
        <button class="btn btn-ce-outline" onclick="showCompareSelect()"><i class="fas fa-balance-scale"></i> Comparer</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Dossiers crédit immobilier</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchDossiers" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableDossiers">
        <thead>
            <tr>
                <th>Date</th>
                <th>N° personne</th>
                <th>Type crédit</th>
                <th>Montant</th>
                <th>Taux</th>
                <th>Durée</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dossiers as $d):
            $wf = $d['workflow_status'] ?? 'etude';
            $wfInfo = $workflowLabels[$wf] ?? ['Inconnu', 'secondary'];
        ?>
            <tr>
                <td><?= formatDate($d['date_ajout']) ?></td>
                <td><?= e($d['numero_personne']) ?></td>
                <td><?= e($d['type_credit']) ?></td>
                <td><?= number_format((float)$d['montant_acquisition'], 0, ',', ' ') ?> &euro;</td>
                <td><?= $d['taux_emprunt'] ?> %</td>
                <td><?= $d['duree_emprunt'] ?> mois</td>
                <td><span class="badge bg-<?= $wfInfo[1] ?>"><?= $wfInfo[0] ?></span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDossier(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showAmortissement(<?= $d['id'] ?>)" title="Amortissement"><i class="fas fa-table"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="showSimulation(<?= $d['id'] ?>)" title="Simulation"><i class="fas fa-calculator"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="printDossier(<?= $d['id'] ?>)" title="Imprimer"><i class="fas fa-print"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce dossier ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout -->
<div class="modal fade modal-fullscreen-custom" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau dossier crédit immobilier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabClient">Client</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCredit">Crédit</a></li>
                        <li class="nav-item add-ptz-tab" style="display:none;"><a class="nav-link" data-bs-toggle="tab" href="#tabPTZ">PTZ/EcoPTZ</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSituation">Situation financière</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSuivi">Suivi</a></li>
                    </ul>
                    <div class="tab-content">
                        <!-- Client -->
                        <div class="tab-pane fade show active" id="tabClient">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">N° personne</label>
                                    <input type="text" name="numero_personne" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type client</label>
                                    <select name="type_client" class="form-select">
                                        <option value="Particulier">Particulier</option>
                                        <option value="Pro">Professionnel</option>
                                        <option value="Asso">Association</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type d'occupation</label>
                                    <select name="type_occupation" class="form-select">
                                        <option value="">--</option>
                                        <option value="Proprietaire">Propriétaire</option>
                                        <option value="Locatif">Locatif</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type de résidence</label>
                                    <select name="type_residence" class="form-select">
                                        <option value="">--</option>
                                        <option value="RP">Résidence principale</option>
                                        <option value="RS">Résidence secondaire</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type de bien</label>
                                    <select name="type_bien" class="form-select">
                                        <option value="">--</option>
                                        <option value="Appartement">Appartement</option>
                                        <option value="Maison">Maison</option>
                                        <option value="Copro">Copropriété</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="proprietaire_logement" id="addProprio">
                                        <label class="form-check-label" for="addProprio">Propriétaire du logement actuel</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse du bien</label>
                                    <textarea name="adresse_bien" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- Crédit -->
                        <div class="tab-pane fade" id="tabCredit">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Type de crédit</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check"><input class="form-check-input add-type-credit" type="checkbox" name="type_credit[]" value="PH" id="addTC_PH"><label class="form-check-label" for="addTC_PH">PH</label></div>
                                        <div class="form-check"><input class="form-check-input add-type-credit" type="checkbox" name="type_credit[]" value="PTZ" id="addTC_PTZ"><label class="form-check-label" for="addTC_PTZ">PTZ</label></div>
                                        <div class="form-check"><input class="form-check-input add-type-credit" type="checkbox" name="type_credit[]" value="EcoPTZ" id="addTC_EcoPTZ"><label class="form-check-label" for="addTC_EcoPTZ">EcoPTZ</label></div>
                                        <div class="form-check"><input class="form-check-input add-type-credit" type="checkbox" name="type_credit[]" value="Prescripteur" id="addTC_Prescripteur"><label class="form-check-label" for="addTC_Prescripteur">Prescripteur</label></div>
                                        <div class="form-check"><input class="form-check-input add-type-credit" type="checkbox" name="type_credit[]" value="SCI" id="addTC_SCI"><label class="form-check-label" for="addTC_SCI">SCI</label></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="avec_travaux" id="addTravaux">
                                        <label class="form-check-label" for="addTravaux">Avec travaux</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant acquisition</label>
                                    <input type="number" step="0.01" name="montant_acquisition" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Frais de notaire</label>
                                    <input type="number" step="0.01" name="frais_notaire" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais d'agence</label>
                                    <input type="number" step="0.01" name="frais_agence" class="form-control" value="0">
                                </div>
                                <div class="col-md-3 add-courtage-wrap">
                                    <label class="form-label">Frais de courtage</label>
                                    <input type="number" step="0.01" name="frais_courtage" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais de dossier</label>
                                    <input type="number" step="0.01" name="frais_dossier" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CEGC</label>
                                    <input type="number" step="0.01" name="cegc" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ADE</label>
                                    <input type="number" step="0.01" name="ade" class="form-control" value="0">
                                </div>
                                <div class="col-md-3 add-travaux-wrap" style="display:none;">
                                    <label class="form-label">Travaux</label>
                                    <input type="number" step="0.01" name="travaux" class="form-control" value="0">
                                </div>
                                <div class="col-md-3 add-ecoptz-wrap" style="display:none;">
                                    <label class="form-label">Dont EcoPTZ/PTZ</label>
                                    <input type="number" step="0.01" name="dont_ecoptz_ptz" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Taux d'emprunt (%)</label>
                                    <input type="number" step="0.001" name="taux_emprunt" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Durée (mois)</label>
                                    <input type="number" name="duree_emprunt" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Apport</label>
                                    <input type="number" step="0.01" name="apport" class="form-control" value="0">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0" id="addMontantTotal" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- PTZ -->
                        <div class="tab-pane fade" id="tabPTZ">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Type de demande</label>
                                    <select name="ptz_demande" class="form-select">
                                        <option value="">--</option>
                                        <option value="Initiale">Initiale</option>
                                        <option value="Complementaire">Complémentaire</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select name="ptz_type" class="form-select">
                                        <option value="">--</option>
                                        <option value="Perf globale">Performance globale</option>
                                        <option value="Bouquets">Bouquets</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de bouquets</label>
                                    <select name="ptz_nombre_bouquets" class="form-select">
                                        <option value="">--</option>
                                        <option value="1 Action hors parois vitrees - 15000">1 Action hors parois vitrées - 15 000 &euro;</option>
                                        <option value="1 Action parois vitrees - 7000">1 Action parois vitrées - 7 000 &euro;</option>
                                        <option value="2 Actions - 25000">2 Actions - 25 000 &euro;</option>
                                        <option value="3 Actions et + - 30000">3 Actions et + - 30 000 &euro;</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Situation financière -->
                        <div class="tab-pane fade" id="tabSituation">
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="prefillFromBudget('add')"><i class="fas fa-link"></i> Pré-remplir depuis le calculateur budget</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Revenus mensuels</label>
                                    <input type="number" step="0.01" name="revenus_mensuels" class="form-control" id="addRevenus" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Charges fixes</label>
                                    <input type="number" step="0.01" name="charges_fixes" class="form-control" id="addCharges" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Loyer actuel</label>
                                    <input type="number" step="0.01" name="loyer" class="form-control" id="addLoyer" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Crédits en cours</label>
                                    <input type="number" step="0.01" name="credits_en_cours" class="form-control" id="addCredits" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Épargne</label>
                                    <input type="number" step="0.01" name="epargne" class="form-control" id="addEpargne" value="0">
                                </div>
                            </div>
                        </div>
                        <!-- Suivi -->
                        <div class="tab-pane fade" id="tabSuivi">
                            <h6>Documents</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_ji" value="1" id="addDocJI"><label class="form-check-label" for="addDocJI">JI</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_jd" value="1" id="addDocJD"><label class="form-check-label" for="addDocJD">JD</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_ir" value="1" id="addDocIR"><label class="form-check-label" for="addDocIR">IR</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_contrat_travail" value="1" id="addDocCT"><label class="form-check-label" for="addDocCT">Contrat de travail</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_bulletins_salaire" value="1" id="addDocBS"><label class="form-check-label" for="addDocBS">3 derniers bulletins de salaire</label></div></div>
                                <div class="col-md-3 add-justif-proprio-wrap"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_justif_propriete" value="1" id="addDocJP"><label class="form-check-label" for="addDocJP">Justificatif de propriété</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_releves_externes" value="1" id="addDocRE"><label class="form-check-label" for="addDocRE">3 derniers relevés comptes externes</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_epargnes_externes" value="1" id="addDocEE"><label class="form-check-label" for="addDocEE">Relevés épargnes externes</label></div></div>
                            </div>
                            <div class="add-eco-suivi-wrap" style="display:none;">
                                <h6>EcoPTZ</h6>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_ademe_emprunteur" value="1" id="addEcoAE"><label class="form-check-label" for="addEcoAE">ADEME Emprunteur</label></div></div>
                                    <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_ademe_entreprises" value="1" id="addEcoAEnt"><label class="form-check-label" for="addEcoAEnt">ADEME Entreprises</label></div></div>
                                    <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_dpe" value="1" id="addEcoDPE"><label class="form-check-label" for="addEcoDPE">DPE</label></div></div>
                                    <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_audit" value="1" id="addEcoAudit"><label class="form-check-label" for="addEcoAudit">Audit</label></div></div>
                                    <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_devis_travaux" value="1" id="addEcoDT"><label class="form-check-label" for="addEcoDT">Devis travaux</label></div></div>
                                </div>
                            </div>
                            <h6>Suivi</h6>
                            <div class="row g-2">
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_synthese_envoyee" value="1" id="addSuiviSE"><label class="form-check-label" for="addSuiviSE">Synthèse envoyée</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_controle_conformite" value="1" id="addSuiviCC"><label class="form-check-label" for="addSuiviCC">Contrôle conformité</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_edition_offres" value="1" id="addSuiviEO"><label class="form-check-label" for="addSuiviEO">Édition offres</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_envoi_signature" value="1" id="addSuiviES"><label class="form-check-label" for="addSuiviES">Envoi signature</label></div></div>
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_offre_signee" value="1" id="addSuiviOS"><label class="form-check-label" for="addSuiviOS">Offre signée</label></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Statut du dossier</label>
                            <select name="workflow_status" class="form-select">
                                <?php foreach ($workflowLabels as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes du conseiller</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Observations, points d'attention..."></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Créer le dossier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du dossier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le dossier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<!-- Modal Amortissement -->
<div class="modal fade modal-fullscreen-custom" id="amortModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-table"></i> Tableau d'amortissement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="amortContent"></div>
        </div>
    </div>
</div>

<!-- Modal Comparaison -->
<div class="modal fade modal-fullscreen-custom" id="compareModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-balance-scale"></i> Comparaison de scénarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="compareContent"></div>
        </div>
    </div>
</div>

<!-- Modal Simulation -->
<div class="modal fade modal-fullscreen-custom" id="simulModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calculator"></i> Simulation "Et si..."</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="simulContent"></div>
        </div>
    </div>
</div>

<!-- Zone impression (cachée) -->
<div id="printArea" class="print-dossier" style="display:none;"></div>

<script>
filterTable('searchDossiers', 'tableDossiers');

const dossiersData = <?= json_encode($dossiers) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function fmt(val) {
    return parseFloat(val || 0).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function chk(val) { return val == 1 ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-muted"></i>'; }

// Conditional logic for Add modal
function initAddConditionalLogic() {
    const typeCredits = document.querySelectorAll('.add-type-credit');
    const travauxCheck = document.getElementById('addTravaux');
    const ptzTab = document.querySelector('.add-ptz-tab');
    const travauxWrap = document.querySelector('.add-travaux-wrap');
    const ecoptzWrap = document.querySelector('.add-ecoptz-wrap');
    const courtageWrap = document.querySelector('.add-courtage-wrap');
    const ecoSuiviWrap = document.querySelector('.add-eco-suivi-wrap');
    const proprioCheck = document.getElementById('addProprio');
    const justifProprioWrap = document.querySelector('.add-justif-proprio-wrap');

    function updateVisibility() {
        const checked = Array.from(typeCredits).filter(c => c.checked).map(c => c.value);
        const hasPTZ = checked.includes('PTZ') || checked.includes('EcoPTZ');
        const hasPrescripteur = checked.includes('Prescripteur');

        // Auto-check travaux if PTZ/EcoPTZ
        if (hasPTZ) travauxCheck.checked = true;

        // Show/hide PTZ tab
        ptzTab.style.display = hasPTZ ? '' : 'none';

        // Show/hide courtage if Prescripteur
        courtageWrap.style.display = hasPrescripteur ? '' : 'none';

        // Show/hide EcoPTZ suivi section
        if (ecoSuiviWrap) ecoSuiviWrap.style.display = hasPTZ ? '' : 'none';

        updateTravauxVisibility();
    }

    function updateTravauxVisibility() {
        const checked = Array.from(typeCredits).filter(c => c.checked).map(c => c.value);
        const hasPTZ = checked.includes('PTZ') || checked.includes('EcoPTZ');
        const avecTravaux = travauxCheck.checked;

        travauxWrap.style.display = avecTravaux ? '' : 'none';
        ecoptzWrap.style.display = (avecTravaux && hasPTZ) ? '' : 'none';
    }

    function updateProprioVisibility() {
        if (justifProprioWrap) justifProprioWrap.style.display = proprioCheck.checked ? '' : 'none';
    }

    typeCredits.forEach(cb => cb.addEventListener('change', updateVisibility));
    travauxCheck.addEventListener('change', updateTravauxVisibility);
    if (proprioCheck) proprioCheck.addEventListener('change', updateProprioVisibility);

    updateVisibility();
    updateProprioVisibility();
}

// Workflow labels
const workflowLabels = {
    'etude': ['Étude en cours', 'secondary'],
    'dossier_complet': ['Dossier complet', 'info'],
    'synthese_envoyee': ['Synthèse envoyée', 'primary'],
    'controle': ['Contrôle conformité', 'primary'],
    'edition_offres': ['Édition offres', 'warning'],
    'envoi_signature': ['Envoi signature', 'warning'],
    'offre_signee': ['Offre signée', 'success'],
    'deblocage': ['Déblocage fonds', 'success'],
    'termine': ['Terminé', 'dark'],
    'refuse': ['Refusé', 'danger']
};
const workflowSteps = ['etude','dossier_complet','synthese_envoyee','controle','edition_offres','envoi_signature','offre_signee','deblocage','termine'];

function getEndettementBadge(taux) {
    if (taux === 'N/A') return '<span class="badge bg-secondary">N/A</span>';
    const t = parseFloat(taux);
    if (t <= 25) return `<span class="badge bg-success" style="font-size:1em;">${taux}%</span>`;
    if (t <= 33) return `<span class="badge bg-warning text-dark" style="font-size:1em;">${taux}%</span>`;
    if (t <= 35) return `<span class="badge bg-orange text-white" style="font-size:1em;">${taux}% <i class="fas fa-exclamation-triangle"></i></span>`;
    return `<span class="badge bg-danger" style="font-size:1em;">${taux}% <i class="fas fa-exclamation-circle"></i> ALERTE</span>`;
}

function getEndettementAlert(taux) {
    if (taux === 'N/A') return '';
    const t = parseFloat(taux);
    if (t <= 33) return '';
    if (t <= 35) return '<div class="alert alert-warning mt-2"><i class="fas fa-exclamation-triangle"></i> <strong>Attention :</strong> Taux d\'endettement proche du seuil HCSF de 35%.</div>';
    return '<div class="alert alert-danger mt-2"><i class="fas fa-exclamation-circle"></i> <strong>ALERTE :</strong> Taux d\'endettement supérieur au seuil HCSF de 35%. Le dossier risque d\'être refusé.</div>';
}

function calcMensualite(capital, tauxAnnuel, duree) {
    const tm = tauxAnnuel / 100 / 12;
    if (tm > 0 && duree > 0) return capital * tm / (1 - Math.pow(1 + tm, -duree));
    if (duree > 0) return capital / duree;
    return 0;
}

function getTotalFinancement(d) {
    return parseFloat(d.montant_acquisition||0) + parseFloat(d.frais_notaire||0) + parseFloat(d.frais_agence||0)
        + parseFloat(d.frais_courtage||0) + parseFloat(d.frais_dossier||0) + parseFloat(d.cegc||0)
        + parseFloat(d.travaux||0);
}

function getCapital(d) {
    return getTotalFinancement(d) - parseFloat(d.apport||0);
}

function calcPTZMontant(d) {
    const bouquet = d.ptz_nombre_bouquets || '';
    const match = bouquet.match(/(\d+)$/);
    return match ? parseInt(match[1]) : 0;
}

function buildWorkflowProgress(status) {
    const idx = workflowSteps.indexOf(status);
    if (status === 'refuse') return '<div class="alert alert-danger text-center mb-0"><i class="fas fa-times-circle"></i> Dossier refusé</div>';
    let html = '<div class="d-flex justify-content-between align-items-center" style="font-size:0.75rem;">';
    workflowSteps.forEach((step, i) => {
        const label = workflowLabels[step] ? workflowLabels[step][0] : step;
        const active = i <= idx;
        const current = i === idx;
        const color = active ? (current ? 'var(--ce-primary)' : '#28a745') : '#dee2e6';
        html += `<div class="text-center flex-fill">
            <div style="width:24px;height:24px;border-radius:50%;background:${color};color:#fff;margin:0 auto 2px;line-height:24px;font-size:0.7rem;">${active ? '<i class="fas fa-check"></i>' : (i+1)}</div>
            <div style="color:${current ? 'var(--ce-primary)' : '#888'};font-weight:${current ? 'bold' : 'normal'}">${label}</div>
        </div>`;
        if (i < workflowSteps.length - 1) html += `<div style="flex:1;height:2px;background:${i < idx ? '#28a745' : '#dee2e6'};margin-top:-12px;"></div>`;
    });
    return html + '</div>';
}

function showDetail(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;

    const totalFinancement = getTotalFinancement(d);
    const montantEmprunte = getCapital(d);
    const taux = parseFloat(d.taux_emprunt||0);
    const duree = parseInt(d.duree_emprunt||0);
    const mensualite = calcMensualite(montantEmprunte, taux, duree);
    const coutTotal = mensualite * duree;
    const totalInterets = coutTotal - montantEmprunte;
    const tauxEndettement = parseFloat(d.revenus_mensuels||0) > 0
        ? ((mensualite + parseFloat(d.credits_en_cours||0)) / parseFloat(d.revenus_mensuels) * 100).toFixed(1)
        : 'N/A';
    const resteAVivre = parseFloat(d.revenus_mensuels||0) - mensualite - parseFloat(d.charges_fixes||0) - parseFloat(d.credits_en_cours||0);
    const ptzMontant = calcPTZMontant(d);
    const wf = d.workflow_status || 'etude';
    const wfInfo = workflowLabels[wf] || ['Inconnu', 'secondary'];

    document.getElementById('detailContent').innerHTML = `
        <div class="mb-3">${buildWorkflowProgress(wf)}</div>
        ${getEndettementAlert(tauxEndettement)}
        <div class="row g-3">
            <div class="col-md-6">
                <h5>Client</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>N° personne</td><td><strong>${escapeHtml(d.numero_personne)}</strong></td></tr>
                    <tr><td>Type</td><td>${escapeHtml(d.type_client)}</td></tr>
                    <tr><td>Occupation</td><td>${escapeHtml(d.type_occupation)}</td></tr>
                    <tr><td>Résidence</td><td>${d.type_residence === 'RP' ? 'Principale' : d.type_residence === 'RS' ? 'Secondaire' : ''}</td></tr>
                    <tr><td>Bien</td><td>${escapeHtml(d.type_bien)}</td></tr>
                    <tr><td>Propriétaire logement</td><td>${chk(d.proprietaire_logement)}</td></tr>
                    <tr><td>Adresse bien</td><td>${escapeHtml(d.adresse_bien)}</td></tr>
                </tbody></table>
            </div>
            <div class="col-md-6">
                <h5>Crédit</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>Type crédit</td><td>${escapeHtml(d.type_credit)}</td></tr>
                    <tr><td>Montant acquisition</td><td>${fmt(d.montant_acquisition)} &euro;</td></tr>
                    <tr><td>Frais notaire</td><td>${fmt(d.frais_notaire)} &euro;</td></tr>
                    <tr><td>Frais agence</td><td>${fmt(d.frais_agence)} &euro;</td></tr>
                    <tr><td>Frais courtage/dossier</td><td>${fmt(d.frais_courtage)} / ${fmt(d.frais_dossier)} &euro;</td></tr>
                    <tr><td>CEGC / ADE</td><td>${fmt(d.cegc)} / ${fmt(d.ade)} &euro;</td></tr>
                    <tr><td>Travaux</td><td>${fmt(d.travaux)} &euro;</td></tr>
                    <tr><td>Apport</td><td>${fmt(d.apport)} &euro;</td></tr>
                    <tr><td>Taux / Durée</td><td>${d.taux_emprunt}% / ${d.duree_emprunt} mois</td></tr>
                    <tr class="table-info"><td><strong>Total financement</strong></td><td><strong>${fmt(totalFinancement)} &euro;</strong></td></tr>
                    <tr class="table-info"><td><strong>Montant emprunté</strong></td><td><strong>${fmt(montantEmprunte)} &euro;</strong></td></tr>
                    <tr class="table-warning"><td><strong>Mensualité</strong></td><td><strong>${fmt(mensualite)} &euro;</strong></td></tr>
                    <tr class="table-secondary"><td><strong>Coût total intérêts</strong></td><td><strong>${fmt(totalInterets)} &euro;</strong></td></tr>
                    <tr class="table-secondary"><td><strong>Coût total crédit</strong></td><td><strong>${fmt(coutTotal)} &euro;</strong></td></tr>
                    ${ptzMontant > 0 ? `<tr class="table-success"><td><strong>Montant PTZ/EcoPTZ</strong></td><td><strong>${fmt(ptzMontant)} &euro;</strong></td></tr>` : ''}
                </tbody></table>
            </div>
            <div class="col-md-6">
                <h5>Situation financière</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>Revenus mensuels</td><td>${fmt(d.revenus_mensuels)} &euro;</td></tr>
                    <tr><td>Charges fixes</td><td>${fmt(d.charges_fixes)} &euro;</td></tr>
                    <tr><td>Loyer actuel</td><td>${fmt(d.loyer)} &euro;</td></tr>
                    <tr><td>Crédits en cours</td><td>${fmt(d.credits_en_cours)} &euro;</td></tr>
                    <tr><td>Épargne</td><td>${fmt(d.epargne)} &euro;</td></tr>
                    <tr><td><strong>Taux d'endettement</strong></td><td>${getEndettementBadge(tauxEndettement)}</td></tr>
                    <tr class="table-info"><td><strong>Reste à vivre</strong></td><td><strong>${fmt(resteAVivre)} &euro;</strong></td></tr>
                </tbody></table>
            </div>
            <div class="col-md-6">
                <h5>Suivi documents</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>JI</td><td>${chk(d.doc_ji)}</td><td>JD</td><td>${chk(d.doc_jd)}</td></tr>
                    <tr><td>IR</td><td>${chk(d.doc_ir)}</td><td>Contrat travail</td><td>${chk(d.doc_contrat_travail)}</td></tr>
                    <tr><td>Bulletins salaire</td><td>${chk(d.doc_bulletins_salaire)}</td><td>Justif. propriété</td><td>${chk(d.doc_justif_propriete)}</td></tr>
                    <tr><td>Relevés externes</td><td>${chk(d.doc_releves_externes)}</td><td>Épargnes externes</td><td>${chk(d.doc_epargnes_externes)}</td></tr>
                </tbody></table>
                <h5>Suivi EcoPTZ</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>ADEME emprunteur</td><td>${chk(d.eco_ademe_emprunteur)}</td><td>ADEME entreprises</td><td>${chk(d.eco_ademe_entreprises)}</td></tr>
                    <tr><td>DPE</td><td>${chk(d.eco_dpe)}</td><td>Audit</td><td>${chk(d.eco_audit)}</td></tr>
                    <tr><td>Devis travaux</td><td>${chk(d.eco_devis_travaux)}</td><td></td><td></td></tr>
                </tbody></table>
                <h5>Suivi dossier</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>Synthèse envoyée</td><td>${chk(d.suivi_synthese_envoyee)}</td></tr>
                    <tr><td>Contrôle conformité</td><td>${chk(d.suivi_controle_conformite)}</td></tr>
                    <tr><td>Édition offres</td><td>${chk(d.suivi_edition_offres)}</td></tr>
                    <tr><td>Envoi signature</td><td>${chk(d.suivi_envoi_signature)}</td></tr>
                    <tr><td>Offre signée</td><td>${chk(d.suivi_offre_signee)} ${d.suivi_offre_signee_date ? '(' + d.suivi_offre_signee_date + ')' : ''}</td></tr>
                </tbody></table>
            </div>
            ${d.notes ? `<div class="col-12"><h5>Notes du conseiller</h5><div class="alert alert-light">${escapeHtml(d.notes)}</div></div>` : ''}
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function sel(name, val, options) {
    return options.map(o => `<option value="${o[0]}" ${val === o[0] ? 'selected' : ''}>${o[1]}</option>`).join('');
}

function editDossier(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;
    const tc = (d.type_credit || '').split(',');
    const hasPTZ = tc.includes('PTZ') || tc.includes('EcoPTZ');
    const hasPrescripteur = tc.includes('Prescripteur');
    const avecTravaux = d.avec_travaux == 1;

    const bouquetOptions = [
        ['1 Action hors parois vitrees - 15000', '1 Action hors parois vitrées - 15 000 €'],
        ['1 Action parois vitrees - 7000', '1 Action parois vitrées - 7 000 €'],
        ['2 Actions - 25000', '2 Actions - 25 000 €'],
        ['3 Actions et + - 30000', '3 Actions et + - 30 000 €']
    ];

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#eTabClient">Client</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#eTabCredit">Crédit</a></li>
                <li class="nav-item edit-ptz-tab" style="${hasPTZ ? '' : 'display:none'}"><a class="nav-link" data-bs-toggle="tab" href="#eTabPTZ">PTZ/EcoPTZ</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#eTabSituation">Situation</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#eTabSuivi">Suivi</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="eTabClient">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">N° personne</label><input type="text" name="numero_personne" class="form-control" value="${escapeHtml(d.numero_personne)}"></div>
                        <div class="col-md-4"><label class="form-label">Type client</label><select name="type_client" class="form-select">${sel('type_client', d.type_client, [['Particulier','Particulier'],['Pro','Professionnel'],['Asso','Association']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Type occupation</label><select name="type_occupation" class="form-select"><option value="">--</option>${sel('', d.type_occupation, [['Proprietaire','Propriétaire'],['Locatif','Locatif']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Résidence</label><select name="type_residence" class="form-select"><option value="">--</option>${sel('', d.type_residence, [['RP','Principale'],['RS','Secondaire']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Type bien</label><select name="type_bien" class="form-select"><option value="">--</option>${sel('', d.type_bien, [['Appartement','Appartement'],['Maison','Maison'],['Copro','Copropriété']])}</select></div>
                        <div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input edit-proprio-check" type="checkbox" name="proprietaire_logement" ${d.proprietaire_logement == 1 ? 'checked' : ''}><label class="form-check-label">Propriétaire logement</label></div></div>
                        <div class="col-12"><label class="form-label">Adresse bien</label><textarea name="adresse_bien" class="form-control" rows="2">${escapeHtml(d.adresse_bien)}</textarea></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabCredit">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Type de crédit</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check"><input class="form-check-input edit-type-credit" type="checkbox" name="type_credit[]" value="PH" ${tc.includes('PH') ? 'checked' : ''}><label class="form-check-label">PH</label></div>
                                <div class="form-check"><input class="form-check-input edit-type-credit" type="checkbox" name="type_credit[]" value="PTZ" ${tc.includes('PTZ') ? 'checked' : ''}><label class="form-check-label">PTZ</label></div>
                                <div class="form-check"><input class="form-check-input edit-type-credit" type="checkbox" name="type_credit[]" value="EcoPTZ" ${tc.includes('EcoPTZ') ? 'checked' : ''}><label class="form-check-label">EcoPTZ</label></div>
                                <div class="form-check"><input class="form-check-input edit-type-credit" type="checkbox" name="type_credit[]" value="Prescripteur" ${tc.includes('Prescripteur') ? 'checked' : ''}><label class="form-check-label">Prescripteur</label></div>
                                <div class="form-check"><input class="form-check-input edit-type-credit" type="checkbox" name="type_credit[]" value="SCI" ${tc.includes('SCI') ? 'checked' : ''}><label class="form-check-label">SCI</label></div>
                            </div>
                        </div>
                        <div class="col-md-4"><div class="form-check mt-2"><input class="form-check-input edit-travaux-check" type="checkbox" name="avec_travaux" ${avecTravaux ? 'checked' : ''}><label class="form-check-label">Avec travaux</label></div></div>
                        <div class="col-md-4"><label class="form-label">Montant acquisition</label><input type="number" step="0.01" name="montant_acquisition" class="form-control" value="${d.montant_acquisition}"></div>
                        <div class="col-md-4"><label class="form-label">Frais de notaire</label><input type="number" step="0.01" name="frais_notaire" class="form-control" value="${d.frais_notaire}"></div>
                        <div class="col-md-3"><label class="form-label">Frais d'agence</label><input type="number" step="0.01" name="frais_agence" class="form-control" value="${d.frais_agence}"></div>
                        <div class="col-md-3 edit-courtage-wrap" style="${hasPrescripteur ? '' : 'display:none'}"><label class="form-label">Frais de courtage</label><input type="number" step="0.01" name="frais_courtage" class="form-control" value="${d.frais_courtage}"></div>
                        <div class="col-md-3"><label class="form-label">Frais de dossier</label><input type="number" step="0.01" name="frais_dossier" class="form-control" value="${d.frais_dossier}"></div>
                        <div class="col-md-3"><label class="form-label">CEGC</label><input type="number" step="0.01" name="cegc" class="form-control" value="${d.cegc}"></div>
                        <div class="col-md-3"><label class="form-label">ADE</label><input type="number" step="0.01" name="ade" class="form-control" value="${d.ade}"></div>
                        <div class="col-md-3 edit-travaux-wrap" style="${avecTravaux ? '' : 'display:none'}"><label class="form-label">Travaux</label><input type="number" step="0.01" name="travaux" class="form-control" value="${d.travaux}"></div>
                        <div class="col-md-3 edit-ecoptz-wrap" style="${avecTravaux && hasPTZ ? '' : 'display:none'}"><label class="form-label">Dont EcoPTZ/PTZ</label><input type="number" step="0.01" name="dont_ecoptz_ptz" class="form-control" value="${d.dont_ecoptz_ptz}"></div>
                        <div class="col-md-4"><label class="form-label">Taux (%)</label><input type="number" step="0.001" name="taux_emprunt" class="form-control" value="${d.taux_emprunt}"></div>
                        <div class="col-md-4"><label class="form-label">Durée (mois)</label><input type="number" name="duree_emprunt" class="form-control" value="${d.duree_emprunt}"></div>
                        <div class="col-md-4"><label class="form-label">Apport</label><input type="number" step="0.01" name="apport" class="form-control" value="${d.apport}"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabPTZ">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Demande</label><select name="ptz_demande" class="form-select"><option value="">--</option>${sel('', d.ptz_demande, [['Initiale','Initiale'],['Complementaire','Complémentaire']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Type</label><select name="ptz_type" class="form-select"><option value="">--</option>${sel('', d.ptz_type, [['Perf globale','Performance globale'],['Bouquets','Bouquets']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Nombre de bouquets</label><select name="ptz_nombre_bouquets" class="form-select"><option value="">--</option>${bouquetOptions.map(o => '<option value="'+o[0]+'" '+(d.ptz_nombre_bouquets===o[0]?'selected':'')+'>'+o[1]+'</option>').join('')}</select></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabSituation">
                    <div class="mb-3"><button type="button" class="btn btn-sm btn-outline-primary" onclick="prefillFromBudget('edit')"><i class="fas fa-link"></i> Pré-remplir depuis le calculateur budget</button></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Revenus mensuels</label><input type="number" step="0.01" name="revenus_mensuels" class="form-control" id="editRevenus" value="${d.revenus_mensuels}"></div>
                        <div class="col-md-4"><label class="form-label">Charges fixes</label><input type="number" step="0.01" name="charges_fixes" class="form-control" id="editCharges" value="${d.charges_fixes}"></div>
                        <div class="col-md-4"><label class="form-label">Loyer actuel</label><input type="number" step="0.01" name="loyer" class="form-control" id="editLoyer" value="${d.loyer}"></div>
                        <div class="col-md-4"><label class="form-label">Crédits en cours</label><input type="number" step="0.01" name="credits_en_cours" class="form-control" id="editCredits" value="${d.credits_en_cours}"></div>
                        <div class="col-md-4"><label class="form-label">Épargne</label><input type="number" step="0.01" name="epargne" class="form-control" id="editEpargne" value="${d.epargne}"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabSuivi">
                    <h6>Documents</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_ji" value="1" ${d.doc_ji==1?'checked':''}><label class="form-check-label">JI</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_jd" value="1" ${d.doc_jd==1?'checked':''}><label class="form-check-label">JD</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_ir" value="1" ${d.doc_ir==1?'checked':''}><label class="form-check-label">IR</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_contrat_travail" value="1" ${d.doc_contrat_travail==1?'checked':''}><label class="form-check-label">Contrat de travail</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_bulletins_salaire" value="1" ${d.doc_bulletins_salaire==1?'checked':''}><label class="form-check-label">3 derniers bulletins de salaire</label></div></div>
                        <div class="col-md-3 edit-justif-proprio-wrap" style="${d.proprietaire_logement==1?'':'display:none'}"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_justif_propriete" value="1" ${d.doc_justif_propriete==1?'checked':''}><label class="form-check-label">Justificatif de propriété</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_releves_externes" value="1" ${d.doc_releves_externes==1?'checked':''}><label class="form-check-label">3 derniers relevés comptes externes</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="doc_epargnes_externes" value="1" ${d.doc_epargnes_externes==1?'checked':''}><label class="form-check-label">Relevés épargnes externes</label></div></div>
                    </div>
                    <div class="edit-eco-suivi-wrap" style="${hasPTZ?'':'display:none'}">
                        <h6>EcoPTZ</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_ademe_emprunteur" value="1" ${d.eco_ademe_emprunteur==1?'checked':''}><label class="form-check-label">ADEME Emprunteur</label></div></div>
                            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_ademe_entreprises" value="1" ${d.eco_ademe_entreprises==1?'checked':''}><label class="form-check-label">ADEME Entreprises</label></div></div>
                            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_dpe" value="1" ${d.eco_dpe==1?'checked':''}><label class="form-check-label">DPE</label></div></div>
                            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_audit" value="1" ${d.eco_audit==1?'checked':''}><label class="form-check-label">Audit</label></div></div>
                            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="eco_devis_travaux" value="1" ${d.eco_devis_travaux==1?'checked':''}><label class="form-check-label">Devis travaux</label></div></div>
                        </div>
                    </div>
                    <h6>Suivi</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_synthese_envoyee" value="1" ${d.suivi_synthese_envoyee==1?'checked':''}><label class="form-check-label">Synthèse envoyée</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_controle_conformite" value="1" ${d.suivi_controle_conformite==1?'checked':''}><label class="form-check-label">Contrôle conformité</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_edition_offres" value="1" ${d.suivi_edition_offres==1?'checked':''}><label class="form-check-label">Édition offres</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_envoi_signature" value="1" ${d.suivi_envoi_signature==1?'checked':''}><label class="form-check-label">Envoi signature</label></div></div>
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="suivi_offre_signee" value="1" ${d.suivi_offre_signee==1?'checked':''}><label class="form-check-label">Offre signée</label></div></div>
                        <div class="col-md-3"><label class="form-label">Date offre signée</label><input type="date" name="suivi_offre_signee_date" class="form-control" value="${d.suivi_offre_signee_date || ''}"></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label">Statut du dossier</label>
                    <select name="workflow_status" class="form-select">
                        ${Object.entries(workflowLabels).map(([k,v]) => '<option value="'+k+'" '+(d.workflow_status===k?'selected':'')+'>'+v[0]+'</option>').join('')}
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Notes du conseiller</label>
                    <textarea name="notes" class="form-control" rows="2">${escapeHtml(d.notes || '')}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>`;
    initEditConditionalLogic();
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function initEditConditionalLogic() {
    const typeCredits = document.querySelectorAll('.edit-type-credit');
    const travauxCheck = document.querySelector('.edit-travaux-check');
    const ptzTab = document.querySelector('.edit-ptz-tab');
    const travauxWrap = document.querySelector('.edit-travaux-wrap');
    const ecoptzWrap = document.querySelector('.edit-ecoptz-wrap');
    const courtageWrap = document.querySelector('.edit-courtage-wrap');
    const ecoSuiviWrap = document.querySelector('.edit-eco-suivi-wrap');
    const proprioCheck = document.querySelector('.edit-proprio-check');
    const justifProprioWrap = document.querySelector('.edit-justif-proprio-wrap');

    function updateVisibility() {
        const checked = Array.from(typeCredits).filter(c => c.checked).map(c => c.value);
        const hasPTZ = checked.includes('PTZ') || checked.includes('EcoPTZ');
        const hasPrescripteur = checked.includes('Prescripteur');

        if (hasPTZ) travauxCheck.checked = true;
        if (ptzTab) ptzTab.style.display = hasPTZ ? '' : 'none';
        if (courtageWrap) courtageWrap.style.display = hasPrescripteur ? '' : 'none';
        if (ecoSuiviWrap) ecoSuiviWrap.style.display = hasPTZ ? '' : 'none';
        updateTravauxVisibility();
    }

    function updateTravauxVisibility() {
        const checked = Array.from(typeCredits).filter(c => c.checked).map(c => c.value);
        const hasPTZ = checked.includes('PTZ') || checked.includes('EcoPTZ');
        const avecTravaux = travauxCheck.checked;
        if (travauxWrap) travauxWrap.style.display = avecTravaux ? '' : 'none';
        if (ecoptzWrap) ecoptzWrap.style.display = (avecTravaux && hasPTZ) ? '' : 'none';
    }

    function updateProprioVisibility() {
        if (justifProprioWrap) justifProprioWrap.style.display = proprioCheck && proprioCheck.checked ? '' : 'none';
    }

    typeCredits.forEach(cb => cb.addEventListener('change', updateVisibility));
    if (travauxCheck) travauxCheck.addEventListener('change', updateTravauxVisibility);
    if (proprioCheck) proprioCheck.addEventListener('change', updateProprioVisibility);
}

function showAmortissement(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;

    const totalFinancement = parseFloat(d.montant_acquisition||0) + parseFloat(d.frais_notaire||0) + parseFloat(d.frais_agence||0)
        + parseFloat(d.frais_courtage||0) + parseFloat(d.frais_dossier||0) + parseFloat(d.cegc||0) + parseFloat(d.ade||0)
        + parseFloat(d.travaux||0);
    const capital = totalFinancement - parseFloat(d.apport||0);
    const tauxAnnuel = parseFloat(d.taux_emprunt||0) / 100;
    const tauxMensuel = tauxAnnuel / 12;
    const duree = parseInt(d.duree_emprunt||0);

    if (duree <= 0 || capital <= 0) {
        document.getElementById('amortContent').innerHTML = '<div class="alert alert-warning">Données insuffisantes pour générer le tableau (capital ou durée manquant).</div>';
        new bootstrap.Modal(document.getElementById('amortModal')).show();
        return;
    }

    let mensualite;
    if (tauxMensuel > 0) {
        mensualite = capital * tauxMensuel / (1 - Math.pow(1 + tauxMensuel, -duree));
    } else {
        mensualite = capital / duree;
    }

    const coutTotal = mensualite * duree;
    const totalInterets = coutTotal - capital;

    let solde = capital;
    let rows = '';
    let totalIntPaid = 0;
    let totalCapPaid = 0;

    for (let m = 1; m <= duree; m++) {
        const interets = solde * tauxMensuel;
        const capitalRembourse = mensualite - interets;
        solde -= capitalRembourse;
        if (solde < 0) solde = 0;
        totalIntPaid += interets;
        totalCapPaid += capitalRembourse;

        rows += `<tr>
            <td>${m}</td>
            <td>${fmt(mensualite)}</td>
            <td>${fmt(capitalRembourse)}</td>
            <td>${fmt(interets)}</td>
            <td>${fmt(solde)}</td>
        </tr>`;
    }

    document.getElementById('amortContent').innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(capital)} &euro;</div><div class="stat-label">Capital emprunté</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mensualite)} &euro;</div><div class="stat-label">Mensualité</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(totalInterets)} &euro;</div><div class="stat-label">Coût total intérêts</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(coutTotal)} &euro;</div><div class="stat-label">Coût total crédit</div></div></div>
        </div>
        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
            <table class="table table-sm table-striped table-hover">
                <thead class="table-dark" style="position:sticky;top:0;z-index:1;">
                    <tr><th>Mois</th><th>Mensualité</th><th>Capital</th><th>Intérêts</th><th>Solde restant</th></tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot class="table-secondary">
                    <tr><td><strong>Total</strong></td><td><strong>${fmt(coutTotal)}</strong></td><td><strong>${fmt(totalCapPaid)}</strong></td><td><strong>${fmt(totalIntPaid)}</strong></td><td>-</td></tr>
                </tfoot>
            </table>
        </div>`;
    new bootstrap.Modal(document.getElementById('amortModal')).show();
}

function printDossier(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;

    const totalFinancement = getTotalFinancement(d);
    const montantEmprunte = getCapital(d);
    const mensualite = calcMensualite(montantEmprunte, parseFloat(d.taux_emprunt||0), parseInt(d.duree_emprunt||0));
    const tauxEndettement = parseFloat(d.revenus_mensuels||0) > 0
        ? ((mensualite + parseFloat(d.credits_en_cours||0)) / parseFloat(d.revenus_mensuels) * 100).toFixed(1)
        : 'N/A';
    const wfLabel = (workflowLabels[d.workflow_status] || ['Inconnu'])[0];
    const coutCredit = mensualite > 0 ? (mensualite * parseInt(d.duree_emprunt||0) - montantEmprunte) : 0;

    const printArea = document.getElementById('printArea');
    printArea.innerHTML = `
        <h2 style="text-align:center;margin-bottom:10px;">Synthèse - Crédit Immobilier</h2>
        <p style="text-align:center;margin-bottom:5px;font-size:13pt;"><strong>Dossier N° ${escapeHtml(d.numero_personne)}</strong></p>
        <p style="text-align:center;color:#666;margin-bottom:25px;">Généré le ${new Date().toLocaleDateString('fr-FR')} &bull; Statut : <strong>${wfLabel}</strong></p>

        <h3>Informations client</h3>
        <table><tbody>
            <tr><td><strong>N° personne</strong></td><td>${escapeHtml(d.numero_personne)}</td><td><strong>Type client</strong></td><td>${escapeHtml(d.type_client)}</td></tr>
            <tr><td><strong>Occupation</strong></td><td>${escapeHtml(d.type_occupation)}</td><td><strong>Résidence</strong></td><td>${d.type_residence === 'RP' ? 'Principale' : d.type_residence === 'RS' ? 'Secondaire' : (escapeHtml(d.type_residence)||'-')}</td></tr>
            <tr><td><strong>Type bien</strong></td><td>${escapeHtml(d.type_bien)}</td><td><strong>Adresse du bien</strong></td><td>${escapeHtml(d.adresse_bien)||'-'}</td></tr>
        </tbody></table>

        <h3>Plan de financement</h3>
        <table><tbody>
            <tr><td><strong>Montant acquisition</strong></td><td>${fmt(d.montant_acquisition)} &euro;</td><td><strong>Apport personnel</strong></td><td>${fmt(d.apport)} &euro;</td></tr>
            <tr><td><strong>Frais de notaire</strong></td><td>${fmt(d.frais_notaire)} &euro;</td><td><strong>Frais d'agence</strong></td><td>${fmt(d.frais_agence)} &euro;</td></tr>
            <tr><td><strong>Frais de courtage</strong></td><td>${fmt(d.frais_courtage)} &euro;</td><td><strong>Frais de dossier</strong></td><td>${fmt(d.frais_dossier)} &euro;</td></tr>
            <tr><td><strong>CEGC</strong></td><td>${fmt(d.cegc)} &euro;</td><td><strong>ADE (assurance)</strong></td><td>${fmt(d.ade)} &euro;</td></tr>
            <tr><td><strong>Travaux</strong></td><td>${fmt(d.travaux)} &euro;</td><td><strong>Dont EcoPTZ/PTZ</strong></td><td>${fmt(d.dont_ecoptz_ptz)} &euro;</td></tr>
            <tr style="background:#f5f5f5;"><td><strong>Total à financer</strong></td><td><strong>${fmt(totalFinancement)} &euro;</strong></td><td><strong>Capital emprunté</strong></td><td><strong>${fmt(montantEmprunte)} &euro;</strong></td></tr>
        </tbody></table>

        <h3>Conditions du crédit</h3>
        <table><tbody>
            <tr><td><strong>Type de crédit</strong></td><td>${escapeHtml(d.type_credit)}</td><td><strong>Avec travaux</strong></td><td>${d.avec_travaux == 1 ? 'Oui' : 'Non'}</td></tr>
            <tr><td><strong>Taux d'emprunt</strong></td><td>${d.taux_emprunt} %</td><td><strong>Durée</strong></td><td>${d.duree_emprunt} mois (${Math.round(parseInt(d.duree_emprunt||0)/12*10)/10} ans)</td></tr>
            <tr style="background:#f5f5f5;"><td><strong>Mensualité estimée</strong></td><td><strong style="font-size:1.1em;">${fmt(mensualite)} &euro;/mois</strong></td><td><strong>Coût total du crédit</strong></td><td><strong>${fmt(coutCredit)} &euro;</strong></td></tr>
        </tbody></table>

        <h3>Situation financière</h3>
        <table><tbody>
            <tr><td><strong>Revenus mensuels</strong></td><td>${fmt(d.revenus_mensuels)} &euro;</td><td><strong>Charges fixes</strong></td><td>${fmt(d.charges_fixes)} &euro;</td></tr>
            <tr><td><strong>Loyer actuel</strong></td><td>${fmt(d.loyer)} &euro;</td><td><strong>Crédits en cours</strong></td><td>${fmt(d.credits_en_cours)} &euro;</td></tr>
            <tr><td><strong>Épargne disponible</strong></td><td>${fmt(d.epargne)} &euro;</td><td><strong>Taux d'endettement</strong></td><td><strong>${tauxEndettement} %</strong></td></tr>
        </tbody></table>
        ${d.notes ? `<h3>Notes</h3><p style="border:1px solid #ddd;padding:8px;margin:0;">${escapeHtml(d.notes)}</p>` : ''}
    `;

    window.print();
}

// Simulation "Et si..."
function showSimulation(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;
    const capital = getCapital(d);
    const taux = parseFloat(d.taux_emprunt||0);
    const duree = parseInt(d.duree_emprunt||0);
    const revenus = parseFloat(d.revenus_mensuels||0);
    const creditsEnCours = parseFloat(d.credits_en_cours||0);

    document.getElementById('simulContent').innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-md-12"><h6>Dossier : ${escapeHtml(d.numero_personne)} - Capital emprunté : ${fmt(capital)} &euro;</h6></div>
            <div class="col-md-4">
                <label class="form-label">Taux d'emprunt (%)</label>
                <input type="range" class="form-range" id="simTaux" min="0" max="8" step="0.1" value="${taux}" oninput="updateSimulation(${id})">
                <div class="text-center fw-bold" id="simTauxVal">${taux}%</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Durée (mois)</label>
                <input type="range" class="form-range" id="simDuree" min="60" max="360" step="12" value="${duree}" oninput="updateSimulation(${id})">
                <div class="text-center fw-bold" id="simDureeVal">${duree} mois (${(duree/12).toFixed(0)} ans)</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Remboursement anticipé</label>
                <input type="number" class="form-control" id="simRembAnticipe" value="0" step="1000" min="0" oninput="updateSimulation(${id})">
            </div>
        </div>
        <div id="simResults"></div>
        <hr>
        <h6>Hausse de taux : impact sur la mensualité</h6>
        <div id="simTauxTable"></div>
    `;
    updateSimulation(id);
    new bootstrap.Modal(document.getElementById('simulModal')).show();
}

function updateSimulation(id) {
    const d = dossiersData.find(x => x.id == id);
    const capitalBase = getCapital(d);
    const rembAnticipe = parseFloat(document.getElementById('simRembAnticipe').value || 0);
    const capital = capitalBase - rembAnticipe;
    const taux = parseFloat(document.getElementById('simTaux').value);
    const duree = parseInt(document.getElementById('simDuree').value);
    const revenus = parseFloat(d.revenus_mensuels||0);
    const creditsEnCours = parseFloat(d.credits_en_cours||0);

    document.getElementById('simTauxVal').textContent = taux + '%';
    document.getElementById('simDureeVal').textContent = duree + ' mois (' + (duree/12).toFixed(0) + ' ans)';

    const mensualite = calcMensualite(capital > 0 ? capital : 0, taux, duree);
    const coutTotal = mensualite * duree;
    const totalInterets = coutTotal - (capital > 0 ? capital : 0);
    const tauxEnd = revenus > 0 ? ((mensualite + creditsEnCours) / revenus * 100).toFixed(1) : 'N/A';

    // Comparaison avec l'original
    const origMens = calcMensualite(capitalBase, parseFloat(d.taux_emprunt||0), parseInt(d.duree_emprunt||0));
    const origCout = origMens * parseInt(d.duree_emprunt||0);
    const diffMens = mensualite - origMens;
    const diffCout = (mensualite * duree) - origCout;

    document.getElementById('simResults').innerHTML = `
        <div class="row g-3">
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mensualite)} &euro;</div><div class="stat-label">Mensualité</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(totalInterets)} &euro;</div><div class="stat-label">Total intérêts</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number">${getEndettementBadge(tauxEnd)}</div><div class="stat-label">Endettement</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-number" style="color:${diffMens > 0 ? '#dc3545' : '#28a745'}">${diffMens > 0 ? '+' : ''}${fmt(diffMens)} &euro;</div><div class="stat-label">Diff. mensualité</div></div></div>
        </div>
        ${getEndettementAlert(tauxEnd)}
        ${rembAnticipe > 0 ? '<div class="alert alert-info mt-2"><i class="fas fa-info-circle"></i> Économie avec remboursement anticipé de ' + fmt(rembAnticipe) + ' € : <strong>' + fmt(-diffCout) + ' €</strong> sur le coût total.</div>' : ''}
    `;

    // Tableau hausse de taux
    let tauxRows = '';
    for (let t = taux; t <= taux + 2; t += 0.5) {
        const m = calcMensualite(capital > 0 ? capital : 0, t, duree);
        const te = revenus > 0 ? ((m + creditsEnCours) / revenus * 100).toFixed(1) : 'N/A';
        tauxRows += `<tr><td>${t.toFixed(1)}%</td><td>${fmt(m)} &euro;</td><td>${fmt(m * duree)} &euro;</td><td>${getEndettementBadge(te)}</td></tr>`;
    }
    document.getElementById('simTauxTable').innerHTML = `
        <table class="table table-sm table-striped">
            <thead><tr><th>Taux</th><th>Mensualité</th><th>Coût total</th><th>Endettement</th></tr></thead>
            <tbody>${tauxRows}</tbody>
        </table>
    `;
}

// Comparaison de scénarios
function showCompareSelect() {
    if (dossiersData.length < 2) {
        alert('Il faut au moins 2 dossiers pour comparer.');
        return;
    }
    let opts = dossiersData.map(d => `<div class="form-check"><input class="form-check-input compare-check" type="checkbox" value="${d.id}" id="cmp_${d.id}"><label class="form-check-label" for="cmp_${d.id}">${escapeHtml(d.numero_personne)} - ${fmt(d.montant_acquisition)} € @ ${d.taux_emprunt}% / ${d.duree_emprunt} mois</label></div>`).join('');
    document.getElementById('compareContent').innerHTML = `
        <p>Sélectionnez les dossiers à comparer :</p>
        ${opts}
        <button class="btn btn-ce mt-3" onclick="runComparison()"><i class="fas fa-balance-scale"></i> Comparer</button>
        <div id="compareResults" class="mt-3"></div>
    `;
    new bootstrap.Modal(document.getElementById('compareModal')).show();
}

function runComparison() {
    const ids = Array.from(document.querySelectorAll('.compare-check:checked')).map(c => parseInt(c.value));
    if (ids.length < 2) { alert('Sélectionnez au moins 2 dossiers.'); return; }

    const dossiers = ids.map(id => dossiersData.find(d => d.id == id)).filter(Boolean);
    let headers = '<th>Critère</th>' + dossiers.map(d => `<th>${escapeHtml(d.numero_personne)}<br><small>${escapeHtml(d.type_credit)}</small></th>`).join('');

    function row(label, values, highlight) {
        const vals = values.map(v => typeof v === 'number' ? v : 0);
        const best = highlight === 'min' ? Math.min(...vals) : highlight === 'max' ? Math.max(...vals) : null;
        return '<tr><td><strong>' + label + '</strong></td>' + values.map((v, i) => {
            const isBest = best !== null && vals[i] === best;
            return `<td${isBest ? ' class="table-success"' : ''}>${typeof v === 'number' ? fmt(v) + ' €' : v}</td>`;
        }).join('') + '</tr>';
    }

    const data = dossiers.map(d => {
        const capital = getCapital(d);
        const taux = parseFloat(d.taux_emprunt||0);
        const duree = parseInt(d.duree_emprunt||0);
        const mens = calcMensualite(capital, taux, duree);
        const coutTotal = mens * duree;
        const interets = coutTotal - capital;
        const revenus = parseFloat(d.revenus_mensuels||0);
        const te = revenus > 0 ? ((mens + parseFloat(d.credits_en_cours||0)) / revenus * 100).toFixed(1) : 'N/A';
        return { capital, taux, duree, mens, coutTotal, interets, te };
    });

    let tableRows = row('Capital emprunté', data.map(r => r.capital), null);
    tableRows += '<tr><td><strong>Taux</strong></td>' + data.map(r => `<td>${r.taux}%</td>`).join('') + '</tr>';
    tableRows += '<tr><td><strong>Durée</strong></td>' + data.map(r => `<td>${r.duree} mois</td>`).join('') + '</tr>';
    tableRows += row('Mensualité', data.map(r => r.mens), 'min');
    tableRows += row('Total intérêts', data.map(r => r.interets), 'min');
    tableRows += row('Coût total', data.map(r => r.coutTotal), 'min');
    tableRows += '<tr><td><strong>Endettement</strong></td>' + data.map(r => `<td>${getEndettementBadge(r.te)}</td>`).join('') + '</tr>';

    document.getElementById('compareResults').innerHTML = `
        <table class="table table-sm table-bordered table-hover">
            <thead class="table-dark"><tr>${headers}</tr></thead>
            <tbody>${tableRows}</tbody>
        </table>
        <p class="text-muted"><small><i class="fas fa-check-circle text-success"></i> = meilleure valeur</small></p>
    `;
}

// Pré-remplissage depuis calculateur budget
function prefillFromBudget(mode) {
    fetch('index.php?ajax=budget')
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Aucun budget trouvé. Veuillez d\'abord remplir le calculateur budget.'); return; }
            const prefix = mode === 'edit' ? 'edit' : 'add';
            const revenus = parseFloat(data.salaire||0) + parseFloat(data.salaire_conjoint||0)
                + parseFloat(data.autres_revenus||0) + parseFloat(data.autres_revenus_conjoint||0)
                + parseFloat(data.allocations||0) + parseFloat(data.pensions||0)
                + parseFloat(data.pensions_conjoint||0) + parseFloat(data.revenus_fonciers||0)
                + parseFloat(data.revenus_fonciers_conjoint||0);
            const charges = parseFloat(data.assurance_habitation||0) + parseFloat(data.assurance_auto||0)
                + parseFloat(data.assurance_sante||0) + parseFloat(data.impots||0)
                + parseFloat(data.taxe_fonciere||0) + parseFloat(data.taxe_habitation||0)
                + parseFloat(data.electricite_gaz||0) + parseFloat(data.eau||0)
                + parseFloat(data.telephone_internet||0) + parseFloat(data.transport||0)
                + parseFloat(data.alimentation||0) + parseFloat(data.habillement||0)
                + parseFloat(data.sante||0) + parseFloat(data.loisirs||0) + parseFloat(data.divers||0);
            const loyer = parseFloat(data.loyer_charges||0);
            const credits = parseFloat(data.credit_immo||0) + parseFloat(data.credits_conso||0);
            const epargne = parseFloat(data.epargne_mensuelle||0);

            const el = (id) => document.getElementById(id);
            if (el(prefix + 'Revenus')) el(prefix + 'Revenus').value = revenus.toFixed(2);
            if (el(prefix + 'Charges')) el(prefix + 'Charges').value = charges.toFixed(2);
            if (el(prefix + 'Loyer')) el(prefix + 'Loyer').value = loyer.toFixed(2);
            if (el(prefix + 'Credits')) el(prefix + 'Credits').value = credits.toFixed(2);
            if (el(prefix + 'Epargne')) el(prefix + 'Epargne').value = epargne.toFixed(2);

            alert('Données importées du calculateur budget !');
        })
        .catch(() => alert('Erreur lors du chargement des données budget.'));
}

// Initialize conditional logic for add modal
document.addEventListener('DOMContentLoaded', initAddConditionalLogic);

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<style>
.bg-orange { background-color: #fd7e14 !important; }
@media print {
    body * { visibility: hidden !important; }
    #printArea, #printArea * { visibility: visible !important; }
    #printArea {
        display: block !important;
        position: absolute;
        left: 0; top: 0;
        width: 100%;
        font-family: Arial, sans-serif;
        font-size: 11pt;
        color: #000;
    }
    #printArea h2 { font-size: 16pt; border-bottom: 2px solid #333; padding-bottom: 8px; }
    #printArea h3 { font-size: 12pt; margin-top: 15px; margin-bottom: 8px; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    #printArea table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    #printArea td { border: 1px solid #ddd; padding: 4px 8px; font-size: 10pt; }
}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
