<?php
// Simulateur d'épargne (capital final, objectif à atteindre), partagé par le portail (avec enregistrement) et /tools (sans).
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
  <div class="cap-card"><h2><i class="fas fa-piggy-bank me-2 text-danger"></i>Votre épargne</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Capital de départ (€)</label><input type="number" min="0" step="any" class="form-control" id="ep_c0" value="5000"></div>
      <div class="col-6"><label class="form-label small mb-0">Versement mensuel (€)</label><input type="number" min="0" step="any" class="form-control" id="ep_v" value="200"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="60" step="1" class="form-control" id="ep_n" value="10"></div>
      <div class="col-6"><label class="form-label small mb-0">Rendement annuel brut (%)</label><input type="number" min="0" step="any" class="form-control" id="ep_r" value="3"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais annuels (%)</label><input type="number" min="0" step="any" class="form-control" id="ep_f" value="0.6"></div>
      <div class="col-6"><label class="form-label small mb-0">Inflation annuelle (%)</label><input type="number" min="0" step="any" class="form-control" id="ep_i" value="2"></div>
      <div class="col-12"><label class="form-label small mb-0">Fiscalité des gains</label>
        <select class="form-select" id="ep_reg">
          <option value="none">Aucune (livrets réglementés)</option>
          <option value="pfu">Prélèvement forfaitaire (compte-titres, PEA, compte à terme)</option>
          <option value="av8">Assurance-vie de plus de 8 ans (avant abattement)</option>
          <option value="av">Assurance-vie de moins de 8 ans</option>
        </select></div>
    </div>
    <div class="form-text">Les gains sont supposés capitalisés et imposés à la sortie. Estimation : l'abattement annuel de l'assurance-vie n'est pas pris en compte (voir le simulateur d'assurance-vie).</div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-bullseye me-2 text-danger"></i>Objectif (facultatif)</h2>
    <label class="form-label small mb-0">Capital net souhaité (€)</label><input type="number" min="0" step="any" class="form-control" id="ep_obj" value="40000">
    <div class="form-text">Net d'impôt, au terme de la durée indiquée.</div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'epPrepare'); ?>
 </div>
 <div class="col-lg-7">
  <div class="cap-res js-result">
    <div class="lbl">Capital net estimé à l'issue de la durée</div><div class="big" id="er_net">–</div>
    <div class="lbl mt-1" id="er_real"></div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="er_det"></tbody></table>
  </div>
  <div class="cap-card js-result" id="er_obj_card"><h2><i class="fas fa-flag-checkered me-2 text-danger"></i>Pour atteindre l'objectif</h2><div id="er_obj"></div></div>
  <div class="cap-card"><h2>Évolution année par année</h2>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Année</th><th class="text-end">Versé</th><th class="text-end">Capital brut</th><th style="width:34%"></th></tr></thead><tbody id="er_tab"></tbody></table></div></div>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const F=<?= json_encode($fis) ?>;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €';
  const TX={none:0,pfu:F.pfu_ir+F.ps_standard,av8:F.av_taux_8ans+F.ps_assurance_vie,av:F.pfu_ir+F.ps_assurance_vie};
  function sim(c0,v,years,rn,reg,months){
    const i=Math.pow(1+rn/100,1/12)-1; let bal=c0; const M=months??years*12, ys=[];
    for(let m=1;m<=M;m++){bal=bal*(1+i)+v; if(m%12===0) ys.push({y:m/12,paid:c0+v*m,bal});}
    const paid=c0+v*M, gain=Math.max(0,bal-paid), tax=gain*TX[reg]/100;
    return {bal,paid,gain,tax,net:bal-tax,ys};
  }
  function run(){
    const c0=n('ep_c0'), v=n('ep_v'), y=Math.max(1,n('ep_n')), rn=n('ep_r')-n('ep_f'), reg=$('ep_reg').value, infl=n('ep_i'), obj=n('ep_obj');
    const s=sim(c0,v,y,rn,reg);
    $('er_net').textContent=eur(s.net);
    $('er_real').textContent='Soit '+eur(s.net/Math.pow(1+infl/100,y))+' en pouvoir d\'achat d\'aujourd\'hui (inflation de '+infl.toString().replace('.',',')+' % par an)';
    $('er_det').innerHTML=[['Total versé',s.paid],['Gains bruts',s.gain],['Impôts et prélèvements estimés ('+TX[reg].toFixed(1).replace('.',',')+' % des gains)',s.tax],['Capital brut avant impôts',s.bal]].map(x=>`<tr><td>${x[0]}</td><td class="text-end">${eur(x[1])}</td></tr>`).join('');
    const max=Math.max(...s.ys.map(r=>r.bal),1);
    $('er_tab').innerHTML=s.ys.map(r=>`<tr><td>${r.y}</td><td class="text-end">${eur(r.paid)}</td><td class="text-end">${eur(r.bal)}</td><td><div style="height:10px;border-radius:5px;background:linear-gradient(90deg,#e4002b ${r.paid/max*100}%,#ff8a9d ${r.paid/max*100}%);width:${r.bal/max*100}%"></div></td></tr>`).join('');
    let ob='';
    if(obj>0){
      if(s.net>=obj) ob+=`<p class="mb-2"><i class="fas fa-circle-check text-success me-1"></i>Avec ces paramètres, l'objectif de ${eur(obj)} est atteint (${eur(s.net)}).</p>`;
      else ob+=`<p class="mb-2"><i class="fas fa-triangle-exclamation text-warning me-1"></i>Avec ces paramètres, il manque ${eur(obj-s.net)} pour atteindre ${eur(obj)}.</p>`;
      let lo=0,hi=1e6; for(let k=0;k<60;k++){const mid=(lo+hi)/2;(sim(c0,mid,y,rn,reg).net<obj)?lo=mid:hi=mid;}
      const need=hi; ob+=`<p class="mb-1"><strong>Versement mensuel nécessaire :</strong> ${need<0.5&&sim(c0,0,y,rn,reg).net>=obj?'aucun (le capital de départ suffit)':eur(need)+' par mois'} pendant ${y} an${y>1?'s':''}.</p>`;
      let m=1,found=false; for(;m<=80*12;m++){if(sim(c0,v,0,rn,reg,m).net>=obj){found=true;break;}}
      ob+=`<p class="mb-0"><strong>Durée nécessaire avec ${eur(v)} par mois :</strong> ${found?Math.floor(m/12)+' an'+(Math.floor(m/12)>1?'s':'')+(m%12?' et '+(m%12)+' mois':''):'plus de 80 ans'}.</p>`;
    } else ob='<p class="text-muted mb-0">Renseignez un objectif pour calculer le versement ou la durée nécessaires.</p>';
    $('er_obj').innerHTML=ob;
    return {s,params:{c0,v,y,r:n('ep_r'),f:n('ep_f'),i:infl,reg,obj}};
  }
  ['c0','v','n','r','f','i','reg','obj'].forEach(k=>{$('ep_'+k).addEventListener('input',run);$('ep_'+k).addEventListener('change',run);});
  window.epPrepare=function(f){const x=run();f.params.value=JSON.stringify({c0:n('ep_c0'),v:n('ep_v'),n:n('ep_n'),r:n('ep_r'),f:n('ep_f'),i:n('ep_i'),reg:$('ep_reg').value,obj:n('ep_obj')});f.resultat.value=JSON.stringify({net:Math.round(x.s.net),paid:Math.round(x.s.paid),ans:x.params.y});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) Object.keys(load).forEach(k=>{const el=$('ep_'+k);if(el) el.value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
