<?php
// Simulateur d'économie d'impôt d'un versement sur un PER, partagé par le portail (avec enregistrement) et /tools (sans).
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
  <div class="cap-card"><h2><i class="fas fa-users me-2 text-danger"></i>Le foyer fiscal</h2>
    <div class="row g-2">
      <div class="col-12"><label class="form-label small mb-0">Revenu net imposable du foyer (€ par an)</label><input type="number" min="0" step="any" class="form-control" id="pr_rev" value="45000"></div>
      <div class="col-6"><label class="form-label small mb-0">Nombre de parts</label><select class="form-select" id="pr_parts"><?php foreach ([1,1.5,2,2.5,3,3.5,4,4.5,5] as $p): ?><option value="<?= $p ?>" <?= $p == 2 ? 'selected' : '' ?>><?= str_replace('.', ',', $p) ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-label small mb-0">Revenus professionnels de l'an dernier (€)</label><input type="number" min="0" step="any" class="form-control" id="pr_pro" value="50000"></div>
    </div>
    <div class="form-text">Revenu net imposable : après abattement de 10 % ou frais réels, avant le versement sur le PER.</div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-piggy-bank me-2 text-danger"></i>Le versement</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Versement envisagé (€)</label><input type="number" min="0" step="any" class="form-control" id="pr_v" value="3000"></div>
      <div class="col-6"><label class="form-label small mb-0">Plafonds non utilisés des 3 années précédentes (€)</label><input type="number" min="0" step="any" class="form-control" id="pr_rep" value="0"></div>
    </div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'prPrepare'); ?>
 </div>
 <div class="col-lg-7">
  <div class="cap-res js-result">
    <div class="lbl">Économie d'impôt estimée</div><div class="big" id="prr_eco">–</div>
    <div class="lbl mt-1" id="prr_note"></div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="prr_det"></tbody></table>
  </div>
  <div class="cap-card"><h2>Comparaison selon le versement</h2>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Versement</th><th class="text-end">Économie d'impôt</th><th class="text-end">Coût réel</th></tr></thead><tbody id="prr_tab"></tbody></table></div>
    <div class="form-text mt-2">Estimation sans décote ni plafonnement de l'avantage lié au quotient familial. À la sortie, les sommes déduites sont imposées (capital au barème pour une sortie en capital, rente ajustée) : l'avantage est surtout un report d'imposition lorsque le taux est plus bas à la retraite.</div></div>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const F=<?= json_encode($fis) ?>;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €';
  function impot(rev,parts){
    const q=Math.max(0,rev)/parts; let prev=0,t=0;
    F.ir_seuils.forEach((s,i)=>{t+=Math.max(0,Math.min(q,s)-prev)*F.ir_taux[i]/100;prev=s;});
    t+=Math.max(0,q-prev)*F.ir_taux[4]/100; return t*parts;
  }
  function tmi(rev,parts){const q=Math.max(0,rev)/parts;let i=0;while(i<F.ir_seuils.length&&q>F.ir_seuils[i]) i++;return F.ir_taux[i];}
  function plafond(){
    const pro=n('pr_pro'), base=pro*F.per_pct/100, plancher=F.pass_n1*F.per_pct/100, max=F.pass_n1*F.per_plafond_pass*F.per_pct/100;
    return Math.min(max,Math.max(plancher,base))+n('pr_rep');
  }
  function eco(v){const ded=Math.min(v,plafond()),rev=n('pr_rev'),parts=parseFloat($('pr_parts').value)||1;return {ded,eco:impot(rev,parts)-impot(rev-ded,parts)};}
  function run(){
    const v=n('pr_v'), rev=n('pr_rev'), parts=parseFloat($('pr_parts').value)||1, pl=plafond(), r=eco(v);
    $('prr_eco').textContent=eur(r.eco);
    $('prr_note').textContent=v>pl?'Seule une partie du versement est déductible (plafond de '+eur(pl)+').':'';
    $('prr_det').innerHTML=[['Impôt estimé sans versement',eur(impot(rev,parts))],['Impôt estimé avec versement',eur(impot(rev,parts)-r.eco)],['Versement déductible',eur(r.ded)+' sur un plafond de '+eur(pl)],['Taux marginal d\'imposition',tmi(rev,parts)+' % → '+tmi(rev-r.ded,parts)+' %'],['Coût réel du versement',eur(v-r.eco)]].map(x=>`<tr><td>${x[0]}</td><td class="text-end">${x[1]}</td></tr>`).join('');
    const vs=[500,1000,2000,5000,10000,Math.round(pl)].filter((x,i,a)=>x>0&&a.indexOf(x)===i).sort((a,b)=>a-b);
    $('prr_tab').innerHTML=vs.map(x=>{const e=eco(x);return `<tr><td>${eur(x)}${x===Math.round(pl)?' <span class="badge text-bg-light">plafond</span>':''}</td><td class="text-end">${eur(e.eco)}</td><td class="text-end">${eur(x-e.eco)}</td></tr>`;}).join('');
    return {v,eco:r.eco,pl};
  }
  const IDS=['rev','parts','pro','v','rep'];
  IDS.forEach(k=>{$('pr_'+k).addEventListener('input',run);$('pr_'+k).addEventListener('change',run);});
  window.prPrepare=function(f){const x=run();const p={};IDS.forEach(k=>p[k]=$('pr_'+k).value);f.params.value=JSON.stringify(p);f.resultat.value=JSON.stringify({versement:x.v,economie:Math.round(x.eco)});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{if(load[k]!==undefined) $('pr_'+k).value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
