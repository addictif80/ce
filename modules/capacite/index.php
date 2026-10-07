<?php
$pageTitle = 'Capacité d\'emprunt';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('capacite');
$saved = simList('capacite');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-hand-holding-dollar"></i> Capacité d'emprunt</h4>
    <p class="text-muted mb-0">Estimez le capital et le budget d'achat d'un client, puis enregistrez la simulation.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Capital', fn($r) => isset($r['capital']) ? simEur($r['capital']) : '–'], ['Budget d\'achat', fn($r) => isset($r['budget']) ? simEur($r['budget']) : '–'], ['Mensualité max', fn($r) => isset($r['mensualite']) ? simEur($r['mensualite'], 2) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
