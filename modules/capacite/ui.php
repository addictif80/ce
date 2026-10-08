<?php
// Simulateur de capacité d'emprunt, partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool : affiche le bouton « Enregistrer »), $capLoad (array|null : simulation à recharger).
$capLoad = $capLoad ?? null;
?>
<?php require __DIR__ . '/../_sim/style.php'; require_once __DIR__ . '/../../includes/baremes.php'; $hcsf = baremeGet('hcsf')['data']; ?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-wallet me-2 text-danger"></i>Ressources et charges (par mois)</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Revenus nets du foyer</label><input type="number" min="0" step="any" class="form-control" id="cp_rev" value="3500"></div>
      <div class="col-6"><label class="form-label small mb-0">Charges de crédit en cours</label><input type="number" min="0" step="any" class="form-control" id="cp_chg" value="0"></div>
      <div class="col-6"><label class="form-label small mb-0">Loyer conservé après achat</label><input type="number" min="0" step="any" class="form-control" id="cp_loyer" value="0"></div>
      <div class="col-6"><label class="form-label small mb-0">Taux d'endettement maximum (%)</label><input type="number" min="1" max="60" step="any" class="form-control" id="cp_te" value="<?= (float)$hcsf['taux_endettement_max'] ?>"></div>
      <div class="col-6"><label class="form-label small mb-0">Personnes dans le foyer</label><input type="number" min="1" step="1" class="form-control" id="cp_pers" value="2"></div>
      <div class="col-6"><label class="form-label small mb-0">Apport (€)</label><input type="number" min="0" step="any" class="form-control" id="cp_apport" value="20000"></div>
    </div></div>
  <div class="cap-card"><h2><i class="fas fa-percent me-2 text-danger"></i>Conditions du prêt</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Taux nominal (%)</label><input type="number" min="0" step="0.01" class="form-control" id="cp_taux" value="3.5"></div>
      <div class="col-6"><label class="form-label small mb-0">Durée (années)</label><input type="number" min="1" max="30" step="1" class="form-control" id="cp_duree" value="25"></div>
      <div class="col-6"><label class="form-label small mb-0">Assurance (% du capital initial / an)</label><input type="number" min="0" step="0.001" class="form-control" id="cp_ass" value="0.30"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais de dossier (€)</label><input type="number" min="0" step="any" class="form-control" id="cp_fd" value="500"></div>
      <div class="col-6"><label class="form-label small mb-0">Garantie (% du capital)</label><input type="number" min="0" step="0.01" class="form-control" id="cp_gar" value="1.2"></div>
      <div class="col-6"><label class="form-label small mb-0">Frais de notaire (% du prix)</label>
        <select class="form-select" id="cp_not"><option value="7.5">Ancien (≈ 7,5 %)</option><option value="2.5">Neuf (≈ 2,5 %)</option><option value="0">Aucun</option></select></div>
    </div></div>
  <?php if ($capSave) simSaveForm($capLoad, 'capPrepare'); ?>
 </div>
 <div class="col-lg-6">
  <div class="cap-res js-result">
    <div class="lbl">Capital empruntable</div><div class="big" id="cr_cap">–</div>
    <div class="row mt-3 g-3">
      <div class="col-6"><div class="lbl">Budget d'achat (frais inclus)</div><div class="fs-4 fw-bold" id="cr_prix">–</div></div>
      <div class="col-6"><div class="lbl">Mensualité maximale (assurance incluse)</div><div class="fs-4 fw-bold" id="cr_mens">–</div></div>
      <div class="col-6"><div class="lbl">Reste à vivre estimé</div><div class="fw-bold" id="cr_rav">–</div></div>
      <div class="col-6"><div class="lbl">Coût total du crédit (intérêts + assurance)</div><div class="fw-bold" id="cr_cout">–</div></div>
    </div>
    <div class="mt-3 small" id="cr_msg"></div>
  </div>
  <div class="cap-card"><h2>Selon la durée</h2>
    <table class="table table-sm mb-0"><thead><tr><th>Durée</th><th class="text-end">Capital</th><th class="text-end">Budget d'achat</th></tr></thead><tbody id="cr_dur"></tbody></table>
  </div>
  <p class="small text-muted">Simulation indicative, sans valeur contractuelle : l'accord de prêt dépend de l'étude complète du dossier. Le plafond d'endettement de <?= (float)$hcsf['taux_endettement_max'] ?> % est la référence du Haut Conseil de stabilité financière ; durée maximale recommandée : <?= (int)$hcsf['duree_max_annees'] ?> ans (<?= (int)$hcsf['duree_max_annees_neuf'] ?> ans pour le neuf).</p>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const IDS=['rev','chg','loyer','te','pers','apport','taux','duree','ass','fd','gar','not'];
  // Mensualité par euro emprunté (hors assurance) ; assurance calculée sur le capital initial
  function pmtUnit(tauxPct,mois){const i=tauxPct/1200;return i===0?1/mois:i/(1-Math.pow(1+i,-mois));}
  function calc(p,annees){
    const mois=Math.round(annees*12);
    const mensMax=Math.max(0,p.rev*p.te/100-p.chg-p.loyer);
    const unit=pmtUnit(p.taux,mois)+p.ass/1200;
    const cap=unit>0?mensMax/unit:0;
    // prix tel que prix*(1+notaire) + garantie*cap + frais dossier = cap + apport
    const dispo=cap*(1-p.gar/100)+p.apport-p.fd;
    const prix=Math.max(0,dispo/(1+p.not/100));
    const interets=pmtUnit(p.taux,mois)*cap*mois-cap, assur=cap*p.ass/1200*mois;
    return {mois,mensMax,cap,prix,cout:interets+assur+p.fd+cap*p.gar/100};
  }
  function params(){const p={};IDS.forEach(k=>p[k]=n('cp_'+k));return p;}
  function run(){
    const p=params(), r=calc(p,p.duree);
    $('cr_cap').textContent=eur(r.cap); $('cr_prix').textContent=eur(r.prix); $('cr_mens').textContent=eur2(r.mensMax);
    $('cr_cout').textContent=eur(r.cout);
    const rav=p.rev-p.chg-p.loyer-r.mensMax, perPers=rav/Math.max(1,p.pers);
    $('cr_rav').textContent=eur(rav)+' ('+eur(perPers)+' / pers.)';
    $('cr_msg').innerHTML=r.mensMax<=0?'<span class="cap-ko">Les charges dépassent la capacité de remboursement : aucun emprunt possible dans ces conditions.</span>':'';
    $('cr_dur').innerHTML=[10,15,20,25,30].map(a=>{const x=calc(p,a);return `<tr class="${a==p.duree?'table-active fw-bold':''}"><td>${a} ans</td><td class="text-end">${eur(x.cap)}</td><td class="text-end">${eur(x.prix)}</td></tr>`;}).join('');
    return {p,r};
  }
  IDS.forEach(k=>$('cp_'+k).addEventListener('input',run));
  window.capPrepare=function(f){const x=run();f.params.value=JSON.stringify(x.p);f.resultat.value=JSON.stringify({capital:Math.round(x.r.cap),budget:Math.round(x.r.prix),mensualite:Math.round(x.r.mensMax*100)/100});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{if(load[k]!==undefined) $('cp_'+k).value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
