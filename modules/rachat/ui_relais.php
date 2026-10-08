<?php
// Simulateur de prêt relais, partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null : simulation à recharger, uniquement si elle est de ce type).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
?>
<?php if (empty($simBarDone)) require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
  <div class="col-lg-6"><div class="cap-card"><h2><i class="fas fa-house me-2 text-danger"></i>Bien à vendre</h2><div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Valeur estimée (€)</label><input type="number" min="0" step="any" class="form-control" id="rl_val" value="250000"></div>
      <div class="col-6"><label class="form-label small mb-0">Capital restant dû (€)</label><input type="number" min="0" step="any" class="form-control" id="rl_crd" value="80000"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais d'agence et de vente (%)</label><input type="number" min="0" step="0.1" class="form-control" id="rl_ag" value="5"></div>
      <div class="col-6"><label class="form-label small mb-0">Quotité maximale de la valeur (%)</label><input type="number" min="0" max="100" step="1" class="form-control" id="rl_q" value="70"></div></div></div>
    <div class="cap-card"><h2><i class="fas fa-percent me-2 text-danger"></i>Conditions du relais</h2><div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Taux (%)</label><input type="number" min="0" step="0.01" class="form-control" id="rl_taux" value="4.2"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (mois, 12 à 24)</label><input type="number" min="1" max="36" step="1" class="form-control" id="rl_dur" value="18"></div>
      <div class="col-6"><label class="form-label small mb-0">Assurance (% du capital / an)</label><input type="number" min="0" step="0.01" class="form-control" id="rl_ass" value="0.3"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais de dossier (€)</label><input type="number" min="0" step="any" class="form-control" id="rl_fd" value="500"></div>
      <div class="col-12"><label class="form-label small mb-0">Montant du relais souhaité (€, vide = maximum)</label><input type="number" min="0" step="any" class="form-control" id="rl_mont" placeholder="Maximum autorisé"></div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="rl_capi"><label class="form-check-label" for="rl_capi" >Intérêts différés (remboursés avec le capital à la vente)</label></div></div></div></div></div>
  <div class="col-lg-6"><div class="cap-res js-result">
      <div class="lbl">Montant du prêt relais</div><div class="big" id="rlr_mont">–</div><div class="small mt-1" id="rlr_max"></div>
      <div class="row mt-3 g-3">
        <div class="col-6"><div class="lbl" id="rlr_intl">Intérêts mensuels</div><div class="fs-5 fw-bold" id="rlr_int">–</div></div>
        <div class="col-6"><div class="lbl">Coût total du relais</div><div class="fs-5 fw-bold" id="rlr_cout">–</div></div>
        <div class="col-6"><div class="lbl">Produit net de la vente (valeur estimée)</div><div class="fw-bold" id="rlr_net">–</div></div>
        <div class="col-6"><div class="lbl">Reste après remboursement du relais</div><div class="fw-bold" id="rlr_reste">–</div></div></div>
      <div class="small mt-2" id="rlr_msg"></div></div>
    <div class="cap-card"><h2>Si le bien se vend moins cher</h2>
      <table class="table table-sm mb-0"><thead><tr><th>Prix de vente</th><th class="text-end">Produit net</th><th class="text-end">Après relais et CRD</th></tr></thead><tbody id="rlr_sc"></tbody></table></div>
    <?php if ($capSave) simSaveForm($capLoad, 'rlPrepare'); ?>
  </div></div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const pmt=(c,t,m)=>{const i=t/1200;return m<=0?0:(i===0?c/m:c*i/(1-Math.pow(1+i,-m)));};
  const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const RL=['val','crd','ag','q','taux','dur','ass','fd'];

  // ── Prêt relais ──
  function relais(){
    const p={}; RL.forEach(k=>p[k]=n('rl_'+k)); p.mont=$('rl_mont').value===''?null:n('rl_mont'); p.capi=$('rl_capi').checked;
    const max=Math.max(0,p.val*p.q/100-p.crd), mont=p.mont===null?max:p.mont;
    const mi=mont*p.taux/1200, ma=mont*p.ass/1200;
    const cout=(mi+ma)*p.dur+p.fd; // intérêts et assurance dus sur la durée, différés ou non
    const net=(prix)=>prix*(1-p.ag/100)-p.crd;
    const apres=(prix)=>net(prix)-mont-(p.capi?(mi*p.dur):0);
    return {p,max,mont,mi,ma,cout,net,apres};
  }
  function runRelais(){
    const r=relais();
    $('rlr_mont').textContent=eur(r.mont); $('rlr_max').textContent='Maximum autorisé : '+eur(r.max)+' ('+r.p.q+' % de la valeur − capital restant dû)';
    $('rlr_intl').textContent=r.p.capi?'Intérêts différés (par mois)':'Intérêts mensuels (à payer)';
    $('rlr_int').textContent=eur2(r.mi+r.ma); $('rlr_cout').textContent=eur(r.cout);
    $('rlr_net').textContent=eur(r.net(r.p.val)); $('rlr_reste').textContent=eur(r.apres(r.p.val));
    $('rlr_msg').innerHTML=(r.mont>r.max+1?'<span style="color:#f87171">Le montant demandé dépasse le maximum autorisé.</span> ':'')+(r.apres(r.p.val)<0?'<span style="color:#f87171">La vente ne suffit pas à rembourser le relais.</span>':'');
    $('rlr_sc').innerHTML=[1,.95,.9,.85,.8].map(f=>`<tr><td>${eur(r.p.val*f)} <span class="text-muted small">(${Math.round(f*100)} %)</span></td><td class="text-end">${eur(r.net(r.p.val*f))}</td><td class="text-end ${r.apres(r.p.val*f)<0?'text-danger fw-bold':''}">${eur(r.apres(r.p.val*f))}</td></tr>`).join('');
    return r;
  }

  RL.forEach(k=>$('rl_'+k).addEventListener('input',runRelais)); $('rl_mont').addEventListener('input',runRelais); $('rl_capi').addEventListener('change',runRelais);

  window.rlPrepare=function(f){const r=runRelais();f.params.value=JSON.stringify({mode:'relais',rl:r.p});f.resultat.value=JSON.stringify({mode:'relais',montant:Math.round(r.mont),cle:'Coût '+eur(r.cout)+', reste '+eur(r.apres(r.p.val))});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load&&load.rl){RL.forEach(k=>{if(load.rl[k]!==undefined)$('rl_'+k).value=load.rl[k];});if(load.rl.mont!==null&&load.rl.mont!==undefined)$('rl_mont').value=load.rl.mont;$('rl_capi').checked=!!load.rl.capi;}
  runRelais();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
