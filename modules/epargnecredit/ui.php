<?php
// Comparateur « utiliser son épargne ou emprunter », partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
$fis = baremeGet('fiscalite')['data'];
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['fiscalite']) ?>
<div class="row g-3">
 <div class="col-lg-5">
  <div class="cap-card"><h2><i class="fas fa-bullseye me-2 text-danger"></i>Le projet et l'épargne</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Montant à financer (€)</label><input type="number" min="0" step="any" class="form-control" id="ec_x" value="40000"></div>
      <div class="col-6"><label class="form-label small mb-0">Épargne disponible (€)</label><input type="number" min="0" step="any" class="form-control" id="ec_e" value="40000"></div>
      <div class="col-6"><label class="form-label small mb-0">Épargne de précaution à conserver (€)</label><input type="number" min="0" step="any" class="form-control" id="ec_p" value="10000"></div>
      <div class="col-6"><label class="form-label small mb-0">Apport du scénario « mixte » (€)</label><input type="number" min="0" step="any" class="form-control" id="ec_a" value="15000"></div>
      <div class="col-6"><label class="form-label small mb-0">Rendement brut de l'épargne (% par an)</label><input type="number" min="0" step="any" class="form-control" id="ec_r" value="3"></div>
      <div class="col-6"><label class="form-label small mb-0">Fiscalité des gains</label><select class="form-select" id="ec_reg"><option value="none">Aucune (livrets réglementés)</option><option value="pfu">Prélèvement forfaitaire</option><option value="av8">Assurance-vie de plus de 8 ans</option><option value="av">Assurance-vie de moins de 8 ans</option></select></div>
    </div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-file-contract me-2 text-danger"></i>Le crédit</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Taux nominal (% par an)</label><input type="number" min="0" step="any" class="form-control" id="ec_t" value="4"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="30" step="1" class="form-control" id="ec_n" value="5"></div>
      <div class="col-6"><label class="form-label small mb-0">Assurance (% du capital initial par an)</label><input type="number" min="0" step="any" class="form-control" id="ec_i" value="0.3"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais de dossier (€)</label><input type="number" min="0" step="any" class="form-control" id="ec_fd" value="300"></div>
      <div class="col-6"><label class="form-label small mb-0">Garantie (% du capital)</label><input type="number" min="0" step="any" class="form-control" id="ec_g" value="1"></div>
    </div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'ecPrepare'); ?>
 </div>
 <div class="col-lg-7">
  <div class="cap-res js-result"><div class="lbl" id="ecr_title">Résultat</div><div class="big" id="ecr_big">–</div><div class="lbl mt-1" id="ecr_note"></div></div>
  <div class="cap-card js-result"><h2><i class="fas fa-scale-balanced me-2 text-danger"></i>Comparaison des trois scénarios</h2>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th></th><th class="text-end">Tout épargne</th><th class="text-end">Mixte</th><th class="text-end">Tout crédit</th></tr></thead><tbody id="ecr_tab"></tbody></table></div>
    <div class="form-text mt-2">Le <strong>coût actualisé</strong> ramène tous les paiements (apport, frais, mensualités) à aujourd'hui, au rendement net de votre épargne : plus il est bas, plus le scénario est avantageux. Le scénario « tout crédit » conserve l'épargne disponible ; « tout épargne » l'utilise dans la limite de l'épargne disponible hors précaution.</div></div>
  <p class="small text-muted">Comparaison indicative, hors fiscalité spécifique du projet. Elle suppose que les mensualités sont supportables par le budget : vérifiez la capacité de remboursement et conservez une épargne de précaution.</p>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const F=<?= json_encode($fis) ?>;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', pct=v=>(Math.round(v*100)/100).toLocaleString('fr-FR')+' %';
  const TX={none:0,pfu:F.pfu_ir+F.ps_standard,av8:F.av_taux_8ans+F.ps_assurance_vie,av:F.pfu_ir+F.ps_assurance_vie};
  function annuity(L,t,N){const i=t/1200;return i>0?L*i/(1-Math.pow(1+i,-N)):L/N;}
  function scen(a,p){
    const L=Math.max(0,p.X-a), N=p.N, mens=L>0?annuity(L,p.t,N):0, ins=L*p.ins/1200, frais=L>0?p.fd+L*p.g/100:0;
    const di=Math.pow(1+p.rn/100,1/12)-1; let npv=a+frais; for(let k=1;k<=N;k++) npv+=(mens+ins)/Math.pow(1+di,k);
    const nominal=a+frais+(mens+ins)*N;
    return {a,L,mens:mens+ins,frais,interets:mens*N-L,npv,nominal,kept:p.E-a};
  }
  function effRate(L,mens,frais,N){ // taux effectif annuel du crédit (assurance et frais compris)
    if(L<=0) return null; let lo=0,hi=0.05;
    const pv=r=>{let s=0;for(let k=1;k<=N;k++) s+=mens/Math.pow(1+r,k);return s;};
    if(pv(0)<L-frais) return null;
    for(let it=0;it<200;it++){const m=(lo+hi)/2;(pv(m)>L-frais)?lo=m:hi=m;}
    return (Math.pow(1+(lo+hi)/2,12)-1)*100;
  }
  function run(){
    const rn=n('ec_r')*(1-TX[$('ec_reg').value]/100);
    const p={X:n('ec_x'),E:n('ec_e'),t:n('ec_t'),N:Math.max(1,Math.round(n('ec_n')))*12,ins:n('ec_i'),fd:n('ec_fd'),g:n('ec_g'),rn};
    const util=Math.max(0,Math.min(p.X,p.E-n('ec_p')));
    const A=scen(util,p), C=scen(Math.min(p.X,Math.max(0,n('ec_a'))),p), B=scen(0,p);
    const years=p.N/12, fut=Math.pow(1+rn/100,years);
    const diff=(A.npv-B.npv)*fut; // + : emprunter fait gagner
    const best=[['Tout épargne',A],['Mixte',C],['Tout crédit',B]].sort((x,y)=>x[1].npv-y[1].npv)[0];
    $('ecr_title').textContent='Scénario le plus avantageux (à rendement net de '+pct(rn)+' par an)';
    $('ecr_big').textContent=best[0];
    const g=(A.npv-best[1].npv)*fut;
    $('ecr_note').textContent=best[1]===A?'Utiliser l\'épargne coûte moins cher que d\'emprunter avec ces paramètres.':'Par rapport à « tout épargne », gain estimé de '+eur(g)+' à l\'échéance ('+years+' an'+(years>1?'s':'')+').';
    const r=(f)=>[A,C,B].map(s=>'<td class="text-end">'+f(s)+'</td>').join('');
    $('ecr_tab').innerHTML=[
      ['Apport tiré de l\'épargne',r(s=>eur(s.a))],['Crédit contracté',r(s=>eur(s.L))],['Mensualité (assurance comprise)',r(s=>s.L>0?eur(s.mens):'–')],
      ['Intérêts du crédit',r(s=>s.L>0?eur(s.interets):'–')],['Frais de dossier et de garantie',r(s=>s.L>0?eur(s.frais):'–')],['Épargne conservée',r(s=>eur(s.kept))],
      ['Coût total payé',r(s=>eur(s.nominal))],['<strong>Coût actualisé</strong>',r(s=>'<strong>'+eur(s.npv)+'</strong>')],['Écart avec « tout épargne » à l\'échéance',r(s=>(s===A?'–':(s.npv<A.npv?'gain de ':'perte de ')+eur(Math.abs(A.npv-s.npv)*fut)))]
    ].map(x=>`<tr><td>${x[0]}</td>${x[1]}</tr>`).join('');
    const eff=effRate(B.L,B.mens,B.frais,p.N);
    if(eff!==null) $('ecr_note').textContent+=' Seuil d\'indifférence : emprunter est avantageux si votre épargne rapporte plus de '+pct(eff)+' net par an (taux effectif du crédit, assurance et frais compris).';
    return {p,A,B,C,best:best[0],diff,eff};
  }
  const IDS=['x','e','p','a','r','reg','t','n','i','fd','g'];
  IDS.forEach(k=>{$('ec_'+k).addEventListener('input',run);$('ec_'+k).addEventListener('change',run);});
  window.ecPrepare=function(f){const x=run();const q={};IDS.forEach(k=>q[k]=$('ec_'+k).value);f.params.value=JSON.stringify(q);f.resultat.value=JSON.stringify({meilleur:x.best,ecart:Math.round(x.diff)});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{if(load[k]!==undefined) $('ec_'+k).value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
