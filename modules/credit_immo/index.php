<?php
$pageTitle = 'Crédit immobilier';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

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

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO credit_immobilier (user_id, numero_personne, type_client, type_occupation, type_residence, type_bien, proprietaire_logement, adresse_bien,
        type_credit, avec_travaux, montant_acquisition, frais_notaire, frais_agence, frais_courtage, frais_dossier, cegc, ade, travaux, dont_ecoptz_ptz, taux_emprunt, duree_emprunt, apport,
        ptz_demande, ptz_type, ptz_nombre_bouquets,
        revenus_mensuels, charges_fixes, loyer, credits_en_cours, epargne)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $_POST['numero_personne'] ?? '',
        $_POST['type_client'] ?? 'Particulier',
        $_POST['type_occupation'] ?? null,
        $_POST['type_residence'] ?? null,
        $_POST['type_bien'] ?? null,
        isset($_POST['proprietaire_logement']) ? 1 : 0,
        $_POST['adresse_bien'] ?? '',
        $_POST['type_credit'] ?? '',
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
        $_POST['ptz_demande'] ?? null,
        $_POST['ptz_type'] ?? null,
        $_POST['ptz_nombre_bouquets'] ?? '',
        (float)($_POST['revenus_mensuels'] ?? 0),
        (float)($_POST['charges_fixes'] ?? 0),
        (float)($_POST['loyer'] ?? 0),
        (float)($_POST['credits_en_cours'] ?? 0),
        (float)($_POST['epargne'] ?? 0)
    ]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE credit_immobilier SET
        numero_personne = ?, type_client = ?, type_occupation = ?, type_residence = ?, type_bien = ?, proprietaire_logement = ?, adresse_bien = ?,
        type_credit = ?, avec_travaux = ?, montant_acquisition = ?, frais_notaire = ?, frais_agence = ?, frais_courtage = ?, frais_dossier = ?, cegc = ?, ade = ?, travaux = ?, dont_ecoptz_ptz = ?, taux_emprunt = ?, duree_emprunt = ?, apport = ?,
        ptz_demande = ?, ptz_type = ?, ptz_nombre_bouquets = ?,
        revenus_mensuels = ?, charges_fixes = ?, loyer = ?, credits_en_cours = ?, epargne = ?,
        suivi_offre_signee_date = ?,
        updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([
        $_POST['numero_personne'] ?? '',
        $_POST['type_client'] ?? 'Particulier',
        $_POST['type_occupation'] ?? null,
        $_POST['type_residence'] ?? null,
        $_POST['type_bien'] ?? null,
        isset($_POST['proprietaire_logement']) ? 1 : 0,
        $_POST['adresse_bien'] ?? '',
        $_POST['type_credit'] ?? '',
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
        $_POST['ptz_demande'] ?? null,
        $_POST['ptz_type'] ?? null,
        $_POST['ptz_nombre_bouquets'] ?? '',
        (float)($_POST['revenus_mensuels'] ?? 0),
        (float)($_POST['charges_fixes'] ?? 0),
        (float)($_POST['loyer'] ?? 0),
        (float)($_POST['credits_en_cours'] ?? 0),
        (float)($_POST['epargne'] ?? 0),
        !empty($_POST['suivi_offre_signee_date']) ? $_POST['suivi_offre_signee_date'] : null,
        $id, $userId
    ]);
    header('Location: index.php');
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

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($dossiers) ?></div>
            <div class="stat-label">Dossiers crédit immo</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-number"><?= count(array_filter($dossiers, fn($d) => $d['suivi_offre_signee'])) ?></div>
            <div class="stat-label">Offres signées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau dossier</button>
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
                <th>Type client</th>
                <th>Type crédit</th>
                <th>Montant</th>
                <th>Durée</th>
                <th>Taux</th>
                <th>Offre signée</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dossiers as $d): ?>
            <tr>
                <td><?= formatDate($d['date_ajout']) ?></td>
                <td><?= e($d['numero_personne']) ?></td>
                <td><?= e($d['type_client']) ?></td>
                <td><?= e($d['type_credit']) ?></td>
                <td><?= number_format((float)$d['montant_acquisition'], 0, ',', ' ') ?> &euro;</td>
                <td><?= $d['duree_emprunt'] ?> mois</td>
                <td><?= $d['taux_emprunt'] ?> %</td>
                <td>
                    <input type="checkbox" class="form-check-input" <?= $d['suivi_offre_signee'] ? 'checked' : '' ?>
                        onchange="toggleStatus('index.php', <?= $d['id'] ?>, 'suivi_offre_signee', this)">
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDossier(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
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
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPTZ">PTZ/EcoPTZ</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSituation">Situation financière</a></li>
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
                                <div class="col-md-4">
                                    <label class="form-label">Type de crédit</label>
                                    <input type="text" name="type_credit" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="avec_travaux" id="addTravaux">
                                        <label class="form-check-label" for="addTravaux">Avec travaux</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant acquisition</label>
                                    <input type="number" step="0.01" name="montant_acquisition" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais notaire</label>
                                    <input type="number" step="0.01" name="frais_notaire" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais agence</label>
                                    <input type="number" step="0.01" name="frais_agence" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais courtage</label>
                                    <input type="number" step="0.01" name="frais_courtage" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frais dossier</label>
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
                                <div class="col-md-3">
                                    <label class="form-label">Travaux</label>
                                    <input type="number" step="0.01" name="travaux" class="form-control" value="0">
                                </div>
                                <div class="col-md-3">
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
                            </div>
                        </div>
                        <!-- PTZ -->
                        <div class="tab-pane fade" id="tabPTZ">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Type de demande PTZ</label>
                                    <select name="ptz_demande" class="form-select">
                                        <option value="">--</option>
                                        <option value="Initiale">Initiale</option>
                                        <option value="Complementaire">Complémentaire</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type PTZ</label>
                                    <select name="ptz_type" class="form-select">
                                        <option value="">--</option>
                                        <option value="Perf globale">Performance globale</option>
                                        <option value="Bouquets">Bouquets</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de bouquets</label>
                                    <input type="text" name="ptz_nombre_bouquets" class="form-control">
                                </div>
                            </div>
                        </div>
                        <!-- Situation financière -->
                        <div class="tab-pane fade" id="tabSituation">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Revenus mensuels</label>
                                    <input type="number" step="0.01" name="revenus_mensuels" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Charges fixes</label>
                                    <input type="number" step="0.01" name="charges_fixes" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Loyer actuel</label>
                                    <input type="number" step="0.01" name="loyer" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Crédits en cours</label>
                                    <input type="number" step="0.01" name="credits_en_cours" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Épargne</label>
                                    <input type="number" step="0.01" name="epargne" class="form-control" value="0">
                                </div>
                            </div>
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

function showDetail(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;

    const totalFinancement = parseFloat(d.montant_acquisition||0) + parseFloat(d.frais_notaire||0) + parseFloat(d.frais_agence||0)
        + parseFloat(d.frais_courtage||0) + parseFloat(d.frais_dossier||0) + parseFloat(d.cegc||0) + parseFloat(d.ade||0)
        + parseFloat(d.travaux||0);
    const montantEmprunte = totalFinancement - parseFloat(d.apport||0);
    const tauxMensuel = parseFloat(d.taux_emprunt||0) / 100 / 12;
    const duree = parseInt(d.duree_emprunt||0);
    let mensualite = 0;
    if (tauxMensuel > 0 && duree > 0) {
        mensualite = montantEmprunte * tauxMensuel / (1 - Math.pow(1 + tauxMensuel, -duree));
    } else if (duree > 0) {
        mensualite = montantEmprunte / duree;
    }
    const tauxEndettement = parseFloat(d.revenus_mensuels||0) > 0
        ? ((mensualite + parseFloat(d.credits_en_cours||0)) / parseFloat(d.revenus_mensuels) * 100).toFixed(1)
        : 'N/A';
    const resteAVivre = parseFloat(d.revenus_mensuels||0) - mensualite - parseFloat(d.charges_fixes||0) - parseFloat(d.credits_en_cours||0);

    document.getElementById('detailContent').innerHTML = `
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
                    <tr class="table-warning"><td><strong>Mensualité estimée</strong></td><td><strong>${fmt(mensualite)} &euro;</strong></td></tr>
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
                    <tr class="table-warning"><td><strong>Taux d'endettement</strong></td><td><strong>${tauxEndettement}%</strong></td></tr>
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
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function sel(name, val, options) {
    return options.map(o => `<option value="${o[0]}" ${val === o[0] ? 'selected' : ''}>${o[1]}</option>`).join('');
}

function editDossier(id) {
    const d = dossiersData.find(x => x.id == id);
    if (!d) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#eTabClient">Client</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#eTabCredit">Crédit</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#eTabPTZ">PTZ/EcoPTZ</a></li>
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
                        <div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="proprietaire_logement" ${d.proprietaire_logement == 1 ? 'checked' : ''}><label class="form-check-label">Propriétaire logement</label></div></div>
                        <div class="col-12"><label class="form-label">Adresse bien</label><textarea name="adresse_bien" class="form-control" rows="2">${escapeHtml(d.adresse_bien)}</textarea></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabCredit">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Type crédit</label><input type="text" name="type_credit" class="form-control" value="${escapeHtml(d.type_credit)}"></div>
                        <div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="avec_travaux" ${d.avec_travaux == 1 ? 'checked' : ''}><label class="form-check-label">Avec travaux</label></div></div>
                        <div class="col-md-4"><label class="form-label">Montant acquisition</label><input type="number" step="0.01" name="montant_acquisition" class="form-control" value="${d.montant_acquisition}"></div>
                        <div class="col-md-3"><label class="form-label">Frais notaire</label><input type="number" step="0.01" name="frais_notaire" class="form-control" value="${d.frais_notaire}"></div>
                        <div class="col-md-3"><label class="form-label">Frais agence</label><input type="number" step="0.01" name="frais_agence" class="form-control" value="${d.frais_agence}"></div>
                        <div class="col-md-3"><label class="form-label">Frais courtage</label><input type="number" step="0.01" name="frais_courtage" class="form-control" value="${d.frais_courtage}"></div>
                        <div class="col-md-3"><label class="form-label">Frais dossier</label><input type="number" step="0.01" name="frais_dossier" class="form-control" value="${d.frais_dossier}"></div>
                        <div class="col-md-3"><label class="form-label">CEGC</label><input type="number" step="0.01" name="cegc" class="form-control" value="${d.cegc}"></div>
                        <div class="col-md-3"><label class="form-label">ADE</label><input type="number" step="0.01" name="ade" class="form-control" value="${d.ade}"></div>
                        <div class="col-md-3"><label class="form-label">Travaux</label><input type="number" step="0.01" name="travaux" class="form-control" value="${d.travaux}"></div>
                        <div class="col-md-3"><label class="form-label">Dont EcoPTZ/PTZ</label><input type="number" step="0.01" name="dont_ecoptz_ptz" class="form-control" value="${d.dont_ecoptz_ptz}"></div>
                        <div class="col-md-4"><label class="form-label">Taux (%)</label><input type="number" step="0.001" name="taux_emprunt" class="form-control" value="${d.taux_emprunt}"></div>
                        <div class="col-md-4"><label class="form-label">Durée (mois)</label><input type="number" name="duree_emprunt" class="form-control" value="${d.duree_emprunt}"></div>
                        <div class="col-md-4"><label class="form-label">Apport</label><input type="number" step="0.01" name="apport" class="form-control" value="${d.apport}"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabPTZ">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Demande PTZ</label><select name="ptz_demande" class="form-select"><option value="">--</option>${sel('', d.ptz_demande, [['Initiale','Initiale'],['Complementaire','Complémentaire']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Type PTZ</label><select name="ptz_type" class="form-select"><option value="">--</option>${sel('', d.ptz_type, [['Perf globale','Performance globale'],['Bouquets','Bouquets']])}</select></div>
                        <div class="col-md-4"><label class="form-label">Nombre bouquets</label><input type="text" name="ptz_nombre_bouquets" class="form-control" value="${escapeHtml(d.ptz_nombre_bouquets)}"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabSituation">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Revenus mensuels</label><input type="number" step="0.01" name="revenus_mensuels" class="form-control" value="${d.revenus_mensuels}"></div>
                        <div class="col-md-4"><label class="form-label">Charges fixes</label><input type="number" step="0.01" name="charges_fixes" class="form-control" value="${d.charges_fixes}"></div>
                        <div class="col-md-4"><label class="form-label">Loyer actuel</label><input type="number" step="0.01" name="loyer" class="form-control" value="${d.loyer}"></div>
                        <div class="col-md-4"><label class="form-label">Crédits en cours</label><input type="number" step="0.01" name="credits_en_cours" class="form-control" value="${d.credits_en_cours}"></div>
                        <div class="col-md-4"><label class="form-label">Épargne</label><input type="number" step="0.01" name="epargne" class="form-control" value="${d.epargne}"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="eTabSuivi">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Date offre signée</label><input type="date" name="suivi_offre_signee_date" class="form-control" value="${d.suivi_offre_signee_date || ''}"></div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
