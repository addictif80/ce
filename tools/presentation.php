<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Présentation', 'presentation');
$docMode = 'tools'; $docInit = null; $docApi = 'api.php';
?>
<div class="container-fluid my-3 px-lg-4">
    <?php privacyBanner('Votre document reste dans votre navigateur : il n\'est ni envoyé ni conservé sur le serveur. Utilisez « Enregistrer sur mon ordinateur » ou un export pour le garder.') ?>
    <?php require __DIR__ . '/../modules/documents/slides_ui.php'; ?>
</div>
<?php toolsFooter();
