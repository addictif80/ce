<?php
require_once __DIR__ . '/_layout.php';
requirePublicTool('contacts'); // avant le traitement du formulaire

$db = getDB();
try { $db->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = handleToolsProposalPost('contact');
    if ($error === null) { header('Location: contacts.php?sent=1'); exit; }
}

toolsHeader('Contacts utiles', 'contacts');
// Contacts utiles validés uniquement (ni contacts équipe, ni collaborateurs)
$contacts = $db->query("SELECT id, service, telephone, mail, a_contacter_pour FROM contacts_utiles WHERE approved = 1 ORDER BY service ASC")->fetchAll();

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
    <?php privacyBanner('Consultation seule : rien de ce que vous faites sur cette page n\'est enregistré, sauf si vous choisissez d\'envoyer une proposition ci-dessous.') ?>
    <?php if (isset($_GET['sent'])): ?><div class="alert alert-success">Merci, votre proposition a été transmise pour validation.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($contacts) ?> contact<?= count($contacts) > 1 ? 's' : '' ?></strong>
            <div class="d-flex gap-2 flex-wrap">
                <input type="search" id="search" class="form-control" style="max-width:260px" placeholder="Rechercher…" autocomplete="off">
                <button type="button" class="btn btn-danger js-propose-new"><i class="fas fa-plus me-1"></i>Proposer un contact</button>
            </div>
        </div>
        <?php if (!$contacts): ?>
            <div class="alert alert-info mb-0">Aucun contact disponible pour le moment.</div>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th>Service</th><th>Téléphone</th><th>E-mail</th><th>À contacter pour</th><th style="width:60px"></th></tr></thead>
            <tbody>
            <?php foreach ($contacts as $c): ?>
                <tr><td><strong><?= e($c['service']) ?></strong></td><td><?= toolsTel($c['telephone']) ?></td><td><?= toolsMail($c['mail']) ?></td><td><?= nl2br(e($c['a_contacter_pour'])) ?></td>
                    <td><button type="button" class="btn btn-sm btn-outline-secondary js-propose-edit" title="Suggérer une modification" data-row="<?= e(json_encode($c, JSON_UNESCAPED_UNICODE)) ?>"><i class="fas fa-edit"></i></button></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div></div>
</div>
<?php toolsProposalModal('contact'); ?>
<script>
document.getElementById('search')?.addEventListener('input', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#table tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none');
});
</script>
<?php toolsFooter();
