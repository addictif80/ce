<?php
// Codes utiles, version fichier autonome : instantané de la base au moment de la génération, sans proposition en ligne.
require_once __DIR__ . '/../../tools/_layout.php';
try { try { getDB()->exec("ALTER TABLE codes_utiles ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Throwable $e) {} // colonne ajoutée à la volée par le portail
    $codes = getDB()->query("SELECT id, code, fonction FROM codes_utiles WHERE approved = 1 ORDER BY code ASC")->fetchAll(); }
catch (Throwable $e) { fwrite(STDERR, 'BASE_INDISPONIBLE ' . $e->getMessage()); exit(3); }
if (!$codes) { fwrite(STDERR, 'AUCUN_ELEMENT'); exit(4); }
toolsHeader('Codes utiles', 'codes');
?>
<div class="container my-3">
    <?php privacyBanner('Consultation seule. Codes validés au ' . date('d/m/Y') . ' (date de génération du fichier).') ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong><?= count($codes) ?> code<?= count($codes) > 1 ? 's' : '' ?></strong>
            <input type="search" id="search" class="form-control" style="max-width:260px" placeholder="Rechercher…" autocomplete="off">
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th style="width:25%">Code</th><th>Fonction</th></tr></thead>
            <tbody><?php foreach ($codes as $c): ?><tr><td><strong><?= e($c['code']) ?></strong></td><td><?= nl2br(e($c['fonction'])) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
    </div></div>
</div>
<script>document.getElementById('search').addEventListener('input', function () { const f = this.value.toLowerCase(); document.querySelectorAll('#table tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none'); });</script>
<?php toolsFooter();
