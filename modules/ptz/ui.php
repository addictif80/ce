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
<?php require_once __DIR__ . '/../../includes/baremes.php'; echo baremeNotice(['ptz']); ?>
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
      <div class="col-12" id="pz_geo" style="display:none"><div class="row g-2">
        <div class="col-6"><label class="form-label small mb-0">Département du logement</label>
          <select class="form-select" id="pz_dep" data-noshare><option value="">Choisir…</option></select></div>
        <div class="col-6"><label class="form-label small mb-0">Commune</label>
          <input type="text" class="form-control" id="pz_commune" data-noshare list="pz_communes" autocomplete="off" placeholder="Tapez le nom de la commune" disabled>
          <datalist id="pz_communes"></datalist></div>
      </div></div>
      <div class="col-6"><label class="form-label small mb-0">Zone du logement <span class="text-muted" id="pz_zinfo"></span></label>
        <select class="form-select" id="pz_zone"><option value="A">A bis / A</option><option value="B1">B1</option><option value="B2">B2</option><option value="C">C</option></select></div>
      <div class="col-6"><label class="form-label small mb-0">Type de bien</label>
        <select class="form-select" id="pz_type"><?php foreach ($ptzBareme['types'] as $k => $t): ?><option value="<?= e($k) ?>"><?= e($t['label']) ?></option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-label small mb-0">Coût de l'opération (€)</label><input type="number" min="0" step="any" class="form-control" id="pz_cout" value="220000"></div>
    </div>
    <div class="form-text">La zone dépend de la commune du bien (A bis / A, B1, B2, C) : si vous ne la connaissez pas, utilisez le simulateur de l'ANIL indiqué sous les résultats. Ancien avec travaux : les travaux doivent représenter au moins 25 % du coût total.</div>
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
      <div class="col-6"><div class="lbl">Durée totale / différé</div><div class="fw-bold" id="pr_d">–</div></div>
      <div class="col-6"><div class="lbl">Mensualité pendant le différé</div><div class="fw-bold" id="pr_m1">–</div></div>
      <div class="col-6"><div class="lbl">Mensualité après le différé</div><div class="fw-bold" id="pr_m">–</div></div>
      <div class="col-6"><div class="lbl">Revenu retenu (÷ coefficient)</div><div class="fw-bold" id="pr_rv">–</div></div>
      <div class="col-6"><div class="lbl">Plafond de ressources</div><div class="fw-bold" id="pr_pr">–</div></div>
      <div class="col-6"><div class="lbl">Plafond de prix retenu</div><div class="fw-bold" id="pr_po">–</div></div>
    </div>
  </div>
  <div class="alert alert-warning py-2 small"><i class="fas fa-triangle-exclamation me-1"></i><strong>Résultat indicatif</strong> : il repose uniquement sur les informations saisies et n'a pas de valeur contractuelle. L'éligibilité définitive est vérifiée sur l'offre de prêt et les justificatifs.</div>
  <p class="small text-muted mb-1">Barème <?= e($ptzBareme['millesime'] ?? '') ?>. Le revenu retenu est le plus élevé du revenu fiscal de référence et du coût de l'opération divisé par <?= e((string)($ptzBareme['diviseur_cout'] ?? 9)) ?>.</p>
  <p class="small text-muted"><strong>Cas non pris en compte</strong> : location-accession (PSLA), bail réel solidaire (BRS), logement social, TVA à taux réduit (QPV, ANRU), transformation d'un local en logement : les règles diffèrent (voir <a href="https://www.service-public.fr/particuliers/vosdroits/F10871" target="_blank" rel="noopener noreferrer">service-public.fr</a>). Pour connaître la zone d'une commune : <a href="https://www.anil.org/outils/outils-de-calcul/votre-pret-a-taux-zero/" target="_blank" rel="noopener noreferrer">simulateur de l'ANIL</a> (la zone est déduite de la commune saisie).</p>
 </div>
</div>
<script><?php readfile(__DIR__ . '/../../assets/js/ptz-calc.js'); ?></script>
<script>
(function(){
  const B=<?= json_encode($ptzBareme) ?>;
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const NUM=['pers','rfr','cout'];
  const calc=p=>window.ptzCalc(B,p);
  // ── Zone déduite du département et de la commune (si la liste a été importée par l'administrateur) ──
  const ZURL=<?= json_encode($ptzZonageUrl ?? null) ?>;
  let communes=new Map(), pending=null;
  const ZMAP={Abis:'A',A:'A',B1:'B1',B2:'B2',C:'C'}, ZLIB={Abis:'A bis',A:'A',B1:'B1',B2:'B2',C:'C'};
  async function jget(u){const r=await fetch(u,{credentials:'same-origin'});if(!r.ok)throw new Error(r.status);return r.json();}
  async function loadDeps(){
    if(!ZURL) return;
    try{
      const j=await jget(ZURL); if(!j.available) return;
      $('pz_dep').innerHTML='<option value="">Choisir…</option>'+j.departements.map(d=>`<option value="${d[0]}">${d[0]} – ${d[1]||''}</option>`).join('');
      $('pz_geo').style.display='';
      if(pending){applyPending();}
    }catch(e){}
  }
  async function loadCommunes(dep){
    communes=new Map(); $('pz_communes').innerHTML=''; $('pz_commune').value=''; $('pz_zinfo').textContent='';
    $('pz_commune').disabled=!dep; if(!dep) return;
    try{
      const j=await jget(ZURL+(ZURL.includes('?')?'&':'?')+'dep='+encodeURIComponent(dep));
      (j.communes||[]).forEach(c=>communes.set(c[1].toLowerCase(),c));
      $('pz_communes').innerHTML=(j.communes||[]).map(c=>`<option value="${String(c[1]).replace(/"/g,'&quot;')}">`).join('');
    }catch(e){}
  }
  function pickCommune(){
    const c=communes.get($('pz_commune').value.trim().toLowerCase());
    if(c){$('pz_zone').value=ZMAP[c[2]]||'C'; $('pz_zinfo').textContent='(déduite : '+ZLIB[c[2]]+')'; run();}
    else $('pz_zinfo').textContent='';
  }
  async function applyPending(){
    const g=pending; pending=null; if(!g||!g.dep) return;
    $('pz_dep').value=g.dep; await loadCommunes(g.dep); if(g.commune){$('pz_commune').value=g.commune; pickCommune();}
  }
  $('pz_dep').addEventListener('change',()=>loadCommunes($('pz_dep').value));
  $('pz_commune').addEventListener('input',pickCommune);
  $('pz_zone').addEventListener('change',()=>{$('pz_zinfo').textContent='';});
  window.toolsShare={get:()=>({dep:$('pz_dep').value,commune:$('pz_commune').value}),set:o=>{pending=o;if($('pz_geo').style.display!=='none')applyPending();}};
  function params(){return {dep:$('pz_dep').value,commune:$('pz_commune').value,pers:n('pz_pers'),rfr:n('pz_rfr'),primo:$('pz_primo').checked,rp:$('pz_rp').checked,zone:$('pz_zone').value,type:$('pz_type').value,cout:n('pz_cout')};}
  function run(){
    const p=params(), r=calc(p);
    $('pr_mont').textContent=eur(r.mont);
    $('pr_stat').innerHTML=r.errs.length?'<span style="color:#f87171"><i class="fas fa-circle-xmark me-1"></i>Non éligible : '+r.errs.join(' ; ')+'.</span>':'<span style="color:#4ade80"><i class="fas fa-circle-check me-1"></i>Éligible sur la base des informations saisies.</span>';
    $('pr_tr').textContent='Tranche '+(r.tr+1)+' (revenu retenu '+eur(r.revenu)+')';
    $('pr_q').textContent=r.q+' % du prix plafonné ('+eur(r.prix)+')';
    $('pr_d').textContent=r.d.total+' ans dont '+r.d.differe+' ans de différé';
    $('pr_m').textContent=r.mens>0?eur2(r.mens):'–';
    $('pr_m1').textContent=r.mont>0?(r.d.differe>0?'0 € (différé de '+r.d.differe+' ans)':'Pas de différé'):'–';
    $('pr_rv').textContent=eur(r.revenu)+' ÷ '+String(r.coeff).replace('.',',')+' = '+eur(r.rpc);
    $('pr_pr').textContent=eur(r.maxRev); $('pr_po').textContent=eur(r.po);
    return {p,r};
  }
  ['pers','rfr','primo','rp','zone','type','cout'].forEach(k=>$('pz_'+k).addEventListener('input',run));
  window.pzPrepare=function(f){const x=run();f.params.value=JSON.stringify(x.p);f.resultat.value=JSON.stringify({ptz:x.r.mont,eligible:x.r.errs.length===0,tranche:x.r.tr+1,duree:x.r.d.total*12,differe:x.r.d.differe*12});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){NUM.forEach(k=>{if(load[k]!==undefined)$('pz_'+k).value=load[k];});['zone','type'].forEach(k=>{if(load[k])$('pz_'+k).value=load[k];});if('primo' in load)$('pz_primo').checked=!!load.primo;if('rp' in load)$('pz_rp').checked=!!load.rp;if(load.dep)pending={dep:load.dep,commune:load.commune||''};}
  run(); loadDeps();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
