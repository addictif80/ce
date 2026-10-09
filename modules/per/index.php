<?php
$pageTitle = 'Simulateur PER';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('per');
$saved = simList('per');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-umbrella-beach"></i> Simulateur PER</h4>
    <p class="text-muted mb-0">Estimez l\'économie d\'impôt d\'un versement sur un plan d\'épargne retraite. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Versement', fn($r) => isset($r['versement']) ? simEur($r['versement']) : '–'], ['Économie d\'impôt', fn($r) => isset($r['economie']) ? simEur($r['economie']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
