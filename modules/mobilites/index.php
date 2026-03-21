<?php
$pageTitle = 'Mobilités entrantes';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// POST: Nouvelle mobilité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO mobilites (user_id, numero_personne, nom_client, banque_depart, is_ce_hors_mp, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, trim($_POST['numero_personne']), trim($_POST['nom_client']), trim($_POST['banque_depart']), isset($_POST['is_ce_hors_mp']) ? 1 : 0, trim($_POST['notes'] ?? '')]);
        header('Location: index.php');
        exit;
    }

    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $fields = [
            'numero_personne','nom_client','banque_depart','etape','notes',
            'doc_carte_identite','doc_justif_domicile','doc_avis_imposition','doc_releves_externes','doc_rib',
            'synthese_faite',
            'type_compte','compte_joint','montant_decouvert','izicarte',
            'mandat_signe','date_fin_mobilite','cloture_demandee','date_cloture_depart',
            'mobiliz_mail_envoye','mobiliz_synthese_recue','mobiliz_04_ouvert','mobiliz_tel_fait','mobiliz_epargnes_a_transferer'
        ];
        $checkboxes = ['doc_carte_identite','doc_justif_domicile','doc_avis_imposition','doc_releves_externes','doc_rib',
                        'synthese_faite','compte_joint','izicarte','mandat_signe','cloture_demandee',
                        'is_ce_hors_mp','mobiliz_mail_envoye','mobiliz_synthese_recue','mobiliz_04_ouvert','mobiliz_tel_fait'];
        $nullableFields = ['type_compte','date_fin_mobilite','date_cloture_depart','montant_decouvert'];
        $sets = ['is_ce_hors_mp = ?'];
        $vals = [isset($_POST['is_ce_hors_mp']) ? 1 : 0];
        foreach ($fields as $f) {
            if (in_array($f, $checkboxes)) {
                $sets[] = "$f = ?";
                $vals[] = isset($_POST[$f]) ? 1 : 0;
            } else {
                $sets[] = "$f = ?";
                $val = $_POST[$f] ?? null;
                $vals[] = (in_array($f, $nullableFields) && $val === '') ? null : $val;
            }
        }
        $vals[] = $id;
        $vals[] = $userId;
        $db->prepare("UPDATE mobilites SET " . implode(', ', $sets) . " WHERE id = ? AND user_id = ?")->execute($vals);
        header('Location: index.php?open=' . $id);
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM mobilites WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        header('Location: index.php');
        exit;
    }

    // Cartes
    if ($_POST['action'] === 'add_carte') {
        $mid = (int)$_POST['mobilite_id'];
        $stmt = $db->prepare("INSERT INTO mobilites_cartes (mobilite_id, titulaire, type_carte, type_debit) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mid, trim($_POST['titulaire']), $_POST['type_carte'], $_POST['type_debit']]);
        header('Location: index.php?open=' . $mid);
        exit;
    }
    if ($_POST['action'] === 'update_carte') {
        $id = (int)$_POST['id'];
        $mid = (int)$_POST['mobilite_id'];
        $stmt = $db->prepare("UPDATE mobilites_cartes SET titulaire=?, type_carte=?, type_debit=?, commandee=?, date_commande=?, recue=?, date_reception=?, remise_client=?, date_remise=? WHERE id=?");
        $stmt->execute([
            trim($_POST['titulaire']), $_POST['type_carte'], $_POST['type_debit'],
            isset($_POST['commandee']) ? 1 : 0, $_POST['date_commande'] ?: null,
            isset($_POST['recue']) ? 1 : 0, $_POST['date_reception'] ?: null,
            isset($_POST['remise_client']) ? 1 : 0, $_POST['date_remise'] ?: null,
            $id
        ]);
        header('Location: index.php?open=' . $mid);
        exit;
    }
    if ($_POST['action'] === 'delete_carte') {
        $mid = $db->query("SELECT mobilite_id FROM mobilites_cartes WHERE id = " . (int)$_POST['id'])->fetchColumn();
        $db->prepare("DELETE FROM mobilites_cartes WHERE id = ?")->execute([(int)$_POST['id']]);
        header('Location: index.php?open=' . (int)$mid);
        exit;
    }

    // Chéquiers
    if ($_POST['action'] === 'add_chequier') {
        $mid = (int)$_POST['mobilite_id'];
        $stmt = $db->prepare("INSERT INTO mobilites_chequiers (mobilite_id, titulaire) VALUES (?, ?)");
        $stmt->execute([$mid, trim($_POST['titulaire'])]);
        header('Location: index.php?open=' . $mid);
        exit;
    }
    if ($_POST['action'] === 'update_chequier') {
        $id = (int)$_POST['id'];
        $mid = (int)$_POST['mobilite_id'];
        $stmt = $db->prepare("UPDATE mobilites_chequiers SET titulaire=?, commande=?, date_commande=?, recu=?, date_reception=?, remis_client=?, date_remise=? WHERE id=?");
        $stmt->execute([
            trim($_POST['titulaire']),
            isset($_POST['commande']) ? 1 : 0, $_POST['date_commande'] ?: null,
            isset($_POST['recu']) ? 1 : 0, $_POST['date_reception'] ?: null,
            isset($_POST['remis_client']) ? 1 : 0, $_POST['date_remise'] ?: null,
            $id
        ]);
        header('Location: index.php?open=' . $mid);
        exit;
    }
    if ($_POST['action'] === 'delete_chequier') {
        $mid = $db->query("SELECT mobilite_id FROM mobilites_chequiers WHERE id = " . (int)$_POST['id'])->fetchColumn();
        $db->prepare("DELETE FROM mobilites_chequiers WHERE id = ?")->execute([(int)$_POST['id']]);
        header('Location: index.php?open=' . (int)$mid);
        exit;
    }
}

// Récupération des mobilités de l'utilisateur
$stmt = $db->prepare("SELECT * FROM mobilites WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$mobilites = $stmt->fetchAll();

// Récupération des cartes et chéquiers pour chaque mobilité
$cartes = [];
$chequiers = [];
$mobIds = array_column($mobilites, 'id');
if ($mobIds) {
    $in = implode(',', array_map('intval', $mobIds));
    foreach ($db->query("SELECT * FROM mobilites_cartes WHERE mobilite_id IN ($in) ORDER BY id")->fetchAll() as $c) {
        $cartes[$c['mobilite_id']][] = $c;
    }
    foreach ($db->query("SELECT * FROM mobilites_chequiers WHERE mobilite_id IN ($in) ORDER BY id")->fetchAll() as $c) {
        $chequiers[$c['mobilite_id']][] = $c;
    }
}

$etapeLabels = ['rdv' => '1. RDV', 'synthese' => '2. Synthèse', 'ouverture' => '3. Ouverture', 'mobilite' => '4. Mobilité', 'termine' => 'Terminé'];
$etapeBadges = ['rdv' => 'bg-info', 'synthese' => 'bg-primary', 'ouverture' => 'bg-warning text-dark', 'mobilite' => 'bg-secondary', 'termine' => 'bg-success'];

// Stats
$total = count($mobilites);
$enCours = count(array_filter($mobilites, fn($m) => $m['etape'] !== 'termine'));
$termines = $total - $enCours;
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-number"><?= $total ?></div><div class="stat-label">Total mobilités</div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-number"><?= $enCours ?></div><div class="stat-label">En cours</div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-number"><?= $termines ?></div><div class="stat-label">Terminées</div></div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle mobilité</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-exchange-alt"></i> Mes mobilités</h3>
        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchMob" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table" id="tableMob">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>N° personne</th>
                <th>Banque départ</th>
                <th>Type</th>
                <th>Étape</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mobilites as $m): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($m['date_ajout'])) ?></td>
                <td><strong><?= e($m['nom_client']) ?></strong></td>
                <td><?= e($m['numero_personne']) ?></td>
                <td><?= e($m['banque_depart']) ?></td>
                <td><?= $m['is_ce_hors_mp'] ? '<span class="badge bg-warning text-dark">CE hors MP</span>' : '<span class="badge bg-info">Standard</span>' ?></td>
                <td><span class="badge <?= $etapeBadges[$m['etape']] ?>"><?= $etapeLabels[$m['etape']] ?></span></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="openDetail(<?= $m['id'] ?>)" title="Gérer"><i class="fas fa-folder-open"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette mobilité et toutes ses données ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle mobilité</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nom client *</label><input type="text" name="nom_client" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">N° personne</label><input type="text" name="numero_personne" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Banque de départ</label><input type="text" name="banque_depart" class="form-control"></div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check"><input type="checkbox" name="is_ce_hors_mp" class="form-check-input" id="addCeHorsMP"><label class="form-check-label" for="addCeHorsMP">CE hors MP (Mobiliz)</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détail / Édition (contenu dynamique) -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-folder-open"></i> Gestion mobilité</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<!-- Modal Ajout Carte -->
<div class="modal fade modal-fullscreen-custom" id="addCarteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-credit-card"></i> Ajouter une carte</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_carte">
                    <input type="hidden" name="mobilite_id" id="carteModalMobId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Titulaire</label><input type="text" name="titulaire" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Type carte</label>
                            <select name="type_carte" class="form-select">
                                <option value="VCTRL_Syst">Visa CTRL Systématique</option>
                                <option value="VCTRL_casiSyst">Visa CTRL Quasi-Syst.</option>
                                <option value="VClassic">Visa Classic</option>
                                <option value="V1er">Visa 1er</option>
                                <option value="VPlatinium">Visa Platinium</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Type débit</label>
                            <select name="type_debit" class="form-select"><option value="immediat">Immédiat</option><option value="differe">Différé</option></select>
                        </div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Ajouter</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout Chéquier -->
<div class="modal fade modal-fullscreen-custom" id="addChequierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-money-check"></i> Ajouter un chéquier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_chequier">
                    <input type="hidden" name="mobilite_id" id="chequierModalMobId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Titulaire</label><input type="text" name="titulaire" class="form-control"></div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Ajouter</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchMob', 'tableMob');

const mobilites = <?= json_encode($mobilites) ?>;
const cartes = <?= json_encode($cartes) ?>;
const chequiers = <?= json_encode($chequiers) ?>;

const typeCarteLabels = {VCTRL_Syst:'Visa CTRL Syst.',VCTRL_casiSyst:'Visa CTRL Quasi-Syst.',VClassic:'Visa Classic',V1er:'Visa 1er',VPlatinium:'Visa Platinium'};
const typeCompteOptions = ['CDD','OCF','Initial','Confort','Optimal'];

function chk(val) { return val == 1 ? 'checked' : ''; }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function fmtDate(d) { if (!d) return '-'; const p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }

function openDetail(id) {
    const m = mobilites.find(x => x.id == id);
    if (!m) return;
    const mc = cartes[id] || [];
    const mq = chequiers[id] || [];
    const isMobiliz = m.is_ce_hors_mp == 1;

    let cartesHtml = mc.map(c => `
        <tr>
            <td>${esc(c.titulaire)}</td>
            <td>${typeCarteLabels[c.type_carte] || c.type_carte}</td>
            <td>${c.type_debit === 'differe' ? 'Différé' : 'Immédiat'}</td>
            <td>${c.commandee == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_commande) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>${c.recue == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_reception) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>${c.remise_client == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_remise) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>
                <button class="btn btn-sm btn-ce-outline" onclick="editCarte(${c.id})"><i class="fas fa-edit"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="action" value="delete_carte"><input type="hidden" name="id" value="${c.id}"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>`).join('');

    let cheqHtml = mq.map(c => `
        <tr>
            <td>${esc(c.titulaire)}</td>
            <td>${c.commande == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_commande) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>${c.recu == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_reception) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>${c.remis_client == 1 ? '<i class="fas fa-check text-success"></i> '+fmtDate(c.date_remise) : '<i class="fas fa-times text-danger"></i>'}</td>
            <td>
                <button class="btn btn-sm btn-ce-outline" onclick="editChequier(${c.id})"><i class="fas fa-edit"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="action" value="delete_chequier"><input type="hidden" name="id" value="${c.id}"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>`).join('');

    let typeCompteSelect = typeCompteOptions.map(t => `<option value="${t}" ${m.type_compte===t?'selected':''}>${t}</option>`).join('');

    let mobilizSection = '';
    if (isMobiliz) {
        mobilizSection = `
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning text-dark"><i class="fas fa-exchange-alt"></i> Spécificités Mobiliz (CE hors MP)</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="mobiliz_mail_envoye" class="form-check-input" ${chk(m.mobiliz_mail_envoye)}><label class="form-check-label">Mail Mobiliz envoyé</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="mobiliz_synthese_recue" class="form-check-input" ${chk(m.mobiliz_synthese_recue)}><label class="form-check-label">Synthèse Mobiliz reçue</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="mobiliz_04_ouvert" class="form-check-input" ${chk(m.mobiliz_04_ouvert)}><label class="form-check-label">04 ouvert</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="mobiliz_tel_fait" class="form-check-input" ${chk(m.mobiliz_tel_fait)}><label class="form-check-label">Appel tél. fait</label></div></div>
                    <div class="col-12"><label class="form-label">Épargnes à transférer</label><textarea name="mobiliz_epargnes_a_transferer" class="form-control" rows="2">${esc(m.mobiliz_epargnes_a_transferer)}</textarea></div>
                </div>
            </div>
        </div>`;
    }

    document.getElementById('detailContent').innerHTML = `
    <form method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="${m.id}">

        <!-- Infos générales -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user"></i> Informations client</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Nom client</label><input type="text" name="nom_client" class="form-control" value="${esc(m.nom_client)}"></div>
                    <div class="col-md-4"><label class="form-label">N° personne</label><input type="text" name="numero_personne" class="form-control" value="${esc(m.numero_personne)}"></div>
                    <div class="col-md-4"><label class="form-label">Banque départ</label><input type="text" name="banque_depart" class="form-control" value="${esc(m.banque_depart)}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Étape</label>
                        <select name="etape" class="form-select">
                            <option value="rdv" ${m.etape==='rdv'?'selected':''}>1. RDV</option>
                            <option value="synthese" ${m.etape==='synthese'?'selected':''}>2. Synthèse</option>
                            <option value="ouverture" ${m.etape==='ouverture'?'selected':''}>3. Ouverture</option>
                            <option value="mobilite" ${m.etape==='mobilite'?'selected':''}>4. Mobilité</option>
                            <option value="termine" ${m.etape==='termine'?'selected':''}>Terminé</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_ce_hors_mp" class="form-check-input" ${chk(m.is_ce_hors_mp)}><label class="form-check-label">CE hors MP (Mobiliz)</label></div></div>
                </div>
            </div>
        </div>

        <!-- Étape 1 : Documents -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-file-alt"></i> Étape 1 : Documents RDV</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="doc_carte_identite" class="form-check-input" ${chk(m.doc_carte_identite)}><label class="form-check-label">Carte d'identité</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="doc_justif_domicile" class="form-check-input" ${chk(m.doc_justif_domicile)}><label class="form-check-label">Justificatif de domicile</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="doc_avis_imposition" class="form-check-input" ${chk(m.doc_avis_imposition)}><label class="form-check-label">Avis d'imposition</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="doc_releves_externes" class="form-check-input" ${chk(m.doc_releves_externes)}><label class="form-check-label">Relevés comptes externes</label></div></div>
                    <div class="col-md-6"><div class="form-check"><input type="checkbox" name="doc_rib" class="form-check-input" ${chk(m.doc_rib)}><label class="form-check-label">RIB</label></div></div>
                </div>
            </div>
        </div>

        <!-- Étape 2 : Synthèse -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-clipboard-check"></i> Étape 2 : Synthèse client</div>
            <div class="card-body">
                <div class="form-check"><input type="checkbox" name="synthese_faite" class="form-check-input" ${chk(m.synthese_faite)}><label class="form-check-label">Synthèse réalisée</label></div>
            </div>
        </div>

        <!-- Étape 3 : Ouverture -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-university"></i> Étape 3 : Ouverture du compte</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Type de compte</label><select name="type_compte" class="form-select"><option value="">--</option>${typeCompteSelect}</select></div>
                    <div class="col-md-4"><label class="form-label">Montant découvert</label><input type="number" step="0.01" name="montant_decouvert" class="form-control" value="${m.montant_decouvert || 0}"></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="compte_joint" class="form-check-input" ${chk(m.compte_joint)}><label class="form-check-label">Compte joint</label></div></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="izicarte" class="form-check-input" ${chk(m.izicarte)}><label class="form-check-label">Izicarte</label></div></div>
                </div>
            </div>
        </div>

        <!-- Étape 4 : Mobilité -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-file-signature"></i> Étape 4 : Demande de mobilité</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="form-check"><input type="checkbox" name="mandat_signe" class="form-check-input" ${chk(m.mandat_signe)}><label class="form-check-label">Mandat signé</label></div></div>
                    <div class="col-md-3"><label class="form-label">Date fin mobilité</label><input type="date" name="date_fin_mobilite" class="form-control" value="${m.date_fin_mobilite || ''}"></div>
                    <div class="col-md-3"><div class="form-check"><input type="checkbox" name="cloture_demandee" class="form-check-input" ${chk(m.cloture_demandee)}><label class="form-check-label">Clôture demandée</label></div></div>
                    <div class="col-md-3"><label class="form-label">Date clôture départ</label><input type="date" name="date_cloture_depart" class="form-control" value="${m.date_cloture_depart || ''}"></div>
                </div>
            </div>
        </div>

        ${mobilizSection}

        <!-- Notes -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-sticky-note"></i> Notes</div>
            <div class="card-body"><textarea name="notes" class="form-control" rows="3">${esc(m.notes)}</textarea></div>
        </div>

        <button type="submit" class="btn btn-ce mb-4"><i class="fas fa-save"></i> Enregistrer les modifications</button>
    </form>

    <!-- Cartes bancaires -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-credit-card"></i> Cartes bancaires</span>
            <button class="btn btn-sm btn-ce" onclick="addCarte(${m.id})"><i class="fas fa-plus"></i> Ajouter</button>
        </div>
        <div class="card-body">
            ${mc.length ? '<table class="table table-sm"><thead><tr><th>Titulaire</th><th>Type</th><th>Débit</th><th>Commandée</th><th>Reçue</th><th>Remise</th><th></th></tr></thead><tbody>'+cartesHtml+'</tbody></table>' : '<p class="text-muted mb-0">Aucune carte</p>'}
        </div>
    </div>

    <!-- Chéquiers -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-money-check"></i> Chéquiers</span>
            <button class="btn btn-sm btn-ce" onclick="addChequier(${m.id})"><i class="fas fa-plus"></i> Ajouter</button>
        </div>
        <div class="card-body">
            ${mq.length ? '<table class="table table-sm"><thead><tr><th>Titulaire</th><th>Commandé</th><th>Reçu</th><th>Remis</th><th></th></tr></thead><tbody>'+cheqHtml+'</tbody></table>' : '<p class="text-muted mb-0">Aucun chéquier</p>'}
        </div>
    </div>`;

    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function addCarte(mobId) {
    document.getElementById('carteModalMobId').value = mobId;
    bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('addCarteModal')).show();
}
function addChequier(mobId) {
    document.getElementById('chequierModalMobId').value = mobId;
    bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('addChequierModal')).show();
}

function editCarte(id) {
    const allCartes = Object.values(cartes).flat();
    const c = allCartes.find(x => x.id == id);
    if (!c) return;
    const typeOptions = Object.entries(typeCarteLabels).map(([k,v]) => `<option value="${k}" ${c.type_carte===k?'selected':''}>${v}</option>`).join('');
    document.getElementById('detailContent').innerHTML = `
    <form method="POST">
        <input type="hidden" name="action" value="update_carte">
        <input type="hidden" name="id" value="${c.id}">
        <input type="hidden" name="mobilite_id" value="${c.mobilite_id}">
        <h5 class="mb-3">Modifier la carte</h5>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Titulaire</label><input type="text" name="titulaire" class="form-control" value="${esc(c.titulaire)}"></div>
            <div class="col-md-4"><label class="form-label">Type</label><select name="type_carte" class="form-select">${typeOptions}</select></div>
            <div class="col-md-4"><label class="form-label">Débit</label><select name="type_debit" class="form-select"><option value="immediat" ${c.type_debit==='immediat'?'selected':''}>Immédiat</option><option value="differe" ${c.type_debit==='differe'?'selected':''}>Différé</option></select></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="commandee" class="form-check-input" ${chk(c.commandee)}><label class="form-check-label">Commandée</label></div><input type="date" name="date_commande" class="form-control mt-1" value="${c.date_commande || ''}"></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="recue" class="form-check-input" ${chk(c.recue)}><label class="form-check-label">Reçue</label></div><input type="date" name="date_reception" class="form-control mt-1" value="${c.date_reception || ''}"></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="remise_client" class="form-check-input" ${chk(c.remise_client)}><label class="form-check-label">Remise au client</label></div><input type="date" name="date_remise" class="form-control mt-1" value="${c.date_remise || ''}"></div>
            <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
        </div>
    </form>`;
}

function editChequier(id) {
    const allCheq = Object.values(chequiers).flat();
    const c = allCheq.find(x => x.id == id);
    if (!c) return;
    document.getElementById('detailContent').innerHTML = `
    <form method="POST">
        <input type="hidden" name="action" value="update_chequier">
        <input type="hidden" name="id" value="${c.id}">
        <input type="hidden" name="mobilite_id" value="${c.mobilite_id}">
        <h5 class="mb-3">Modifier le chéquier</h5>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Titulaire</label><input type="text" name="titulaire" class="form-control" value="${esc(c.titulaire)}"></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="commande" class="form-check-input" ${chk(c.commande)}><label class="form-check-label">Commandé</label></div><input type="date" name="date_commande" class="form-control mt-1" value="${c.date_commande || ''}"></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="recu" class="form-check-input" ${chk(c.recu)}><label class="form-check-label">Reçu</label></div><input type="date" name="date_reception" class="form-control mt-1" value="${c.date_reception || ''}"></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="remis_client" class="form-check-input" ${chk(c.remis_client)}><label class="form-check-label">Remis au client</label></div><input type="date" name="date_remise" class="form-control mt-1" value="${c.date_remise || ''}"></div>
            <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
        </div>
    </form>`;
}

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    openDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
