<?php
$pageTitle = 'Quotité saisissable';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('saisie');
$saved = simList('saisie');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-gavel"></i> Quotité saisissable</h4>
    <p class="text-muted mb-0">Calculez la part saisissable d'une rémunération et le solde bancaire insaisissable, puis enregistrez le calcul.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Calcul enregistré.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Rémunération', fn($r) => isset($r['rev']) ? simEur($r['rev'], 2) : '–'], ['Part saisissable', fn($r) => isset($r['saisissable']) ? simEur($r['saisissable'], 2) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
