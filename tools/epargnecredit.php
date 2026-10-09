<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Épargne ou crédit ?', 'epargnecredit');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/epargnecredit/ui.php'; ?>
</div>
<?php toolsFooter();
