<?php
// ─── Init ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$db = getDB();

// ─── Tables ──────────────────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS `stock_credentials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `login` VARCHAR(100) NOT NULL DEFAULT 'stock',
    `password_hash` VARCHAR(255) NOT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `stock_types_unite` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(100) NOT NULL,
    `ordre` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `stock_produits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL,
    `type_unite_id` INT NOT NULL DEFAULT 1,
    `quantite_stock` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `seuil_alerte` DECIMAL(10,2) DEFAULT NULL,
    `alerte_active` TINYINT(1) DEFAULT 0,
    `plateforme_commande` VARCHAR(255) DEFAULT NULL,
    `actif` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `stock_commandes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT NOT NULL,
    `quantite` DECIMAL(10,2) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `statut` ENUM('en_cours','recu','annule') DEFAULT 'en_cours',
    `date_commande` DATE NOT NULL,
    `date_reception` DATE DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `stock_mouvements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT NOT NULL,
    `type` ENUM('retrait','ajout','inventaire') NOT NULL,
    `quantite` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `auteur` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `stock_alert_users` (
    `user_id` INT NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Demandes de commande émises depuis l'accès standalone
$db->exec("CREATE TABLE IF NOT EXISTS `stock_demandes_commande` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT NOT NULL,
    `quantite_souhaitee` DECIMAL(10,2) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `statut` ENUM('nouvelle','traitee') DEFAULT 'nouvelle',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migration : ajout des colonnes de conditionnement si absentes
try { $db->exec("ALTER TABLE stock_produits ADD COLUMN unite_commande VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE stock_produits ADD COLUMN facteur_conditionnement DECIMAL(10,4) DEFAULT NULL COMMENT '1 unité commande = N unités stock'"); } catch (Exception $e) {}

// Seed credentials par défaut (login: stock / mdp: stock)
if ((int)$db->query("SELECT COUNT(*) FROM stock_credentials")->fetchColumn() === 0) {
    $db->prepare("INSERT INTO stock_credentials (login, password_hash) VALUES (?, ?)")
       ->execute(['stock', password_hash('stock', PASSWORD_DEFAULT)]);
}

// Seed types d'unité par défaut
if ((int)$db->query("SELECT COUNT(*) FROM stock_types_unite")->fetchColumn() === 0) {
    $stmt = $db->prepare("INSERT INTO stock_types_unite (libelle, ordre) VALUES (?, ?)");
    foreach ([['Unité',1],['Ramette',2],['Boîte',3],['Pack',4],['Carton',5],['Lot',6],['Rouleau',7]] as $u) {
        $stmt->execute($u);
    }
}

// ─── Auth ────────────────────────────────────────────────────────────────────
$isPortalUser  = isset($_SESSION['user_id']);
$isPortalAdmin = $isPortalUser && !empty($_SESSION['is_admin']);
$isStockUser   = !empty($_SESSION['stock_auth']); // accès via identifiants partagés
// Accès restreint = connecté uniquement via identifiants partagés (pas via portail)
$isRestreint   = $isStockUser && !$isPortalUser;

$loginError = '';

// Logout standalone
if (!$isPortalUser && isset($_GET['logout'])) {
    unset($_SESSION['stock_auth']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Login standalone
if (!$isPortalUser && !$isStockUser) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_login'])) {
        $cred = $db->query("SELECT * FROM stock_credentials LIMIT 1")->fetch();
        if ($cred
            && trim($_POST['login'] ?? '') === $cred['login']
            && password_verify($_POST['password'] ?? '', $cred['password_hash'])
        ) {
            $_SESSION['stock_auth'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        $loginError = 'Identifiants incorrects.';
    }

    if (!$isStockUser) {
        // ── Page de connexion standalone ─────────────────────────────────
        $projectRootFs  = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
        $scriptFs       = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']));
        $relativeScript = str_replace($projectRootFs, '', $scriptFs);
        $B              = rtrim(str_replace($relativeScript, '', $_SERVER['SCRIPT_NAME']), '/');
        ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des fournitures — Connexion</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= $B ?>/assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="background:#f5f5f5;min-height:100vh;display:flex;align-items:center;justify-content:center;">
  <div style="width:100%;max-width:380px;padding:16px;">
    <div class="card shadow-sm border-0" style="border-radius:12px;overflow:hidden;">
      <div style="background:#CF0A2C;padding:28px 32px 20px;">
        <img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png"
             alt="Caisse d'Épargne" style="max-width:160px;display:block;margin-bottom:14px;">
        <h4 style="color:#fff;margin:0;font-size:1.1rem;font-weight:600;">
          <i class="fas fa-boxes"></i> Gestion des fournitures
        </h4>
      </div>
      <div class="card-body p-4">
        <?php if ($loginError): ?>
          <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="stock_login" value="1">
          <div class="mb-3">
            <label class="form-label fw-semibold">Identifiant</label>
            <input type="text" name="login" class="form-control" required autofocus
                   value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Mot de passe</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-ce w-100">
            <i class="fas fa-sign-in-alt"></i> Connexion
          </button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
        <?php
        exit;
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
if ($isPortalUser) {
    require_once __DIR__ . '/../../includes/functions.php';
}
if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
}

$auteur = $isPortalUser
    ? (trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?: 'Utilisateur')
    : 'Accès stock';

function stockFmt($n) {
    $n = (float)$n;
    return $n == (int)$n ? (int)$n : number_format($n, 2, ',', ' ');
}

// ─── POST Handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Actions autorisées pour TOUS (portail + standalone) ──────────────────

    // Retrait de stock
    if ($action === 'retrait') {
        $id  = (int)$_POST['produit_id'];
        $qte = max(0.01, (float)$_POST['quantite']);
        $db->prepare("UPDATE stock_produits SET quantite_stock = GREATEST(0, quantite_stock - ?) WHERE id = ?")
           ->execute([$qte, $id]);
        $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'retrait', ?, ?, ?)")
           ->execute([$id, $qte, trim($_POST['notes'] ?? '') ?: null, $auteur]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

    // Ajout de stock (manuel)
    if ($action === 'ajout') {
        $id  = (int)$_POST['produit_id'];
        $qte = max(0.01, (float)$_POST['quantite']);
        $db->prepare("UPDATE stock_produits SET quantite_stock = quantite_stock + ? WHERE id = ?")
           ->execute([$qte, $id]);
        $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'ajout', ?, ?, ?)")
           ->execute([$id, $qte, trim($_POST['notes'] ?? '') ?: null, $auteur]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

    // Réception commande (portail + standalone)
    if ($action === 'recu_commande') {
        $id       = (int)$_POST['id'];
        $qteOrder = (float)$_POST['quantite_recue']; // saisi en unité de commande
        $stmt = $db->prepare("
            SELECT c.*, p.facteur_conditionnement, p.unite_commande
            FROM stock_commandes c
            JOIN stock_produits p ON p.id = c.produit_id
            WHERE c.id = ?");
        $stmt->execute([$id]);
        $cmd = $stmt->fetch();
        if ($cmd && $cmd['statut'] === 'en_cours') {
            $facteur   = (float)($cmd['facteur_conditionnement'] ?: 1);
            $qteStock  = $qteOrder * $facteur; // conversion en unité de stock
            $noteRecu  = $cmd['facteur_conditionnement']
                ? 'Réception commande #' . $id . ' (' . stockFmt($qteOrder) . ' ' . $cmd['unite_commande'] . ')'
                : 'Réception commande #' . $id;
            $db->prepare("UPDATE stock_commandes SET statut='recu', date_reception=CURDATE() WHERE id=?")
               ->execute([$id]);
            $db->prepare("UPDATE stock_produits SET quantite_stock = quantite_stock + ? WHERE id = ?")
               ->execute([$qteStock, $cmd['produit_id']]);
            $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'ajout', ?, ?, ?)")
               ->execute([$cmd['produit_id'], $qteStock, $noteRecu, $auteur]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
        exit;
    }

    // Demande de commande (standalone uniquement — notifie les admins portail)
    if ($action === 'demande_commande') {
        $db->prepare("INSERT INTO stock_demandes_commande (produit_id, quantite_souhaitee, notes) VALUES (?, ?, ?)")
           ->execute([
               (int)$_POST['produit_id'],
               $_POST['quantite_souhaitee'] !== '' ? (float)$_POST['quantite_souhaitee'] : null,
               trim($_POST['notes'] ?? '') ?: null,
           ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock&demande_ok=1');
        exit;
    }

    // ── Actions réservées au portail ──────────────────────────────────────────

    if ($isPortalUser) {

        // Ajouter un produit
        if ($action === 'add_produit') {
            $seuil   = $_POST['seuil_alerte'] !== '' ? (float)$_POST['seuil_alerte'] : null;
            $ucmd    = trim($_POST['unite_commande'] ?? '') ?: null;
            $facteur = ($_POST['facteur_conditionnement'] ?? '') !== '' ? (float)$_POST['facteur_conditionnement'] : null;
            // Cohérence : les deux champs vont ensemble
            if (!$ucmd || !$facteur || $facteur <= 0) { $ucmd = null; $facteur = null; }
            $db->prepare("INSERT INTO stock_produits (nom, type_unite_id, quantite_stock, seuil_alerte, alerte_active, plateforme_commande, unite_commande, facteur_conditionnement)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([
                   trim($_POST['nom']),
                   (int)$_POST['type_unite_id'],
                   (float)$_POST['quantite_stock'],
                   $seuil,
                   isset($_POST['alerte_active']) ? 1 : 0,
                   trim($_POST['plateforme_commande'] ?? '') ?: null,
                   $ucmd,
                   $facteur,
               ]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
            exit;
        }

        // Modifier un produit
        if ($action === 'edit_produit') {
            $id      = (int)$_POST['id'];
            $seuil   = $_POST['seuil_alerte'] !== '' ? (float)$_POST['seuil_alerte'] : null;
            $ucmd    = trim($_POST['unite_commande'] ?? '') ?: null;
            $facteur = ($_POST['facteur_conditionnement'] ?? '') !== '' ? (float)$_POST['facteur_conditionnement'] : null;
            if (!$ucmd || !$facteur || $facteur <= 0) { $ucmd = null; $facteur = null; }
            $db->prepare("UPDATE stock_produits SET nom=?, type_unite_id=?, seuil_alerte=?, alerte_active=?, plateforme_commande=?, unite_commande=?, facteur_conditionnement=? WHERE id=?")
               ->execute([
                   trim($_POST['nom']),
                   (int)$_POST['type_unite_id'],
                   $seuil,
                   isset($_POST['alerte_active']) ? 1 : 0,
                   trim($_POST['plateforme_commande'] ?? '') ?: null,
                   $ucmd,
                   $facteur,
                   $id,
               ]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
            exit;
        }

        // Supprimer un produit
        if ($action === 'delete_produit') {
            $db->prepare("DELETE FROM stock_produits WHERE id = ?")->execute([(int)$_POST['id']]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
            exit;
        }

        // Correction inventaire (portail uniquement)
        if ($action === 'inventaire') {
            $id  = (int)$_POST['produit_id'];
            $qte = max(0, (float)$_POST['quantite']);
            $stmt = $db->prepare("SELECT quantite_stock FROM stock_produits WHERE id = ?");
            $stmt->execute([$id]);
            $ancienne = (float)($stmt->fetchColumn() ?? 0);
            $db->prepare("UPDATE stock_produits SET quantite_stock = ? WHERE id = ?")->execute([$qte, $id]);
            $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'inventaire', ?, ?, ?)")
               ->execute([$id, abs($qte - $ancienne), 'Correction inventaire : ' . stockFmt($ancienne) . ' → ' . stockFmt($qte), $auteur]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
            exit;
        }

        // Nouvelle commande
        if ($action === 'add_commande') {
            $pid      = (int)$_POST['produit_id'];
            $qteOrder = (float)$_POST['quantite']; // saisie en unité de commande
            // Récupérer le facteur pour stocker en unité de stock
            $stmtF = $db->prepare("SELECT facteur_conditionnement FROM stock_produits WHERE id=?");
            $stmtF->execute([$pid]);
            $facteurCmd = (float)($stmtF->fetchColumn() ?: 1);
            $qteStock   = $qteOrder * $facteurCmd;
            $db->prepare("INSERT INTO stock_commandes (produit_id, quantite, notes, date_commande) VALUES (?, ?, ?, ?)")
               ->execute([
                   $pid,
                   $qteStock, // stocké en unité de base
                   trim($_POST['notes'] ?? '') ?: null,
                   $_POST['date_commande'],
               ]);
            // Si cette commande provient d'une demande standalone, la marquer traitée
            if (!empty($_POST['demande_id'])) {
                $db->prepare("UPDATE stock_demandes_commande SET statut='traitee' WHERE id=?")
                   ->execute([(int)$_POST['demande_id']]);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
            exit;
        }

        // Annuler commande
        if ($action === 'annule_commande') {
            $db->prepare("UPDATE stock_commandes SET statut='annule' WHERE id=? AND statut='en_cours'")
               ->execute([(int)$_POST['id']]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
            exit;
        }

        // Marquer une demande standalone comme traitée sans commander
        if ($action === 'dismiss_demande') {
            $db->prepare("UPDATE stock_demandes_commande SET statut='traitee' WHERE id=?")
               ->execute([(int)$_POST['id']]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
            exit;
        }
    }

    // ── Actions admin portail ─────────────────────────────────────────────────
    if ($isPortalAdmin) {

        if ($action === 'save_credentials') {
            $login   = trim($_POST['stock_login_val'] ?? '');
            $newPass = trim($_POST['new_password'] ?? '');
            if ($login !== '') {
                if ($newPass !== '') {
                    $db->prepare("UPDATE stock_credentials SET login=?, password_hash=? WHERE id=1")
                       ->execute([$login, password_hash($newPass, PASSWORD_DEFAULT)]);
                } else {
                    $db->prepare("UPDATE stock_credentials SET login=? WHERE id=1")->execute([$login]);
                }
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin&saved=1');
            exit;
        }

        if ($action === 'add_unite') {
            $lib = trim($_POST['libelle'] ?? '');
            if ($lib !== '') {
                $db->prepare("INSERT INTO stock_types_unite (libelle, ordre) VALUES (?, (SELECT COALESCE(MAX(o),0)+1 FROM (SELECT ordre AS o FROM stock_types_unite) t))")
                   ->execute([$lib]);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin');
            exit;
        }

        if ($action === 'delete_unite') {
            $id   = (int)$_POST['id'];
            $used = $db->prepare("SELECT COUNT(*) FROM stock_produits WHERE type_unite_id=?");
            $used->execute([$id]);
            if ((int)$used->fetchColumn() === 0) {
                $db->prepare("DELETE FROM stock_types_unite WHERE id=?")->execute([$id]);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin');
            exit;
        }

        if ($action === 'toggle_alert_user') {
            $uid  = (int)$_POST['user_id'];
            $stmt = $db->prepare("SELECT 1 FROM stock_alert_users WHERE user_id=?");
            $stmt->execute([$uid]);
            if ($stmt->fetchColumn()) {
                $db->prepare("DELETE FROM stock_alert_users WHERE user_id=?")->execute([$uid]);
            } else {
                $db->prepare("INSERT IGNORE INTO stock_alert_users (user_id) VALUES (?)")->execute([$uid]);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin');
            exit;
        }
    }
}

// ─── Données ─────────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'stock';

$typesUnite = $db->query("SELECT * FROM stock_types_unite ORDER BY ordre ASC, libelle ASC")->fetchAll();

$produits = $db->query("
    SELECT p.*, t.libelle AS unite
    FROM stock_produits p
    LEFT JOIN stock_types_unite t ON t.id = p.type_unite_id
    WHERE p.actif = 1
    ORDER BY p.nom ASC
")->fetchAll();

$commandes = $db->query("
    SELECT c.*, p.nom AS produit_nom, p.unite_commande, p.facteur_conditionnement,
           t.libelle AS unite
    FROM stock_commandes c
    JOIN stock_produits p ON p.id = c.produit_id
    LEFT JOIN stock_types_unite t ON t.id = p.type_unite_id
    ORDER BY CASE WHEN c.statut='en_cours' THEN 0 ELSE 1 END, c.date_commande DESC
    LIMIT 200
")->fetchAll();

$mouvements = $db->query("
    SELECT m.*, p.nom AS produit_nom, t.libelle AS unite
    FROM stock_mouvements m
    JOIN stock_produits p ON p.id = m.produit_id
    LEFT JOIN stock_types_unite t ON t.id = p.type_unite_id
    ORDER BY m.created_at DESC
    LIMIT 300
")->fetchAll();

// Demandes standalone en attente (visibles aux utilisateurs portail)
$demandesEnAttente = [];
$nbDemandesNouvelles = 0;
if ($isPortalUser) {
    $demandesEnAttente = $db->query("
        SELECT d.*, p.nom AS produit_nom, p.unite_commande, p.facteur_conditionnement,
               t.libelle AS unite, p.plateforme_commande
        FROM stock_demandes_commande d
        JOIN stock_produits p ON p.id = d.produit_id
        LEFT JOIN stock_types_unite t ON t.id = p.type_unite_id
        ORDER BY d.created_at DESC
    ")->fetchAll();
    $nbDemandesNouvelles = count(array_filter($demandesEnAttente, fn($d) => $d['statut'] === 'nouvelle'));
}

$nbEnCoursCmd    = count(array_filter($commandes, fn($c) => $c['statut'] === 'en_cours'));
$nbAlertes       = count(array_filter($produits, fn($p) =>
    $p['alerte_active'] && $p['seuil_alerte'] !== null && (float)$p['quantite_stock'] <= (float)$p['seuil_alerte']
));

// Admin
$credentials    = $db->query("SELECT login FROM stock_credentials LIMIT 1")->fetch();
$allPortalUsers = [];
$alertUserIds   = [];
if ($isPortalAdmin) {
    $allPortalUsers = $db->query("SELECT id, prenom, nom FROM users ORDER BY nom, prenom")->fetchAll();
    $alertUserIds   = $db->query("SELECT user_id FROM stock_alert_users")->fetchAll(PDO::FETCH_COLUMN);
}

// ─── Layout ───────────────────────────────────────────────────────────────────
$projectRootFs  = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
$scriptFs       = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']));
$relativeScript = str_replace($projectRootFs, '', $scriptFs);
$B              = rtrim(str_replace($relativeScript, '', $_SERVER['SCRIPT_NAME']), '/');

if ($isPortalUser) {
    $pageTitle = 'Gestion des fournitures';
    require_once __DIR__ . '/../../templates/header.php';
} else {
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des fournitures</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= $B ?>/assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  function filterTable(inputId, tableId) {
      const input = document.getElementById(inputId);
      if (!input) return;
      input.addEventListener('keyup', function() {
          const filter = this.value.toLowerCase();
          document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
              row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
          });
      });
  }
  </script>
</head>
<body>
  <div style="background:#CF0A2C;padding:14px 28px;display:flex;align-items:center;gap:16px;">
    <img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png"
         alt="CE" style="height:34px;">
    <span style="color:#fff;font-weight:600;font-size:1.05rem;">
      <i class="fas fa-boxes"></i> Gestion des fournitures
    </span>
    <a href="?logout=1" class="btn btn-sm ms-auto"
       style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
  </div>
  <div class="page-content" style="padding:24px;max-width:1400px;margin:0 auto;">
<?php } ?>

<!-- ─── Message confirmation demande ─────────────────────────────────────────── -->
<?php if (isset($_GET['demande_ok'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="fas fa-check-circle"></i>
  Votre demande de commande a bien été transmise.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ─── Navigation tabs ──────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'stock' ? 'active' : '' ?>" href="?tab=stock">
      <i class="fas fa-boxes"></i> Stock
      <?php if ($nbAlertes > 0 && $isPortalUser): ?>
        <span class="badge bg-danger ms-1"><?= $nbAlertes ?></span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'commandes' ? 'active' : '' ?>" href="?tab=commandes">
      <i class="fas fa-shopping-cart"></i> Commandes
      <?php
        $badgeCmd = $nbEnCoursCmd + ($isPortalUser ? $nbDemandesNouvelles : 0);
        if ($badgeCmd > 0): ?>
        <span class="badge <?= $nbDemandesNouvelles > 0 ? 'bg-danger' : 'bg-warning text-dark' ?> ms-1"><?= $badgeCmd ?></span>
      <?php endif; ?>
    </a>
  </li>
  <?php if ($isPortalUser): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'historique' ? 'active' : '' ?>" href="?tab=historique">
      <i class="fas fa-history"></i> Historique
    </a>
  </li>
  <?php if ($isPortalAdmin): ?>
  <li class="nav-item ms-auto">
    <a class="nav-link <?= $tab === 'admin' ? 'active' : '' ?>" href="?tab=admin">
      <i class="fas fa-cog"></i> Administration
    </a>
  </li>
  <?php endif; ?>
  <?php endif; ?>
</ul>

<?php // ══════════════════════════════════════════════════════════════════════
// ── TAB : STOCK ──────────────────────────────────────────────────────────────
if ($tab === 'stock'): ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0"><i class="fas fa-boxes"></i> Produits en stock</h4>
  <?php if ($isPortalUser): ?>
  <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addProduitModal">
    <i class="fas fa-plus"></i> Nouveau produit
  </button>
  <?php endif; ?>
</div>

<div class="data-table-container">
  <div class="data-table-header">
    <div></div>
    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchStock" placeholder="Rechercher..."></div>
  </div>
  <table class="data-table" id="tableStock">
    <thead>
      <tr>
        <th>Produit</th>
        <th>Type</th>
        <th>Stock actuel</th>
        <?php if ($isPortalUser): ?><th>Seuil alerte</th><?php endif; ?>
        <th>Se commande sur</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p):
        $enAlerte = $p['alerte_active'] && $p['seuil_alerte'] !== null
                 && (float)$p['quantite_stock'] <= (float)$p['seuil_alerte'];
    ?>
      <tr <?= $enAlerte && $isPortalUser ? 'class="table-danger"' : '' ?>>
        <td><strong><?= e($p['nom']) ?></strong></td>
        <td>
          <?= e($p['unite'] ?? '—') ?>
          <?php if ($p['facteur_conditionnement'] && $p['unite_commande']): ?>
            <br><small class="text-muted">
              <i class="fas fa-layer-group"></i>
              1 <?= e($p['unite_commande']) ?> = <?= stockFmt($p['facteur_conditionnement']) ?> <?= e($p['unite'] ?? '') ?>
            </small>
          <?php endif; ?>
        </td>
        <td>
          <span class="fw-bold <?= $enAlerte && $isPortalUser ? 'text-danger' : '' ?>">
            <?= stockFmt($p['quantite_stock']) ?> <?= e($p['unite'] ?? '') ?>
          </span>
          <?php if ($p['facteur_conditionnement'] && $p['unite_commande']): ?>
            <br><small class="text-muted">
              ≈ <?= stockFmt((float)$p['quantite_stock'] / (float)$p['facteur_conditionnement']) ?>
              <?= e($p['unite_commande']) ?>
            </small>
          <?php endif; ?>
        </td>
        <?php if ($isPortalUser): ?>
        <td>
          <?php if ($p['seuil_alerte'] !== null): ?>
            <?= stockFmt($p['seuil_alerte']) ?>
            <i class="fas fa-bell<?= $p['alerte_active'] ? '' : '-slash text-muted' ?> ms-1
               <?= $p['alerte_active'] ? 'text-warning' : '' ?>"
               title="Alerte <?= $p['alerte_active'] ? 'active' : 'inactive' ?>"></i>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td><?= $p['plateforme_commande'] ? e($p['plateforme_commande']) : '<span class="text-muted">—</span>' ?></td>
        <td class="actions">
          <!-- Retrait -->
          <button class="btn btn-sm btn-outline-danger"
                  onclick="openMvt('retrait',<?= $p['id'] ?>,'<?= e(addslashes($p['nom'])) ?>','<?= e($p['unite'] ?? '') ?>')"
                  title="Retirer du stock">
            <i class="fas fa-minus-circle"></i>
          </button>
          <!-- Ajout -->
          <button class="btn btn-sm btn-outline-success"
                  onclick="openMvt('ajout',<?= $p['id'] ?>,'<?= e(addslashes($p['nom'])) ?>','<?= e($p['unite'] ?? '') ?>')"
                  title="Ajouter au stock">
            <i class="fas fa-plus-circle"></i>
          </button>
          <?php if ($isPortalUser): ?>
          <!-- Inventaire (portail uniquement) -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="openMvt('inventaire',<?= $p['id'] ?>,'<?= e(addslashes($p['nom'])) ?>','<?= e($p['unite'] ?? '') ?>',<?= (float)$p['quantite_stock'] ?>)"
                  title="Correction inventaire">
            <i class="fas fa-clipboard-check"></i>
          </button>
          <!-- Commander (portail uniquement) -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="openCommande(<?= $p['id'] ?>,'<?= e(addslashes($p['nom'])) ?>','<?= e($p['unite'] ?? '') ?>','<?= e($p['unite_commande'] ?? '') ?>',<?= (float)($p['facteur_conditionnement'] ?: 0) ?>)"
                  title="Passer une commande">
            <i class="fas fa-shopping-cart"></i>
          </button>
          <!-- Modifier -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="editProduit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"
                  title="Modifier">
            <i class="fas fa-edit"></i>
          </button>
          <!-- Supprimer -->
          <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce produit et tout son historique ?')">
            <input type="hidden" name="action" value="delete_produit">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
          </form>
          <?php else: ?>
          <!-- Demande de commande (standalone uniquement) -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="openDemande(<?= $p['id'] ?>,'<?= e(addslashes($p['nom'])) ?>','<?= e($p['unite'] ?? '') ?>','<?= e($p['unite_commande'] ?? '') ?>',<?= (float)($p['facteur_conditionnement'] ?: 0) ?>)"
                  title="Demander une commande">
            <i class="fas fa-bell"></i> Demander
          </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($produits)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Aucun produit.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php // ── TAB : COMMANDES ───────────────────────────────────────────────────
elseif ($tab === 'commandes'): ?>

<?php if ($isPortalUser && !empty($demandesEnAttente)): ?>
<!-- ─ Demandes standalone en attente ──────────────────────────────────────── -->
<div class="mb-4">
  <h5 class="mb-3">
    <i class="fas fa-bell text-danger"></i> Demandes de commande
    <?php $nbNouvelles = count(array_filter($demandesEnAttente, fn($d) => $d['statut'] === 'nouvelle')); ?>
    <?php if ($nbNouvelles > 0): ?>
      <span class="badge bg-danger"><?= $nbNouvelles ?> nouvelle<?= $nbNouvelles > 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </h5>
  <div class="data-table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Produit</th>
          <th>Qté souhaitée</th>
          <th>Message</th>
          <th>Se commande sur</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($demandesEnAttente as $d): ?>
        <tr class="<?= $d['statut'] === 'nouvelle' ? 'table-warning' : 'text-muted' ?>">
          <td><strong><?= e($d['produit_nom']) ?></strong></td>
          <td>
            <?php if ($d['quantite_souhaitee'] !== null): ?>
              <?= stockFmt($d['quantite_souhaitee']) ?>
              <?= e($d['unite_commande'] ?? $d['unite'] ?? '') ?>
              <?php if ($d['facteur_conditionnement'] && $d['unite_commande']): ?>
                <br><small class="text-muted">= <?= stockFmt((float)$d['quantite_souhaitee'] * (float)$d['facteur_conditionnement']) ?> <?= e($d['unite'] ?? '') ?></small>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= e($d['notes'] ?? '') ?></td>
          <td><?= $d['plateforme_commande'] ? e($d['plateforme_commande']) : '<span class="text-muted">—</span>' ?></td>
          <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
          <td>
            <?= $d['statut'] === 'nouvelle'
                ? '<span class="badge bg-danger">Nouvelle</span>'
                : '<span class="badge bg-secondary">Traitée</span>' ?>
          </td>
          <td class="actions">
            <?php if ($d['statut'] === 'nouvelle'): ?>
            <!-- Convertir en commande -->
            <button class="btn btn-sm btn-ce"
                    onclick="convertirDemande(<?= $d['id'] ?>,<?= $d['produit_id'] ?>,'<?= e(addslashes($d['produit_nom'])) ?>','<?= e($d['unite'] ?? '') ?>',<?= $d['quantite_souhaitee'] !== null ? (float)$d['quantite_souhaitee'] : 'null' ?>,'<?= e($d['unite_commande'] ?? '') ?>',<?= (float)($d['facteur_conditionnement'] ?? 0) ?>)"
                    title="Créer la commande">
              <i class="fas fa-cart-plus"></i> Commander
            </button>
            <!-- Ignorer -->
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="dismiss_demande">
              <input type="hidden" name="id" value="<?= $d['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" title="Ignorer"><i class="fas fa-times"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ─ Commandes ──────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Commandes</h5>
  <?php if ($isPortalUser): ?>
  <button class="btn btn-ce" onclick="openCommande(0,'','')">
    <i class="fas fa-plus"></i> Nouvelle commande
  </button>
  <?php endif; ?>
</div>

<div class="data-table-container">
  <div class="data-table-header">
    <div></div>
    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchCmd" placeholder="Rechercher..."></div>
  </div>
  <table class="data-table" id="tableCmd">
    <thead>
      <tr>
        <th>Produit</th>
        <th>Quantité</th>
        <th>Date commande</th>
        <th>Notes</th>
        <th>Statut</th>
        <th>Réception</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($commandes as $c): ?>
      <tr>
        <td><?= e($c['produit_nom']) ?></td>
        <td>
          <?php if ($c['facteur_conditionnement'] && $c['unite_commande']): ?>
            <?= stockFmt((float)$c['quantite'] / (float)$c['facteur_conditionnement']) ?>
            <?= e($c['unite_commande']) ?>
            <br><small class="text-muted">= <?= stockFmt($c['quantite']) ?> <?= e($c['unite'] ?? '') ?></small>
          <?php else: ?>
            <?= stockFmt($c['quantite']) ?> <?= e($c['unite'] ?? '') ?>
          <?php endif; ?>
        </td>
        <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
        <td><?= e($c['notes'] ?? '') ?></td>
        <td>
          <?php if ($c['statut'] === 'en_cours'): ?>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En cours</span>
          <?php elseif ($c['statut'] === 'recu'): ?>
            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Reçue</span>
          <?php else: ?>
            <span class="badge bg-secondary"><i class="fas fa-times-circle"></i> Annulée</span>
          <?php endif; ?>
        </td>
        <td><?= $c['date_reception'] ? date('d/m/Y', strtotime($c['date_reception'])) : '—' ?></td>
        <td class="actions">
          <?php if ($c['statut'] === 'en_cours'): ?>
            <button class="btn btn-sm btn-success"
                    onclick="recuCommande(<?= $c['id'] ?>,<?= (float)$c['quantite'] ?>,'<?= e(addslashes($c['produit_nom'])) ?>','<?= e($c['unite'] ?? '') ?>','<?= e($c['unite_commande'] ?? '') ?>',<?= (float)($c['facteur_conditionnement'] ?: 0) ?>)"
                    title="Marquer comme reçue">
              <i class="fas fa-check"></i> Reçue
            </button>
            <?php if ($isPortalUser): ?>
            <form method="POST" class="d-inline" onsubmit="return confirm('Annuler cette commande ?')">
              <input type="hidden" name="action" value="annule_commande">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" title="Annuler"><i class="fas fa-times"></i></button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($commandes)): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">Aucune commande.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php // ── TAB : HISTORIQUE (portail uniquement) ────────────────────────────
elseif ($tab === 'historique' && $isPortalUser): ?>

<h4 class="mb-3"><i class="fas fa-history"></i> Historique des mouvements</h4>
<div class="data-table-container">
  <div class="data-table-header">
    <div></div>
    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchHisto" placeholder="Rechercher..."></div>
  </div>
  <table class="data-table" id="tableHisto">
    <thead>
      <tr><th>Date</th><th>Produit</th><th>Mouvement</th><th>Quantité</th><th>Notes</th><th>Auteur</th></tr>
    </thead>
    <tbody>
    <?php foreach ($mouvements as $m): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
        <td><?= e($m['produit_nom']) ?></td>
        <td>
          <?php if ($m['type'] === 'retrait'): ?>
            <span class="badge bg-danger"><i class="fas fa-minus"></i> Retrait</span>
          <?php elseif ($m['type'] === 'ajout'): ?>
            <span class="badge bg-success"><i class="fas fa-plus"></i> Ajout</span>
          <?php else: ?>
            <span class="badge bg-info text-dark"><i class="fas fa-clipboard-check"></i> Inventaire</span>
          <?php endif; ?>
        </td>
        <td><?= stockFmt($m['quantite']) ?> <?= e($m['unite'] ?? '') ?></td>
        <td><?= e($m['notes'] ?? '') ?></td>
        <td><?= e($m['auteur'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($mouvements)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Aucun mouvement.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php // ── TAB : ADMIN ───────────────────────────────────────────────────────
elseif ($tab === 'admin' && $isPortalAdmin): ?>

<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> Paramètres enregistrés.</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Credentials -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header" style="background:#CF0A2C;color:#fff;">
        <i class="fas fa-key"></i> Identifiants d'accès partagés
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Identifiant actuel : <strong><?= e($credentials['login'] ?? '—') ?></strong>
        </p>
        <form method="POST">
          <input type="hidden" name="action" value="save_credentials">
          <div class="mb-3">
            <label class="form-label">Identifiant</label>
            <input type="text" name="stock_login_val" class="form-control"
                   value="<?= e($credentials['login'] ?? '') ?>" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label">Nouveau mot de passe <small class="text-muted">(vide = inchangé)</small></label>
            <input type="password" name="new_password" class="form-control" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Types d'unité -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header" style="background:#CF0A2C;color:#fff;">
        <i class="fas fa-ruler"></i> Types d'unité
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush mb-3">
          <?php foreach ($typesUnite as $u): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
            <?= e($u['libelle']) ?>
            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce type ?')">
              <input type="hidden" name="action" value="delete_unite">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </li>
          <?php endforeach; ?>
        </ul>
        <form method="POST" class="d-flex gap-2">
          <input type="hidden" name="action" value="add_unite">
          <input type="text" name="libelle" class="form-control form-control-sm" placeholder="Nouveau type..." required>
          <button type="submit" class="btn btn-ce btn-sm"><i class="fas fa-plus"></i></button>
        </form>
        <p class="text-muted small mt-2 mb-0">
          <i class="fas fa-info-circle"></i> Un type utilisé par un produit ne peut pas être supprimé.
        </p>
      </div>
    </div>
  </div>

  <!-- Alertes stock : utilisateurs portail -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header" style="background:#CF0A2C;color:#fff;">
        <i class="fas fa-bell"></i> Alertes stock — Utilisateurs portail notifiés
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Les utilisateurs cochés voient un badge d'alerte dans leur menu lorsqu'un produit
          passe sous son seuil (alerte active sur le produit).
        </p>
        <div class="row g-2">
          <?php foreach ($allPortalUsers as $u): ?>
          <div class="col-md-4 col-lg-3">
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="toggle_alert_user">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit"
                      class="btn btn-sm w-100 text-start <?= in_array($u['id'], $alertUserIds) ? 'btn-warning' : 'btn-outline-secondary' ?>">
                <i class="fas <?= in_array($u['id'], $alertUserIds) ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
                <?= e($u['prenom'] . ' ' . $u['nom']) ?>
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; // End tabs ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════════════════════ -->

<?php if ($isPortalUser): ?>
<!-- Modal : Nouveau produit (portail uniquement) -->
<div class="modal fade modal-fullscreen-custom" id="addProduitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-boxes"></i> Nouveau produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_produit">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nom du produit</label>
              <input type="text" name="nom" class="form-control" required placeholder="Ex : Papier A4">
            </div>
            <div class="col-md-4">
              <label class="form-label">Type / Unité</label>
              <select name="type_unite_id" class="form-select" required>
                <?php foreach ($typesUnite as $u): ?>
                  <option value="<?= $u['id'] ?>"><?= e($u['libelle']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Quantité initiale en stock</label>
              <input type="number" name="quantite_stock" class="form-control" min="0" step="0.01" value="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Seuil d'alerte <small class="text-muted">(optionnel)</small></label>
              <input type="number" name="seuil_alerte" class="form-control" min="0" step="0.01"
                     placeholder="Laisser vide = pas de seuil">
            </div>
            <div class="col-md-6">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="alerte_active" id="alerteActive" value="1">
                <label class="form-check-label" for="alerteActive">
                  <i class="fas fa-bell text-warning"></i> Alerte active
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Se commande sur <small class="text-muted">(optionnel)</small></label>
              <input type="text" name="plateforme_commande" class="form-control"
                     placeholder="Ex : Amazon Business, Lyreco...">
            </div>
            <!-- Conditionnement -->
            <div class="col-12">
              <hr class="my-1">
              <label class="form-label fw-semibold">
                <i class="fas fa-layer-group"></i> Conditionnement
                <small class="text-muted fw-normal">(optionnel — si on commande en carton mais retire à l'unité)</small>
              </label>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unité de commande</label>
              <input type="text" name="unite_commande" class="form-control"
                     placeholder="Ex : Carton, Pack, Lot...">
            </div>
            <div class="col-md-6">
              <label class="form-label" id="facteurLabel">1 unité de commande =</label>
              <div class="input-group">
                <input type="number" name="facteur_conditionnement" id="addFacteur"
                       class="form-control" min="0.01" step="0.01"
                       placeholder="Ex : 10">
                <span class="input-group-text" id="addFacteurSuffix">unités</span>
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

<!-- Modal : Modifier produit -->
<div class="modal fade modal-fullscreen-custom" id="editProduitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="editProduitContent"></div>
    </div>
  </div>
</div>

<!-- Modal : Commande (portail uniquement) -->
<div class="modal fade" id="commandeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-shopping-cart"></i> Passer une commande</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="commandeForm">
          <input type="hidden" name="action" value="add_commande">
          <input type="hidden" name="demande_id" id="commandeDemandId" value="">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Produit</label>
              <select name="produit_id" id="cmdProduitSelect" class="form-select" required>
                <option value="">— Choisir —</option>
                <?php foreach ($produits as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= e($p['nom']) ?> (<?= e($p['unite'] ?? '') ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" id="cmdQteLabel">Quantité commandée</label>
              <input type="number" name="quantite" id="cmdQuantite" class="form-control" min="0.01" step="0.01" required>
              <small id="cmdQteHint" class="text-muted"></small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de commande</label>
              <input type="date" name="date_commande" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Notes <small class="text-muted">(optionnel)</small></label>
              <input type="text" name="notes" class="form-control" placeholder="Référence, fournisseur...">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-ce">
                <i class="fas fa-cart-plus"></i> Enregistrer la commande
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; // isPortalUser modals ?>

<!-- Modal : Mouvement de stock (retrait / ajout / inventaire) -->
<div class="modal fade" id="mvtModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" id="mvtModalHeader">
        <h5 class="modal-title" id="mvtModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="mvtForm">
          <input type="hidden" name="action" id="mvtAction">
          <input type="hidden" name="produit_id" id="mvtProduitId">
          <div class="mb-3">
            <label class="form-label" id="mvtQteLabel">Quantité</label>
            <input type="number" name="quantite" id="mvtQte" class="form-control" min="0" step="0.01" required>
          </div>
          <div class="mb-3" id="mvtNotesRow">
            <label class="form-label">Notes <small class="text-muted">(optionnel)</small></label>
            <input type="text" name="notes" class="form-control" placeholder="Raison, référence...">
          </div>
          <button type="submit" class="btn" id="mvtSubmitBtn">Valider</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal : Réception commande -->
<div class="modal fade" id="recuModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#198754;color:#fff;">
        <h5 class="modal-title"><i class="fas fa-check-circle"></i> Réception de commande</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="recuDesc"></p>
        <form method="POST">
          <input type="hidden" name="action" value="recu_commande">
          <input type="hidden" name="id" id="recuId">
          <div class="mb-3">
            <label class="form-label" id="recuQteLabel">Quantité effectivement reçue</label>
            <input type="number" name="quantite_recue" id="recuQte" class="form-control" min="0.01" step="0.01" required>
            <small id="recuQteHint" class="text-muted mt-1 d-block"></small>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Valider la réception
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal : Demande de commande (standalone uniquement) -->
<?php if ($isRestreint): ?>
<div class="modal fade" id="demandeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#CF0A2C;color:#fff;">
        <h5 class="modal-title" id="demandeModalTitle">
          <i class="fas fa-bell"></i> Demande de commande
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">
          <i class="fas fa-info-circle"></i>
          Votre demande sera transmise et visible par les responsables.
        </p>
        <form method="POST">
          <input type="hidden" name="action" value="demande_commande">
          <input type="hidden" name="produit_id" id="demandeProduitId">
          <div class="mb-3">
            <label class="form-label" id="demandeQteLabel">Quantité souhaitée <small class="text-muted">(optionnel)</small></label>
            <input type="number" name="quantite_souhaitee" id="demandeQte"
                   class="form-control" min="0.01" step="0.01" placeholder="Laisser vide si non précisée">
            <small id="demandeQteHint" class="text-muted mt-1 d-block"></small>
          </div>
          <div class="mb-3">
            <label class="form-label">Message / précisions <small class="text-muted">(optionnel)</small></label>
            <input type="text" name="notes" class="form-control" placeholder="Urgence, référence...">
          </div>
          <button type="submit" class="btn btn-ce w-100">
            <i class="fas fa-paper-plane"></i> Envoyer la demande
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════════════════════ -->
<script>
filterTable('searchStock',  'tableStock');
filterTable('searchCmd',    'tableCmd');
filterTable('searchHisto',  'tableHisto');

const typesUnite   = <?= json_encode(array_values($typesUnite)) ?>;
const isPortalUser = <?= $isPortalUser ? 'true' : 'false' ?>;

// ── Mouvement de stock ────────────────────────────────────────────────────────
function openMvt(type, id, nom, unite, currentQte) {
    const cfg = {
        retrait:    { title:'Retrait de stock',      action:'retrait',    headerCls:'bg-danger text-white',  qteLabel:'Quantité à retirer', qteMin:0.01, qteVal:'',         btnCls:'btn-danger'  },
        ajout:      { title:'Ajout au stock',        action:'ajout',      headerCls:'bg-success text-white', qteLabel:'Quantité à ajouter', qteMin:0.01, qteVal:'',         btnCls:'btn-success' },
        inventaire: { title:'Correction inventaire', action:'inventaire', headerCls:'',                      qteLabel:'Nouvelle quantité totale', qteMin:0, qteVal:currentQte ?? '', btnCls:'btn-ce' },
    };
    const c = cfg[type];
    document.getElementById('mvtModalTitle').innerHTML =
        `<i class="fas fa-${type==='retrait'?'minus':'plus'}-circle"></i> ${c.title} — <em>${nom}</em>`;
    document.getElementById('mvtModalHeader').className = 'modal-header ' + c.headerCls;
    document.getElementById('mvtAction').value    = c.action;
    document.getElementById('mvtProduitId').value = id;
    document.getElementById('mvtQteLabel').textContent = c.qteLabel + (unite ? ` (${unite})` : '');
    document.getElementById('mvtQte').min   = c.qteMin;
    document.getElementById('mvtQte').value = c.qteVal;
    document.getElementById('mvtSubmitBtn').className = 'btn ' + c.btnCls;
    document.getElementById('mvtNotesRow').style.display = type === 'inventaire' ? 'none' : '';
    new bootstrap.Modal(document.getElementById('mvtModal')).show();
    setTimeout(() => document.getElementById('mvtQte').focus(), 300);
}

// ── Réception commande ────────────────────────────────────────────────────────
// qteBase = stocké en unité de base ; uniteCmd/facteur = conditionnement éventuel
function recuCommande(id, qteBase, nom, unite, uniteCmd, facteur) {
    const hasFacteur = uniteCmd && facteur > 0;
    // Pré-remplir en unité de commande si conditionnement défini
    const qteOrder = hasFacteur ? Math.round((qteBase / facteur) * 100) / 100 : qteBase;

    document.getElementById('recuId').value  = id;
    document.getElementById('recuQte').value = qteOrder;

    const desc = hasFacteur
        ? `Commande de <strong>${qteOrder} ${uniteCmd}</strong> (= ${qteBase} ${unite}) — <em>${nom}</em><br>Confirmez ou ajustez la quantité réellement reçue.`
        : `Commande de <strong>${qteBase} ${unite}</strong> — <em>${nom}</em><br>Confirmez ou ajustez la quantité réellement reçue.`;
    document.getElementById('recuDesc').innerHTML = desc;

    const label = hasFacteur
        ? `Quantité reçue (${uniteCmd})`
        : `Quantité effectivement reçue (${unite})`;
    document.getElementById('recuQteLabel').textContent = label;

    // Hint de conversion mis à jour à la saisie
    const hint = document.getElementById('recuQteHint');
    function updateHint() {
        const v = parseFloat(document.getElementById('recuQte').value);
        hint.textContent = hasFacteur && v > 0
            ? `= ${Math.round(v * facteur * 100) / 100} ${unite} ajoutés au stock`
            : '';
    }
    document.getElementById('recuQte').oninput = updateHint;
    updateHint();

    new bootstrap.Modal(document.getElementById('recuModal')).show();
    setTimeout(() => document.getElementById('recuQte').focus(), 300);
}

<?php if ($isPortalUser): ?>
// ── Commande (portail) ────────────────────────────────────────────────────────
function _setCmdLabels(uniteCmd, facteur, unite) {
    const hasFacteur = uniteCmd && facteur > 0;
    document.getElementById('cmdQteLabel').textContent = hasFacteur
        ? `Quantité commandée (${uniteCmd})`
        : `Quantité commandée (${unite || 'unités'})`;
    const hint = document.getElementById('cmdQteHint');
    function updateHint() {
        const v = parseFloat(document.getElementById('cmdQuantite').value);
        hint.textContent = hasFacteur && v > 0
            ? `= ${Math.round(v * facteur * 100) / 100} ${unite} ajoutés au stock à la réception`
            : '';
    }
    document.getElementById('cmdQuantite').oninput = updateHint;
    updateHint();
}

function openCommande(produitId, nom, unite, uniteCmd, facteur) {
    document.getElementById('commandeDemandId').value = '';
    if (produitId) document.getElementById('cmdProduitSelect').value = produitId;
    _setCmdLabels(uniteCmd, facteur, unite);
    new bootstrap.Modal(document.getElementById('commandeModal')).show();
}

// qteStandalone = quantité souhaitée en unité de commande (déjà dans l'unité d'ordre)
function convertirDemande(demandeId, produitId, nom, unite, qteStandalone, uniteCmd, facteur) {
    document.getElementById('commandeDemandId').value = demandeId;
    document.getElementById('cmdProduitSelect').value = produitId;
    // La demande standalone est en unité de commande si conditionnement défini
    document.getElementById('cmdQuantite').value      = qteStandalone || '';
    _setCmdLabels(uniteCmd, facteur, unite);
    new bootstrap.Modal(document.getElementById('commandeModal')).show();
}

// ── Modifier produit (portail) ────────────────────────────────────────────────
function editProduit(p) {
    const optionsHtml = typesUnite.map(t =>
        `<option value="${t.id}" ${t.id == p.type_unite_id ? 'selected' : ''}>${t.libelle}</option>`
    ).join('');
    document.getElementById('editProduitContent').innerHTML = `
        <form method="POST">
          <input type="hidden" name="action" value="edit_produit">
          <input type="hidden" name="id" value="${p.id}">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nom du produit</label>
              <input type="text" name="nom" class="form-control"
                     value="${p.nom.replace(/"/g,'&quot;')}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type / Unité</label>
              <select name="type_unite_id" class="form-select" required>${optionsHtml}</select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Seuil d'alerte</label>
              <input type="number" name="seuil_alerte" class="form-control" min="0" step="0.01"
                     value="${p.seuil_alerte !== null ? p.seuil_alerte : ''}"
                     placeholder="Laisser vide = pas de seuil">
            </div>
            <div class="col-md-6 d-flex align-items-end pb-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="alerte_active"
                       id="editAlerteActive" value="1" ${p.alerte_active == 1 ? 'checked' : ''}>
                <label class="form-check-label" for="editAlerteActive">
                  <i class="fas fa-bell text-warning"></i> Alerte active
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Se commande sur</label>
              <input type="text" name="plateforme_commande" class="form-control"
                     value="${(p.plateforme_commande || '').replace(/"/g,'&quot;')}"
                     placeholder="Amazon Business, Lyreco...">
            </div>
            <div class="col-12"><hr class="my-1">
              <label class="form-label fw-semibold">
                <i class="fas fa-layer-group"></i> Conditionnement
                <small class="text-muted fw-normal">(optionnel)</small>
              </label>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unité de commande</label>
              <input type="text" name="unite_commande" class="form-control"
                     value="${(p.unite_commande || '').replace(/"/g,'&quot;')}"
                     placeholder="Ex : Carton, Pack, Lot...">
            </div>
            <div class="col-md-6">
              <label class="form-label">1 unité de commande =</label>
              <div class="input-group">
                <input type="number" name="facteur_conditionnement" class="form-control"
                       min="0.01" step="0.01"
                       value="${p.facteur_conditionnement || ''}"
                       placeholder="Ex : 10">
                <span class="input-group-text">${p.unite || 'unités'}</span>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
          </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editProduitModal')).show();
}
<?php endif; ?>

<?php if ($isRestreint): ?>
// ── Demande de commande (standalone) ─────────────────────────────────────────
function openDemande(id, nom, unite, uniteCmd, facteur) {
    const hasFacteur = uniteCmd && facteur > 0;
    document.getElementById('demandeProduitId').value = id;
    document.getElementById('demandeModalTitle').innerHTML =
        `<i class="fas fa-bell"></i> Demande de commande — <em>${nom}</em>`;
    document.getElementById('demandeQte').value = '';
    document.getElementById('demandeQteLabel').innerHTML = hasFacteur
        ? `Quantité souhaitée <span class="text-muted small fw-normal">(en ${uniteCmd}, optionnel)</span>`
        : `Quantité souhaitée <span class="text-muted small fw-normal">(${unite}, optionnel)</span>`;
    const hint = document.getElementById('demandeQteHint');
    function updateHint() {
        const v = parseFloat(document.getElementById('demandeQte').value);
        hint.textContent = hasFacteur && v > 0
            ? `= ${Math.round(v * facteur * 100) / 100} ${unite}`
            : '';
    }
    document.getElementById('demandeQte').oninput = updateHint;
    hint.textContent = '';
    new bootstrap.Modal(document.getElementById('demandeModal')).show();
    setTimeout(() => document.getElementById('demandeQte').focus(), 300);
}
<?php endif; ?>
</script>

<?php
if ($isPortalUser) {
    require_once __DIR__ . '/../../templates/footer.php';
} else {
    echo '</div></body></html>';
}
