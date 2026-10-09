<?php
$pageTitle = 'Comparateur de scénarios';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('scenarios');
$saved = simList('scenarios');
$capLoad = simLoad($saved);
$capSave = true;
$planZonageUrl = '../../tools/zonage.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-code-compare"></i> Comparateur de scénarios</h4>
    <p class="text-muted mb-0">Comparez jusqu\'à trois montages de financement côte à côte (apport, durée, prêts aidés et spéciaux). Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Mensualité 1', fn($r) => isset($r['mensualites'][0]) ? simEur($r['mensualites'][0], 2) : '–'], ['Mensualité 2', fn($r) => isset($r['mensualites'][1]) ? simEur($r['mensualites'][1], 2) : '–'], ['Mensualité 3', fn($r) => isset($r['mensualites'][2]) ? simEur($r['mensualites'][2], 2) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
