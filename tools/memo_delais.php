<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Mémo : délais légaux', 'memo_delais');
$memoKey = 'memo_delais';
?>
<div class="container my-3">
    <?php privacyBanner('Consultation sans enregistrement : rien n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/memos/ui.php'; ?>
</div>
<?php toolsFooter();
