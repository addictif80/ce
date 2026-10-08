<?php
$pageTitle = 'Pièces justificatives';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('pieces');
$saved = simList('pieces');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-list-check"></i> Pièces justificatives</h4>
    <p class="text-muted mb-0">Listez les pièces à demander au client, cochez celles reçues et enregistrez le suivi avec une date de relance.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Suivi enregistré.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [
    ['Demande', fn($r) => $r['modele'] ?? '–'],
    ['Pièces reçues', fn($r) => isset($r['total']) ? ($r['recues'] ?? 0) . ' / ' . $r['total'] : '–'],
    ['Relance', fn($r) => !empty($r['relance']) ? (date('d/m/Y', strtotime($r['relance'])) . ((($r['recues'] ?? 0) < ($r['total'] ?? 0) && $r['relance'] <= date('Y-m-d')) ? ' ⚠ à relancer' : '')) : '–'],
]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
