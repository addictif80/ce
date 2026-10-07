<?php
$pageTitle = 'Simulateur PTZ';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/simulations.php';
require_once __DIR__ . '/bareme.php';
simEnsureSchema();
simHandlePost('ptz');

// Mise à jour du barème (administrateurs uniquement)
$baremeMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bareme' && isAdmin()) {
    $b = json_decode($_POST['bareme'] ?? '', true);
    $baremeMsg = ptzCheckBareme($b);
    if (!$baremeMsg) {
        $b['valide'] = isset($_POST['valide']);
        ptzEnsureSchema();
        getDB()->prepare("REPLACE INTO ptz_bareme (id, data, updated_by) VALUES (1, ?, ?)")->execute([json_encode($b, JSON_UNESCAPED_UNICODE), getCurrentUserId()]);
        header('Location: index.php?bareme=1');
        exit;
    }
}

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
<?php if (isset($_GET['bareme'])): ?><div class="alert alert-success py-2">Barème mis à jour.</div><?php endif; ?>
<?php require __DIR__ . '/ui.php'; ?>
<?php simTable($saved, [['Éligible', fn($r) => isset($r['eligible']) ? ($r['eligible'] ? 'Oui' : 'Non') : '–'], ['Tranche', fn($r) => $r['tranche'] ?? '–'], ['PTZ estimé', fn($r) => isset($r['ptz']) ? simEur($r['ptz']) : '–']]); ?>

<?php if (isAdmin()): ?>
<div class="card mt-3"><div class="card-header fw-semibold"><i class="fas fa-gear me-1"></i>Barème PTZ (administrateurs)</div>
<div class="card-body">
    <?php if ($baremeMsg): ?><div class="alert alert-danger py-2"><?= e($baremeMsg) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="bareme">
        <textarea name="bareme" class="form-control font-monospace" rows="14" spellcheck="false"><?= e($_POST['bareme'] ?? json_encode($ptzBareme, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="valide" id="bvalide" <?= !empty($ptzBareme['valide']) ? 'checked' : '' ?>><label class="form-check-label" for="bvalide">Barème contrôlé sur l'arrêté en vigueur (retire l'avertissement « provisoire » aux utilisateurs et sur /tools)</label></div>
        <button class="btn btn-primary mt-2">Enregistrer le barème</button>
    </form>
</div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
