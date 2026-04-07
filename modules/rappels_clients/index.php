<?php
$pageTitle = 'Rappels clients';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Migration
$db->exec("CREATE TABLE IF NOT EXISTS demandes_rappel_client (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    identite_client VARCHAR(200) NOT NULL,
    conseiller_id INT NOT NULL,
    motif TEXT,
    traitee TINYINT(1) DEFAULT 0,
    token VARCHAR(64) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE demandes_rappel_client ADD COLUMN token VARCHAR(64) DEFAULT NULL"); } catch (Exception $e) {}

// Récupérer les conseillers depuis contacts_equipe
$conseillers = $db->query("SELECT id, prenom, nom, email FROM contacts_equipe ORDER BY nom, prenom")->fetchAll();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO demandes_rappel_client (user_id, identite_client, conseiller_id, motif, traitee, token, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())");
    $stmt->execute([$userId, trim($_POST['identite_client']), (int)$_POST['conseiller_id'], trim($_POST['motif']), $token]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $traitee = isset($_POST['traitee']) ? 1 : 0;
    if ($isUserAdmin) {
        $stmt = $db->prepare("UPDATE demandes_rappel_client SET identite_client = ?, conseiller_id = ?, motif = ?, traitee = ? WHERE id = ?");
        $stmt->execute([trim($_POST['identite_client']), (int)$_POST['conseiller_id'], trim($_POST['motif']), $traitee, $id]);
    } else {
        $stmt = $db->prepare("UPDATE demandes_rappel_client SET identite_client = ?, conseiller_id = ?, motif = ?, traitee = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([trim($_POST['identite_client']), (int)$_POST['conseiller_id'], trim($_POST['motif']), $traitee, $id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM demandes_rappel_client WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM demandes_rappel_client WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste
$stmt = $db->prepare("SELECT d.*, c.prenom AS cons_prenom, c.nom AS cons_nom, c.email AS cons_email
    FROM demandes_rappel_client d
    LEFT JOIN contacts_equipe c ON d.conseiller_id = c.id
    WHERE d.user_id = ?
    ORDER BY d.traitee ASC, d.created_at DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();

$nbEnAttente = count(array_filter($demandes, fn($d) => !$d['traitee']));
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card<?= $nbEnAttente > 0 ? ' stat-warning' : '' ?>">
            <div class="stat-number"><?= $nbEnAttente ?></div>
            <div class="stat-label">Rappels en attente</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <?php if (empty($conseillers)): ?>
            <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Aucun conseiller dans les contacts équipe. <a href="../contacts/index.php">Ajouter des contacts</a></div>
        <?php else: ?>
            <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle demande</button>
        <?php endif; ?>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Demandes de rappel clients</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchDemandes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableDemandes">
        <thead>
            <tr>
                <th>Client</th>
                <th>Conseiller concerné</th>
                <th>Motif</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $d): ?>
            <tr>
                <td><strong><?= e($d['identite_client']) ?></strong></td>
                <td>
                    <?= e($d['cons_prenom'] . ' ' . $d['cons_nom']) ?>
                    <?php if ($d['cons_email']): ?>
                        <br><small class="text-muted"><?= e($d['cons_email']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= e(mb_strimwidth($d['motif'] ?? '', 0, 80, '…')) ?></td>
                <td><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
                <td>
                    <?php if ($d['traitee']): ?>
                        <span class="badge-fait"><i class="fas fa-check"></i> Traité</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($d['cons_email']): ?>
                        <a href="share_mail.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-ce-outline" title="Envoyer par mail"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDemande(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette demande ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout -->
<div class="modal fade" id="addModal" tabindex="-1">
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
                        <div class="col-12">
                            <label class="form-label">Identité du client</label>
                            <input type="text" name="identite_client" class="form-control" placeholder="Nom Prénom ou n° client" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Conseiller concerné</label>
                            <select name="conseiller_id" class="form-select" required>
                                <option value="">— Sélectionner un conseiller —</option>
                                <?php foreach ($conseillers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?><?= $c['email'] ? ' (' . e($c['email']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif de la demande</label>
                            <textarea name="motif" class="form-control" rows="4" placeholder="Décrivez le motif du rappel..."></textarea>
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

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchDemandes', 'tableDemandes');

const demandesData = <?= json_encode($demandes) ?>;
const conseillersData = <?= json_encode($conseillers) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function editDemande(id) {
    const d = demandesData.find(x => x.id == id);
    if (!d) return;

    const optionsHtml = conseillersData.map(c =>
        `<option value="${c.id}" ${d.conseiller_id == c.id ? 'selected' : ''}>${escapeHtml(c.prenom + ' ' + c.nom)}${c.email ? ' (' + escapeHtml(c.email) + ')' : ''}</option>`
    ).join('');

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Identité du client</label>
                    <input type="text" name="identite_client" class="form-control" value="${escapeHtml(d.identite_client)}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Conseiller concerné</label>
                    <select name="conseiller_id" class="form-select" required>
                        <option value="">— Sélectionner un conseiller —</option>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Motif de la demande</label>
                    <textarea name="motif" class="form-control" rows="4">${escapeHtml(d.motif)}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="traitee" id="editTraitee" value="1" ${d.traitee == 1 ? 'checked' : ''}>
                        <label class="form-check-label" for="editTraitee">Marquer comme traité</label>
                    </div>
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
