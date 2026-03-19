<?php
$pageTitle = 'Administration';
require_once __DIR__ . '/../../templates/header.php';
requireAdmin();
$db = getDB();
$adminUserId = getCurrentUserId();

// Auto-add approval columns if missing
try { $db->exec("ALTER TABLE modeles_courriers ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE modeles_courriers ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE procedures ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE procedures ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// === ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- Utilisateurs ---
    if ($action === 'add_user') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, nom, prenom, email_pro, tel_pro, ligne_interne, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['username']), $hash, trim($_POST['nom']), trim($_POST['prenom']),
            trim($_POST['email_pro'] ?? ''), trim($_POST['tel_pro'] ?? ''), trim($_POST['ligne_interne'] ?? ''),
            isset($_POST['is_admin']) ? 1 : 0
        ]);
        header('Location: index.php?msg=user_added');
        exit;
    }

    if ($action === 'edit_user') {
        $id = (int)$_POST['id'];
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, nom = ?, prenom = ?, email_pro = ?, tel_pro = ?, ligne_interne = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                trim($_POST['username']), $hash, trim($_POST['nom']), trim($_POST['prenom']),
                trim($_POST['email_pro'] ?? ''), trim($_POST['tel_pro'] ?? ''), trim($_POST['ligne_interne'] ?? ''),
                isset($_POST['is_admin']) ? 1 : 0, $id
            ]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, nom = ?, prenom = ?, email_pro = ?, tel_pro = ?, ligne_interne = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                trim($_POST['username']), trim($_POST['nom']), trim($_POST['prenom']),
                trim($_POST['email_pro'] ?? ''), trim($_POST['tel_pro'] ?? ''), trim($_POST['ligne_interne'] ?? ''),
                isset($_POST['is_admin']) ? 1 : 0, $id
            ]);
        }
        header('Location: index.php?msg=user_updated');
        exit;
    }

    if ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        if ($id !== getCurrentUserId()) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?msg=user_deleted');
        exit;
    }

    // --- Liens externes ---
    if ($action === 'add_link') {
        $stmt = $db->prepare("INSERT INTO liens_externes (nom, url, ordre) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), (int)($_POST['ordre'] ?? 0)]);
        header('Location: index.php?tab=liens&msg=link_added');
        exit;
    }

    if ($action === 'edit_link') {
        $stmt = $db->prepare("UPDATE liens_externes SET nom = ?, url = ?, ordre = ? WHERE id = ?");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), (int)($_POST['ordre'] ?? 0), (int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_updated');
        exit;
    }

    if ($action === 'delete_link') {
        $stmt = $db->prepare("DELETE FROM liens_externes WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_deleted');
        exit;
    }

    // --- Approbations ---
    if ($action === 'approve') {
        $table = $_POST['table'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $allowed_tables = ['modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles'];
        if (in_array($table, $allowed_tables) && $id > 0) {
            $stmt = $db->prepare("UPDATE `$table` SET approved = 1, approved_by = ? WHERE id = ?");
            $stmt->execute([$adminUserId, $id]);
        }
        header('Location: index.php?tab=approbations&msg=approved');
        exit;
    }

    if ($action === 'reject') {
        $table = $_POST['table'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $allowed_tables = ['modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles'];
        if (in_array($table, $allowed_tables) && $id > 0) {
            $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?tab=approbations&msg=rejected');
        exit;
    }

    // --- Suppression de données (admin gestion) ---
    if ($action === 'admin_delete') {
        $table = $_POST['table'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $allowed_tables = ['instances', 'demandes_rappel', 'offres', 'demandes_clients', 'suivi_production',
            'seances_phoning', 'credit_immobilier', 'calculateur_budget', 'formations', 'blocnotes',
            'courriers', 'modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles'];
        if (in_array($table, $allowed_tables) && $id > 0) {
            $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?tab=donnees&module=' . urlencode($_POST['module'] ?? '') . '&msg=deleted');
        exit;
    }
}

// Données
$users = $db->query("SELECT * FROM users ORDER BY nom, prenom")->fetchAll();
$liens = $db->query("SELECT * FROM liens_externes ORDER BY ordre ASC, nom ASC")->fetchAll();

// Pending approvals
$pendingModeles = $db->query("SELECT m.*, u.nom AS author_nom, u.prenom AS author_prenom FROM modeles_courriers m LEFT JOIN users u ON m.user_id = u.id WHERE m.approved = 0 ORDER BY m.id DESC")->fetchAll();
$pendingProcedures = $db->query("SELECT p.*, u.nom AS author_nom, u.prenom AS author_prenom FROM procedures p LEFT JOIN users u ON p.user_id = u.id WHERE p.approved = 0 ORDER BY p.id DESC")->fetchAll();
$pendingCodes = $db->query("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom FROM codes_utiles c LEFT JOIN users u ON c.user_id = u.id WHERE c.approved = 0 ORDER BY c.id DESC")->fetchAll();
$pendingContacts = $db->query("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom FROM contacts_utiles c LEFT JOIN users u ON c.user_id = u.id WHERE c.approved = 0 ORDER BY c.id DESC")->fetchAll();
$totalPending = count($pendingModeles) + count($pendingProcedures) + count($pendingCodes) + count($pendingContacts);

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
        'approved' => 'Élément approuvé avec succès.',
        'rejected' => 'Élément refusé et supprimé.',
        'deleted' => 'Enregistrement supprimé.',
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
        <a class="nav-link <?= $activeTab === 'approbations' ? 'active' : '' ?>" href="?tab=approbations">
            <i class="fas fa-check-circle"></i> Approbations
            <?php if ($totalPending > 0): ?>
                <span class="badge bg-danger"><?= $totalPending ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'donnees' ? 'active' : '' ?>" href="?tab=donnees"><i class="fas fa-database"></i> Données utilisateurs</a>
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
                        <div class="col-md-6"><label class="form-label">Identifiant *</label><input type="text" name="username" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Mot de passe *</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Email professionnel</label><input type="email" name="email_pro" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Tél. professionnel</label><input type="text" name="tel_pro" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Ligne interne</label><input type="text" name="ligne_interne" class="form-control"></div>
                        <div class="col-md-6"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_admin" id="addIsAdmin"><label class="form-check-label" for="addIsAdmin">Administrateur</label></div></div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Créer</button></div>
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
                <div class="col-md-6"><label class="form-label">Identifiant *</label><input type="text" name="username" class="form-control" value="${escapeHtml(u.username)}" required></div>
                <div class="col-md-6"><label class="form-label">Nouveau mot de passe <small class="text-muted">(vide = inchangé)</small></label><input type="password" name="password" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="${escapeHtml(u.nom)}" required></div>
                <div class="col-md-6"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" value="${escapeHtml(u.prenom)}" required></div>
                <div class="col-md-6"><label class="form-label">Email professionnel</label><input type="email" name="email_pro" class="form-control" value="${escapeHtml(u.email_pro)}"></div>
                <div class="col-md-3"><label class="form-label">Tél. professionnel</label><input type="text" name="tel_pro" class="form-control" value="${escapeHtml(u.tel_pro)}"></div>
                <div class="col-md-3"><label class="form-label">Ligne interne</label><input type="text" name="ligne_interne" class="form-control" value="${escapeHtml(u.ligne_interne)}"></div>
                <div class="col-md-6"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_admin" id="editIsAdmin" ${u.is_admin == 1 ? 'checked' : ''}><label class="form-check-label" for="editIsAdmin">Administrateur</label></div></div>
                <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php elseif ($activeTab === 'approbations'): ?>
<!-- =============== APPROBATIONS =============== -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color:#ffc107;">
            <div class="stat-number"><?= $totalPending ?></div>
            <div class="stat-label">En attente d'approbation</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($pendingModeles) ?></div>
            <div class="stat-label">Modèles courriers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($pendingProcedures) ?></div>
            <div class="stat-label">Procédures</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($pendingCodes) + count($pendingContacts) ?></div>
            <div class="stat-label">Codes / Contacts</div>
        </div>
    </div>
</div>

<?php if ($totalPending === 0): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Aucun élément en attente d'approbation.</div>
<?php else: ?>

<?php if (!empty($pendingModeles)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-envelope"></i> Modèles de courriers en attente</h3></div>
    <table class="data-table">
        <thead><tr><th>Nom</th><th>Objet</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingModeles as $m): ?>
            <tr>
                <td><strong><?= e($m['nom_modele']) ?></strong></td>
                <td><?= e(excerpt($m['objet'] ?? '', 50)) ?></td>
                <td><?= e($m['author_prenom'] . ' ' . $m['author_nom']) ?></td>
                <td class="actions">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="table" value="modeles_courriers">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer ce modèle ?')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="table" value="modeles_courriers">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($pendingProcedures)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-book"></i> Procédures en attente</h3></div>
    <table class="data-table">
        <thead><tr><th>Nom</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingProcedures as $p): ?>
            <tr>
                <td><strong><?= e($p['nom']) ?></strong></td>
                <td><?= e($p['author_prenom'] . ' ' . $p['author_nom']) ?></td>
                <td class="actions">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="table" value="procedures">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer ?')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="table" value="procedures">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($pendingCodes)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-code"></i> Codes utiles en attente</h3></div>
    <table class="data-table">
        <thead><tr><th>Code</th><th>Fonction</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingCodes as $c): ?>
            <tr>
                <td><strong><?= e($c['code']) ?></strong></td>
                <td><?= e(excerpt($c['fonction'], 50)) ?></td>
                <td><?= e($c['author_prenom'] . ' ' . $c['author_nom']) ?></td>
                <td class="actions">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="table" value="codes_utiles">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer ?')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="table" value="codes_utiles">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($pendingContacts)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-address-book"></i> Contacts utiles en attente</h3></div>
    <table class="data-table">
        <thead><tr><th>Service</th><th>Téléphone</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingContacts as $c): ?>
            <tr>
                <td><strong><?= e($c['service']) ?></strong></td>
                <td><?= e($c['telephone']) ?></td>
                <td><?= e($c['author_prenom'] . ' ' . $c['author_nom']) ?></td>
                <td class="actions">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="table" value="contacts_utiles">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer ?')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="table" value="contacts_utiles">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>

<?php elseif ($activeTab === 'donnees'): ?>
<!-- =============== DONNÉES UTILISATEURS =============== -->
<?php
$modules = [
    'instances' => ['label' => 'Instances', 'icon' => 'tasks', 'cols' => ['titre','date_ajout'], 'display' => ['Titre','Date']],
    'demandes_rappel' => ['label' => 'Demandes de rappel', 'icon' => 'phone', 'cols' => ['telephone','raison','date_ajout'], 'display' => ['Téléphone','Raison','Date']],
    'offres' => ['label' => 'Offres du moment', 'icon' => 'gift', 'cols' => ['titre','date_debut','date_fin'], 'display' => ['Titre','Début','Fin']],
    'demandes_clients' => ['label' => 'Demandes clients', 'icon' => 'headset', 'cols' => ['service','date_ajout'], 'display' => ['Service','Date']],
    'suivi_production' => ['label' => 'Suivi production', 'icon' => 'chart-line', 'cols' => ['categorie','montant','date_ajout'], 'display' => ['Catégorie','Montant','Date']],
    'seances_phoning' => ['label' => 'Séances phoning', 'icon' => 'phone-volume', 'cols' => ['date_seance','nb_appels'], 'display' => ['Date','Nb appels']],
    'credit_immobilier' => ['label' => 'Crédit immobilier', 'icon' => 'home', 'cols' => ['numero_personne','montant_acquisition','date_ajout'], 'display' => ['N° personne','Montant','Date']],
    'calculateur_budget' => ['label' => 'Calculateur budget', 'icon' => 'calculator', 'cols' => ['id','created_at'], 'display' => ['ID','Date']],
    'formations' => ['label' => 'Formations', 'icon' => 'graduation-cap', 'cols' => ['titre','date_debut'], 'display' => ['Titre','Date']],
    'blocnotes' => ['label' => 'Bloc-notes', 'icon' => 'sticky-note', 'cols' => ['titre','updated_at'], 'display' => ['Titre','Dernière modif']],
    'courriers' => ['label' => 'Courriers', 'icon' => 'envelope', 'cols' => ['nom_prenom_dest','objet','date_courrier'], 'display' => ['Destinataire','Objet','Date']],
    'modeles_courriers' => ['label' => 'Modèles courriers', 'icon' => 'file-alt', 'cols' => ['nom_modele','approved'], 'display' => ['Nom','Approuvé']],
    'procedures' => ['label' => 'Procédures', 'icon' => 'book', 'cols' => ['nom','approved'], 'display' => ['Nom','Approuvé']],
    'codes_utiles' => ['label' => 'Codes utiles', 'icon' => 'code', 'cols' => ['code','fonction'], 'display' => ['Code','Fonction']],
    'contacts_utiles' => ['label' => 'Contacts utiles', 'icon' => 'address-book', 'cols' => ['service','telephone'], 'display' => ['Service','Téléphone']],
];

$selectedModule = $_GET['module'] ?? '';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($modules as $key => $mod): ?>
                <a href="?tab=donnees&module=<?= $key ?>" class="btn <?= $selectedModule === $key ? 'btn-ce' : 'btn-ce-outline' ?> btn-sm">
                    <i class="fas fa-<?= $mod['icon'] ?>"></i> <?= $mod['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($selectedModule && isset($modules[$selectedModule])):
    $mod = $modules[$selectedModule];
    $cols = $mod['cols'];
    $colsStr = implode(', ', array_map(fn($c) => "t.`$c`", $cols));

    // Check if table has user_id column
    $hasUserId = true;
    try {
        $testStmt = $db->query("SELECT user_id FROM `$selectedModule` LIMIT 1");
    } catch (Exception $e) {
        $hasUserId = false;
    }

    if ($hasUserId) {
        $allData = $db->query("SELECT t.id, $colsStr, t.user_id, u.nom AS user_nom, u.prenom AS user_prenom
            FROM `$selectedModule` t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC
            LIMIT 500")->fetchAll();
    } else {
        $allData = $db->query("SELECT t.id, $colsStr FROM `$selectedModule` t ORDER BY t.id DESC LIMIT 500")->fetchAll();
    }
?>

<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-<?= $mod['icon'] ?>"></i> <?= $mod['label'] ?> (<?= count($allData) ?> enregistrements)</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchData" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableData">
        <thead>
            <tr>
                <th>ID</th>
                <?php if ($hasUserId): ?><th>Utilisateur</th><?php endif; ?>
                <?php foreach ($mod['display'] as $d): ?><th><?= $d ?></th><?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allData as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <?php if ($hasUserId): ?>
                <td><span class="badge bg-secondary"><?= e(($row['user_prenom'] ?? '') . ' ' . ($row['user_nom'] ?? '')) ?></span></td>
                <?php endif; ?>
                <?php foreach ($cols as $c):
                    $val = $row[$c] ?? '';
                    if ($c === 'approved') $val = $val ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-warning text-dark">Non</span>';
                    elseif ($c === 'montant' || $c === 'montant_acquisition') $val = number_format((float)$val, 2, ',', ' ') . ' €';
                    else $val = e(excerpt((string)$val, 60));
                ?>
                <td><?= $val ?></td>
                <?php endforeach; ?>
                <td class="actions">
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet enregistrement ?')">
                        <input type="hidden" name="action" value="admin_delete">
                        <input type="hidden" name="table" value="<?= $selectedModule ?>">
                        <input type="hidden" name="module" value="<?= $selectedModule ?>">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>filterTable('searchData', 'tableData');</script>

<?php else: ?>
<div class="alert alert-info"><i class="fas fa-hand-pointer"></i> Sélectionnez un module ci-dessus pour voir les données de tous les utilisateurs.</div>
<?php endif; ?>

<?php elseif ($activeTab === 'liens'): ?>
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
                        <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">URL *</label><input type="url" name="url" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Ordre d'affichage</label><input type="number" name="ordre" class="form-control" value="0"></div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Ajouter</button></div>
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
                <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="${escapeHtml(l.nom)}" required></div>
                <div class="col-md-6"><label class="form-label">URL *</label><input type="url" name="url" class="form-control" value="${escapeHtml(l.url)}" required></div>
                <div class="col-md-3"><label class="form-label">Ordre d'affichage</label><input type="number" name="ordre" class="form-control" value="${l.ordre}"></div>
                <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editLinkModal')).show();
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
