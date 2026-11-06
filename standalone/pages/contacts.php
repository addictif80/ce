<?php
// Contacts utiles, version fichier autonome : instantané de la base au moment de la génération, sans proposition en ligne.
require_once __DIR__ . '/../../tools/_layout.php';
try { try { getDB()->exec("ALTER TABLE contacts_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Throwable $e) {}
    $contacts = getDB()->query("SELECT id, service, telephone, mail, a_contacter_pour FROM contacts_utiles WHERE approved = 1 ORDER BY service ASC")->fetchAll(); }
catch (Throwable $e) { fwrite(STDERR, 'BASE_INDISPONIBLE ' . $e->getMessage()); exit(3); }
if (!$contacts) { fwrite(STDERR, 'AUCUN_ELEMENT'); exit(4); }
function saTel($n) { if (empty($n)) return '<span class="text-muted">-</span>'; $dial = '0' . preg_replace('/[^0-9+]/', '', $n); return '<a href="tel:' . e($dial) . '"><i class="fas fa-phone-alt fa-sm"></i> ' . e($n) . '</a>'; }
function saMail($m) { if (empty($m)) return '<span class="text-muted">-</span>'; return '<a href="mailto:' . e($m) . '"><i class="fas fa-envelope fa-sm"></i> ' . e($m) . '</a>'; }
toolsHeader('Contacts utiles', 'contacts');
?>
<div class="container my-3">
    <?php privacyBanner('Consultation seule. Contacts validés au ' . date('d/m/Y') . ' (date de génération du fichier).') ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($contacts) ?> contact<?= count($contacts) > 1 ? 's' : '' ?></strong>
            <input type="search" id="search" class="form-control" style="max-width:260px" placeholder="Rechercher…" autocomplete="off">
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th>Service</th><th>Téléphone</th><th>E-mail</th><th>À contacter pour</th></tr></thead>
            <tbody><?php foreach ($contacts as $c): ?><tr><td><strong><?= e($c['service']) ?></strong></td><td><?= saTel($c['telephone']) ?></td><td><?= saMail($c['mail']) ?></td><td><?= nl2br(e($c['a_contacter_pour'])) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
    </div></div>
</div>
<script>document.getElementById('search').addEventListener('input', function () { const f = this.value.toLowerCase(); document.querySelectorAll('#table tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none'); });</script>
<?php toolsFooter();
