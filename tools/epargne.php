<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Simulateur d\'épargne', 'epargne');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/epargne/ui.php'; ?>
</div>
<?php toolsFooter();
