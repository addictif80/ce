<?php
$pageTitle = 'Suivi production';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Migration
try { $db->exec("ALTER TABLE suivi_production ADD COLUMN eai_cle VARCHAR(50) DEFAULT '' AFTER produit_vendu"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE suivi_production ADD COLUMN categorie VARCHAR(100) DEFAULT ''"); } catch (PDOException $e) {}
$categoriesProduction = getCategoriesProduction();

// Liste des produits avec clé EAI et unité associées
$produits = [
    ['libelle' => 'ANV',                        'eai_cle' => 'ventes_brut_anv',      'unite' => ''],
    ['libelle' => 'Prêt personnel',              'eai_cle' => 'volume_pret_perso',    'unite' => '€'],
    ['libelle' => 'Carte HDG',                   'eai_cle' => 'cartes_hdg_dd',        'unite' => ''],
    ['libelle' => 'Carte DD',                    'eai_cle' => 'cartes_hdg_dd',        'unite' => ''],
    ['libelle' => 'Izicarte',                    'eai_cle' => 'izicartes',            'unite' => ''],
    ['libelle' => 'Forfait',                     'eai_cle' => 'forfaits',             'unite' => ''],
    ['libelle' => 'Collecte',                    'eai_cle' => 'volume_collecte',      'unite' => '€'],
    ['libelle' => 'Ouverture livret',            'eai_cle' => 'livrets',              'unite' => ''],
    ['libelle' => 'Versement programmé livret',  'eai_cle' => 'livrets',              'unite' => ''],
    ['libelle' => 'PEL / Quadreto',              'eai_cle' => 'pel_quadreto',         'unite' => ''],
    ['libelle' => 'Versement assurance vie',     'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Versement PERi',              'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Ouverture assurance vie',     'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Ouverture PERi',              'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Abonnement Assurance vie',    'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Abonnement PERi',             'eai_cle' => 'assvie_peri',          'unite' => ''],
    ['libelle' => 'Versement parts sociales',    'eai_cle' => 'volume_parts_sociales','unite' => '€'],
    ['libelle' => 'Nouveau sociétaire',          'eai_cle' => 'nouveau_societaire',   'unite' => ''],
    ['libelle' => 'Equipement',                  'eai_cle' => 'equip_jequip',         'unite' => ''],
    ['libelle' => 'Equipement Jeune',            'eai_cle' => 'equip_jequip',         'unite' => ''],
    ['libelle' => 'Bancarisé principal',         'eai_cle' => 'bp_jbp',              'unite' => ''],
    ['libelle' => 'Jeune Bancarisé principal',   'eai_cle' => 'bp_jbp',              'unite' => ''],
];
// Index libelle => produit pour les handlers POST
$produitsIndex = [];
foreach ($produits as $p) $produitsIndex[$p['libelle']] = $p;

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $libelle = $_POST['produit_vendu'] ?? '';
    $eaiCle = $produitsIndex[$libelle]['eai_cle'] ?? '';
    $categorie = in_array($_POST['categorie'] ?? '', $categoriesProduction, true) ? $_POST['categorie'] : '';
    $stmt = $db->prepare("INSERT INTO suivi_production (user_id, date_rdv, categorie, produit_vendu, eai_cle, montant_nombre, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['date_rdv'] ?: null, $categorie, $libelle, $eaiCle, $_POST['montant_nombre'], $_POST['details']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $libelle = $_POST['produit_vendu'] ?? '';
    $eaiCle = $produitsIndex[$libelle]['eai_cle'] ?? '';
    $categorie = in_array($_POST['categorie'] ?? '', $categoriesProduction, true) ? $_POST['categorie'] : '';
    $stmt = $db->prepare("UPDATE suivi_production SET date_rdv = ?, categorie = ?, produit_vendu = ?, eai_cle = ?, montant_nombre = ?, details = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['date_rdv'] ?: null, $categorie, $libelle, $eaiCle, $_POST['montant_nombre'], $_POST['details'], (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM suivi_production WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('suivi_production', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM suivi_production WHERE user_id = ?");
$stmt->execute([$userId]);
$nbTotal = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM suivi_production WHERE user_id = ? AND MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE())");
$stmt->execute([$userId]);
$nbMois = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM suivi_production WHERE user_id = ? ORDER BY date_rdv DESC");
$stmt->execute([$userId]);
$productions = $stmt->fetchAll();
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbTotal ?></div>
            <div class="stat-label">Productions totales</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbMois ?></div>
            <div class="stat-label">Ce mois-ci</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center gap-2">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle production</button>
        <a href="rapport.php" class="btn btn-ce-outline" target="_blank"><i class="fas fa-print"></i> Rapport</a>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les productions</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchProduction" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableProduction">
        <thead>
            <tr>
                <th>Date RDV</th>
                <th>Catégorie</th>
                <th>Produit</th>
                <th>Montant/Nombre</th>
                <th>Détails</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productions as $prod): ?>
            <tr>
                <td><?= formatDateTime($prod['date_rdv']) ?></td>
                <td><?= !empty($prod['categorie']) ? '<span class="badge bg-secondary">' . e($prod['categorie']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                <td><strong><?= e($prod['produit_vendu']) ?></strong></td>
                <td><?= e($prod['montant_nombre']) ?></td>
                <td><?= e(excerpt($prod['details'])) ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $prod['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editProduction(<?= $prod['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette production ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $prod['id'] ?>">
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date du RDV</label>
                            <input type="datetime-local" name="date_rdv" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produit vendu <span class="text-danger">*</span></label>
                            <select name="produit_vendu" class="form-select" required onchange="updateMontantLabel(this, 'addMontantLabel')">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($produits as $p): ?>
                                    <option value="<?= e($p['libelle']) ?>" data-unite="<?= $p['unite'] ?>"><?= e($p['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($categoriesProduction as $cat): ?>
                                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" id="addMontantLabel">Montant / Nombre</label>
                            <input type="text" name="montant_nombre" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Détails</label>
                            <textarea name="details" class="form-control" rows="5"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchProduction', 'tableProduction');

const productionsData = <?= json_encode($productions) ?>;
const produitsData = <?= json_encode($produits) ?>;
const categoriesProduction = <?= json_encode($categoriesProduction) ?>;

function buildCategoriesOptions(selected) {
    return '<option value="">-- Choisir --</option>' + categoriesProduction.map(c =>
        `<option value="${c}" ${c === selected ? 'selected' : ''}>${c}</option>`
    ).join('');
}
const allNotes = {};
<?php
foreach ($productions as $prod) {
    $notes = getNotes('suivi_production', $prod['id']);
    echo "allNotes[{$prod['id']}] = " . json_encode($notes) . ";\n";
}
?>

function formatDT(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}

function toDatetimeLocal(dt) {
    if (!dt) return '';
    return dt.replace(' ', 'T').substring(0, 16);
}

// Met à jour le label Montant/Nombre selon l'unité du produit sélectionné
function updateMontantLabel(selectEl, labelId) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const unite = opt ? opt.dataset.unite : '';
    const label = document.getElementById(labelId);
    if (label) label.textContent = unite === '€' ? 'Montant (€)' : 'Nombre';
}

function buildProduitsOptions(selected) {
    return produitsData.map(p =>
        `<option value="${p.libelle}" data-unite="${p.unite}" ${p.libelle === selected ? 'selected' : ''}>${p.libelle}</option>`
    ).join('');
}

function showDetail(id) {
    const prod = productionsData.find(p => p.id == id);
    if (!prod) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    const produitInfo = produitsData.find(p => p.libelle === prod.produit_vendu);
    const montantLabel = produitInfo && produitInfo.unite === '€' ? 'Montant (€)' : 'Nombre';

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date RDV :</strong> ${formatDT(prod.date_rdv)}</p>
                <p><strong>Catégorie :</strong> ${prod.categorie || '-'}</p>
                <p><strong>Produit :</strong> ${prod.produit_vendu || '-'}</p>
                <p><strong>${montantLabel} :</strong> ${prod.montant_nombre || '-'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Détails :</strong></p>
                <div class="p-3 bg-light rounded">${prod.details || '-'}</div>
            </div>
        </div>
        <div class="notes-section">
            <h5><i class="fas fa-sticky-note"></i> Notes</h5>
            ${notesHtml || '<p class="text-muted">Aucune note</p>'}
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="record_id" value="${id}">
                <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Ajouter une note..." required>
                    <button class="btn btn-ce" type="submit"><i class="fas fa-plus"></i></button>
                </div>
            </form>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editProduction(id) {
    const prod = productionsData.find(p => p.id == id);
    if (!prod) return;

    const produitInfo = produitsData.find(p => p.libelle === prod.produit_vendu);
    const montantLabel = produitInfo && produitInfo.unite === '€' ? 'Montant (€)' : 'Nombre';

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Date du RDV</label>
                    <input type="datetime-local" name="date_rdv" class="form-control" value="${toDatetimeLocal(prod.date_rdv)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Produit vendu <span class="text-danger">*</span></label>
                    <select name="produit_vendu" class="form-select" required onchange="updateMontantLabel(this, 'editMontantLabel')">
                        <option value="">-- Choisir --</option>
                        ${buildProduitsOptions(prod.produit_vendu)}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select">${buildCategoriesOptions(prod.categorie)}</select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="editMontantLabel">${montantLabel}</label>
                    <input type="text" name="montant_nombre" class="form-control" value="${prod.montant_nombre || ''}">
                </div>
                <div class="col-12">
                    <label class="form-label">Détails</label>
                    <textarea name="details" class="form-control" rows="5">${prod.details || ''}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
