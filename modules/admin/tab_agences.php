<?php
// Onglet « Agences » : nom, adresse et numéro de chaque agence. Cette liste alimente tous les choix d'agence du portail et de /tools.
require_once __DIR__ . '/../../includes/agences.php';
$agences = getAgencesFull(false);
$edit = null;
if (isset($_GET['edit'])) foreach ($agences as $a) if ((int)$a['id'] === (int)$_GET['edit']) $edit = $a;
$nbSansNum = count(array_filter($agences, fn($a) => $a['numero'] === ''));
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> Liste des agences</strong>
    Cette liste est utilisée partout où l'on choisit une agence : formulaire de bureau domiciliaire (portail et /tools, affiché « NOM (numéro) » sans l'adresse) et demande d'accès au portail depuis /tools (nom et adresse).
    Une agence désactivée n'apparaît plus dans les choix. <?php if ($nbSansNum): ?><br><span class="text-warning-emphasis"><i class="fas fa-triangle-exclamation"></i> <?= $nbSansNum ?> agence(s) sans numéro : complétez-les (ou importez-les en lot ci-dessous).</span><?php endif; ?>
</div>
<div class="card mb-4" id="agence-form"><div class="card-header"><strong><?= $edit ? 'Modifier l\'agence' : 'Ajouter une agence' ?></strong></div>
<div class="card-body"><form method="post" class="row g-3">
    <input type="hidden" name="action" value="save_agence"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="col-md-4"><label class="form-label">Nom</label><input name="nom" class="form-control" required maxlength="120" value="<?= e($edit['nom'] ?? '') ?>" placeholder="CAPDENAC GARE"></div>
    <div class="col-md-5"><label class="form-label">Adresse</label><input name="adresse" class="form-control" maxlength="255" value="<?= e($edit['adresse'] ?? '') ?>" placeholder="5 Avenue Charles de Gaulle, 12700 Capdenac-Gare"></div>
    <div class="col-md-1"><label class="form-label">N°</label><input name="numero" class="form-control" maxlength="10" value="<?= e($edit['numero'] ?? '') ?>" placeholder="346"></div>
    <div class="col-md-2 d-flex align-items-end"><?php if ($edit): ?><div class="form-check me-3"><input class="form-check-input" type="checkbox" name="actif" id="agActif" <?= $edit['actif'] ? 'checked' : '' ?>><label class="form-check-label" for="agActif">Active</label></div><?php endif; ?>
        <button class="btn btn-ce"><?= $edit ? 'Enregistrer' : 'Ajouter' ?></button><?php if ($edit): ?><a class="btn btn-outline-secondary ms-2" href="?tab=agences">Annuler</a><?php endif; ?></div>
</form></div></div>
<div class="data-table-container mb-4"><div class="data-table-header"><h3><i class="fas fa-building"></i> Agences (<?= count($agences) ?>)</h3>
    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchAgences" placeholder="Rechercher..."></div></div>
<div style="overflow-x:auto"><table class="data-table" id="tableAgences"><thead><tr><th>Nom</th><th>Adresse</th><th>N°</th><th>Statut</th><th style="width:210px">Actions</th></tr></thead><tbody>
<?php foreach ($agences as $a): ?>
<tr class="<?= $a['actif'] ? '' : 'text-muted' ?>"><td><strong><?= e($a['nom']) ?></strong></td><td><?= e($a['adresse']) ?: '<span class="text-muted">—</span>' ?></td>
    <td><?= $a['numero'] !== '' ? e($a['numero']) : '<span class="badge bg-warning text-dark">manquant</span>' ?></td>
    <td><span class="badge bg-<?= $a['actif'] ? 'success' : 'secondary' ?>"><?= $a['actif'] ? 'Active' : 'Désactivée' ?></span></td>
    <td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="?tab=agences&edit=<?= (int)$a['id'] ?>#agence-form"><i class="fas fa-pen"></i></a>
        <form method="post" class="d-inline"><input type="hidden" name="action" value="toggle_agence"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-secondary" title="Activer / désactiver"><i class="fas fa-power-off"></i></button></form>
        <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette agence ? (préférez la désactiver)')"><input type="hidden" name="action" value="delete_agence"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div class="card mb-4"><div class="card-header"><strong><i class="fas fa-file-import"></i> Importer ou mettre à jour en lot</strong></div><div class="card-body">
    <form method="post"><input type="hidden" name="action" value="import_agences">
        <p class="small text-muted mb-2">Une agence par ligne : <code>nom ; adresse ; numéro</code> (copiez-collez depuis un tableur). Si le nom existe déjà, l'adresse et le numéro sont mis à jour.</p>
        <textarea name="lignes" class="form-control font-monospace" rows="6" placeholder="CAPDENAC GARE ; 5 Avenue Charles de Gaulle, 12700 Capdenac-Gare ; 346"></textarea>
        <button class="btn btn-ce mt-2">Importer</button></form>
</div></div>
<script>document.getElementById('searchAgences').addEventListener('input',function(){const t=this.value.toLowerCase();document.querySelectorAll('#tableAgences tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(t)?'':'none');});</script>
