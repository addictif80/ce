<?php
$pageTitle = 'Demandes de rappel';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Toggle traitee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_traitee') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE demandes_rappel SET traitee = IF(traitee=1,0,1) WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php');
    exit;
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO demandes_rappel (user_id, numero_personne, motif) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['motif']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE demandes_rappel SET numero_personne = ?, motif = ?, traitee = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['numero_personne'], $_POST['motif'], (int)$_POST['traitee'], (int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $db->prepare("DELETE FROM tentatives_appel WHERE demande_rappel_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM notes WHERE table_name = 'demandes_rappel' AND record_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM demandes_rappel WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
    header('Location: index.php');
    exit;
}

// Ajout tentative d'appel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tentative') {
    $stmt = $db->prepare("INSERT INTO tentatives_appel (demande_rappel_id, user_id, commentaire) VALUES (?, ?, ?)");
    $stmt->execute([(int)$_POST['demande_rappel_id'], $userId, $_POST['commentaire'] ?? '']);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('demandes_rappel', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php');
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM demandes_rappel WHERE user_id = ? AND traitee = 0");
$stmt->execute([$userId]);
$nbARappeler = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM demandes_rappel WHERE user_id = ? ORDER BY traitee ASC, date_ajout DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();

// Tentatives par demande
$tentativesParDemande = [];
foreach ($demandes as $d) {
    $stmt = $db->prepare("SELECT ta.*, u.prenom, u.nom FROM tentatives_appel ta LEFT JOIN users u ON ta.user_id = u.id WHERE ta.demande_rappel_id = ? ORDER BY ta.date_tentative DESC");
    $stmt->execute([$d['id']]);
    $tentativesParDemande[$d['id']] = $stmt->fetchAll();
}
?>

<!-- Recap -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbARappeler ?></div>
            <div class="stat-label">Nombre de clients a rappeler</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle demande de rappel</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les demandes de rappel</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchRappels" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableRappels">
        <thead>
            <tr>
                <th>Date ajout</th>
                <th>N&#176; Personne/Nom</th>
                <th>Motif</th>
                <th>Tentatives</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $d): ?>
            <tr>
                <td><?= formatDate($d['date_ajout']) ?></td>
                <td><strong><?= e($d['numero_personne']) ?></strong></td>
                <td>
                    <?= e(excerpt($d['motif'])) ?>
                    <?php if (!empty($d['motif'])): ?>
                        <button class="btn btn-sm btn-ce-outline ms-1" onclick="showDetail(<?= $d['id'] ?>)" title="Voir le motif complet"><i class="fas fa-eye"></i></button>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-secondary"><?= count($tentativesParDemande[$d['id']] ?? []) ?></span>
                </td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_traitee">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <?php if ($d['traitee']): ?>
                            <button type="submit" class="badge-fait border-0" style="cursor:pointer"><i class="fas fa-check"></i> Traitee</button>
                        <?php else: ?>
                            <button type="submit" class="badge-afaire border-0" style="cursor:pointer"><i class="fas fa-clock"></i> A rappeler</button>
                        <?php endif; ?>
                    </form>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editRecord(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette demande de rappel ?')">
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

<!-- Modal Ajout -->
<div class="modal fade modal-fullscreen-custom" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle demande de rappel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N&#176; Personne / Nom</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif</label>
                            <textarea name="motif" class="form-control" rows="5" placeholder="Decrivez le motif du rappel..."></textarea>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detail de la demande de rappel</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la demande de rappel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchRappels', 'tableRappels');

const demandesData = <?= json_encode($demandes) ?>;
const tentativesData = <?= json_encode($tentativesParDemande) ?>;
const allNotes = {};
<?php
foreach ($demandes as $d) {
    $notes = getNotes('demandes_rappel', $d['id']);
    echo "allNotes[{$d['id']}] = " . json_encode($notes) . ";\n";
}
?>

function showDetail(id) {
    const d = demandesData.find(i => i.id == id);
    if (!d) return;
    const notes = allNotes[id] || [];
    const tentatives = tentativesData[id] || [];

    let tentativesHtml = tentatives.length > 0
        ? tentatives.map(t =>
            `<div class="note-item">
                <div class="note-meta"><strong>${t.prenom || ''} ${t.nom || ''}</strong> - ${t.date_tentative}</div>
                <div class="note-content">${t.commentaire || '<em class="text-muted">Aucun commentaire</em>'}</div>
            </div>`
        ).join('')
        : '<p class="text-muted">Aucune tentative d\'appel enregistree</p>';

    let notesHtml = notes.length > 0
        ? notes.map(n =>
            `<div class="note-item">
                <div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${n.created_at}</div>
                <div class="note-content">${n.message}</div>
            </div>`
        ).join('')
        : '<p class="text-muted">Aucune note</p>';

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date d'ajout :</strong> ${d.date_ajout}</p>
                <p><strong>N\u00b0 Personne / Nom :</strong> ${d.numero_personne}</p>
                <p><strong>Statut :</strong> ${d.traitee == 1 ? '<span class="badge-fait">Traitee</span>' : '<span class="badge-afaire">A rappeler</span>'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Motif :</strong></p>
                <div class="p-3 bg-light rounded">${d.motif || '-'}</div>
            </div>
        </div>
        <hr>
        <div class="mb-4">
            <h5><i class="fas fa-phone-alt"></i> Tentatives d'appel</h5>
            ${tentativesHtml}
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_tentative">
                <input type="hidden" name="demande_rappel_id" value="${id}">
                <div class="row g-2 align-items-end">
                    <div class="col">
                        <label class="form-label">Commentaire (optionnel)</label>
                        <input type="text" name="commentaire" class="form-control" placeholder="Ex: pas de reponse, rappeler demain...">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-ce" type="submit"><i class="fas fa-phone"></i> Enregistrer une tentative</button>
                    </div>
                </div>
            </form>
        </div>
        <hr>
        <div class="notes-section">
            <h5><i class="fas fa-sticky-note"></i> Notes</h5>
            ${notesHtml}
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

function editRecord(id) {
    const d = demandesData.find(i => i.id == id);
    if (!d) return;

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">N\u00b0 Personne / Nom</label>
                    <input type="text" name="numero_personne" class="form-control" value="${d.numero_personne}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="traitee" class="form-select">
                        <option value="0" ${d.traitee == 0 ? 'selected' : ''}>A rappeler</option>
                        <option value="1" ${d.traitee == 1 ? 'selected' : ''}>Traitee</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Motif</label>
                    <textarea name="motif" class="form-control" rows="5">${d.motif || ''}</textarea>
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
