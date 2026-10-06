<?php
$pageTitle = 'Vérification RGE';
require_once __DIR__ . '/../../templates/header.php';
$rgeApiUrl = 'api.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-certificate"></i> Vérification de certification RGE</h4>
    <p class="text-muted mb-0">Vérifiez si une entreprise est reconnue RGE (Reconnu Garant de l'Environnement), par nom, SIREN ou SIRET.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
