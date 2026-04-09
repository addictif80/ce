<?php
$pageTitle = 'Suivi demandes clients';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Toggle traitee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_traitee'])) {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE demandes_clients SET traitee = IF(traitee=1,0,1) WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php?open=' . $id);
    exit;
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO demandes_clients (user_id, numero_personne, details_demande, date_envoi, service) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['details_demande'], $_POST['date_envoi'] ?: null, $_POST['service']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE demandes_clients SET numero_personne = ?, details_demande = ?, date_envoi = ?, service = ?, traitee = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['numero_personne'], $_POST['details_demande'], $_POST['date_envoi'] ?: null, $_POST['service'], (int)$_POST['traitee'], (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM demandes_clients WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('demandes_clients', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM demandes_clients WHERE user_id = ? AND traitee = 0");
$stmt->execute([$userId]);
$nbNonTraitees = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM demandes_clients WHERE user_id = ? AND traitee = 1");
$stmt->execute([$userId]);
$nbTerminees = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM demandes_clients WHERE user_id = ? ORDER BY traitee ASC, date_ajout DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbNonTraitees ?></div>
            <div class="stat-label">Demandes non traitées</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $nbTerminees ?></div>
            <div class="stat-label">Demandes terminées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center gap-2">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle demande</button>
        <a href="../instances/rapport.php" class="btn btn-ce-outline"><i class="fas fa-print"></i> Rapport</a>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les demandes</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchDemandes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableDemandes">
        <thead>
            <tr>
                <th>Date ajout</th>
                <th>N° Personne</th>
                <th>Détails</th>
                <th>Date envoi</th>
                <th>Service</th>
                <th>Traitée</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $dem): ?>
            <tr>
                <td><?= formatDate($dem['date_ajout']) ?></td>
                <td><strong><?= e($dem['numero_personne']) ?></strong></td>
                <td><?= e(excerpt($dem['details_demande'])) ?>
                </td>
                <td><?= formatDate($dem['date_envoi']) ?></td>
                <td><?= e($dem['service']) ?></td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="toggle_traitee" value="1">
                        <input type="hidden" name="id" value="<?= $dem['id'] ?>">
                        <?php if ($dem['traitee']): ?>
                            <button type="submit" class="badge-fait border-0" style="cursor:pointer"><i class="fas fa-check"></i> Traitée</button>
                        <?php else: ?>
                            <button type="submit" class="badge-afaire border-0" style="cursor:pointer"><i class="fas fa-clock"></i> En cours</button>
                        <?php endif; ?>
                    </form>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $dem['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDemande(<?= $dem['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette demande ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $dem['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle demande client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° Personne</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'envoi</label>
                            <input type="date" name="date_envoi" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <input type="text" name="service" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Détails de la demande</label>
                            <textarea name="details_demande" class="form-control" rows="5" required></textarea>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la demande</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchDemandes', 'tableDemandes');

const demandesData = <?= json_encode($demandes) ?>;
const allNotes = {};
<?php
foreach ($demandes as $dem) {
    $notes = getNotes('demandes_clients', $dem['id']);
    echo "allNotes[{$dem['id']}] = " . json_encode($notes) . ";\n";
}
?>

function showDetail(id) {
    const dem = demandesData.find(d => d.id == id);
    if (!dem) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date d'ajout :</strong> ${dem.date_ajout}</p>
                <p><strong>N° Personne :</strong> ${dem.numero_personne}</p>
                <p><strong>Date d'envoi :</strong> ${dem.date_envoi || 'Non définie'}</p>
                <p><strong>Service :</strong> ${dem.service || '-'}</p>
                <p><strong>Statut :</strong>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="toggle_traitee" value="1">
                        <input type="hidden" name="id" value="${id}">
                        ${dem.traitee == 1
                            ? '<button type="submit" class="badge-fait border-0" style="cursor:pointer"><i class="fas fa-check"></i> Traitée</button>'
                            : '<button type="submit" class="badge-afaire border-0" style="cursor:pointer"><i class="fas fa-clock"></i> En cours</button>'}
                    </form>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Détails de la demande :</strong></p>
                <div class="p-3 bg-light rounded">${dem.details_demande || '-'}</div>
            </div>
        </div>
        <div class="notes-section">
            <h5><i class="fas fa-sticky-note"></i> Notes de suivi</h5>
            ${notesHtml || '<p class="text-muted">Aucune note</p>'}
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="record_id" value="${id}">
                <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Ajouter une note de suivi..." required>
                    <button class="btn btn-ce" type="submit"><i class="fas fa-plus"></i></button>
                </div>
            </form>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editDemande(id) {
    const dem = demandesData.find(d => d.id == id);
    if (!dem) return;

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">N° Personne</label>
                    <input type="text" name="numero_personne" class="form-control" value="${dem.numero_personne}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date d'envoi</label>
                    <input type="date" name="date_envoi" class="form-control" value="${dem.date_envoi || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Service</label>
                    <input type="text" name="service" class="form-control" value="${dem.service || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="traitee" class="form-select">
                        <option value="0" ${dem.traitee == 0 ? 'selected' : ''}>En cours</option>
                        <option value="1" ${dem.traitee == 1 ? 'selected' : ''}>Traitée</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Détails de la demande</label>
                    <textarea name="details_demande" class="form-control" rows="5">${dem.details_demande || ''}</textarea>
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
