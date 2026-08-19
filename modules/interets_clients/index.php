<?php
$pageTitle = 'Intérêts clients';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

ensureInteretsClientsSchema();
ensureMotsClesInteretsSchema();
$categoriesProduction = getCategoriesProduction();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $categorie = in_array($_POST['categorie'] ?? '', $categoriesProduction, true) ? $_POST['categorie'] : '';
    $stmt = $db->prepare("INSERT INTO interets_clients (user_id, client_nom, categorie, interet, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, trim($_POST['client_nom']), $categorie, trim($_POST['interet']), $_POST['details']]);
    registerMotsClesInterets($_POST['interet']);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $categorie = in_array($_POST['categorie'] ?? '', $categoriesProduction, true) ? $_POST['categorie'] : '';
    $stmt = $db->prepare("UPDATE interets_clients SET client_nom = ?, categorie = ?, interet = ?, details = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([trim($_POST['client_nom']), $categorie, trim($_POST['interet']), $_POST['details'], (int)$_POST['id'], $userId]);
    registerMotsClesInterets($_POST['interet']);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM interets_clients WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    addNote('interets_clients', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM interets_clients WHERE user_id = ?");
$stmt->execute([$userId]);
$nbTotal = $stmt->fetchColumn();

// Liste des intérêts de l'utilisateur
$stmt = $db->prepare("SELECT * FROM interets_clients WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$interets = $stmt->fetchAll();

// Liste dynamique des mots-clés déjà utilisés (tous utilisateurs confondus)
$motsCles = getMotsClesInterets();

// Offres actuellement visibles par l'utilisateur, pour indiquer les correspondances existantes
$stmt = $db->prepare("SELECT id, nom, details, date_debut, date_fin FROM offres WHERE user_id = ? OR approved = 1");
$stmt->execute([$userId]);
$offresPourMatch = $stmt->fetchAll();

foreach ($interets as &$interet) {
    $offresMatch = [];
    foreach ($offresPourMatch as $offre) {
        if (!empty(matchInteretsForOffre([$interet], $offre['nom'], $offre['details']))) {
            $offresMatch[] = $offre['nom'];
        }
    }
    $interet['offres_correspondantes'] = $offresMatch;
}
unset($interet);
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="stat-card">
            <div class="stat-number"><?= $nbTotal ?></div>
            <div class="stat-label">Intérêts clients suivis</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvel intérêt client</button>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Enregistrez ici les clients intéressés par une offre à venir. Dès qu'une offre reprend le mot-clé renseigné (dans son titre ou son contenu), le nom du client apparaît automatiquement dans la synthèse de cette offre.
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Tous les intérêts clients</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInterets" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableInterets">
        <thead>
            <tr>
                <th>Client</th>
                <th>Catégorie</th>
                <th>Mot-clé / Intérêt</th>
                <th>Offre(s) correspondante(s)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($interets as $interet): ?>
            <tr>
                <td><strong><?= e($interet['client_nom']) ?></strong></td>
                <td><?= !empty($interet['categorie']) ? '<span class="badge bg-secondary">' . e($interet['categorie']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                <td><?= e($interet['interet']) ?></td>
                <td>
                    <?php if (!empty($interet['offres_correspondantes'])): ?>
                        <?php foreach ($interet['offres_correspondantes'] as $nomOffre): ?>
                            <span class="badge bg-success mb-1"><i class="fas fa-check"></i> <?= e($nomOffre) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">Aucune offre pour le moment</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $interet['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editInteret(<?= $interet['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet intérêt client ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $interet['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvel intérêt client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom du client <span class="text-danger">*</span></label>
                            <input type="text" name="client_nom" class="form-control" required>
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
                        <div class="col-12">
                            <label class="form-label">Mot-clé / Intérêt <span class="text-danger">*</span></label>
                            <input type="text" name="interet" id="addInteret" class="form-control" placeholder="Ex : prêt immobilier, assurance vie..." required>
                            <div class="form-text">Séparez plusieurs mots-clés par une virgule (ex : « prêt immobilier, assurance vie »). Il suffit qu'un seul soit repris dans le titre ou le contenu d'une offre pour signaler ce client automatiquement.</div>
                            <div class="mt-2">
                                <label class="form-label small text-muted mb-1">Mots-clés déjà utilisés (cliquez pour ajouter)</label>
                                <input type="text" id="addMotCleSearch" class="form-control form-control-sm mb-2" placeholder="Rechercher un mot-clé existant..." oninput="renderMotCleList('addMotCleList', this.value, 'addInteret')">
                                <div id="addMotCleList" class="d-flex flex-wrap gap-1"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Détails</label>
                            <textarea name="details" class="form-control" rows="4"></textarea>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de l'intérêt client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'intérêt client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchInterets', 'tableInterets');

const interetsData = <?= json_encode($interets) ?>;
const categoriesProduction = <?= json_encode($categoriesProduction) ?>;
const allNotes = {};
<?php
foreach ($interets as $interet) {
    $notes = getNotes('interets_clients', $interet['id']);
    echo "allNotes[{$interet['id']}] = " . json_encode($notes) . ";\n";
}
?>

function escapeHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function buildCategoriesOptions(selected) {
    let opts = '<option value="">-- Choisir --</option>';
    categoriesProduction.forEach(c => {
        opts += `<option value="${c}" ${c === selected ? 'selected' : ''}>${c}</option>`;
    });
    return opts;
}

// --- Liste dynamique de mots-clés : recherche + ajout au champ par clic ---
const motsClesData = <?= json_encode($motsCles) ?>;

function renderMotCleList(listId, query, inputId) {
    const list = document.getElementById(listId);
    if (!list) return;
    list.dataset.target = inputId;
    const q = query.trim().toLowerCase();
    const filtered = motsClesData.filter(m => q === '' || m.toLowerCase().includes(q));
    if (filtered.length === 0) {
        list.innerHTML = '<span class="text-muted small">' +
            (q ? 'Aucun mot-clé pour « ' + escapeHtml(query) + ' ».' : 'Aucun mot-clé enregistré pour le moment.') +
            '</span>';
        return;
    }
    list.innerHTML = filtered.slice(0, 25).map(m =>
        `<button type="button" class="badge bg-secondary border-0 mot-cle-btn" style="cursor:pointer;" data-mot="${escapeHtml(m)}">${escapeHtml(m)}</button>`
    ).join('');
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.mot-cle-btn');
    if (!btn) return;
    const list = btn.closest('[data-target]');
    if (!list) return;
    const input = document.getElementById(list.dataset.target);
    if (!input) return;
    const mot = btn.dataset.mot;
    const existing = input.value.split(',').map(s => s.trim()).filter(Boolean);
    if (!existing.some(v => v.toLowerCase() === mot.toLowerCase())) existing.push(mot);
    input.value = existing.join(', ');
    input.focus();
});

renderMotCleList('addMotCleList', '', 'addInteret');

function showDetail(id) {
    const interet = interetsData.find(i => i.id == id);
    if (!interet) return;
    const notes = allNotes[id] || [];
    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> - ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    const offresHtml = (interet.offres_correspondantes && interet.offres_correspondantes.length)
        ? interet.offres_correspondantes.map(n => `<span class="badge bg-success mb-1"><i class="fas fa-check"></i> ${escapeHtml(n)}</span>`).join(' ')
        : '<span class="text-muted">Aucune offre pour le moment</span>';

    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Client :</strong> ${escapeHtml(interet.client_nom)}</p>
                <p><strong>Catégorie :</strong> ${interet.categorie || '-'}</p>
                <p><strong>Mot-clé / Intérêt :</strong> ${escapeHtml(interet.interet)}</p>
                <p><strong>Offre(s) correspondante(s) :</strong><br>${offresHtml}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Détails :</strong></p>
                <div class="p-3 bg-light rounded">${interet.details || '-'}</div>
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

function editInteret(id) {
    const interet = interetsData.find(i => i.id == id);
    if (!interet) return;

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom du client <span class="text-danger">*</span></label>
                    <input type="text" name="client_nom" class="form-control" value="${escapeHtml(interet.client_nom)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select">${buildCategoriesOptions(interet.categorie)}</select>
                </div>
                <div class="col-12">
                    <label class="form-label">Mot-clé / Intérêt <span class="text-danger">*</span></label>
                    <input type="text" name="interet" id="editInteret" class="form-control" value="${escapeHtml(interet.interet)}" required>
                    <div class="mt-2">
                        <label class="form-label small text-muted mb-1">Mots-clés déjà utilisés (cliquez pour ajouter)</label>
                        <input type="text" id="editMotCleSearch" class="form-control form-control-sm mb-2" placeholder="Rechercher un mot-clé existant..." oninput="renderMotCleList('editMotCleList', this.value, 'editInteret')">
                        <div id="editMotCleList" class="d-flex flex-wrap gap-1"></div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Détails</label>
                    <textarea name="details" class="form-control" rows="4">${interet.details || ''}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    renderMotCleList('editMotCleList', '', 'editInteret');
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-ouverture après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
