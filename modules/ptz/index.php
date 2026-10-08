<?php
$pageTitle = 'Simulateur PTZ';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
require_once __DIR__ . '/bareme.php';
simEnsureSchema();
simHandlePost('ptz');

$saved = simList('ptz');
$capLoad = simLoad($saved);
$capSave = true;
$ptzBareme = ptzGetBareme();
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-percent"></i> Simulateur PTZ</h4>
    <p class="text-muted mb-0">Vérifiez l'éligibilité au Prêt à Taux Zéro et estimez son montant, puis enregistrez la simulation.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Éligible', fn($r) => isset($r['eligible']) ? ($r['eligible'] ? 'Oui' : 'Non') : '–'], ['Tranche', fn($r) => $r['tranche'] ?? '–'], ['PTZ estimé', fn($r) => isset($r['ptz']) ? simEur($r['ptz']) : '–']]); ?>

<?php if (isAdmin()): ?><p class="small text-muted mt-3"><i class="fas fa-gear"></i> Le barème se modifie dans <a href="../admin/index.php?tab=baremes">Administration › Barèmes</a>.</p><?php endif; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
