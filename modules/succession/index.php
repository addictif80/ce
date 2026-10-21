<?php
$pageTitle = 'Droits de succession';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('succession');
$saved = simList('succession');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-hand-holding-heart"></i> Droits de succession et de donation</h4>
    <p class="text-muted mb-0">Estimez les droits dus par chaque héritier (dévolution légale ou testament, assurance-vie, démembrement, donations antérieures) et mesurez l'intérêt d'une donation. Enregistrez la simulation pour la retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Actif net', fn($r) => isset($r['actif']) ? simEur($r['actif']) : '–'], ['Droits', fn($r) => isset($r['droits']) ? simEur($r['droits']) : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
