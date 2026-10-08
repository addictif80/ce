<?php
// Onglet « Pièces justificatives » de l'administration. Variables : $db.
require_once __DIR__ . '/../../includes/pieces.php';
piecesEnsureSchema();
$modeles = $db->query("SELECT * FROM pieces_modeles ORDER BY ordre, nom")->fetchAll();
$modeles[] = ['id' => 0, 'nom' => '', 'lignes' => '', 'actif' => 1, 'ordre' => count($modeles) + 1];
?>
<div class="alert alert-info small"><i class="fas fa-circle-info"></i> Une ligne par pièce : <code>Groupe ; Libellé ; Détail ; Profil</code>. Profil : <code>tous</code>, <code>salarie</code>, <code>independant</code> ou <code>retraite</code> (par défaut <code>tous</code>). Les modèles actifs sont proposés dans /tools et dans le portail.</div>
<?php foreach ($modeles as $m): ?>
<div class="data-table-container mb-3">
    <form method="post" class="p-3">
        <input type="hidden" name="action" value="save_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-6"><input type="text" name="nom" class="form-control" required maxlength="150" placeholder="<?= $m['id'] ? '' : 'Nouveau modèle : nom (ex. Prêt professionnel)' ?>" value="<?= e($m['nom']) ?>"></div>
            <div class="col-md-2"><input type="number" name="ordre" class="form-control" title="Ordre d'affichage" value="<?= (int)$m['ordre'] ?>"></div>
            <div class="col-md-2 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="actif" id="pa<?= (int)$m['id'] ?>" <?= $m['actif'] ? 'checked' : '' ?>><label class="form-check-label" for="pa<?= (int)$m['id'] ?>">Actif</label></div></div>
            <div class="col-md-2 text-end">
                <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i></button>
                <?php if ($m['id']): ?><button class="btn btn-outline-danger btn-sm" name="action" value="delete_pieces_modele" formnovalidate onclick="return confirm('Supprimer ce modèle ?')"><i class="fas fa-trash"></i></button><?php endif; ?>
            </div>
        </div>
        <textarea name="lignes" class="form-control font-monospace" rows="<?= $m['id'] ? 8 : 4 ?>" spellcheck="false" style="font-size:.82rem"><?= e($m['lignes']) ?></textarea>
    </form>
</div>
<?php endforeach; ?>
