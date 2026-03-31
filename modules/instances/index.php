<?php
$pageTitle = 'Mes instances';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Toggle statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_statut'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE instances SET statut = IF(statut='fait','a_faire','fait') WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php?open=' . $id);
    exit;
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $cats = isset($_POST['categories']) ? implode(', ', $_POST['categories']) : '';
    $stmt = $db->prepare("INSERT INTO instances (user_id, numero_personne, date_echeance, categories, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['date_echeance'] ?: null, $cats, $_POST['details']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $cats = isset($_POST['categories']) ? implode(', ', $_POST['categories']) : '';
    $stmt = $db->prepare("UPDATE instances SET numero_personne = ?, date_echeance = ?, categories = ?, details = ?, statut = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['numero_personne'], $_POST['date_echeance'] ?: null, $cats, $_POST['details'], $_POST['statut'], (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM instances WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('instances', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM instances WHERE user_id = ? AND statut = 'a_faire'");
$stmt->execute([$userId]);
$nbNonTraitees = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM instances WHERE user_id = ? AND statut = 'a_faire' AND date_echeance < CURDATE()");
$stmt->execute([$userId]);
$nbRetard = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM instances WHERE user_id = ? ORDER BY CASE WHEN statut = 'a_faire' THEN 0 ELSE 1 END, date_echeance ASC");
$stmt->execute([$userId]);
$instances = $stmt->fetchAll();

$categories = ['Bancarisation', 'Epargne', 'IARD', 'Prévoyance', 'Placement', 'Crédit conso', 'Crédit immo'];
?>

<!-- Récap -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbNonTraitees ?></div>
            <div class="stat-label">Instances non traitées</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $nbRetard ?></div>
            <div class="stat-label">Instances en retard</div>
        </div>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle instance</button>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <a href="calendrier.php" class="btn btn-ce-outline"><i class="fas fa-calendar"></i> Calendrier</a>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les instances</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInstances" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableInstances">
        <thead>
            <tr>
                <th>Date ajout</th>
                <th>N° Personne/Nom</th>
                <th>Échéance</th>
                <th>Catégorie</th>
                <th>Détails</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($instances as $inst): ?>
            <tr>
                <td><?= formatDate($inst['date_ajout']) ?></td>
                <td><strong><?= e($inst['numero_personne']) ?></strong></td>
                <td><span class="<?= getEcheanceClass($inst['date_echeance']) ?> px-2 py-1 rounded"><?= formatDate($inst['date_echeance']) ?></span></td>
                <td><?= e($inst['categories']) ?></td>
                <td><?= e(excerpt($inst['details'])) ?>
                </td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="toggle_statut" value="1">
                        <input type="hidden" name="id" value="<?= $inst['id'] ?>">
                        <?php if ($inst['statut'] === 'fait'): ?>
                            <button type="submit" class="badge-fait border-0 cursor-pointer" style="cursor:pointer"><i class="fas fa-check"></i> Fait</button>
                        <?php else: ?>
                            <button type="submit" class="badge-afaire border-0" style="cursor:pointer"><i class="fas fa-clock"></i> À faire</button>
                        <?php endif; ?>
                    </form>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $inst['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editInstance(<?= $inst['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $inst['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle instance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° Personne / Nom</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'échéance</label>
                            <input type="date" name="date_echeance" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Catégorie</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat ?>" id="cat_<?= str_replace(' ', '_', $cat) ?>">
                                        <label class="form-check-label" for="cat_<?= str_replace(' ', '_', $cat) ?>"><?= $cat ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de l'instance</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'instance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchInstances', 'tableInstances');

const instancesData = <?= json_encode($instances) ?>;
const allNotes = {};
<?php
foreach ($instances as $inst) {
    $notes = getNotes('instances', $inst['id']);
    echo "allNotes[{$inst['id']}] = " . json_encode($notes) . ";\n";
}
?>

function showDetail(id) {
    const inst = instancesData.find(i => i.id == id);
    if (!inst) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${n.created_at}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date d'ajout :</strong> ${inst.date_ajout}</p>
                <p><strong>N° Personne / Nom :</strong> ${inst.numero_personne}</p>
                <p><strong>Échéance :</strong> ${inst.date_echeance || 'Non définie'}</p>
                <p><strong>Catégorie :</strong> ${inst.categories || '-'}</p>
                <p><strong>Statut :</strong>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="toggle_statut" value="1">
                        <input type="hidden" name="id" value="${id}">
                        ${inst.statut === 'fait'
                            ? '<button type="submit" class="badge-fait border-0" style="cursor:pointer"><i class="fas fa-check"></i> Fait</button>'
                            : '<button type="submit" class="badge-afaire border-0" style="cursor:pointer"><i class="fas fa-clock"></i> À faire</button>'}
                    </form>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Détails :</strong></p>
                <div class="p-3 bg-light rounded">${inst.details || '-'}</div>
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

function editInstance(id) {
    const inst = instancesData.find(i => i.id == id);
    if (!inst) return;
    const cats = (inst.categories || '').split(', ');
    const allCats = <?= json_encode($categories) ?>;
    let catsHtml = allCats.map(c => {
        const checked = cats.includes(c) ? 'checked' : '';
        return `<div class="form-check"><input class="form-check-input" type="checkbox" name="categories[]" value="${c}" ${checked} id="edit_cat_${c.replace(/ /g,'_')}"><label class="form-check-label" for="edit_cat_${c.replace(/ /g,'_')}">${c}</label></div>`;
    }).join('');

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">N° Personne / Nom</label>
                    <input type="text" name="numero_personne" class="form-control" value="${inst.numero_personne}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date d'échéance</label>
                    <input type="date" name="date_echeance" class="form-control" value="${inst.date_echeance || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="a_faire" ${inst.statut==='a_faire'?'selected':''}>À faire</option>
                        <option value="fait" ${inst.statut==='fait'?'selected':''}>Fait</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Catégorie</label>
                    <div class="d-flex flex-wrap gap-3">${catsHtml}</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Détails</label>
                    <textarea name="details" class="form-control" rows="5">${inst.details || ''}</textarea>
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
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
