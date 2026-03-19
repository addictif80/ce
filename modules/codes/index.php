<?php
$pageTitle = 'Codes utiles';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Auto-add approval columns
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO codes_utiles (user_id, code, fonction, approved, approved_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $_POST['code'], $_POST['fonction'],
        $isUserAdmin ? 1 : 0,
        $isUserAdmin ? $userId : null
    ]);
    header('Location: index.php');
    exit;
}

// Edition (only own items, or admin can edit any)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("UPDATE codes_utiles SET code = ?, fonction = ? WHERE id = ?");
        $stmt->execute([$_POST['code'], $_POST['fonction'], $id]);
    } else {
        $stmt = $db->prepare("UPDATE codes_utiles SET code = ?, fonction = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['code'], $_POST['fonction'], $id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Suppression (only own items, or admin can delete any)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM codes_utiles WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM codes_utiles WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste : ses propres codes + tous les codes approuvés
$stmt = $db->prepare("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom
    FROM codes_utiles c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ? OR c.approved = 1
    ORDER BY c.code ASC");
$stmt->execute([$userId]);
$codes = $stmt->fetchAll();
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($codes) ?></div>
            <div class="stat-label">Codes disponibles</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau code</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Tous les codes</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCodes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableCodes">
        <thead>
            <tr>
                <th>Code</th>
                <th>Fonction</th>
                <th>Auteur</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($codes as $code):
            $isOwn = ($code['user_id'] == $userId);
        ?>
            <tr>
                <td><strong><?= e($code['code']) ?></strong></td>
                <td><?= e(excerpt($code['fonction'])) ?></td>
                <td>
                    <?php if ($isOwn): ?>
                        <span class="badge bg-primary">Moi</span>
                    <?php else: ?>
                        <?= e($code['author_prenom'] . ' ' . $code['author_nom']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($code['approved']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Approuvé</span>
                    <?php elseif ($isOwn): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $code['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <?php if ($isOwn || $isUserAdmin): ?>
                    <button class="btn btn-sm btn-ce-outline" onclick="editCode(<?= $code['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce code ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $code['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fonction</label>
                            <textarea name="fonction" class="form-control" rows="5" required></textarea>
                        </div>
                        <?php if (!$isUserAdmin): ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Ce code sera soumis à validation par un administrateur avant d'être visible par tous.</div>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du code</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchCodes', 'tableCodes');

const codesData = <?= json_encode($codes) ?>;
const currentUserId = <?= $userId ?>;

function showDetail(id) {
    const code = codesData.find(c => c.id == id);
    if (!code) return;
    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Code :</strong> ${code.code}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Fonction :</strong></p>
                <div class="p-3 bg-light rounded">${code.fonction || '-'}</div>
            </div>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editCode(id) {
    const code = codesData.find(c => c.id == id);
    if (!code) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="${code.code}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Fonction</label>
                    <textarea name="fonction" class="form-control" rows="5" required>${code.fonction || ''}</textarea>
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
