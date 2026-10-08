<?php
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../modules/ptz/bareme.php';
toolsHeader('Simulateur PTZ', 'ptz');
$capSave = false;
$ptzBareme = ptzGetBareme();
$ptzZonageUrl = 'zonage.php';
?>
<div class="container my-3">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/ptz/ui.php'; ?>
</div>
<?php toolsFooter();
