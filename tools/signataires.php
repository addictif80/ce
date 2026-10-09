<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Qui peut signer quoi ?', 'signataires');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Consultation sans enregistrement : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/signataires/ui.php'; ?>
</div>
<?php toolsFooter();
