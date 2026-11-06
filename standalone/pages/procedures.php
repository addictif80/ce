<?php
// Procédures publiées, version fichier autonome : instantané de la base au moment de la génération, filtre côté navigateur.
require_once __DIR__ . '/../../tools/_layout.php';
try {
    $db = getDB();
    foreach (['approved TINYINT(1) DEFAULT 0', 'approved_by INT DEFAULT NULL'] as $col) { try { $db->exec("ALTER TABLE procedures ADD COLUMN $col"); } catch (Throwable $e) {} }
    ensureProcedureProposalsSchema();
    ensureProcedureCategoriesSchema();
    $cats = getCategoriesProcedures();
    $procs = $db->query("SELECT p.id, p.nom, p.texte, p.mise_en_avant, p.created_at, p.contributor_prenom, p.contributor_nom, p.categorie_id, c.nom AS categorie_nom
        FROM procedures p LEFT JOIN categories_procedures c ON p.categorie_id = c.id WHERE p.approved = 1 ORDER BY p.mise_en_avant DESC, p.created_at DESC")->fetchAll();
} catch (Throwable $e) { fwrite(STDERR, 'BASE_INDISPONIBLE ' . $e->getMessage()); exit(3); }
if (!$procs) { fwrite(STDERR, 'AUCUN_ELEMENT'); exit(4); }
// contenu éditorial validé en administration : on retire malgré tout scripts, cadres et gestionnaires d'événements (le fichier s'ouvre en local)
$clean = function ($h) {
    $h = preg_replace('~<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1\s*>~is', '', (string)$h);
    $h = preg_replace('~</?(script|style|iframe|object|embed|form|link|meta)\b[^>]*>~i', '', $h);
    $h = preg_replace('~\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)~i', '', $h);
    return preg_replace('~(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2~i', '$1=$2#$2', $h);
};
foreach ($procs as &$p) { $p['date'] = formatDate($p['created_at']); $p['texte'] = $clean($p['texte']); }
unset($p);
toolsHeader('Procédures', 'procedures');
?>
<style>.wysiwyg-content{line-height:1.7}.wysiwyg-content img{max-width:100%;height:auto}.wysiwyg-content h1,.wysiwyg-content h2,.wysiwyg-content h3{color:#e4002b;margin-top:14px;font-size:1.2rem}</style>
<div class="container my-3">
    <?php privacyBanner('Consultation seule. Les procédures sont celles publiées au ' . date('d/m/Y') . ' (date de génération du fichier).') ?>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <strong id="nb"><?= count($procs) ?> procédure<?= count($procs) > 1 ? 's' : '' ?></strong>
            <div class="d-flex gap-2 flex-wrap">
                <select id="cat" class="form-select" style="width:auto"><option value="">Toutes les catégories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nom']) ?></option><?php endforeach; ?></select>
                <input type="search" id="search" class="form-control" style="max-width:260px" placeholder="Rechercher (titre ou contenu)…" autocomplete="off">
            </div>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle" id="table">
            <thead><tr><th>Nom</th><th>Catégorie</th><th>Publiée le</th><th style="width:70px"></th></tr></thead>
            <tbody>
            <?php foreach ($procs as $p): ?>
                <tr data-id="<?= (int)$p['id'] ?>" data-cat="<?= (int)$p['categorie_id'] ?>" data-txt="<?= e(mb_strtolower($p['nom'] . ' ' . strip_tags((string)$p['texte']))) ?>">
                    <td><strong><?= e($p['nom']) ?></strong><?= $p['mise_en_avant'] ? ' <i class="fas fa-star text-warning" title="Mise en avant"></i>' : '' ?></td>
                    <td><?= $p['categorie_nom'] ? '<span class="badge bg-secondary">' . e($p['categorie_nom']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= e($p['date']) ?></td>
                    <td><button type="button" class="btn btn-sm btn-outline-secondary js-voir" title="Voir"><i class="fas fa-eye"></i></button></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <div class="alert alert-info mb-0" id="vide" style="display:none">Aucune procédure ne correspond à votre recherche.</div>
    </div></div>
</div>
<div class="modal fade" id="detail" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="detail-titre"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="detail-corps"></div>
</div></div></div>
<script>
const DATA = <?= json_encode(array_column($procs, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
function filtrer() {
    const q = document.getElementById('search').value.toLowerCase(), c = document.getElementById('cat').value; let n = 0;
    document.querySelectorAll('#table tbody tr').forEach(r => { const ok = (!q || r.dataset.txt.includes(q)) && (!c || r.dataset.cat === c); r.style.display = ok ? '' : 'none'; if (ok) n++; });
    document.getElementById('vide').style.display = n ? 'none' : ''; document.getElementById('nb').textContent = n + ' procédure' + (n > 1 ? 's' : '');
}
document.getElementById('search').addEventListener('input', filtrer); document.getElementById('cat').addEventListener('change', filtrer);
document.getElementById('table').addEventListener('click', e => {
    const b = e.target.closest('.js-voir'); if (!b) return; const p = DATA[b.closest('tr').dataset.id]; if (!p) return;
    document.getElementById('detail-titre').textContent = p.nom;
    const t = p.texte || '', html = /<[a-z][\s\S]*>/i.test(t) ? '<div class="wysiwyg-content">' + t + '</div>' : '<div style="white-space:pre-wrap;line-height:1.7">' + esc(t) + '</div>';
    document.getElementById('detail-corps').innerHTML = (p.categorie_nom ? '<span class="badge bg-secondary me-2">' + esc(p.categorie_nom) + '</span>' : '') + '<small class="text-muted">Publiée le ' + esc(p.date) + '</small><hr>' + (t ? html : '<em class="text-muted">Aucun contenu</em>');
    new bootstrap.Modal(document.getElementById('detail')).show();
});
</script>
<?php toolsFooter();
