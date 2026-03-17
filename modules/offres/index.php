<?php
$pageTitle = 'Offres en cours';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO offres (user_id, nom, date_debut, date_fin, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['nom'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $_POST['details']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE offres SET nom = ?, date_debut = ?, date_fin = ?, details = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['nom'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $_POST['details'], (int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM offres WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('offres', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php');
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE user_id = ? AND (date_debut IS NULL OR date_debut <= CURDATE()) AND (date_fin IS NULL OR date_fin >= CURDATE())");
$stmt->execute([$userId]);
$nbEnCours = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE user_id = ? AND date_fin < CURDATE()");
$stmt->execute([$userId]);
$nbTerminees = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM offres WHERE user_id = ? ORDER BY date_debut DESC");
$stmt->execute([$userId]);
$offres = $stmt->fetchAll();
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbEnCours ?></div>
            <div class="stat-label">Offres en cours</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $nbTerminees ?></div>
            <div class="stat-label">Offres terminées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle offre</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les offres</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchOffres" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableOffres">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Détails</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($offres as $offre): ?>
            <?php
                $classDebut = '';
                $classFin = '';
                $now = date('Y-m-d');
                if ($offre['date_debut'] && $offre['date_debut'] > $now) $classDebut = 'bg-warning';
                if ($offre['date_fin'] && $offre['date_fin'] < $now) $classFin = 'bg-danger text-white';
            ?>
            <tr>
                <td><strong><?= e($offre['nom']) ?></strong></td>
                <td><span class="<?= $classDebut ?> px-2 py-1 rounded"><?= formatDate($offre['date_debut']) ?></span></td>
                <td><span class="<?= $classFin ?> px-2 py-1 rounded"><?= formatDate($offre['date_fin']) ?></span></td>
                <td><?= e(excerpt($offre['details'])) ?>
                    <button class="btn btn-sm btn-ce-outline ms-1" onclick="showDetail(<?= $offre['id'] ?>)"><i class="fas fa-eye"></i></button>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $offre['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editOffre(<?= $offre['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette offre ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $offre['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle offre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nom de l'offre</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de début</label>
                            <input type="date" name="date_debut" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de fin</label>
                            <input type="date" name="date_fin" class="form-control">
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de l'offre</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'offre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchOffres', 'tableOffres');

const offresData = <?= json_encode($offres) ?>;
const allNotes = {};
<?php
foreach ($offres as $offre) {
    $notes = getNotes('offres', $offre['id']);
    echo "allNotes[{$offre['id']}] = " . json_encode($notes) . ";\n";
}
?>

function showDetail(id) {
    const offre = offresData.find(o => o.id == id);
    if (!offre) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${n.created_at}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Nom :</strong> ${offre.nom}</p>
                <p><strong>Date de début :</strong> ${offre.date_debut || 'Non définie'}</p>
                <p><strong>Date de fin :</strong> ${offre.date_fin || 'Non définie'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Détails :</strong></p>
                <div class="p-3 bg-light rounded">${offre.details || '-'}</div>
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

function editOffre(id) {
    const offre = offresData.find(o => o.id == id);
    if (!offre) return;

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Nom de l'offre</label>
                    <input type="text" name="nom" class="form-control" value="${offre.nom}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de début</label>
                    <input type="date" name="date_debut" class="form-control" value="${offre.date_debut || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de fin</label>
                    <input type="date" name="date_fin" class="form-control" value="${offre.date_fin || ''}">
                </div>
                <div class="col-12">
                    <label class="form-label">Détails</label>
                    <textarea name="details" class="form-control" rows="5">${offre.details || ''}</textarea>
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
