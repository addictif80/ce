<?php
$pageTitle = 'Frais de notaire';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('notaire');
$saved = simList('notaire');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-scale-balanced"></i> Frais de notaire</h4>
    <p class="text-muted mb-0">Estimez les frais d'acquisition d'un bien (ancien ou neuf), puis enregistrez la simulation.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php if ($capLoad): ?><div class="mb-3"><?= simDossierButton($capLoad, true) ?></div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Prix', fn($r) => isset($r['prix']) ? simEur($r['prix']) : '–'], ['Frais estimés', fn($r) => isset($r['frais']) ? simEur($r['frais'], 2) : '–'], ['% du prix', fn($r) => isset($r['pct']) ? number_format($r['pct'], 2, ',', ' ') . ' %' : '–']], fn($s) => simDossierButton($s)); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
