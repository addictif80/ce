<?php
$pageTitle = 'Contacts utiles';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Auto-add approval columns
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// Auto-create contacts_equipe table
$db->exec("CREATE TABLE IF NOT EXISTS contacts_equipe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    ligne_interne VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE contacts_equipe ADD COLUMN user_id INT DEFAULT NULL"); } catch (Exception $e) {}

// Ajout contact équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_equipe') {
    $stmt = $db->prepare("INSERT INTO contacts_equipe (user_id, nom, prenom, email, telephone, ligne_interne) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, trim($_POST['nom']), trim($_POST['prenom']), trim($_POST['email'] ?? ''), trim($_POST['telephone'] ?? ''), trim($_POST['ligne_interne'] ?? '')]);
    header('Location: index.php');
    exit;
}

// Edition contact équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_equipe') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE contacts_equipe SET nom = ?, prenom = ?, email = ?, telephone = ?, ligne_interne = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([trim($_POST['nom']), trim($_POST['prenom']), trim($_POST['email'] ?? ''), trim($_POST['telephone'] ?? ''), trim($_POST['ligne_interne'] ?? ''), $id, $userId]);
    header('Location: index.php');
    exit;
}

// Suppression contact équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_equipe') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM contacts_equipe WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php');
    exit;
}

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
    header('Location: index.php?open=' . $id);
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

// Helper: lien tel: avec préfixe 0 si nécessaire
function telLink($number, $addZero = true) {
    if (empty($number)) return '<span class="text-muted">-</span>';
    $clean = preg_replace('/[^0-9+]/', '', $number);
    $dial = $addZero ? '0' . $clean : $clean;
    return '<a href="tel:' . e($dial) . '"><i class="fas fa-phone-alt fa-sm"></i> ' . e($number) . '</a>';
}
function mailLink($mail) {
    if (empty($mail)) return '<span class="text-muted">-</span>';
    return '<a href="mailto:' . e($mail) . '"><i class="fas fa-envelope fa-sm"></i> ' . e($mail) . '</a>';
}

// Utilisateurs de l'application (contacts automatiques) + contacts équipe manuels (privés par utilisateur)
$stmtUsers = $db->prepare("SELECT id, nom, prenom, email_pro AS email, tel_pro AS telephone, ligne_interne, 'auto' AS source FROM users
    UNION ALL
    SELECT id, nom, prenom, email, telephone, ligne_interne, 'manual' AS source FROM contacts_equipe WHERE user_id = ?
    ORDER BY nom, prenom");
$stmtUsers->execute([$userId]);
$userContacts = $stmtUsers->fetchAll();

// Contacts équipe manuels seuls (pour le JS edit)
$stmtManualEquipe = $db->prepare("SELECT * FROM contacts_equipe WHERE user_id = ? ORDER BY nom, prenom");
$stmtManualEquipe->execute([$userId]);
$manualEquipe = $stmtManualEquipe->fetchAll();
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($userContacts) ?></div>
            <div class="stat-label">Collaborateurs</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($contacts) ?></div>
            <div class="stat-label">Contacts utiles</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau contact</button>
    </div>
</div>

<!-- Tableau Équipe -->
<div class="data-table-container mb-4">
    <div class="data-table-header">
        <h3><i class="fas fa-users"></i> Équipe</h3>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-sm btn-ce" data-bs-toggle="modal" data-bs-target="#addEquipeModal"><i class="fas fa-plus"></i> Ajouter</button>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchEquipe" placeholder="Rechercher un collaborateur...">
            </div>
        </div>
    </div>
    <table class="data-table" id="tableEquipe">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Mail</th>
                <th>Téléphone</th>
                <th>Ligne directe</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($userContacts as $uc): ?>
            <tr>
                <td><strong><?= e($uc['nom']) ?></strong></td>
                <td><?= e($uc['prenom']) ?></td>
                <td><?= mailLink($uc['email']) ?></td>
                <td><?= telLink($uc['telephone'], true) ?></td>
                <td><?= telLink($uc['ligne_interne'], false) ?></td>
                <td class="actions">
                    <?php if ($uc['source'] === 'manual'): ?>
                        <button class="btn btn-sm btn-ce-outline" onclick="editEquipe(<?= $uc['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce contact équipe ?')">
                            <input type="hidden" name="action" value="delete_equipe">
                            <input type="hidden" name="id" value="<?= $uc['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="fas fa-user-shield"></i> Utilisateur</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Tableau Contacts manuels -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-address-book"></i> Contacts utiles</h3>
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
                <td><?= telLink($contact['telephone'], true) ?></td>
                <td><?= mailLink($contact['mail']) ?></td>
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

<!-- Modal Ajout Équipe -->
<div class="modal fade modal-fullscreen-custom" id="addEquipeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouveau contact équipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_equipe">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mail</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ligne directe</label>
                            <input type="text" name="ligne_interne" class="form-control">
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

<!-- Modal Edit Équipe -->
<div class="modal fade modal-fullscreen-custom" id="editEquipeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le contact équipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editEquipeContent">
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
filterTable('searchEquipe', 'tableEquipe');
filterTable('searchContacts', 'tableContacts');

const contactsData = <?= json_encode($contacts) ?>;

function telHtml(num, addZero) {
    if (!num) return '-';
    var clean = num.replace(/[^0-9+]/g, '');
    var dial = addZero ? '0' + clean : clean;
    return `<a href="tel:${dial}"><i class="fas fa-phone-alt fa-sm"></i> ${num}</a>`;
}
function mailHtml(m) {
    if (!m) return '-';
    return `<a href="mailto:${m}"><i class="fas fa-envelope fa-sm"></i> ${m}</a>`;
}

function showDetail(id) {
    const contact = contactsData.find(c => c.id == id);
    if (!contact) return;
    document.getElementById('detailContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Service :</strong> ${contact.service || '-'}</p>
                <p><strong>Téléphone :</strong> ${telHtml(contact.telephone, true)}</p>
                <p><strong>Mail :</strong> ${mailHtml(contact.mail)}</p>
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

const equipeData = <?= json_encode($manualEquipe) ?>;

function editEquipe(id) {
    const c = equipeData.find(e => e.id == id);
    if (!c) return;
    document.getElementById('editEquipeContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_equipe">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" value="${c.nom || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" value="${c.prenom || ''}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mail</label>
                    <input type="email" name="email" class="form-control" value="${c.email || ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="${c.telephone || ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ligne directe</label>
                    <input type="text" name="ligne_interne" class="form-control" value="${c.ligne_interne || ''}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editEquipeModal')).show();
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
