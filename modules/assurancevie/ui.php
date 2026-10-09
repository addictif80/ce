<?php
// Simulateur d'assurance-vie : projection, fiscalité d'un rachat et capital décès, partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
$fis = baremeGet('fiscalite')['data'];
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['fiscalite']) ?>
<ul class="nav nav-pills mb-3 no-print">
  <?php foreach (['proj' => 'Projection du contrat', 'rach' => 'Fiscalité d\'un rachat', 'deces' => 'Capital décès'] as $k => $l): ?>
  <li class="nav-item"><button type="button" class="nav-link <?= $k === 'proj' ? 'active' : '' ?>" data-av="<?= $k ?>"><?= e($l) ?></button></li>
  <?php endforeach; ?>
</ul>
<div class="row g-3" data-avpane="proj">
 <div class="col-lg-5"><div class="cap-card"><h2><i class="fas fa-chart-line me-2 text-danger"></i>Le contrat</h2>
  <div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0">Versement initial (€)</label><input type="number" min="0" step="any" class="form-control" id="av_c0" value="10000"></div>
    <div class="col-6"><label class="form-label small mb-0">Versement mensuel (€)</label><input type="number" min="0" step="any" class="form-control" id="av_v" value="150"></div>
    <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="60" class="form-control" id="av_n" value="15"></div>
    <div class="col-6"><label class="form-label small mb-0">Rendement annuel brut (%)</label><input type="number" min="0" step="any" class="form-control" id="av_r" value="3"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais de gestion annuels (%)</label><input type="number" min="0" step="any" class="form-control" id="av_f" value="0.8"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais sur versements (%)</label><input type="number" min="0" step="any" class="form-control" id="av_fv" value="2"></div>
  </div></div></div>
 <div class="col-lg-7"><div class="cap-res js-result"><div class="lbl">Valeur estimée du contrat avant fiscalité</div><div class="big" id="avp_val">–</div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="avp_det"></tbody></table></div>
    <p class="small text-muted">Projection à rendement constant, indicative : le rendement des unités de compte n'est pas garanti. La fiscalité en cas de rachat est détaillée dans l'onglet suivant.</p></div>
</div>
<div class="row g-3" data-avpane="rach" hidden>
 <div class="col-lg-5"><div class="cap-card"><h2><i class="fas fa-hand-holding-dollar me-2 text-danger"></i>Le rachat</h2>
  <div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0">Valeur du contrat (€)</label><input type="number" min="0" step="any" class="form-control" id="av_V" value="60000"></div>
    <div class="col-6"><label class="form-label small mb-0">Primes versées (€)</label><input type="number" min="0" step="any" class="form-control" id="av_P" value="45000"></div>
    <div class="col-6"><label class="form-label small mb-0">Montant du rachat (€)</label><input type="number" min="0" step="any" class="form-control" id="av_R" value="15000"></div>
    <div class="col-6"><label class="form-label small mb-0">Ancienneté du contrat</label><select class="form-select" id="av_anc"><option value="8">8 ans ou plus</option><option value="0">Moins de 8 ans</option></select></div>
    <div class="col-12"><label class="form-label small mb-0">Situation fiscale</label><select class="form-select" id="av_sit"><option value="seul">Personne seule</option><option value="couple">Couple (imposition commune)</option></select></div>
  </div>
  <div class="form-text">Estimation : les prélèvements sociaux des fonds en euros sont en réalité prélevés chaque année ; ils ne sont comptés ici que sur la part de gains rachetée. L'abattement annuel est supposé entièrement disponible.</div></div></div>
 <div class="col-lg-7"><div class="cap-res js-result"><div class="lbl">Montant net perçu après impôts et prélèvements</div><div class="big" id="avr_net">–</div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="avr_det"></tbody></table></div></div>
</div>
<div class="row g-3" data-avpane="deces" hidden>
 <div class="col-lg-5"><div class="cap-card"><h2><i class="fas fa-people-roof me-2 text-danger"></i>Le capital décès</h2>
  <div class="row g-2">
    <div class="col-12"><label class="form-label small mb-0">Capital transmis (primes versées avant 70 ans) (€)</label><input type="number" min="0" step="any" class="form-control" id="av_K" value="400000"></div>
    <div class="col-6"><label class="form-label small mb-0">Nombre de bénéficiaires</label><input type="number" min="1" max="20" class="form-control" id="av_nb" value="2"></div>
    <div class="col-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="av_conj"><label class="form-check-label small" for="av_conj">Bénéficiaire = conjoint ou partenaire de PACS (exonéré)</label></div></div>
  </div>
  <div class="form-text">Répartition à parts égales. Les primes versées après 70 ans suivent un autre régime (abattement global de 30 500 € puis droits de succession) : elles ne sont pas calculées ici.</div></div></div>
 <div class="col-lg-7"><div class="cap-res js-result"><div class="lbl">Prélèvement estimé sur le capital décès</div><div class="big" id="avd_tax">–</div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="avd_det"></tbody></table></div></div>
</div>
<?php if (!empty($capSave)) simSaveForm($capLoad, 'avPrepare'); ?>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const F=<?= json_encode($fis) ?>;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const rows=(id,a)=>$(id).innerHTML=a.map(x=>`<tr><td>${x[0]}</td><td class="text-end">${x[1]}</td></tr>`).join('');
  function proj(){
    const y=Math.max(1,n('av_n')), M=y*12, fv=n('av_fv')/100, rn=n('av_r')-n('av_f'), i=Math.pow(1+rn/100,1/12)-1;
    let bal=n('av_c0')*(1-fv), paid=n('av_c0'), fees=n('av_c0')*fv;
    for(let m=0;m<M;m++){bal=bal*(1+i)+n('av_v')*(1-fv);paid+=n('av_v');fees+=n('av_v')*fv;}
    $('avp_val').textContent=eur(bal);
    rows('avp_det',[['Total versé',eur(paid)],['Frais sur versements',eur(fees)],['Gains estimés',eur(Math.max(0,bal-paid+fees))],['Rendement net annuel retenu',rn.toFixed(2).replace('.',',')+' %']]);
    return {bal,paid};
  }
  function rach(){
    const V=n('av_V'),P=n('av_P'),R=Math.min(n('av_R'),V), anc=parseInt($('av_anc').value), couple=$('av_sit').value==='couple';
    const gains=V>0?R*Math.max(0,(V-P)/V):0, ps=gains*F.ps_assurance_vie/100;
    let ir,det=[];
    if(anc>=8){
      const ab=couple?F.av_abattement_couple:F.av_abattement_seul, base=Math.max(0,gains-ab);
      const taux=P<=F.av_seuil_primes?F.av_taux_8ans:(F.av_seuil_primes/P*F.av_taux_8ans+(1-F.av_seuil_primes/P)*F.pfu_ir);
      ir=base*taux/100; det=[['Part de gains dans le rachat',eur2(gains)],['Abattement annuel',eur2(Math.min(gains,ab))],['Gains imposables',eur2(base)],['Impôt ('+taux.toFixed(2).replace('.',',')+' %)',eur2(ir)]];
    } else {ir=gains*F.pfu_ir/100; det=[['Part de gains dans le rachat',eur2(gains)],['Impôt forfaitaire ('+String(F.pfu_ir).replace('.',',')+' %)',eur2(ir)]];}
    det.push(['Prélèvements sociaux ('+String(F.ps_assurance_vie).replace('.',',')+' %)',eur2(ps)],['Total impôts et prélèvements',eur2(ir+ps)]);
    $('avr_net').textContent=eur(R-ir-ps); rows('avr_det',det);
    return {R,net:R-ir-ps,impots:ir+ps};
  }
  function deces(){
    const K=n('av_K'), nb=Math.max(1,Math.floor(n('av_nb'))), conj=$('av_conj').checked, part=K/nb;
    const tx=Math.max(0,part-F.av_990i_abattement), t1=Math.min(tx,F.av_990i_seuil), t2=Math.max(0,tx-F.av_990i_seuil);
    const per=conj?0:t1*F.av_990i_taux1/100+t2*F.av_990i_taux2/100, tot=per*nb;
    $('avd_tax').textContent=eur(tot);
    rows('avd_det',[['Part par bénéficiaire',eur(part)],['Abattement par bénéficiaire',conj?'Exonéré (conjoint / PACS)':eur(F.av_990i_abattement)],['Part imposable par bénéficiaire',eur(conj?0:tx)],['Prélèvement par bénéficiaire',eur(per)],['Net perçu par bénéficiaire',eur(part-per)]]);
    return {K,nb,tot};
  }
  function run(){return {p:proj(),r:rach(),d:deces()};}
  const IDS=['c0','v','n','r','f','fv','V','P','R','anc','sit','K','nb','conj'];
  IDS.forEach(k=>{const el=$('av_'+k);el.addEventListener('input',run);el.addEventListener('change',run);});
  document.querySelectorAll('[data-av]').forEach(b=>b.onclick=()=>{
    document.querySelectorAll('[data-av]').forEach(x=>x.classList.toggle('active',x===b));
    document.querySelectorAll('[data-avpane]').forEach(p=>p.hidden=p.dataset.avpane!==b.dataset.av);
  });
  window.avPrepare=function(f){const x=run();const p={};IDS.forEach(k=>{const el=$('av_'+k);p[k]=el.type==='checkbox'?el.checked:el.value;});f.params.value=JSON.stringify(p);f.resultat.value=JSON.stringify({valeur:Math.round(x.p.bal),rachat_net:Math.round(x.r.net),deces:Math.round(x.d.tot)});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{const el=$('av_'+k);if(!el||load[k]===undefined) return;if(el.type==='checkbox') el.checked=!!load[k]; else el.value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
