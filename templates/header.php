<?php
ob_start(); // Buffer la sortie pour que header() fonctionne même après du HTML
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$currentUser = getCurrentUser();
$liensExternes = getLiensExternes();
$menuConfig = getMenuConfig();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Calcul du chemin de base pour les URLs (fonctionne depuis n'importe quel sous-dossier)
$projectRootFs = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$scriptFs = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']));
$relativeScript = str_replace($projectRootFs, '', $scriptFs);
$B = rtrim(str_replace($relativeScript, '', $_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Accueil') ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $B ?>/assets/css/style.css" rel="stylesheet">
    <?php if (isset($extraCss)): foreach((array)$extraCss as $css): ?>
        <link href="<?= $css ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filterTable(inputId, tableId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    }
    function toggleStatus(url, id, field, cb) {
        const value = cb.checked ? 1 : 0;
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&field=' + field + '&value=' + value
        }).then(r => r.json()).then(data => {
            if (!data.success) { cb.checked = !cb.checked; alert('Erreur'); }
            else { location.reload(); }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.sidebar-nav .nav-section').forEach(function(section) {
            section.addEventListener('click', function() {
                var group = this.nextElementSibling;
                if (!group || !group.classList.contains('nav-group')) return;
                if (this.classList.contains('open')) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
        });
    });
    </script>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png" alt="Caisse d'Épargne Midi-Pyrénées" style="max-width:180px;">
            <h3>Gestion d'Activités</h3>
        </div>
        <?php
        // Helper: check if URI matches any pattern in the list
        $uri = $_SERVER['REQUEST_URI'];
        function uriMatch($patterns) {
            global $uri;
            foreach ((array)$patterns as $p) {
                if (strpos($uri, $p) !== false) return true;
            }
            return false;
        }
        ?>
        <nav class="sidebar-nav">
            <a href="<?= $B ?>/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><i class="fas fa-home"></i> Accueil</a>

            <?php foreach ($menuConfig as $section):
                $patterns = array_map('trim', explode(',', $section['uri_patterns'] ?? ''));
                $open = uriMatch($patterns);
            ?>
            <div class="nav-section <?= $open ? 'open' : '' ?>" onclick="this.classList.toggle('open')">
                <span><?= e($section['label']) ?></span><i class="fas fa-chevron-right nav-chevron"></i>
            </div>
            <div class="nav-group" <?= $open ? '' : 'style="display:none"' ?>>
                <?php foreach ($section['items'] as $item):
                    $itemPatterns = array_map('trim', explode(',', $item['uri_patterns'] ?? ''));
                    $isActive = uriMatch($itemPatterns);
                    // Exclude sub-pages (calendrier) from parent match
                    if ($item['item_key'] === 'formations' && uriMatch(['formations/calendrier','caldav'])) $isActive = false;
                    if ($item['item_key'] === 'instances' && uriMatch('instances/calendrier')) $isActive = false;
                ?>
                    <a href="<?= $B . e($item['url']) ?>" class="<?= $isActive ? 'active' : '' ?>"><i class="fas <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <?php if (!empty($liensExternes)):
                // Séparateur entre pages internes et liens externes
                // Trouver le dernier lien ajouté
                $dernierLien = null;
                foreach ($liensExternes as $lien) {
                    if (!$dernierLien || ($lien['created_at'] ?? '') > ($dernierLien['created_at'] ?? '')) {
                        $dernierLien = $lien;
                    }
                }
            ?>
                <div class="nav-divider"></div>
                <?php if ($dernierLien): ?>
                    <div class="sidebar-liens-info">
                        <i class="fas fa-clock"></i> Dernier lien : <strong><?= e(excerpt($dernierLien['nom'], 20)) ?></strong>
                        <?php if (!empty($dernierLien['categorie_nom'])): ?>
                            dans <em><?= e($dernierLien['categorie_nom']) ?></em>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php
                // Grouper les liens par catégorie
                $liensParCategorie = [];
                foreach ($liensExternes as $lien) {
                    $cat = $lien['categorie_nom'] ?? null;
                    $liensParCategorie[$cat ?? ''][] = $lien;
                }
                if (count($liensParCategorie) === 1 && array_key_exists('', $liensParCategorie)):
                    // Pas de catégories, afficher comme avant
            ?>
                <div class="nav-section" onclick="this.classList.toggle('open')">
                    <span>Liens</span><i class="fas fa-chevron-right nav-chevron"></i>
                </div>
                <div class="nav-group" style="display:none">
                    <?php foreach ($liensExternes as $lien): ?>
                        <a href="<?= e($lien['url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> <?= e($lien['nom']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php else:
                    // Liens groupés par catégorie
                    foreach ($liensParCategorie as $catNom => $catLiens):
                        $labelCat = $catNom ?: 'Liens';
            ?>
                <div class="nav-section" onclick="this.classList.toggle('open')">
                    <span><?= e($labelCat) ?></span><i class="fas fa-chevron-right nav-chevron"></i>
                </div>
                <div class="nav-group" style="display:none">
                    <?php foreach ($catLiens as $lien): ?>
                        <a href="<?= e($lien['url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> <?= e($lien['nom']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; endif; endif; ?>

            <div class="nav-divider"></div>
            <?php if (isAdmin()): ?>
                <a href="<?= $B ?>/modules/admin/index.php" class="<?= uriMatch('admin') ? 'active' : '' ?>"><i class="fas fa-cog"></i> Administration</a>
            <?php endif; ?>
            <a href="<?= $B ?>/modules/profil/index.php" class="<?= uriMatch('profil') ? 'active' : '' ?>"><i class="fas fa-user"></i> Mon profil</a>
            <a href="<?= $B ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </nav>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></div>
            <?php if (!empty($currentUser['email_pro'])): ?>
                <div class="sidebar-user-detail"><?= e($currentUser['email_pro']) ?></div>
            <?php endif; ?>
            <?php if (!empty($currentUser['tel_pro'])): ?>
                <div class="sidebar-user-detail"><?= e($currentUser['tel_pro']) ?></div>
            <?php endif; ?>
            <?php if (!empty($currentUser['ligne_interne'])): ?>
                <div class="sidebar-user-detail">Ligne : <?= e($currentUser['ligne_interne']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('active')">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?= e($pageTitle ?? 'Accueil') ?></h1>
            </div>
            <div class="topbar-right">
                <?php if ($currentPage === 'index'): ?>
                <form class="topbar-search" method="GET" action="<?= $B ?>/index.php">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Rechercher partout..." value="<?= e($_GET['q'] ?? '') ?>">
                </form>
                <?php endif; ?>
                <span class="text-muted" style="font-size:13px;">
                    <i class="fas fa-user-circle"></i> <?= e($currentUser['prenom'] . ' ' . $currentUser['nom']) ?>
                </span>
            </div>
        </div>
        <div class="page-content">
