<?php
$pageTitle = 'Parcours événements de vie';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('evenements');
$saved = simList('evenements');
$capLoad = simLoad($saved);
$capSave = true;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-route"></i> Parcours événements de vie</h4>
    <p class="text-muted mb-0">Pour chaque événement (naissance, mariage, décès, retraite…), les démarches, les solutions à proposer et les pièces à demander, avec une liste à cocher. Enregistrez la liste d'un client pour la reprendre plus tard.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Liste enregistrée.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Événement', fn($r) => $r['evenement'] ?? '–'], ['Avancement', fn($r) => isset($r['fait'], $r['total']) ? $r['fait'] . ' / ' . $r['total'] : '–']]); ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
