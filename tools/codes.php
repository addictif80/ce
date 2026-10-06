<?php
require_once __DIR__ . '/_layout.php';
requirePublicTool('codes'); // avant le traitement du formulaire

$db = getDB();
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = handleToolsProposalPost('code');
    if ($error === null) { header('Location: codes.php?sent=1'); exit; }
}

toolsHeader('Codes utiles', 'codes');
$codes = $db->query("SELECT id, code, fonction FROM codes_utiles WHERE approved = 1 ORDER BY code ASC")->fetchAll();
?>
<div class="container my-3">
    <?php privacyBanner('Consultation seule : rien de ce que vous faites sur cette page n\'est enregistré, sauf si vous choisissez d\'envoyer une proposition ci-dessous.') ?>
    <?php if (isset($_GET['sent'])): ?><div class="alert alert-success">Merci, votre proposition a été transmise pour validation.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($codes) ?> code<?= count($codes) > 1 ? 's' : '' ?></strong>
            <div class="d-flex gap-2 flex-wrap">
                <input type="search" id="search" class="form-control" style="max-width:260px" placeholder="Rechercher…" autocomplete="off">
                <button type="button" class="btn btn-danger js-propose-new"><i class="fas fa-plus me-1"></i>Proposer un code</button>
            </div>
        </div>
        <?php if (!$codes): ?>
            <div class="alert alert-info mb-0">Aucun code disponible pour le moment.</div>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th style="width:25%">Code</th><th>Fonction</th><th style="width:60px"></th></tr></thead>
            <tbody>
            <?php foreach ($codes as $c): ?>
                <tr><td><strong><?= e($c['code']) ?></strong></td><td><?= nl2br(e($c['fonction'])) ?></td>
                    <td><button type="button" class="btn btn-sm btn-outline-secondary js-propose-edit" title="Suggérer une modification" data-row="<?= e(json_encode($c, JSON_UNESCAPED_UNICODE)) ?>"><i class="fas fa-edit"></i></button></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div></div>
</div>
<?php toolsProposalModal('code'); ?>
<script>
(function() { // pré-filtre depuis la recherche globale (?q=)
    const q = new URLSearchParams(location.search).get('q'), el = document.getElementById('search');
    if (q && el) { el.value = q; setTimeout(() => el.dispatchEvent(new Event('input')), 0); }
})();
document.getElementById('search')?.addEventListener('input', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#table tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none');
});
</script>
<?php toolsFooter();
