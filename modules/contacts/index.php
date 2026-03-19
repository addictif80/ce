<?php
$pageTitle = 'Contacts utiles';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Auto-add approval columns
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO contacts_utiles (user_id, telephone, mail, service, a_contacter_pour, approved, approved_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $_POST['telephone'], $_POST['mail'], $_POST['service'], $_POST['a_contacter_pour'],
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
        $stmt = $db->prepare("UPDATE contacts_utiles SET telephone = ?, mail = ?, service = ?, a_contacter_pour = ? WHERE id = ?");
        $stmt->execute([$_POST['telephone'], $_POST['mail'], $_POST['service'], $_POST['a_contacter_pour'], $id]);
    } else {
        $stmt = $db->prepare("UPDATE contacts_utiles SET telephone = ?, mail = ?, service = ?, a_contacter_pour = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['telephone'], $_POST['mail'], $_POST['service'], $_POST['a_contacter_pour'], $id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM contacts_utiles WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM contacts_utiles WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste : ses propres contacts + tous les contacts approuvés
$stmt = $db->prepare("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom
    FROM contacts_utiles c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ? OR c.approved = 1
    ORDER BY c.service ASC");
$stmt->execute([$userId]);
$contacts = $stmt->fetchAll();
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($contacts) ?></div>
            <div class="stat-label">Contacts disponibles</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau contact</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Tous les contacts</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchContacts" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableContacts">
        <thead>
            <tr>
                <th>Service</th>
                <th>Téléphone</th>
                <th>Mail</th>
                <th>À contacter pour</th>
                <th>Auteur</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contacts as $contact):
            $isOwn = ($contact['user_id'] == $userId);
        ?>
            <tr>
                <td><strong><?= e($contact['service']) ?></strong></td>
                <td><?= e($contact['telephone']) ?></td>
                <td><?= e($contact['mail']) ?></td>
                <td><?= e(excerpt($contact['a_contacter_pour'])) ?></td>
                <td>
                    <?php if ($isOwn): ?>
                        <span class="badge bg-primary">Moi</span>
                    <?php else: ?>
                        <?= e($contact['author_prenom'] . ' ' . $contact['author_nom']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($contact['approved']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Approuvé</span>
                    <?php elseif ($isOwn): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $contact['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <?php if ($isOwn || $isUserAdmin): ?>
                    <button class="btn btn-sm btn-ce-outline" onclick="editContact(<?= $contact['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce contact ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $contact['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <input type="text" name="service" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mail</label>
                            <input type="email" name="mail" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">À contacter pour</label>
                            <textarea name="a_contacter_pour" class="form-control" rows="5"></textarea>
                        </div>
                        <?php if (!$isUserAdmin): ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Ce contact sera soumis à validation par un administrateur avant d'être visible par tous.</div>
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
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails du contact</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchContacts', 'tableContacts');

const contactsData = <?= json_encode($contacts) ?>;

function showDetail(id) {
    const contact = contactsData.find(c => c.id == id);
    if (!contact) return;
    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Service :</strong> ${contact.service || '-'}</p>
                <p><strong>Téléphone :</strong> ${contact.telephone || '-'}</p>
                <p><strong>Mail :</strong> ${contact.mail || '-'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>À contacter pour :</strong></p>
                <div class="p-3 bg-light rounded">${contact.a_contacter_pour || '-'}</div>
            </div>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editContact(id) {
    const contact = contactsData.find(c => c.id == id);
    if (!contact) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Service</label>
                    <input type="text" name="service" class="form-control" value="${contact.service || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="${contact.telephone || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mail</label>
                    <input type="email" name="mail" class="form-control" value="${contact.mail || ''}">
                </div>
                <div class="col-12">
                    <label class="form-label">À contacter pour</label>
                    <textarea name="a_contacter_pour" class="form-control" rows="5">${contact.a_contacter_pour || ''}</textarea>
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
