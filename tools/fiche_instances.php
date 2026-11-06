<?php
require_once __DIR__ . '/_layout.php';
toolsHeader("Fiche d'instances à imprimer", 'fiche_instances');
$ficheTypes = ['instances'];
?>
<div class="container-fluid my-3 px-lg-4">
    <?php privacyBanner('La feuille se prépare dans votre navigateur : rien de ce que vous saisissez n\'est envoyé ni conservé.') ?>
    <?php require __DIR__ . '/../modules/fiches/ui.php'; ?>
</div>
<?php toolsFooter();
