<?php
// Simulateur d'éligibilité et de montant du PTZ, partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null), $ptzBareme (array).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
?>
<?php if (empty($ptzBareme['valide'])): ?>
<div class="alert alert-warning py-2"><i class="fas fa-triangle-exclamation me-1"></i><strong>Barème provisoire.</strong> Les plafonds et quotités n'ont pas encore été validés : résultat à confirmer avant toute communication au client.</div>
<?php endif; ?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-users me-2 text-danger"></i>Le foyer</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Personnes destinées à occuper le logement</label><input type="number" min="1" max="12" step="1" class="form-control" id="pz_pers" value="2"></div>
      <div class="col-6"><label class="form-label small mb-0">Revenu fiscal de référence (N-2, €)</label><input type="number" min="0" step="any" class="form-control" id="pz_rfr" value="30000"></div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="pz_primo" checked><label class="form-check-label" for="pz_primo">Primo-accédant (pas propriétaire de sa résidence principale depuis 2 ans)</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" id="pz_rp" checked><label class="form-check-label" for="pz_rp">Le logement sera la résidence principale</label></div></div>
    </div></div>
  <div class="cap-card"><h2><i class="fas fa-house me-2 text-danger"></i>L'opération</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Zone du logement</label>
        <select class="form-select" id="pz_zone"><option value="A">A bis / A</option><option value="B1">B1</option><option value="B2">B2</option><option value="C">C</option></select></div>
      <div class="col-6"><label class="form-label small mb-0">Type de bien</label>
        <select class="form-select" id="pz_type"><?php foreach ($ptzBareme['types'] as $k => $t): ?><option value="<?= e($k) ?>"><?= e($t['label']) ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-label small mb-0">Coût de l'opération (€)</label><input type="number" min="0" step="any" class="form-control" id="pz_cout" value="220000"></div>
    </div>
    <div class="form-text">La zone dépend de la commune du bien (A bis, A, B1, B2, C).</div>
  </div>
  <?php if ($capSave) simSaveForm($capLoad, 'pzPrepare'); ?>
 </div>
 <div class="col-lg-6">
  <div class="cap-res js-result">
    <div class="lbl">Montant du PTZ estimé</div><div class="big" id="pr_mont">–</div>
    <div class="mt-2" id="pr_stat"></div>
    <div class="row mt-3 g-3">
      <div class="col-6"><div class="lbl">Tranche de revenus</div><div class="fw-bold" id="pr_tr">–</div></div>
      <div class="col-6"><div class="lbl">Quotité</div><div class="fw-bold" id="pr_q">–</div></div>
      <div class="col-6"><div class="lbl">Durée / différé</div><div class="fw-bold" id="pr_d">–</div></div>
      <div class="col-6"><div class="lbl">Mensualité après différé</div><div class="fw-bold" id="pr_m">–</div></div>
      <div class="col-6"><div class="lbl">Plafond de ressources</div><div class="fw-bold" id="pr_pr">–</div></div>
      <div class="col-6"><div class="lbl">Plafond de prix retenu</div><div class="fw-bold" id="pr_po">–</div></div>
    </div>
  </div>
  <p class="small text-muted">Barème <?= e($ptzBareme['millesime'] ?? '') ?>. Simulation indicative : l'éligibilité définitive est vérifiée sur l'offre de prêt et les justificatifs.</p>
 </div>
</div>
<script>
(function(){
  const B=<?= json_encode($ptzBareme) ?>;
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const NUM=['pers','rfr','cout'];
  function calc(p){
    const np=Math.max(1,Math.round(p.pers)), z=p.zone, t=B.types[p.type];
    const coeff=B.coeff_familial[Math.min(np,5)-1];
    const revenu=Math.max(p.rfr,p.cout/B.diviseur_cout);          // revenu retenu : le plus élevé du RFR et du coût / 9
    const maxRev=B.plafonds_ressources[z][Math.min(np,8)-1];
    const lim=B.tranches[z], rpc=revenu/coeff;                     // revenu par unité de coefficient familial
    let tr=lim.findIndex(l=>rpc<=l); if(tr<0) tr=3;
    const po=B.plafonds_operation[z][Math.min(np,5)-1];
    const out={coeff,revenu,rpc,maxRev,lim,po,errs:[]};
    if(!p.primo) out.errs.push("le PTZ est réservé aux primo-accédants");
    if(!p.rp) out.errs.push("le logement doit être la résidence principale");
    if(!t||!t.zones.includes(z)) out.errs.push("ce type de logement n'est pas éligible dans cette zone");
    if(revenu>maxRev) out.errs.push("revenus retenus ("+eur(revenu)+") supérieurs au plafond de la zone ("+eur(maxRev)+")");
    if(rpc>lim[3]) out.errs.push("revenus au-delà de la tranche 4");
    out.tr=tr; out.q=t?t.quotites[tr]:0;
    out.prix=Math.min(p.cout,po); out.mont=out.errs.length?0:Math.round(out.prix*out.q/100);
    out.d=B.durees[tr]; out.mens=out.mont>0&&out.d.total>out.d.differe?out.mont/((out.d.total-out.d.differe)*12):0;
    return out;
  }
  function params(){return {pers:n('pz_pers'),rfr:n('pz_rfr'),primo:$('pz_primo').checked,rp:$('pz_rp').checked,zone:$('pz_zone').value,type:$('pz_type').value,cout:n('pz_cout')};}
  function run(){
    const p=params(), r=calc(p);
    $('pr_mont').textContent=eur(r.mont);
    $('pr_stat').innerHTML=r.errs.length?'<span style="color:#f87171"><i class="fas fa-circle-xmark me-1"></i>Non éligible : '+r.errs.join(' ; ')+'.</span>':'<span style="color:#4ade80"><i class="fas fa-circle-check me-1"></i>Éligible sur la base des informations saisies.</span>';
    $('pr_tr').textContent='Tranche '+(r.tr+1)+' (revenu retenu '+eur(r.revenu)+')';
    $('pr_q').textContent=r.q+' % du prix plafonné ('+eur(r.prix)+')';
    $('pr_d').textContent=r.d.total+' ans dont '+r.d.differe+' ans de différé';
    $('pr_m').textContent=r.mens>0?eur2(r.mens):'–';
    $('pr_pr').textContent=eur(r.maxRev); $('pr_po').textContent=eur(r.po);
    return {p,r};
  }
  ['pers','rfr','primo','rp','zone','type','cout'].forEach(k=>$('pz_'+k).addEventListener('input',run));
  window.pzPrepare=function(f){const x=run();f.params.value=JSON.stringify(x.p);f.resultat.value=JSON.stringify({ptz:x.r.mont,eligible:x.r.errs.length===0,tranche:x.r.tr+1,duree:x.r.d.total*12,differe:x.r.d.differe*12});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){NUM.forEach(k=>{if(load[k]!==undefined)$('pz_'+k).value=load[k];});['zone','type'].forEach(k=>{if(load[k])$('pz_'+k).value=load[k];});if('primo' in load)$('pz_primo').checked=!!load.primo;if('rp' in load)$('pz_rp').checked=!!load.rp;}
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
