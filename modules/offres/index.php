<?php
$pageTitle = 'Offres en cours';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Auto-add approval columns
try { $db->exec("ALTER TABLE offres ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE offres ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO offres (user_id, nom, date_debut, date_fin, details, approved, approved_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $_POST['nom'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $_POST['details'],
        $isUserAdmin ? 1 : 0,
        $isUserAdmin ? $userId : null
    ]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("UPDATE offres SET nom = ?, date_debut = ?, date_fin = ?, details = ? WHERE id = ?");
        $stmt->execute([$_POST['nom'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $_POST['details'], $id]);
    } else {
        $stmt = $db->prepare("UPDATE offres SET nom = ?, date_debut = ?, date_fin = ?, details = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['nom'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $_POST['details'], $id, $userId]);
    }
    header('Location: index.php?open=' . $id);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM offres WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM offres WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('offres', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats (include approved from others)
$stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE (user_id = ? OR approved = 1) AND (date_debut IS NULL OR date_debut <= CURDATE()) AND (date_fin IS NULL OR date_fin >= CURDATE())");
$stmt->execute([$userId]);
$nbEnCours = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE (user_id = ? OR approved = 1) AND date_fin < CURDATE()");
$stmt->execute([$userId]);
$nbTerminees = $stmt->fetchColumn();

// Liste : ses propres offres + toutes les offres approuvées
$stmt = $db->prepare("SELECT o.*, u.nom AS author_nom, u.prenom AS author_prenom
    FROM offres o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.user_id = ? OR o.approved = 1
    ORDER BY o.date_debut DESC");
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
                <th>Auteur</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($offres as $offre):
            $isOwn = ($offre['user_id'] == $userId);
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
                <td>
                    <?php if ($isOwn): ?>
                        <span class="badge bg-primary">Moi</span>
                    <?php else: ?>
                        <?= e($offre['author_prenom'] . ' ' . $offre['author_nom']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($offre['approved']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Approuvé</span>
                    <?php elseif ($isOwn): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $offre['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <?php if ($isOwn || $isUserAdmin): ?>
                    <button class="btn btn-sm btn-ce-outline" onclick="editOffre(<?= $offre['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette offre ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $offre['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
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
                        <?php if (!$isUserAdmin): ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Cette offre sera soumise à validation par un administrateur avant d'être visible par tous.</div>
                        </div>
                        <?php endif; ?>
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
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Nom :</strong> ${offre.nom}</p>
                <p><strong>Date de début :</strong> ${offre.date_debut || 'Non définie'}</p>
                <p><strong>Date de fin :</strong> ${offre.date_fin || 'Non définie'}</p>
                <p><strong>Statut :</strong> ${offre.approved == 1 ? '<span class="badge bg-success">Approuvé</span>' : '<span class="badge bg-warning text-dark">En attente</span>'}</p>
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

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
