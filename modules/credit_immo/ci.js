// ── UTILS ─────────────────────────────────────────────────────────────────────
function escapeHtml(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function fmt(v){return parseFloat(v||0).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2});}
function fmtD(s){if(!s)return '—';const d=new Date(s);return isNaN(d)?s:d.toLocaleDateString('fr-FR');}
function chk(v){return v==1?'<i class="fas fa-check-circle text-success"></i>':'<i class="fas fa-times-circle text-muted"></i>';}
function ck(v){return v==1?'<span class="ck-ok">✓</span>':'<span class="ck-no">—</span>';}
function parseJ(s){try{return JSON.parse(s||'[]');}catch(e){return[];}}
function addJ11(dateStr){if(!dateStr)return '';const d=new Date(dateStr);d.setDate(d.getDate()+11);return d.toISOString().split('T')[0];}


// ── CALCULS ──────────────────────────────────────────────────────────────────
// Modèle : 1 à 2 emprunteurs, 1..n lignes de crédit (+ PTZ / EcoPTZ à 0 %), assurance en taux par emprunteur ET par ligne.
// Les dossiers créés avant cette refonte (un seul crédit, revenus non répartis) sont lus via des valeurs de repli.
const num=v=>{const x=parseFloat(v);return isNaN(x)?0:x;};
function parseArr(s){const a=parseJ(s);return Array.isArray(a)?a:[];}

function calcMensualite(capital,tauxAnnuel,duree){
  const tm=tauxAnnuel/100/12;
  if(tm>0&&duree>0) return capital*tm/(1-Math.pow(1+tm,-duree));
  if(duree>0) return capital/duree;
  return 0;
}

function getEmprunteurs(d){
  const e=parseArr(d.emprunteurs_json);
  if(e.length) return e.slice(0,2);
  return [{nom:'',bdf:d.banque_de_france||'',drc:d.drc||'',topcc:d.topcc||'',rfr:'',
    revenus:parseArr(d.revenus_json),charges:parseArr(d.charges_json),epargne:parseArr(d.epargne_json)}];
}
function sumRevenus(arr){
  return (arr||[]).reduce((t,r)=>{
    let m=num(r.montant);
    if(r.revenu_futur) m*=(parseFloat(r.ponderation||100)/100);
    if(r.periodicite==='annuelle') m/=12;
    return t+m;
  },0);
}
function sumCharges(arr){return (arr||[]).reduce((t,c)=>t+(c.non_conserve?0:num(c.montant)),0);}

// Coût du projet (hors frais de dossier, portés par les lignes de crédit)
function totalHorsDossier(d){
  return num(d.montant_acquisition)+num(d.frais_notaire)+num(d.frais_negociation)+num(d.frais_divers)+num(d.frais_agence)
    +(d.frais_midi_epargne==1?num(d.montant_midi_epargne):0)+num(d.tva_financee)+num(d.garantie_montant);
}
function ptzMontant(d){return d.ptz_actif==1?num(d.ptz_montant):0;}
function ecoMontant(d){return d.ecoptz_actif==1?num(d.ecoptz_montant):0;}
function getMensPTZ(d){return d.ptz_actif==1&&num(d.ptz_duree)>0?num(d.ptz_montant)/parseInt(d.ptz_duree):0;}
function getMensEcoPTZ(d){return d.ecoptz_actif==1&&num(d.ecoptz_duree)>0?num(d.ecoptz_montant)/parseInt(d.ecoptz_duree):0;}

function getLignes(d){
  const l=parseArr(d.lignes_credit_json);
  if(l.length) return l;
  // Ancien dossier : un seul crédit dont le capital était déduit du plan de financement
  const cap=totalHorsDossier(d)+num(d.frais_dossier)-num(d.apport)-ptzMontant(d)-ecoMontant(d);
  return [{libelle:'Prêt principal',montant:Math.max(0,cap),duree:parseInt(d.duree_emprunt||0)||0,taux:num(d.taux_emprunt),frais_dossier:num(d.frais_dossier)}];
}
function getFraisDossier(d){return getLignes(d).reduce((t,l)=>t+num(l.frais_dossier),0);}
function getTotalFinancement(d){return totalHorsDossier(d)+getFraisDossier(d);}
// Base du Doublissimo : acquisition + frais de notaire + frais de négociation (ni garantie, ni frais divers, ni frais de dossier)
function getCoutProjet(d){return num(d.montant_acquisition)+num(d.frais_notaire)+num(d.frais_negociation)+num(d.frais_agence);}
function getResteAFinancer(d){
  return getTotalFinancement(d)-num(d.apport)-ptzMontant(d)-ecoMontant(d)-getLignes(d).reduce((t,l)=>t+num(l.montant),0);
}
function doublissimoMontant(d){return Math.max(0,0.2*(getCoutProjet(d)-num(d.apport)));}

// Échéancier d'une ligne : mensualité, capital restant dû avant chaque échéance, intérêts
function scheduleLine(montant,taux,duree){
  const n=parseInt(duree)||0, tm=taux/100/12;
  const mens=calcMensualite(montant,taux,n);
  const rows=[]; let solde=montant;
  for(let m=1;m<=n;m++){
    const i=solde*tm, c=mens-i;
    rows.push({crd:solde,interet:i,capital:c});
    solde=Math.max(0,solde-c);
  }
  return {mens,rows,interets:rows.reduce((t,r)=>t+r.interet,0)};
}

function getAssurances(d){return parseArr(d.ade_json);}
// Assurance emprunteur : une ligne d'assurance = un emprunteur sur une ligne de crédit.
//  - Capital initial : cotisation constante = capital × quotité × taux / 12.
//  - Capital restant dû (méthode du logiciel de crédit, mensualité lissée) : la mensualité tout inclus est celle d'un prêt au taux
//    nominal + taux d'assurance cumulés des emprunteurs ; la part « assurance » est l'écart avec la mensualité du prêt seul (constante).
function computeAssurances(lignes,rows){
  const out=rows.map(()=>({monthly:0,total:0}));
  lignes.forEach((l,li)=>{
    const cap=num(l.montant), n=parseInt(l.duree)||0, t=num(l.taux);
    const mine=rows.map((a,k)=>({a,k})).filter(x=>(x.a.ligne??0)===li);
    const quotOf=a=>num(a.quotite)>0?num(a.quotite)/100:1;
    const crd=mine.filter(x=>x.a.base==='CRD'&&num(x.a.taux)>0);
    const R=crd.reduce((sum,x)=>sum+num(x.a.taux)*quotOf(x.a),0);
    const M=(R>0&&n>0)?calcMensualite(cap,t+R,n)-calcMensualite(cap,t,n):0;
    mine.forEach(({a,k})=>{
      const taux=num(a.taux); let m=0;
      if(taux>0&&a.base==='CRD') m=R>0?M*(taux*quotOf(a)/R):0;
      else if(taux>0) m=cap*quotOf(a)*taux/1200;
      else if(num(a.cout_total)>0&&n>0) m=num(a.cout_total)/n; // ancien dossier : coût total saisi
      out[k]={monthly:m,total:m*n};
    });
  });
  return out;
}
// TAEG : taux actuariel annuel effectif (1+i)^12−1 du flux « montant net reçu / mensualités », i = taux mensuel d'équilibre.
// « prop » = taux proportionnel annualisé (12 × i), utile pour rapprocher d'un logiciel qui affiche ce taux.
function calcTaeg(montantNet,paiements){
  if(!(montantNet>0)||!paiements.length) return null;
  const pv=i=>paiements.reduce((t,p,k)=>t+p/Math.pow(1+i,k+1),0);
  if(pv(0)<montantNet) return null;
  let lo=0,hi=0.05;
  for(let it=0;it<200;it++){const mid=(lo+hi)/2; if(pv(mid)>montantNet) lo=mid; else hi=mid;}
  const i=(lo+hi)/2;
  return {taeg:(Math.pow(1+i,12)-1)*100,prop:i*1200};
}
function taegTxt(t){return t?parseFloat(t.taeg).toFixed(2).replace('.',',')+' %':'N/A';}

// Tous les indicateurs d'un dossier (ou de l'état courant du formulaire)
function computeAll(d){
  const lignes=getLignes(d), emps=getEmprunteurs(d);
  const sch=lignes.map(l=>scheduleLine(num(l.montant),num(l.taux),l.duree));
  const mensLignes=sch.reduce((t,s)=>t+s.mens,0);
  const mensPTZ=getMensPTZ(d), mensEco=getMensEcoPTZ(d);
  const mensHorsAssur=mensLignes+mensPTZ+mensEco;
  const assRaw=getAssurances(d);
  const ass=computeAssurances(lignes,assRaw);
  const mensAssur=ass.reduce((t,a)=>t+a.monthly,0), totAssur=ass.reduce((t,a)=>t+a.total,0);
  const mensTout=mensHorsAssur+mensAssur;
  const revenus=emps.reduce((t,e)=>t+sumRevenus(e.revenus),0);
  const charges=emps.reduce((t,e)=>t+sumCharges(e.charges),0);
  const te=revenus>0?(mensTout+charges)/revenus*100:null;
  const reste=revenus-charges-mensTout;
  const nbPers=parseInt(d.nb_personnes_foyer)||(emps.length+(parseInt(d.nb_enfants)||0)+(parseInt(d.nb_personnes_charge_supp)||0));
  const interets=sch.reduce((t,s)=>t+s.interets,0);
  const fraisDossier=getFraisDossier(d), garantie=num(d.garantie_montant);
  // TAEG (méthode du logiciel de référence, retrouvée sur un dossier réel : 4,23 % / 2,51 %) :
  //  - taux actuariel effectif ; frais de dossier propres à chaque ligne ; garantie répartie au prorata des montants ;
  //  - assurance : celle d'un seul emprunteur par ligne (« le moins cher » par défaut), réglable (taeg_assurance).
  const taegMode=d.taeg_assurance||'MIN';
  const montantLignes=lignes.reduce((t,l)=>t+num(l.montant),0);
  const taegIns=lignes.map((l,li)=>{
    const rows=assRaw.filter(a=>(a.ligne??0)===li).map(a=>({...a,ligne:0}));
    const cost=r=>computeAssurances([l],[r])[0].monthly;
    let sel=rows;
    if(taegMode==='EMP1') sel=rows.filter(r=>(r.emp??0)===0);
    else if(taegMode==='EMP2') sel=rows.filter(r=>(r.emp??0)===1);
    else if(taegMode==='MIN'){
      const priced=rows.filter(r=>cost(r)>0).sort((x,y)=>cost(x)-cost(y));
      sel=priced.length?[priced[0]]:[];
    }
    return computeAssurances([l],sel).reduce((t,x)=>t+x.monthly,0);
  });
  const taegLignes=lignes.map((l,i)=>{
    const n=parseInt(l.duree)||0;
    const garantiePart=montantLignes>0?garantie*num(l.montant)/montantLignes:0;
    return n>0?calcTaeg(num(l.montant)-num(l.frais_dossier)-garantiePart,Array(n).fill(sch[i].mens+taegIns[i])):null;
  });
  const lineIns=lignes.map((l,i)=>ass.reduce((t,a,k)=>t+(((assRaw[k].ligne)??0)===i?a.monthly:0),0));
  const nPtz=d.ptz_actif==1?(parseInt(d.ptz_duree)||0):0, nEco=d.ecoptz_actif==1?(parseInt(d.ecoptz_duree)||0):0;
  const N=Math.max(0,nPtz,nEco,...lignes.map(l=>parseInt(l.duree)||0));
  const flows=Array(N).fill(0);
  lignes.forEach((l,i)=>{const n=parseInt(l.duree)||0; for(let k=0;k<n;k++) flows[k]+=sch[i].mens+taegIns[i];});
  for(let k=0;k<nPtz;k++) flows[k]+=mensPTZ;
  for(let k=0;k<nEco;k++) flows[k]+=mensEco;
  const taegGlobal=calcTaeg(lignes.reduce((t,l)=>t+num(l.montant),0)+ptzMontant(d)+ecoMontant(d)-fraisDossier-garantie,flows);
  return {lignes,sch,emps,ass,assRaw,taegLignes,taegGlobal,taegIns,lineIns,mensLignes,mensPTZ,mensEco,mensHorsAssur,mensAssur,mensTout,totAssur,
    revenus,charges,te,reste,restePers:nbPers>0?reste/nbPers:null,nbPers,interets,fraisDossier,garantie,
    coutCredit:interets+totAssur+fraisDossier+garantie,totalFin:getTotalFinancement(d),
    capital:lignes.reduce((t,l)=>t+num(l.montant),0),rfr:emps.reduce((t,e)=>t+num(e.rfr),0),resteAFin:getResteAFinancer(d)};
}

function badgeEndett(t){
  if(t===null||t===undefined) return '<span class="badge bg-secondary">N/A</span>';
  const v=parseFloat(t).toFixed(2).replace('.',',');
  if(t<=25) return `<span class="badge bg-success">${v} %</span>`;
  if(t<=33) return `<span class="badge bg-warning text-dark">${v} %</span>`;
  if(t<=35) return `<span class="badge bg-orange text-white">${v} % <i class="fas fa-exclamation-triangle"></i></span>`;
  return `<span class="badge bg-danger">${v} % <i class="fas fa-exclamation-circle"></i></span>`;
}
function alertEndett(t){
  if(t===null||t===undefined||t<=33) return '';
  if(t<=35) return '<div class="alert alert-warning mt-2 py-1"><i class="fas fa-exclamation-triangle"></i> Taux proche du seuil HCSF de 35 %</div>';
  return '<div class="alert alert-danger mt-2 py-1"><i class="fas fa-exclamation-circle"></i> <strong>ALERTE</strong> : taux supérieur au seuil HCSF de 35 %</div>';
}
function pct2(t){return t===null||t===undefined?'N/A':parseFloat(t).toFixed(2).replace('.',',')+' %';}

// Libellés
const OCC_LABELS={LOCATAIRE_HLM:'Locataire HLM',AUTRE_LOCATAIRE:'Autre locataire',LOGE_GRATUIT:'Logé à titre gratuit',AUTRE:'Autre',PROPRIETAIRE:'Autre'};
const PRIMO_LABELS={NON:'Non',OUI:'Oui',OUI_AAH:'Oui – allocation personne handicapée',OUI_CARTE_INVALIDITE:'Oui – carte invalidité',OUI_CATASTROPHE:'Oui – victime de catastrophe'};
const TYPE_PROJET_LABELS={ANCIEN_SANS_TRAVAUX:'Ancien sans travaux',ANCIEN_AVEC_TRAVAUX:'Ancien avec travaux',CONSTRUCTION_CCMI:'Construction avec CCMI',CONSTRUCTION_SANS_CCMI:'Construction sans CCMI',NEUF_VEFA:'Neuf VEFA'};
const USAGE_LABELS={RP:'Principal',RS:'Secondaire',RL_PRINCIPALE:'Locatif principal',RL_SECONDAIRE:'Locatif secondaire'};
const MODE_OCC_LABELS={EMPRUNTEUR:'Emprunteur',ASCENDANT:'Ascendant',DESCENDANT:'Descendant'};
const TYPE_ACQ_LABELS={MAISON:'Maison',APPARTEMENT:'Appartement'};
const TYPE_PROP_LABELS={NU_PROPRIETAIRE:'Nu-propriétaire',USUFRUITIER:'Usufruitier',PLEINE_PROPRIETE:'Pleine propriété',NON_PROPRIETAIRE:'Non propriétaire'};
const TYPES_LOGEMENT=['T1','T1 bis','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
function usageChoice(d){
  if(d.usage_bien==='RL') return d.usage_rl_type==='RL_SECONDAIRE'?'RL_SECONDAIRE':'RL_PRINCIPALE';
  return d.usage_bien||'';
}
function primoStatut(d){
  if(d.primo_accedant_statut) return d.primo_accedant_statut;
  if(d.primo_accedant==1) return 'OUI';
  if(d.primo_accedant===0||d.primo_accedant==='0') return 'NON';
  return '';
}
function modeOcc(d){return d.mode_occupation==='RP'?'EMPRUNTEUR':(MODE_OCC_LABELS[d.mode_occupation]?d.mode_occupation:'');}

// Remplir le tableau (mensualité tout inclus + endettement) au chargement
document.addEventListener('DOMContentLoaded',()=>{
  filterTable('searchDossiers','tableDossiers');
  dossiersData.forEach(d=>{
    const c=computeAll(d);
    const em=document.getElementById('mens_'+d.id), et=document.getElementById('tend_'+d.id);
    const set=(k,v)=>{const el=document.getElementById(k+'_'+d.id); if(el) el.textContent=v;};
    set('usg',USAGE_LABELS[usageChoice(d)]||'—'); set('cap',fmt(c.capital)+' €'); set('nl',c.lignes.length);
    if(em) em.textContent=fmt(c.mensTout)+' €';
    if(et) et.innerHTML=badgeEndett(c.te);
  });
  const op=new URLSearchParams(location.search).get('open');
  if(op){showDetail(parseInt(op));history.replaceState(null,'','index.php');}
});

// ── LIGNES DYNAMIQUES (revenus / charges / épargne par emprunteur) ───────────
let _uid=0;
function buildRevenuRow(r,px){
  r=r||{}; const u=++_uid;
  const isFutur=r.revenu_futur?'checked':'';
  const mensChecked=(r.periodicite==='mensuelle'||!r.periodicite)?'checked':'';
  const annChecked=r.periodicite==='annuelle'?'checked':'';
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-start" data-type="revenu">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(r.intitule||'')}" data-field="intitule" oninput="onFormChange('${px}')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${r.montant||''}" data-field="montant" oninput="onFormChange('${px}')">
    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" ${isFutur} data-field="revenu_futur" onchange="toggleFutur(this);onFormChange('${px}')"><label class="form-check-label small">Revenu futur</label></div>
    <div class="ci-futur-opts ms-2" style="${r.revenu_futur?'':'display:none'}">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="per_${u}" value="mensuelle" ${mensChecked} data-field="periodicite" onchange="onFormChange('${px}')"><label class="form-check-label small">Mensuelle</label></div>
        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="per_${u}" value="annuelle" ${annChecked} data-field="periodicite" onchange="onFormChange('${px}')"><label class="form-check-label small">Annuelle</label></div>
        <div class="d-flex align-items-center gap-1"><input type="number" step="1" min="0" max="100" class="form-control form-control-sm" style="width:70px" placeholder="%" value="${r.ponderation||100}" data-field="ponderation" oninput="onFormChange('${px}')"><span class="small">%</span></div>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}')"><i class="fas fa-times"></i></button>
  </div>`;
}
function buildChargeRow(c,px){
  c=c||{};
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-center" data-type="charge">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(c.intitule||'')}" data-field="intitule" oninput="onFormChange('${px}')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${c.montant||''}" data-field="montant" oninput="onFormChange('${px}')">
    <div class="form-check"><input class="form-check-input" type="checkbox" ${c.non_conserve?'checked':''} data-field="non_conserve" onchange="onFormChange('${px}')"><label class="form-check-label small text-muted">Non conservé</label></div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}')"><i class="fas fa-times"></i></button>
  </div>`;
}
function buildEpargneRow(e,px){
  e=e||{};
  return `<div class="ci-json-row d-flex flex-wrap gap-2 align-items-center" data-type="epargne">
    <input type="text" class="form-control form-control-sm" style="flex:2;min-width:140px" placeholder="Intitulé" value="${escapeHtml(e.intitule||'')}" data-field="intitule" oninput="onFormChange('${px}')">
    <input type="number" step="0.01" class="form-control form-control-sm" style="width:110px" placeholder="Montant €" value="${e.montant||''}" data-field="montant" oninput="onFormChange('${px}')">
    <div class="form-check"><input class="form-check-input" type="checkbox" ${e.hors_cemp?'checked':''} data-field="hors_cemp" onchange="toggleCemp(this);onFormChange('${px}')"><label class="form-check-label small">Hors CEMP</label></div>
    <div class="ci-cemp-banque" style="${e.hors_cemp?'':'display:none'}"><input type="text" class="form-control form-control-sm" style="width:140px" placeholder="Nom de la banque" value="${escapeHtml(e.nom_banque||'')}" data-field="nom_banque" oninput="onFormChange('${px}')"></div>
    <button type="button" class="btn btn-sm btn-outline-danger btn-rm ms-auto" onclick="removeRow(this,'${px}')"><i class="fas fa-times"></i></button>
  </div>`;
}
function toggleFutur(cb){const o=cb.closest('.ci-json-row').querySelector('.ci-futur-opts');if(o)o.style.display=cb.checked?'':'none';}
function toggleCemp(cb){const b=cb.closest('.ci-json-row').querySelector('.ci-cemp-banque');if(b)b.style.display=cb.checked?'':'none';}

function readRows(container,type){
  const out=[];
  if(!container) return out;
  container.querySelectorAll('.ci-json-row').forEach(row=>{
    const g=f=>row.querySelector(`[data-field="${f}"]`);
    const o={intitule:g('intitule')?.value||'',montant:parseFloat(g('montant')?.value||0)||0};
    if(type==='revenus'){
      o.revenu_futur=g('revenu_futur')?.checked||false;
      if(o.revenu_futur){
        o.periodicite=Array.from(row.querySelectorAll('[data-field="periodicite"]')).find(r=>r.checked)?.value||'mensuelle';
        o.ponderation=parseFloat(g('ponderation')?.value||100)||100;
      }
    } else if(type==='charges'){
      o.non_conserve=g('non_conserve')?.checked||false;
    } else {
      o.hors_cemp=g('hors_cemp')?.checked||false;
      o.nom_banque=g('nom_banque')?.value||'';
    }
    out.push(o);
  });
  return out;
}
function addRow(px,ei,type){
  const c=document.getElementById(`${px}_e${ei}_${type}_list`);
  if(!c) return;
  const b=type==='revenus'?buildRevenuRow:type==='charges'?buildChargeRow:buildEpargneRow;
  c.insertAdjacentHTML('beforeend',b(null,px));
  onFormChange(px);
}
function removeRow(btn,px){btn.closest('.ci-json-row').remove();onFormChange(px);}

// ── EMPRUNTEURS ──────────────────────────────────────────────────────────────
function buildEmprunteurPanel(px,i,e){
  e=e||{};
  const st=(field,label,val)=>`<div class="col-md-2"><label class="form-label small mb-0">${label}</label><select class="form-select form-select-sm" data-ef="${field}" onchange="onFormChange('${px}')">
    <option value="">Non interrogé</option><option value="OK" ${val==='OK'?'selected':''}>OK</option><option value="KO" ${val==='KO'?'selected':''}>KO</option></select></div>`;
  return `<div class="card mb-3 ci-emp"><div class="card-header d-flex justify-content-between align-items-center">
      <strong><i class="fas fa-user"></i> Emprunteur ${i+1}</strong>
      ${i>0?`<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEmprunteur('${px}')"><i class="fas fa-user-minus"></i> Retirer</button>`:''}
    </div><div class="card-body">
    <div class="row g-2 mb-3">
      <div class="col-md-3"><label class="form-label small mb-0">Nom / prénom</label><input type="text" class="form-control form-control-sm" data-ef="nom" value="${escapeHtml(e.nom||'')}" oninput="onFormChange('${px}')"></div>
      ${st('bdf','Interro. Banque de France',e.bdf)}${st('drc','Interro. DRC',e.drc)}${st('topcc','TopCC',e.topcc)}
      <div class="col-md-3"><label class="form-label small mb-0">Revenu fiscal de référence (€)</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-ef="rfr" value="${e.rfr??''}" oninput="onFormChange('${px}')"></div>
    </div>
    <h6>Revenus</h6><div id="${px}_e${i}_revenus_list" class="ci-json-list mb-2"></div>
    <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}',${i},'revenus')"><i class="fas fa-plus"></i> Ajouter un revenu</button>
    <h6>Charges</h6><div id="${px}_e${i}_charges_list" class="ci-json-list mb-2"></div>
    <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addRow('${px}',${i},'charges')"><i class="fas fa-plus"></i> Ajouter une charge</button>
    <h6>Épargne</h6><div id="${px}_e${i}_epargne_list" class="ci-json-list mb-2"></div>
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('${px}',${i},'epargne')"><i class="fas fa-plus"></i> Ajouter une épargne</button>
  </div></div>`;
}
function fillEmprunteurRows(px,i,e){
  [['revenus',buildRevenuRow],['charges',buildChargeRow],['epargne',buildEpargneRow]].forEach(([t,b])=>{
    const c=document.getElementById(`${px}_e${i}_${t}_list`);
    if(c) c.innerHTML=(e[t]||[]).map(r=>b(r,px)).join('');
  });
}
function renderEmprunteurs(px,list){
  const box=document.getElementById(px+'_emps');
  box.innerHTML=list.map((e,i)=>buildEmprunteurPanel(px,i,e)).join('');
  list.forEach((e,i)=>fillEmprunteurRows(px,i,e));
  const add=document.getElementById(px+'_add_emp');
  if(add) add.style.display=list.length>=2?'none':'';
}
function collectEmprunteurs(px){
  return Array.from(document.querySelectorAll(`#${px}_emps .ci-emp`)).map((card,i)=>{
    const o={};
    card.querySelectorAll('[data-ef]').forEach(el=>{o[el.dataset.ef]=el.value;});
    o.revenus=readRows(document.getElementById(`${px}_e${i}_revenus_list`),'revenus');
    o.charges=readRows(document.getElementById(`${px}_e${i}_charges_list`),'charges');
    o.epargne=readRows(document.getElementById(`${px}_e${i}_epargne_list`),'epargne');
    return o;
  });
}
function addEmprunteur(px){
  const list=collectEmprunteurs(px);
  if(list.length>=2) return;
  list.push({nom:'',revenus:[],charges:[],epargne:[]});
  renderEmprunteurs(px,list);
  renderAssurances(px);
  onFormChange(px);
}
function removeEmprunteur(px){
  const list=collectEmprunteurs(px);
  if(list.length<=1) return;
  if(!confirm('Retirer le 2ème emprunteur et ses données ?')) return;
  list.pop();
  renderEmprunteurs(px,list);
  renderAssurances(px);
  onFormChange(px);
}

// ── ENFANTS ──────────────────────────────────────────────────────────────────
function renderEnfants(px,ages){
  const n=Math.max(0,Math.min(20,parseInt(document.getElementById(px+'_nb_enfants')?.value)||0));
  const box=document.getElementById(px+'_enfants_ages');
  if(!box) return;
  const cur=ages||Array.from(box.querySelectorAll('[data-age]')).map(i=>i.value);
  box.innerHTML=Array.from({length:n},(_,i)=>`<div class="col-auto"><label class="form-label small mb-0">Enfant ${i+1} (âge)</label>
    <input type="number" min="0" max="99" class="form-control form-control-sm" style="width:90px" data-age value="${cur[i]??''}" oninput="onFormChange('${px}')"></div>`).join('');
  onFormChange(px);
}

// ── LIGNES DE CRÉDIT ─────────────────────────────────────────────────────────
function buildLigneRow(px,l,idx){
  l=l||{};
  const dbl=l.doublissimo;
  const f=(label,field,val,attrs,col)=>`<div class="${col}"><label class="form-label small mb-0">${label}</label><input type="number" ${attrs} class="form-control form-control-sm" data-lf="${field}" value="${val??''}" oninput="onFormChange('${px}')" ${dbl&&field==='montant'?'readonly style="background:#eef"':''}></div>`;
  return `<div class="ci-json-row ci-ligne" data-dbl="${dbl?1:0}">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label small mb-0">Libellé${dbl?' <span class="badge bg-info">Doublissimo</span>':''}</label><input type="text" class="form-control form-control-sm" data-lf="libelle" value="${escapeHtml(l.libelle||('Ligne '+(idx+1)))}" oninput="onFormChange('${px}')"></div>
      ${f('Montant (€)','montant',l.montant,'step="0.01" min="0"','col-md-2')}
      ${f('Durée (mois)','duree',l.duree,'min="0"','col-md-2')}
      ${f('Taux (%)','taux',l.taux,'step="0.001" min="0"','col-md-1')}
      ${f('Frais de dossier (€)','frais_dossier',l.frais_dossier,'step="0.01" min="0"','col-md-2')}
      <div class="col-md-2 d-flex justify-content-between align-items-end"><span class="small text-muted ci-ligne-mens"></span>
        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer la ligne" onclick="removeLigne(this,'${px}')"><i class="fas fa-times"></i></button></div>
    </div></div>`;
}
function readLignes(px){
  return Array.from(document.querySelectorAll(`#${px}_lignes_list .ci-ligne`)).map(row=>{
    const g=f=>row.querySelector(`[data-lf="${f}"]`)?.value||'';
    return {libelle:g('libelle'),montant:num(g('montant')),duree:parseInt(g('duree'))||0,taux:num(g('taux')),frais_dossier:num(g('frais_dossier')),doublissimo:row.dataset.dbl==='1'};
  });
}
function renderLignes(px,lignes){
  document.getElementById(px+'_lignes_list').innerHTML=lignes.map((l,i)=>buildLigneRow(px,l,i)).join('');
}
function addLigne(px,l){
  const list=readLignes(px); list.push(l||{libelle:'Ligne '+(list.length+1),montant:'',duree:'',taux:'',frais_dossier:0});
  renderLignes(px,list); renderAssurances(px); onFormChange(px);
}
function removeLigne(btn,px){
  const list=readLignes(px);
  if(list.length<=1){alert('Il faut conserver au moins une ligne de crédit.');return;}
  const row=btn.closest('.ci-ligne');
  const idx=Array.from(row.parentNode.children).indexOf(row);
  const wasDbl=list[idx].doublissimo;
  list.splice(idx,1);
  renderLignes(px,list);
  if(wasDbl){const cb=document.getElementById(px+'_dbl_cb'); if(cb) cb.checked=false;}
  renderAssurances(px); onFormChange(px);
}
function onDoublissimo(px){
  const on=document.getElementById(px+'_dbl_cb').checked;
  let list=readLignes(px);
  if(on&&!list.some(l=>l.doublissimo)) list.push({libelle:'Doublissimo',montant:0,duree:'',taux:'',frais_dossier:0,doublissimo:true});
  if(!on) list=list.filter(l=>!l.doublissimo);
  if(!list.length) list.push({libelle:'Ligne 1',montant:'',duree:'',taux:'',frais_dossier:0});
  renderLignes(px,list); renderAssurances(px); onFormChange(px);
}
// Doublissimo = 20 % de (coût du projet − apport), recalculé tant que la case est cochée
function syncDoublissimo(px){
  if(!document.getElementById(px+'_dbl_cb')?.checked) return;
  const row=document.querySelector(`#${px}_lignes_list .ci-ligne[data-dbl="1"] [data-lf="montant"]`);
  if(row) row.value=doublissimoMontant(readFormRaw(px)).toFixed(2);
}
function equilibrerLignes(px){
  const raw=readFormRaw(px); raw.lignes_credit_json=JSON.stringify(readLignes(px));
  const reste=getResteAFinancer(raw);
  const rows=document.querySelectorAll(`#${px}_lignes_list .ci-ligne`);
  for(let i=rows.length-1;i>=0;i--){
    if(rows[i].dataset.dbl==='1') continue;
    const inp=rows[i].querySelector('[data-lf="montant"]');
    inp.value=Math.max(0,num(inp.value)+reste).toFixed(2); break;
  }
  onFormChange(px);
}

// ── ASSURANCES (une ligne par emprunteur ET par ligne de crédit) ─────────────
function buildAssuranceRow(px,a,ei,li,empLabel,ligneLabel){
  a=a||{}; const couv=a.couverture||[];
  const sel=(field,opts,val)=>`<select class="form-select form-select-sm" data-af="${field}" onchange="onFormChange('${px}')"><option value="">--</option>${opts.map(o=>`<option value="${o}" ${val===o?'selected':''}>${o}</option>`).join('')}</select>`;
  return `<div class="ci-json-row ci-ass" data-emp="${ei}" data-ligne="${li}">
    <div class="d-flex justify-content-between align-items-center mb-2"><strong class="small">${escapeHtml(empLabel)} — ${escapeHtml(ligneLabel)}</strong><span class="small text-muted ci-ass-cout"></span></div>
    <input type="hidden" data-af="cout_total" value="${a.cout_total||''}">
    <div class="row g-2">
      <div class="col-12"><div class="d-flex gap-3 flex-wrap">${['DC','PTIA','ITT','Invalidité'].map(c=>`<div class="form-check"><input class="form-check-input" type="checkbox" value="${c}" ${couv.includes(c)?'checked':''} data-af="couverture" onchange="onFormChange('${px}')"><label class="form-check-label small">${c}</label></div>`).join('')}</div></div>
      <div class="col-md-2"><label class="form-label small mb-0">Taux assurance (%/an)</label><input type="number" step="0.001" min="0" class="form-control form-control-sm" data-af="taux" value="${a.taux??''}" oninput="onFormChange('${px}')"></div>
      <div class="col-md-2"><label class="form-label small mb-0">Base du taux</label><select class="form-select form-select-sm" data-af="base" onchange="onFormChange('${px}')"><option value="CRD" ${a.base!=='CI'?'selected':''}>Capital restant dû (mensualité lissée)</option><option value="CI" ${a.base==='CI'?'selected':''}>Capital initial</option></select></div>
      <div class="col-md-2"><label class="form-label small mb-0">Quotité %</label><input type="number" step="1" min="0" max="100" class="form-control form-control-sm" data-af="quotite" value="${a.quotite??100}" oninput="onFormChange('${px}')"></div>
      <div class="col-md-2"><label class="form-label small mb-0">Type</label>${sel('type',['INDEMNITAIRE','FORFAITAIRE'],a.type)}</div>
      <div class="col-md-2"><label class="form-label small mb-0">Franchise</label>${sel('franchise',['30J','90J'],a.franchise)}</div>
      <div class="col-md-2"><label class="form-label small mb-0">IPP</label>${sel('ipp',['33%','66%'],a.ipp)}</div>
    </div></div>`;
}
function toutesAssurancesCRD(px){
  document.querySelectorAll(`#${px}_ade_list [data-af=base]`).forEach(sel=>{sel.value='CRD';});
  onFormChange(px);
}
function readAssurances(px){
  return Array.from(document.querySelectorAll(`#${px}_ade_list .ci-ass`)).map(row=>{
    const g=f=>row.querySelector(`[data-af="${f}"]`);
    return {emp:parseInt(row.dataset.emp),ligne:parseInt(row.dataset.ligne),
      couverture:Array.from(row.querySelectorAll('[data-af="couverture"]:checked')).map(c=>c.value),
      taux:g('taux')?.value===''?'':num(g('taux')?.value),base:g('base')?.value||'CRD',quotite:num(g('quotite')?.value),
      type:g('type')?.value||'',franchise:g('franchise')?.value||'',ipp:g('ipp')?.value||'',cout_total:num(g('cout_total')?.value)};
  });
}
// Régénère la grille emprunteur × ligne en conservant les valeurs déjà saisies
function renderAssurances(px,initial){
  const prev=initial||readAssurances(px);
  const emps=collectEmprunteurs(px), lignes=readLignes(px);
  const byKey={}; prev.forEach(a=>{byKey[(a.emp??0)+'_'+(a.ligne??0)]=a;});
  let html='';
  emps.forEach((e,ei)=>lignes.forEach((l,li)=>{
    html+=buildAssuranceRow(px,byKey[ei+'_'+li],ei,li,e.nom||('Emprunteur '+(ei+1)),l.libelle||('Ligne '+(li+1)));
  }));
  document.getElementById(px+'_ade_list').innerHTML=html;
}

// ── MRH ──────────────────────────────────────────────────────────────────────
function buildMrhOption(px,txt){
  return `<div class="ci-json-row d-flex gap-2 align-items-center"><input type="text" class="form-control form-control-sm" data-mo value="${escapeHtml(txt||'')}" placeholder="Option choisie" oninput="onFormChange('${px}')">
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.ci-json-row').remove();onFormChange('${px}')"><i class="fas fa-times"></i></button></div>`;
}
function addMrhOption(px){document.getElementById(px+'_mrh_list').insertAdjacentHTML('beforeend',buildMrhOption(px,''));}
function readMrhOptions(px){return Array.from(document.querySelectorAll(`#${px}_mrh_list [data-mo]`)).map(i=>i.value.trim()).filter(Boolean);}

// ── ÉTAT DU FORMULAIRE ───────────────────────────────────────────────────────
function readFormRaw(px){
  const f=document.getElementById(px+'_form'); const o={};
  new FormData(f).forEach((v,k)=>{o[k]=v;});
  ['ptz_actif','ecoptz_actif','frais_midi_epargne','doublissimo'].forEach(k=>{o[k]=f.querySelector(`[name="${k}"]`)?.checked?1:0;});
  return o;
}
// Écrit dans les champs cachés les listes (JSON) envoyées au serveur
function serializeAll(px){
  const set=(n,v)=>{const el=document.getElementById(px+'_'+n); if(el) el.value=JSON.stringify(v);};
  const emps=collectEmprunteurs(px);
  set('emprunteurs_json',emps);
  // Colonnes historiques : cumul des deux emprunteurs (lecture par d'anciens écrans)
  set('revenus_json',emps.flatMap(e=>e.revenus));
  set('charges_json',emps.flatMap(e=>e.charges));
  set('epargne_json',emps.flatMap(e=>e.epargne));
  set('lignes_credit_json',readLignes(px));
  set('ade_json',readAssurances(px));
  set('enfants_ages_json',Array.from(document.querySelectorAll(`#${px}_enfants_ages [data-age]`)).map(i=>i.value===''?null:parseInt(i.value)));
  set('mrh_options_json',readMrhOptions(px));
}
function readForm(px){serializeAll(px);return readFormRaw(px);}

function onFormChange(px){
  if(!document.getElementById(px+'_form')) return;
  syncDoublissimo(px);
  const d=readForm(px), c=computeAll(d);
  // Cumul des revenus fiscaux de référence
  const rfr=document.getElementById(px+'_rfr_total'); if(rfr) rfr.textContent=fmt(c.rfr)+' €';
  // Mensualité et coût par ligne / par assurance
  document.querySelectorAll(`#${px}_lignes_list .ci-ligne`).forEach((row,i)=>{
    const s=c.sch[i], el=row.querySelector('.ci-ligne-mens'); if(!el||!s) return;
    const ass=ligneAssuranceMensuelle(c,i);
    el.innerHTML=fmt(s.mens)+' € <span class="text-muted">hors ass.</span>'+(ass>0?'<br><strong>'+fmt(s.mens+ass)+' € avec ass.</strong>':'')+(c.taegLignes[i]?'<br><span class="text-muted">TAEG '+taegTxt(c.taegLignes[i])+'</span>':'');
  });
  document.querySelectorAll(`#${px}_ade_list .ci-ass`).forEach((row,i)=>{
    const a=c.ass[i], el=row.querySelector('.ci-ass-cout'); if(el&&a) el.textContent=a.total>0?fmt(a.monthly)+' €/mois — total '+fmt(a.total)+' €':'';
  });
  const warn=document.getElementById(px+'_ass_warn');
  if(warn){
    const mixte=c.lignes.some((l,li)=>new Set(c.assRaw.filter(a=>(a.ligne??0)===li&&num(a.taux)>0).map(a=>a.base||'CRD')).size>1);
    warn.innerHTML=mixte?'<div class="alert alert-warning py-1 small mb-2"><i class="fas fa-exclamation-triangle"></i> Les assurances d\'une même ligne n\'ont pas la même base (capital initial / capital restant dû) : la mensualité n\'est alors pas lissée comme dans le logiciel de crédit. Utilisez le bouton « Tout passer en capital restant dû » si ce n\'est pas voulu.</div>':'';
  }
  renderResultPanel(px,d,c);
}
// Cotisation d'assurance mensuelle (1re échéance) rattachée à une ligne de crédit, tous emprunteurs confondus
function ligneAssuranceMensuelle(c,idx){
  return c.ass.reduce((t,a,k)=>t+(((c.assRaw[k]?.ligne)??0)===idx?a.monthly:0),0);
}
function resultPanelHtml(d,c){
  const rest=c.resteAFin;
  const k=(label,val,cls)=>`<div class="col-md-3 col-6"><div class="card border-0 bg-light text-center p-2 ${cls||''}"><div class="fw-bold">${val}</div><div class="small text-muted">${label}</div></div></div>`;
  return `<div class="row g-2 mt-1">
      ${k('Montant du projet (acquisition + notaire + négociation)',fmt(getCoutProjet(d))+' €')}
      ${k('Total à financer (frais de dossier et garantie inclus)',fmt(c.totalFin)+' €')}
      ${k('Montant financé (après apport)',fmt(c.totalFin-num(d.apport)-ptzMontant(d)-ecoMontant(d))+' €')}
      ${k('Reste à financer',fmt(rest)+' €',Math.abs(rest)>0.5?'border border-warning':'')}
      ${k('Mensualité hors assurance',fmt(c.mensHorsAssur)+' €')}
      ${k('Assurance / mois',fmt(c.mensAssur)+' €')}
      ${k('Mensualité tout inclus',`<span class="fs-5">${fmt(c.mensTout)} €</span>`,'border border-primary')}
      ${k("Taux d'endettement (assurance incluse)",badgeEndett(c.te))}
      ${k('Revenus cumulés / mois',fmt(c.revenus)+' €')}
      ${k('Charges conservées',fmt(c.charges)+' €')}
      ${k('Reste à vivre cumulé',`<span class="${c.reste>=0?'text-success':'text-danger'}">${fmt(c.reste)} €</span>`)}
      ${k('Reste à vivre / personne ('+c.nbPers+')',c.restePers===null?'N/A':`<span class="${c.restePers>=0?'text-success':'text-danger'}">${fmt(c.restePers)} €</span>`)}
      ${k('TAEG global (assurance, frais de dossier et garantie inclus)',c.taegGlobal?`<span class="fs-5">${taegTxt(c.taegGlobal)}</span><div class="small text-muted">annualisé simple ${parseFloat(c.taegGlobal.prop).toFixed(2).replace('.',',')} %</div>`:'N/A','border border-info')}
      ${k('Intérêts',fmt(c.interets)+' €')}
      ${k('Assurances (total)',fmt(c.totAssur)+' €')}
      ${k('Frais de dossier + garantie',fmt(c.fraisDossier+c.garantie)+' €')}
      <div class="col-md-3 col-6"><div class="card border-0 text-center p-2" style="background:#e8f5ee"><div class="fw-bold fs-5">${fmt(c.coutCredit)} €</div><div class="small">Coût total du crédit pour le client</div></div></div>
    </div>${alertEndett(c.te)}`;
}
function renderResultPanel(px,d,c){
  const html=resultPanelHtml(d,c);
  ['_endett_live','_endett_client'].forEach(s=>{const el=document.getElementById(px+s); if(el) el.innerHTML=html;});
}

// ── FORM TABS BUILDER ─────────────────────────────────────────────────────────
function opt(map,val,placeholder){
  return `<option value="">${placeholder||'--'}</option>`+Object.entries(map).map(([k,l])=>`<option value="${k}" ${val===k?'selected':''}>${l}</option>`).join('');
}
function buildFormTabs(px,d){
  d=d||{};
  const hasPTZ=d.ptz_actif==1;
  const hasEcoPTZ=d.ecoptz_actif==1;
  const hasPTZorEco=hasPTZ||hasEcoPTZ;
  const hasCEGC=(d.garantie_type||'')==='CEGC';
  const action=d.id?'edit':'add';
  const occ=d.statut_occupation==='PROPRIETAIRE'?'AUTRE':(d.statut_occupation||'');
  const fn=f=>`${d[f]??''}`;
  const money=(name,id,label,val,extra)=>`<div class="col-md-3"><label class="form-label">${label}</label><input type="number" step="0.01" min="0" name="${name}" ${id?`id="${px}_${id}"`:''} class="form-control" value="${val||0}" oninput="onFormChange('${px}')" ${extra||''}></div>`;
  const nego=num(d.frais_negociation)+num(d.frais_agence); // anciens « frais d'agence » regroupés avec la négociation

  return `<form method="POST" id="${px}_form" onsubmit="beforeSubmit('${px}')">
    <input type="hidden" name="action" value="${action}">
    ${d.id?`<input type="hidden" name="id" value="${d.id}">`:''}
    <!-- Champs cachés (listes JSON) -->
    ${['emprunteurs_json','revenus_json','charges_json','epargne_json','lignes_credit_json','ade_json','enfants_ages_json','mrh_options_json'].map(n=>`<input type="hidden" id="${px}_${n}" name="${n}" value="">`).join('')}
    <input type="hidden" name="bien_lat" id="${px}_bien_lat" value="${fn('bien_lat')}">
    <input type="hidden" name="bien_lon" id="${px}_bien_lon" value="${fn('bien_lon')}">
    <input type="hidden" name="dpe_ges" id="${px}_dpe_ges" value="${fn('dpe_ges')}">
    <input type="hidden" name="dpe_numero" id="${px}_dpe_numero" value="${escapeHtml(fn('dpe_numero'))}">
    <input type="hidden" name="dpe_date" id="${px}_dpe_date" value="${fn('dpe_date')}">
    <input type="hidden" name="dpe_conso" id="${px}_dpe_conso" value="${fn('dpe_conso')}">

    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#${px}T1">Client</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T2">Projet</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T3">Financement</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T4">Gestion admin.</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T5">Pièces</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T6">Suivi & Signature</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${px}T8">MRH</a></li>
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
        </div>

        <div id="${px}_emps"></div>
        <div class="d-flex align-items-center gap-3 mb-3">
          <button type="button" class="btn btn-sm btn-outline-primary" id="${px}_add_emp" onclick="addEmprunteur('${px}')"><i class="fas fa-user-plus"></i> Ajouter un 2ème emprunteur</button>
          <span class="ms-auto">Revenu fiscal de référence cumulé : <strong id="${px}_rfr_total">0,00 €</strong></span>
        </div>

        <div class="card mb-3"><div class="card-header"><strong>Foyer et situation</strong></div><div class="card-body"><div class="row g-3">
          <div class="col-md-4"><label class="form-label">Primo accédant</label>
            <select name="primo_accedant_statut" class="form-select">${opt(PRIMO_LABELS,primoStatut(d))}</select></div>
          <div class="col-md-4"><label class="form-label">Statut d'occupation actuel</label>
            <select name="statut_occupation" class="form-select">${opt({LOCATAIRE_HLM:'Locataire HLM',AUTRE_LOCATAIRE:'Autre locataire',LOGE_GRATUIT:'Logé à titre gratuit',AUTRE:'Autre'},occ)}</select></div>
          <div class="col-md-4"></div>
          <div class="col-md-3"><label class="form-label">Nombre de personnes dans le foyer</label><input type="number" min="0" name="nb_personnes_foyer" class="form-control" value="${d.nb_personnes_foyer??''}" oninput="onFormChange('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Nombre d'enfants</label><input type="number" min="0" max="20" name="nb_enfants" id="${px}_nb_enfants" class="form-control" value="${d.nb_enfants??''}" oninput="renderEnfants('${px}')"></div>
          <div class="col-md-3"><label class="form-label">Personnes supplémentaires à charge</label><input type="number" min="0" name="nb_personnes_charge_supp" class="form-control" value="${d.nb_personnes_charge_supp??''}" oninput="onFormChange('${px}')"></div>
          <div class="col-12"><div class="row g-2" id="${px}_enfants_ages"></div></div>
        </div></div></div>

        <div id="${px}_endett_client"></div>
      </div>

      <!-- ── TAB 2 PROJET ── -->
      <div class="tab-pane fade" id="${px}T2">
        <div class="row g-3 mb-3">
          <div class="col-md-4"><label class="form-label">Type de projet</label><select name="type_projet" class="form-select">${opt(TYPE_PROJET_LABELS,d.type_projet)}</select></div>
          <div class="col-md-4"><label class="form-label">Usage</label><select name="usage_choice" class="form-select">${opt(USAGE_LABELS,usageChoice(d))}</select></div>
          <div class="col-md-4"><label class="form-label">Mode d'occupation</label><select name="mode_occupation" class="form-select">${opt(MODE_OCC_LABELS,modeOcc(d))}</select></div>
        </div>

        <div class="card mb-3"><div class="card-header"><strong>Montants</strong></div><div class="card-body"><div class="row g-3">
          ${money('montant_acquisition','mont_acq',"Acquisition (€)",d.montant_acquisition)}
          ${money('dont_mobilier_financable','mob',"Dont mobilier financable (€)",d.dont_mobilier_financable)}
          ${money('frais_notaire','frais_notaire',"Frais de notaire (€)",d.frais_notaire)}
          ${money('frais_negociation','frais_neg',"Frais de négociation – agence immo (€)",nego)}
          ${money('frais_divers','frais_div',"Frais divers (€)",d.frais_divers)}
        </div></div></div>

        <div class="card mb-3"><div class="card-header"><strong>Le bien</strong></div><div class="card-body"><div class="row g-3">
          <div class="col-md-12 position-relative"><label class="form-label">Localisation du bien <small class="text-muted">(saisissez l'adresse puis choisissez une suggestion)</small></label>
            <input type="text" name="adresse_bien" id="${px}_adresse" class="form-control" value="${escapeHtml(d.adresse_bien||'')}" autocomplete="off" placeholder="Ex. : 5 avenue Charles de Gaulle 12700 Capdenac-Gare">
            <div id="${px}_adr_sugg" class="list-group shadow position-absolute w-100" style="z-index:2000;max-height:240px;overflow:auto;display:none"></div></div>
          <div class="col-md-3"><label class="form-label">Type d'acquisition</label><select name="type_acquisition" class="form-select">${opt(TYPE_ACQ_LABELS,d.type_acquisition)}</select></div>
          <div class="col-md-3"><label class="form-label">Type de propriété</label><select name="type_propriete" class="form-select">${opt(TYPE_PROP_LABELS,d.type_propriete)}</select></div>
          <div class="col-md-2"><label class="form-label">Type de logement</label><select name="type_logement" class="form-select"><option value="">--</option>${TYPES_LOGEMENT.map(t=>`<option value="${t}" ${d.type_logement===t?'selected':''}>${t}</option>`).join('')}</select></div>
          <div class="col-md-2"><label class="form-label">Nombre de logements</label><input type="number" min="0" name="nb_logements" class="form-control" value="${d.nb_logements??''}"></div>
          <div class="col-md-2"><label class="form-label">Surface habitable (m²)</label><input type="number" step="0.01" min="0" name="surface_habitable" id="${px}_surface" class="form-control" value="${d.surface_habitable??''}"></div>
          <div class="col-md-3"><label class="form-label">Date de fin de construction</label><input type="date" name="date_fin_construction" class="form-control" value="${d.date_fin_construction||''}"></div>
          <div class="col-md-3"><label class="form-label">DPE (étiquette)</label>
            <div class="input-group"><select name="dpe_etiquette" id="${px}_dpe_etiquette" class="form-select"><option value="">--</option>${['A','B','C','D','E','F','G'].map(l=>`<option value="${l}" ${d.dpe_etiquette===l?'selected':''}>${l}</option>`).join('')}</select>
              <button type="button" class="btn btn-outline-secondary" onclick="dpeSearch('${px}')" title="Rechercher le DPE à partir de l'adresse"><i class="fas fa-leaf"></i> Rechercher</button></div></div>
          <div class="col-md-6"><div id="${px}_dpe_box" class="small">${d.dpe_numero?dpeSummary(d):''}</div></div>
        </div></div></div>
      </div>

      <!-- ── TAB 3 FINANCEMENT ── -->
      <div class="tab-pane fade" id="${px}T3">
        <div class="row g-3 mb-3">
          ${money('apport','apport',"Apport (€)",d.apport)}
          <div class="col-md-3">
            <label class="form-label">Garantie</label>
            <select name="garantie_type" id="${px}_gar_type" class="form-select" onchange="onGarantieChange('${px}')">
              <option value="">--</option>
              ${[['CEGC','CEGC'],['SACCEF','SACCEF'],['HYPOTHEQUE','HYPOTHÈQUE']].map(([v,l])=>`<option value="${v}" ${d.garantie_type===v?'selected':''}>${l}</option>`).join('')}
            </select>
          </div>
          ${money('garantie_montant','gar_mt',"Montant des frais de garantie (€)",d.garantie_montant)}
          ${money('tva_financee','tva',"TVA financée à rembourser (€)",d.tva_financee)}
          <div class="col-md-3">
            <label class="form-label"><input type="checkbox" name="frais_midi_epargne" value="1" id="${px}_fme_cb" ${d.frais_midi_epargne==1?'checked':''} onchange="toggleFME('${px}');onFormChange('${px}')"> Frais Midi Épargne</label>
            <div id="${px}_fme_wrap" style="${d.frais_midi_epargne==1?'':'display:none'}">
              <input type="number" step="0.01" name="montant_midi_epargne" id="${px}_fme_mt" class="form-control" value="${d.montant_midi_epargne||0}" oninput="onFormChange('${px}')">
            </div>
          </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <h6 class="mb-0">Lignes de crédit</h6>
          <div class="d-flex align-items-center gap-3">
            <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="doublissimo" value="1" id="${px}_dbl_cb" ${d.doublissimo==1?'checked':''} onchange="onDoublissimo('${px}')"><label class="form-check-label" for="${px}_dbl_cb" title="Ajoute une ligne égale à 20 % de (acquisition + frais de notaire + frais de négociation − apport)">Doublissimo</label></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="equilibrerLignes('${px}')" title="Ajuste la dernière ligne pour couvrir exactement le besoin"><i class="fas fa-equals"></i> Affecter le reste à la dernière ligne</button>
          </div>
        </div>
        <div id="${px}_lignes_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addLigne('${px}')"><i class="fas fa-plus"></i> Ajouter une ligne de crédit</button>

        <hr>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <h6 class="mb-0">Assurance emprunteur <span class="text-muted small">(une ligne par emprunteur et par ligne de crédit)</span></h6>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toutesAssurancesCRD('${px}')" title="Applique la méthode du logiciel de crédit (capital restant dû, mensualité lissée) à toutes les lignes d'assurance"><i class="fas fa-sync-alt"></i> Tout passer en « capital restant dû »</button>
        </div>
        <div id="${px}_ade_list" class="ci-json-list mb-2"></div>
        <div id="${px}_ass_warn"></div>
        <div class="row g-2 align-items-center mb-3"><div class="col-md-5"><label class="form-label small mb-0">Assurance prise en compte dans le TAEG</label>
          <select name="taeg_assurance" class="form-select form-select-sm" onchange="onFormChange('${px}')">
            ${[['MIN','Le moins cher des emprunteurs (comme le logiciel de référence)'],['ALL','Tous les emprunteurs'],['EMP1','Emprunteur 1 seul'],['EMP2','Emprunteur 2 seul']].map(([v,l])=>`<option value="${v}" ${(d.taeg_assurance||'MIN')===v?'selected':''}>${l}</option>`).join('')}
          </select></div></div>

        <hr>
        <div class="row g-3">
          <div class="col-12">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="ptz_actif" value="1" id="${px}_ptz_cb" ${d.ptz_actif==1?'checked':''} onchange="togglePTZ('${px}');onFormChange('${px}')"><label class="form-check-label fw-semibold">PTZ</label></div>
            <div id="${px}_ptz_wrap" class="row g-2 mt-1 ms-3" style="${d.ptz_actif==1?'':'display:none'}">
              <div class="col-md-3"><label class="form-label small">Montant PTZ (€)</label><input type="number" step="0.01" name="ptz_montant" id="${px}_ptz_mt" class="form-control form-control-sm" value="${d.ptz_montant||0}" oninput="onFormChange('${px}')"></div>
              <div class="col-md-3"><label class="form-label small">Durée PTZ (mois)</label><input type="number" name="ptz_duree" id="${px}_ptz_dur" class="form-control form-control-sm" value="${d.ptz_duree||0}" oninput="onFormChange('${px}')"></div>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="ecoptz_actif" value="1" id="${px}_ecoptz_cb" ${d.ecoptz_actif==1?'checked':''} onchange="toggleEcoPTZ('${px}');onFormChange('${px}');updatePiecesEco('${px}')"><label class="form-check-label fw-semibold">EcoPTZ</label></div>
            <div id="${px}_ecoptz_wrap" class="ms-3 mt-1" style="${d.ecoptz_actif==1?'':'display:none'}">
              <div class="row g-2">
                <div class="col-md-3"><label class="form-label small">Montant EcoPTZ (€)</label><input type="number" step="0.01" name="ecoptz_montant" id="${px}_ecoptz_mt" class="form-control form-control-sm" value="${d.ecoptz_montant||0}" oninput="onFormChange('${px}')"></div>
                <div class="col-md-3"><label class="form-label small">Durée EcoPTZ (mois)</label><input type="number" name="ecoptz_duree" id="${px}_ecoptz_dur" class="form-control form-control-sm" value="${d.ecoptz_duree||0}" oninput="onFormChange('${px}')"></div>
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

      <!-- ── TAB 8 MRH ── -->
      <div class="tab-pane fade" id="${px}T8">
        <div class="row g-3 mb-3">
          <div class="col-md-4"><label class="form-label">Montant du devis (€)</label><input type="number" step="0.01" min="0" name="mrh_montant_devis" class="form-control" value="${d.mrh_montant_devis||''}"></div>
          <div class="col-md-4"><label class="form-label">Formule choisie</label><input type="text" name="mrh_formule" class="form-control" maxlength="100" value="${escapeHtml(d.mrh_formule||'')}"></div>
        </div>
        <h6>Options choisies <span class="text-muted small">(une par ligne)</span></h6>
        <div id="${px}_mrh_list" class="ci-json-list mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMrhOption('${px}')"><i class="fas fa-plus"></i> Ajouter une option</button>
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

// ── ADRESSE (autocomplétion BAN) ET DPE ──────────────────────────────────────
function dpeSummary(d){
  const l=d.dpe_etiquette||'–', g=d.dpe_ges||'–';
  return `<i class="fas fa-leaf text-success"></i> DPE <strong>${escapeHtml(l)}</strong> · GES ${escapeHtml(g)}${d.dpe_conso?' · '+escapeHtml(String(d.dpe_conso))+' kWh/m²/an':''}${d.dpe_date?' · établi le '+fmtD(d.dpe_date):''}<div class="text-muted">N° ${escapeHtml(d.dpe_numero||'')}</div>`;
}
let _dpeCache={};
function setupAddress(px){
  const input=document.getElementById(px+'_adresse'), box=document.getElementById(px+'_adr_sugg');
  if(!input||!box) return;
  let timer=null;
  input.addEventListener('input',()=>{
    clearTimeout(timer);
    const v=input.value.trim();
    if(v.length<4){box.style.display='none';return;}
    timer=setTimeout(async()=>{
      try{
        const r=await fetch('https://api-adresse.data.gouv.fr/search/?type=housenumber&limit=6&q='+encodeURIComponent(v));
        const j=await r.json();
        box.innerHTML='';
        (j.features||[]).forEach(f=>{
          const b=document.createElement('button');
          b.type='button'; b.className='list-group-item list-group-item-action'; b.textContent=f.properties.label;
          b.onclick=()=>{
            input.value=f.properties.label; box.style.display='none';
            document.getElementById(px+'_bien_lon').value=f.geometry.coordinates[0];
            document.getElementById(px+'_bien_lat').value=f.geometry.coordinates[1];
            dpeSearch(px);
          };
          box.appendChild(b);
        });
        box.style.display=box.children.length?'block':'none';
      }catch(e){box.style.display='none';}
    },250);
  });
  document.addEventListener('click',e=>{if(!box.contains(e.target)&&e.target!==input) box.style.display='none';});
}
// Récupération du DPE à partir de l'adresse (module DPE : base ADEME avant/après juillet 2021)
async function dpeSearch(px){
  const adr=document.getElementById(px+'_adresse').value.trim();
  const box=document.getElementById(px+'_dpe_box');
  if(adr.length<5){box.innerHTML='<span class="text-muted">Saisissez d\'abord l\'adresse du bien.</span>';return;}
  box.innerHTML='<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Recherche du DPE…</span>';
  try{
    const r=await fetch('../dpe/api.php?mode=address&sources=new,old&q='+encodeURIComponent(adr));
    const j=await r.json();
    if(j.error){box.innerHTML=`<span class="text-danger">${escapeHtml(j.error)}</span>`;return;}
    const list=j.exact||[];
    if(!list.length){box.innerHTML='<span class="text-muted">Aucun DPE trouvé à cette adresse exacte : renseignez l\'étiquette à la main.</span>';return;}
    _dpeCache[px]=list;
    const surf=num(document.getElementById(px+'_surface').value);
    let pick=null;
    if(list.length===1) pick=0;
    else if(surf>0){
      let bd=1e9; list.forEach((x,i)=>{if(x.surface){const dd=Math.abs(x.surface-surf); if(dd<bd){bd=dd;pick=i;}}});
      if(bd>1.5) pick=null; // surface trop différente : on laisse choisir
    }
    renderDpeChoices(px,pick);
    if(pick!==null) applyDpe(px,pick);
  }catch(e){box.innerHTML='<span class="text-danger">Erreur lors de la recherche du DPE.</span>';}
}
function renderDpeChoices(px,sel){
  const list=_dpeCache[px]||[], box=document.getElementById(px+'_dpe_box');
  const col={A:'#319834',B:'#33cc31',C:'#9ccf1f',D:'#d9d900',E:'#fccc05',F:'#fc9935',G:'#fc0205'};
  box.innerHTML=`<div class="mb-1 text-muted">${list.length} DPE à cette adresse — cliquez sur le bon logement :</div>
    <div style="max-height:170px;overflow:auto"><table class="table table-sm table-hover mb-0"><tbody>${list.map((x,i)=>`
      <tr style="cursor:pointer" class="${i===sel?'table-success':''}" onclick="applyDpe('${px}',${i})">
        <td>${fmtD(x.date)}</td><td><span class="badge" style="background:${col[x.etiquette_dpe]||'#999'};color:#fff">${escapeHtml(x.etiquette_dpe||'–')}</span></td>
        <td>${x.surface!=null?Math.round(x.surface*10)/10+' m²':''}</td><td>${escapeHtml(x.complement||x.type||'')}${x.source==='old'?' <span class="badge bg-secondary">avant 07/2021</span>':''}</td></tr>`).join('')}
    </tbody></table></div>`;
}
function applyDpe(px,i){
  const x=(_dpeCache[px]||[])[i]; if(!x) return;
  const set=(id,v)=>{const el=document.getElementById(px+'_'+id); if(el) el.value=v??'';};
  set('dpe_etiquette',x.etiquette_dpe||''); set('dpe_ges',x.etiquette_ges||''); set('dpe_numero',x.numero_dpe||'');
  set('dpe_date',x.date||''); set('dpe_conso',x.conso??'');
  const s=document.getElementById(px+'_surface');
  if(s&&!s.value&&x.surface) s.value=x.surface;
  renderDpeChoices(px,i);
  const box=document.getElementById(px+'_dpe_box');
  box.insertAdjacentHTML('afterbegin','<div class="mb-1">'+dpeSummary({dpe_etiquette:x.etiquette_dpe,dpe_ges:x.etiquette_ges,dpe_conso:x.conso,dpe_date:x.date,dpe_numero:x.numero_dpe})+'</div>');
}

// ── FORM TOGGLES ──────────────────────────────────────────────────────────────
function toggleFME(px){
  const cb=document.getElementById(px+'_fme_cb'), w=document.getElementById(px+'_fme_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
}
function togglePTZ(px){
  const cb=document.getElementById(px+'_ptz_cb'), w=document.getElementById(px+'_ptz_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
  updatePiecesEco(px);
}
function toggleEcoPTZ(px){
  const cb=document.getElementById(px+'_ecoptz_cb'), w=document.getElementById(px+'_ecoptz_wrap');
  if(w) w.style.display=cb?.checked?'':'none';
  updatePiecesEco(px);
}
function onGarantieChange(px){
  const v=document.getElementById(px+'_gar_type')?.value;
  const w=document.getElementById(px+'_cegc_admin_wrap');
  if(w) w.style.display=(v==='CEGC')?'':'none';
  onFormChange(px);
}
function onEcoBouquet(px){
  const cb=document.getElementById(px+'_eco_bq'), pg=document.getElementById(px+'_eco_pg'), wrap=document.getElementById(px+'_eco_bq_wrap');
  if(cb?.checked){if(pg){pg.checked=false;pg.disabled=true;}}
  else{if(pg)pg.disabled=false;}
  if(wrap) wrap.style.display=cb?.checked?'inline-block':'none';
}
function onEcoPerfGlobale(px){
  const pg=document.getElementById(px+'_eco_pg'), bq=document.getElementById(px+'_eco_bq'), wrap=document.getElementById(px+'_eco_bq_wrap');
  if(pg?.checked){if(bq){bq.checked=false;bq.disabled=true;}if(wrap)wrap.style.display='none';}
  else{if(bq)bq.disabled=false;}
}
function toggleMotif(px){
  const ko=document.getElementById(px+'_conf_ko'), w=document.getElementById(px+'_motif_wrap');
  if(w) w.style.display=ko?.checked?'':'none';
}
function updatePiecesEco(px){
  const ptz=document.getElementById(px+'_ptz_cb')?.checked, eco=document.getElementById(px+'_ecoptz_cb')?.checked;
  const w=document.getElementById(px+'_pieces_eco');
  if(w) w.style.display=(ptz||eco)?'':'none';
  const nb=parseInt(document.getElementById(px+'_eco_nb_bq')?.value||0);
  const lbl=document.getElementById(px+'_ent_label');
  if(lbl) lbl.textContent='Formulaire entreprises'+(nb>0?' ('+nb+')':'');
}
function calcJ11live(px){
  const j11=addJ11(document.getElementById(px+'_accuse')?.value);
  const disp=document.getElementById(px+'_j11_display'), hid=document.getElementById(px+'_j11_hidden');
  if(disp) disp.value=j11;
  if(hid) hid.value=j11;
}
function beforeSubmit(px){
  serializeAll(px);
  const form=document.getElementById(px+'_form'); if(!form) return;
  const r=form.querySelector('[name="cegc_reponse_admin"]:checked');
  if(r){
    ['suivi_cegc_accord','suivi_cegc_refus'].forEach(n=>{
      let h=form.querySelector(`input[type=hidden][name="${n}"]`);
      if(!h){h=document.createElement('input');h.type='hidden';h.name=n;form.appendChild(h);}
      h.value=(n==='suivi_cegc_accord'?r.value==='ACCORD':r.value==='REFUS')?1:0;
    });
  }
}

// ── OPEN ADD / EDIT ───────────────────────────────────────────────────────────
function initForm(px,d){
  const emps=getEmprunteurs(d);
  renderEmprunteurs(px,emps);
  // Lignes : nouveau dossier = une ligne vide ; ancien dossier = ligne reconstituée depuis l'ancien crédit unique
  renderLignes(px,d.id?getLignes(d):[{libelle:'Ligne 1',montant:'',duree:'',taux:'',frais_dossier:''}]);
  const ades=getAssurances(d).map((a,i)=>('emp' in a)?a:{...a,emp:Math.min(i,emps.length-1),ligne:0});
  renderAssurances(px,ades);
  document.getElementById(px+'_mrh_list').innerHTML=parseArr(d.mrh_options_json).map(o=>buildMrhOption(px,o)).join('');
  setupAddress(px);
  renderEnfants(px,parseArr(d.enfants_ages_json));
}
function openAddModal(){
  document.getElementById('addContent').innerHTML=buildFormTabs('add',{});
  initForm('add',{});
  new bootstrap.Modal(document.getElementById('addModal')).show();
}
function editDossier(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  document.getElementById('editContent').innerHTML=buildFormTabs('edit',d);
  initForm('edit',d);
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
  const c=computeAll(d);
  const wf=d.workflow_status||'etude';
  const money=v=>fmt(v)+' €';
  const bdge=v=>v?`<span class="badge ${v==='OK'?'bg-success':'bg-danger'}">${v}</span>`:'<span class="text-muted">Non interrogé</span>';

  let html=`<div class="mb-3">${buildWorkflowProgress(wf)}</div>${alertEndett(c.te)}`;
  html+=`<ul class="nav nav-tabs mb-3"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#dT1">Client</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT2">Projet</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT3">Financement</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT4">Gestion admin</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT5">Pièces</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT6">Suivi</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT8">MRH</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dT7" onclick="loadNotes(${id},'detail')">Notes</a></li>
  </ul><div class="tab-content">`;

  // Tab Client
  const ages=parseArr(d.enfants_ages_json).filter(a=>a!==null&&a!=='');
  const rowsT=(arr,cols)=>arr.length?`<table class="table table-sm table-striped mb-2"><tbody>${arr.map(cols).join('')}</tbody></table>`:'<p class="text-muted small mb-2">Aucun élément</p>';
  html+=`<div class="tab-pane fade show active" id="dT1">
    <table class="table table-sm mb-3"><tbody>
      <tr><td>N° personne</td><td><strong>${escapeHtml(d.numero_personne)}</strong></td></tr>
      <tr><td>Type client</td><td>${escapeHtml(d.type_client)}</td></tr>
      <tr><td>Primo accédant</td><td>${escapeHtml(PRIMO_LABELS[primoStatut(d)]||'—')}</td></tr>
      <tr><td>Statut d'occupation actuel</td><td>${escapeHtml(OCC_LABELS[d.statut_occupation]||'—')}</td></tr>
      <tr><td>Personnes dans le foyer</td><td>${d.nb_personnes_foyer??'—'}</td></tr>
      <tr><td>Enfants</td><td>${d.nb_enfants??'—'}${ages.length?' (âges : '+ages.map(a=>escapeHtml(String(a))).join(', ')+' ans)':''}</td></tr>
      <tr><td>Personnes supplémentaires à charge</td><td>${d.nb_personnes_charge_supp??'—'}</td></tr>
      <tr><td>Revenu fiscal de référence cumulé</td><td><strong>${money(c.rfr)}</strong></td></tr>
    </tbody></table>
    <div class="row g-3">${c.emps.map((e,i)=>`<div class="col-md-6"><div class="card h-100"><div class="card-header"><strong>Emprunteur ${i+1}${e.nom?' — '+escapeHtml(e.nom):''}</strong></div><div class="card-body">
      <div class="small mb-2">BdF : ${bdge(e.bdf)} · DRC : ${bdge(e.drc)} · TopCC : ${bdge(e.topcc)} · RFR : <strong>${money(num(e.rfr))}</strong></div>
      <h6>Revenus <small class="text-muted">(${money(sumRevenus(e.revenus))}/mois)</small></h6>
      ${rowsT(e.revenus||[],r=>`<tr><td>${escapeHtml(r.intitule)}</td><td>${money(num(r.montant))}${r.periodicite==='annuelle'?' /an':' /mois'}</td><td>${r.revenu_futur?'Futur '+(r.ponderation||100)+'%':''}</td></tr>`)}
      <h6>Charges <small class="text-muted">(${money(sumCharges(e.charges))}/mois conservées)</small></h6>
      ${rowsT(e.charges||[],r=>`<tr class="${r.non_conserve?'text-muted':''}"><td>${escapeHtml(r.intitule)}</td><td>${money(num(r.montant))}</td><td>${r.non_conserve?'<span class="badge bg-secondary">Exclu</span>':''}</td></tr>`)}
      <h6>Épargne</h6>
      ${rowsT(e.epargne||[],r=>`<tr><td>${escapeHtml(r.intitule)}</td><td>${money(num(r.montant))}</td><td>${r.hors_cemp?escapeHtml(r.nom_banque||'Hors CEMP'):''}</td></tr>`)}
    </div></div></div>`).join('')}</div>
  </div>`;

  // Tab Projet
  const dpeTxt=d.dpe_etiquette?`${escapeHtml(d.dpe_etiquette)}${d.dpe_ges?' · GES '+escapeHtml(d.dpe_ges):''}${d.dpe_conso?' · '+escapeHtml(String(d.dpe_conso))+' kWh/m²/an':''}${d.dpe_date?' · du '+fmtD(d.dpe_date):''}${d.dpe_numero?' (n° '+escapeHtml(d.dpe_numero)+')':''}`:'—';
  html+=`<div class="tab-pane fade" id="dT2"><div class="row g-3"><div class="col-md-6"><table class="table table-sm"><tbody>
    <tr><td>Type de projet</td><td>${escapeHtml(TYPE_PROJET_LABELS[d.type_projet]||'—')}</td></tr>
    <tr><td>Usage</td><td>${escapeHtml(USAGE_LABELS[usageChoice(d)]||'—')}</td></tr>
    <tr><td>Mode d'occupation</td><td>${escapeHtml(MODE_OCC_LABELS[modeOcc(d)]||'—')}</td></tr>
    <tr><td colspan="2" class="fw-bold bg-light">Montants</td></tr>
    <tr><td>Acquisition</td><td>${money(num(d.montant_acquisition))}</td></tr>
    <tr><td>Dont mobilier financable</td><td>${money(num(d.dont_mobilier_financable))}</td></tr>
    <tr><td>Frais de notaire</td><td>${money(num(d.frais_notaire))}</td></tr>
    <tr><td>Frais de négociation (agence)</td><td>${money(num(d.frais_negociation)+num(d.frais_agence))}</td></tr>
    <tr><td>Frais divers</td><td>${money(num(d.frais_divers))}</td></tr>
  </tbody></table></div><div class="col-md-6"><table class="table table-sm"><tbody>
    <tr><td colspan="2" class="fw-bold bg-light">Le bien</td></tr>
    <tr><td>Localisation</td><td>${escapeHtml(d.adresse_bien||'—')}</td></tr>
    <tr><td>Type d'acquisition</td><td>${escapeHtml(TYPE_ACQ_LABELS[d.type_acquisition]||'—')}</td></tr>
    <tr><td>Type de propriété</td><td>${escapeHtml(TYPE_PROP_LABELS[d.type_propriete]||'—')}</td></tr>
    <tr><td>Type de logement</td><td>${escapeHtml(d.type_logement||'—')}</td></tr>
    <tr><td>Nombre de logements</td><td>${d.nb_logements??'—'}</td></tr>
    <tr><td>Surface habitable</td><td>${d.surface_habitable?escapeHtml(String(d.surface_habitable))+' m²':'—'}</td></tr>
    <tr><td>Fin de construction</td><td>${fmtD(d.date_fin_construction)}</td></tr>
    <tr><td>DPE</td><td>${dpeTxt}</td></tr>
  </tbody></table></div></div></div>`;

  // Tab Financement
  const lignesRows=c.lignes.map((l,i)=>`<tr><td>${escapeHtml(l.libelle||('Ligne '+(i+1)))}${l.doublissimo?' <span class="badge bg-info">Doublissimo</span>':''}</td><td>${money(num(l.montant))}</td><td>${parseInt(l.duree)||0} mois</td><td>${num(l.taux).toFixed(3).replace('.',',')} %</td><td>${money(num(l.frais_dossier))}</td><td><strong>${money(c.sch[i].mens)}</strong></td><td><strong>${money(c.sch[i].mens+ligneAssuranceMensuelle(c,i))}</strong></td><td>${taegTxt(c.taegLignes[i])}${c.taegLignes[i]?`<div class="small text-muted">simple ${parseFloat(c.taegLignes[i].prop).toFixed(2).replace('.',',')} %</div>`:''}</td><td>${money(c.sch[i].interets)}</td></tr>`).join('');
  const assRows=getAssurances(d).map((a,i)=>{
    const cost=c.ass[i]||{monthly:0,total:0};
    return `<tr><td>${escapeHtml((c.emps[a.emp??0]?.nom)||('Emprunteur '+((a.emp??0)+1)))} — ${escapeHtml((c.lignes[a.ligne??0]?.libelle)||('Ligne '+((a.ligne??0)+1)))}</td>
      <td>${a.taux!==''&&a.taux!=null?num(a.taux).toFixed(3).replace('.',',')+' % ('+(a.base==='CRD'?'CRD':'CI')+')':'—'}</td><td>${num(a.quotite)||100} %</td>
      <td>${escapeHtml((a.couverture||[]).join(', ')||'—')}</td><td>${escapeHtml([a.type,a.franchise,a.ipp].filter(Boolean).join(' · ')||'—')}</td>
      <td>${money(cost.monthly)}</td><td>${money(cost.total)}</td></tr>`;}).join('');
  html+=`<div class="tab-pane fade" id="dT3">
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${money(c.totalFin)}</div><div class="stat-label">Total à financer</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${money(c.mensHorsAssur)}</div><div class="stat-label">Mensualité hors assurance</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${money(c.mensTout)}</div><div class="stat-label">Mensualité tout inclus (assurance ${money(c.mensAssur)})</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${badgeEndett(c.te)}</div><div class="stat-label">Taux d'endettement (assurance incluse)</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${taegTxt(c.taegGlobal)}</div><div class="stat-label">TAEG global (assurance, frais de dossier et garantie inclus)</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${money(c.reste)}</div><div class="stat-label">Reste à vivre cumulé</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${c.restePers===null?'N/A':money(c.restePers)}</div><div class="stat-label">Reste à vivre / personne (${c.nbPers})</div></div></div>
      <div class="col-md-6"><div class="stat-card" style="border-left-color:#1B6234"><div class="stat-number">${money(c.coutCredit)}</div><div class="stat-label">Coût total du crédit (intérêts ${money(c.interets)} + assurances ${money(c.totAssur)} + frais de dossier ${money(c.fraisDossier)} + garantie ${money(c.garantie)})</div></div></div>
    </div>
    <div class="row g-3"><div class="col-md-5"><table class="table table-sm"><tbody>
      <tr><td>Montant du projet (acquisition + notaire + négociation)</td><td>${money(getCoutProjet(d))}</td></tr>
      <tr><td>Apport</td><td>${money(num(d.apport))}</td></tr>
      <tr><td>Montant financé (après apport)</td><td>${money(c.totalFin-num(d.apport)-ptzMontant(d)-ecoMontant(d))}</td></tr>
      <tr><td>Frais de garantie (${escapeHtml(d.garantie_type||'—')})</td><td>${money(num(d.garantie_montant))}</td></tr>
      ${d.frais_midi_epargne==1?`<tr><td>Frais Midi Épargne</td><td>${money(num(d.montant_midi_epargne))}</td></tr>`:''}
      <tr><td>TVA financée</td><td>${money(num(d.tva_financee))}</td></tr>
      <tr><td>Reste à financer</td><td>${money(c.resteAFin)}</td></tr>
      ${d.doublissimo==1?'<tr><td>Doublissimo</td><td>Oui</td></tr>':''}
    </tbody></table>
    ${d.ptz_actif==1?`<div class="alert alert-info py-1">PTZ : ${money(num(d.ptz_montant))} / ${d.ptz_duree} mois → mensualité : ${money(c.mensPTZ)}</div>`:''}
    ${d.ecoptz_actif==1?`<div class="alert alert-info py-1">EcoPTZ : ${money(num(d.ecoptz_montant))} / ${d.ecoptz_duree} mois → mensualité : ${money(c.mensEco)}</div>`:''}
    </div><div class="col-md-7">
      <h6>Lignes de crédit</h6>
      <table class="table table-sm table-striped"><thead><tr><th>Ligne</th><th>Montant</th><th>Durée</th><th>Taux</th><th>Frais dossier</th><th>Mensualité hors ass.</th><th>Mensualité avec ass.</th><th>TAEG</th><th>Intérêts</th></tr></thead><tbody>${lignesRows}</tbody></table>
    </div></div>
    <h6>Assurance emprunteur</h6>
    ${assRows?`<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Assuré — ligne</th><th>Taux</th><th>Quotité</th><th>Garanties</th><th>Détail</th><th>Mensualité</th><th>Coût total</th></tr></thead><tbody>${assRows}</tbody></table></div>`:'<p class="text-muted small">Aucune assurance renseignée</p>'}
    ${alertEndett(c.te)}
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


  // Tab MRH
  const mrhOpts=parseArr(d.mrh_options_json);
  html+=`<div class="tab-pane fade" id="dT8"><table class="table table-sm"><tbody>
    <tr><td>Montant du devis</td><td>${d.mrh_montant_devis?money(num(d.mrh_montant_devis)):'—'}</td></tr>
    <tr><td>Formule choisie</td><td>${escapeHtml(d.mrh_formule||'—')}</td></tr>
    <tr><td>Options choisies</td><td>${mrhOpts.length?'<ul class="mb-0 ps-3">'+mrhOpts.map(o=>`<li>${escapeHtml(o)}</li>`).join('')+'</ul>':'—'}</td></tr>
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
// Échéancier cumulé de toutes les lignes (crédits, PTZ, EcoPTZ) avec cotisation d'assurance
function allScheduleLines(d){
  const c=computeAll(d);
  const lines=c.lignes.map((l,i)=>({label:l.libelle||('Ligne '+(i+1)),s:c.sch[i],ligneIdx:i,montant:num(l.montant)}));
  if(d.ptz_actif==1&&num(d.ptz_duree)>0) lines.push({label:'PTZ',s:scheduleLine(num(d.ptz_montant),0,d.ptz_duree),ligneIdx:null,montant:num(d.ptz_montant)});
  if(d.ecoptz_actif==1&&num(d.ecoptz_duree)>0) lines.push({label:'EcoPTZ',s:scheduleLine(num(d.ecoptz_montant),0,d.ecoptz_duree),ligneIdx:null,montant:num(d.ecoptz_montant)});
  return {c,lines};
}
function assuranceByMonth(d,c,ligneIdx,m){ // cotisation d'assurance du mois m pour une ligne (constante sur la durée de la ligne)
  if(m>(parseInt(c.lignes[ligneIdx]?.duree)||0)) return 0;
  return c.ass.reduce((t,a,k)=>t+(((c.assRaw[k].ligne)??0)===ligneIdx?a.monthly:0),0);
}
function showAmortissement(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const {c,lines}=allScheduleLines(d);
  const nMax=Math.max(0,...lines.map(l=>l.s.rows.length));
  if(!nMax||c.capital<=0){
    document.getElementById('amortContent').innerHTML='<div class="alert alert-warning">Données insuffisantes.</div>';
    new bootstrap.Modal(document.getElementById('amortModal')).show();return;
  }
  let rows='',ti=0,tc=0,ta=0,tt=0;
  for(let m=1;m<=nMax;m++){
    let cap=0,int=0,mens=0,ass=0,crd=0;
    lines.forEach(l=>{
      const r=l.s.rows[m-1]; if(!r) return;
      cap+=r.capital;int+=r.interet;mens+=l.s.mens;crd+=Math.max(0,r.crd-r.capital);
      if(l.ligneIdx!==null) ass+=assuranceByMonth(d,c,l.ligneIdx,m);
    });
    ti+=int;tc+=cap;ta+=ass;tt+=mens+ass;
    rows+=`<tr><td>${m}</td><td>${fmt(mens)}</td><td>${fmt(cap)}</td><td>${fmt(int)}</td><td>${fmt(ass)}</td><td>${fmt(mens+ass)}</td><td>${fmt(crd)}</td></tr>`;
  }
  document.getElementById('amortContent').innerHTML=`
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(c.capital+ptzMontant(d)+ecoMontant(d))} €</div><div class="stat-label">Capital emprunté (toutes lignes)</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(ti)} €</div><div class="stat-label">Coût total intérêts</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(ta)} €</div><div class="stat-label">Coût total assurances</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(tt)} €</div><div class="stat-label">Total remboursé (assurances incluses)</div></div></div>
    </div>
    <p class="text-muted small">Lignes prises en compte : ${lines.map(l=>escapeHtml(l.label)).join(', ')}</p>
    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
      <table class="table table-sm table-striped">
        <thead class="table-dark" style="position:sticky;top:0"><tr><th>Mois</th><th>Mensualité</th><th>Capital</th><th>Intérêts</th><th>Assurance</th><th>Total mois</th><th>Restant dû</th></tr></thead>
        <tbody>${rows}</tbody>
        <tfoot class="table-secondary"><tr><td><strong>Total</strong></td><td>—</td><td><strong>${fmt(tc)}</strong></td><td><strong>${fmt(ti)}</strong></td><td><strong>${fmt(ta)}</strong></td><td><strong>${fmt(tt)}</strong></td><td>—</td></tr></tfoot>
      </table>
    </div>`;
  new bootstrap.Modal(document.getElementById('amortModal')).show();
}

// ── SIMULATION « ET SI… » (porte sur la première ligne de crédit) ─────────────
function simDossier(d,taux,duree,ra){
  const lignes=getLignes(d).map(l=>({...l}));
  lignes[0]={...lignes[0],taux,duree,montant:Math.max(0,num(lignes[0].montant)-ra)};
  return {...d,lignes_credit_json:JSON.stringify(lignes)};
}
function showSimulation(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const l0=getLignes(d)[0];
  document.getElementById('simulContent').innerHTML=`
    <h6>${escapeHtml(d.numero_personne)} — simulation sur « ${escapeHtml(l0.libelle||'Ligne 1')} » (${fmt(l0.montant)} €)</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">Taux (%)</label>
        <input type="range" class="form-range" id="simTaux" min="0" max="8" step="0.1" value="${num(l0.taux)}" oninput="updateSim(${id})">
        <div class="text-center fw-bold" id="simTauxVal">${num(l0.taux)} %</div></div>
      <div class="col-md-4"><label class="form-label">Durée (mois)</label>
        <input type="range" class="form-range" id="simDuree" min="60" max="360" step="12" value="${parseInt(l0.duree)||0}" oninput="updateSim(${id})">
        <div class="text-center fw-bold" id="simDureeVal">${parseInt(l0.duree)||0} mois</div></div>
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
  const ra=num(document.getElementById('simRa')?.value);
  const taux=num(document.getElementById('simTaux')?.value);
  const duree=parseInt(document.getElementById('simDuree')?.value)||0;
  document.getElementById('simTauxVal').textContent=taux+' %';
  document.getElementById('simDureeVal').textContent=duree+' mois ('+Math.round(duree/12)+' ans)';
  const base=computeAll(d), sim=computeAll(simDossier(d,taux,duree,ra));
  const diff=sim.mensTout-base.mensTout;
  document.getElementById('simResults').innerHTML=`
    <div class="row g-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(sim.mensTout)} €</div><div class="stat-label">Mensualité tout inclus</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${fmt(sim.coutCredit)} €</div><div class="stat-label">Coût total du crédit</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number">${badgeEndett(sim.te)}</div><div class="stat-label">Endettement</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-number" style="color:${diff>0?'#dc3545':'#28a745'}">${diff>0?'+':''}${fmt(diff)} €</div><div class="stat-label">Diff. mensualité</div></div></div>
    </div>${alertEndett(sim.te)}`;
  let rows='';
  for(let t=taux;t<=taux+2.001;t+=0.5){
    const s=computeAll(simDossier(d,t,duree,ra));
    rows+=`<tr><td>${t.toFixed(1)} %</td><td>${fmt(s.mensTout)} €</td><td>${fmt(s.coutCredit)} €</td><td>${badgeEndett(s.te)}</td></tr>`;
  }
  document.getElementById('simTable').innerHTML=`<table class="table table-sm table-striped"><thead><tr><th>Taux</th><th>Mensualité tout inclus</th><th>Coût total crédit</th><th>Endettement</th></tr></thead><tbody>${rows}</tbody></table>`;
}

// ── COMPARAISON ───────────────────────────────────────────────────────────────
function showCompareSelect(){
  if(dossiersData.length<2){alert('Il faut au moins 2 dossiers.');return;}
  document.getElementById('compareContent').innerHTML=`
    <p>Sélectionnez les dossiers à comparer :</p>
    ${dossiersData.map(d=>`<div class="form-check"><input class="form-check-input compare-chk" type="checkbox" value="${d.id}"><label class="form-check-label">${escapeHtml(d.numero_personne)} — ${fmt(computeAll(d).capital)} € empruntés</label></div>`).join('')}
    <button class="btn btn-ce mt-3" onclick="runComparison()"><i class="fas fa-balance-scale"></i> Comparer</button>
    <div id="compareResults" class="mt-3"></div>`;
  new bootstrap.Modal(document.getElementById('compareModal')).show();
}
function runComparison(){
  const ids=Array.from(document.querySelectorAll('.compare-chk:checked')).map(c=>parseInt(c.value));
  if(ids.length<2){alert('Sélectionnez au moins 2 dossiers.');return;}
  const ds=ids.map(id=>dossiersData.find(d=>d.id==id)).filter(Boolean);
  const cs=ds.map(computeAll);
  const headers='<th>Critère</th>'+ds.map(d=>`<th>${escapeHtml(d.numero_personne)}</th>`).join('');
  function row(label,vals,hi){
    const best=hi==='min'?Math.min(...vals):hi==='max'?Math.max(...vals):null;
    return `<tr><td><strong>${label}</strong></td>${vals.map(v=>`<td${best!==null&&v===best?' class="table-success"':''}>${fmt(v)} €</td>`).join('')}</tr>`;
  }
  document.getElementById('compareResults').innerHTML=`
    <table class="table table-sm table-bordered">
      <thead class="table-dark"><tr>${headers}</tr></thead>
      <tbody>
        ${row('Capital emprunté',cs.map(c=>c.capital),null)}
        <tr><td><strong>Lignes de crédit</strong></td>${cs.map(c=>`<td>${c.lignes.length}</td>`).join('')}</tr>
        ${row('Mensualité hors assurance',cs.map(c=>c.mensHorsAssur),'min')}
        ${row('Mensualité tout inclus',cs.map(c=>c.mensTout),'min')}
        ${row('Total intérêts',cs.map(c=>c.interets),'min')}
        ${row('Coût total du crédit',cs.map(c=>c.coutCredit),'min')}
        ${row('Reste à vivre cumulé',cs.map(c=>c.reste),'max')}
        <tr><td><strong>TAEG global</strong></td>${cs.map(c=>`<td>${taegTxt(c.taegGlobal)}</td>`).join('')}</tr>
        <tr><td><strong>Endettement</strong></td>${cs.map(c=>`<td>${badgeEndett(c.te)}</td>`).join('')}</tr>
      </tbody>
    </table>`;
}

// ── PRINT ─────────────────────────────────────────────────────────────────────
function printDossier(id){
  const d=dossiersData.find(x=>x.id==id);
  if(!d) return;
  const c=computeAll(d);
  const wfLabel=(workflowLabels[d.workflow_status]||['—'])[0];
  const teClass=c.te===null?'pr-kpi-ok':c.te<=33?'pr-kpi-ok':c.te<=35?'pr-kpi-warn':'pr-kpi-danger';
  const p=(l,v)=>`<tr><td class="lbl">${l}</td><td class="val">${v}</td></tr>`;
  const m=v=>fmt(v)+' €';
  const ages=parseArr(d.enfants_ages_json).filter(a=>a!==null&&a!=='');
  const bd=e=>`BdF ${e.bdf||'—'} / DRC ${e.drc||'—'} / TopCC ${e.topcc||'—'}`;

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
      ${c.emps.map((e,i)=>p('Emprunteur '+(i+1)+(e.nom?' – '+escapeHtml(e.nom):''),bd(e)+' · RFR '+m(num(e.rfr)))).join('')}
      ${p('RFR cumulé',m(c.rfr))}
      ${p('Primo accédant',escapeHtml(PRIMO_LABELS[primoStatut(d)]||'—'))}
      ${p('Statut occupation',escapeHtml(OCC_LABELS[d.statut_occupation]||'—'))}
      ${p('Foyer / Enfants / Charge supp.',(d.nb_personnes_foyer??'—')+' / '+(d.nb_enfants??'—')+(ages.length?' ('+ages.join(', ')+' ans)':'')+' / '+(d.nb_personnes_charge_supp??'—'))}
    </tbody></table></div>
    <div class="pr-section"><div class="pr-section-title">PROJET</div><table><tbody>
      ${p('Type de projet',escapeHtml(TYPE_PROJET_LABELS[d.type_projet]||'—'))}
      ${p('Usage / Occupation',escapeHtml((USAGE_LABELS[usageChoice(d)]||'—')+' / '+(MODE_OCC_LABELS[modeOcc(d)]||'—')))}
      ${p('Bien',escapeHtml([TYPE_ACQ_LABELS[d.type_acquisition],d.type_logement,TYPE_PROP_LABELS[d.type_propriete]].filter(Boolean).join(' · ')||'—'))}
      ${p('Adresse',escapeHtml(d.adresse_bien||'—'))}
      ${p('Surface / Logements',(d.surface_habitable?escapeHtml(String(d.surface_habitable))+' m²':'—')+' / '+(d.nb_logements??'—'))}
      ${p('Fin de construction',fmtD(d.date_fin_construction))}
      ${p('DPE',d.dpe_etiquette?escapeHtml(d.dpe_etiquette)+(d.dpe_ges?' (GES '+escapeHtml(d.dpe_ges)+')':''):'—')}
    </tbody></table></div></div>
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">REVENUS / CHARGES</div>
      <table><tbody>
        ${c.emps.map((e,i)=>`<tr><td class="pr-sub-title" colspan="2">Emprunteur ${i+1}</td></tr>
          ${(e.revenus||[]).map(r=>p(escapeHtml(r.intitule)+(r.revenu_futur?' (futur, '+(r.ponderation||100)+'%)':''),fmt(r.montant)+' €'+(r.periodicite==='annuelle'?' /an':' /mois'))).join('')}
          ${(e.charges||[]).filter(x=>!x.non_conserve).map(x=>p('Charge : '+escapeHtml(x.intitule),m(num(x.montant)))).join('')}`).join('')}
      </tbody></table>
      <div class="pr-kpi-row">
        <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.revenus)}</div><div class="pr-kpi-lbl">Revenus effectifs/mois</div></div>
        <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.charges)}</div><div class="pr-kpi-lbl">Charges conservées</div></div>
      </div>
      <div class="pr-kpi-row">
        <div class="pr-kpi ${teClass}"><div class="pr-kpi-val">${pct2(c.te)}</div><div class="pr-kpi-lbl">Taux d'endettement (assurance incluse)</div></div>
        <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.reste)}</div><div class="pr-kpi-lbl">Reste à vivre cumulé</div></div>
        <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${c.restePers===null?'N/A':m(c.restePers)}</div><div class="pr-kpi-lbl">Reste à vivre / pers. (${c.nbPers})</div></div>
      </div>
    </div></div>
  </div>
  <div class="pr-cols">
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">PLAN DE FINANCEMENT</div><table><tbody>
      ${p('Acquisition',m(num(d.montant_acquisition)))}
      ${num(d.dont_mobilier_financable)>0?p('Dont mobilier financable',m(num(d.dont_mobilier_financable))):''}
      ${p('Frais de notaire',m(num(d.frais_notaire)))}
      ${num(d.frais_negociation)+num(d.frais_agence)>0?p('Frais de négociation',m(num(d.frais_negociation)+num(d.frais_agence))):''}
      ${num(d.frais_divers)>0?p('Frais divers',m(num(d.frais_divers))):''}
      ${d.frais_midi_epargne==1?p('Frais Midi Épargne',m(num(d.montant_midi_epargne))):''}
      ${num(d.tva_financee)>0?p('TVA financée',m(num(d.tva_financee))):''}
      ${p('Garantie '+escapeHtml(d.garantie_type||'—'),m(num(d.garantie_montant)))}
      ${p('Frais de dossier',m(c.fraisDossier))}
      ${p('Apport',m(num(d.apport)))}
    </tbody></table>
    <div class="pr-kpi-row">
      <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.totalFin)}</div><div class="pr-kpi-lbl">Total à financer</div></div>
      <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.capital)}</div><div class="pr-kpi-lbl">Capital emprunté</div></div>
    </div></div></div>
    <div class="pr-col"><div class="pr-section"><div class="pr-section-title">CONDITIONS CRÉDIT</div><table><tbody>
      ${c.lignes.map((l,i)=>p(escapeHtml(l.libelle||('Ligne '+(i+1)))+(l.doublissimo?' (Doublissimo)':''),m(num(l.montant))+' · '+(parseInt(l.duree)||0)+' m · '+num(l.taux).toFixed(3).replace('.',',')+' % → '+m(c.sch[i].mens)+'/mois · TAEG '+taegTxt(c.taegLignes[i]))).join('')}
      ${d.ptz_actif==1?p('PTZ',m(num(d.ptz_montant))+' / '+d.ptz_duree+' mois → '+m(c.mensPTZ)+'/mois'):''}
      ${d.ecoptz_actif==1?p('EcoPTZ',m(num(d.ecoptz_montant))+' / '+d.ecoptz_duree+' mois → '+m(c.mensEco)+'/mois'):''}
      ${getAssurances(d).map((a,i)=>p('Assurance '+escapeHtml((c.emps[a.emp??0]?.nom)||('Empr. '+((a.emp??0)+1)))+' / L'+((a.ligne??0)+1)+' ('+escapeHtml((a.couverture||[]).join('+')||'—')+' '+(num(a.quotite)||100)+'%)',(a.taux!==''&&a.taux!=null?num(a.taux).toFixed(3).replace('.',',')+' % → ':'')+m(c.ass[i].monthly)+'/mois')).join('')}
      ${d.mrh_formule||d.mrh_montant_devis?p('MRH',escapeHtml(d.mrh_formule||'—')+(d.mrh_montant_devis?' · devis '+m(num(d.mrh_montant_devis)):'')):''}
    </tbody></table>
    <div class="pr-kpi-row">
      <div class="pr-kpi pr-kpi-primary"><div class="pr-kpi-val">${m(c.mensHorsAssur)}</div><div class="pr-kpi-lbl">Mensualité hors assurance</div></div>
      <div class="pr-kpi pr-kpi-primary"><div class="pr-kpi-val">${m(c.mensTout)}</div><div class="pr-kpi-lbl">Mensualité tout inclus</div></div>
    </div>
    <div class="pr-kpi-row">
      <div class="pr-kpi pr-kpi-primary"><div class="pr-kpi-val">${taegTxt(c.taegGlobal)}</div><div class="pr-kpi-lbl">TAEG global</div></div>
      <div class="pr-kpi pr-kpi-green"><div class="pr-kpi-val">${m(c.coutCredit)}</div><div class="pr-kpi-lbl">Coût total du crédit (intérêts ${m(c.interets)}, assurances ${m(c.totAssur)}, frais ${m(c.fraisDossier+c.garantie)})</div></div>
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
