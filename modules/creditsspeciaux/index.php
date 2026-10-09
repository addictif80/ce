<?php
$pageTitle = 'Crédits spéciaux';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('creditsspeciaux');
$saved = simList('creditsspeciaux');
$capLoad = simLoad($saved);
$capSave = true;
$planZonageUrl = '../../tools/zonage.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-gift"></i> Crédits spéciaux</h4>
    <p class="text-muted mb-0">Éligibilité et montant du PTZ, du Doublissimo, du Primo Jeune 0 %, du Primoz et du Grandioz pour un projet et des emprunteurs donnés. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['PTZ', fn($r) => isset($r['ptz']) ? simEur($r['ptz']) : '–'], ['Doublissimo', fn($r) => isset($r['dbl']) ? simEur($r['dbl']) : '–'], ['Primo Jeune', fn($r) => isset($r['pj']) ? simEur($r['pj']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
