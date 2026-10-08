<?php
$pageTitle = 'Prêt relais et rachat de crédits';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
simEnsureSchema();
simHandlePost('rachat');
$saved = simList('rachat');
$capLoad = simLoad($saved);
$capSave = true;
$loadMode = $capLoad ? ((json_decode($capLoad['params'] ?? '{}', true)['mode'] ?? 'relais')) : 'relais';
$loadRelais = $loadMode === 'relais' ? $capLoad : null;
$loadRachat = $loadMode === 'rachat' ? $capLoad : null;
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-arrows-rotate"></i> Prêt relais et rachat de crédits</h4>
    <p class="text-muted mb-0">Simulez un prêt relais (vente d'un bien avant l'achat du suivant) ou un regroupement de crédits avec indemnités de remboursement anticipé.</p>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Simulation enregistrée.</div><?php endif; ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $loadMode === 'rachat' ? '' : 'active' ?>" data-bs-toggle="tab" href="#tRelais" id="tabRelais"><i class="fas fa-house-circle-check me-1"></i>Prêt relais</a></li>
    <li class="nav-item"><a class="nav-link <?= $loadMode === 'rachat' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tRachat" id="tabRachat"><i class="fas fa-layer-group me-1"></i>Rachat de crédits</a></li>
</ul>
<?php require __DIR__ . '/../_sim/actions.php'; $simBarDone = true; ?>
<div class="tab-content">
<div class="tab-pane fade <?= $loadMode === 'rachat' ? '' : 'show active' ?>" id="tRelais"><?php $capLoad = $loadRelais; require __DIR__ . '/ui_relais.php'; ?></div>
<div class="tab-pane fade <?= $loadMode === 'rachat' ? 'show active' : '' ?>" id="tRachat"><?php $capLoad = $loadRachat; require __DIR__ . '/ui_rachat.php'; ?></div>
</div>

<?php simTable($saved, [['Type', fn($r) => ($r['mode'] ?? '') === 'rachat' ? 'Rachat' : (($r['mode'] ?? '') === 'relais' ? 'Prêt relais' : '–')], ['Montant', fn($r) => isset($r['montant']) ? simEur($r['montant']) : '–'], ['Résultat clé', fn($r) => $r['cle'] ?? '–']]); ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
