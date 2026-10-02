<?php
require_once __DIR__ . '/_layout.php';
toolsHeader('Contacts utiles', 'contacts');

$db = getDB();
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
// Contacts utiles validés uniquement (ni contacts équipe, ni collaborateurs)
$contacts = $db->query("SELECT service, telephone, mail, a_contacter_pour FROM contacts_utiles WHERE approved = 1 ORDER BY service ASC")->fetchAll();

function toolsTel($n) {
    if (empty($n)) return '<span class="text-muted">-</span>';
    $dial = '0' . preg_replace('/[^0-9+]/', '', $n);
    return '<a href="tel:' . e($dial) . '"><i class="fas fa-phone-alt fa-sm"></i> ' . e($n) . '</a>';
}
function toolsMail($m) {
    if (empty($m)) return '<span class="text-muted">-</span>';
    return '<a href="mailto:' . e($m) . '"><i class="fas fa-envelope fa-sm"></i> ' . e($m) . '</a>';
}
?>
<div class="container my-3">
    <?php privacyBanner('Consultation seule : rien de ce que vous faites sur cette page n\'est enregistré.') ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($contacts) ?> contact<?= count($contacts) > 1 ? 's' : '' ?></strong>
            <input type="search" id="search" class="form-control" style="max-width:320px" placeholder="Rechercher…" autocomplete="off">
        </div>
        <?php if (!$contacts): ?>
            <div class="alert alert-info mb-0">Aucun contact disponible pour le moment.</div>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th>Service</th><th>Téléphone</th><th>E-mail</th><th>À contacter pour</th></tr></thead>
            <tbody>
            <?php foreach ($contacts as $c): ?>
                <tr><td><strong><?= e($c['service']) ?></strong></td><td><?= toolsTel($c['telephone']) ?></td><td><?= toolsMail($c['mail']) ?></td><td><?= nl2br(e($c['a_contacter_pour'])) ?></td></tr>
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
