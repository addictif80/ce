<?php
$pageTitle = 'Procédures';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $miseEnAvant = isset($_POST['mise_en_avant']) ? 1 : 0;
    $lienPartage = generateShareLink();
    $stmt = $db->prepare("INSERT INTO procedures (user_id, nom, texte, mise_en_avant, lien_partage, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $_POST['nom'], $_POST['texte'], $miseEnAvant, $lienPartage]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $miseEnAvant = isset($_POST['mise_en_avant']) ? 1 : 0;
    $stmt = $db->prepare("UPDATE procedures SET nom = ?, texte = ?, mise_en_avant = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['nom'], $_POST['texte'], $miseEnAvant, (int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM procedures WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Liste
$stmt = $db->prepare("SELECT * FROM procedures WHERE user_id = ? ORDER BY mise_en_avant DESC, created_at DESC");
$stmt->execute([$userId]);
$procedures = $stmt->fetchAll();

// URL de base pour les liens de partage
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/view.php';
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($procedures) ?></div>
            <div class="stat-label">Procédures enregistrées</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle procédure</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les procédures</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchProcedures" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableProcedures">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Mise en avant</th>
                <th>Date</th>
                <th>Lien de partage</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($procedures as $proc): ?>
            <tr>
                <td><strong><?= e($proc['nom']) ?></strong></td>
                <td>
                    <?php if ($proc['mise_en_avant']): ?>
                        <span class="badge-fait"><i class="fas fa-star"></i> Oui</span>
                    <?php else: ?>
                        <span class="badge-afaire">Non</span>
                    <?php endif; ?>
                </td>
                <td><?= formatDate($proc['created_at']) ?></td>
                <td>
                    <div class="input-group input-group-sm" style="max-width:320px;">
                        <input type="text" class="form-control form-control-sm" value="<?= e($baseUrl . '?token=' . $proc['lien_partage']) ?>" readonly id="link_<?= $proc['id'] ?>">
                        <button class="btn btn-ce-outline btn-sm" onclick="copyLink(<?= $proc['id'] ?>)" title="Copier"><i class="fas fa-copy"></i></button>
                    </div>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $proc['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editProcedure(<?= $proc['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette procédure ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $proc['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle procédure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la procédure</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mise_en_avant" id="addMiseEnAvant" value="1">
                                <label class="form-check-label" for="addMiseEnAvant">Mise en avant</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Texte de la procédure</label>
                            <textarea name="texte" class="form-control" rows="15" required></textarea>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la procédure</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la procédure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchProcedures', 'tableProcedures');

const proceduresData = <?= json_encode($procedures) ?>;
const baseShareUrl = <?= json_encode($baseUrl) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function copyLink(id) {
    const input = document.getElementById('link_' + id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 1500);
    });
}

function showDetail(id) {
    const proc = proceduresData.find(p => p.id == id);
    if (!proc) return;
    const shareUrl = baseShareUrl + '?token=' + proc.lien_partage;
    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-12 mb-3">
                <h4>${escapeHtml(proc.nom)}</h4>
                <small class="text-muted">Créée le ${proc.created_at}</small>
                ${proc.mise_en_avant == 1 ? ' <span class="badge-fait ms-2"><i class="fas fa-star"></i> Mise en avant</span>' : ''}
            </div>
            <div class="col-12 mb-3">
                <div class="p-3 bg-light rounded" style="white-space:pre-wrap;">${escapeHtml(proc.texte)}</div>
            </div>
            <div class="col-12">
                <label class="form-label"><strong>Lien de partage :</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" value="${escapeHtml(shareUrl)}" readonly id="detail_link_${id}">
                    <button class="btn btn-ce-outline" onclick="navigator.clipboard.writeText(document.getElementById('detail_link_${id}').value)"><i class="fas fa-copy"></i> Copier</button>
                    <a href="${escapeHtml(shareUrl)}" target="_blank" class="btn btn-ce-outline"><i class="fas fa-external-link-alt"></i> Ouvrir</a>
                </div>
            </div>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editProcedure(id) {
    const proc = proceduresData.find(p => p.id == id);
    if (!proc) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de la procédure</label>
                    <input type="text" name="nom" class="form-control" value="${escapeHtml(proc.nom)}" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mise_en_avant" id="editMiseEnAvant" value="1" ${proc.mise_en_avant == 1 ? 'checked' : ''}>
                        <label class="form-check-label" for="editMiseEnAvant">Mise en avant</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Texte de la procédure</label>
                    <textarea name="texte" class="form-control" rows="15" required>${escapeHtml(proc.texte)}</textarea>
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
