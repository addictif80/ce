<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Vérification RGE', 'rge');
$rgeApiUrl = '../modules/rge/api.php';
?>
<div class="container my-3">
    <?php privacyBanner('Le nom ou le numéro saisi est transmis à l\'API publique de l\'ADEME pour la recherche, sans être enregistré par ce portail.') ?>
    <?php require __DIR__ . '/../modules/rge/ui.php'; ?>
</div>
<?php toolsFooter();
