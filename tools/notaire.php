<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Frais de notaire', 'notaire');
$capSave = false;
?>
<div class="container my-3">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/notaire/ui.php'; ?>
</div>
<?php toolsFooter();
