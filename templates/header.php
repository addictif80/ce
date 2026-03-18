<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$currentUser = getCurrentUser();
$liensExternes = getLiensExternes();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Calcul du chemin de base pour les URLs (fonctionne depuis n'importe quel sous-dossier)
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
// Remonter au dossier racine du projet si on est dans un module
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$rootDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$baseUrl = rtrim(str_replace($docRoot, '', $rootDir), '/');
if ($baseUrl === '') $baseUrl = '';
$B = $baseUrl; // raccourci
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
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2" alt="CE">
            <h3>Gestion d'Activités</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= $B ?>/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><i class="fas fa-home"></i> Accueil</a>

            <div class="nav-section">Mon activité</div>
            <a href="<?= $B ?>/modules/instances/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'instances') !== false ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Mes instances</a>
            <a href="<?= $B ?>/modules/rappels/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'rappels') !== false ? 'active' : '' ?>"><i class="fas fa-phone-alt"></i> Demandes de rappel</a>
            <a href="<?= $B ?>/modules/demandes_clients/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'demandes_clients') !== false ? 'active' : '' ?>"><i class="fas fa-headset"></i> Suivi demandes clients</a>
            <a href="<?= $B ?>/modules/offres/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'offres') !== false ? 'active' : '' ?>"><i class="fas fa-tags"></i> Offres en cours</a>
            <a href="<?= $B ?>/modules/instances/calendrier.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'instances/calendrier') !== false ? 'active' : '' ?>"><i class="fas fa-calendar"></i> Calendrier instances</a>

            <div class="nav-section">Formation</div>
            <a href="<?= $B ?>/modules/formations/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'formations/index') !== false || (strpos($_SERVER['REQUEST_URI'], 'formations') !== false && strpos($_SERVER['REQUEST_URI'], 'calendrier') === false && strpos($_SERVER['REQUEST_URI'], 'caldav') === false) ? 'active' : '' ?>"><i class="fas fa-graduation-cap"></i> Formations</a>
            <a href="<?= $B ?>/modules/formations/calendrier.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'formations/calendrier') !== false ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Calendrier formations</a>

            <div class="nav-section">Commercial</div>
            <a href="<?= $B ?>/modules/production/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'production') !== false ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Suivi production</a>
            <a href="<?= $B ?>/modules/phoning/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'phoning') !== false ? 'active' : '' ?>"><i class="fas fa-phone-volume"></i> Séances phoning</a>
            <a href="<?= $B ?>/modules/eai/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'eai') !== false ? 'active' : '' ?>"><i class="fas fa-bullseye"></i> EAI</a>

            <div class="nav-section">Outils</div>
            <a href="<?= $B ?>/modules/credit_immo/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'credit_immo') !== false ? 'active' : '' ?>"><i class="fas fa-house-chimney"></i> Crédit immobilier</a>
            <a href="<?= $B ?>/modules/calculateur/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'calculateur') !== false ? 'active' : '' ?>"><i class="fas fa-calculator"></i> Calculateur budget</a>
            <a href="<?= $B ?>/modules/courriers/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'courriers') !== false ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Générateur courriers</a>
            <a href="<?= $B ?>/modules/blocnotes/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'blocnotes') !== false ? 'active' : '' ?>"><i class="fas fa-sticky-note"></i> Bloc-notes</a>
            <a href="<?= $B ?>/modules/procedures/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'procedures') !== false ? 'active' : '' ?>"><i class="fas fa-book"></i> Procédures</a>

            <div class="nav-section">Références</div>
            <a href="<?= $B ?>/modules/codes/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], '/codes/') !== false ? 'active' : '' ?>"><i class="fas fa-key"></i> Codes utiles</a>
            <a href="<?= $B ?>/modules/contacts/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], '/contacts/') !== false ? 'active' : '' ?>"><i class="fas fa-address-book"></i> Contacts utiles</a>

            <?php if (!empty($liensExternes)): ?>
                <div class="nav-section">Liens</div>
                <?php foreach ($liensExternes as $lien): ?>
                    <a href="<?= e($lien['url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> <?= e($lien['nom']) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="nav-divider"></div>
            <?php if (isAdmin()): ?>
                <a href="<?= $B ?>/modules/admin/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : '' ?>"><i class="fas fa-cog"></i> Administration</a>
            <?php endif; ?>
            <a href="<?= $B ?>/modules/profil/index.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'profil') !== false ? 'active' : '' ?>"><i class="fas fa-user"></i> Mon profil</a>
            <a href="<?= $B ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </nav>
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
