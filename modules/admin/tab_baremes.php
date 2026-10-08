<?php
// Onglet « Barèmes » de l'administration : formulaires libellés (sans JSON), sources à consulter et suivi du contrôle.
require_once __DIR__ . '/../../includes/baremes.php';
require_once __DIR__ . '/../../includes/baremes_forms.php';
$cat = baremeCatalog();
$err = $_SESSION['bareme_err'] ?? null; unset($_SESSION['bareme_err']);
$badges = ['ok' => ['success', 'Contrôlé'], 'provisoire' => ['warning text-dark', 'À contrôler'], 'ancien' => ['danger', 'À revoir : plus de 12 mois']];
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> À quoi servent les barèmes ?</strong>
    Ce sont les chiffres réglementaires utilisés par les simulateurs (PTZ, frais de notaire, capacité d'emprunt) du portail et de /tools.
    Pour chaque barème : 1) ouvrez les sources indiquées, 2) comparez avec les valeurs du formulaire et corrigez si besoin, 3) cochez « J'ai contrôlé » et enregistrez.
    Tant que ce n'est pas fait, les simulateurs affichent « barème à confirmer ». Le barème repasse « à revoir » au bout de 12 mois.
</div>
<?php foreach ($cat as $cle => $c):
    $m = baremeGet($cle); $st = baremeStatut($m);
    $data = ($err && $err[0] === $cle) ? json_decode(json_encode($err[2]), true) : $m['data'];
    if ($cle === 'notaire' && !isset($data['departements'])) $data['departements'] = [];
?>
<div class="data-table-container mb-4" id="bareme-<?= e($cle) ?>">
    <div class="data-table-header"><h3><i class="fas <?= e($c['icon']) ?>"></i> <?= e($c['label']) ?></h3>
        <span class="badge bg-<?= $badges[$st][0] ?>"><?= $badges[$st][1] ?></span></div>
    <div class="p-3">
        <p class="text-muted"><?= e($c['help']) ?><?= $m['date'] ? ' Dernier contrôle déclaré : <strong>' . e(date('d/m/Y', strtotime($m['date']))) . '</strong>.' : '' ?></p>

        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="border rounded p-3 h-100 bg-light">
                <div class="fw-semibold mb-2"><i class="fas fa-magnifying-glass"></i> Où vérifier ces valeurs</div>
                <?php foreach ($c['sources'] as [$lib, $url, $quoi]): ?>
                    <div class="mb-2"><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($lib) ?> <i class="fas fa-arrow-up-right-from-square small"></i></a><div class="small text-muted"><?= e($quoi) ?></div></div>
                <?php endforeach; ?>
            </div></div>
            <div class="col-lg-5"><div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-2"><i class="fas fa-clipboard-check"></i> État du contrôle</div>
                <?php $v = $c['verifie']; ?>
                <?php if ($v['ok']): ?><div class="small mb-2"><span class="text-success fw-semibold"><i class="fas fa-check"></i> Déjà comparé avec <?= e($v['source']) ?> le <?= e($v['date']) ?> :</span> <?= e($v['ok']) ?>.</div><?php endif; ?>
                <?php if ($v['ko']): ?><div class="small"><span class="text-danger fw-semibold"><i class="fas fa-triangle-exclamation"></i> Reste à contrôler :</span> <?= e($v['ko']) ?>.</div><?php endif; ?>
            </div></div>
        </div>

        <?php if ($err && $err[0] === $cle): ?><div class="alert alert-danger py-2"><i class="fas fa-circle-xmark"></i> <?= e($err[1]) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="save_bareme"><input type="hidden" name="cle" value="<?= e($cle) ?>">
            <?= baremeRenderForm($cle, $data) ?>
            <div class="border-top mt-4 pt-3 row g-2 align-items-center">
                <div class="col-auto"><label class="col-form-label">Date du contrôle</label></div>
                <div class="col-auto"><input type="date" name="date_reference" class="form-control form-control-sm" value="<?= e($m['date'] ?: date('Y-m-d')) ?>"></div>
                <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="valide" id="bv-<?= e($cle) ?>" <?= $m['valide'] ? 'checked' : '' ?>><label class="form-check-label" for="bv-<?= e($cle) ?>"><strong>J'ai contrôlé ces valeurs</strong> sur les sources ci-dessus</label></div></div>
                <div class="col text-end">
                    <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
                    <button class="btn btn-outline-secondary btn-sm" name="action" value="reset_bareme" formnovalidate onclick="return confirm('Revenir aux valeurs proposées par défaut ?')">Valeurs par défaut</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<script>
// Ajout d'une ligne dans un tableau dynamique (tranches d'émoluments, départements)
function bfAddRow(tableId, key, cols) {
    const tb = document.querySelector('#' + tableId + ' tbody');
    const i = Date.now() % 1000000;
    const tr = document.createElement('tr');
    tr.innerHTML = cols.map(([f, w, ph]) => `<td><input name="d[${key}][${i}][${f}]" placeholder="${ph}" class="form-control form-control-sm" style="width:${w}px"></td>`).join('')
        + '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="fas fa-xmark"></i></button></td>';
    tb.appendChild(tr);
}
</script>
