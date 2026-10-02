<?php
/**
 * Gabarit commun des pages publiques /tools (aucune connexion, aucune donnée enregistrée).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

function toolsHeader($title, $key = null, $extraHead = '') {
    $GLOBALS['toolsCurrentKey'] = $key;
    if ($key !== null) requirePublicTool($key);
    elseif (empty($GLOBALS['toolsNoGate'])) requireToolsAccess();
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background:#f5f5f5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        .tools-header { background:linear-gradient(135deg,#e4002b 0%,#c40025 100%); color:#fff; padding:20px 0; }
        .privacy-banner { background:#e8f5e9; border:2px solid #2e7d32; color:#1b5e20; border-radius:10px; padding:14px 18px; }
        .privacy-banner i { font-size:1.6rem; }
        @media print { .no-print { display:none !important; } }
    </style>
    <?= $extraHead ?>
</head>
<body>
<div class="tools-header no-print">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="h3 mb-0"><?= e($title) ?></h1>
        <?php if ($key !== null): ?>
        <a href="./" class="btn btn-sm btn-light"><i class="fas fa-th-large me-1"></i>Tous les outils</a>
        <?php endif; ?>
    </div>
</div>
<?php }

function privacyBanner($text = null) { ?>
<div class="privacy-banner d-flex align-items-center gap-3 my-3 no-print">
    <i class="fas fa-user-shield"></i>
    <div><strong>Aucune donnée n'est enregistrée.</strong>
        <?= $text ?? 'Ce qui est saisi sur cette page reste dans votre navigateur et disparaît quand vous la fermez ou l\'actualisez.' ?></div>
</div>
<?php }

function toolsFooter() {
    $key = $GLOBALS['toolsCurrentKey'] ?? null;
    if (!empty($GLOBALS['toolsNoFeedbackLink'])) { echo "</body>\n</html>\n"; return; } ?>
<div class="container text-center text-muted small my-4 no-print">
    <a href="feedback.php<?= $key ? '?tool=' . urlencode($key) : '' ?>" class="text-muted"><i class="fas fa-comment-dots me-1"></i>Un problème, une idée ? Envoyer un retour à l'administrateur</a>
</div>
</body>
</html>
<?php }
