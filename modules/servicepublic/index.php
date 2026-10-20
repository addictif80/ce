<?php
$pageTitle = 'Recherche service-public.fr';
require_once __DIR__ . '/../../templates/header.php';
$spEndpoint = '../../tools/sp_search.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-magnifying-glass"></i> Recherche service-public.fr</h4>
    <p class="text-muted mb-0">Retrouvez l'information officielle (fiches pratiques « particuliers ») et obtenez un résumé sourcé.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
