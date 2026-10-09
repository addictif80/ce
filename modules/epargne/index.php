<?php
$pageTitle = 'Simulateur d\'épargne';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('epargne');
$saved = simList('epargne');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-piggy-bank"></i> Simulateur d\'épargne</h4>
    <p class="text-muted mb-0">Estimez le capital constitué, le versement ou la durée nécessaire pour atteindre un objectif d\'épargne. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Capital net', fn($r) => isset($r['net']) ? simEur($r['net']) : '–'], ['Total versé', fn($r) => isset($r['paid']) ? simEur($r['paid']) : '–'], ['Durée', fn($r) => isset($r['ans']) ? $r['ans'] . ' ans' : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
