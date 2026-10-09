<?php
$pageTitle = 'Calculateur de dates';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('dates');
$saved = simList('dates');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-calendar-days"></i> Calculateur de dates</h4>
    <p class="text-muted mb-0">Ajoutez des jours ouvrés, ouvrables ou calendaires, mesurez un écart et repérez les délais usuels, puis enregistrez le calcul.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Calcul enregistré.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Date de départ', fn($r) => !empty($r['depart']) ? date('d/m/Y', strtotime($r['depart'])) : '–'], ['Résultat', fn($r) => $r['date'] ?? '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
