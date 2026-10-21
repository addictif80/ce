<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Droits de succession et de donation', 'succession');
$capSave = false;
$capLoad = null;
?>
<div class="container-fluid my-3 px-lg-4">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien de ce que vous saisissez n\'est envoyé ni conservé par cette page.') ?>
    <?php require __DIR__ . '/../modules/succession/ui.php'; ?>
</div>
<?php toolsFooter();
