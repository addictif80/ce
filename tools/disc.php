<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Test DISC', 'disc');
$capSave = false;
$capLoad = null;
?>
<div class="container my-3">
    <?php privacyBanner('Vos réponses restent dans votre navigateur : rien n\'est envoyé ni conservé par cette page. Le lien de partage contient vos réponses : ne le transmettez qu\'à qui vous le souhaitez.') ?>
    <?php require __DIR__ . '/../modules/disc/ui.php'; ?>
</div>
<?php toolsFooter();
