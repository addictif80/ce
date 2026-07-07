<?php
$pageTitle = 'Formations';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO formations (user_id, titre, date_debut, date_fin, lieu, adresse_hotel, reservation_faite, peage_ar, repas, indemnites_km, montant_total, envoyee_expansya, remboursee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $lieu = $_POST['lieu'];
    $adresse_hotel = $lieu === 'presentiel' ? ($_POST['adresse_hotel'] ?? '') : null;
    $reservation = $lieu === 'presentiel' ? (isset($_POST['reservation_faite']) ? 1 : 0) : 0;
    $peage = $lieu === 'presentiel' ? (float)($_POST['peage_ar'] ?? 0) : 0;
    $repas = $lieu === 'presentiel' ? (float)($_POST['repas'] ?? 0) : 0;
    $km = $lieu === 'presentiel' ? (float)($_POST['indemnites_km'] ?? 0) : 0;
    $total = $peage + $repas + $km;
    $expansya = $lieu === 'presentiel' ? (isset($_POST['envoyee_expansya']) ? 1 : 0) : 0;
    $remboursee = $lieu === 'presentiel' ? (isset($_POST['remboursee']) ? 1 : 0) : 0;
    $stmt->execute([$userId, $_POST['titre'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $lieu, $adresse_hotel, $reservation, $peage, $repas, $km, $total, $expansya, $remboursee]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $lieu = $_POST['lieu'];
    $adresse_hotel = $lieu === 'presentiel' ? ($_POST['adresse_hotel'] ?? '') : null;
    $reservation = $lieu === 'presentiel' ? (isset($_POST['reservation_faite']) ? 1 : 0) : 0;
    $peage = $lieu === 'presentiel' ? (float)($_POST['peage_ar'] ?? 0) : 0;
    $repas = $lieu === 'presentiel' ? (float)($_POST['repas'] ?? 0) : 0;
    $km = $lieu === 'presentiel' ? (float)($_POST['indemnites_km'] ?? 0) : 0;
    $total = $peage + $repas + $km;
    $expansya = $lieu === 'presentiel' ? (isset($_POST['envoyee_expansya']) ? 1 : 0) : 0;
    $remboursee = $lieu === 'presentiel' ? (isset($_POST['remboursee']) ? 1 : 0) : 0;
    $stmt = $db->prepare("UPDATE formations SET titre = ?, date_debut = ?, date_fin = ?, lieu = ?, adresse_hotel = ?, reservation_faite = ?, peage_ar = ?, repas = ?, indemnites_km = ?, montant_total = ?, envoyee_expansya = ?, remboursee = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['titre'], $_POST['date_debut'] ?: null, $_POST['date_fin'] ?: null, $lieu, $adresse_hotel, $reservation, $peage, $repas, $km, $total, $expansya, $remboursee, (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM formations WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('formations', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM formations WHERE user_id = ? AND date_fin >= CURDATE()");
$stmt->execute([$userId]);
$nbAVenir = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM formations WHERE user_id = ? AND lieu = 'presentiel' AND envoyee_expansya = 0");
$stmt->execute([$userId]);
$nbNonExpansya = $stmt->fetchColumn();

// Liste
$stmt = $db->prepare("SELECT * FROM formations WHERE user_id = ? ORDER BY date_debut DESC");
$stmt->execute([$userId]);
$formations = $stmt->fetchAll();
?>

<!-- Récap -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $nbAVenir ?></div>
            <div class="stat-label">Formations à venir</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $nbNonExpansya ?></div>
            <div class="stat-label">Notes de frais non envoyées</div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle formation</button>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <a href="calendrier.php" class="btn btn-ce-outline"><i class="fas fa-calendar-alt"></i> Calendrier</a>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les formations</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchFormations" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableFormations">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Lieu</th>
                <th>Hôtel réservé</th>
                <th>Note de frais</th>
                <th>Remboursée</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($formations as $f): ?>
            <tr>
                <td><strong><?= e($f['titre']) ?></strong></td>
                <td><?= formatDate($f['date_debut']) ?></td>
                <td><?= formatDate($f['date_fin']) ?></td>
                <td>
                    <?php if ($f['lieu'] === 'presentiel'): ?>
                        <span class="badge bg-primary">Présentiel</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Distanciel</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($f['lieu'] === 'presentiel'): ?>
                        <?= $f['reservation_faite'] ? '<span class="badge-fait"><i class="fas fa-check"></i> Oui</span>' : '<span class="badge-afaire"><i class="fas fa-clock"></i> Non</span>' ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($f['lieu'] === 'presentiel'): ?>
                        <?= number_format($f['montant_total'], 2, ',', ' ') ?> &euro;
                        <?php if ($f['envoyee_expansya']): ?>
                            <span class="badge bg-success ms-1">Expansya</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark ms-1">Non envoyée</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($f['lieu'] === 'presentiel'): ?>
                        <?= $f['remboursee'] ? '<span class="badge-fait"><i class="fas fa-check"></i> Oui</span>' : '<span class="badge-afaire"><i class="fas fa-clock"></i> Non</span>' ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $f['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editFormation(<?= $f['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle formation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Titre</label>
                            <input type="text" name="titre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de début</label>
                            <input type="date" name="date_debut" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de fin</label>
                            <input type="date" name="date_fin" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lieu</label>
                            <select name="lieu" class="form-select" id="addLieu" onchange="togglePresentiel('add')">
                                <option value="distanciel">Distanciel</option>
                                <option value="presentiel">Présentiel</option>
                            </select>
                        </div>

                        <!-- Bloc présentiel -->
                        <div class="col-12 presentiel-block" id="addPresentielBlock" style="display:none;">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark"><i class="fas fa-hotel"></i> Informations présentiel</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Adresse de l'hôtel</label>
                                            <input type="text" name="adresse_hotel" class="form-control">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="reservation_faite" id="addReservation">
                                                <label class="form-check-label" for="addReservation">Réservation faite</label>
                                            </div>
                                        </div>

                                        <div class="col-12"><hr><h6><i class="fas fa-receipt"></i> Note de frais</h6></div>
                                        <div class="col-md-4">
                                            <label class="form-label">Péage aller/retour (&euro;)</label>
                                            <input type="number" step="0.01" name="peage_ar" class="form-control frais-add" value="0" oninput="calcTotal('add')">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Repas (&euro;)</label>
                                            <input type="number" step="0.01" name="repas" class="form-control frais-add" value="0" oninput="calcTotal('add')">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Indemnités kilométriques (&euro;)</label>
                                            <input type="number" step="0.01" name="indemnites_km" class="form-control frais-add" value="0" oninput="calcTotal('add')">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Montant total</label>
                                            <div class="form-control bg-light" id="addTotal">0,00 &euro;</div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="envoyee_expansya" id="addExpansya">
                                                <label class="form-check-label" for="addExpansya">Envoyée sur Expansya</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remboursee" id="addRemboursee">
                                                <label class="form-check-label" for="addRemboursee">Remboursée</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la formation</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la formation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchFormations', 'tableFormations');

const formationsData = <?= json_encode($formations) ?>;
const allNotes = {};
<?php
foreach ($formations as $f) {
    $notes = getNotes('formations', $f['id']);
    echo "allNotes[{$f['id']}] = " . json_encode($notes) . ";\n";
}
?>

function togglePresentiel(prefix) {
    const lieu = document.getElementById(prefix + 'Lieu').value;
    const block = document.getElementById(prefix + 'PresentielBlock');
    block.style.display = lieu === 'presentiel' ? '' : 'none';
}

function calcTotal(prefix) {
    const inputs = document.querySelectorAll('.frais-' + prefix);
    let total = 0;
    inputs.forEach(inp => total += parseFloat(inp.value) || 0);
    document.getElementById(prefix + 'Total').textContent = total.toFixed(2).replace('.', ',') + ' \u20AC';
}

function showDetail(id) {
    const f = formationsData.find(i => i.id == id);
    if (!f) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    let presentielHtml = '';
    if (f.lieu === 'presentiel') {
        presentielHtml = `
            <div class="col-md-6">
                <div class="card border-warning mt-3">
                    <div class="card-header bg-warning text-dark"><i class="fas fa-hotel"></i> Présentiel</div>
                    <div class="card-body">
                        <p><strong>Adresse hôtel :</strong> ${f.adresse_hotel || '-'}</p>
                        <p><strong>Réservation faite :</strong> ${f.reservation_faite == 1 ? '<span class="badge-fait">Oui</span>' : '<span class="badge-afaire">Non</span>'}</p>
                        <hr>
                        <h6><i class="fas fa-receipt"></i> Note de frais</h6>
                        <p><strong>Péage A/R :</strong> ${parseFloat(f.peage_ar).toFixed(2).replace('.', ',')} \u20AC</p>
                        <p><strong>Repas :</strong> ${parseFloat(f.repas).toFixed(2).replace('.', ',')} \u20AC</p>
                        <p><strong>Indemnités km :</strong> ${parseFloat(f.indemnites_km).toFixed(2).replace('.', ',')} \u20AC</p>
                        <p><strong>Total :</strong> <span class="fw-bold text-danger">${parseFloat(f.montant_total).toFixed(2).replace('.', ',')} \u20AC</span></p>
                        <p><strong>Expansya :</strong> ${f.envoyee_expansya == 1 ? '<span class="badge bg-success">Envoyée</span>' : '<span class="badge bg-warning text-dark">Non envoyée</span>'}</p>
                        <p><strong>Remboursée :</strong> ${f.remboursee == 1 ? '<span class="badge-fait">Oui</span>' : '<span class="badge-afaire">Non</span>'}</p>
                    </div>
                </div>
            </div>`;
    }

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Titre :</strong> ${f.titre}</p>
                <p><strong>Date début :</strong> ${f.date_debut || '-'}</p>
                <p><strong>Date fin :</strong> ${f.date_fin || '-'}</p>
                <p><strong>Lieu :</strong> ${f.lieu === 'presentiel' ? '<span class="badge bg-primary">Présentiel</span>' : '<span class="badge bg-secondary">Distanciel</span>'}</p>
            </div>
            ${presentielHtml}
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

function editFormation(id) {
    const f = formationsData.find(i => i.id == id);
    if (!f) return;
    const isPresentiel = f.lieu === 'presentiel';

    document.getElementById('editContent').innerHTML = `
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Titre</label>
                    <input type="text" name="titre" class="form-control" value="${f.titre}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de début</label>
                    <input type="date" name="date_debut" class="form-control" value="${f.date_debut || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de fin</label>
                    <input type="date" name="date_fin" class="form-control" value="${f.date_fin || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lieu</label>
                    <select name="lieu" class="form-select" id="editLieu" onchange="togglePresentiel('edit')">
                        <option value="distanciel" ${f.lieu==='distanciel'?'selected':''}>Distanciel</option>
                        <option value="presentiel" ${f.lieu==='presentiel'?'selected':''}>Présentiel</option>
                    </select>
                </div>
                <div class="col-12 presentiel-block" id="editPresentielBlock" style="${isPresentiel ? '' : 'display:none;'}">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark"><i class="fas fa-hotel"></i> Informations présentiel</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Adresse de l'hôtel</label>
                                    <input type="text" name="adresse_hotel" class="form-control" value="${f.adresse_hotel || ''}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="reservation_faite" id="editReservation" ${f.reservation_faite == 1 ? 'checked' : ''}>
                                        <label class="form-check-label" for="editReservation">Réservation faite</label>
                                    </div>
                                </div>
                                <div class="col-12"><hr><h6><i class="fas fa-receipt"></i> Note de frais</h6></div>
                                <div class="col-md-4">
                                    <label class="form-label">Péage aller/retour (\u20AC)</label>
                                    <input type="number" step="0.01" name="peage_ar" class="form-control frais-edit" value="${parseFloat(f.peage_ar || 0).toFixed(2)}" oninput="calcTotal('edit')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Repas (\u20AC)</label>
                                    <input type="number" step="0.01" name="repas" class="form-control frais-edit" value="${parseFloat(f.repas || 0).toFixed(2)}" oninput="calcTotal('edit')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Indemnités km (\u20AC)</label>
                                    <input type="number" step="0.01" name="indemnites_km" class="form-control frais-edit" value="${parseFloat(f.indemnites_km || 0).toFixed(2)}" oninput="calcTotal('edit')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant total</label>
                                    <div class="form-control bg-light" id="editTotal">${parseFloat(f.montant_total || 0).toFixed(2).replace('.', ',')} \u20AC</div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="envoyee_expansya" id="editExpansya" ${f.envoyee_expansya == 1 ? 'checked' : ''}>
                                        <label class="form-check-label" for="editExpansya">Envoyée sur Expansya</label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remboursee" id="editRemboursee" ${f.remboursee == 1 ? 'checked' : ''}>
                                        <label class="form-check-label" for="editRemboursee">Remboursée</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
