<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Bureau domiciliaire', 'bureau_dom', '<link href="../assets/css/style.css" rel="stylesheet">');
?>
<div class="container my-3">
    <div class="no-print"><?php privacyBanner('Le formulaire est rempli dans votre navigateur : rien n\'est envoyé ni sauvegardé, et tout est effacé si vous actualisez la page.') ?></div>
    <?php require __DIR__ . '/../modules/bureau_dom/form.php'; ?>
</div>
<?php toolsFooter();
