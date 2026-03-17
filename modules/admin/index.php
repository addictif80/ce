<?php
$pageTitle = 'Administration';
require_once __DIR__ . '/../../templates/header.php';
requireAdmin();
$db = getDB();

// === GESTION UTILISATEURS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Ajout utilisateur
    if ($action === 'add_user') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, nom, prenom, email_pro, tel_pro, ligne_interne, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['username']),
            $hash,
            trim($_POST['nom']),
            trim($_POST['prenom']),
            trim($_POST['email_pro'] ?? ''),
            trim($_POST['tel_pro'] ?? ''),
            trim($_POST['ligne_interne'] ?? ''),
            isset($_POST['is_admin']) ? 1 : 0
        ]);
        header('Location: index.php?msg=user_added');
        exit;
    }

    // Modification utilisateur
    if ($action === 'edit_user') {
        $id = (int)$_POST['id'];
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, nom = ?, prenom = ?, email_pro = ?, tel_pro = ?, ligne_interne = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                trim($_POST['username']),
                $hash,
                trim($_POST['nom']),
                trim($_POST['prenom']),
                trim($_POST['email_pro'] ?? ''),
                trim($_POST['tel_pro'] ?? ''),
                trim($_POST['ligne_interne'] ?? ''),
                isset($_POST['is_admin']) ? 1 : 0,
                $id
            ]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, nom = ?, prenom = ?, email_pro = ?, tel_pro = ?, ligne_interne = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                trim($_POST['username']),
                trim($_POST['nom']),
                trim($_POST['prenom']),
                trim($_POST['email_pro'] ?? ''),
                trim($_POST['tel_pro'] ?? ''),
                trim($_POST['ligne_interne'] ?? ''),
                isset($_POST['is_admin']) ? 1 : 0,
                $id
            ]);
        }
        header('Location: index.php?msg=user_updated');
        exit;
    }

    // Suppression utilisateur
    if ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        if ($id !== getCurrentUserId()) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?msg=user_deleted');
        exit;
    }

    // Ajout lien externe
    if ($action === 'add_link') {
        $stmt = $db->prepare("INSERT INTO liens_externes (nom, url, ordre) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), (int)($_POST['ordre'] ?? 0)]);
        header('Location: index.php?tab=liens&msg=link_added');
        exit;
    }

    // Modification lien externe
    if ($action === 'edit_link') {
        $stmt = $db->prepare("UPDATE liens_externes SET nom = ?, url = ?, ordre = ? WHERE id = ?");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), (int)($_POST['ordre'] ?? 0), (int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_updated');
        exit;
    }

    // Suppression lien externe
    if ($action === 'delete_link') {
        $stmt = $db->prepare("DELETE FROM liens_externes WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_deleted');
        exit;
    }
}

// Données
$users = $db->query("SELECT * FROM users ORDER BY nom, prenom")->fetchAll();
$liens = $db->query("SELECT * FROM liens_externes ORDER BY ordre ASC, nom ASC")->fetchAll();
$activeTab = $_GET['tab'] ?? 'users';
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php
    $msgs = [
        'user_added' => 'Utilisateur ajouté avec succès.',
        'user_updated' => 'Utilisateur modifié avec succès.',
        'user_deleted' => 'Utilisateur supprimé.',
        'link_added' => 'Lien ajouté avec succès.',
        'link_updated' => 'Lien modifié avec succès.',
        'link_deleted' => 'Lien supprimé.',
    ];
    echo $msgs[$_GET['msg']] ?? 'Opération effectuée.';
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Onglets -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?>" href="?tab=users"><i class="fas fa-users"></i> Utilisateurs</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'liens' ? 'active' : '' ?>" href="?tab=liens"><i class="fas fa-external-link-alt"></i> Liens externes</a>
    </li>
</ul>

<?php if ($activeTab === 'users'): ?>
<!-- =============== UTILISATEURS =============== -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($users) ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus"></i> Nouvel utilisateur</button>
    </div>
</div>

<div class="data-table-container">
    <div class="data-table-header">
        <h3>Liste des utilisateurs</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchUsers" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableUsers">
        <thead>
            <tr>
                <th>Identifiant</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email pro</th>
                <th>Tél. pro</th>
                <th>Ligne interne</th>
                <th>Admin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['username']) ?></td>
                <td><?= e($u['nom']) ?></td>
                <td><?= e($u['prenom']) ?></td>
                <td><?= e($u['email_pro']) ?></td>
                <td><?= e($u['tel_pro']) ?></td>
                <td><?= e($u['ligne_interne']) ?></td>
                <td><?= $u['is_admin'] ? '<span class="badge bg-primary">Admin</span>' : '' ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="editUser(<?= $u['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <?php if ($u['id'] !== getCurrentUserId()): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur et toutes ses données ?')">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout Utilisateur -->
<div class="modal fade modal-fullscreen-custom" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvel utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Identifiant *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email professionnel</label>
                            <input type="email" name="email_pro" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tél. professionnel</label>
                            <input type="text" name="tel_pro" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ligne interne</label>
                            <input type="text" name="ligne_interne" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_admin" id="addIsAdmin">
                                <label class="form-check-label" for="addIsAdmin">Administrateur</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Créer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edition Utilisateur -->
<div class="modal fade modal-fullscreen-custom" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editUserContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchUsers', 'tableUsers');

const usersData = <?= json_encode($users) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function editUser(id) {
    const u = usersData.find(x => x.id == id);
    if (!u) return;
    document.getElementById('editUserContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Identifiant *</label>
                    <input type="text" name="username" class="form-control" value="${escapeHtml(u.username)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nouveau mot de passe <small class="text-muted">(laisser vide pour ne pas changer)</small></label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" value="${escapeHtml(u.nom)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" value="${escapeHtml(u.prenom)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email professionnel</label>
                    <input type="email" name="email_pro" class="form-control" value="${escapeHtml(u.email_pro)}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tél. professionnel</label>
                    <input type="text" name="tel_pro" class="form-control" value="${escapeHtml(u.tel_pro)}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ligne interne</label>
                    <input type="text" name="ligne_interne" class="form-control" value="${escapeHtml(u.ligne_interne)}">
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_admin" id="editIsAdmin" ${u.is_admin == 1 ? 'checked' : ''}>
                        <label class="form-check-label" for="editIsAdmin">Administrateur</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php else: ?>
<!-- =============== LIENS EXTERNES =============== -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($liens) ?></div>
            <div class="stat-label">Liens configurés</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addLinkModal"><i class="fas fa-plus"></i> Nouveau lien</button>
    </div>
</div>

<div class="data-table-container">
    <div class="data-table-header">
        <h3>Liens externes</h3>
    </div>
    <table class="data-table" id="tableLinks">
        <thead>
            <tr>
                <th>Ordre</th>
                <th>Nom</th>
                <th>URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($liens as $l): ?>
            <tr>
                <td><?= $l['ordre'] ?></td>
                <td><?= e($l['nom']) ?></td>
                <td><a href="<?= e($l['url']) ?>" target="_blank"><?= e(excerpt($l['url'], 60)) ?></a></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="editLink(<?= $l['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce lien ?')">
                        <input type="hidden" name="action" value="delete_link">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout Lien -->
<div class="modal fade modal-fullscreen-custom" id="addLinkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau lien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_link">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL *</label>
                            <input type="url" name="url" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" name="ordre" class="form-control" value="0">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Ajouter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edition Lien -->
<div class="modal fade modal-fullscreen-custom" id="editLinkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le lien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editLinkContent"></div>
        </div>
    </div>
</div>

<script>
const liensData = <?= json_encode($liens) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function editLink(id) {
    const l = liensData.find(x => x.id == id);
    if (!l) return;
    document.getElementById('editLinkContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_link">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" value="${escapeHtml(l.nom)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL *</label>
                    <input type="url" name="url" class="form-control" value="${escapeHtml(l.url)}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordre d'affichage</label>
                    <input type="number" name="ordre" class="form-control" value="${l.ordre}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editLinkModal')).show();
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
