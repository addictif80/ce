<?php
// Quotité saisissable sur rémunération et solde bancaire insaisissable, partagés par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
$sbar = baremeGet('saisie'); $sd = $sbar['data'];
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['saisie']) ?>
<div class="row g-3">
 <div class="col-lg-5">
  <div class="cap-card"><h2><i class="fas fa-user me-2 text-danger"></i>Le débiteur</h2>
    <div class="row g-2">
      <div class="col-12"><label class="form-label small mb-0">Rémunération nette mensuelle saisissable (€)</label><input type="number" min="0" step="any" class="form-control" id="sq_rev" value="1800"></div>
      <div class="col-12"><label class="form-label small mb-0">Personnes à charge</label><input type="number" min="0" max="15" step="1" class="form-control" id="sq_dep" value="0"></div>
    </div>
    <div class="form-text">Rémunération nette de cotisations sociales, y compris primes et accessoires. Chaque personne à charge relève les seuils de <?= number_format($sd['charge_annuelle'] / 12, 2, ',', ' ') ?> € par mois, sur justificatif.</div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'sqPrepare'); ?>
 </div>
 <div class="col-lg-7">
  <div class="cap-res js-result">
    <div class="lbl">Part saisissable de la rémunération</div><div class="big" id="sr_sais">–</div>
    <div class="lbl mt-1" id="sr_note"></div>
    <div class="row mt-3"><div class="col-6"><div class="lbl">Reste disponible pour le débiteur</div><div class="fs-5 fw-bold" id="sr_reste">–</div></div>
      <div class="col-6"><div class="lbl">Solde bancaire insaisissable</div><div class="fs-5 fw-bold"><?= number_format($sd['rsa_mensuel'], 2, ',', ' ') ?> €</div></div></div>
  </div>
  <div class="cap-card"><h2>Détail par tranche (en mensuel)</h2>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tranche</th><th class="text-end">Part de la rémunération</th><th class="text-end">Quotité</th><th class="text-end">Saisissable</th></tr></thead><tbody id="sr_det"></tbody></table></div>
    <div class="form-text mt-2">Le débiteur doit toujours conserver au moins le montant du RSA pour une personne seule (<?= number_format($sd['rsa_mensuel'], 2, ',', ' ') ?> € par mois) : la saisie est plafonnée en conséquence. Ce calcul ne tient pas compte des autres retenues (pension alimentaire, avis à tiers détenteur).</div>
  </div>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const D=<?= json_encode($sd) ?>;
  const eur=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const frac=q=>Math.abs(q-33.33)<0.02?100/3:Math.abs(q-66.67)<0.02?200/3:q;
  const lab=q=>Math.abs(q-5)<0.01?'1/20':Math.abs(q-10)<0.01?'1/10':Math.abs(q-20)<0.01?'1/5':Math.abs(q-25)<0.01?'1/4':Math.abs(q-33.33)<0.02?'1/3':Math.abs(q-66.67)<0.02?'2/3':Math.abs(q-100)<0.01?'Totalité':String(q).replace('.',',')+' %';
  function run(){
    const rev=Math.max(0,n('sq_rev')), dep=Math.max(0,Math.floor(n('sq_dep'))), add=D.charge_annuelle/12*dep;
    const T=D.seuils.map(s=>s/12+add); let prev=0, sais=0, rows='';
    T.forEach((t,i)=>{const part=Math.max(0,Math.min(rev,t)-prev), q=frac(D.quotites[i]), s=part*q/100;
      rows+=`<tr><td>${i===0?'Jusqu\'à ':'De '+eur(prev).replace(',00','')+' à '}${eur(t).replace(',00','')}</td><td class="text-end">${eur(part)}</td><td class="text-end">${lab(D.quotites[i])}</td><td class="text-end">${eur(s)}</td></tr>`;sais+=s;prev=t;});
    const part=Math.max(0,rev-prev), s=part*frac(D.quotites[6])/100; sais+=s;
    rows+=`<tr><td>Au-delà de ${eur(prev).replace(',00','')}</td><td class="text-end">${eur(part)}</td><td class="text-end">${lab(D.quotites[6])}</td><td class="text-end">${eur(s)}</td></tr>`;
    let cap='';
    if(rev-sais<D.rsa_mensuel){const m=Math.max(0,rev-D.rsa_mensuel); if(m<sais){cap='Plafonné : le débiteur conserve au moins le RSA personne seule.';sais=m;}}
    $('sr_sais').textContent=eur(sais); $('sr_reste').textContent=eur(rev-sais); $('sr_note').textContent=cap||(rev>0?(sais/rev*100).toFixed(1).replace('.',',')+' % de la rémunération':'');
    $('sr_det').innerHTML=rows;
    return {rev,dep,sais};
  }
  ['rev','dep'].forEach(k=>$('sq_'+k).addEventListener('input',run));
  window.sqPrepare=function(f){const x=run();f.params.value=JSON.stringify({rev:x.rev,dep:x.dep});f.resultat.value=JSON.stringify({saisissable:Math.round(x.sais*100)/100,rev:x.rev});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){if(load.rev!==undefined)$('sq_rev').value=load.rev;if(load.dep!==undefined)$('sq_dep').value=load.dep;}
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
