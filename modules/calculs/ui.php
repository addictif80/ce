<?php
// Boîte à calculs rapides (pourcentages, TVA, règle de trois, durées, prorata, intérêts simples, taux équivalents, moyenne),
// partagée par le portail et /tools. Aucun enregistrement : ce sont des calculs instantanés.
require __DIR__ . '/../_sim/style.php';
?>
<?php $actionsOpts = ['copy' => false, 'print' => false, 'mail' => false]; // résultats courts sans libellé : seul le lien de partage a du sens
require __DIR__ . '/../_sim/actions.php'; ?>
<style>.bc-out{background:#f1f3f5;border-radius:8px;padding:8px 12px;margin-top:8px;font-weight:600;min-height:38px}.bc-out small{font-weight:400;color:#6c757d}</style>
<div class="row g-3">
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-percent me-2 text-danger"></i>Pourcentages</h2>
    <div class="row g-2 align-items-end mb-2"><div class="col-4"><label class="form-label small mb-0">Pourcentage</label><input type="number" step="any" class="form-control" id="bc_p1" value="15"></div><div class="col-4"><label class="form-label small mb-0">de</label><input type="number" step="any" class="form-control" id="bc_v1" value="200"></div><div class="col-4"><div class="bc-out " id="bo_p1">–</div></div></div>
    <div class="row g-2 align-items-end mb-2"><div class="col-4"><label class="form-label small mb-0">Valeur</label><input type="number" step="any" class="form-control" id="bc_a2" value="30"></div><div class="col-4"><label class="form-label small mb-0">sur</label><input type="number" step="any" class="form-control" id="bc_b2" value="200"></div><div class="col-4"><div class="bc-out " id="bo_p2">–</div></div></div>
    <div class="row g-2 align-items-end mb-2"><div class="col-4"><label class="form-label small mb-0">Valeur de départ</label><input type="number" step="any" class="form-control" id="bc_a3" value="200"></div><div class="col-4"><label class="form-label small mb-0">Valeur d'arrivée</label><input type="number" step="any" class="form-control" id="bc_b3" value="230"></div><div class="col-4"><div class="bc-out " id="bo_p3">–</div></div></div>
    <div class="row g-2 align-items-end"><div class="col-4"><label class="form-label small mb-0">Valeur</label><input type="number" step="any" class="form-control" id="bc_v4" value="200"></div><div class="col-4"><label class="form-label small mb-0">± pourcentage</label><input type="number" step="any" class="form-control" id="bc_p4" value="10"></div><div class="col-4"><div class="bc-out " id="bo_p4">–</div></div></div>
    <div class="form-text">Lignes : x % de y · valeur sur total (en %) · variation entre deux valeurs · valeur après une hausse (+) ou une baisse (−).</div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-receipt me-2 text-danger"></i>TVA</h2>
    <div class="row g-2 align-items-end"><div class="col-4"><label class="form-label small mb-0">Montant</label><input type="number" step="any" class="form-control" id="bc_tm" value="100"></div>
      <div class="col-4"><label class="form-label small mb-0">Taux</label><select class="form-select" id="bc_tt"><option>20</option><option>10</option><option>5.5</option><option>2.1</option></select></div>
      <div class="col-4"><label class="form-label small mb-0">Le montant est</label><select class="form-select" id="bc_tk"><option value="ht">HT</option><option value="ttc">TTC</option></select></div></div>
    <div class="bc-out " id="bo_tva">–</div></div>
  <div class="cap-card"><h2><i class="fas fa-divide me-2 text-danger"></i>Règle de trois</h2>
    <div class="row g-2 align-items-end"><div class="col-3"><label class="form-label small mb-0">Si A</label><input type="number" step="any" class="form-control" id="bc_r1" value="3"></div><div class="col-3"><label class="form-label small mb-0">donne B</label><input type="number" step="any" class="form-control" id="bc_r2" value="450"></div><div class="col-3"><label class="form-label small mb-0">alors C</label><input type="number" step="any" class="form-control" id="bc_r3" value="5"></div><div class="col-3"><div class="bc-out " id="bo_r">–</div></div></div>
    <div class="form-text">Résultat : C × B ÷ A.</div></div>
  <div class="cap-card"><h2><i class="fas fa-calculator me-2 text-danger"></i>Somme et moyenne</h2>
    <label class="form-label small mb-0">Valeurs (une par ligne ou séparées par des espaces)</label><textarea class="form-control" rows="3" id="bc_list">1200 1350 980 1500</textarea>
    <div class="bc-out " id="bo_list">–</div></div>
 </div>
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-hourglass-half me-2 text-danger"></i>Conversion de durées</h2>
    <div class="row g-2 align-items-end"><div class="col-5"><label class="form-label small mb-0">Durée</label><input type="number" step="any" class="form-control" id="bc_dn" value="20"></div><div class="col-7"><label class="form-label small mb-0">Unité</label><select class="form-select" id="bc_du"><option value="a">Années</option><option value="m">Mois</option><option value="s">Semaines</option><option value="j">Jours</option></select></div></div>
    <div class="bc-out " id="bo_dur">–</div></div>
  <div class="cap-card"><h2><i class="fas fa-scissors me-2 text-danger"></i>Prorata temporis</h2>
    <div class="row g-2 align-items-end"><div class="col-4"><label class="form-label small mb-0">Montant (période entière)</label><input type="number" step="any" class="form-control" id="bc_pm" value="1200"></div><div class="col-4"><label class="form-label small mb-0">Jours concernés</label><input type="number" step="any" class="form-control" id="bc_pj" value="17"></div><div class="col-4"><label class="form-label small mb-0">Jours de la période</label><input type="number" step="any" class="form-control" id="bc_pt" value="30"></div></div>
    <div class="bc-out " id="bo_pro">–</div></div>
  <div class="cap-card"><h2><i class="fas fa-coins me-2 text-danger"></i>Intérêts simples</h2>
    <div class="row g-2 align-items-end"><div class="col-3"><label class="form-label small mb-0">Capital (€)</label><input type="number" step="any" class="form-control" id="bc_ic" value="10000"></div><div class="col-3"><label class="form-label small mb-0">Taux annuel (%)</label><input type="number" step="any" class="form-control" id="bc_it" value="3"></div><div class="col-3"><label class="form-label small mb-0">Jours</label><input type="number" step="any" class="form-control" id="bc_ij" value="90"></div><div class="col-3"><label class="form-label small mb-0">Base</label><select class="form-select" id="bc_ib"><option value="360">360 jours</option><option value="365" selected>365 jours</option></select></div></div>
    <div class="bc-out " id="bo_int">–</div></div>
  <div class="cap-card"><h2><i class="fas fa-arrows-left-right me-2 text-danger"></i>Taux équivalents</h2>
    <div class="row g-2 align-items-end"><div class="col-6"><label class="form-label small mb-0">Taux (%)</label><input type="number" step="any" class="form-control" id="bc_xr" value="3.5"></div><div class="col-6"><label class="form-label small mb-0">Ce taux est</label><select class="form-select" id="bc_xk"><option value="nom">Nominal annuel (proportionnel, périodes mensuelles)</option><option value="act">Actuariel annuel</option><option value="men">Mensuel</option></select></div></div>
    <div class="bc-out " id="bo_taux">–</div></div>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value), ok=v=>Number.isFinite(v);
  const f=(v,d=2)=>ok(v)?(Math.round(v*Math.pow(10,d))/Math.pow(10,d)).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:d}):'–';
  const out=(id,t)=>$(id).innerHTML=t;
  function run(){
    out('bo_p1',f(n('bc_p1')/100*n('bc_v1'),4)+'<br><small>'+f(n('bc_p1'))+' % de '+f(n('bc_v1'))+'</small>');
    out('bo_p2',ok(n('bc_a2')/n('bc_b2'))?f(n('bc_a2')/n('bc_b2')*100,4)+' %':'–');
    const v3=(n('bc_b3')-n('bc_a3'))/n('bc_a3')*100; out('bo_p3',ok(v3)&&n('bc_a3')!==0?(v3>=0?'+':'')+f(v3,4)+' %':'–');
    out('bo_p4',ok(n('bc_v4')*(1+n('bc_p4')/100))?f(n('bc_v4')*(1+n('bc_p4')/100),4):'–');
    const m=n('bc_tm'), t=parseFloat($('bc_tt').value)/100, ht=$('bc_tk').value==='ht'?m:m/(1+t), ttc=ht*(1+t);
    out('bo_tva',ok(m)?'HT : '+f(ht)+' € · TVA : '+f(ttc-ht)+' € · TTC : '+f(ttc)+' €':'–');
    out('bo_r',ok(n('bc_r3')*n('bc_r2')/n('bc_r1'))&&n('bc_r1')!==0?f(n('bc_r3')*n('bc_r2')/n('bc_r1'),4):'–');
    const L=($('bc_list').value.replace(/,/g,'.').match(/-?\d+(\.\d+)?/g)||[]).map(Number);
    out('bo_list',L.length?'Somme : '+f(L.reduce((a,b)=>a+b,0))+' · Moyenne : '+f(L.reduce((a,b)=>a+b,0)/L.length)+' · Min : '+f(Math.min(...L))+' · Max : '+f(Math.max(...L))+'<br><small>'+L.length+' valeur'+(L.length>1?'s':'')+'</small>':'–');
    const d=n('bc_dn'), u=$('bc_du').value, jours=d*({a:365.25,m:365.25/12,s:7,j:1})[u];
    out('bo_dur',ok(jours)?f(jours/365.25,3)+' an(s) · '+f(jours/(365.25/12),2)+' mois · '+f(jours/7,2)+' semaines · '+f(jours,1)+' jours':'–');
    out('bo_pro',ok(n('bc_pm')*n('bc_pj')/n('bc_pt'))&&n('bc_pt')!==0?f(n('bc_pm')*n('bc_pj')/n('bc_pt'))+' €':'–');
    const i=n('bc_ic')*n('bc_it')/100*n('bc_ij')/parseInt($('bc_ib').value);
    out('bo_int',ok(i)?'Intérêts : '+f(i)+' € · Capital + intérêts : '+f(n('bc_ic')+i)+' €':'–');
    const r=n('bc_xr')/100, k=$('bc_xk').value; let mens=k==='men'?r:k==='nom'?r/12:Math.pow(1+r,1/12)-1;
    out('bo_taux',ok(mens)?'Mensuel : '+f(mens*100,4)+' % · Nominal annuel : '+f(mens*1200,4)+' % · Actuariel annuel : '+f((Math.pow(1+mens,12)-1)*100,4)+' %':'–');
  }
  document.querySelectorAll('input[id^=bc_],select[id^=bc_],textarea[id^=bc_]').forEach(e=>{e.addEventListener('input',run);e.addEventListener('change',run);});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
