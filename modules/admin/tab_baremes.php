<?php
// Onglet « Barèmes » de l'administration. Variables : $db.
require_once __DIR__ . '/../../includes/baremes.php';
$cat = baremeCatalog();
$err = $_SESSION['bareme_err'] ?? null; unset($_SESSION['bareme_err']);
$badges = ['ok' => ['success', 'Contrôlé'], 'provisoire' => ['warning text-dark', 'Provisoire : non contrôlé'], 'ancien' => ['danger', 'À revoir : date de référence de plus de 12 mois']];
?>
<div class="alert alert-info small"><i class="fas fa-circle-info"></i> Ces barèmes alimentent les simulateurs du portail et de /tools. Après contrôle sur le texte officiel en vigueur, renseignez la date de référence et cochez « contrôlé » : l'avertissement « barème provisoire » disparaît. Au-delà de 12 mois, le barème est signalé « à revoir ».</div>
<?php foreach ($cat as $cle => $c): $m = baremeGet($cle); $st = baremeStatut($m); $json = ($err && $err[0] === $cle) ? $err[2] : json_encode($m['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas <?= e($c['icon']) ?>"></i> <?= e($c['label']) ?></h3>
        <span class="badge bg-<?= $badges[$st][0] ?>"><?= $badges[$st][1] ?></span></div>
    <div class="p-3">
        <p class="small text-muted"><?= e($c['help']) ?><?= $m['defaut'] ? ' <strong>Valeurs par défaut (jamais enregistrées).</strong>' : '' ?></p>
        <?php if ($err && $err[0] === $cle): ?><div class="alert alert-danger py-2"><?= e($err[1]) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="save_bareme"><input type="hidden" name="cle" value="<?= e($cle) ?>">
            <textarea name="data" class="form-control font-monospace" rows="12" spellcheck="false"><?= e($json) ?></textarea>
            <div class="row g-2 align-items-center mt-1">
                <div class="col-auto"><label class="col-form-label">Date de référence</label></div>
                <div class="col-auto"><input type="date" name="date_reference" class="form-control form-control-sm" value="<?= e($m['date'] ?? '') ?>"></div>
                <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="valide" id="bv-<?= e($cle) ?>" <?= $m['valide'] ? 'checked' : '' ?>><label class="form-check-label" for="bv-<?= e($cle) ?>">Contrôlé sur le texte officiel</label></div></div>
                <div class="col text-end">
                    <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
                    <button class="btn btn-outline-secondary btn-sm" name="action" value="reset_bareme" formnovalidate onclick="return confirm('Revenir aux valeurs par défaut ?')">Valeurs par défaut</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
