<?php
// Simulateur des crédits spéciaux Caisse d'Épargne (PTZ, Doublissimo, Primo Jeune 0 %, Primoz, Grandioz) : éligibilité et montant,
// partagé par le portail (avec enregistrement) et /tools (sans). Calcul par le moteur du module crédit immobilier.
// Variables : $capSave (bool), $capLoad (array|null), $planZonageUrl (string).
$capLoad = $capLoad ?? null;
$planZonageUrl = $planZonageUrl ?? 'zonage.php';
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['ptz', 'doublissimo', 'primo_jeune', 'primoz', 'grandioz']) ?>
<div class="row g-3">
 <div class="col-xl-4 col-lg-5">
  <?php require __DIR__ . '/../plan/form.php'; ?>
  <div class="cap-card"><h2><i class="fas fa-sliders me-2 text-danger"></i>Hypothèses du prêt principal</h2>
    <div class="row g-2"><div class="col-6"><label class="form-label small mb-0">Taux (%)</label><input type="number" min="0" step="any" class="form-control" id="cs_pt" value="3.55"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="30" step="1" class="form-control" id="cs_pd" value="20"></div>
      <div class="col-6"><label class="form-label small mb-0">Taux du Primoz (%)</label><input type="number" min="0" step="any" class="form-control" id="cs_zt" value="3.2"></div>
      <div class="col-6"><label class="form-label small mb-0">Taux du Grandioz (%)</label><input type="number" min="0" step="any" class="form-control" id="cs_gt" value="3.7"></div>
      <div class="col-6"><label class="form-label small mb-0">Montant du Grandioz envisagé (€)</label><input type="number" min="0" step="any" class="form-control" id="cs_gm" value="60000"></div></div>
    <div class="form-text">Les taux des prêts spéciaux sont fixés par la Caisse d'Épargne : saisissez ceux du barème en vigueur.</div></div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'csPrepare'); ?>
 </div>
 <div class="col-xl-8 col-lg-7"><div id="cs_cards"></div>
  <p class="small text-muted">Simulation indicative : l'éligibilité définitive dépend de l'étude du dossier et des règles internes en vigueur. Chaque montant est calculé indépendamment des autres prêts (le PTZ n'est pas déduit des autres montants).</p></div>
</div>
<?php require __DIR__ . '/../plan/engine.php'; ?>
<script>
(function(){
  const $=id=>document.getElementById(id), N=planAdapter.N;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const dp=()=>{const p=dblParams(),c=p.campagne||{};return {pct:p.pourcentage,plafond:dblCampagneActive()?(c.plafond_agence+' € (agence) / '+c.plafond_prescription+' € (prescription)'):p.plafond+' €',taux:dblCampagneActive()?c.taux:null};};
  function scenario(patch){
    const f=planForm.read(); f.principal={taux:N($('cs_pt').value),duree:Math.round(N($('cs_pd').value)*12)};
    f.ptz={on:false};f.dbl={on:false};f.pj={on:false};f.pz={on:false};f.gr={on:false};
    return planAdapter.run(Object.assign(f,patch(f)));
  }
  const PRODUCTS=[
    {k:'ptz',name:'Prêt à taux zéro (PTZ)',icon:'fa-percent',re:/PTZ/,patch:()=>({ptz:{on:true}}),line:null},
    {k:'dbl',name:'Doublissimo',icon:'fa-clone',re:/Doublissimo/,patch:()=>({dbl:{on:true}}),flag:'doublissimo'},
    {k:'pj',name:'Primo Jeune 0 %',icon:'fa-child',re:/Primo Jeune/,patch:()=>({ptz:{on:true},pj:{on:true}}),flag:'primo_jeune'},
    {k:'pz',name:'Primoz',icon:'fa-hourglass-half',re:/Primoz/,patch:()=>({pz:{on:true,taux:$('cs_zt').value}}),flag:'primoz'},
    {k:'gr',name:'Grandioz',icon:'fa-arrow-trend-up',re:/Grandioz/,patch:()=>({gr:{on:true,montant:$('cs_gm').value,taux:$('cs_gt').value}}),flag:'grandioz'},
  ];
  function card(p){
    const r=scenario(p.patch), c=r.c, d=r.d;
    let msgs=r.controles.filter(x=>p.re.test(x.txt)&&!/attestation sur l'honneur/.test(x.txt));
    if(p.k==='ptz'&&r.ptzInfo){ if(r.ptzInfo.indispo) msgs=[{niv:'alerte',txt:'Calcul impossible : '+r.ptzInfo.indispo}]; else if(r.ptzInfo.errs&&r.ptzInfo.errs.length) msgs=r.ptzInfo.errs.map(t=>({niv:'erreur',txt:t})); }
    const err=msgs.some(x=>x.niv==='erreur'), warn=msgs.length>0;
    const st=err?['danger','Non éligible']:warn?['warning','À vérifier']:['success','Conditions réunies'];
    let body='';
    if(p.k==='ptz'){
      const i=r.ptzInfo; body=(i&&!i.indispo&&!(i.errs&&i.errs.length))?`<div class="fs-4 fw-bold">${eur(i.mont)}</div><div class="small text-muted">${i.d.total*12} mois dont ${i.d.differe*12} mois de différé · tranche ${i.tr+1}, ${i.q} % de ${eur(i.prix)}</div>`:'<div class="text-muted">Aucun montant calculable.</div>';
    } else {
      const li=c.lignes.findIndex(l=>l[p.flag]); const l=c.lignes[li];
      body=l&&N(l.montant)>0?`<div class="fs-4 fw-bold">${eur(l.montant)}</div><div class="small text-muted">${l.duree} mois à ${N(l.taux).toFixed(2).replace('.',',')} % · mensualité ${eur2(c.sch[li].mens+c.lineIns[li])} assurance comprise (taux d'assurance saisi)</div>${p.k==='pz'?`<div class="small text-muted">différé de ${l.differe} mois puis amortissement</div>`:''}${p.k==='gr'?`<div class="small text-muted">échéances progressives de ${l.progression} % par an : de ${eur2(c.sch[li].mens)} (hors assurance) la première année à ${eur2(c.sch[li].rows[c.sch[li].rows.length-1].pay)} la dernière</div>`:''}`:'<div class="text-muted">Aucun montant calculable avec ces données.</div>';
    }
    const rules=({ptz:()=>'Primo-accédant, résidence principale, revenus sous plafond selon la zone, opération éligible.',dbl:()=>{const x=dp();return x.pct+' % du financement total (coût du projet − apport − prêt patronal), plafonné à '+x.plafond+(x.taux!==null?' · taux de la campagne '+String(x.taux).replace('.',',')+' %':'')+'. Sans PTZ : joindre l\'attestation sur l\'honneur de primo-accession.';},pj:()=>pjParams().pourcentage+' % du montant financé, '+pjParams().plafond+' € maximum, durée maximale '+pjParams().duree_max_mois/12+' ans, taux 0 %, sans frais ; PTZ obligatoire ; un emprunteur de '+pjParams().age_max+' ans ou moins.',pz:()=>pzParams().pct_min+' à '+pzParams().pct_max+' % du financement ('+pzParams().montant_min+' à '+pzParams().montant_max+' €), durée '+pzParams().duree_min_mois/12+' à '+pzParams().duree_max_mois/12+' ans dont différé de '+pzParams().differe_min_mois/12+' à '+pzParams().differe_max_mois/12+' ans ; emprunteurs de moins de 36 ans.',gr:()=>'Échéances progressives ('+grParams().progression+' % par an), financement minimum '+grParams().montant_min+' €, durée '+grParams().duree_min_mois/12+' à '+grParams().duree_max_mois/12+' ans ; ne se combine pas avec le PTZ.'})[p.k]();
    return `<div class="cap-card"><div class="d-flex justify-content-between align-items-start flex-wrap gap-2"><h2 class="mb-2 border-0 pb-0"><i class="fas ${p.icon} me-2 text-danger"></i>${p.name}</h2><span class="badge text-bg-${st[0]}">${st[1]}</span></div>${body}<div class="small mt-2">${rules}</div>${msgs.length?'<ul class="small mt-2 mb-0 ps-3">'+msgs.map(x=>`<li class="${x.niv==='erreur'?'text-danger':'text-warning-emphasis'}">${x.txt}</li>`).join('')+'</ul>':''}</div>`;
  }
  function run(){$('cs_cards').innerHTML=PRODUCTS.map(card).join('');}
  planForm.onChange(run); ['pt','pd','zt','gt','gm'].forEach(k=>{$('cs_'+k).addEventListener('input',run);});
  planForm.initGeo(<?= json_encode($planZonageUrl) ?>);
  window.toolsShare={get:()=>({geo:planForm.geoGet()}),set:o=>{if(o&&o.geo) planForm.geoSet(o.geo);}};
  window.csPrepare=function(f0){run();const r=scenario(()=>({ptz:{on:true}}));const o={};['pt','pd','zt','gt','gm'].forEach(k=>o[k]=$('cs_'+k).value);
    f0.params.value=JSON.stringify({form:planForm.snapshot(),hyp:o,geo:planForm.geoGet()});
    const res={};PRODUCTS.forEach(p=>{const rr=scenario(p.patch);if(p.k==='ptz'){res.ptz=rr.ptzInfo&&!rr.ptzInfo.indispo?rr.ptzInfo.mont||0:0;}else{const l=rr.c.lignes.find(x=>x[p.flag]);res[p.k]=l?Math.round(N(l.montant)):0;}});
    f0.resultat.value=JSON.stringify(res);return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){planForm.apply(load.form);Object.keys(load.hyp||{}).forEach(k=>{if($('cs_'+k))$('cs_'+k).value=load.hyp[k];});if(load.geo)planForm.geoSet(load.geo);}
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
