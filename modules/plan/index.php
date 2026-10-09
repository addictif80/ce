<?php
$pageTitle = 'Plan de financement complet';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('plan');
$saved = simList('plan');
$capLoad = simLoad($saved);
$capSave = true;
$planZonageUrl = '../../tools/zonage.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-diagram-project"></i> Plan de financement complet</h4>
    <p class="text-muted mb-0">Montez le financement d\'un projet : PTZ, prêts spéciaux et prêt principal, avec mensualité, endettement, reste à vivre et TAEG. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Mensualité', fn($r) => isset($r['mensualite']) ? simEur($r['mensualite'], 2) : '–'], ['Endettement', fn($r) => isset($r['endettement']) ? number_format($r['endettement'], 2, ',', ' ') . ' %' : '–'], ['Capital', fn($r) => isset($r['capital']) ? simEur($r['capital']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
