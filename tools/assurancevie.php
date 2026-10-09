<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Simulateur d\'assurance-vie', 'assurancevie');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/assurancevie/ui.php'; ?>
</div>
<?php toolsFooter();
