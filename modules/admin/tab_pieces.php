<?php
// Onglet « Pièces justificatives » de l'administration : éditeur ligne à ligne des modèles de listes. Variables : $db.
require_once __DIR__ . '/../../includes/pieces.php';
piecesEnsureSchema();
$modeles = $db->query("SELECT * FROM pieces_modeles ORDER BY ordre, nom, id")->fetchAll();
$profils = piecesProfils();
$ids = array_column($modeles, 'id');
$modeles[] = ['id' => 0, 'nom' => '', 'lignes' => '', 'actif' => 1, 'ordre' => 0]; // nouveau modèle
?>
<style>
    .pc-ed td{vertical-align:middle;padding:3px}
    .pc-ed input,.pc-ed select{font-size:.85rem}
    .pc-ed tr.pc-sep td{background:#f1f3f5;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.03em}
</style>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> Comment ça marche ?</strong>
    Chaque modèle est une liste de pièces à demander pour un type de dossier (crédit immobilier, ouverture de compte…).
    Les modèles <strong>actifs</strong> sont proposés dans /tools et dans le portail. Pour chaque pièce, choisissez la <strong>rubrique</strong> (elles servent de titres dans le document remis au client),
    la précision éventuelle et la situation concernée : « Tous » = toujours demandée ; « Salarié », « Indépendant » ou « Retraité » = demandée seulement pour cette situation.
</div>

<div class="accordion mb-4" id="pcAcc">
<?php foreach ($modeles as $k => $m):
    $items = piecesParse($m['lignes']);
    $isNew = !$m['id'];
    $rubriques = array_values(array_unique(array_column($items, 'groupe')));
    $pos = array_search($m['id'], $ids, true);
?>
<div class="accordion-item" id="m<?= (int)$m['id'] ?>">
    <h2 class="accordion-header">
        <button class="accordion-button <?= ($isNew || $k > 0) ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#mc<?= (int)$m['id'] ?>">
            <?php if ($isNew): ?><i class="fas fa-plus me-2 text-success"></i><strong>Créer un nouveau modèle</strong>
            <?php else: ?><i class="fas fa-list-check me-2"></i><strong><?= e($m['nom']) ?></strong>
                <span class="badge bg-secondary ms-2"><?= count($items) ?> pièce<?= count($items) > 1 ? 's' : '' ?></span>
                <?php if (!$m['actif']): ?><span class="badge bg-warning text-dark ms-2">Inactif : non proposé</span><?php endif; ?>
            <?php endif; ?>
        </button>
    </h2>
    <div id="mc<?= (int)$m['id'] ?>" class="accordion-collapse collapse <?= (!$isNew && $k === 0) ? 'show' : '' ?>" data-bs-parent="#pcAcc">
    <div class="accordion-body">
        <form method="post" class="pc-form">
            <input type="hidden" name="action" value="save_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6"><label class="form-label small mb-0">Nom du modèle</label><input type="text" name="nom" class="form-control" required maxlength="150" value="<?= e($m['nom']) ?>" placeholder="Ex. : Prêt professionnel"></div>
                <div class="col-md-3"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="actif" id="pa<?= (int)$m['id'] ?>" <?= $m['actif'] ? 'checked' : '' ?>><label class="form-check-label" for="pa<?= (int)$m['id'] ?>">Proposé aux utilisateurs</label></div></div>
            </div>
            <div class="table-responsive"><table class="table table-sm pc-ed mb-2">
                <thead><tr><th style="width:20%">Rubrique</th><th>Pièce demandée</th><th style="width:26%">Précision (facultatif)</th><th style="width:15%">Concerne</th><th style="width:96px"></th></tr></thead>
                <tbody data-rub="rub<?= (int)$m['id'] ?>">
                <?php foreach ($items as $i => $it): ?>
                    <tr>
                        <td><input name="items[<?= $i ?>][groupe]" list="rub<?= (int)$m['id'] ?>" value="<?= e($it['groupe']) ?>" class="form-control form-control-sm" maxlength="60"></td>
                        <td><input name="items[<?= $i ?>][libelle]" value="<?= e($it['libelle']) ?>" class="form-control form-control-sm" maxlength="200"></td>
                        <td><input name="items[<?= $i ?>][detail]" value="<?= e($it['detail']) ?>" class="form-control form-control-sm" maxlength="200"></td>
                        <td><select name="items[<?= $i ?>][profil]" class="form-select form-select-sm"><?php foreach ($profils as $pk => $pv): ?><option value="<?= e($pk) ?>" <?= $it['profil'] === $pk ? 'selected' : '' ?>><?= e($pv) ?></option><?php endforeach; ?></select></td>
                        <td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-secondary" data-mv="-1" title="Monter"><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-mv="1" title="Descendre"><i class="fas fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rm title="Supprimer cette pièce"><i class="fas fa-xmark"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <datalist id="rub<?= (int)$m['id'] ?>"><?php foreach ($rubriques as $r): ?><option value="<?= e($r) ?>"><?php endforeach; ?></datalist>
            <button type="button" class="btn btn-sm btn-outline-secondary pc-add"><i class="fas fa-plus"></i> Ajouter une pièce</button>
            <div class="form-text">Pour regrouper des pièces, saisissez la même rubrique (une liste de suggestions apparaît). L'ordre affiché ici est l'ordre du document.</div>
            <div class="mt-3"><button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> <?= $isNew ? 'Créer le modèle' : 'Enregistrer' ?></button></div>
        </form>
        <?php if (!$isNew): ?>
        <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2">
            <form method="post" class="d-inline"><input type="hidden" name="action" value="move_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="dir" value="up">
                <button class="btn btn-outline-secondary btn-sm" <?= $pos === 0 ? 'disabled' : '' ?> title="Afficher ce modèle plus haut dans la liste"><i class="fas fa-arrow-up"></i> Monter le modèle</button></form>
            <form method="post" class="d-inline"><input type="hidden" name="action" value="move_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="dir" value="down">
                <button class="btn btn-outline-secondary btn-sm" <?= $pos === count($ids) - 1 ? 'disabled' : '' ?> title="Afficher ce modèle plus bas dans la liste"><i class="fas fa-arrow-down"></i> Descendre le modèle</button></form>
            <form method="post" class="d-inline"><input type="hidden" name="action" value="duplicate_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-copy"></i> Dupliquer</button></form>
            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce modèle ?')"><input type="hidden" name="action" value="delete_pieces_modele"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Supprimer</button></form>
        </div>
        <?php endif; ?>
    </div></div>
</div>
<?php endforeach; ?>
</div>
<script>
(function () {
    const profils = <?= json_encode($profils, JSON_UNESCAPED_UNICODE) ?>;
    // Les champs sont renumérotés à chaque modification pour conserver l'ordre affiché
    function renumber(tbody) {
        [...tbody.querySelectorAll('tr')].forEach((tr, i) => tr.querySelectorAll('[name^="items["]').forEach(el => el.name = el.name.replace(/^items\[\d+\]/, 'items[' + i + ']')));
    }
    function newRow(tbody, groupe) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><input name="items[0][groupe]" list="${tbody.dataset.rub}" class="form-control form-control-sm" maxlength="60" value="${groupe || ''}"></td>
            <td><input name="items[0][libelle]" class="form-control form-control-sm" maxlength="200"></td>
            <td><input name="items[0][detail]" class="form-control form-control-sm" maxlength="200"></td>
            <td><select name="items[0][profil]" class="form-select form-select-sm">${Object.entries(profils).map(([k, v]) => `<option value="${k}">${v}</option>`).join('')}</select></td>
            <td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-secondary" data-mv="-1" title="Monter"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-mv="1" title="Descendre"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-rm title="Supprimer cette pièce"><i class="fas fa-xmark"></i></button></td>`;
        tbody.appendChild(tr);
        renumber(tbody);
        tr.querySelector('[name$="[libelle]"]').focus();
    }
    document.querySelectorAll('.pc-form').forEach(form => {
        const tbody = form.querySelector('tbody');
        if (!tbody.children.length) newRow(tbody, '');
        form.querySelector('.pc-add').addEventListener('click', () => {
            const last = tbody.lastElementChild;
            newRow(tbody, last ? last.querySelector('[name$="[groupe]"]').value : '');
        });
        tbody.addEventListener('click', e => {
            const tr = e.target.closest('tr'); if (!tr) return;
            const mv = e.target.closest('[data-mv]');
            if (mv) {
                const d = +mv.dataset.mv, sib = d < 0 ? tr.previousElementSibling : tr.nextElementSibling;
                if (sib) d < 0 ? tbody.insertBefore(tr, sib) : tbody.insertBefore(sib, tr);
                renumber(tbody);
            }
            if (e.target.closest('[data-rm]')) { tr.remove(); renumber(tbody); }
        });
    });
})();
</script>
