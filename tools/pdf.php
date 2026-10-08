<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Boîte à outils PDF', 'pdf');
$capSave = false;
?>
<div class="container my-3">
    <?php privacyBanner('Le traitement des PDF se fait dans votre navigateur : vos fichiers ne sont ni envoyés ni conservés.') ?>
    <?php require __DIR__ . '/../modules/pdf/ui.php'; ?>
</div>
<?php toolsFooter();
