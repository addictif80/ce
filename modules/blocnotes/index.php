<?php
$pageTitle = 'Bloc-notes';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO blocnotes (user_id, nom_note, contenu, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$userId, $_POST['nom_note'], $_POST['contenu']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE blocnotes SET nom_note = ?, contenu = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['nom_note'], $_POST['contenu'], (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM blocnotes WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Liste
$stmt = $db->prepare("SELECT * FROM blocnotes WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$userId]);
$notes = $stmt->fetchAll();
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($notes) ?></div>
            <div class="stat-label">Notes enregistrées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle note</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les notes</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchNotes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableNotes">
        <thead>
            <tr>
                <th>Nom de la note</th>
                <th>Contenu</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($notes as $note): ?>
            <tr>
                <td><strong><?= e($note['nom_note']) ?></strong></td>
                <td><?= e(excerpt($note['contenu'], 100)) ?></td>
                <td><?= formatDate($note['updated_at']) ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $note['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editNote(<?= $note['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette note ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $note['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la note</label>
                            <input type="text" name="nom_note" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contenu</label>
                            <textarea name="contenu" class="form-control" rows="15" required></textarea>
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

<!-- Modal Detail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la note</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchNotes', 'tableNotes');

const notesData = <?= json_encode($notes) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function nl2br(str) {
    if (!str) return '';
    return escapeHtml(str).replace(/\n/g, '<br>');
}

function showDetail(id) {
    const note = notesData.find(n => n.id == id);
    if (!note) return;
    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-12 mb-3">
                <h4>${escapeHtml(note.nom_note)}</h4>
                <small class="text-muted">Créée le ${note.created_at} — Modifiée le ${note.updated_at}</small>
            </div>
            <div class="col-12">
                <div class="p-3 bg-light rounded" style="white-space:pre-wrap;">${escapeHtml(note.contenu)}</div>
            </div>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editNote(id) {
    const note = notesData.find(n => n.id == id);
    if (!note) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de la note</label>
                    <input type="text" name="nom_note" class="form-control" value="${escapeHtml(note.nom_note)}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Contenu</label>
                    <textarea name="contenu" class="form-control" rows="15" required>${escapeHtml(note.contenu)}</textarea>
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
