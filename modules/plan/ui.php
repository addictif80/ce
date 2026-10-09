<?php
// Plan de financement complet (PTZ, Doublissimo, Primo Jeune, Primoz, Grandioz, prêt principal), partagé par le portail (avec enregistrement)
// et /tools (sans). Les calculs sont faits par le moteur du module crédit immobilier. Variables : $capSave (bool), $capLoad (array|null), $planZonageUrl (string).
$capLoad = $capLoad ?? null;
$planZonageUrl = $planZonageUrl ?? 'zonage.php';
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['ptz', 'doublissimo', 'primo_jeune']) ?>
<div class="row g-3">
 <div class="col-xl-4 col-lg-5">
  <?php require __DIR__ . '/form.php'; ?>
 </div>
 <div class="col-xl-3 col-lg-7 order-xl-3">
  <div class="cap-card"><h2><i class="fas fa-layer-group me-2 text-danger"></i>Le financement</h2>
    <div class="fw-semibold mb-1">Prêt principal</div>
    <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small mb-0">Taux (%)</label><input type="number" min="0" step="any" class="form-control" id="pf_pt" value="3.55"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="30" step="1" class="form-control" id="pf_pd" value="20"></div></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pf_ptz" checked><label class="form-check-label" for="pf_ptz">PTZ (montant maximum calculé)</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pf_dbl" checked><label class="form-check-label" for="pf_dbl">Doublissimo</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pf_pj"><label class="form-check-label" for="pf_pj">Primo Jeune 0 % (35 ans maximum, PTZ obligatoire)</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pf_pz"><label class="form-check-label" for="pf_pz">Primoz (différé de longue durée)</label></div>
    <div id="pf_pzbox" class="ms-3 mb-2" style="display:none"><div class="row g-1">
      <div class="col-6"><input type="number" min="0" step="any" class="form-control form-control-sm" id="pf_pzm" placeholder="Montant (auto)"></div>
      <div class="col-6"><input type="number" min="0" step="any" class="form-control form-control-sm" id="pf_pzt" placeholder="Taux %" value="3.2"></div>
      <div class="col-6"><input type="number" min="0" step="12" class="form-control form-control-sm" id="pf_pzd" placeholder="Durée (mois)" value="240"></div>
      <div class="col-6"><input type="number" min="0" step="12" class="form-control form-control-sm" id="pf_pzdf" placeholder="Différé (mois)" value="120"></div></div></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pf_gr"><label class="form-check-label" for="pf_gr">Grandioz (échéances progressives, sans PTZ)</label></div>
    <div id="pf_grbox" class="ms-3 mb-2" style="display:none"><div class="row g-1">
      <div class="col-6"><input type="number" min="0" step="any" class="form-control form-control-sm" id="pf_grm" placeholder="Montant" value="60000"></div>
      <div class="col-6"><input type="number" min="0" step="any" class="form-control form-control-sm" id="pf_grt" placeholder="Taux %" value="3.7"></div>
      <div class="col-6"><input type="number" min="0" step="12" class="form-control form-control-sm" id="pf_grd" placeholder="Durée (mois)" value="240"></div>
      <div class="col-6"><input type="number" min="0" step="any" class="form-control form-control-sm" id="pf_grp" placeholder="Progression %" value="1"></div></div></div>
    <div class="form-text">Le prêt principal couvre le solde à financer. Les montants du PTZ, du Doublissimo, du Primo Jeune et du Primoz par défaut sont calculés d'après les barèmes ; les taux des prêts spéciaux sont à saisir.</div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'plPrepare'); ?>
 </div>
 <div class="col-xl-5 order-xl-2">
  <div class="cap-res js-result"><div class="lbl">Mensualité tout inclus (assurance comprise, PTZ après différé)</div><div class="big" id="plr_mens">–</div>
    <div class="row mt-3 g-2">
      <div class="col-6"><div class="lbl">Taux d'endettement</div><div class="fs-5 fw-bold" id="plr_te">–</div></div>
      <div class="col-6"><div class="lbl">Reste à vivre</div><div class="fs-5 fw-bold" id="plr_rav">–</div></div>
      <div class="col-6"><div class="lbl">TAEG global</div><div class="fs-6 fw-bold" id="plr_taeg">–</div></div>
      <div class="col-6"><div class="lbl">Coût total du crédit</div><div class="fs-6 fw-bold" id="plr_cout">–</div></div></div></div>
  <div id="plr_alerts"></div>
  <div class="cap-card js-result"><h2>Plan de financement</h2><table class="table table-sm mb-0"><tbody id="plr_plan"></tbody></table></div>
  <div class="cap-card js-result"><h2>Les prêts</h2><div class="table-responsive"><table class="table table-sm mb-0 align-middle"><thead><tr><th>Prêt</th><th class="text-end">Montant</th><th class="text-end">Durée</th><th class="text-end">Taux</th><th class="text-end">Hors ass.</th><th class="text-end">Tout inclus</th><th class="text-end">TAEG</th></tr></thead><tbody id="plr_lines"></tbody></table></div>
    <div class="form-text mt-2">Assurance de la première échéance (capital restant dû × taux). PTZ : mensualité après le différé, assurance seule pendant le différé.</div></div>
 </div>
</div>
<?php require __DIR__ . '/engine.php'; ?>
<script>
(function(){
  const $=id=>document.getElementById(id), N=planAdapter.N;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const FIN=['pt','pd','ptz','dbl','pj','pz','pzm','pzt','pzd','pzdf','gr','grm','grt','grd','grp'];
  const chk=id=>$(id).checked;
  function readFin(f){
    f.principal={taux:N($('pf_pt').value),duree:Math.round(N($('pf_pd').value)*12)};
    f.ptz={on:chk('pf_ptz')}; f.dbl={on:chk('pf_dbl')}; f.pj={on:chk('pf_pj')};
    f.pz={on:chk('pf_pz'),montant:$('pf_pzm').value,taux:$('pf_pzt').value,duree:$('pf_pzd').value,differe:$('pf_pzdf').value};
    f.gr={on:chk('pf_gr'),montant:$('pf_grm').value,taux:$('pf_grt').value,duree:$('pf_grd').value,prog:$('pf_grp').value};
    return f;
  }
  function run(){
    $('pf_pzbox').style.display=chk('pf_pz')?'':'none'; $('pf_grbox').style.display=chk('pf_gr')?'':'none';
    const f=readFin(planForm.read()), r=planAdapter.run(f), c=r.c, d=r.d;
    $('plr_mens').textContent=eur2(c.mensTout);
    $('plr_te').innerHTML=badgeEndett(c.te); $('plr_rav').innerHTML=eur(c.reste)+'<div class="small" style="opacity:.75">'+eur(c.restePers||0)+' / pers.</div>';
    $('plr_taeg').textContent=c.taegGlobal?taegTxt(c.taegGlobal):'–'; $('plr_cout').textContent=eur(c.coutCredit);
    // alertes
    let al='';
    if(d.ptz_actif==1&&typeof ciPtzMaxHtml==='function') al+=ciPtzMaxHtml(d,null).replace('alert-secondary','alert-secondary mb-2');
    al+=r.controles.map(x=>`<div class="alert alert-${x.niv==='erreur'?'danger':'warning'} py-1 small mb-2"><i class="fas fa-${x.niv==='erreur'?'circle-xmark':'triangle-exclamation'} me-1"></i>${x.txt}</div>`).join('');
    $('plr_alerts').innerHTML=al;
    // plan
    const rows=[['Acquisition',d.montant_acquisition],['Travaux',d.montant_travaux],['Frais de notaire',d.frais_notaire],['Frais d\'agence / négociation',d.frais_negociation],['Frais divers',d.frais_divers]].filter(x=>N(x[1])>0).map(x=>`<tr><td>${x[0]}</td><td class="text-end">${eur(x[1])}</td></tr>`);
    rows.push(`<tr class="fw-bold"><td>Coût du projet</td><td class="text-end">${eur(getCoutProjet(d))}</td></tr>`);
    [['+ Garantie',d.garantie_montant,1],['+ Frais de dossier',c.fraisDossier,1],['− Apport',d.apport,-1],['− Prêt 1 % patronal',d.pret_patronal,-1]].forEach(x=>{if(N(x[1])>0) rows.push(`<tr><td>${x[0]}</td><td class="text-end">${eur(x[1])}</td></tr>`);});
    rows.push(`<tr class="fw-bold"><td>Total à financer par prêts</td><td class="text-end">${eur(c.capital)}</td></tr>`);
    $('plr_plan').innerHTML=rows.join('');
    // prêts
    const L=c.lignes.map((l,i)=>`<tr><td>${l.libelle}${l.primoz?` <span class="badge text-bg-light">différé ${l.differe} m</span>`:''}${l.grandioz?' <span class="badge text-bg-light">progressif</span>':''}</td><td class="text-end">${eur(l.montant)}</td><td class="text-end">${l.duree} m</td><td class="text-end">${N(l.taux).toFixed(2).replace('.',',')} %</td><td class="text-end">${eur2(c.sch[i].mens)}</td><td class="text-end"><strong>${eur2(c.sch[i].mens+c.lineIns[i])}</strong></td><td class="text-end">${c.taegLignes[i]?taegTxt(c.taegLignes[i]):'–'}</td></tr>`);
    if(d.ptz_actif==1) L.push(`<tr><td>PTZ <span class="badge text-bg-light">différé ${ptzDiffere(d)} m</span></td><td class="text-end">${eur(d.ptz_montant)}</td><td class="text-end">${d.ptz_duree} m</td><td class="text-end">0,00 %</td><td class="text-end">${eur2(c.mensPTZ)}</td><td class="text-end"><strong>${eur2(c.mensPTZ+c.insPtzM)}</strong></td><td class="text-end">${c.taegPtz?taegTxt(c.taegPtz):'–'}</td></tr>`);
    $('plr_lines').innerHTML=L.join('');
    return {f,c,d};
  }
  planForm.onChange(run); FIN.forEach(k=>{$('pf_'+k).addEventListener('input',run);$('pf_'+k).addEventListener('change',run);});
  planForm.initGeo(<?= json_encode($planZonageUrl) ?>);
  window.toolsShare={get:()=>({geo:planForm.geoGet()}),set:o=>{if(o&&o.geo) planForm.geoSet(o.geo);}};
  window.plPrepare=function(f0){const x=run();const fin={};FIN.forEach(k=>fin[k]=$('pf_'+k).type==='checkbox'?$('pf_'+k).checked:$('pf_'+k).value);
    f0.params.value=JSON.stringify({form:planForm.snapshot(),fin,geo:planForm.geoGet()});
    f0.resultat.value=JSON.stringify({mensualite:Math.round(x.c.mensTout*100)/100,endettement:x.c.te===null?null:Math.round(x.c.te*100)/100,capital:Math.round(x.c.capital),projet:Math.round(getCoutProjet(x.d||{}))});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){planForm.apply(load.form);Object.keys(load.fin||{}).forEach(k=>{const el=$('pf_'+k);if(!el)return;if(el.type==='checkbox')el.checked=!!load.fin[k];else el.value=load.fin[k];});if(load.geo)planForm.geoSet(load.geo);}
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
