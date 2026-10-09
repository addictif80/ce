<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Taux d\'usure', 'usure');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Consultation sans enregistrement : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/usure/ui.php'; ?>
</div>
<?php toolsFooter();
