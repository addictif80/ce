<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Guide de la fiscalité bancaire', 'guide_fiscal');
?>
<div class="container my-3">
    <?php privacyBanner('Consultation sans enregistrement : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/guide_fiscal/ui.php'; ?>
</div>
<?php toolsFooter();
