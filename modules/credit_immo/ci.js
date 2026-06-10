// ── UTILS ─────────────────────────────────────────────────────────────────────
function escapeHtml(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function fmt(v){return parseFloat(v||0).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2});}
function fmtD(s){if(!s)return '—';const d=new Date(s);return isNaN(d)?s:d.toLocaleDateString('fr-FR');}
function chk(v){return v==1?'<i class="fas fa-check-circle text-success"></i>':'<i class="fas fa-times-circle text-muted"></i>';}
function ck(v){return v==1?'<span class="ck-ok">✓</span>':'<span class="ck-no">—</span>';}
function parseJ(s){try{return JSON.parse(s||'[]');}catch(e){return[];}}
function addJ11(dateStr){if(!dateStr)return '';const d=new Date(dateStr);d.setDate(d.getDate()+11);return d.toISOString().split('T')[0];}

// ── CALCULS ──────────────────────────────────────────────────────────────────
function calcMensualite(capital,tauxAnnuel,duree){
  const tm=tauxAnnuel/100/12;
  if(tm>0&&duree>0) return capital*tm/(1-Math.pow(1+tm,-duree));
  if(duree>0) return capital/duree;
  return 0;
}
function getTotalFinancement(d){
  return parseFloat(d.montant_acquisition||0)
    +parseFloat(d.frais_notaire||0)
    +parseFloat(d.frais_dossier||0)
    +(d.frais_midi_epargne==1?parseFloat(d.montant_midi_epargne||0):0)
    +parseFloat(d.frais_negociation||0)
    +parseFloat(d.frais_divers||0)
    +parseFloat(d.frais_agence||0)
    +parseFloat(d.tva_financee||0)
    +parseFloat(d.garantie_montant||0);
}
function getCapitalPH(d){
  return getTotalFinancement(d)
    -parseFloat(d.apport||0)
    -(d.ptz_actif==1?parseFloat(d.ptz_montant||0):0)
    -(d.ecoptz_actif==1?parseFloat(d.ecoptz_montant||0):0);
}
function getMensPTZ(d){return d.ptz_actif==1&&d.ptz_duree>0?parseFloat(d.ptz_montant||0)/parseInt(d.ptz_duree):0;}
function getMensEcoPTZ(d){return d.ecoptz_actif==1&&d.ecoptz_duree>0?parseFloat(d.ecoptz_montant||0)/parseInt(d.ecoptz_duree):0;}
function getMensGlobale(d){
  return calcMensualite(getCapitalPH(d),parseFloat(d.taux_emprunt||0),parseInt(d.duree_emprunt||0))
    +getMensPTZ(d)+getMensEcoPTZ(d);
}
function calcRevenus(json){
  return parseJ(json).reduce((t,r)=>{
    let m=parseFloat(r.montant||0);
    if(r.revenu_futur) m*=(parseFloat(r.ponderation||100)/100);
    if(r.periodicite==='annuelle') m/=12;
    return t+m;
  },0);
}
function calcChargesConservees(json){
  return parseJ(json).reduce((t,c)=>t+(c.non_conserve?0:parseFloat(c.montant||0)),0);
}
function calcTauxEndett(d){
  const rev=calcRevenus(d.revenus_json);
  if(rev<=0) return null;
  const charges=calcChargesConservees(d.charges_json);
  return (getMensGlobale(d)+charges)/rev*100;
}
function badgeEndett(t){
  if(t===null) return '<span class="badge bg-secondary">N/A</span>';
  const v=parseFloat(t).toFixed(1);
  if(t<=25) return `<span class="badge bg-success">${v}%</span>`;
  if(t<=33) return `<span class="badge bg-warning text-dark">${v}%</span>`;
  if(t<=35) return `<span class="badge bg-orange text-white">${v}% <i class="fas fa-exclamation-triangle"></i></span>`;
  return `<span class="badge bg-danger">${v}% <i class="fas fa-exclamation-circle"></i></span>`;
}
function alertEndett(t){
  if(t===null||t<=33) return '';
  if(t<=35) return '<div class="alert alert-warning mt-2 py-1"><i class="fas fa-exclamation-triangle"></i> Taux proche du seuil HCSF 35%</div>';
  return '<div class="alert alert-danger mt-2 py-1"><i class="fas fa-exclamation-circle"></i> <strong>ALERTE</strong> : Taux supérieur au seuil HCSF de 35%</div>';
}

// Remplir tableau ligne de résumé au chargement
document.addEventListener('DOMContentLoaded',()=>{
  filterTable('searchDossiers','tableDossiers');
  dossiersData.forEach(d=>{
    const mg=getMensGlobale(d);
    const te=calcTauxEndett(d);
    const em=document.getElementById('mens_'+d.id);
    const et=document.getElementById('tend_'+d.id);
    if(em) em.textContent=fmt(mg)+' €';
    if(et) et.innerHTML=badgeEndett(te);
  });
  const op=new URLSearchParams(location.search).get('open');
  if(op){showDetail(parseInt(op));history.replaceState(null,'','index.php');}
});

// ── DYNAMIC JSON ROWS ─────────────────────────────────────────────────────────
function buildRevenuRow(r,px,idx){
  r=r||{};
  const isFutur=r.revenu_futur?'checked':'';
  const mensChecked=(r.periodicite==='mensuelle'||!r.periodicite)?'checked':'';
  const annChecked=r.periodicite==='annuelle'?'checked':'';
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-start" data-type="revenu">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(r.intitule||'')}" data-field="intitule" oninput="serializeJson('${px}','revenus')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${r.montant||''}" data-field="montant" oninput="serializeJson('${px}','revenus');liveEndett('${px}')">
    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" ${isFutur} data-field="revenu_futur" onchange="toggleFutur(this,'${px}',${idx});serializeJson('${px}','revenus');liveEndett('${px}')"><label class="form-check-label small">Revenu futur</label></div>
    <div class="ci-futur-opts ms-2" style="${r.revenu_futur?'':'display:none'}">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="${px}_per_${idx}" value="mensuelle" ${mensChecked} data-field="periodicite" onchange="serializeJson('${px}','revenus');liveEndett('${px}')"><label class="form-check-label small">Mensuelle</label></div>
        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="${px}_per_${idx}" value="annuelle" ${annChecked} data-field="periodicite" onchange="serializeJson('${px}','revenus');liveEndett('${px}')"><label class="form-check-label small">Annuelle</label></div>
        <div class="d-flex align-items-center gap-1"><input type="number" step="1" min="0" max="100" class="form-control form-control-sm" style="width:70px" placeholder="%" value="${r.ponderation||100}" data-field="ponderation" oninput="serializeJson('${px}','revenus');liveEndett('${px}')"><span class="small">%</span></div>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}','revenus')"><i class="fas fa-times"></i></button>
  </div>`;
}

function buildChargeRow(c,px){
  c=c||{};
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-center" data-type="charge">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(c.intitule||'')}" data-field="intitule" oninput="serializeJson('${px}','charges')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${c.montant||''}" data-field="montant" oninput="serializeJson('${px}','charges');liveEndett('${px}')">
    <div class="form-check"><input class="form-check-input" type="checkbox" ${c.non_conserve?'checked':''} data-field="non_conserve" onchange="serializeJson('${px}','charges');liveEndett('${px}')"><label class="form-check-label small text-muted">Non conservé</label></div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}','charges')"><i class="fas fa-times"></i></button>
  </div>`;
}

function buildEpargneRow(e,px){
  e=e||{};
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-center" data-type="epargne">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(e.intitule||'')}" data-field="intitule" oninput="serializeJson('${px}','epargne')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${e.montant||''}" data-field="montant" oninput="serializeJson('${px}','epargne')">
    <div class="form-check"><input class="form-check-input" type="checkbox" ${e.hors_cemp?'checked':''} data-field="hors_cemp" onchange="toggleCemp(this,'${px}');serializeJson('${px}','epargne')"><label class="form-check-label small">Hors CEMP</label></div>
    <div class="ci-cemp-banque" style="${e.hors_cemp?'':'display:none'}"><input type="text" class="form-control form-control-sm" style="width:140px" placeholder="Nom de la banque" value="${escapeHtml(e.nom_banque||'')}" data-field="nom_banque" oninput="serializeJson('${px}','epargne')"></div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}','epargne')"><i class="fas fa-times"></i></button>
  </div>`;
}

function buildAdeBlock(a,px,idx){
  a=a||{};
  const couv=a.couverture||[];
  return `<div class="ci-json-row" data-type="ade">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong class="small">Assuré ${idx+1}</strong>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this,'${px}','ade')"><i class="fas fa-times"></i></button>
    </div>
    <div class="row g-2">
      <div class="col-12">
        <label class="form-label small mb-1">Couverture</label>
        <div class="d-flex gap-3 flex-wrap">
          ${['DC','PTIA','ITT','Invalidité'].map(c=>`<div class="form-check"><input class="form-check-input" type="checkbox" value="${c}" ${couv.includes(c)?'checked':''} data-field="couverture" onchange="serializeJson('${px}','ade')"><label class="form-check-label small">${c}</label></div>`).join('')}
        </div>
      </div>
      <div class="col-md-2"><label class="form-label small">Quotité %</label><input type="number" step="1" min="0" max="100" class="form-control form-control-sm" value="${a.quotite||''}" data-field="quotite" oninput="serializeJson('${px}','ade')"></div>
      <div class="col-md-3"><label class="form-label small">Type</label><select class="form-select form-select-sm" data-field="type" onchange="serializeJson('${px}','ade')">
        <option value="">--</option>
        <option value="INDEMNITAIRE" ${a.type==='INDEMNITAIRE'?'selected':''}>INDEMNITAIRE</option>
        <option value="FORFAITAIRE" ${a.type==='FORFAITAIRE'?'selected':''}>FORFAITAIRE</option>
      </select></div>
      <div class="col-md-2"><label class="form-label small">Franchise</label><select class="form-select form-select-sm" data-field="franchise" onchange="serializeJson('${px}','ade')">
        <option value="">--</option>
        <option value="30J" ${a.franchise==='30J'?'selected':''}>30J</option>
        <option value="90J" ${a.franchise==='90J'?'selected':''}>90J</option>
      </select></div>
      <div class="col-md-2"><label class="form-label small">IPP</label><select class="form-select form-select-sm" data-field="ipp" onchange="serializeJson('${px}','ade')">
        <option value="">--</option>
        <option value="33%" ${a.ipp==='33%'?'selected':''}>33%</option>
        <option value="66%" ${a.ipp==='66%'?'selected':''}>66%</option>
      </select></div>
      <div class="col-md-3"><label class="form-label small">Coût total € <span class="text-muted">(hors endettement)</span></label><input type="number" step="0.01" class="form-control form-control-sm" value="${a.cout_total||''}" data-field="cout_total" oninput="serializeJson('${px}','ade')"></div>
    </div>
  </div>`;
}

function toggleFutur(cb,px,idx){
  const row=cb.closest('.ci-json-row');
  const opts=row.querySelector('.ci-futur-opts');
  if(opts) opts.style.display=cb.checked?'':'none';
}
function toggleCemp(cb,px){
  const row=cb.closest('.ci-json-row');
  const banq=row.querySelector('.ci-cemp-banque');
  if(banq) banq.style.display=cb.checked?'':'none';
}

function serializeJson(px,type){
  const map={revenus:'revenu',charges:'charge',epargne:'epargne',ade:'ade'};
  const container=document.getElementById(px+'_'+type+'_list');
  if(!container) return;
  const rows=container.querySelectorAll('.ci-json-row');
  const result=[];
  rows.forEach(row=>{
    const obj={};
    if(type==='revenus'){
      obj.intitule=row.querySelector('[data-field="intitule"]')?.value||'';
      obj.montant=parseFloat(row.querySelector('[data-field="montant"]')?.value||0);
      obj.revenu_futur=row.querySelector('[data-field="revenu_futur"]')?.checked||false;
      if(obj.revenu_futur){
        const radios=row.querySelectorAll('[data-field="periodicite"]');
        obj.periodicite=Array.from(radios).find(r=>r.checked)?.value||'mensuelle';
        obj.ponderation=parseFloat(row.querySelector('[data-field="ponderation"]')?.value||100);
      }
    } else if(type==='charges'){
      obj.intitule=row.querySelector('[data-field="intitule"]')?.value||'';
      obj.montant=parseFloat(row.querySelector('[data-field="montant"]')?.value||0);
      obj.non_conserve=row.querySelector('[data-field="non_conserve"]')?.checked||false;
    } else if(type==='epargne'){
      obj.intitule=row.querySelector('[data-field="intitule"]')?.value||'';
      obj.montant=parseFloat(row.querySelector('[data-field="montant"]')?.value||0);
      obj.hors_cemp=row.querySelector('[data-field="hors_cemp"]')?.checked||false;
      obj.nom_banque=row.querySelector('[data-field="nom_banque"]')?.value||'';
    } else if(type==='ade'){
      const covCbs=row.querySelectorAll('[data-field="couverture"]:checked');
      obj.couverture=Array.from(covCbs).map(c=>c.value);
      obj.quotite=parseFloat(row.querySelector('[data-field="quotite"]')?.value||0);
      obj.type=row.querySelector('[data-field="type"]')?.value||'';
      obj.franchise=row.querySelector('[data-field="franchise"]')?.value||'';
      obj.ipp=row.querySelector('[data-field="ipp"]')?.value||'';
      obj.cout_total=parseFloat(row.querySelector('[data-field="cout_total"]')?.value||0);
    }
    result.push(obj);
  });
  const hidden=document.getElementById(px+'_'+type+'_json');
  if(hidden) hidden.value=JSON.stringify(result);
}

function addRow(px,type){
  const container=document.getElementById(px+'_'+type+'_list');
  if(!container) return;
  const idx=container.querySelectorAll('.ci-json-row').length;
  let html='';
  if(type==='revenus') html=buildRevenuRow(null,px,idx);
  else if(type==='charges') html=buildChargeRow(null,px);
  else if(type==='epargne') html=buildEpargneRow(null,px);
  else if(type==='ade') html=buildAdeBlock(null,px,idx);
  container.insertAdjacentHTML('beforeend',html);
  serializeJson(px,type);
}

function removeRow(btn,px,type){
  btn.closest('.ci-json-row').remove();
  serializeJson(px,type);
  if(type==='revenus'||type==='charges') liveEndett(px);
}

function loadJsonRows(px,d){
  ['revenus','charges','epargne'].forEach(type=>{
    const container=document.getElementById(px+'_'+type+'_list');
    if(!container) return;
    container.innerHTML='';
    const data=parseJ(d[type+'_json']);
    data.forEach((r,i)=>{
      if(type==='revenus') container.insertAdjacentHTML('beforeend',buildRevenuRow(r,px,i));
      else if(type==='charges') container.insertAdjacentHTML('beforeend',buildChargeRow(r,px));
      else container.insertAdjacentHTML('beforeend',buildEpargneRow(r,px));
    });
    serializeJson(px,type);
  });
  // ADE
  const adeC=document.getElementById(px+'_ade_list');
  if(adeC){
    adeC.innerHTML='';
    parseJ(d.ade_json).forEach((a,i)=>adeC.insertAdjacentHTML('beforeend',buildAdeBlock(a,px,i)));
    serializeJson(px,'ade');
  }
}

function liveEndett(px){
  const el=document.getElementById(px+'_endett_live');
  if(!el) return;
  // Collect inline data from form
  const fakeD={
    taux_emprunt:document.getElementById(px+'_taux')?.value||0,
    duree_emprunt:document.getElementById(px+'_duree')?.value||0,
    montant_acquisition:document.getElementById(px+'_mont_acq')?.value||0,
    frais_notaire:document.getElementById(px+'_frais_notaire')?.value||0,
    frais_dossier:document.getElementById(px+'_frais_dossier')?.value||0,
    frais_midi_epargne:document.getElementById(px+'_fme_cb')?.checked?1:0,
    montant_midi_epargne:document.getElementById(px+'_fme_mt')?.value||0,
    frais_negociation:document.getElementById(px+'_frais_neg')?.value||0,
    frais_divers:document.getElementById(px+'_frais_div')?.value||0,
    frais_agence:document.getElementById(px+'_frais_age')?.value||0,
    tva_financee:document.getElementById(px+'_tva')?.value||0,
    apport:document.getElementById(px+'_apport')?.value||0,
    garantie_montant:document.getElementById(px+'_gar_mt')?.value||0,
    ptz_actif:document.getElementById(px+'_ptz_cb')?.checked?1:0,
    ptz_montant:document.getElementById(px+'_ptz_mt')?.value||0,
    ptz_duree:document.getElementById(px+'_ptz_dur')?.value||0,
    ecoptz_actif:document.getElementById(px+'_ecoptz_cb')?.checked?1:0,
    ecoptz_montant:document.getElementById(px+'_ecoptz_mt')?.value||0,
    ecoptz_duree:document.getElementById(px+'_ecoptz_dur')?.value||0,
    revenus_json:document.getElementById(px+'_revenus_json')?.value||'[]',
    charges_json:document.getElementById(px+'_charges_json')?.value||'[]',
  };
  const te=calcTauxEndett(fakeD);
  const mg=getMensGlobale(fakeD);
  const rev=calcRevenus(fakeD.revenus_json);
  const charges=calcChargesConservees(fakeD.charges_json);
  el.innerHTML=`
    <div class="row g-2 mt-1">
      <div class="col-md-3"><div class="card border-0 bg-light text-center p-2"><div class="fw-bold">${fmt(mg)} €</div><div class="small text-muted">Mensualité globale</div></div></div>
      <div class="col-md-3"><div class="card border-0 bg-light text-center p-2"><div class="fw-bold">${fmt(rev)} €</div><div class="small text-muted">Revenus effectifs/mois</div></div></div>
      <div class="col-md-3"><div class="card border-0 bg-light text-center p-2"><div class="fw-bold">${fmt(charges)} €</div><div class="small text-muted">Charges conservées</div></div></div>
      <div class="col-md-3"><div class="card border-0 bg-light text-center p-2"><div class="fw-bold">${badgeEndett(te)}</div><div class="small text-muted">Taux d'endettement</div></div></div>
    </div>${alertEndett(te)}`;
}

// ── FORM TABS BUILDER ─────────────────────────────────────────────────────────
function buildFormTabs(px,d){
  d=d||{};
  const isRL=(d.usage_bien||'')==='RL';
  const hasPTZ=d.ptz_actif==1;
  const hasEcoPTZ=d.ecoptz_actif==1;
  const hasPTZorEco=hasPTZ||hasEcoPTZ;
  const hasCEGC=(d.garantie_type||'')==='CEGC';
  const action=d.id?'edit':'add';

  return `<form method="POST" onsubmit="beforeSubmit('${px}')">
    <input type="hidden" name="action" value="${action}">
    ${d.id?`<input type="hidden" name="id" value="${d.id}">`:''}
    <!-- Hidden JSON fields -->
    <input type="hidden" id="${px}_revenus_json" name="revenus_json" value="${escapeHtml(d.revenus_json||'[]')}">
    <input type="hidden" id="${px}_charges_json" name="charges_json" value="${escapeHtml(d.charges_json||'[]')}">
    <input type="hidden" id="${px}_epargne_json" name="epargne_json" value="${escapeHtml(d.epargne_json||'[]')}">
    <input type="hidden" id="${px}_ade_json" name="ade_json" value="${escapeHtml(d.ade_json||'[]')}">

    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#${px}T1">Client</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T2">Projet</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T3">Financement</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T4">Gestion admin.</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T5">Pièces</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T6">Suivi & Signature</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T7" ${d.id?`onclick="loadNotes(${d.id},'${px}')"`:''}>${d.id?'Notes':''}</a></li>
    </ul>
    <div class="tab-content">

      <!-- ── TAB 1 CLIENT ── -->
      <div class="tab-pane fade show active" id="${px}T1">
        <div class="row g-3 mb-3">
          <div class="col-md-4"><label class="form-label">N° personne</label><input type="text" name="numero_personne" class="form-control" value="${escapeHtml(d.numero_personne||'')}"></div>
          <div class="col-md-4"><label class="form-label">Type client</label><select name="type_client" class="form-select">
            ${['Particulier','Pro','Asso'].map(v=>`<option value="${v}" ${(d.type_client||'Particulier')===v?'selected':''}>${v==='Pro'?'Professionnel':v==='Asso'?'Association':v}</option>`).join('')}
          </select></div>
          <div class="col-md-4"></div>
          <div class="col-md-2"><label class="form-label">Banque de France</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check"><input class="form-check-input" type="radio" name="banque_de_france" value="OK" ${d.banque_de_france==='OK'?'checked':''}><label class="form-check-label">OK</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="banque_de_france" value="KO" ${d.banque_de_france==='KO'?'checked':''}><label class="form-check-label">KO</label></div>
            </div>
          </div>
          <div class="col-md-2"><label class="form-label">DRC</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check"><input class="form-check-input" type="radio" name="drc" value="OK" ${d.drc==='OK'?'checked':''}><label class="form-check-label">OK</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="drc" value="KO" ${d.drc==='KO'?'checked':''}><label class="form-check-label">KO</label></div>
            </div>
          </div>
          <div class="col-md-2"><label class="form-label">TopCC</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check"><input class="form-check-input" type="radio" name="topcc" value="OK" ${d.topcc==='OK'?'checked':''}><label class="form-check-label">OK</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="topcc" value="KO" ${d.topcc==='KO'?'checked':''}><label class="form-check-label">KO</label></div>
            </div>
          </div>
          <div class="col-md-2"><label class="form-label">Primo accédant</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check"><input class="form-check-input" type="radio" name="primo_accedant" value="1" ${d.primo_accedant==1?'checked':''}><label class="form-check-label">OUI</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="primo_accedant" value="0" ${d.primo_accedant===0||d.primo_accedant==='0'?'checked':''}><label class="form-check-label">NON</label></div>
            </div>
          </div>
          <div class="col-md-4"><label class="form-label">Statut d'occupation actuel</label>
            <select name="statut_occupation" class="form-select">
              <option value="">--</option>
              ${[['PROPRIETAIRE','Propriétaire'],['LOCATAIRE_HLM','Locataire HLM'],['AUTRE_LOCATAIRE','Autre locataire'],['LOGE_GRATUIT','Logé à titre gratuit']].map(([v,l])=>`<option value="${v}" ${d.statut_occupation===v?'selected':''}>${l}</option>`).join('')}
            </select>
          </div>
          <div class="col-md-2"><label class="form-label">Nb personnes foyer</label><input type="number" min="0" name="nb_personnes_foyer" class="form-control" value="${d.nb_personnes_foyer??''}"></div>
          <div class="col-md-2"><label class="form-label">Nb enfants</label><input type="number" min="0" name="nb_enfants" class="form-control" value="${d.nb_enfants??''}"></div>
        </div>

        <h6 class="mt-2">Revenus</h6>
        <div id="${px}_revenus_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}','revenus')"><i class="fas fa-plus"></i> Ajouter un revenu</button>

        <h6>Charges</h6>
        <div id="${px}_charges_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}','charges')"><i class="fas fa-plus"></i> Ajouter une charge</button>

        <h6>Épargne</h6>
        <div id="${px}_epargne_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}','epargne')"><i class="fas fa-plus"></i> Ajouter une épargne</button>

        <div id="${px}_endett_live"></div>
      </div>

      <!-- ── TAB 2 PROJET ── -->
      <div class="tab-pane fade" id="${px}T2">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Usage du bien</label>
            <div class="d-flex gap-4 mt-1">
              ${['RP','RS','RL'].map(v=>`<div class="form-check"><input class="form-check-input" type="radio" name="usage_bien" value="${v}" id="${px}_ub_${v}" ${(d.usage_bien||'')==v?'checked':''} onchange="toggleRL('${px}')"><label class="form-check-label" for="${px}_ub_${v}">${v}</label></div>`).join('')}
            </div>
            <div id="${px}_rl_opts" class="mt-2 ms-3" style="${isRL?'':'display:none'}">
              <div class="d-flex gap-4">
                <div class="form-check"><input class="form-check-input" type="radio" name="usage_rl_type" value="RL_PRINCIPALE" ${d.usage_rl_type==='RL_PRINCIPALE'?'checked':''}><label class="form-check-label">RL Principale</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="usage_rl_type" value="RL_SECONDAIRE" ${d.usage_rl_type==='RL_SECONDAIRE'?'checked':''}><label class="form-check-label">RL Secondaire</label></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Mode d'occupation</label>
            <div class="d-flex gap-4 mt-1">
              <div class="form-check"><input class="form-check-input" type="radio" name="mode_occupation" value="RP" ${d.mode_occupation==='RP'?'checked':''}><label class="form-check-label">RP</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="mode_occupation" value="LOCATAIRE" ${d.mode_occupation==='LOCATAIRE'?'checked':''}><label class="form-check-label">Locataire</label></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── TAB 3 FINANCEMENT ── -->
      <div class="tab-pane fade" id="${px}T3">
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Taux d'emprunt (%)</label><input type="number" step="0.001" name="taux_emprunt" id="${px}_taux" class="form-control" value="${d.taux_emprunt||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Durée (mois)</label><input type="number" name="duree_emprunt" id="${px}_duree" class="form-control" value="${d.duree_emprunt||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Montant de l'acquisition (€)</label><input type="number" step="0.01" name="montant_acquisition" id="${px}_mont_acq" class="form-control" value="${d.montant_acquisition||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Dont mobilier financable (€)</label><input type="number" step="0.01" name="dont_mobilier_financable" class="form-control" value="${d.dont_mobilier_financable||0}"></div>
          <div class="col-md-3"><label class="form-label">Frais de notaire (€)</label><input type="number" step="0.01" name="frais_notaire" id="${px}_frais_notaire" class="form-control" value="${d.frais_notaire||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Frais de dossier (€)</label><input type="number" step="0.01" name="frais_dossier" id="${px}_frais_dossier" class="form-control" value="${d.frais_dossier||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3">
            <label class="form-label">
              <input type="checkbox" name="frais_midi_epargne" id="${px}_fme_cb" ${d.frais_midi_epargne==1?'checked':''} onchange="toggleFME('${px}');liveEndett('${px}')"> Frais Midi Épargne
            </label>
            <div id="${px}_fme_wrap" style="${d.frais_midi_epargne==1?'':'display:none'}">
              <input type="number" step="0.01" name="montant_midi_epargne" id="${px}_fme_mt" class="form-control" value="${d.montant_midi_epargne||0}" oninput="liveEndett('${px}')">
            </div>
          </div>
          <div class="col-md-3"><label class="form-label">Frais de négociation (€)</label><input type="number" step="0.01" name="frais_negociation" id="${px}_frais_neg" class="form-control" value="${d.frais_negociation||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Frais divers (€)</label><input type="number" step="0.01" name="frais_divers" id="${px}_frais_div" class="form-control" value="${d.frais_divers||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Frais d'agence (€)</label><input type="number" step="0.01" name="frais_agence" id="${px}_frais_age" class="form-control" value="${d.frais_agence||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">TVA financée à rembourser (€)</label><input type="number" step="0.01" name="tva_financee" id="${px}_tva" class="form-control" value="${d.tva_financee||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Apport (€)</label><input type="number" step="0.01" name="apport" id="${px}_apport" class="form-control" value="${d.apport||0}" oninput="liveEndett('${px}')"></div>
          <div class="col-md-3">
            <label class="form-label">Garantie</label>
            <select name="garantie_type" id="${px}_gar_type" class="form-select" onchange="onGarantieChange('${px}')">
              <option value="">--</option>
              <option value="CEGC" ${d.garantie_type==='CEGC'?'selected':''}>CEGC</option>
              <option value="HYPOTHEQUE" ${d.garantie_type==='HYPOTHEQUE'?'selected':''}>HYPOTHÈQUE</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Montant garantie (€)</label><input type="number" step="0.01" name="garantie_montant" id="${px}_gar_mt" class="form-control" value="${d.garantie_montant||0}" oninput="liveEndett('${px}')"></div>
        </div>

        <hr>
        <h6>ADE <span class="text-muted small">(coût total non inclus dans l'endettement)</span></h6>
        <div id="${px}_ade_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}','ade')"><i class="fas fa-plus"></i> Ajouter un assuré</button>

        <hr>
        <div class="row g-3">
          <div class="col-12">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="ptz_actif" id="${px}_ptz_cb" ${d.ptz_actif==1?'checked':''} onchange="togglePTZ('${px}');liveEndett('${px}')"><label class="form-check-label fw-semibold">PTZ</label></div>
            <div id="${px}_ptz_wrap" class="row g-2 mt-1 ms-3" style="${d.ptz_actif==1?'':'display:none'}">
              <div class="col-md-3"><label class="form-label small">Montant PTZ (€)</label><input type="number" step="0.01" name="ptz_montant" id="${px}_ptz_mt" class="form-control form-control-sm" value="${d.ptz_montant||0}" oninput="liveEndett('${px}')"></div>
              <div class="col-md-3"><label class="form-label small">Durée PTZ (mois)</label><input type="number" name="ptz_duree" id="${px}_ptz_dur" class="form-control form-control-sm" value="${d.ptz_duree||0}" oninput="liveEndett('${px}')"></div>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="ecoptz_actif" id="${px}_ecoptz_cb" ${d.ecoptz_actif==1?'checked':''} onchange="toggleEcoPTZ('${px}');liveEndett('${px}');updatePiecesEco('${px}')"><label class="form-check-label fw-semibold">EcoPTZ</label></div>
            <div id="${px}_ecoptz_wrap" class="ms-3 mt-1" style="${d.ecoptz_actif==1?'':'display:none'}">
              <div class="row g-2">
                <div class="col-md-3"><label class="form-label small">Montant EcoPTZ (€)</label><input type="number" step="0.01" name="ecoptz_montant" id="${px}_ecoptz_mt" class="form-control form-control-sm" value="${d.ecoptz_montant||0}" oninput="liveEndett('${px}')"></div>
                <div class="col-md-3"><label class="form-label small">Durée EcoPTZ (mois)</label><input type="number" name="ecoptz_duree" id="${px}_ecoptz_dur" class="form-control form-control-sm" value="${d.ecoptz_duree||0}" oninput="liveEndett('${px}')"></div>
                <div class="col-12">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="ecoptz_bouquets" id="${px}_eco_bq" ${d.ecoptz_bouquets==1?'checked':''} onchange="onEcoBouquet('${px}');updatePiecesEco('${px}')">
                    <label class="form-check-label small">Bouquets</label>
                  </div>
                  <div id="${px}_eco_bq_wrap" class="d-inline-block ms-2" style="${d.ecoptz_bouquets==1?'':'display:none'}">
                    <input type="number" min="1" name="ecoptz_nb_bouquets" id="${px}_eco_nb_bq" class="form-control form-control-sm d-inline-block" style="width:70px" value="${d.ecoptz_nb_bouquets||1}" oninput="updatePiecesEco('${px}')"> bouquet(s)
                  </div>
                  <div class="form-check form-check-inline ms-3">
                    <input class="form-check-input" type="checkbox" name="ecoptz_performance_globale" id="${px}_eco_pg" ${d.ecoptz_performance_globale==1?'checked':''} onchange="onEcoPerfGlobale('${px}')">
                    <label class="form-check-label small">Performance Globale</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div id="${px}_endett_live" class="mt-3"></div>
      </div>

      <!-- ── TAB 4 GESTION ADMIN ── -->
      <div class="tab-pane fade" id="${px}T4">
        <h6>ADE</h6>
        <div class="row g-3 mb-3">
          <div class="col-md-3"><label class="form-label">Envoyée le</label><input type="date" name="ade_envoyee_le" class="form-control" value="${d.ade_envoyee_le||''}"></div>
          <div class="col-md-3"><label class="form-label">Retour le</label><input type="date" name="ade_retour_le" class="form-control" value="${d.ade_retour_le||''}"></div>
          <div class="col-md-3"><label class="form-label">Réponse</label>
            <div class="d-flex gap-3 mt-2">
              <div class="form-check"><input class="form-check-input" type="radio" name="ade_reponse" value="ACCORD" ${d.ade_reponse==='ACCORD'?'checked':''}><label class="form-check-label">ACCORD</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="ade_reponse" value="REFUS" ${d.ade_reponse==='REFUS'?'checked':''}><label class="form-check-label">REFUS</label></div>
            </div>
          </div>
        </div>
        <div id="${px}_cegc_admin_wrap" style="${hasCEGC?'':'display:none'}">
          <h6>CEGC</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label">Envoyée le</label><input type="date" name="suivi_date_demande_cegc" class="form-control" value="${d.suivi_date_demande_cegc||''}"></div>
            <div class="col-md-3"><label class="form-label">Retour le</label><input type="date" name="suivi_date_retour_cegc" class="form-control" value="${d.suivi_date_retour_cegc||''}"></div>
            <div class="col-md-3"><label class="form-label">Réponse</label>
              <div class="d-flex gap-3 mt-2">
                <div class="form-check"><input class="form-check-input" type="radio" name="cegc_reponse_admin" value="ACCORD" ${d.suivi_cegc_accord==1?'checked':''}><label class="form-check-label">ACCORD</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="cegc_reponse_admin" value="REFUS" ${d.suivi_cegc_refus==1?'checked':''}><label class="form-check-label">REFUS</label></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Date de prélèvement</label><input type="date" name="date_prelevement" class="form-control" value="${d.date_prelevement||''}"></div>
          <div class="col-md-3"><label class="form-label">Nom du notaire</label><input type="text" name="notaire_nom" class="form-control" value="${escapeHtml(d.notaire_nom||'')}"></div>
          <div class="col-md-6"><label class="form-label">Adresse du notaire</label><textarea name="notaire_adresse" class="form-control" rows="2">${escapeHtml(d.notaire_adresse||'')}</textarea></div>
          <div class="col-md-3"><label class="form-label">Date de signature notaire (prévisionnelle)</label><input type="date" name="date_signature_notaire_prev" class="form-control" value="${d.date_signature_notaire_prev||''}"></div>
        </div>
      </div>

      <!-- ── TAB 5 PIÈCES ── -->
      <div class="tab-pane fade" id="${px}T5">
        <table class="table table-sm table-hover">
          <thead><tr><th>Document</th><th style="width:40px">Reçu</th><th style="width:160px">Date réception</th></tr></thead>
          <tbody>
            ${docRow(px,'doc_ji','Justificatif d\'identité',d)}
            ${docRow(px,'doc_jd','Justificatif de domicile',d)}
            ${docRow(px,'doc_ir','Justificatif de revenus',d)}
            ${docRow(px,'doc_contrat_travail','Contrat de travail',d)}
            ${docRow(px,'doc_bulletins_salaire','3 derniers bulletins de salaire',d)}
            ${docRow(px,'doc_justif_propriete','Justificatif de patrimoine immobilier',d)}
            ${docRow(px,'doc_releves_externes','3 derniers relevés de comptes externes',d)}
            ${docRow(px,'doc_epargnes_externes','Relevé d\'épargnes externes',d)}
            ${docRow(px,'doc_devis','Devis',d)}
          </tbody>
        </table>
        <div id="${px}_pieces_eco" style="${hasPTZorEco?'':'display:none'}">
          <h6 class="mt-3">PTZ / EcoPTZ</h6>
          <table class="table table-sm table-hover">
            <thead><tr><th>Document</th><th style="width:40px">Reçu</th><th style="width:160px">Date réception</th></tr></thead>
            <tbody>
              ${docRow(px,'eco_formulaire_emprunteur','Formulaire emprunteur',d)}
              <tr>
                <td><label class="form-check-label" id="${px}_ent_label">Formulaire entreprises${d.ecoptz_nb_bouquets>0?' ('+d.ecoptz_nb_bouquets+')':''}</label></td>
                <td><input class="form-check-input" type="checkbox" name="eco_formulaire_entreprises" ${d.eco_formulaire_entreprises==1?'checked':''}></td>
                <td><input type="date" name="eco_formulaire_entreprises_date" class="form-control form-control-sm" value="${d.eco_formulaire_entreprises_date||''}"></td>
              </tr>
              ${docRow(px,'eco_dpe','DPE',d)}
              ${docRow(px,'eco_audit','Audit',d)}
              ${docRow(px,'eco_ademe_emprunteur','ADEME Emprunteur',d)}
              ${docRow(px,'eco_ademe_entreprises','ADEME Entreprises',d)}
              ${docRow(px,'eco_devis_travaux','Devis travaux',d)}
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── TAB 6 SUIVI & SIGNATURE ── -->
      <div class="tab-pane fade" id="${px}T6">
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Date d'édition de la liasse (FSI)</label><input type="date" name="suivi_date_edition_liasse" class="form-control" value="${d.suivi_date_edition_liasse||''}"></div>
        </div>
        <h6 class="mt-3">Contrôle conformité</h6>
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Date d'envoi</label><input type="date" name="suivi_date_envoi_conformite" class="form-control" value="${d.suivi_date_envoi_conformite||''}"></div>
          <div class="col-md-3"><label class="form-label">Date de retour</label><input type="date" name="suivi_date_retour_conformite" class="form-control" value="${d.suivi_date_retour_conformite||''}"></div>
          <div class="col-md-3"><label class="form-label">Réponse</label>
            <div class="d-flex gap-3 mt-2">
              <div class="form-check"><input class="form-check-input" type="radio" name="suivi_conformite_reponse" value="CONFORME" id="${px}_conf_ok" ${d.suivi_conformite_reponse==='CONFORME'?'checked':''} onchange="toggleMotif('${px}')"><label class="form-check-label">CONFORME</label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="suivi_conformite_reponse" value="NON_CONFORME" id="${px}_conf_ko" ${d.suivi_conformite_reponse==='NON_CONFORME'?'checked':''} onchange="toggleMotif('${px}')"><label class="form-check-label">NON CONFORME</label></div>
            </div>
          </div>
          <div class="col-md-3" id="${px}_motif_wrap" style="${d.suivi_conformite_reponse==='NON_CONFORME'?'':'display:none'}">
            <label class="form-label">Motif de non-conformité</label>
            <input type="text" name="suivi_conformite_motif" class="form-control" value="${escapeHtml(d.suivi_conformite_motif||'')}">
          </div>
        </div>
        <h6 class="mt-3">Offres</h6>
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Date d'édition des offres</label><input type="date" name="suivi_date_edition_offres_dt" class="form-control" value="${d.suivi_date_edition_offres_dt||''}"></div>
          <div class="col-md-3"><label class="form-label">Date d'accusé de réception</label><input type="date" name="suivi_date_accuse_reception" id="${px}_accuse" class="form-control" value="${d.suivi_date_accuse_reception||''}" oninput="calcJ11live('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Date de signature possible <span class="text-muted small">(AR+11j)</span></label><input type="date" id="${px}_j11_display" class="form-control bg-light" readonly value="${d.suivi_date_j11||''}"><input type="hidden" name="suivi_date_j11" id="${px}_j11_hidden" value="${d.suivi_date_j11||''}"></div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-3"><label class="form-label">Date de signature définitive effective</label><input type="date" name="suivi_date_signature_definitive" class="form-control" value="${d.suivi_date_signature_definitive||''}"></div>
          <div class="col-md-3"><label class="form-label">Date de versement notaire</label><input type="date" name="suivi_date_versement_notaire" class="form-control" value="${d.suivi_date_versement_notaire||''}"></div>
          <div class="col-md-4"><label class="form-label">Statut du dossier</label>
            <select name="workflow_status" class="form-select">
              ${Object.entries(workflowLabels).map(([k,v])=>`<option value="${k}" ${(d.workflow_status||'etude')===k?'selected':''}>${v[0]}</option>`).join('')}
            </select>
          </div>
        </div>
      </div>

      <!-- ── TAB 7 NOTES ── -->
      <div class="tab-pane fade" id="${px}T7">
        ${d.id?`
        <div id="${px}_notes_list" class="mb-3"><div class="text-muted small">Chargement...</div></div>
        <div class="border rounded p-3 bg-light">
          <label class="form-label fw-semibold">Ajouter une note</label>
          <textarea id="${px}_note_input" class="form-control mb-2" rows="3" placeholder="Saisir une note..."></textarea>
          <input type="hidden" id="${px}_note_edit_id" value="0">
          <button type="button" class="btn btn-ce btn-sm" onclick="saveNote(${d.id},'${px}')"><i class="fas fa-save"></i> Enregistrer la note</button>
          <button type="button" class="btn btn-secondary btn-sm ms-2" id="${px}_note_cancel" style="display:none" onclick="cancelNoteEdit('${px}')">Annuler</button>
        </div>`:'<p class="text-muted">Sauvegardez d\'abord le dossier pour pouvoir ajouter des notes.</p>'}
      </div>
    </div>

    <div class="mt-3 pt-2 border-top">
      <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> ${d.id?'Enregistrer':'Créer le dossier'}</button>
    </div>
  </form>`;
}

function docRow(px,field,label,d){
  return `<tr>
    <td><label class="form-check-label">${label}</label></td>
    <td><input class="form-check-input" type="checkbox" name="${field}" ${d[field]==1?'checked':''}></td>
    <td><input type="date" name="${field}_date" class="form-control form-control-sm" value="${d[field+'_date']||''}"></td>
  </tr>`;
}

// ── FORM TOGGLES ──────────────────────────────────────────────────────────────
function toggleRL(px){
  const v=document.querySelector(`[name="usage_bien"]:checked`)?.value;
  const el=document.getElementById(px+'_rl_opts');
  if(el) el.style.display=(v==='RL')?'':'none';
}
function toggleFME(px){
  const cb=document.getElementById(px+'_fme_cb');
  const w=document.getElementById(px+'_fme_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
}
function togglePTZ(px){
  const cb=document.getElementById(px+'_ptz_cb');
  const w=document.getElementById(px+'_ptz_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
  updatePiecesEco(px);
}
function toggleEcoPTZ(px){
  const cb=document.getElementById(px+'_ecoptz_cb');
  const w=document.getElementById(px+'_ecoptz_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
  updatePiecesEco(px);
}
function onGarantieChange(px){
  const v=document.getElementById(px+'_gar_type')?.value;
  const w=document.getElementById(px+'_cegc_admin_wrap');
  if(w) w.style.display=(v==='CEGC')?'':'none';
}
function onEcoBouquet(px){
  const cb=document.getElementById(px+'_eco_bq');
  const pg=document.getElementById(px+'_eco_pg');
  const wrap=document.getElementById(px+'_eco_bq_wrap');
  if(cb?.checked){if(pg)pg.checked=false;if(pg)pg.disabled=true;}
  else{if(pg)pg.disabled=false;}
  if(wrap) wrap.style.display=cb?.checked?'inline-block':'none';
}
function onEcoPerfGlobale(px){
  const pg=document.getElementById(px+'_eco_pg');
  const bq=document.getElementById(px+'_eco_bq');
  const wrap=document.getElementById(px+'_eco_bq_wrap');
  if(pg?.checked){if(bq)bq.checked=false;if(bq)bq.disabled=true;if(wrap)wrap.style.display='none';}
  else{if(bq)bq.disabled=false;}
}
function toggleMotif(px){
  const ko=document.getElementById(px+'_conf_ko');
  const w=document.getElementById(px+'_motif_wrap');
  if(w) w.style.display=ko?.checked?'':'none';
}
function updatePiecesEco(px){
  const ptz=document.getElementById(px+'_ptz_cb')?.checked;
  const eco=document.getElementById(px+'_ecoptz_cb')?.checked;
  const w=document.getElementById(px+'_pieces_eco');
  if(w) w.style.display=(ptz||eco)?'':'none';
  const nb=parseInt(document.getElementById(px+'_eco_nb_bq')?.value||0);
  const lbl=document.getElementById(px+'_ent_label');
  if(lbl) lbl.textContent='Formulaire entreprises'+(nb>0?' ('+nb+')':'');
}
function calcJ11live(px){
  const v=document.getElementById(px+'_accuse')?.value;
  const j11=addJ11(v);
  const disp=document.getElementById(px+'_j11_display');
  const hid=document.getElementById(px+'_j11_hidden');
  if(disp) disp.value=j11;
  if(hid) hid.value=j11;
}
function beforeSubmit(px){
  ['revenus','charges','epargne','ade'].forEach(t=>serializeJson(px,t));
  // Map cegc_reponse_admin → suivi_cegc_accord/refus hidden fields if needed
  const form=document.querySelector(`[name="id"][value="${document.querySelector('[name=id]')?.value}"]`)?.closest('form')
    || document.getElementById(px+'T4')?.closest('form');
  if(!form) return;
  const r=form.querySelector('[name="cegc_reponse_admin"]:checked');
  if(r){
    let acc=form.querySelector('[name="suivi_cegc_accord_h"]');
    let ref=form.querySelector('[name="suivi_cegc_refus_h"]');
    if(!acc){acc=document.createElement('input');acc.type='hidden';acc.name='suivi_cegc_accord';form.appendChild(acc);}
    if(!ref){ref=document.createElement('input');ref.type='hidden';ref.name='suivi_cegc_refus';form.appendChild(ref);}
    acc.value=r.value==='ACCORD'?1:0;
    ref.value=r.value==='REFUS'?1:0;
  }
}

// ── OPEN ADD / EDIT ───────────────────────────────────────────────────────────
function openAddModal(){
  document.getElementById('addContent').innerHTML=buildFormTabs('add',{});
  loadJsonRows('add',{});
  liveEndett('add');
  new bootstrap.Modal(document.getElementById('addModal')).show();
}
function editDossier(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  document.getElementById('editContent').innerHTML=buildFormTabs('edit',d);
  loadJsonRows('edit',d);
  liveEndett('edit');
  new bootstrap.Modal(document.getElementById('editModal')).show();
  loadNotes(id,'edit');
}

// ── WORKFLOW PROGRESS ─────────────────────────────────────────────────────────
function buildWorkflowProgress(status){
  const idx=workflowSteps.indexOf(status);
  if(status==='refuse') return '<div class="alert alert-danger text-center mb-0"><i class="fas fa-times-circle"></i> Dossier refusé</div>';
  let h='<div class="d-flex justify-content-between align-items-center" style="font-size:.72rem;overflow-x:auto">';
  workflowSteps.forEach((step,i)=>{
    const active=i<=idx, current=i===idx;
    const col=active?(current?'var(--ce-primary)':'#28a745'):'#dee2e6';
    h+=`<div class="text-center" style="min-width:60px">
      <div style="width:22px;height:22px;border-radius:50%;background:${col};color:#fff;margin:0 auto 2px;line-height:22px;font-size:.65rem">${active?'✓':(i+1)}</div>
      <div style="color:${current?'var(--ce-primary)':'#888'};font-weight:${current?'bold':'normal'}">${(workflowLabels[step]||[step])[0]}</div>
    </div>`;
    if(i<workflowSteps.length-1) h+=`<div style="flex:1;height:2px;background:${i<idx?'#28a745':'#dee2e6'};margin-top:-10px"></div>`;
  });
  return h+'</div>';
}

// ── SHOW DETAIL ───────────────────────────────────────────────────────────────
function showDetail(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const totalF=getTotalFinancement(d);
  const capPH=getCapitalPH(d);
  const mensPH=calcMensualite(capPH,parseFloat(d.taux_emprunt||0),parseInt(d.duree_emprunt||0));
  const mensPTZ=getMensPTZ(d);
  const mensEco=getMensEcoPTZ(d);
  const mensG=mensPH+mensPTZ+mensEco;
  const te=calcTauxEndett(d);
  const rev=calcRevenus(d.revenus_json);
  const charges=calcChargesConservees(d.charges_json);
  const revenus=parseJ(d.revenus_json);
  const chargesArr=parseJ(d.charges_json);
  const epargne=parseJ(d.epargne_json);
  const ade=parseJ(d.ade_json);
  const wf=d.workflow_status||'etude';
  const occMap={PROPRIETAIRE:'Propriétaire',LOCATAIRE_HLM:'Locataire HLM',AUTRE_LOCATAIRE:'Autre locataire',LOGE_GRATUIT:'Logé à titre gratuit'};

  let html=`<div class="mb-3">${buildWorkflowProgress(wf)}</div>${alertEndett(te)}`;
  html+=`<ul class="nav nav-tabs mb-3"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#dT1">Client</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT2">Projet</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT3">Financement</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT4">Gestion admin</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT5">Pièces</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT6">Suivi</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT7" onclick="loadNotes(${id},'detail')">Notes</a></li>
  </ul><div class="tab-content">`;

  // Tab Client
  const bdge=(v)=>v?`<span class="badge ${v==='OK'?'bg-success':'bg-danger'}">${v}</span>`:'—';
  html+=`<div class="tab-pane fade show active" id="dT1"><div class="row g-3">
    <div class="col-md-6"><table class="table table-sm"><tbody>
      <tr><td>N° personne</td><td><strong>${escapeHtml(d.numero_personne)}</strong></td></tr>
      <tr><td>Type client</td><td>${escapeHtml(d.type_client)}</td></tr>
      <tr><td>Banque de France</td><td>${bdge(d.banque_de_france)}</td></tr>
      <tr><td>DRC</td><td>${bdge(d.drc)}</td></tr>
      <tr><td>TopCC</td><td>${bdge(d.topcc)}</td></tr>
      <tr><td>Primo accédant</td><td>${d.primo_accedant==1?'OUI':d.primo_accedant===0||d.primo_accedant==='0'?'NON':'—'}</td></tr>
      <tr><td>Statut occupation</td><td>${occMap[d.statut_occupation]||d.statut_occupation||'—'}</td></tr>
      <tr><td>Personnes foyer / Enfants</td><td>${d.nb_personnes_foyer??'—'} / ${d.nb_enfants??'—'}</td></tr>
    </tbody></table></div>
    <div class="col-md-6">
      <h6>Revenus</h6>
      ${revenus.length?`<table class="table table-sm table-striped"><thead><tr><th>Intitulé</th><th>Montant</th><th>Futur</th><th>Pondération</th></tr></thead><tbody>
        ${revenus.map(r=>`<tr><td>${escapeHtml(r.intitule)}</td><td>${fmt(r.montant)} €${r.periodicite==='annuelle'?' /an':' /mois'}</td><td>${r.revenu_futur?'Oui':''}</td><td>${r.revenu_futur?(r.ponderation||100)+'%':''}</td></tr>`).join('')}
      </tbody></table>`:'<p class="text-muted small">Aucun revenu renseigné</p>'}
      <h6 class="mt-2">Charges</h6>
      ${chargesArr.length?`<table class="table table-sm table-striped"><thead><tr><th>Intitulé</th><th>Montant</th><th>Non conservé</th></tr></thead><tbody>
        ${chargesArr.map(c=>`<tr class="${c.non_conserve?'text-muted':''}" ><td>${escapeHtml(c.intitule)}</td><td>${fmt(c.montant)} €</td><td>${c.non_conserve?'<span class="badge bg-secondary">Exclu</span>':''}</td></tr>`).join('')}
      </tbody></table>`:'<p class="text-muted small">Aucune charge renseignée</p>'}
      <h6 class="mt-2">Épargne</h6>
      ${epargne.length?`<table class="table table-sm table-striped"><thead><tr><th>Intitulé</th><th>Montant</th><th>Banque</th></tr></thead><tbody>
        ${epargne.map(e=>`<tr><td>${escapeHtml(e.intitule)}</td><td>${fmt(e.montant)} €</td><td>${e.hors_cemp?escapeHtml(e.nom_banque||'—'):''}</td></tr>`).join('')}
      </tbody></table>`:'<p class="text-muted small">Aucune épargne renseignée</p>'}
    </div>
  </div></div>`;

  // Tab Projet
  const ubMap={RP:'Résidence Principale',RS:'Résidence Secondaire',RL:'Locatif'};
  html+=`<div class="tab-pane fade" id="dT2"><table class="table table-sm"><tbody>
    <tr><td>Usage du bien</td><td>${ubMap[d.usage_bien]||d.usage_bien||'—'}${d.usage_bien==='RL'&&d.usage_rl_type?' – '+(d.usage_rl_type==='RL_PRINCIPALE'?'RL Principale':'RL Secondaire'):''}</td></tr>
    <tr><td>Mode d'occupation</td><td>${d.mode_occupation||'—'}</td></tr>
  </tbody></table></div>`;

  // Tab Financement
  html+=`<div class="tab-pane fade" id="dT3">
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(totalF)} €</div><div class="stat-label">Total financement</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(capPH)} €</div><div class="stat-label">Capital PH</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mensG)} €</div><div class="stat-label">Mensualité globale</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${badgeEndett(te)}</div><div class="stat-label">Taux endettement</div></div></div>
    </div>
    <div class="row g-3">
    <div class="col-md-6"><table class="table table-sm"><tbody>
      <tr><td>Taux / Durée</td><td>${d.taux_emprunt}% / ${d.duree_emprunt} mois</td></tr>
      <tr><td>Montant acquisition</td><td>${fmt(d.montant_acquisition)} €</td></tr>
      <tr><td>Dont mobilier financable</td><td>${fmt(d.dont_mobilier_financable)} €</td></tr>
      <tr><td>Frais de notaire</td><td>${fmt(d.frais_notaire)} €</td></tr>
      <tr><td>Frais de dossier</td><td>${fmt(d.frais_dossier)} €</td></tr>
      ${d.frais_midi_epargne==1?`<tr><td>Frais Midi Épargne</td><td>${fmt(d.montant_midi_epargne)} €</td></tr>`:''}
      <tr><td>Frais de négociation</td><td>${fmt(d.frais_negociation)} €</td></tr>
      <tr><td>Frais divers</td><td>${fmt(d.frais_divers)} €</td></tr>
      <tr><td>Frais d'agence</td><td>${fmt(d.frais_agence)} €</td></tr>
      <tr><td>TVA financée</td><td>${fmt(d.tva_financee)} €</td></tr>
      <tr><td>Apport</td><td>${fmt(d.apport)} €</td></tr>
      <tr><td>Garantie (${d.garantie_type||'—'})</td><td>${fmt(d.garantie_montant)} €</td></tr>
    </tbody></table></div>
    <div class="col-md-6">
      <h6>ADE</h6>
      ${ade.length?ade.map((a,i)=>`<div class="card mb-2"><div class="card-body p-2">
        <strong>Assuré ${i+1}</strong><br>
        Couverture: ${(a.couverture||[]).join(', ')||'—'} | Quotité: ${a.quotite||0}%<br>
        Type: ${a.type||'—'} | Franchise: ${a.franchise||'—'} | IPP: ${a.ipp||'—'}<br>
        Coût total: <strong>${fmt(a.cout_total)} €</strong>
      </div></div>`).join(''):'<p class="text-muted small">Aucune ADE</p>'}
      ${d.ptz_actif==1?`<div class="alert alert-info py-1 mt-2">PTZ : ${fmt(d.ptz_montant)} € / ${d.ptz_duree} mois → mensualité : ${fmt(getMensPTZ(d))} €</div>`:''}
      ${d.ecoptz_actif==1?`<div class="alert alert-info py-1">EcoPTZ : ${fmt(d.ecoptz_montant)} € / ${d.ecoptz_duree} mois → mensualité : ${fmt(getMensEcoPTZ(d))} €</div>`:''}
    </div></div>
    ${alertEndett(te)}
  </div>`;

  // Tab Gestion Admin
  const rep=(v,ok,ko)=>v===ok?`<span class="badge bg-success">${ok}</span>`:v===ko?`<span class="badge bg-danger">${ko}</span>`:'—';
  html+=`<div class="tab-pane fade" id="dT4"><table class="table table-sm"><tbody>
    <tr><td colspan="2" class="fw-bold bg-light">ADE</td></tr>
    <tr><td>Envoyée le</td><td>${fmtD(d.ade_envoyee_le)}</td></tr>
    <tr><td>Retour le</td><td>${fmtD(d.ade_retour_le)}</td></tr>
    <tr><td>Réponse</td><td>${rep(d.ade_reponse,'ACCORD','REFUS')}</td></tr>
    ${d.garantie_type==='CEGC'?`
    <tr><td colspan="2" class="fw-bold bg-light">CEGC</td></tr>
    <tr><td>Envoyée le</td><td>${fmtD(d.suivi_date_demande_cegc)}</td></tr>
    <tr><td>Retour le</td><td>${fmtD(d.suivi_date_retour_cegc)}</td></tr>
    <tr><td>Réponse</td><td>${d.suivi_cegc_accord==1?'<span class="badge bg-success">ACCORD</span>':d.suivi_cegc_refus==1?'<span class="badge bg-danger">REFUS</span>':'—'}</td></tr>`:''}
    <tr><td>Date de prélèvement</td><td>${fmtD(d.date_prelevement)}</td></tr>
    <tr><td>Notaire</td><td>${escapeHtml(d.notaire_nom||'—')}</td></tr>
    <tr><td>Adresse notaire</td><td>${escapeHtml(d.notaire_adresse||'—')}</td></tr>
    <tr><td>Date signature notaire (prévis.)</td><td>${fmtD(d.date_signature_notaire_prev)}</td></tr>
  </tbody></table></div>`;

  // Tab Pièces
  const hasPTZorEco=d.ptz_actif==1||d.ecoptz_actif==1;
  const drow=(field,label)=>`<tr><td>${label}</td><td>${chk(d[field])}</td><td>${fmtD(d[field+'_date'])}</td></tr>`;
  html+=`<div class="tab-pane fade" id="dT5">
    <table class="table table-sm"><thead><tr><th>Document</th><th>Reçu</th><th>Date</th></tr></thead><tbody>
      ${drow('doc_ji',"Justificatif d'identité")}
      ${drow('doc_jd','Justificatif de domicile')}
      ${drow('doc_ir','Justificatif de revenus')}
      ${drow('doc_contrat_travail','Contrat de travail')}
      ${drow('doc_bulletins_salaire','3 derniers bulletins de salaire')}
      ${drow('doc_justif_propriete','Justificatif de patrimoine immobilier')}
      ${drow('doc_releves_externes','3 derniers relevés de comptes externes')}
      ${drow('doc_epargnes_externes',"Relevé d'épargnes externes")}
      ${drow('doc_devis','Devis')}
    </tbody></table>
    ${hasPTZorEco?`<h6>PTZ / EcoPTZ</h6>
    <table class="table table-sm"><thead><tr><th>Document</th><th>Reçu</th><th>Date</th></tr></thead><tbody>
      ${drow('eco_formulaire_emprunteur','Formulaire emprunteur')}
      <tr><td>Formulaire entreprises${d.ecoptz_nb_bouquets>0?' ('+d.ecoptz_nb_bouquets+')':''}</td><td>${chk(d.eco_formulaire_entreprises)}</td><td>${fmtD(d.eco_formulaire_entreprises_date)}</td></tr>
      ${drow('eco_dpe','DPE')}
      ${drow('eco_audit','Audit')}
      ${drow('eco_ademe_emprunteur','ADEME Emprunteur')}
      ${drow('eco_ademe_entreprises','ADEME Entreprises')}
      ${drow('eco_devis_travaux','Devis travaux')}
    </tbody></table>`:''}
  </div>`;

  // Tab Suivi
  const confR=d.suivi_conformite_reponse;
  html+=`<div class="tab-pane fade" id="dT6"><table class="table table-sm"><tbody>
    <tr><td>Édition liasse (FSI)</td><td>${fmtD(d.suivi_date_edition_liasse)}</td></tr>
    <tr><td colspan="2" class="fw-bold bg-light">Contrôle conformité</td></tr>
    <tr><td>Envoi</td><td>${fmtD(d.suivi_date_envoi_conformite)}</td></tr>
    <tr><td>Retour</td><td>${fmtD(d.suivi_date_retour_conformite)}</td></tr>
    <tr><td>Réponse</td><td>${confR==='CONFORME'?'<span class="badge bg-success">CONFORME</span>':confR==='NON_CONFORME'?'<span class="badge bg-danger">NON CONFORME</span>':'—'}</td></tr>
    ${d.suivi_conformite_motif?`<tr><td>Motif</td><td>${escapeHtml(d.suivi_conformite_motif)}</td></tr>`:''}
    <tr><td colspan="2" class="fw-bold bg-light">Offres</td></tr>
    <tr><td>Édition offres</td><td>${fmtD(d.suivi_date_edition_offres_dt)}</td></tr>
    <tr><td>Accusé de réception</td><td>${fmtD(d.suivi_date_accuse_reception)}</td></tr>
    <tr><td>Date de signature possible (AR+11j)</td><td>${fmtD(d.suivi_date_j11)}</td></tr>
    <tr><td>Signature définitive effective</td><td>${fmtD(d.suivi_date_signature_definitive)}</td></tr>
    <tr><td>Versement notaire</td><td>${fmtD(d.suivi_date_versement_notaire)}</td></tr>
  </tbody></table></div>`;

  // Tab Notes
  html+=`<div class="tab-pane fade" id="dT7"><div id="detail_notes_list">Chargement...</div></div>`;
  html+=`</div>`;

  document.getElementById('detailContent').innerHTML=html;
  new bootstrap.Modal(document.getElementById('detailModal')).show();
  loadNotes(id,'detail');
}

// ── NOTES AJAX ────────────────────────────────────────────────────────────────
function loadNotes(dossierId,px){
  const listId=px==='detail'?'detail_notes_list':(px+'_notes_list');
  const el=document.getElementById(listId);
  if(!el) return;
  fetch('index.php?ajax=notes&dossier_id='+dossierId)
    .then(r=>r.json()).then(notes=>{
      if(!notes.length){el.innerHTML='<p class="text-muted small">Aucune note.</p>';return;}
      el.innerHTML=notes.map(n=>`
        <div class="note-card" id="note_${n.id}">
          <div class="note-meta">${escapeHtml(n.conseiller)} — ${fmtD(n.created_at)}${n.updated_at!==n.created_at?' (modifié)':''}</div>
          <div class="note-text">${escapeHtml(n.note).replace(/\n/g,'<br>')}</div>
          ${px!=='detail'?`<div class="mt-1">
            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="editNote(${n.id},'${n.note.replace(/'/g,"\\'")}','${px}')"><i class="fas fa-pen"></i></button>
            <button type="button" class="btn btn-xs btn-outline-danger btn-sm" onclick="deleteNote(${n.id},${dossierId},'${px}')"><i class="fas fa-trash"></i></button>
          </div>`:''}
        </div>`).join('');
    }).catch(()=>{if(el)el.innerHTML='<p class="text-danger small">Erreur de chargement</p>';});
}
function saveNote(dossierId,px){
  const ta=document.getElementById(px+'_note_input');
  const nid=document.getElementById(px+'_note_edit_id')?.value||0;
  const txt=ta?.value?.trim();
  if(!txt) return;
  const fd=new FormData();
  fd.append('ajax_action','save_note');
  fd.append('dossier_id',dossierId);
  fd.append('note_id',nid);
  fd.append('note',txt);
  fetch('index.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{
      if(res.success){ta.value='';cancelNoteEdit(px);loadNotes(dossierId,px);}
    });
}
function editNote(nid,text,px){
  const ta=document.getElementById(px+'_note_input');
  const hidId=document.getElementById(px+'_note_edit_id');
  const btn=document.getElementById(px+'_note_cancel');
  if(ta){ta.value=text;ta.focus();}
  if(hidId) hidId.value=nid;
  if(btn) btn.style.display='';
}
function cancelNoteEdit(px){
  const ta=document.getElementById(px+'_note_input');
  const hidId=document.getElementById(px+'_note_edit_id');
  const btn=document.getElementById(px+'_note_cancel');
  if(ta) ta.value='';
  if(hidId) hidId.value='0';
  if(btn) btn.style.display='none';
}
function deleteNote(nid,dossierId,px){
  if(!confirm('Supprimer cette note ?')) return;
  const fd=new FormData();
  fd.append('ajax_action','delete_note');
  fd.append('note_id',nid);
  fetch('index.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{if(res.success) loadNotes(dossierId,px);});
}

// ── AMORTISSEMENT ─────────────────────────────────────────────────────────────
function showAmortissement(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const capital=getCapitalPH(d);
  const taux=parseFloat(d.taux_emprunt||0)/100;
  const tm=taux/12;
  const duree=parseInt(d.duree_emprunt||0);
  if(duree<=0||capital<=0){
    document.getElementById('amortContent').innerHTML='<div class="alert alert-warning">Données insuffisantes.</div>';
    new bootstrap.Modal(document.getElementById('amortModal')).show();return;
  }
  const mens=tm>0?capital*tm/(1-Math.pow(1+tm,-duree)):capital/duree;
  let solde=capital,rows='',ti=0,tc=0;
  for(let m=1;m<=duree;m++){
    const int=solde*tm;
    const cap=mens-int;
    solde=Math.max(0,solde-cap);
    ti+=int;tc+=cap;
    rows+=`<tr><td>${m}</td><td>${fmt(mens)}</td><td>${fmt(cap)}</td><td>${fmt(int)}</td><td>${fmt(solde)}</td></tr>`;
  }
  document.getElementById('amortContent').innerHTML=`
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(capital)} €</div><div class="stat-label">Capital PH emprunté</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mens)} €</div><div class="stat-label">Mensualité PH</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(ti)} €</div><div class="stat-label">Coût total intérêts</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mens*duree)} €</div><div class="stat-label">Coût total crédit</div></div></div>
    </div>
    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
      <table class="table table-sm table-striped">
        <thead class="table-dark" style="position:sticky;top:0"><tr><th>Mois</th><th>Mensualité</th><th>Capital</th><th>Intérêts</th><th>Solde restant</th></tr></thead>
        <tbody>${rows}</tbody>
        <tfoot class="table-secondary"><tr><td><strong>Total</strong></td><td><strong>${fmt(mens*duree)}</strong></td><td><strong>${fmt(tc)}</strong></td><td><strong>${fmt(ti)}</strong></td><td>—</td></tr></tfoot>
      </table>
    </div>`;
  new bootstrap.Modal(document.getElementById('amortModal')).show();
}

// ── SIMULATION ────────────────────────────────────────────────────────────────
function showSimulation(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const cap=getCapitalPH(d);
  document.getElementById('simulContent').innerHTML=`
    <h6>${escapeHtml(d.numero_personne)} — Capital PH : ${fmt(cap)} €</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">Taux (%)</label>
        <input type="range" class="form-range" id="simTaux" min="0" max="8" step="0.1" value="${d.taux_emprunt||0}" oninput="updateSim(${id})">
        <div class="text-center fw-bold" id="simTauxVal">${d.taux_emprunt||0}%</div></div>
      <div class="col-md-4"><label class="form-label">Durée (mois)</label>
        <input type="range" class="form-range" id="simDuree" min="60" max="360" step="12" value="${d.duree_emprunt||0}" oninput="updateSim(${id})">
        <div class="text-center fw-bold" id="simDureeVal">${d.duree_emprunt||0} mois</div></div>
      <div class="col-md-4"><label class="form-label">Remboursement anticipé (€)</label>
        <input type="number" class="form-control" id="simRa" value="0" step="1000" oninput="updateSim(${id})"></div>
    </div>
    <div id="simResults"></div><hr>
    <h6>Sensibilité au taux</h6><div id="simTable"></div>`;
  updateSim(id);
  new bootstrap.Modal(document.getElementById('simulModal')).show();
}
function updateSim(id){
  const d=dossiersData.find(x=>x.id==id);
  const cap=getCapitalPH(d)-parseFloat(document.getElementById('simRa')?.value||0);
  const taux=parseFloat(document.getElementById('simTaux')?.value||0);
  const duree=parseInt(document.getElementById('simDuree')?.value||0);
  document.getElementById('simTauxVal').textContent=taux+'%';
  document.getElementById('simDureeVal').textContent=duree+' mois ('+Math.round(duree/12)+' ans)';
  const mens=calcMensualite(Math.max(0,cap),taux,duree);
  const fakeD={...d,taux_emprunt:taux,duree_emprunt:duree};
  const te=calcTauxEndett({...fakeD,revenus_json:d.revenus_json,charges_json:d.charges_json});
  const origMens=calcMensualite(getCapitalPH(d),parseFloat(d.taux_emprunt||0),parseInt(d.duree_emprunt||0));
  const diff=mens-origMens;
  document.getElementById('simResults').innerHTML=`
    <div class="row g-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mens)} €</div><div class="stat-label">Mensualité PH</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(mens*duree-Math.max(0,cap))} €</div><div class="stat-label">Total intérêts</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${badgeEndett(te)}</div><div class="stat-label">Endettement</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number" style="color:${diff>0?'#dc3545':'#28a745'}">${diff>0?'+':''}${fmt(diff)} €</div><div class="stat-label">Diff. mensualité</div></div></div>
    </div>${alertEndett(te)}`;
  let rows='';
  for(let t=taux;t<=taux+2;t+=0.5){
    const m=calcMensualite(Math.max(0,cap),t,duree);
    const te2=calcTauxEndett({...fakeD,taux_emprunt:t});
    rows+=`<tr><td>${t.toFixed(1)}%</td><td>${fmt(m)} €</td><td>${fmt(m*duree)} €</td><td>${badgeEndett(te2)}</td></tr>`;
  }
  document.getElementById('simTable').innerHTML=`<table class="table table-sm table-striped"><thead><tr><th>Taux</th><th>Mensualité</th><th>Coût total</th><th>Endettement</th></tr></thead><tbody>${rows}</tbody></table>`;
}

// ── COMPARAISON ───────────────────────────────────────────────────────────────
function showCompareSelect(){
  if(dossiersData.length<2){alert('Il faut au moins 2 dossiers.');return;}
  document.getElementById('compareContent').innerHTML=`
    <p>Sélectionnez les dossiers à comparer :</p>
    ${dossiersData.map(d=>`<div class="form-check"><input class="form-check-input compare-chk" type="checkbox" value="${d.id}"><label class="form-check-label">${escapeHtml(d.numero_personne)} — ${fmt(d.montant_acquisition)} € @ ${d.taux_emprunt}% / ${d.duree_emprunt} mois</label></div>`).join('')}
    <button class="btn btn-ce mt-3" onclick="runComparison()"><i class="fas fa-balance-scale"></i> Comparer</button>
    <div id="compareResults" class="mt-3"></div>`;
  new bootstrap.Modal(document.getElementById('compareModal')).show();
}
function runComparison(){
  const ids=Array.from(document.querySelectorAll('.compare-chk:checked')).map(c=>parseInt(c.value));
  if(ids.length<2){alert('Sélectionnez au moins 2 dossiers.');return;}
  const ds=ids.map(id=>dossiersData.find(d=>d.id==id)).filter(Boolean);
  const headers='<th>Critère</th>'+ds.map(d=>`<th>${escapeHtml(d.numero_personne)}</th>`).join('');
  function row(label,vals,hi){
    const nums=vals.map(v=>typeof v==='number'?v:0);
    const best=hi==='min'?Math.min(...nums):hi==='max'?Math.max(...nums):null;
    return `<tr><td><strong>${label}</strong></td>${vals.map((v,i)=>`<td${best!==null&&nums[i]===best?' class="table-success"':''}>${typeof v==='number'?fmt(v)+' €':v}</td>`).join('')}</tr>`;
  }
  const data=ds.map(d=>({
    cap:getCapitalPH(d),mens:getMensGlobale(d),
    ti:calcMensualite(getCapitalPH(d),parseFloat(d.taux_emprunt||0),parseInt(d.duree_emprunt||0))*parseInt(d.duree_emprunt||0)-getCapitalPH(d),
    te:calcTauxEndett(d)
  }));
  document.getElementById('compareResults').innerHTML=`
    <table class="table table-sm table-bordered">
      <thead class="table-dark"><tr>${headers}</tr></thead>
      <tbody>
        ${row('Capital PH',data.map(r=>r.cap),null)}
        <tr><td><strong>Taux</strong></td>${ds.map(d=>`<td>${d.taux_emprunt}%</td>`).join('')}</tr>
        <tr><td><strong>Durée</strong></td>${ds.map(d=>`<td>${d.duree_emprunt} mois</td>`).join('')}</tr>
        ${row('Mensualité globale',data.map(r=>r.mens),'min')}
        ${row('Total intérêts PH',data.map(r=>r.ti),'min')}
        <tr><td><strong>Endettement</strong></td>${data.map(r=>`<td>${badgeEndett(r.te)}</td>`).join('')}</tr>
      </tbody>
    </table>`;
}

// ── PRINT ─────────────────────────────────────────────────────────────────────
function printDossier(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const totalF=getTotalFinancement(d);
  const capPH=getCapitalPH(d);
  const mensPH=calcMensualite(capPH,parseFloat(d.taux_emprunt||0),parseInt(d.duree_emprunt||0));
  const mensPTZ=getMensPTZ(d);
  const mensEco=getMensEcoPTZ(d);
  const mensG=mensPH+mensPTZ+mensEco;
  const te=calcTauxEndett(d);
  const rev=calcRevenus(d.revenus_json);
  const charges=calcChargesConservees(d.charges_json);
  const revenus=parseJ(d.revenus_json);
  const chargesArr=parseJ(d.charges_json);
  const epargne=parseJ(d.epargne_json);
  const ade=parseJ(d.ade_json);
  const wfLabel=(workflowLabels[d.workflow_status]||['—'])[0];
  const teClass=te===null?'pr-kpi-ok':te<=33?'pr-kpi-ok':te<=35?'pr-kpi-warn':'pr-kpi-danger';
  const p=(l,v)=>`<tr><td class="lbl">${l}</td><td class="val">${v}</td></tr>`;
  const occMap={PROPRIETAIRE:'Propriétaire',LOCATAIRE_HLM:'Locataire HLM',AUTRE_LOCATAIRE:'Autre locataire',LOGE_GRATUIT:'Logé à titre gratuit'};

  document.getElementById('printArea').innerHTML=`<div class="pr-wrap">
  <div class="pr-header">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRKmky9-XoScC_uRBERr-pjPJuedYPHmyGh5w&s" class="pr-logo" alt="">
    <div class="pr-header-center"><div class="pr-title">SYNTHÈSE CRÉDIT IMMOBILIER</div><div class="pr-subtitle">Dossier N° <strong>${escapeHtml(d.numero_personne)}</strong></div></div>
    <div class="pr-header-right"><div class="pr-date">${new Date().toLocaleDateString('fr-FR')}</div><div class="pr-statut">${wfLabel}</div></div>
  </div>
  <div class="pr-cols">
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">CLIENT</div><table><tbody>
      ${p('N° personne',escapeHtml(d.numero_personne))}
      ${p('Type client',escapeHtml(d.type_client)||'—')}
      ${p('BdF / DRC / TopCC',(d.banque_de_france||'—')+' / '+(d.drc||'—')+' / '+(d.topcc||'—'))}
      ${p('Primo accédant',d.primo_accedant==1?'OUI':d.primo_accedant===0||d.primo_accedant==='0'?'NON':'—')}
      ${p('Statut occupation',occMap[d.statut_occupation]||d.statut_occupation||'—')}
      ${p('Foyer / Enfants',(d.nb_personnes_foyer??'—')+' / '+(d.nb_enfants??'—'))}
      ${p('Usage du bien',(d.usage_bien||'—')+(d.usage_rl_type?' – '+d.usage_rl_type:''))}
      ${p('Mode occupation',d.mode_occupation||'—')}
    </tbody></table></div></div>
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">REVENUS / CHARGES</div>
      <table><tbody>
        ${revenus.map(r=>`${p(escapeHtml(r.intitule)+(r.revenu_futur?' (futur, '+(r.ponderation||100)+'%)':''),fmt(r.montant)+' €'+(r.periodicite==='annuelle'?' /an':' /mois'))}`).join('')}
        <tr><td class="pr-sub-title" colspan="2">Charges conservées</td></tr>
        ${chargesArr.filter(c=>!c.non_conserve).map(c=>`${p(escapeHtml(c.intitule),fmt(c.montant)+' €')}`).join('')}
        ${chargesArr.filter(c=>c.non_conserve).length?`<tr><td class="lbl text-muted" colspan="2">${chargesArr.filter(c=>c.non_conserve).length} charge(s) non conservée(s)</td></tr>`:''}
      </tbody></table>
      <div class="pr-kpi-row">
        <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${fmt(rev)} €</div><div class="pr-kpi-lbl">Revenus effectifs/mois</div></div>
        <div class="pr-kpi ${teClass}"><div class="pr-kpi-val">${te!==null?parseFloat(te).toFixed(1)+'%':'N/A'}</div><div class="pr-kpi-lbl">Taux d'endettement</div></div>
      </div>
    </div></div>
  </div>
  <div class="pr-cols">
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">PLAN DE FINANCEMENT</div><table><tbody>
      ${p('Montant acquisition',fmt(d.montant_acquisition)+' €')}
      ${d.dont_mobilier_financable>0?p('Dont mobilier financable',fmt(d.dont_mobilier_financable)+' €'):''}
      ${p('Frais de notaire',fmt(d.frais_notaire)+' €')}
      ${p('Frais de dossier',fmt(d.frais_dossier)+' €')}
      ${d.frais_midi_epargne==1?p('Frais Midi Épargne',fmt(d.montant_midi_epargne)+' €'):''}
      ${parseFloat(d.frais_negociation)>0?p('Frais de négociation',fmt(d.frais_negociation)+' €'):''}
      ${parseFloat(d.frais_divers)>0?p('Frais divers',fmt(d.frais_divers)+' €'):''}
      ${parseFloat(d.frais_agence)>0?p("Frais d'agence",fmt(d.frais_agence)+' €'):''}
      ${parseFloat(d.tva_financee)>0?p('TVA financée',fmt(d.tva_financee)+' €'):''}
      ${p('Garantie '+(d.garantie_type||'—'),fmt(d.garantie_montant)+' €')}
      ${p('Apport',fmt(d.apport)+' €')}
    </tbody></table>
    <div class="pr-kpi-row">
      <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${fmt(totalF)} €</div><div class="pr-kpi-lbl">Total à financer</div></div>
      <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${fmt(capPH)} €</div><div class="pr-kpi-lbl">Capital PH</div></div>
    </div></div></div>
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">CONDITIONS CRÉDIT</div><table><tbody>
      ${p('Taux / Durée',d.taux_emprunt+'% / '+d.duree_emprunt+' mois')}
      ${d.ptz_actif==1?p('PTZ',fmt(d.ptz_montant)+' € / '+d.ptz_duree+' mois → '+fmt(mensPTZ)+' €/mois'):''}
      ${d.ecoptz_actif==1?p('EcoPTZ',fmt(d.ecoptz_montant)+' € / '+d.ecoptz_duree+' mois → '+fmt(mensEco)+' €/mois'):''}
      ${ade.length?ade.map((a,i)=>p('ADE '+(i+1)+' – '+(a.couverture||[]).join('+')+' '+a.quotite+'%','Coût: '+fmt(a.cout_total)+' €')).join(''):''}
    </tbody></table>
    <div class="pr-kpi-row">
      <div class="pr-kpi pr-kpi-primary"><div class="pr-kpi-val">${fmt(mensPH)} €</div><div class="pr-kpi-lbl">Mensualité PH</div></div>
      <div class="pr-kpi pr-kpi-primary"><div class="pr-kpi-val">${fmt(mensG)} €</div><div class="pr-kpi-lbl">Mensualité globale</div></div>
    </div></div></div>
  </div>
  <div class="pr-cols">
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">GESTION ADMINISTRATIVE</div><table><tbody>
      <tr><td colspan="2" class="pr-sub-title">ADE</td></tr>
      ${p('Envoyée le',fmtD(d.ade_envoyee_le))}${p('Retour',fmtD(d.ade_retour_le))}${p('Réponse',d.ade_reponse||'—')}
      ${d.garantie_type==='CEGC'?`<tr><td colspan="2" class="pr-sub-title">CEGC</td></tr>${p('Envoyée le',fmtD(d.suivi_date_demande_cegc))}${p('Retour',fmtD(d.suivi_date_retour_cegc))}${p('Réponse',d.suivi_cegc_accord==1?'ACCORD':d.suivi_cegc_refus==1?'REFUS':'—')}`:''}
      ${p('Date prélèvement',fmtD(d.date_prelevement))}
      ${p('Notaire',escapeHtml(d.notaire_nom||'—'))}
      ${p('Signature notaire (prévis.)',fmtD(d.date_signature_notaire_prev))}
    </tbody></table></div></div>
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">SUIVI & SIGNATURE</div><table><tbody>
      ${p('Édition liasse',fmtD(d.suivi_date_edition_liasse))}
      ${p('Envoi conformité',fmtD(d.suivi_date_envoi_conformite))}
      ${p('Retour conformité',fmtD(d.suivi_date_retour_conformite))}
      ${p('Résultat conformité',d.suivi_conformite_reponse||'—')}
      ${p('Édition offres',fmtD(d.suivi_date_edition_offres_dt))}
      ${p('Accusé de réception',fmtD(d.suivi_date_accuse_reception))}
      ${p('Date signature possible',fmtD(d.suivi_date_j11))}
      ${p('Signature définitive',fmtD(d.suivi_date_signature_definitive))}
      ${p('Versement notaire',fmtD(d.suivi_date_versement_notaire))}
    </tbody></table></div></div>
  </div>
  <div class="pr-footer">Document confidentiel — Caisse d'Épargne — ${new Date().toLocaleDateString('fr-FR')}</div>
</div>`;
  window.print();
}
