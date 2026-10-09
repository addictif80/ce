<?php
$pageTitle = 'Épargne ou crédit ?';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('epargnecredit');
$saved = simList('epargnecredit');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-code-compare"></i> Épargne ou crédit ?</h4>
    <p class="text-muted mb-0">Comparez le coût d\'un financement par votre épargne, par un crédit ou par un mélange des deux. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Scénario le plus avantageux', fn($r) => $r['meilleur'] ?? '–'], ['Écart (tout crédit vs tout épargne)', fn($r) => isset($r['ecart']) ? simEur($r['ecart']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
