<?php
$pageTitle = 'Documents';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/documents.php';
docsEnsureSchema();
$db = getDB();
$st = $db->prepare("SELECT id, type, nom, taille, updated_at FROM ce_docs WHERE user_id = ? ORDER BY updated_at DESC");
$st->execute([getCurrentUserId()]);
$docs = $st->fetchAll();
foreach ($docs as &$d) $d['links'] = docsLinksOf($d['id']);
unset($d);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div><h4 class="mb-1"><i class="fas fa-folder-open"></i> Documents</h4>
        <p class="text-muted mb-0">Créez des tableurs et des présentations, puis rattachez-les à un dossier ou à n'importe quel enregistrement d'un module.</p></div>
    <div class="d-flex gap-2"><button type="button" class="btn btn-success" data-new="sheet"><i class="fas fa-table me-1"></i>Nouveau tableur</button><button type="button" class="btn btn-danger" data-new="slides"><i class="fas fa-display me-1"></i>Nouvelle présentation</button></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Nom</th><th>Rattaché à</th><th>Modifié</th><th>Taille</th><th></th></tr></thead><tbody>
    <?php foreach ($docs as $d): ?>
    <tr><td><i class="fas <?= $d['type'] === 'sheet' ? 'fa-table text-success' : 'fa-display text-danger' ?> me-2"></i><a href="<?= $d['type'] === 'sheet' ? 'sheet' : 'slides' ?>.php?id=<?= (int)$d['id'] ?>"><?= e($d['nom']) ?></a></td>
        <td><?php foreach ($d['links'] as $l): ?><span class="badge bg-light text-dark border me-1"><i class="fas fa-paperclip"></i> <?= e($l['module_label']) ?> · <?= e($l['label']) ?></span><?php endforeach; ?><?php if (!$d['links']): ?><span class="text-muted small">—</span><?php endif; ?></td>
        <td class="small"><?= e(date('d/m/Y H:i', strtotime($d['updated_at']))) ?></td><td class="small"><?= e(number_format($d['taille'] / 1024, 0, ',', ' ')) ?> Ko</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-ren="<?= (int)$d['id'] ?>" data-nom="<?= e($d['nom']) ?>" title="Renommer"><i class="fas fa-pen"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-del="<?= (int)$d['id'] ?>" title="Supprimer"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?>
    <?php if (!$docs): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun document pour l'instant.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<script>
(function () {
  const BLANK = { sheet: { v: 1, active: 0, sheets: [{ name: 'Feuille 1', cells: {}, colw: {}, rows: 60, cols: 20 }] },
    slides: { v: 1, theme: 'ce', trans: 'fade', slides: [{ bg: '#ffffff', notes: '', els: [{ id: 't1', t: 'text', x: 80, y: 170, w: 800, h: 110, text: 'Titre de la présentation', size: 52, b: true, i: false, color: '#c40025', al: 'center', bul: false, font: 'Arial' }, { id: 't2', t: 'text', x: 80, y: 300, w: 800, h: 60, text: 'Sous-titre', size: 26, b: false, i: false, color: '#222222', al: 'center', bul: false, font: 'Arial' }] }] } };
  const call = async (a, p) => { const fd = new FormData(); fd.append('action', a); Object.keys(p || {}).forEach(k => fd.append(k, p[k])); try { return await (await fetch('api.php', { method: 'POST', body: fd, headers: { 'X-Doc-Api': '1' }, credentials: 'same-origin' })).json(); } catch (e) { return { ok: false, error: 'Connexion impossible.' }; } };
  document.addEventListener('click', async e => {
    const n = e.target.closest('[data-new]'), r = e.target.closest('[data-ren]'), d = e.target.closest('[data-del]');
    if (n) { const t = n.dataset.new, nom = prompt('Nom du document :', t === 'sheet' ? 'Tableur sans titre' : 'Présentation sans titre'); if (nom === null) return; const x = await call('create', { type: t, nom, data: JSON.stringify(BLANK[t]) }); if (x.ok) location.href = (t === 'sheet' ? 'sheet' : 'slides') + '.php?id=' + x.id; else alert(x.error); }
    else if (r) { const nom = prompt('Nouveau nom :', r.dataset.nom); if (nom === null) return; const x = await call('rename', { id: r.dataset.ren, nom }); if (x.ok) location.reload(); else alert(x.error); }
    else if (d) { if (!confirm('Supprimer ce document et ses rattachements ? Cette action est définitive.')) return; const x = await call('delete', { id: d.dataset.del }); if (x.ok) location.reload(); else alert(x.error); }
  });
})();
</script>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
