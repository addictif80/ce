<?php
$pageTitle = 'Simulateur d\'assurance-vie';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('assurancevie');
$saved = simList('assurancevie');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-shield-heart"></i> Simulateur d\'assurance-vie</h4>
    <p class="text-muted mb-0">Projetez un contrat, estimez la fiscalité d\'un rachat et du capital décès. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Valeur projetée', fn($r) => isset($r['valeur']) ? simEur($r['valeur']) : '–'], ['Rachat net', fn($r) => isset($r['rachat_net']) ? simEur($r['rachat_net']) : '–'], ['Prélèvement décès', fn($r) => isset($r['deces']) ? simEur($r['deces']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
