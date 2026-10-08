<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Pièces justificatives', 'pieces');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('La liste se construit dans votre navigateur : les cases cochées et le nom du client ne sont ni envoyés ni conservés.') ?>
    <?php require __DIR__ . '/../modules/pieces/ui.php'; ?>
</div>
<?php toolsFooter();
