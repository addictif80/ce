<?php
// Taux d'usure : seuils en vigueur par catégorie et contrôle d'un TAEG. Seuils saisis dans l'administration (barèmes) ; consultation sans enregistrement.
require_once __DIR__ . '/../../includes/baremes.php';
$ub = baremeGet('usure'); $ud = $ub['data']; $cats = $ud['categories'] ?? [];
require __DIR__ . '/../_sim/style.php';
$fmtD = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['usure']) ?>
<div class="row g-3">
 <div class="col-lg-5">
  <div class="cap-card"><h2><i class="fas fa-ban me-2 text-danger"></i>Contrôler un TAEG</h2>
    <label class="form-label small mb-0" for="us_cat">Catégorie de prêt</label>
    <select class="form-select mb-2" id="us_cat"><?php foreach ($cats as $i => $c): ?><option value="<?= (int)$i ?>"><?= e(($c['groupe'] ? $c['groupe'] . ' – ' : '') . $c['libelle']) ?></option><?php endforeach; ?></select>
    <label class="form-label small mb-0" for="us_taeg">TAEG de l'offre (%)</label>
    <input type="number" min="0" step="0.01" class="form-control" id="us_taeg" value="4.5">
    <div class="form-text">Le TAEG compare à l'usure comprend les intérêts, les frais de dossier, l'assurance exigée et la garantie.</div>
  </div></div>
 <div class="col-lg-7">
  <div class="cap-res js-result"><div class="lbl" id="ur_per">Période d'application : <?= e($ud['periode'] ?? '') ?><?= !empty($ud['du']) ? ' (du ' . e($fmtD($ud['du'])) . ($ud['au'] ? ' au ' . e($fmtD($ud['au'])) : '') . ')' : '' ?></div>
    <div class="big" id="ur_seuil">–</div><div class="lbl mt-1" id="ur_verdict"></div></div>
  <div class="cap-card js-result"><h2>Seuils en vigueur</h2>
    <?php $last = null; foreach ($cats as $c): if ($c['groupe'] !== $last): $last = $c['groupe']; ?><div class="fw-semibold mt-2 mb-1"><?= e($last ?: 'Autres catégories') ?></div><?php endif; ?>
    <div class="d-flex justify-content-between border-bottom py-1"><span><?= e($c['libelle']) ?></span><strong><?= e(number_format((float)$c['taux'], 2, ',', ' ')) ?> %</strong></div>
    <?php endforeach; if (!$cats): ?><div class="text-muted">Aucun seuil renseigné.</div><?php endif; ?>
    <div class="form-text mt-2">Les seuils sont publiés par la Banque de France chaque trimestre (au Journal officiel) et s'appliquent aux offres émises pendant la période. Vérifiez la publication en vigueur avant toute décision.</div></div>
 </div>
</div>
<script>
(function(){
  const C=<?= json_encode($cats) ?>, $=id=>document.getElementById(id), f=v=>v.toFixed(2).replace('.',',')+' %';
  function run(){
    const c=C[parseInt($('us_cat').value)]; if(!c){$('ur_seuil').textContent='–';return;}
    const t=parseFloat($('us_taeg').value);
    $('ur_seuil').textContent=f(+c.taux);
    $('ur_verdict').innerHTML=isNaN(t)?'':(t>c.taux?`<span style="color:#f87171"><i class="fas fa-circle-xmark me-1"></i>Le TAEG de ${f(t)} dépasse le seuil de l'usure de ${f(+c.taux)} (excédent de ${(t-c.taux).toFixed(2).replace('.',',')} point${t-c.taux>=0.02?'s':''}) : l'offre ne peut pas être émise en l'état.</span>`:`<span style="color:#4ade80"><i class="fas fa-circle-check me-1"></i>Le TAEG de ${f(t)} est sous le seuil de l'usure : marge de ${(c.taux-t).toFixed(2).replace('.',',')} point${c.taux-t>=0.02?'s':''}.</span>`);
  }
  $('us_cat').addEventListener('change',run);$('us_taeg').addEventListener('input',run);run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
