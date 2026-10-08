<?php
// Estimation des frais de notaire, partagée par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
require_once __DIR__ . '/../../includes/baremes.php';
$ntBar = baremeGet('notaire');
$ntData = $ntBar['data'];
$ntDeps = (array)($ntData['departements'] ?? []);
ksort($ntDeps);
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-house me-2 text-danger"></i>Le bien</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Prix d'acquisition (€)</label><input type="number" min="0" step="any" class="form-control" id="nt_prix" value="200000"></div>
      <div class="col-6"><label class="form-label small mb-0">Dont mobilier (€)</label><input type="number" min="0" step="any" class="form-control" id="nt_mob" value="0"></div>
      <div class="col-6"><label class="form-label small mb-0">Type de bien</label>
        <select class="form-select" id="nt_type"><option value="ancien">Ancien</option><option value="neuf">Neuf (moins de 5 ans, VEFA)</option></select></div>
      <div class="col-6"><label class="form-label small mb-0"><?= $ntDeps ? 'Département du bien (ancien)' : 'Droits départementaux (ancien)' ?></label>
        <select class="form-select" id="nt_dep">
        <?php if ($ntDeps): foreach ($ntDeps as $code => $dep): $tx = (float)$dep['taux']; $tp = (float)($dep['taux_primo'] ?? $dep['taux']); ?>
            <option value="<?= e((string)$code) ?>" data-taux="<?= $tx ?>" data-primo="<?= $tp ?>"><?= e($code . ' – ' . ($dep['nom'] ?? '') . ' (' . str_replace('.', ',', rtrim(rtrim(number_format($tx, 2, '.', ''), '0'), '.')) . ' %)') ?></option><?php endforeach; ?>
        <option value="autre" data-taux="<?= (float)$ntData['taux_departemental_defaut'] ?>" data-primo="<?= (float)$ntData['taux_departemental_defaut'] ?>">Autre département (<?= e(str_replace('.', ',', (string)$ntData['taux_departemental_defaut'])) ?> %)</option>
        <?php else: ?><option value="4.5" data-taux="4.5" data-primo="4.5">4,50 % (cas général)</option><option value="5" data-taux="5" data-primo="5">5,00 % (départements ayant relevé le taux)</option><option value="3.8" data-taux="3.8" data-primo="3.8">3,80 % (taux réduit)</option><?php endif; ?>
        </select></div>
      <?php if ($ntDeps): ?><div class="col-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="nt_primo"><label class="form-check-label small" for="nt_primo">Primo-accédant (résidence principale)</label></div></div><?php endif; ?>
      <div class="col-12"><label class="form-label small mb-0">Débours et formalités estimés (€)</label><input type="number" min="0" step="any" class="form-control" id="nt_deb" value="1200"></div>
    </div>
    <div class="form-text">Le taux départemental dépend du département du bien : vérifiez-le auprès de l'étude notariale.</div>
    <?php if (baremeStatut($ntBar) !== 'ok'): ?><div class="form-text text-warning-emphasis"><i class="fas fa-triangle-exclamation"></i> Barème <?= $ntBar['valide'] ? 'à revoir (plus de 12 mois)' : 'non contrôlé' ?> : estimation à confirmer.</div><?php endif; ?>
  </div>
  <?php if ($capSave) simSaveForm($capLoad, 'ntPrepare'); ?>
 </div>
 <div class="col-lg-6">
  <div class="cap-res js-result">
    <div class="lbl">Frais de notaire estimés</div><div class="big" id="nr_tot">–</div>
    <div class="lbl mt-1" id="nr_pct"></div>
    <table class="table table-sm table-borderless text-white mb-0 mt-3" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="nr_det"></tbody></table>
  </div>
  <p class="small text-muted">Estimation indicative : le montant définitif est fixé par le notaire. Les frais d'acquisition sont calculés sur le prix hors mobilier.</p>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const IDS=['prix','mob','type','dep','deb','primo'];
  // Barème dégressif des émoluments du notaire (HT)
  const BAR=<?= json_encode($ntData) ?>;
  const TRANCHES=BAR.emoluments.map(t=>[t[0]===null?Infinity:t[0],t[1]]);
  function emoluments(base){let r=0,prev=0;for(const [max,t] of TRANCHES){if(base>prev){r+=(Math.min(base,max)-prev)*t/100;}prev=max;}return r;}
  function calc(p){
    const base=Math.max(0,p.prix-p.mob);
    const opt=$('nt_dep').selectedOptions[0]||{dataset:{}}, tdep=parseFloat((p.primo&&opt.dataset.primo!==undefined)?opt.dataset.primo:opt.dataset.taux)||0;
    let droits,lib;
    if(p.type==='neuf'){droits=base*BAR.droits_neuf/100;lib='Droits et taxes (neuf, '+String(BAR.droits_neuf).replace('.',',')+' %)';}
    else{const dep=base*tdep/100,comm=base*BAR.taxe_communale/100,assiette=dep*BAR.frais_assiette/100;droits=dep+comm+assiette;lib='Droits de mutation (départ. '+String(tdep).replace('.',',')+' % + commune '+String(BAR.taxe_communale).replace('.',',')+' % + frais d\'assiette)';}
    const emol=emoluments(base), tva=emol*BAR.tva/100, csi=Math.max(15,base*BAR.csi/100);
    const tot=droits+emol+tva+csi+p.deb;
    return {base,droits,lib,emol,tva,csi,deb:p.deb,tot};
  }
  function params(){const p={};IDS.forEach(k=>{const el=$('nt_'+k);p[k]=!el?false:(k==='type'||k==='dep')?el.value:(k==='primo')?el.checked:n('nt_'+k);});return p;}
  function run(){
    const p=params(), r=calc(p);
    $('nr_tot').textContent=eur(r.tot);
    $('nr_pct').textContent=r.base>0?(r.tot/r.base*100).toFixed(2).replace('.',',')+' % du prix hors mobilier':'';
    $('nr_det').innerHTML=[[r.lib,r.droits],['Émoluments du notaire (HT)',r.emol],['TVA sur émoluments ('+BAR.tva+' %)',r.tva],['Contribution de sécurité immobilière',r.csi],['Débours et formalités',r.deb]]
      .map(x=>`<tr><td>${x[0]}</td><td class="text-end">${eur(x[1])}</td></tr>`).join('');
    return {p,r};
  }
  IDS.forEach(k=>{const el=$('nt_'+k);if(el){el.addEventListener('input',run);el.addEventListener('change',run);}});
  window.ntPrepare=function(f){const x=run();f.params.value=JSON.stringify(x.p);f.resultat.value=JSON.stringify({frais:Math.round(x.r.tot*100)/100,prix:x.p.prix,pct:x.r.base>0?Math.round(x.r.tot/x.r.base*10000)/100:0});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{const el=$('nt_'+k);if(!el||load[k]===undefined) return; if(k==='primo') el.checked=!!load[k]; else el.value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
