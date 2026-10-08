<?php
// Simulateur de rachat de crédits, partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null : simulation à recharger, uniquement si elle est de ce type).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
?>
<?php if (empty($simBarDone)) require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
  <div class="col-lg-7"><div class="cap-card"><h2><i class="fas fa-list me-2 text-danger"></i>Crédits à regrouper</h2>
      <div id="rc_list"></div>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="rc_add"><i class="fas fa-plus"></i> Ajouter un crédit</button>
      <div class="form-text">IRA : crédit immobilier = le plus faible de 3 % du capital restant dû et 6 mois d'intérêts ; consommation = 1 % (0,5 % si moins d'un an restant), aucune indemnité si le capital remboursé ne dépasse pas 10 000 €.</div></div>
    <div class="cap-card"><h2><i class="fas fa-percent me-2 text-danger"></i>Nouveau prêt de regroupement</h2><div class="row g-2">
      <div class="col-4"><label class="form-label small mb-0">Taux (%)</label><input type="number" min="0" step="0.01" class="form-control" id="rc_taux" value="4.5"></div>
      <div class="col-4"><label class="form-label small mb-0">Durée (mois)</label><input type="number" min="1" step="1" class="form-control" id="rc_dur" value="180"></div>
      <div class="col-4"><label class="form-label small mb-0">Assurance (% capital / an)</label><input type="number" min="0" step="0.01" class="form-control" id="rc_ass" value="0.3"></div>
      <div class="col-4"><label class="form-label small mb-0">Frais de dossier (€)</label><input type="number" min="0" step="any" class="form-control" id="rc_fd" value="1000"></div>
      <div class="col-4"><label class="form-label small mb-0">Garantie (% capital)</label><input type="number" min="0" step="0.01" class="form-control" id="rc_gar" value="1.2"></div>
      <div class="col-4"><label class="form-label small mb-0">Revenus mensuels du foyer (€)</label><input type="number" min="0" step="any" class="form-control" id="rc_rev" value="4000"></div>
      <div class="col-12"><label class="form-label small mb-0">Trésorerie complémentaire demandée (€)</label><input type="number" min="0" step="any" class="form-control" id="rc_tres" value="0"></div></div></div></div>
  <div class="col-lg-5"><div class="cap-res js-result">
      <div class="lbl">Capital du nouveau prêt</div><div class="big" id="rcr_cap">–</div>
      <div class="small mt-1" id="rcr_det"></div>
      <div class="row mt-3 g-3">
        <div class="col-6"><div class="lbl">Mensualités actuelles</div><div class="fs-5 fw-bold" id="rcr_av">–</div></div>
        <div class="col-6"><div class="lbl">Nouvelle mensualité (assurance incluse)</div><div class="fs-5 fw-bold" id="rcr_ap">–</div></div>
        <div class="col-6"><div class="lbl">Variation mensuelle</div><div class="fw-bold" id="rcr_gain">–</div></div>
        <div class="col-6"><div class="lbl">Endettement avant → après</div><div class="fw-bold" id="rcr_te">–</div></div>
        <div class="col-6"><div class="lbl">Coût restant avant</div><div class="fw-bold" id="rcr_cav">–</div></div>
        <div class="col-6"><div class="lbl">Coût total après</div><div class="fw-bold" id="rcr_cap2">–</div></div></div>
      <div class="mt-3 fw-bold" id="rcr_conc"></div></div>
    <div class="alert alert-warning py-2 small"><i class="fas fa-triangle-exclamation me-1"></i><strong>Attention :</strong> un regroupement baisse la mensualité mais allonge la durée, et le coût global augmente le plus souvent. Simulation indicative, sans valeur contractuelle.</div>
    <?php if ($capSave) simSaveForm($capLoad, 'rcPrepare'); ?>
  </div></div></div>
<script>
(function(){
  const $=id=>document.getElementById(id), n=id=>parseFloat($(id).value)||0;
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €', eur2=v=>(Math.round(v*100)/100).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' €';
  const pmt=(c,t,m)=>{const i=t/1200;return m<=0?0:(i===0?c/m:c*i/(1-Math.pow(1+i,-m)));};
  const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  // ── Rachat de crédits ──
  let credits=[{nom:'Crédit conso 1',type:'conso',crd:15000,mens:380,taux:5.5,rest:48},{nom:'Crédit auto',type:'conso',crd:9000,mens:250,taux:4.9,rest:40}];
  function ira(c){
    if(c.type==='immo') return Math.min(c.crd*0.03,c.crd*c.taux/1200*6);
    if(c.crd<=10000) return 0; // pas d'indemnité si le remboursement anticipé ne dépasse pas 10 000 € sur 12 mois
    return c.rest>12?c.crd*0.01:c.crd*0.005;
  }
  function drawList(){
    $('rc_list').innerHTML=credits.map((c,i)=>`<div class="row g-1 align-items-end mb-2" data-i="${i}">
      <div class="col-3"><label class="form-label small mb-0">Nom</label><input class="form-control form-control-sm" data-f="nom" value="${esc(c.nom)}"></div>
      <div class="col-2"><label class="form-label small mb-0">Type</label><select class="form-select form-select-sm" data-f="type"><option value="conso" ${c.type==='conso'?'selected':''}>Conso</option><option value="immo" ${c.type==='immo'?'selected':''}>Immo</option></select></div>
      <div class="col-2"><label class="form-label small mb-0">Capital dû</label><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="crd" value="${c.crd}"></div>
      <div class="col-2"><label class="form-label small mb-0">Mensualité</label><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="mens" value="${c.mens}"></div>
      <div class="col-1"><label class="form-label small mb-0">Taux</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-f="taux" value="${c.taux}"></div>
      <div class="col-1"><label class="form-label small mb-0">Mois</label><input type="number" step="1" min="0" class="form-control form-control-sm" data-f="rest" value="${c.rest}"></div>
      <div class="col-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-del="${i}"><i class="fas fa-trash"></i></button></div></div>`).join('')||'<div class="text-muted small">Aucun crédit.</div>';
  }
  $('rc_list').addEventListener('input',e=>{const row=e.target.closest('[data-i]');if(!row||!e.target.dataset.f)return;const c=credits[row.dataset.i],f=e.target.dataset.f;c[f]=(f==='nom'||f==='type')?e.target.value:(parseFloat(e.target.value)||0);runRachat();});
  $('rc_list').addEventListener('click',e=>{const b=e.target.closest('[data-del]');if(b){credits.splice(+b.dataset.del,1);drawList();runRachat();}});
  $('rc_add').onclick=()=>{credits.push({nom:'Crédit '+(credits.length+1),type:'conso',crd:0,mens:0,taux:0,rest:0});drawList();runRachat();};
  const RC=['taux','dur','ass','fd','gar','rev','tres'];
  function rachat(){
    const p={}; RC.forEach(k=>p[k]=n('rc_'+k));
    const crd=credits.reduce((t,c)=>t+c.crd,0), iras=credits.reduce((t,c)=>t+ira(c),0), mAv=credits.reduce((t,c)=>t+c.mens,0);
    // capital C tel que C = crd + IRA + trésorerie + frais dossier + garantie% × C
    const cap=(crd+iras+p.tres+p.fd)/Math.max(0.01,1-p.gar/100);
    const mAp=pmt(cap,p.taux,p.dur)+cap*p.ass/1200;
    const cav=credits.reduce((t,c)=>t+c.mens*c.rest,0), cap2=mAp*p.dur;
    return {p,crd,iras,mAv,cap,mAp,cav,cap2,gar:cap*p.gar/100};
  }
  function runRachat(){
    const r=rachat();
    $('rcr_cap').textContent=eur(r.cap);
    $('rcr_det').textContent='Capitaux restants dus '+eur(r.crd)+' + IRA '+eur(r.iras)+' + trésorerie '+eur(r.p.tres)+' + frais '+eur(r.p.fd)+' + garantie '+eur(r.gar);
    $('rcr_av').textContent=eur2(r.mAv); $('rcr_ap').textContent=eur2(r.mAp);
    const g=r.mAp-r.mAv; $('rcr_gain').innerHTML=`<span style="color:${g<=0?'#4ade80':'#f87171'}">${g<=0?'':'+'}${eur2(g)} / mois</span>`;
    const te=m=>r.p.rev>0?(m/r.p.rev*100).toFixed(2).replace('.',',')+' %':'–';
    $('rcr_te').textContent=te(r.mAv)+' → '+te(r.mAp);
    $('rcr_cav').textContent=eur(r.cav); $('rcr_cap2').textContent=eur(r.cap2);
    const d=r.cap2-r.cav;
    $('rcr_conc').innerHTML=r.cap>0?(d>0?`Coût global : <span style="color:#f87171">+${eur(d)}</span> sur la durée (mensualité plus basse, durée plus longue).`:`Coût global : <span style="color:#4ade80">${eur(d)}</span> sur la durée.`):'';
    return r;
  }

  RC.forEach(k=>$('rc_'+k).addEventListener('input',runRachat));

  window.rcPrepare=function(f){const r=runRachat();f.params.value=JSON.stringify({mode:'rachat',credits,rc:r.p});f.resultat.value=JSON.stringify({mode:'rachat',montant:Math.round(r.cap),cle:'Mensualité '+eur2(r.mAp)+' (avant '+eur2(r.mAv)+')'});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load&&load.credits){credits=load.credits;RC.forEach(k=>{if(load.rc&&load.rc[k]!==undefined)$('rc_'+k).value=load.rc[k];});}
  window.toolsShare={get:()=>({credits}),set:o=>{if(Array.isArray(o.credits)){credits=o.credits;drawList();runRachat();}}};
  drawList(); runRachat();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
