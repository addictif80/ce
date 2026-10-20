<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Recherche service-public.fr', 'servicepublic');
$spEndpoint = 'sp_search.php';
?>
<div class="container my-3">
    <?php privacyBanner('Votre question est traitée sur ce portail puis, pour rédiger le résumé, transmise à un service externe de rédaction : n\'y saisissez aucune donnée personnelle de client.') ?>
    <?php require __DIR__ . '/../modules/servicepublic/ui.php'; ?>
</div>
<?php toolsFooter();
