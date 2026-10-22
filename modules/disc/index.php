<?php
$pageTitle = 'Test DISC';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('disc');
$saved = simList('disc');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-user-gear"></i> Test DISC</h4>
    <p class="text-muted mb-0">Découvrez votre style de comportement (Dominant, Influent, Stable, Consciencieux) et adaptez votre communication à vos collègues et à vos clients. Enregistrez votre résultat pour le retrouver.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Résultat enregistré.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Profil', fn($r) => $r['profil'] ?? '–'], ['D', fn($r) => $r['D'] ?? '–'], ['I', fn($r) => $r['I'] ?? '–'], ['S', fn($r) => $r['S'] ?? '–'], ['C', fn($r) => $r['C'] ?? '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
