<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Validateurs de numéros', 'validateurs');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Les numéros saisis restent dans votre navigateur : ils ne sont ni envoyés ni conservés.') ?>
    <?php require __DIR__ . '/../modules/validateurs/ui.php'; ?>
</div>
<?php toolsFooter();
