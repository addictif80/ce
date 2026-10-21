<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Assistant IA', 'assistant_ia');
$iaEndpoint = 'ia_chat.php';
?>
<div class="container my-3">
    <?php privacyBanner('Vos messages sont transmis à un service externe pour générer la réponse ; la conversation reste dans votre navigateur. N\'y saisissez aucune donnée personnelle de client.') ?>
    <?php require __DIR__ . '/../modules/assistant_ia/ui.php'; ?>
</div>
<?php toolsFooter();
