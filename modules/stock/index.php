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
$isStockUser   = !empty($_SESSION['stock_auth']);
$loginError    = '';

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
        // ── Page de connexion standalone ──────────────────────────────────
        ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des fournitures — Connexion</title>
  <?php
  // Calcul du chemin de base
  $projectRootFs = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
  $scriptFs = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']));
  $relativeScript = str_replace($projectRootFs, '', $scriptFs);
  $B = rtrim(str_replace($relativeScript, '', $_SERVER['SCRIPT_NAME']), '/');
  ?>
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

    // Ajouter un produit
    if ($action === 'add_produit') {
        $seuil = $_POST['seuil_alerte'] !== '' ? (float)$_POST['seuil_alerte'] : null;
        $db->prepare("INSERT INTO stock_produits (nom, type_unite_id, quantite_stock, seuil_alerte, alerte_active, plateforme_commande)
                      VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([
               trim($_POST['nom']),
               (int)$_POST['type_unite_id'],
               (float)$_POST['quantite_stock'],
               $seuil,
               isset($_POST['alerte_active']) ? 1 : 0,
               trim($_POST['plateforme_commande'] ?? '') ?: null,
           ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

    // Modifier un produit
    if ($action === 'edit_produit') {
        $id    = (int)$_POST['id'];
        $seuil = $_POST['seuil_alerte'] !== '' ? (float)$_POST['seuil_alerte'] : null;
        $db->prepare("UPDATE stock_produits SET nom=?, type_unite_id=?, seuil_alerte=?, alerte_active=?, plateforme_commande=? WHERE id=?")
           ->execute([
               trim($_POST['nom']),
               (int)$_POST['type_unite_id'],
               $seuil,
               isset($_POST['alerte_active']) ? 1 : 0,
               trim($_POST['plateforme_commande'] ?? '') ?: null,
               $id,
           ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

    // Supprimer un produit
    if ($action === 'delete_produit') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM stock_produits WHERE id = ?")->execute([$id]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

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

    // Ajout de stock (inventaire)
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

    // Correction d'inventaire (remettre à zéro ou à une valeur)
    if ($action === 'inventaire') {
        $id  = (int)$_POST['produit_id'];
        $qte = max(0, (float)$_POST['quantite']);
        $stmt = $db->prepare("SELECT quantite_stock FROM stock_produits WHERE id = ?");
        $stmt->execute([$id]);
        $ancienne = (float)($stmt->fetchColumn() ?? 0);
        $db->prepare("UPDATE stock_produits SET quantite_stock = ? WHERE id = ?")
           ->execute([$qte, $id]);
        $diff = $qte - $ancienne;
        $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'inventaire', ?, ?, ?)")
           ->execute([$id, abs($diff), 'Correction inventaire : ' . stockFmt($ancienne) . ' → ' . stockFmt($qte), $auteur]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=stock');
        exit;
    }

    // Nouvelle commande
    if ($action === 'add_commande') {
        $db->prepare("INSERT INTO stock_commandes (produit_id, quantite, notes, date_commande) VALUES (?, ?, ?, ?)")
           ->execute([
               (int)$_POST['produit_id'],
               (float)$_POST['quantite'],
               trim($_POST['notes'] ?? '') ?: null,
               $_POST['date_commande'],
           ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
        exit;
    }

    // Réception commande
    if ($action === 'recu_commande') {
        $id  = (int)$_POST['id'];
        $qte = (float)$_POST['quantite_recue'];
        $stmt = $db->prepare("SELECT * FROM stock_commandes WHERE id = ?");
        $stmt->execute([$id]);
        $cmd = $stmt->fetch();
        if ($cmd && $cmd['statut'] === 'en_cours') {
            $db->prepare("UPDATE stock_commandes SET statut='recu', date_reception=CURDATE(), quantite=? WHERE id=?")
               ->execute([$qte, $id]);
            $db->prepare("UPDATE stock_produits SET quantite_stock = quantite_stock + ? WHERE id = ?")
               ->execute([$qte, $cmd['produit_id']]);
            $db->prepare("INSERT INTO stock_mouvements (produit_id, type, quantite, notes, auteur) VALUES (?, 'ajout', ?, ?, ?)")
               ->execute([$cmd['produit_id'], $qte, 'Réception commande #' . $id, $auteur]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
        exit;
    }

    // Annuler commande
    if ($action === 'annule_commande') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE stock_commandes SET statut='annule' WHERE id=? AND statut='en_cours'")
           ->execute([$id]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=commandes');
        exit;
    }

    // ── Admin only ────────────────────────────────────────────────────────────

    // Modifier les credentials
    if ($action === 'save_credentials' && $isPortalAdmin) {
        $login   = trim($_POST['stock_login_val'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        if ($login !== '') {
            if ($newPass !== '') {
                $db->prepare("UPDATE stock_credentials SET login=?, password_hash=? WHERE id=1")
                   ->execute([$login, password_hash($newPass, PASSWORD_DEFAULT)]);
            } else {
                $db->prepare("UPDATE stock_credentials SET login=? WHERE id=1")
                   ->execute([$login]);
            }
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin&saved=1');
        exit;
    }

    // Ajouter / supprimer un type d'unité
    if ($action === 'add_unite' && $isPortalAdmin) {
        $lib = trim($_POST['libelle'] ?? '');
        if ($lib !== '') {
            $db->prepare("INSERT INTO stock_types_unite (libelle, ordre) VALUES (?, (SELECT COALESCE(MAX(ordre),0)+1 FROM stock_types_unite t2))")
               ->execute([$lib]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin');
        exit;
    }
    if ($action === 'delete_unite' && $isPortalAdmin) {
        $id = (int)$_POST['id'];
        // Ne pas supprimer si des produits l'utilisent
        $used = $db->prepare("SELECT COUNT(*) FROM stock_produits WHERE type_unite_id=?");
        $used->execute([$id]);
        if ((int)$used->fetchColumn() === 0) {
            $db->prepare("DELETE FROM stock_types_unite WHERE id=?")->execute([$id]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?tab=admin');
        exit;
    }

    // Gérer les utilisateurs d'alerte
    if ($action === 'toggle_alert_user' && $isPortalAdmin) {
        $uid = (int)$_POST['user_id'];
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

// ─── Données ─────────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'stock';

$typesUnite = $db->query("SELECT * FROM stock_types_unite ORDER BY ordre ASC, libelle ASC")->fetchAll();
$typesById  = array_column($typesUnite, 'libelle', 'id');

$produits = $db->query("
    SELECT p.*, t.libelle AS unite
    FROM stock_produits p
    LEFT JOIN stock_types_unite t ON t.id = p.type_unite_id
    WHERE p.actif = 1
    ORDER BY p.nom ASC
")->fetchAll();

$commandes = $db->query("
    SELECT c.*, p.nom AS produit_nom, t.libelle AS unite
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

$nbEnCoursCmd = count(array_filter($commandes, fn($c) => $c['statut'] === 'en_cours'));

$nbAlertes = count(array_filter($produits, fn($p) =>
    $p['alerte_active'] && $p['seuil_alerte'] !== null && (float)$p['quantite_stock'] <= (float)$p['seuil_alerte']
));

// Données admin
$credentials = $db->query("SELECT login FROM stock_credentials LIMIT 1")->fetch();
$allPortalUsers = [];
$alertUserIds   = [];
if ($isPortalAdmin) {
    $allPortalUsers = $db->query("SELECT id, prenom, nom, username FROM users ORDER BY nom, prenom")->fetchAll();
    $alertUserIds   = $db->query("SELECT user_id FROM stock_alert_users")->fetchAll(PDO::FETCH_COLUMN);
}

// ─── Layout ───────────────────────────────────────────────────────────────────
// Chemin de base
$projectRootFs  = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
$scriptFs       = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']));
$relativeScript = str_replace($projectRootFs, '', $scriptFs);
$B              = rtrim(str_replace($relativeScript, '', $_SERVER['SCRIPT_NAME']), '/');

if ($isPortalUser) {
    $pageTitle = 'Gestion des fournitures';
    require_once __DIR__ . '/../../templates/header.php';
} else {
    // Standalone header
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
  <div style="background:#CF0A2C;padding:14px 28px;display:flex;align-items:center;gap:16px;margin-bottom:0;">
    <img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png"
         alt="CE" style="height:34px;">
    <span style="color:#fff;font-weight:600;font-size:1.05rem;">
      <i class="fas fa-boxes"></i> Gestion des fournitures
    </span>
    <a href="?logout=1" class="btn btn-sm ms-auto" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
  </div>
  <div class="page-content" style="padding:24px;max-width:1400px;margin:0 auto;">
    <?php } /* End standalone header */ ?>

<!-- ─── Navigation tabs ─────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'stock' ? 'active' : '' ?>" href="?tab=stock">
      <i class="fas fa-boxes"></i> Stock
      <?php if ($nbAlertes > 0): ?>
        <span class="badge bg-danger ms-1"><?= $nbAlertes ?></span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'commandes' ? 'active' : '' ?>" href="?tab=commandes">
      <i class="fas fa-shopping-cart"></i> Commandes
      <?php if ($nbEnCoursCmd > 0): ?>
        <span class="badge bg-warning text-dark ms-1"><?= $nbEnCoursCmd ?></span>
      <?php endif; ?>
    </a>
  </li>
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
</ul>

<?php // ═══════════════════════════════════════════════════════════════════════
// ── TAB : STOCK ──────────────────────────────────────────────────────────────
if ($tab === 'stock'): ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0"><i class="fas fa-boxes"></i> Produits en stock</h4>
  <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addProduitModal">
    <i class="fas fa-plus"></i> Nouveau produit
  </button>
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
        <th>Stock</th>
        <th>Seuil alerte</th>
        <th>Se commande sur</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p):
        $enAlerte = $p['alerte_active'] && $p['seuil_alerte'] !== null && (float)$p['quantite_stock'] <= (float)$p['seuil_alerte'];
    ?>
      <tr class="<?= $enAlerte ? 'table-danger' : '' ?>">
        <td>
          <strong><?= e($p['nom']) ?></strong>
          <?php if ($enAlerte): ?>
            <span class="badge bg-danger ms-1"><i class="fas fa-exclamation-triangle"></i> Alerte</span>
          <?php endif; ?>
        </td>
        <td><?= e($p['unite'] ?? '—') ?></td>
        <td>
          <span class="fw-bold <?= $enAlerte ? 'text-danger' : 'text-success' ?>">
            <?= stockFmt($p['quantite_stock']) ?>
          </span>
        </td>
        <td>
          <?php if ($p['seuil_alerte'] !== null): ?>
            <?= stockFmt($p['seuil_alerte']) ?>
            <?php if ($p['alerte_active']): ?>
              <i class="fas fa-bell text-warning ms-1" title="Alerte active"></i>
            <?php else: ?>
              <i class="fas fa-bell-slash text-muted ms-1" title="Alerte inactive"></i>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td>
          <?= $p['plateforme_commande'] ? e($p['plateforme_commande']) : '<span class="text-muted">—</span>' ?>
        </td>
        <td class="actions">
          <!-- Retrait -->
          <button class="btn btn-sm btn-outline-danger"
                  onclick="openMvt('retrait',<?= $p['id'] ?>, '<?= e(addslashes($p['nom'])) ?>', '<?= e($p['unite'] ?? '') ?>')"
                  title="Retirer du stock">
            <i class="fas fa-minus-circle"></i>
          </button>
          <!-- Ajout manuel -->
          <button class="btn btn-sm btn-outline-success"
                  onclick="openMvt('ajout',<?= $p['id'] ?>, '<?= e(addslashes($p['nom'])) ?>', '<?= e($p['unite'] ?? '') ?>')"
                  title="Ajouter au stock">
            <i class="fas fa-plus-circle"></i>
          </button>
          <!-- Correction inventaire -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="openMvt('inventaire',<?= $p['id'] ?>, '<?= e(addslashes($p['nom'])) ?>', '<?= e($p['unite'] ?? '') ?>', <?= (float)$p['quantite_stock'] ?>)"
                  title="Correction inventaire">
            <i class="fas fa-clipboard-check"></i>
          </button>
          <!-- Commander -->
          <button class="btn btn-sm btn-ce-outline"
                  onclick="openCommande(<?= $p['id'] ?>, '<?= e(addslashes($p['nom'])) ?>', '<?= e($p['unite'] ?? '') ?>')"
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
            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($produits)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Aucun produit — ajoutez-en un avec le bouton ci-dessus.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php // ── TAB : COMMANDES ───────────────────────────────────────────────────
elseif ($tab === 'commandes'): ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> Commandes</h4>
  <button class="btn btn-ce" onclick="openCommande(0,'','')">
    <i class="fas fa-plus"></i> Nouvelle commande
  </button>
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
        <td><?= stockFmt($c['quantite']) ?> <?= e($c['unite'] ?? '') ?></td>
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
                    onclick="recuCommande(<?= $c['id'] ?>, <?= (float)$c['quantite'] ?>, '<?= e(addslashes($c['produit_nom'])) ?>', '<?= e($c['unite'] ?? '') ?>')"
                    title="Marquer comme reçue">
              <i class="fas fa-check"></i> Reçue
            </button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Annuler cette commande ?')">
              <input type="hidden" name="action" value="annule_commande">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></button>
            </form>
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

<?php // ── TAB : HISTORIQUE ──────────────────────────────────────────────────
elseif ($tab === 'historique'): ?>

<h4 class="mb-3"><i class="fas fa-history"></i> Historique des mouvements</h4>

<div class="data-table-container">
  <div class="data-table-header">
    <div></div>
    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchHisto" placeholder="Rechercher..."></div>
  </div>
  <table class="data-table" id="tableHisto">
    <thead>
      <tr>
        <th>Date</th>
        <th>Produit</th>
        <th>Mouvement</th>
        <th>Quantité</th>
        <th>Notes</th>
        <th>Auteur</th>
      </tr>
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
      <tr><td colspan="6" class="text-center text-muted py-4">Aucun mouvement enregistré.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php // ── TAB : ADMIN ───────────────────────────────────────────────────────
elseif ($tab === 'admin' && $isPortalAdmin): ?>

<?php $saved = isset($_GET['saved']); ?>
<?php if ($saved): ?>
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
          Ces identifiants permettent l'accès au module fournitures sans compte portail.<br>
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
            <label class="form-label">Nouveau mot de passe <small class="text-muted">(laisser vide pour ne pas changer)</small></label>
            <input type="password" name="new_password" class="form-control" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-ce">
            <i class="fas fa-save"></i> Enregistrer
          </button>
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
              <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
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
          <i class="fas fa-info-circle"></i> Un type ne peut être supprimé que s'il n'est utilisé par aucun produit.
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
          Les utilisateurs cochés verront un badge d'alerte dans leur menu lorsqu'un produit
          passe en dessous de son seuil (si l'alerte est activée sur le produit).
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
          <?php if (empty($allPortalUsers)): ?>
            <div class="col-12"><p class="text-muted">Aucun utilisateur portail.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>
<?php endif; // End tabs ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════════════════════ -->

<!-- Modal : Nouveau produit -->
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
              <input type="number" name="seuil_alerte" class="form-control" min="0" step="0.01" placeholder="Laisser vide = pas de seuil">
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
              <input type="text" name="plateforme_commande" class="form-control" placeholder="Ex : Amazon Business, Lyreco...">
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

<!-- Modal : Modifier produit (contenu injecté par JS) -->
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
            <input type="number" name="quantite" id="mvtQte" class="form-control"
                   min="0" step="0.01" required>
          </div>
          <div class="mb-3" id="mvtNotesRow">
            <label class="form-label">Notes <small class="text-muted">(optionnel)</small></label>
            <input type="text" name="notes" class="form-control" placeholder="Raison, référence...">
          </div>
          <button type="submit" class="btn btn-ce" id="mvtSubmitBtn">Valider</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal : Commande -->
<div class="modal fade" id="commandeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-shopping-cart"></i> Passer une commande</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_commande">
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
              <label class="form-label">Quantité commandée</label>
              <input type="number" name="quantite" class="form-control" min="0.01" step="0.01" required>
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
              <button type="submit" class="btn btn-ce"><i class="fas fa-cart-plus"></i> Enregistrer la commande</button>
            </div>
          </div>
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
            <label class="form-label">Quantité effectivement reçue</label>
            <input type="number" name="quantite_recue" id="recuQte" class="form-control"
                   min="0.01" step="0.01" required>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Valider la réception
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════════════════════ -->
<script>
filterTable('searchStock',  'tableStock');
filterTable('searchCmd',    'tableCmd');
filterTable('searchHisto',  'tableHisto');

const typesUnite = <?= json_encode(array_values($typesUnite)) ?>;

// ── Mouvement de stock ────────────────────────────────────────────────────────
function openMvt(type, id, nom, unite, currentQte) {
    const cfg = {
        retrait:    { title:'Retrait de stock', action:'retrait',    btn:'btn-danger',  qteLabel:'Quantité à retirer', qteMin:0.01, qteVal:'' },
        ajout:      { title:'Ajout au stock',   action:'ajout',      btn:'btn-success', qteLabel:'Quantité à ajouter', qteMin:0.01, qteVal:'' },
        inventaire: { title:'Correction inventaire', action:'inventaire', btn:'btn-ce', qteLabel:'Nouvelle quantité totale', qteMin:0, qteVal: currentQte ?? '' },
    };
    const c = cfg[type];
    document.getElementById('mvtModalTitle').innerHTML = `<i class="fas fa-${type==='retrait'?'minus':'plus'}-circle"></i> ${c.title} — <em>${nom}</em>`;
    document.getElementById('mvtModalHeader').className = 'modal-header' + (type==='retrait' ? ' bg-danger text-white' : type==='ajout' ? ' bg-success text-white' : '');
    document.getElementById('mvtAction').value    = c.action;
    document.getElementById('mvtProduitId').value = id;
    document.getElementById('mvtQteLabel').textContent = c.qteLabel + (unite ? ` (${unite})` : '');
    document.getElementById('mvtQte').min   = c.qteMin;
    document.getElementById('mvtQte').value = c.qteVal;
    document.getElementById('mvtSubmitBtn').className = 'btn ' + c.btn;
    document.getElementById('mvtNotesRow').style.display = type === 'inventaire' ? 'none' : '';
    new bootstrap.Modal(document.getElementById('mvtModal')).show();
    setTimeout(() => document.getElementById('mvtQte').focus(), 300);
}

// ── Commande ──────────────────────────────────────────────────────────────────
function openCommande(produitId, nom, unite) {
    if (produitId) {
        document.getElementById('cmdProduitSelect').value = produitId;
    }
    new bootstrap.Modal(document.getElementById('commandeModal')).show();
}

// ── Réception commande ────────────────────────────────────────────────────────
function recuCommande(id, qte, nom, unite) {
    document.getElementById('recuId').value  = id;
    document.getElementById('recuQte').value = qte;
    document.getElementById('recuDesc').innerHTML =
        `Commande de <strong>${qte} ${unite}</strong> — <em>${nom}</em><br>
         Confirmez ou ajustez la quantité réellement reçue.`;
    new bootstrap.Modal(document.getElementById('recuModal')).show();
    setTimeout(() => document.getElementById('recuQte').focus(), 300);
}

// ── Modifier produit ──────────────────────────────────────────────────────────
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
              <input type="number" name="seuil_alerte" class="form-control"
                     min="0" step="0.01"
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
            <div class="col-12">
              <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
          </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editProduitModal')).show();
}
</script>

<?php
// ─── Fermeture layout ─────────────────────────────────────────────────────────
if ($isPortalUser) {
    require_once __DIR__ . '/../../templates/footer.php';
} else {
    echo '</div></body></html>';
}
