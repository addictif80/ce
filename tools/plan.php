<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Plan de financement complet', 'plan');
$capSave = false;
$capLoad = null;
$planZonageUrl = 'zonage.php';
?>
<div class="container-fluid my-3 px-lg-4">
    <?php privacyBanner('Les calculs se font dans votre navigateur. Seule la liste des communes d\'un département est demandée à ce portail : rien de ce que vous saisissez n\'est conservé.') ?>
    <?php require __DIR__ . '/../modules/plan/ui.php'; ?>
</div>
<?php toolsFooter();
