<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Fiche de production à imprimer', 'fiche_production');
$ficheTypes = ['production'];
?>
<div class="container-fluid my-3 px-lg-4">
    <?php privacyBanner('La feuille se prépare dans votre navigateur : rien de ce que vous saisissez n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/fiches/ui.php'; ?>
</div>
<?php toolsFooter();
