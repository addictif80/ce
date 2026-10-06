<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Calculateur de budget', 'calculateur');
$budgetSave = false;
$budget = null;
$avec_conjoint = 0;
?>
<div class="container my-3">
    <?php privacyBanner('Les montants saisis ne quittent pas votre navigateur : ils ne sont ni envoyés, ni sauvegardés, et disparaissent si vous actualisez la page.') ?>
    <?php require __DIR__ . '/../modules/calculateur/ui.php'; ?>
</div>
<?php toolsFooter();
