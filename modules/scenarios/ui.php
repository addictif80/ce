<?php
// Comparateur de scénarios de financement (jusqu'à trois montages côte à côte), partagé par le portail (avec enregistrement) et /tools (sans).
// Calcul par le moteur du module crédit immobilier. Variables : $capSave (bool), $capLoad (array|null), $planZonageUrl (string).
$capLoad = $capLoad ?? null;
$planZonageUrl = $planZonageUrl ?? 'zonage.php';
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['ptz', 'doublissimo', 'primo_jeune']) ?>
<div class="row g-3">
 <div class="col-xl-3 col-lg-4"><?php require __DIR__ . '/../plan/form.php'; ?>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'scPrepare'); ?></div>
 <div class="col-xl-9 col-lg-8">
  <div class="cap-card"><h2><i class="fas fa-code-compare me-2 text-danger"></i>Les trois montages</h2>
    <div class="row g-3" id="sc_cols"></div>
    <div class="form-text mt-2">Chaque montage reprend le projet et les emprunteurs ci-contre. Le prêt principal couvre le solde ; les montants du PTZ, du Doublissimo et du Primo Jeune sont calculés d'après les barèmes.</div></div>
  <div class="cap-card js-result"><h2>Comparaison</h2><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead id="sc_head"></thead><tbody id="sc_body"></tbody></table></div>
    <div class="form-text mt-2">Les meilleures valeurs sont soulignées en vert (mensualité, endettement et coût les plus bas, reste à vivre le plus haut).</div></div>
  <div id="sc_alerts"></div>
 </div>
</div>
<?php require __DIR__ . '/../plan/engine.php'; ?>
<script>
(function(){
  const $=id=>document.getElementById(id), N=planAdapter.N;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const DEF=[
    {nom:'Montage classique',apport:'',pt:3.55,pd:20,ptz:1,dbl:1,pj:0,pz:0,gr:0},
    {nom:'Avec Primo Jeune',apport:'',pt:3.55,pd:20,ptz:1,dbl:1,pj:1,pz:0,gr:0},
    {nom:'Durée 25 ans',apport:'',pt:3.7,pd:25,ptz:1,dbl:1,pj:0,pz:0,gr:0}];
  const FLD=['nom','apport','pt','pd','ptz','dbl','pj','pz','gr','pzt','grm','grt'];
  $('sc_cols').innerHTML=DEF.map((d,i)=>`<div class="col-md-4"><div class="border rounded p-2 h-100">
    <input class="form-control form-control-sm fw-bold mb-2" id="sc${i}_nom" value="${d.nom}" aria-label="Nom du montage ${i+1}">
    <div class="row g-1">
      <div class="col-12"><label class="form-label small mb-0">Apport (€) — vide = celui du projet</label><input type="number" min="0" step="any" class="form-control form-control-sm" id="sc${i}_apport" value="${d.apport}"></div>
      <div class="col-6"><label class="form-label small mb-0">Taux principal (%)</label><input type="number" min="0" step="any" class="form-control form-control-sm" id="sc${i}_pt" value="${d.pt}"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="30" class="form-control form-control-sm" id="sc${i}_pd" value="${d.pd}"></div></div>
    <div class="mt-2 small">${[['ptz','PTZ'],['dbl','Doublissimo'],['pj','Primo Jeune 0 %'],['pz','Primoz'],['gr','Grandioz']].map(x=>`<div class="form-check"><input class="form-check-input" type="checkbox" id="sc${i}_${x[0]}" ${d[x[0]]?'checked':''}><label class="form-check-label" for="sc${i}_${x[0]}">${x[1]}</label></div>`).join('')}</div>
    <div class="row g-1 mt-1"><div class="col-6"><input type="number" step="any" min="0" class="form-control form-control-sm" id="sc${i}_pzt" placeholder="Taux Primoz %" value="3.2"></div><div class="col-6"><input type="number" step="any" min="0" class="form-control form-control-sm" id="sc${i}_grt" placeholder="Taux Grandioz %" value="3.7"></div>
    <div class="col-12"><input type="number" step="any" min="0" class="form-control form-control-sm" id="sc${i}_grm" placeholder="Montant Grandioz €" value="60000"></div></div>
  </div></div>`).join('');
  const chk=id=>$(id).checked;
  function scen(i){
    const f=planForm.read(); const ap=$('sc'+i+'_apport').value; if(ap!=='') f.apport=N(ap);
    f.principal={taux:N($('sc'+i+'_pt').value),duree:Math.round(N($('sc'+i+'_pd').value)*12)};
    f.ptz={on:chk('sc'+i+'_ptz')};f.dbl={on:chk('sc'+i+'_dbl')};f.pj={on:chk('sc'+i+'_pj')};
    f.pz={on:chk('sc'+i+'_pz'),taux:$('sc'+i+'_pzt').value};f.gr={on:chk('sc'+i+'_gr'),montant:$('sc'+i+'_grm').value,taux:$('sc'+i+'_grt').value};
    return planAdapter.run(f);
  }
  function run(){
    const R=[0,1,2].map(scen), names=[0,1,2].map(i=>$('sc'+i+'_nom').value||('Montage '+(i+1)));
    const line=(c,flag)=>c.lignes.find(l=>l[flag]);
    const metric=(label,fn,fmt,best)=>({label,vals:R.map(fn),fmt,best});
    const M=[
      metric('Apport',r=>N(r.d.apport),eur),
      metric('Prêt principal',r=>N(r.c.lignes[0].montant),eur),
      metric('PTZ',r=>r.d.ptz_actif==1?N(r.d.ptz_montant):0,eur),
      metric('Doublissimo',r=>{const l=line(r.c,'doublissimo');return l?N(l.montant):0;},eur),
      metric('Primo Jeune 0 %',r=>{const l=line(r.c,'primo_jeune');return l?N(l.montant):0;},eur),
      metric('Primoz',r=>{const l=line(r.c,'primoz');return l?N(l.montant):0;},eur),
      metric('Grandioz',r=>{const l=line(r.c,'grandioz');return l?N(l.montant):0;},eur),
      metric('Durée du prêt principal',r=>N(r.c.lignes[0].duree)/12,v=>v+' ans'),
      metric('Mensualité tout inclus',r=>r.c.mensTout,eur2,'min'),
      metric('Taux d\'endettement',r=>r.c.te,v=>v===null?'–':v.toFixed(2).replace('.',',')+' %','min'),
      metric('Reste à vivre',r=>r.c.reste,eur,'max'),
      metric('TAEG global',r=>r.c.taegGlobal?r.c.taegGlobal.taeg:null,v=>v===null?'–':v.toFixed(2).replace('.',',')+' %','min'),
      metric('Coût total du crédit',r=>r.c.coutCredit,eur,'min'),
    ];
    $('sc_head').innerHTML='<tr><th></th>'+names.map(n=>`<th class="text-end">${n}</th>`).join('')+'</tr>';
    $('sc_body').innerHTML=M.map(m=>{
      const nums=m.vals.map(v=>v===null||v===undefined?NaN:v).map(Number); let bi=-1;
      if(m.best&&nums.some(x=>!isNaN(x))){const ok=nums.map((x,i)=>[x,i]).filter(x=>!isNaN(x[0])); const t=m.best==='min'?Math.min(...ok.map(x=>x[0])):Math.max(...ok.map(x=>x[0])); if(ok.some(x=>x[0]!==t)) bi=ok.find(x=>x[0]===t)[1];}
      const hide=m.vals.every(v=>!N(v)&&m.label.match(/Primo|Primoz|Grandioz|PTZ|Doublissimo/));
      return hide?'':`<tr><td>${m.label}</td>${m.vals.map((v,i)=>`<td class="text-end ${i===bi?'text-success fw-bold':''}">${m.fmt(v)}</td>`).join('')}</tr>`;
    }).join('');
    $('sc_alerts').innerHTML=R.map((r,i)=>{const t=r.controles.filter(x=>x.niv==='erreur'||/endettement|Reste à vivre|éligible|Primo|Doublissimo|Primoz|Grandioz|PTZ/.test(x.txt));return t.length?`<div class="alert alert-warning py-2 small mb-2"><strong>${names[i]}</strong><ul class="mb-0 ps-3">${t.map(x=>`<li>${x.txt}</li>`).join('')}</ul></div>`:'';}).join('');
    return {R,names};
  }
  planForm.onChange(run); document.querySelectorAll('#sc_cols input').forEach(e=>{e.addEventListener('input',run);e.addEventListener('change',run);});
  planForm.initGeo(<?= json_encode($planZonageUrl) ?>);
  window.toolsShare={get:()=>({geo:planForm.geoGet()}),set:o=>{if(o&&o.geo) planForm.geoSet(o.geo);}};
  window.scPrepare=function(f0){const x=run();const sc=[0,1,2].map(i=>{const o={};FLD.forEach(k=>{const el=$('sc'+i+'_'+k);if(el)o[k]=el.type==='checkbox'?el.checked:el.value;});return o;});
    f0.params.value=JSON.stringify({form:planForm.snapshot(),sc,geo:planForm.geoGet()});
    f0.resultat.value=JSON.stringify({noms:x.names,mensualites:x.R.map(r=>Math.round(r.c.mensTout*100)/100),couts:x.R.map(r=>Math.round(r.c.coutCredit))});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){planForm.apply(load.form);(load.sc||[]).forEach((o,i)=>FLD.forEach(k=>{const el=$('sc'+i+'_'+k);if(!el||o[k]===undefined)return;if(el.type==='checkbox')el.checked=!!o[k];else el.value=o[k];}));if(load.geo)planForm.geoSet(load.geo);}
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
