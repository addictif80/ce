<?php
// Onglet « Nouveautés » : entrées du « Quoi de neuf » de /tools.
require_once __DIR__ . '/../../includes/nouveautes.php';
$news = newsList(false, 200);
$types = newsTypes();
$catalog = getPublicToolsCatalog();
$edit = null;
if (isset($_GET['edit'])) foreach ($news as $n) if ((int)$n['id'] === (int)$_GET['edit']) $edit = $n;
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> « Quoi de neuf » de /tools</strong>
    Chaque entrée apparaît sur la page <code>/tools/nouveautes.php</code>, du plus récent au plus ancien. Un badge « nouveau » s'affiche dans l'en-tête de /tools tant que le visiteur n'a pas consulté la page depuis la dernière entrée.
    Les barèmes mis à jour sont listés automatiquement à partir de l'onglet Barèmes.
</div>
<div class="card mb-4" id="news-form"><div class="card-header"><strong><?= $edit ? 'Modifier la nouveauté' : 'Ajouter une nouveauté' ?></strong></div>
<div class="card-body"><form method="post" class="row g-3">
    <input type="hidden" name="action" value="save_news"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="date_news" class="form-control" value="<?= e($edit['date_news'] ?? date('Y-m-d')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><?php foreach ($types as $k => $t): ?><option value="<?= e($k) ?>" <?= ($edit['type'] ?? 'evolution') === $k ? 'selected' : '' ?>><?= e($t[0]) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Outil concerné (facultatif)</label><select name="tool_key" class="form-select"><option value="">—</option><?php foreach ($catalog as $k => $t): ?><option value="<?= e($k) ?>" <?= ($edit['tool_key'] ?? '') === $k ? 'selected' : '' ?>><?= e($t['label']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="form-label">Titre</label><input name="titre" class="form-control" maxlength="150" required value="<?= e($edit['titre'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="texte" class="form-control" rows="3" maxlength="2000"><?= e($edit['texte'] ?? '') ?></textarea></div>
    <div class="col-12 d-flex gap-3 align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="visible" id="newsVisible" <?= ($edit['visible'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="newsVisible">Visible sur /tools</label></div>
        <button class="btn btn-ce">Enregistrer</button><?php if ($edit): ?><a class="btn btn-outline-secondary" href="?tab=nouveautes">Annuler</a><?php endif; ?></div>
</form></div></div>
<div class="data-table-container"><table class="data-table"><thead><tr><th>Date</th><th>Type</th><th>Titre</th><th>Visible</th><th></th></tr></thead><tbody>
<?php foreach ($news as $n): ?><tr><td><?= e(date('d/m/Y', strtotime($n['date_news']))) ?></td><td><?= e(($types[$n['type']] ?? $types['evolution'])[0]) ?></td><td><?= e($n['titre']) ?></td><td><?= $n['visible'] ? 'Oui' : 'Non' ?></td>
<td class="actions"><a class="btn btn-sm btn-ce-outline" href="?tab=nouveautes&edit=<?= (int)$n['id'] ?>#news-form"><i class="fas fa-edit"></i></a>
<form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette nouveauté ?')"><input type="hidden" name="action" value="delete_news"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$news): ?><tr><td colspan="5" class="text-muted">Aucune nouveauté.</td></tr><?php endif; ?></tbody></table></div>
