<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Codes utiles', 'codes');

$db = getDB();
try { $db->exec("ALTER TABLE codes_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
$codes = $db->query("SELECT code, fonction FROM codes_utiles WHERE approved = 1 ORDER BY code ASC")->fetchAll();
?>
<div class="container my-3">
    <?php privacyBanner('Consultation seule : rien de ce que vous faites sur cette page n\'est enregistré.') ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($codes) ?> code<?= count($codes) > 1 ? 's' : '' ?></strong>
            <input type="search" id="search" class="form-control" style="max-width:320px" placeholder="Rechercher…" autocomplete="off">
        </div>
        <?php if (!$codes): ?>
            <div class="alert alert-info mb-0">Aucun code disponible pour le moment.</div>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th style="width:25%">Code</th><th>Fonction</th></tr></thead>
            <tbody>
            <?php foreach ($codes as $c): ?>
                <tr><td><strong><?= e($c['code']) ?></strong></td><td><?= nl2br(e($c['fonction'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div></div>
</div>
<script>
document.getElementById('search')?.addEventListener('input', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#table tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none');
});
</script>
<?php toolsFooter();
