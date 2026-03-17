<?php
$pageTitle = 'Suivi production';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO suivi_production (user_id, date_rdv, categorie, produit_vendu, montant_nombre, details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['date_rdv'] ?: null, $_POST['categorie'], $_POST['produit_vendu'], $_POST['montant_nombre'], $_POST['details']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE suivi_production SET date_rdv = ?, categorie = ?, produit_vendu = ?, montant_nombre = ?, details = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['date_rdv'] ?: null, $_POST['categorie'], $_POST['produit_vendu'], $_POST['montant_nombre'], $_POST['details'], (int)$_POST['id'], $userId]);
    header('Location: index.php');
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
    header('Location: index.php');
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

$categories = ['Banca', 'Epargne', 'Placement', 'Credit', 'Assurance'];
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
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle production</button>
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
                <th>Produit vendu</th>
                <th>Montant/Nombre</th>
                <th>Détails</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productions as $prod): ?>
            <tr>
                <td><?= formatDateTime($prod['date_rdv']) ?></td>
                <td><span class="badge bg-secondary"><?= e($prod['categorie']) ?></span></td>
                <td><strong><?= e($prod['produit_vendu']) ?></strong></td>
                <td><?= e($prod['montant_nombre']) ?></td>
                <td>
                    <?= e(excerpt($prod['details'])) ?>
                    <?php if ($prod['details']): ?>
                        <button class="btn btn-sm btn-ce-outline ms-1" onclick="showDetail(<?= $prod['id'] ?>)"><i class="fas fa-eye"></i></button>
                    <?php endif; ?>
                </td>
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
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produit vendu</label>
                            <input type="text" name="produit_vendu" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Montant / Nombre</label>
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

function showDetail(id) {
    const prod = productionsData.find(p => p.id == id);
    if (!prod) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${n.created_at}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date RDV :</strong> ${formatDT(prod.date_rdv)}</p>
                <p><strong>Catégorie :</strong> <span class="badge bg-secondary">${prod.categorie || '-'}</span></p>
                <p><strong>Produit vendu :</strong> ${prod.produit_vendu || '-'}</p>
                <p><strong>Montant / Nombre :</strong> ${prod.montant_nombre || '-'}</p>
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
    const cats = <?= json_encode($categories) ?>;
    let catsOptions = cats.map(c =>
        `<option value="${c}" ${prod.categorie === c ? 'selected' : ''}>${c}</option>`
    ).join('');

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
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        ${catsOptions}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Produit vendu</label>
                    <input type="text" name="produit_vendu" class="form-control" value="${prod.produit_vendu || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Montant / Nombre</label>
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
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
