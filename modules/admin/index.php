<?php
// AJAX endpoints (before header to avoid HTML output)
$allowedTables = ['instances','demandes_rappel','offres','demandes_clients','suivi_production',
    'seances_phoning','credit_immobilier','calculateur_budget','formations','blocnotes',
    'courriers','modeles_courriers','procedures','codes_utiles','contacts_utiles'];

// Fields that should never be editable
$systemFields = ['id','user_id','user_nom','user_prenom','created_at','updated_at','password','variables','approved_by'];

if (isset($_GET['ajax']) && $_GET['ajax'] === 'schema') {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
    if (!isAdmin()) { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }
    $db = getDB();
    $table = $_GET['table'] ?? '';
    if (!in_array($table, $allowedTables)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'invalid']);
        exit;
    }
    $cols = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($cols);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'detail') {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
    if (!isAdmin()) { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }
    $db = getDB();
    $table = $_GET['table'] ?? '';
    $id = (int)($_GET['id'] ?? 0);
    if (!in_array($table, $allowedTables) || $id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'invalid']);
        exit;
    }
    // Check if table has user_id
    $hasUserId = true;
    try { $db->query("SELECT user_id FROM `$table` LIMIT 1"); } catch (Exception $e) { $hasUserId = false; }
    if ($hasUserId) {
        $stmt = $db->prepare("SELECT t.*, u.nom AS user_nom, u.prenom AS user_prenom FROM `$table` t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    } else {
        $stmt = $db->prepare("SELECT t.* FROM `$table` t WHERE t.id = ?");
    }
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($row ?: ['error' => 'not_found']);
    exit;
}

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
try { $db->exec("ALTER TABLE offres ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE offres ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}
ensureProcedureProposalsSchema();


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
        $catId = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
        $mode  = in_array($_POST['mode_ouverture'] ?? '', ['onglet','fenetre']) ? $_POST['mode_ouverture'] : 'onglet';
        $stmt = $db->prepare("INSERT INTO liens_externes (nom, url, categorie_id, ordre, mode_ouverture) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), $catId, (int)($_POST['ordre'] ?? 0), $mode]);
        header('Location: index.php?tab=liens&msg=link_added');
        exit;
    }

    if ($action === 'edit_link') {
        $catId = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
        $mode  = in_array($_POST['mode_ouverture'] ?? '', ['onglet','fenetre']) ? $_POST['mode_ouverture'] : 'onglet';
        $stmt = $db->prepare("UPDATE liens_externes SET nom = ?, url = ?, categorie_id = ?, ordre = ?, mode_ouverture = ? WHERE id = ?");
        $stmt->execute([trim($_POST['nom']), trim($_POST['url']), $catId, (int)($_POST['ordre'] ?? 0), $mode, (int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_updated');
        exit;
    }

    if ($action === 'delete_link') {
        $stmt = $db->prepare("DELETE FROM liens_externes WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=link_deleted');
        exit;
    }

    // --- Catégories de liens ---
    if ($action === 'add_categorie_lien') {
        $stmt = $db->prepare("INSERT INTO categories_liens (nom, ordre) VALUES (?, ?)");
        $stmt->execute([trim($_POST['nom']), (int)($_POST['ordre'] ?? 0)]);
        header('Location: index.php?tab=liens&msg=cat_added');
        exit;
    }

    if ($action === 'edit_categorie_lien') {
        $stmt = $db->prepare("UPDATE categories_liens SET nom = ?, ordre = ? WHERE id = ?");
        $stmt->execute([trim($_POST['nom']), (int)($_POST['ordre'] ?? 0), (int)$_POST['id']]);
        header('Location: index.php?tab=liens&msg=cat_updated');
        exit;
    }

    if ($action === 'delete_categorie_lien') {
        $id = (int)$_POST['id'];
        // Détacher les liens de cette catégorie
        $db->prepare("UPDATE liens_externes SET categorie_id = NULL WHERE categorie_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM categories_liens WHERE id = ?")->execute([$id]);
        header('Location: index.php?tab=liens&msg=cat_deleted');
        exit;
    }

    // --- Menu ---
    if ($action === 'save_menu_order') {
        $items = json_decode($_POST['menu_order'] ?? '[]', true);
        if (is_array($items)) {
            $stmt = $db->prepare("UPDATE menu_config SET ordre = ?, parent_key = ? WHERE item_key = ?");
            foreach ($items as $item) {
                $stmt->execute([(int)$item['ordre'], $item['parent_key'] ?? null, $item['item_key']]);
            }
        }
        header('Location: index.php?tab=menu&msg=menu_saved');
        exit;
    }

    if ($action === 'edit_menu_item') {
        $stmt = $db->prepare("UPDATE menu_config SET label = ?, icon = ?, visible = ? WHERE id = ?");
        $stmt->execute([trim($_POST['label']), trim($_POST['icon']), isset($_POST['visible']) ? 1 : 0, (int)$_POST['id']]);
        header('Location: index.php?tab=menu&msg=menu_updated');
        exit;
    }

    if ($action === 'reset_menu') {
        $db->exec("DELETE FROM menu_config");
        // Will be re-seeded on next getMenuConfig() call
        getMenuConfig();
        header('Location: index.php?tab=menu&msg=menu_reset');
        exit;
    }

    // --- Approbations ---
    if ($action === 'approve') {
        $table = $_POST['table'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $allowed_tables = ['modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles', 'offres'];
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
        $allowed_tables = ['modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles', 'offres'];
        if (in_array($table, $allowed_tables) && $id > 0) {
            $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?tab=approbations&msg=rejected');
        exit;
    }

    // --- Contributions publiques (procédures proposées par des visiteurs non connectés) ---
    if ($action === 'approve_proposal') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM procedure_proposals WHERE id = ?");
        $stmt->execute([$id]);
        $proposal = $stmt->fetch();
        if ($proposal) {
            if ($proposal['type'] === 'create') {
                $stmt = $db->prepare("INSERT INTO procedures (user_id, nom, texte, mise_en_avant, lien_partage, approved, approved_by, contributor_prenom, contributor_nom, created_at) VALUES (NULL, ?, ?, 0, ?, 1, ?, ?, ?, NOW())");
                $stmt->execute([$proposal['nom'], $proposal['texte'], generateShareLink(), $adminUserId, $proposal['contributor_prenom'], $proposal['contributor_nom']]);
            } else {
                $stmt = $db->prepare("UPDATE procedures SET nom = ?, texte = ?, contributor_prenom = ?, contributor_nom = ? WHERE id = ?");
                $stmt->execute([$proposal['nom'], $proposal['texte'], $proposal['contributor_prenom'], $proposal['contributor_nom'], $proposal['procedure_id']]);
            }
            $stmt = $db->prepare("DELETE FROM procedure_proposals WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?tab=approbations&msg=approved');
        exit;
    }

    if ($action === 'reject_proposal') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM procedure_proposals WHERE id = ?");
        $stmt->execute([$id]);
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

    // --- Modification de données (admin gestion) ---
    if ($action === 'admin_edit') {
        $table = $_POST['table'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $allowed_tables = ['instances', 'demandes_rappel', 'offres', 'demandes_clients', 'suivi_production',
            'seances_phoning', 'credit_immobilier', 'calculateur_budget', 'formations', 'blocnotes',
            'courriers', 'modeles_courriers', 'procedures', 'codes_utiles', 'contacts_utiles'];
        $systemFields = ['id','user_id','created_at','updated_at','password','variables','approved_by'];
        if (in_array($table, $allowed_tables) && $id > 0) {
            $cols = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            $sets = [];
            $values = [];
            foreach ($cols as $col) {
                $field = $col['Field'];
                if (in_array($field, $systemFields)) continue;
                if (!array_key_exists('field_' . $field, $_POST)) continue;
                $val = $_POST['field_' . $field];
                if ($val === '' && ($col['Null'] === 'YES' || str_contains($col['Type'], 'date'))) {
                    $val = null;
                }
                $sets[] = "`$field` = ?";
                $values[] = $val;
            }
            if (!empty($sets)) {
                $values[] = $id;
                $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
        }
        header('Location: index.php?tab=donnees&module=' . urlencode($_POST['module'] ?? '') . '&msg=updated');
        exit;
    }

    if ($action === 'save_smtp') {
        $db->exec("CREATE TABLE IF NOT EXISTS smtp_config (
            id INT AUTO_INCREMENT PRIMARY KEY, smtp_host VARCHAR(255) NOT NULL DEFAULT '', smtp_port INT DEFAULT 587,
            smtp_user VARCHAR(255) DEFAULT '', smtp_pass VARCHAR(255) DEFAULT '', smtp_secure ENUM('tls','ssl','none') DEFAULT 'tls',
            mail_from VARCHAR(255) DEFAULT '', mail_from_name VARCHAR(255) DEFAULT 'Portail CE',
            rappel_enabled TINYINT(1) DEFAULT 1, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $existing = $db->query("SELECT id FROM smtp_config LIMIT 1")->fetch();
        $params = [
            trim($_POST['smtp_host'] ?? ''),
            (int)($_POST['smtp_port'] ?? 587),
            trim($_POST['smtp_user'] ?? ''),
            trim($_POST['smtp_pass'] ?? ''),
            $_POST['smtp_secure'] ?? 'tls',
            trim($_POST['mail_from'] ?? ''),
            trim($_POST['mail_from_name'] ?? 'Portail CE'),
            isset($_POST['rappel_enabled']) ? 1 : 0,
        ];
        if ($existing) {
            $db->prepare("UPDATE smtp_config SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_secure=?, mail_from=?, mail_from_name=?, rappel_enabled=? WHERE id=?")
                ->execute(array_merge($params, [$existing['id']]));
        } else {
            $db->prepare("INSERT INTO smtp_config (smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, mail_from, mail_from_name, rappel_enabled) VALUES (?,?,?,?,?,?,?,?)")
                ->execute($params);
        }
        header('Location: index.php?tab=smtp&msg=smtp_saved');
        exit;
    }

    if ($action === 'test_smtp') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (!empty($testEmail)) {
            $html = "<div style='font-family:Arial;padding:20px;'><h2 style='color:#dc0032;'>Test SMTP OK</h2><p>Si vous lisez cet email, la configuration SMTP du portail fonctionne correctement.</p><p style='color:#999;font-size:12px;'>Envoyé le " . date('d/m/Y H:i') . "</p></div>";
            $ok = sendSmtpMail($testEmail, '[Portail CE] Test SMTP', $html);
            header('Location: index.php?tab=smtp&msg=' . ($ok ? 'smtp_test_ok' : 'smtp_test_fail'));
        } else {
            header('Location: index.php?tab=smtp&msg=smtp_test_fail');
        }
        exit;
    }

    // --- Événements calendrier (admin) ---
    if ($action === 'admin_add_event') {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS evenements_perso (
                id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, created_by INT NOT NULL,
                titre VARCHAR(255) NOT NULL, description TEXT, date_debut DATE NOT NULL,
                date_fin DATE, heure_debut TIME, heure_fin TIME, couleur VARCHAR(7) DEFAULT '#8e44ad',
                is_admin_event TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        $titre   = trim($_POST['titre'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $debut   = $_POST['date_debut'] ?? '';
        $fin     = !empty($_POST['date_fin'])    ? $_POST['date_fin']    : null;
        $h_debut = !empty($_POST['heure_debut']) ? $_POST['heure_debut'] : null;
        $h_fin   = !empty($_POST['heure_fin'])   ? $_POST['heure_fin']   : null;
        $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '') ? $_POST['couleur'] : '#8e44ad';
        $targets = $_POST['user_ids'] ?? [];

        if (!empty($titre) && !empty($debut) && !empty($targets)) {
            $stmt = $db->prepare("INSERT INTO evenements_perso
                (user_id, created_by, titre, description, date_debut, date_fin, heure_debut, heure_fin, couleur, is_admin_event)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            foreach ($targets as $uid) {
                $stmt->execute([(int)$uid, $adminUserId, $titre, $desc, $debut, $fin, $h_debut, $h_fin, $couleur]);
            }
        }
        header('Location: index.php?tab=calendrier&msg=event_added');
        exit;
    }

    if ($action === 'admin_edit_event') {
        $id      = (int)($_POST['id'] ?? 0);
        $titre   = trim($_POST['titre'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $debut   = $_POST['date_debut'] ?? '';
        $fin     = !empty($_POST['date_fin'])    ? $_POST['date_fin']    : null;
        $h_debut = !empty($_POST['heure_debut']) ? $_POST['heure_debut'] : null;
        $h_fin   = !empty($_POST['heure_fin'])   ? $_POST['heure_fin']   : null;
        $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '') ? $_POST['couleur'] : '#8e44ad';

        if ($id > 0 && !empty($titre) && !empty($debut)) {
            $db->prepare("UPDATE evenements_perso SET titre=?, description=?, date_debut=?, date_fin=?,
                heure_debut=?, heure_fin=?, couleur=?, updated_at=NOW() WHERE id=? AND is_admin_event=1")
               ->execute([$titre, $desc, $debut, $fin, $h_debut, $h_fin, $couleur, $id]);
        }
        header('Location: index.php?tab=calendrier&msg=event_updated');
        exit;
    }

    if ($action === 'admin_delete_event') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare("DELETE FROM evenements_perso WHERE id = ? AND is_admin_event = 1")->execute([$id]);
        }
        header('Location: index.php?tab=calendrier&msg=event_deleted');
        exit;
    }
}

// Données
$users = $db->query("SELECT * FROM users ORDER BY nom, prenom")->fetchAll();
$categoriesLiens = getCategoriesLiens();
$liens = $db->query("SELECT l.*, c.nom AS categorie_nom FROM liens_externes l LEFT JOIN categories_liens c ON l.categorie_id = c.id ORDER BY l.ordre ASC, l.nom ASC")->fetchAll();
$menuItems = $db->query("SELECT * FROM menu_config ORDER BY ordre ASC")->fetchAll();
$smtpConfig = getSmtpConfig();

// Pending approvals
$pendingModeles = $db->query("SELECT m.*, u.nom AS author_nom, u.prenom AS author_prenom FROM modeles_courriers m LEFT JOIN users u ON m.user_id = u.id WHERE m.approved = 0 ORDER BY m.id DESC")->fetchAll();
$pendingProcedures = $db->query("SELECT p.*, u.nom AS author_nom, u.prenom AS author_prenom FROM procedures p LEFT JOIN users u ON p.user_id = u.id WHERE p.approved = 0 ORDER BY p.id DESC")->fetchAll();
$pendingCodes = $db->query("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom FROM codes_utiles c LEFT JOIN users u ON c.user_id = u.id WHERE c.approved = 0 ORDER BY c.id DESC")->fetchAll();
$pendingContacts = $db->query("SELECT c.*, u.nom AS author_nom, u.prenom AS author_prenom FROM contacts_utiles c LEFT JOIN users u ON c.user_id = u.id WHERE c.approved = 0 ORDER BY c.id DESC")->fetchAll();
$pendingOffres = $db->query("SELECT o.*, u.nom AS author_nom, u.prenom AS author_prenom FROM offres o LEFT JOIN users u ON o.user_id = u.id WHERE o.approved = 0 ORDER BY o.id DESC")->fetchAll();
$pendingProposals = $db->query("SELECT pr.*, p.nom AS target_nom FROM procedure_proposals pr LEFT JOIN procedures p ON pr.procedure_id = p.id ORDER BY pr.id DESC")->fetchAll();
$totalPending = count($pendingModeles) + count($pendingProcedures) + count($pendingCodes) + count($pendingContacts) + count($pendingOffres) + count($pendingProposals);

$activeTab = $_GET['tab'] ?? 'users';

// Événements calendrier admin
$adminEvents = [];
try {
    $adminEvents = $db->query(
        "SELECT e.*, u.nom AS user_nom, u.prenom AS user_prenom
         FROM evenements_perso e
         LEFT JOIN users u ON e.user_id = u.id
         WHERE e.is_admin_event = 1
         ORDER BY e.date_debut DESC"
    )->fetchAll();
} catch (Exception $e) {}
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
        'cat_added' => 'Catégorie ajoutée avec succès.',
        'cat_updated' => 'Catégorie modifiée avec succès.',
        'cat_deleted' => 'Catégorie supprimée.',
        'menu_saved' => 'Ordre du menu enregistré.',
        'menu_updated' => 'Élément du menu modifié.',
        'menu_reset' => 'Menu réinitialisé aux valeurs par défaut.',
        'approved' => 'Élément approuvé avec succès.',
        'rejected' => 'Élément refusé et supprimé.',
        'deleted' => 'Enregistrement supprimé.',
        'updated' => 'Enregistrement modifié avec succès.',
        'smtp_saved' => 'Configuration SMTP enregistrée.',
        'smtp_test_ok'   => 'Email de test envoyé avec succès !',
        'smtp_test_fail' => 'Échec de l\'envoi du mail de test. Vérifiez la configuration SMTP.',
        'event_added'    => 'Événement ajouté avec succès.',
        'event_updated'  => 'Événement modifié avec succès.',
        'event_deleted'  => 'Événement supprimé.',
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
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'menu' ? 'active' : '' ?>" href="?tab=menu"><i class="fas fa-bars"></i> Menu</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'smtp' ? 'active' : '' ?>" href="?tab=smtp"><i class="fas fa-envelope"></i> Emails</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'calendrier' ? 'active' : '' ?>" href="?tab=calendrier"><i class="fas fa-calendar-alt"></i> Calendrier</a>
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
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($pendingProposals) ?></div>
            <div class="stat-label">Contributions publiques</div>
        </div>
    </div>
</div>

<?php if ($totalPending === 0): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Aucun élément en attente d'approbation.</div>
<?php else: ?>

<?php if (!empty($pendingProposals)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-hands-helping"></i> Contributions publiques en attente (page publique des procédures)</h3></div>
    <table class="data-table">
        <thead><tr><th>Type</th><th>Nom proposé</th><th>Contributeur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingProposals as $pr): ?>
            <tr>
                <td>
                    <?php if ($pr['type'] === 'create'): ?>
                        <span class="badge bg-primary"><i class="fas fa-plus"></i> Nouvelle procédure</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-edit"></i> Modification de « <?= e($pr['target_nom'] ?? 'procédure supprimée') ?> »</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($pr['nom']) ?></strong></td>
                <td><?= e(trim($pr['contributor_prenom'] . ' ' . $pr['contributor_nom'])) ?></td>
                <td class="actions">
                    <button type="button" class="btn btn-sm btn-ce-outline" onclick="showProposalDetail(<?= $pr['id'] ?>)" title="Voir le contenu proposé"><i class="fas fa-eye"></i></button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve_proposal">
                        <input type="hidden" name="id" value="<?= $pr['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer cette proposition ?')">
                        <input type="hidden" name="action" value="reject_proposal">
                        <input type="hidden" name="id" value="<?= $pr['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal detail contribution publique -->
<div class="modal fade" id="proposalDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hands-helping"></i> Contenu proposé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="proposalDetailContent"></div>
        </div>
    </div>
</div>
<script>
const pendingProposalsData = <?= json_encode($pendingProposals) ?>;
function escapeProposalHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function showProposalDetail(id) {
    const pr = pendingProposalsData.find(p => p.id == id);
    if (!pr) return;
    document.getElementById('proposalDetailContent').innerHTML = `
        <p><strong>Contributeur :</strong> ${escapeProposalHtml((pr.contributor_prenom || '') + ' ' + (pr.contributor_nom || ''))}</p>
        <p><strong>Nom proposé :</strong> ${escapeProposalHtml(pr.nom)}</p>
        <div class="p-3 bg-light rounded" style="white-space:pre-wrap;">${escapeProposalHtml(pr.texte)}</div>`;
    new bootstrap.Modal(document.getElementById('proposalDetailModal')).show();
}
</script>
<?php endif; ?>

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

<?php if (!empty($pendingOffres)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-gift"></i> Offres en attente</h3></div>
    <table class="data-table">
        <thead><tr><th>Nom</th><th>Date début</th><th>Date fin</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pendingOffres as $o): ?>
            <tr>
                <td><strong><?= e($o['nom']) ?></strong></td>
                <td><?= formatDate($o['date_debut']) ?></td>
                <td><?= formatDate($o['date_fin']) ?></td>
                <td><?= e($o['author_prenom'] . ' ' . $o['author_nom']) ?></td>
                <td class="actions">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="table" value="offres">
                        <input type="hidden" name="id" value="<?= $o['id'] ?>">
                        <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Refuser et supprimer ?')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="table" value="offres">
                        <input type="hidden" name="id" value="<?= $o['id'] ?>">
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
    'offres' => ['label' => 'Offres du moment', 'icon' => 'gift', 'cols' => ['nom','date_debut','date_fin'], 'display' => ['Nom','Début','Fin']],
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
                    <button class="btn btn-sm btn-ce-outline" onclick="showRecordDetail('<?= $selectedModule ?>', <?= $row['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editRecord('<?= $selectedModule ?>', <?= $row['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
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

<!-- Modal Détail Enregistrement -->
<div class="modal fade modal-fullscreen-custom" id="recordDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de l'enregistrement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recordDetailContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Enregistrement -->
<div class="modal fade modal-fullscreen-custom" id="recordEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'enregistrement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recordEditContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchData', 'tableData');

const fieldLabels = {
    id: 'ID', user_id: 'ID utilisateur', user_nom: 'Nom utilisateur', user_prenom: 'Prénom utilisateur',
    numero_personne: 'N° personne', type_client: 'Type client', type_occupation: 'Occupation',
    type_residence: 'Résidence', type_bien: 'Type bien', adresse_bien: 'Adresse bien',
    montant_acquisition: 'Montant acquisition', frais_notaire: 'Frais notaire', frais_agence: 'Frais agence',
    frais_courtage: 'Frais courtage', frais_dossier: 'Frais dossier', taux_emprunt: 'Taux emprunt',
    duree_emprunt: 'Durée emprunt', apport: 'Apport', revenus_mensuels: 'Revenus mensuels',
    charges_fixes: 'Charges fixes', credits_en_cours: 'Crédits en cours', epargne: 'Épargne',
    nom: 'Nom', prenom: 'Prénom', titre: 'Titre', texte: 'Texte', details: 'Détails',
    date_debut: 'Date début', date_fin: 'Date fin', date_ajout: 'Date ajout',
    date_courrier: 'Date courrier', objet: 'Objet', corps: 'Corps',
    nom_modele: 'Nom modèle', nom_prenom_dest: 'Destinataire',
    telephone: 'Téléphone', mail: 'Mail', service: 'Service', a_contacter_pour: 'À contacter pour',
    code: 'Code', fonction: 'Fonction', raison: 'Raison', montant: 'Montant',
    categorie: 'Catégorie', nb_appels: 'Nb appels', date_seance: 'Date séance',
    mise_en_avant: 'Mise en avant', lien_partage: 'Lien partage',
    approved: 'Approuvé', approved_by: 'Approuvé par', workflow_status: 'Statut workflow',
    notes: 'Notes', created_at: 'Créé le', updated_at: 'Modifié le',
    loyer: 'Loyer', cegc: 'CEGC', ade: 'ADE', travaux: 'Travaux',
    salaire: 'Salaire', salaire_conjoint: 'Salaire conjoint',
    proprietaire_logement: 'Propriétaire logement', avec_travaux: 'Avec travaux',
    nom_dest: 'Nom dest.', prenom_dest: 'Prénom dest.', civilite_dest: 'Civilité dest.',
    adresse_dest: 'Adresse dest.', cp_ville_dest: 'CP/Ville dest.', lieu: 'Lieu',
    suivi_offre_signee: 'Offre signée', suivi_offre_signee_date: 'Date signature offre',
    contenu: 'Contenu', date_seance: 'Date séance',
};

const hiddenFields = ['password', 'variables'];
const boolFields = ['approved', 'proprietaire_logement', 'avec_travaux', 'mise_en_avant',
    'doc_ji','doc_jd','doc_ir','doc_contrat_travail','doc_bulletins_salaire',
    'doc_justif_propriete','doc_releves_externes','doc_epargnes_externes',
    'eco_ademe_emprunteur','eco_ademe_entreprises','eco_dpe','eco_audit','eco_devis_travaux',
    'suivi_synthese_envoyee','suivi_controle_conformite','suivi_edition_offres',
    'suivi_envoi_signature','suivi_offre_signee','is_admin'];
const moneyFields = ['montant','montant_acquisition','frais_notaire','frais_agence','frais_courtage',
    'frais_dossier','cegc','ade','travaux','apport','revenus_mensuels','charges_fixes',
    'loyer','credits_en_cours','epargne','dont_ecoptz_ptz','salaire','salaire_conjoint'];

function showRecordDetail(table, id) {
    const content = document.getElementById('recordDetailContent');
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...</div>';
    new bootstrap.Modal(document.getElementById('recordDetailModal')).show();

    fetch(`index.php?ajax=detail&table=${encodeURIComponent(table)}&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = '<div class="alert alert-danger">Enregistrement non trouvé.</div>';
                return;
            }
            let html = '<table class="table table-sm table-striped"><tbody>';
            // Show user first if available
            if (data.user_prenom || data.user_nom) {
                html += `<tr><td style="width:35%"><strong>Utilisateur</strong></td><td><span class="badge bg-secondary">${esc(data.user_prenom || '')} ${esc(data.user_nom || '')}</span></td></tr>`;
            }
            for (const [key, val] of Object.entries(data)) {
                if (key === 'user_nom' || key === 'user_prenom' || hiddenFields.includes(key)) continue;
                if (val === null || val === '') continue;
                const label = fieldLabels[key] || key.replace(/_/g, ' ');
                let display;
                if (boolFields.includes(key)) {
                    display = val == 1 ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>';
                } else if (moneyFields.includes(key)) {
                    display = parseFloat(val).toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' €';
                } else if (key === 'corps' || key === 'texte' || key === 'details' || key === 'contenu' || key === 'a_contacter_pour' || key === 'notes') {
                    const strVal = String(val);
                    display = '<div class="p-2 bg-light rounded" style="max-height:300px;overflow-y:auto;white-space:pre-wrap;">' + (key === 'corps' ? val : esc(strVal)) + '</div>';
                } else {
                    display = esc(String(val));
                }
                html += `<tr><td style="width:35%"><strong>${esc(label)}</strong></td><td>${display}</td></tr>`;
            }
            html += '</tbody></table>';
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement.</div>';
        });
}

const systemFields = ['id','user_id','created_at','updated_at','password','variables','approved_by','user_nom','user_prenom'];
const dateFields = ['date_debut','date_fin','date_ajout','date_courrier','date_rdv','date_seance','date_echeance','date_envoi','suivi_offre_signee_date'];
const enumMap = {
    categorie: ['Banca','Epargne','Placement','Credit','Assurance'],
    statut: ['a_faire','fait'],
    type_client: ['Particulier','Pro','Asso'],
    type_occupation: ['Proprietaire','Locatif'],
    type_residence: ['RP','RS'],
    type_bien: ['Appartement','Maison','Copro'],
    lieu: ['presentiel','distanciel'],
    ptz_demande: ['Initiale','Complementaire'],
    ptz_type: ['Perf globale','Bouquets'],
};

let schemaCache = {};

function editRecord(table, id) {
    const content = document.getElementById('recordEditContent');
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...</div>';
    new bootstrap.Modal(document.getElementById('recordEditModal')).show();

    // Fetch schema and data in parallel
    const fetchSchema = schemaCache[table]
        ? Promise.resolve(schemaCache[table])
        : fetch(`index.php?ajax=schema&table=${encodeURIComponent(table)}`).then(r => r.json()).then(s => { schemaCache[table] = s; return s; });
    const fetchData = fetch(`index.php?ajax=detail&table=${encodeURIComponent(table)}&id=${id}`).then(r => r.json());

    Promise.all([fetchSchema, fetchData]).then(([schema, data]) => {
        if (data.error) {
            content.innerHTML = '<div class="alert alert-danger">Enregistrement non trouvé.</div>';
            return;
        }
        let html = `<form method="POST">
            <input type="hidden" name="action" value="admin_edit">
            <input type="hidden" name="table" value="${esc(table)}">
            <input type="hidden" name="module" value="${esc(table)}">
            <input type="hidden" name="id" value="${id}">`;

        if (data.user_prenom || data.user_nom) {
            html += `<div class="mb-3"><label class="form-label fw-bold">Utilisateur</label>
                <div><span class="badge bg-secondary">${esc(data.user_prenom || '')} ${esc(data.user_nom || '')}</span></div></div>`;
        }

        html += '<div class="row g-3">';
        for (const col of schema) {
            const field = col.Field;
            if (systemFields.includes(field)) continue;
            const val = data[field] ?? '';
            const label = fieldLabels[field] || field.replace(/_/g, ' ');
            const colType = col.Type.toLowerCase();
            const inputName = 'field_' + field;

            // Determine input type
            if (boolFields.includes(field) || colType === 'tinyint(1)') {
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <select name="${inputName}" class="form-select">
                        <option value="0" ${val == 0 ? 'selected' : ''}>Non</option>
                        <option value="1" ${val == 1 ? 'selected' : ''}>Oui</option>
                    </select></div>`;
            } else if (enumMap[field]) {
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <select name="${inputName}" class="form-select">
                        <option value="">-- Aucun --</option>
                        ${enumMap[field].map(o => `<option value="${esc(o)}" ${val === o ? 'selected' : ''}>${esc(o)}</option>`).join('')}
                    </select></div>`;
            } else if (colType.includes('enum')) {
                const opts = colType.match(/enum\((.+)\)/);
                if (opts) {
                    const values = opts[1].split(',').map(v => v.replace(/'/g, '').trim());
                    html += `<div class="col-md-6">
                        <label class="form-label">${esc(label)}</label>
                        <select name="${inputName}" class="form-select">
                            <option value="">-- Aucun --</option>
                            ${values.map(o => `<option value="${esc(o)}" ${val === o ? 'selected' : ''}>${esc(o)}</option>`).join('')}
                        </select></div>`;
                }
            } else if (dateFields.includes(field) || colType.includes('date')) {
                const dateVal = val ? (colType.includes('datetime') ? val.substring(0, 16) : val.substring(0, 10)) : '';
                const inputType = colType.includes('datetime') ? 'datetime-local' : 'date';
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <input type="${inputType}" name="${inputName}" class="form-control" value="${esc(dateVal)}">
                    </div>`;
            } else if (colType.includes('text') || colType.includes('longtext') || field === 'corps' || field === 'texte' || field === 'details' || field === 'contenu') {
                html += `<div class="col-12">
                    <label class="form-label">${esc(label)}</label>
                    <textarea name="${inputName}" class="form-control" rows="4">${esc(String(val))}</textarea>
                    </div>`;
            } else if (moneyFields.includes(field) || colType.includes('decimal')) {
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="${inputName}" class="form-control" value="${val || 0}">
                        <span class="input-group-text">&euro;</span>
                    </div></div>`;
            } else if (colType.includes('int')) {
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <input type="number" name="${inputName}" class="form-control" value="${esc(String(val))}">
                    </div>`;
            } else {
                html += `<div class="col-md-6">
                    <label class="form-label">${esc(label)}</label>
                    <input type="text" name="${inputName}" class="form-control" value="${esc(String(val))}">
                    </div>`;
            }
        }
        html += `</div>
            <div class="mt-4">
                <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Annuler</button>
            </div>
        </form>`;
        content.innerHTML = html;
    }).catch(() => {
        content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement.</div>';
    });
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

<?php else: ?>
<div class="alert alert-info"><i class="fas fa-hand-pointer"></i> Sélectionnez un module ci-dessus pour voir les données de tous les utilisateurs.</div>
<?php endif; ?>

<?php elseif ($activeTab === 'liens'): ?>
<!-- =============== LIENS EXTERNES =============== -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($liens) ?></div>
            <div class="stat-label">Liens configurés</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($categoriesLiens) ?></div>
            <div class="stat-label">Catégories</div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addLinkModal"><i class="fas fa-plus"></i> Nouveau lien</button>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce-outline" data-bs-toggle="modal" data-bs-target="#addCatModal"><i class="fas fa-folder-plus"></i> Nouvelle catégorie</button>
    </div>
</div>

<!-- Catégories -->
<?php if (!empty($categoriesLiens)): ?>
<div class="data-table-container mb-4">
    <div class="data-table-header">
        <h3>Catégories de liens</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Ordre</th>
                <th>Nom</th>
                <th>Nb liens</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categoriesLiens as $cat):
            $nbLiensCat = 0;
            foreach ($liens as $l) { if (($l['categorie_id'] ?? null) == $cat['id']) $nbLiensCat++; }
        ?>
            <tr>
                <td><?= $cat['ordre'] ?></td>
                <td><?= e($cat['nom']) ?></td>
                <td><?= $nbLiensCat ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="editCategorie(<?= $cat['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ? Les liens seront détachés mais pas supprimés.')">
                        <input type="hidden" name="action" value="delete_categorie_lien">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Liens -->
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
                <th>Catégorie</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($liens as $l): ?>
            <tr>
                <td><?= $l['ordre'] ?></td>
                <td><?= e($l['nom']) ?></td>
                <td><a href="<?= e($l['url']) ?>" target="_blank"><?= e(excerpt($l['url'], 50)) ?></a></td>
                <td><?= e($l['categorie_nom'] ?? '-') ?></td>
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

<!-- Modal Ajout Catégorie -->
<div class="modal fade modal-fullscreen-custom" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-folder-plus"></i> Nouvelle catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_categorie_lien">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="0"></div>
                        <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Ajouter</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edition Catégorie -->
<div class="modal fade modal-fullscreen-custom" id="editCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editCatContent"></div>
        </div>
    </div>
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
                        <div class="col-md-5"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-md-5"><label class="form-label">URL *</label><input type="url" name="url" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-select">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($categoriesLiens as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="0"></div>
                        <div class="col-md-3"><label class="form-label">Ouverture</label>
                            <select name="mode_ouverture" class="form-select">
                                <option value="onglet">Nouvel onglet</option>
                                <option value="fenetre">Fenêtre flottante</option>
                            </select>
                        </div>
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
const categoriesData = <?= json_encode($categoriesLiens) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function editLink(id) {
    const l = liensData.find(x => x.id == id);
    if (!l) return;
    let catOptions = '<option value="">-- Aucune --</option>';
    categoriesData.forEach(c => {
        catOptions += `<option value="${c.id}" ${l.categorie_id == c.id ? 'selected' : ''}>${escapeHtml(c.nom)}</option>`;
    });
    document.getElementById('editLinkContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_link">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-5"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="${escapeHtml(l.nom)}" required></div>
                <div class="col-md-5"><label class="form-label">URL *</label><input type="url" name="url" class="form-control" value="${escapeHtml(l.url)}" required></div>
                <div class="col-md-4"><label class="form-label">Catégorie</label><select name="categorie_id" class="form-select">${catOptions}</select></div>
                <div class="col-md-2"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="${l.ordre}"></div>
                <div class="col-md-3"><label class="form-label">Ouverture</label>
                    <select name="mode_ouverture" class="form-select">
                        <option value="onglet" ${(l.mode_ouverture||'onglet')==='onglet'?'selected':''}>Nouvel onglet</option>
                        <option value="fenetre" ${l.mode_ouverture==='fenetre'?'selected':''}>Fenêtre flottante</option>
                    </select>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editLinkModal')).show();
}

function editCategorie(id) {
    const c = categoriesData.find(x => x.id == id);
    if (!c) return;
    document.getElementById('editCatContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_categorie_lien">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="${escapeHtml(c.nom)}" required></div>
                <div class="col-md-3"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="${c.ordre}"></div>
                <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editCatModal')).show();
}
</script>

<?php elseif ($activeTab === 'menu'): ?>
<!-- =============== ORGANISATION DU MENU =============== -->
<div class="row g-3 mb-4">
    <div class="col-md-4 d-flex align-items-center">
        <h4 class="mb-0"><i class="fas fa-bars"></i> Organisation du menu</h4>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <form method="POST" onsubmit="return confirm('Réinitialiser le menu aux valeurs par défaut ?')">
            <input type="hidden" name="action" value="reset_menu">
            <button class="btn btn-outline-warning"><i class="fas fa-undo"></i> Réinitialiser</button>
        </form>
    </div>
</div>

<p class="text-muted mb-3">Glissez-déposez les sections et les éléments pour réorganiser le menu. Cliquez sur un élément pour le modifier.</p>

<?php
$menuSections = array_filter($menuItems, fn($i) => $i['parent_key'] === null);
$menuChildren = [];
foreach ($menuItems as $i) {
    if ($i['parent_key'] !== null) {
        $menuChildren[$i['parent_key']][] = $i;
    }
}
?>

<form method="POST" id="menuOrderForm">
    <input type="hidden" name="action" value="save_menu_order">
    <input type="hidden" name="menu_order" id="menuOrderInput">

    <div id="menuSortable">
    <?php foreach ($menuSections as $section): ?>
        <div class="card mb-3 menu-section" data-key="<?= e($section['item_key']) ?>">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: grab; background: #f0f4f8;">
                <span>
                    <i class="fas fa-grip-vertical text-muted me-2"></i>
                    <i class="fas <?= e($section['icon']) ?> me-1"></i>
                    <strong><?= e($section['label']) ?></strong>
                    <?php if (!$section['visible']): ?><span class="badge bg-secondary ms-2">Masqué</span><?php endif; ?>
                </span>
                <button type="button" class="btn btn-sm btn-ce-outline" onclick="editMenuItem(<?= $section['id'] ?>)"><i class="fas fa-edit"></i></button>
            </div>
            <div class="card-body p-2">
                <ul class="list-group menu-items-sortable" data-parent="<?= e($section['item_key']) ?>">
                <?php foreach ($menuChildren[$section['item_key']] ?? [] as $child): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center menu-item" data-key="<?= e($child['item_key']) ?>" style="cursor: grab;">
                        <span>
                            <i class="fas fa-grip-vertical text-muted me-2"></i>
                            <i class="fas <?= e($child['icon']) ?> me-1"></i>
                            <?= e($child['label']) ?>
                            <?php if (!$child['visible']): ?><span class="badge bg-secondary ms-1">Masqué</span><?php endif; ?>
                        </span>
                        <button type="button" class="btn btn-sm btn-ce-outline" onclick="editMenuItem(<?= $child['id'] ?>)"><i class="fas fa-edit"></i></button>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-ce btn-lg" onclick="prepareMenuOrder()"><i class="fas fa-save"></i> Enregistrer l'ordre</button>
</form>

<!-- Modal Edition Item Menu -->
<div class="modal fade modal-fullscreen-custom" id="editMenuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'élément</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editMenuItemContent"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const menuItemsData = <?= json_encode($menuItems) ?>;

// Sortable pour les sections
new Sortable(document.getElementById('menuSortable'), {
    animation: 150,
    handle: '.card-header',
    ghostClass: 'bg-light'
});

// Sortable pour les items dans chaque section
document.querySelectorAll('.menu-items-sortable').forEach(el => {
    new Sortable(el, {
        animation: 150,
        group: 'menu-items',
        ghostClass: 'bg-light'
    });
});

function prepareMenuOrder() {
    const order = [];
    let sectionOrdre = 1;
    document.querySelectorAll('.menu-section').forEach(section => {
        const sectionKey = section.dataset.key;
        order.push({ item_key: sectionKey, parent_key: null, ordre: sectionOrdre++ });

        let itemOrdre = 1;
        const parentKey = section.querySelector('.menu-items-sortable').dataset.parent;
        section.querySelectorAll('.menu-item').forEach(item => {
            order.push({ item_key: item.dataset.key, parent_key: sectionKey, ordre: itemOrdre++ });
        });
    });
    document.getElementById('menuOrderInput').value = JSON.stringify(order);
}

function editMenuItem(id) {
    const item = menuItemsData.find(x => x.id == id);
    if (!item) return;
    const escHtml = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
    document.getElementById('editMenuItemContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit_menu_item">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Libellé *</label><input type="text" name="label" class="form-control" value="${escHtml(item.label)}" required></div>
                <div class="col-md-4"><label class="form-label">Icône Font Awesome</label><input type="text" name="icon" class="form-control" value="${escHtml(item.icon)}" placeholder="fa-home"></div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="visible" class="form-check-input" id="editVisible" ${item.visible == 1 ? 'checked' : ''}>
                        <label class="form-check-label" for="editVisible">Visible</label>
                    </div>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editMenuItemModal')).show();
}
</script>

<?php elseif ($activeTab === 'smtp'): ?>
<!-- =============== CONFIGURATION SMTP =============== -->
<div class="row g-4">
    <div class="col-md-7">
        <div class="data-table-container">
            <div class="data-table-header">
                <h3><i class="fas fa-server"></i> Configuration SMTP</h3>
            </div>
            <div class="p-3">
                <form method="post">
                    <input type="hidden" name="action" value="save_smtp">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Serveur SMTP</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= e($smtpConfig['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?= (int)($smtpConfig['smtp_port'] ?? 587) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Identifiant SMTP</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?= e($smtpConfig['smtp_user'] ?? '') ?>" placeholder="user@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe SMTP</label>
                            <input type="password" name="smtp_pass" class="form-control" value="<?= e($smtpConfig['smtp_pass'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sécurité</label>
                            <select name="smtp_secure" class="form-select">
                                <option value="tls" <?= ($smtpConfig['smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (recommandé)</option>
                                <option value="ssl" <?= ($smtpConfig['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($smtpConfig['smtp_secure'] ?? '') === 'none' ? 'selected' : '' ?>>Aucune</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email expéditeur</label>
                            <input type="email" name="mail_from" class="form-control" value="<?= e($smtpConfig['mail_from'] ?? '') ?>" placeholder="noreply@example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom expéditeur</label>
                            <input type="text" name="mail_from_name" class="form-control" value="<?= e($smtpConfig['mail_from_name'] ?? 'Portail CE') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="rappel_enabled" class="form-check-input" id="rappelEnabled" <?= ($smtpConfig['rappel_enabled'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rappelEnabled">
                                    <strong>Activer les emails de rappel automatiques</strong>
                                    <br><small class="text-muted">Un email quotidien sera envoyé à chaque utilisateur ayant des traitements en retard (>7 jours)</small>
                                </label>
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

    <div class="col-md-5">
        <!-- Test SMTP -->
        <div class="data-table-container mb-4">
            <div class="data-table-header">
                <h3><i class="fas fa-paper-plane"></i> Tester l'envoi</h3>
            </div>
            <div class="p-3">
                <?php if (empty($smtpConfig['smtp_host'])): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Configurez d'abord le serveur SMTP avant de tester.
                    </div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="test_smtp">
                        <div class="mb-3">
                            <label class="form-label">Adresse email de test</label>
                            <input type="email" name="test_email" class="form-control" placeholder="votre@email.com" required>
                        </div>
                        <button type="submit" class="btn btn-ce-outline"><i class="fas fa-paper-plane"></i> Envoyer un test</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="data-table-container">
            <div class="data-table-header">
                <h3><i class="fas fa-info-circle"></i> Fonctionnement</h3>
            </div>
            <div class="p-3">
                <p><strong>Rappels automatiques :</strong></p>
                <ul>
                    <li>Un email est envoyé <strong>1 fois par jour maximum</strong> à chaque connexion</li>
                    <li>Uniquement si l'utilisateur a des <strong>instances, demandes clients ou rappels en retard</strong> (&gt; 7 jours)</li>
                    <li>L'email est envoyé à l'adresse <strong>email professionnel</strong> du profil utilisateur</li>
                    <li>Les envois sont enregistrés dans la table <code>notifications_log</code></li>
                </ul>
                <p class="mb-0"><strong>Serveurs SMTP courants :</strong></p>
                <ul class="mb-0">
                    <li>Gmail : <code>smtp.gmail.com</code> port 587 (TLS)</li>
                    <li>Outlook : <code>smtp.office365.com</code> port 587 (TLS)</li>
                    <li>OVH : <code>ssl0.ovh.net</code> port 465 (SSL)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($activeTab === 'calendrier'): ?>
<!-- =============== CALENDRIER ADMIN =============== -->
<div class="row g-4">
    <!-- Formulaire création -->
    <div class="col-lg-5">
        <div class="data-table-container">
            <div class="data-table-header">
                <h3><i class="fas fa-plus-circle"></i> Créer un événement</h3>
            </div>
            <div class="p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="admin_add_event">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" maxlength="255" required placeholder="Ex : Réunion mensuelle">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Date début <span class="text-danger">*</span></label>
                            <input type="date" name="date_debut" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Date fin</label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Heure début</label>
                            <input type="time" name="heure_debut" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Heure fin</label>
                            <input type="time" name="heure_fin" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Détails optionnels..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Couleur</label>
                        <input type="color" name="couleur" value="#8e44ad" class="form-control form-control-color">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Utilisateurs concernés <span class="text-danger">*</span></label>
                        <div style="max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;padding:10px;">
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllUsers(true)">Tout sélectionner</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="toggleAllUsers(false)">Tout désélectionner</button>
                            </div>
                            <?php foreach ($users as $u): ?>
                            <div class="form-check">
                                <input class="form-check-input user-cb" type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" id="u<?= $u['id'] ?>">
                                <label class="form-check-label" for="u<?= $u['id'] ?>">
                                    <?= e($u['nom']).' '.e($u['prenom']) ?>
                                    <?php if ($u['is_admin']): ?><span class="badge bg-primary" style="font-size:10px">Admin</span><?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">L'événement sera visible dans le calendrier de chaque utilisateur sélectionné et ne pourra pas être modifié par eux.</small>
                    </div>
                    <button type="submit" class="btn btn-ce w-100"><i class="fas fa-calendar-plus"></i> Créer l'événement</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste des événements admin existants -->
    <div class="col-lg-7">
        <div class="data-table-container">
            <div class="data-table-header">
                <h3><i class="fas fa-list"></i> Événements créés par les admins</h3>
            </div>
            <?php if (empty($adminEvents)): ?>
                <div class="p-4 text-center text-muted">Aucun événement admin pour l'instant.</div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Utilisateur</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($adminEvents as $ev): ?>
                <tr>
                    <td>
                        <span class="d-flex align-items-center gap-2">
                            <span style="width:12px;height:12px;border-radius:50%;background:<?= e($ev['couleur']) ?>;display:inline-block;flex-shrink:0"></span>
                            <?= e($ev['titre']) ?>
                            <?php if ($ev['is_admin_event']): ?><i class="fas fa-lock text-secondary ms-1" title="Non modifiable par l'utilisateur"></i><?php endif; ?>
                        </span>
                    </td>
                    <td><?= e($ev['prenom'] ?? '').' '.e($ev['nom'] ?? '') ?></td>
                    <td><?= formatDate($ev['date_debut']) ?></td>
                    <td><?= $ev['date_fin'] ? formatDate($ev['date_fin']) : '—' ?></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-ce-outline" title="Modifier"
                            onclick="openAdminEditModal(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet événement ?')">
                            <input type="hidden" name="action" value="admin_delete_event">
                            <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modale modification événement admin -->
<div class="modal fade" id="modalAdminEvent" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier l'événement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="admin_edit_event">
        <input type="hidden" name="id" id="adminEvtId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
            <input type="text" name="titre" id="adminEvtTitre" class="form-control" required maxlength="255">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Date début <span class="text-danger">*</span></label>
              <input type="date" name="date_debut" id="adminEvtDebut" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Date fin</label>
              <input type="date" name="date_fin" id="adminEvtFin" class="form-control">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Heure début</label>
              <input type="time" name="heure_debut" id="adminEvtHDebut" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Heure fin</label>
              <input type="time" name="heure_fin" id="adminEvtHFin" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="adminEvtDesc" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Couleur</label>
            <input type="color" name="couleur" id="adminEvtCouleur" class="form-control form-control-color" value="#8e44ad">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleAllUsers(state) {
    document.querySelectorAll('.user-cb').forEach(cb => cb.checked = state);
}
function openAdminEditModal(ev) {
    document.getElementById('adminEvtId').value     = ev.id;
    document.getElementById('adminEvtTitre').value  = ev.titre;
    document.getElementById('adminEvtDesc').value   = ev.description || '';
    document.getElementById('adminEvtDebut').value  = ev.date_debut;
    document.getElementById('adminEvtFin').value    = ev.date_fin || '';
    document.getElementById('adminEvtHDebut').value = ev.heure_debut ? ev.heure_debut.slice(0,5) : '';
    document.getElementById('adminEvtHFin').value   = ev.heure_fin  ? ev.heure_fin.slice(0,5)  : '';
    document.getElementById('adminEvtCouleur').value= ev.couleur || '#8e44ad';
    new bootstrap.Modal(document.getElementById('modalAdminEvent')).show();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
