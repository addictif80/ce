// ── RELANCES, CONTRÔLES DE COHÉRENCE ET TABLEAU DE BORD (module crédit immobilier) ─────────────
const CI_SEUILS={ade:7,cegc:7,conformite:5,ar:10,inactif:15,tauxMax:(typeof ciBaremes!=='undefined'&&ciBaremes.tauxEndettementMax)||35}; // délais en jours, endettement max en %
const CI_DOCS={doc_ji:"Justificatif d'identité",doc_jd:'Justificatif de domicile',doc_ir:'Justificatif de revenus',doc_contrat_travail:'Contrat de travail',doc_bulletins_salaire:'Bulletins de salaire',doc_justif_propriete:'Justif. patrimoine immobilier',doc_releves_externes:'Relevés externes',doc_epargnes_externes:'Épargnes externes',doc_devis:'Devis'};
const CI_WF_ORDER=['etude','dossier_complet','synthese_envoyee','controle','edition_offres','envoi_signature','offre_signee','deblocage','termine'];
const ciTs=s=>{if(!s)return null;const t=new Date(String(s).replace(' ','T'));return isNaN(t)?null:t;};
const ciDays=(a,b)=>{const x=ciTs(a),y=b?ciTs(b):new Date();return(x&&y)?Math.floor((y-x)/86400000):null;};
const ciWfIdx=d=>CI_WF_ORDER.indexOf(d.workflow_status||'etude');

// Relances : étapes en attente d'un retour depuis trop longtemps, signature à échéance, dossier inactif
function ciRelances(d){
  const wf=d.workflow_status||'etude';
  if(wf==='termine'||wf==='refuse') return [];
  const r=[], push=(niv,txt)=>r.push({niv,txt});
  const att=(debut,fin,seuil,lib)=>{
    if(!debut||fin) return;
    const j=ciDays(debut); if(j!==null&&j>=seuil) push(j>=2*seuil?'danger':'warning',lib+' depuis le '+fmtD(debut)+' : '+j+' j sans retour');
  };
  att(d.ade_envoyee_le,d.ade_retour_le,CI_SEUILS.ade,'ADE envoyée');
  if(d.garantie_type==='CEGC') att(d.suivi_date_demande_cegc,d.suivi_date_retour_cegc,CI_SEUILS.cegc,'Demande CEGC');
  att(d.suivi_date_envoi_conformite,d.suivi_date_retour_conformite,CI_SEUILS.conformite,'Conformité envoyée');
  att(d.suivi_date_edition_offres_dt,d.suivi_date_accuse_reception,CI_SEUILS.ar,'Offres éditées, accusé de réception attendu');
  if(d.suivi_date_j11&&!d.suivi_date_signature_definitive){
    const j=ciDays(d.suivi_date_j11); if(j!==null&&j>=0) push(j>=7?'danger':'warning','Signature définitive possible depuis le '+fmtD(d.suivi_date_j11)+' ('+j+' j)');
  }
  if(d.date_signature_notaire_prev&&!d.suivi_date_signature_definitive){
    const j=ciDays(d.date_signature_notaire_prev); if(j!==null&&j>0) push('danger','Signature chez le notaire prévue le '+fmtD(d.date_signature_notaire_prev)+' : non enregistrée');
  }
  const inact=ciDays(d.updated_at||d.created_at);
  if(inact!==null&&inact>=CI_SEUILS.inactif) push('warning','Aucune modification depuis '+inact+' j');
  return r;
}

// Contrôles de cohérence : erreurs (à corriger) et alertes (à vérifier)
function ciControles(d){
  const c=computeAll(d), out=[], err=t=>out.push({niv:'erreur',txt:t}), al=t=>out.push({niv:'alerte',txt:t});
  const nomE=i=>c.emps[i]&&c.emps[i].nom?c.emps[i].nom:'Emprunteur '+(i+1);
  const nomL=i=>c.lignes[i]&&c.lignes[i].libelle?c.lignes[i].libelle:'Ligne '+(i+1);
  if(!d.numero_personne) err('N° de dossier non renseigné');
  c.emps.forEach((em,i)=>{
    if(!em.num_personne) al('N° de personne manquant : '+nomE(i));
    if(!(em.revenus||[]).some(r=>num(r.montant)>0)) err('Aucun revenu renseigné : '+nomE(i));
  });
  if(!d.adresse_bien) al("Adresse du bien non renseignée");
  if(getCoutProjet(d)<=0) err("Coût du projet à 0 : acquisition non renseignée");
  c.lignes.forEach((l,i)=>{
    if(num(l.montant)>0){
      if(!(parseInt(l.duree)>0)) err(nomL(i)+' : durée manquante');
      if(l.taux===''||l.taux==null) err(nomL(i)+' : taux manquant');
      if(!c.assRaw.some(a=>(a.ligne??0)===i&&(num(a.taux)>0||num(a.cout_total)>0))) al(nomL(i)+' : aucune assurance');
    }

  });
  c.assRaw.forEach(a=>{ if((a.ligne??0)>=c.lignes.length) err('Une assurance est rattachée à une ligne qui n\'existe plus'); if((a.emp??0)>=c.emps.length) err('Une assurance est rattachée à un emprunteur qui n\'existe plus'); });
  c.lignes.forEach((l,i)=>{const b=new Set(c.assRaw.filter(a=>(a.ligne??0)===i&&num(a.taux)>0).map(a=>a.base)); if(b.size>1) al(nomL(i)+' : assurances sur des bases différentes (CI et CRD)');});
  if(c.lignes.some(l=>num(l.montant)>0)&&Math.abs(c.resteAFin)>1) al('Plan de financement non bouclé : écart de '+fmt(c.resteAFin)+' € entre le montant à financer et les lignes');
  if(c.te!==null&&c.te>CI_SEUILS.tauxMax) al("Taux d'endettement de "+pct2(c.te)+" : supérieur à "+CI_SEUILS.tauxMax+" %");
  if(c.reste<0) err('Reste à vivre négatif');
  if(['F','G'].includes(String(d.dpe_etiquette||'').toUpperCase())) al('DPE '+d.dpe_etiquette+' : vérifier les restrictions liées à la performance énergétique');
  if(ciWfIdx(d)>=CI_WF_ORDER.indexOf('controle')){
    const miss=Object.keys(CI_DOCS).filter(k=>d[k]!=1);
    if(miss.length) al('Pièces non cochées ('+miss.length+') : '+miss.map(k=>CI_DOCS[k]).slice(0,4).join(', ')+(miss.length>4?'…':''));
  }
  if(d.garantie_type==='CEGC'&&ciWfIdx(d)>=CI_WF_ORDER.indexOf('edition_offres')&&d.suivi_cegc_accord!=1) al('Garantie CEGC : accord non enregistré alors que le dossier est au stade « édition des offres »');
  c.lignes.forEach((l,i)=>{
    if(!l.doublissimo) return;
    const pl=doublissimoPlafond(d), p=dblParams(), princ=c.lignes.find(x=>!x.doublissimo&&!x.primo_jeune&&parseInt(x.duree)>0), ps=primoStatut(d);
    if(num(l.montant)>pl+0.5) err(nomL(i)+' (Doublissimo) : '+fmt(l.montant)+' € supérieur au plafond de '+fmt(pl)+' €');
    if(num(l.montant)>doublissimoMontant(d)+0.5) al(nomL(i)+' (Doublissimo) : supérieur à '+p.pourcentage+' % du financement total ('+fmt(doublissimoMontant(d))+' €)');
    const dur=parseInt(l.duree)||0;
    if(dur>0&&dur<num(p.duree_min_mois)) al(nomL(i)+' (Doublissimo) : durée inférieure au minimum de '+p.duree_min_mois+' mois');
    if(dur>num(p.duree_max_mois)||(princ&&dur>parseInt(princ.duree))) al(nomL(i)+' (Doublissimo) : durée supérieure à celle du prêt principal ('+(princ?princ.duree:p.duree_max_mois)+' mois, '+p.duree_max_mois+' maximum)');
    if(ps===''||ps==='NON') al('Doublissimo réservé aux primo-accédants (statut non renseigné ou « Non »)');
    if(usageChoice(d)!=='RP') al('Doublissimo réservé au financement de la résidence principale');
    if(d.ptz_actif!=1) al('Doublissimo sans PTZ : joindre l\'attestation sur l\'honneur de primo-accession');
  });
  c.lignes.forEach((l,i)=>{
    if(!l.primo_jeune) return;
    const p=pjParams(), dur=parseInt(l.duree)||0, ps=primoStatut(d), ages=c.emps.map(e=>ageDe(e.date_naissance)).filter(a=>a!==null);
    if(num(l.montant)>num(p.plafond)+0.5) err(nomL(i)+' (Primo Jeune) : '+fmt(l.montant)+' € supérieur au maximum de '+fmt(p.plafond)+' €');
    if(num(l.montant)>pjMontant(d)+0.5) err(nomL(i)+' (Primo Jeune) : supérieur à '+p.pourcentage+' % du financement total ('+fmt(pjMontant(d))+' €)');
    if(dur>num(p.duree_max_mois)||(dur>0&&dur%12!==0)) al(nomL(i)+' (Primo Jeune) : durée de '+dur+' mois ; maximum '+p.duree_max_mois+' mois, par multiples de 12');
    if(num(l.taux)!==0||num(l.frais_dossier)!==0) al(nomL(i)+' (Primo Jeune) : le prêt est à 0 % et sans frais de dossier');
    if(d.ptz_actif!=1) err('Primo Jeune 0 % : le PTZ est obligatoire (prêt complémentaire au PTZ)');
    if(ps===''||ps==='NON') al('Primo Jeune réservé aux primo-accédants (statut non renseigné ou « Non »)');
    if(usageChoice(d)!=='RP') al('Primo Jeune réservé au financement de la résidence principale');
    if(!ages.length) al('Primo Jeune : renseigner la date de naissance des emprunteurs (35 ans maximum pour l\'un d\'eux)');
    else if(Math.min(...ages)>num(p.age_max)) err('Primo Jeune : aucun emprunteur de '+p.age_max+' ans ou moins (plus jeune : '+Math.min(...ages)+' ans)');
  });
  const ageMin=()=>{const a=c.emps.map(e=>ageDe(e.date_naissance)).filter(x=>x!==null);return a.length?Math.min(...a):null;};
  const ageMax=()=>{const a=c.emps.map(e=>ageDe(e.date_naissance)).filter(x=>x!==null);return a.length?Math.max(...a):null;};
  c.lignes.forEach((l,i)=>{
    if(!l.primoz) return;
    const p=pzParams(), dur=parseInt(l.duree)||0, df=parseInt(l.differe)||0, ft=financementTotal(d), m=num(l.montant), ps=primoStatut(d), am=ageMax();
    if(m<num(p.montant_min)-0.5||m>num(p.montant_max)+0.5) err(nomL(i)+' (Primoz) : '+fmt(m)+' € hors de la fourchette '+fmt(p.montant_min)+' – '+fmt(p.montant_max)+' €');
    if(ft>0&&(m<ft*num(p.pct_min)/100-0.5||m>ft*num(p.pct_max)/100+0.5)) al(nomL(i)+' (Primoz) : doit représenter '+p.pct_min+' à '+p.pct_max+' % du financement total ('+fmt(ft*p.pct_min/100)+' à '+fmt(ft*p.pct_max/100)+' €)');
    if(dur<num(p.duree_min_mois)||dur>num(p.duree_max_mois)||(dur>0&&dur%12!==0)) al(nomL(i)+' (Primoz) : durée de '+dur+' mois ; '+p.duree_min_mois+' à '+p.duree_max_mois+' mois, par multiples de 12');
    if(df<num(p.differe_min_mois)||df>num(p.differe_max_mois)) al(nomL(i)+' (Primoz) : différé de '+df+' mois ; '+p.differe_min_mois+' à '+p.differe_max_mois+' mois');
    else if(dur-df<120) al(nomL(i)+' (Primoz) : la phase d\'amortissement ne doit pas être inférieure à 10 ans');
    if(!c.lignes.some((x,k)=>k!==i&&!x.doublissimo&&!x.primo_jeune&&!x.primoz&&!x.grandioz&&num(x.montant)>0)) al('Primoz : doit être couplé à un prêt principal amortissable');
    if(c.lignes.some(x=>x.grandioz)) err('Primoz et Grandioz sont incompatibles');
    if(ps===''||ps==='NON') al('Primoz réservé aux primo-accédants');
    if(usageChoice(d)!=='RP') al('Primoz réservé au financement de la résidence principale');
    if(am===null) al('Primoz : renseigner la date de naissance des emprunteurs (moins de 36 ans)'); else if(am>num(p.age_max)) err('Primoz : emprunteur de '+am+' ans (maximum '+p.age_max+' ans, les deux emprunteurs étant concernés)');
  });
  c.lignes.forEach((l,i)=>{
    if(!l.grandioz) return;
    const p=grParams(), dur=parseInt(l.duree)||0, ps=primoStatut(d), an=ageMin();
    if(num(l.montant)<num(p.montant_min)-0.5) al(nomL(i)+' (Grandioz) : financement minimum de '+fmt(p.montant_min)+' €');
    if(dur<num(p.duree_min_mois)||dur>num(p.duree_max_mois)) al(nomL(i)+' (Grandioz) : durée de '+dur+' mois ; '+p.duree_min_mois+' à '+p.duree_max_mois+' mois');
    if(d.ptz_actif==1) al('Grandioz et PTZ sont peu compatibles (le PTZ ne peut être associé que si le Grandioz est remboursé pendant son différé)');
    if(ps===''||ps==='NON') al('Grandioz réservé aux primo-accédants');
    if(usageChoice(d)!=='RP') al('Grandioz réservé à la résidence principale');
    if(an===null) al('Grandioz : renseigner la date de naissance des emprunteurs (35 ans maximum)'); else if(an>num(p.age_max)) al('Grandioz : aucun emprunteur de '+p.age_max+' ans ou moins (plus jeune : '+an+' ans)');
  });
  if(d.ptz_actif==1){
    const r=ciPtzMaxFor(d);
    if(!r.indispo){
      if(r.errs.length) err('PTZ saisi mais non accessible : '+r.errs.join(' ; '));
      else{
        if(num(d.ptz_montant)>r.mont+0.5) err('PTZ de '+fmt(d.ptz_montant)+' € supérieur au maximum de '+fmt(r.mont)+' €');
        if((parseInt(d.ptz_duree)||0)>r.d.total*12) al('Durée du PTZ supérieure au maximum de '+r.d.total*12+' mois');
        if(ptzDiffere(d)>r.d.differe*12) al('Différé du PTZ supérieur au maximum de '+r.d.differe*12+' mois');
      }
    } else if(r.indispo.indexOf('zone')>=0) al('PTZ : '+r.indispo);
  }
  if(d.ade_reponse==='REFUS'||d.ade_reponse==='Refus') al('Réponse ADE : refus');
  return out;
}
const ciBadgeCls={danger:'danger',warning:'warning text-dark',erreur:'danger',alerte:'warning text-dark'};
function ciAlertBlock(d){
  const rel=ciRelances(d), ctl=ciControles(d);
  if(!rel.length&&!ctl.length) return '<div class="alert alert-success py-2 small mb-3"><i class="fas fa-circle-check"></i> Aucune relance ni anomalie détectée.</div>';
  const li=(x)=>`<li><span class="badge bg-${ciBadgeCls[x.niv]} me-1">${x.niv}</span>${escapeHtml(x.txt)}</li>`;
  return `<div class="alert alert-warning py-2 small mb-3"><div class="row g-2">
    ${rel.length?`<div class="col-md-6"><strong><i class="fas fa-bell"></i> Relances (${rel.length})</strong><ul class="mb-0 ps-3">${rel.map(li).join('')}</ul></div>`:''}
    ${ctl.length?`<div class="col-md-6"><strong><i class="fas fa-triangle-exclamation"></i> Contrôles (${ctl.length})</strong><ul class="mb-0 ps-3">${ctl.map(li).join('')}</ul></div>`:''}
  </div></div>`;
}
// Avant impression : liste les anomalies et laisse le choix d'imprimer quand même
function ciConfirmPrint(d){
  const ctl=ciControles(d);
  if(!ctl.length) return true;
  return confirm('Anomalies détectées sur ce dossier :\n\n'+ctl.map(x=>'• '+x.txt).join('\n')+'\n\nImprimer quand même ?');
}

// Détail du dossier : bandeau relances / contrôles en tête
(function(){
  const base=showDetail;
  showDetail=function(id){
    base(id);
    const d=dossiersData.find(x=>x.id==id), box=document.getElementById('detailContent');
    if(d&&box) box.insertAdjacentHTML('afterbegin',ciAlertBlock(d));
  };
})();

// Liste : colonne « Alertes » et compteur
document.addEventListener('DOMContentLoaded',()=>{
  let nbRel=0;
  dossiersData.forEach(d=>{
    const rel=ciRelances(d), ctl=ciControles(d), el=document.getElementById('al_'+d.id);
    if(rel.length) nbRel++;
    if(!el) return;
    const tip=l=>escapeHtml(l.map(x=>x.txt).join('\n'));
    el.innerHTML=(rel.length?`<span class="badge bg-${rel.some(x=>x.niv==='danger')?'danger':'warning text-dark'}" title="${tip(rel)}" style="cursor:help"><i class="fas fa-bell"></i> ${rel.length}</span> `:'')
      +(ctl.length?`<span class="badge bg-${ctl.some(x=>x.niv==='erreur')?'danger':'secondary'}" title="${tip(ctl)}" style="cursor:help"><i class="fas fa-triangle-exclamation"></i> ${ctl.length}</span>`:'')
      ||'<span class="text-success"><i class="fas fa-check"></i></span>';
  });
  const k=document.getElementById('kpiRelances'); if(k) k.textContent=nbRel;
});

// ── TABLEAU DE BORD ───────────────────────────────────────────────────────────
function ciAvg(arr){const a=arr.filter(x=>x!==null&&x>=0);return a.length?a.reduce((s,x)=>s+x,0)/a.length:null;}
function ciDashboard(){
  const box=document.getElementById('ciDashBody'); if(!box) return;
  const per=document.getElementById('ciDashPeriode').value;
  const lim=per==='all'?null:new Date(Date.now()-parseInt(per)*86400000);
  const ds=dossiersData.filter(d=>!lim||(ciTs(d.date_ajout)&&ciTs(d.date_ajout)>=lim));
  const cs=ds.map(d=>({d,c:computeAll(d)}));
  const actifs=cs.filter(x=>!['termine','refuse'].includes(x.d.workflow_status||'etude'));
  const signes=cs.filter(x=>ciWfIdx(x.d)>=CI_WF_ORDER.indexOf('offre_signee')&&x.d.workflow_status!=='refuse');
  const sum=(a,f)=>a.reduce((t,x)=>t+f(x),0);
  const capSigne=sum(signes,x=>x.c.capital), capActif=sum(actifs,x=>x.c.capital);
  const lignes=cs.flatMap(x=>x.c.lignes.filter(l=>num(l.montant)>0)), mt=sum(lignes,l=>num(l.montant));
  const tauxMoy=mt>0?sum(lignes,l=>num(l.montant)*num(l.taux))/mt:null;
  const teMoy=ciAvg(cs.map(x=>x.c.te));
  const mois=new Date().toISOString().slice(0,7);
  const signMois=cs.filter(x=>String(x.d.suivi_date_signature_definitive||'').slice(0,7)===mois);
  const dl=(f)=>ciAvg(ds.map(f));
  const delais=[
    ['ADE (envoi → retour)',dl(d=>ciDays(d.ade_envoyee_le,d.ade_retour_le))],
    ['CEGC (demande → retour)',dl(d=>ciDays(d.suivi_date_demande_cegc,d.suivi_date_retour_cegc))],
    ['Conformité (envoi → retour)',dl(d=>ciDays(d.suivi_date_envoi_conformite,d.suivi_date_retour_conformite))],
    ['Offres éditées → accusé de réception',dl(d=>ciDays(d.suivi_date_edition_offres_dt,d.suivi_date_accuse_reception))],
    ['Création → signature définitive',dl(d=>ciDays(d.date_ajout,d.suivi_date_signature_definitive))],
    ['Signature → versement notaire',dl(d=>ciDays(d.suivi_date_signature_definitive,d.suivi_date_versement_notaire))],
  ];
  const parSt=Object.keys(workflowLabels).map(k=>[k,workflowLabels[k],ds.filter(d=>(d.workflow_status||'etude')===k).length]);
  const maxN=Math.max(1,...parSt.map(x=>x[2]));
  const kpi=(l,v,s)=>`<div class="col-6 col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">${l}</div><div class="fs-5 fw-bold">${v}</div>${s?`<div class="small text-muted">${s}</div>`:''}</div></div>`;
  box.innerHTML=`<div class="row g-2 mb-3">
      ${kpi('Dossiers sur la période',ds.length,actifs.length+' en cours')}
      ${kpi('Capital en cours',fmt(capActif)+' €',actifs.length+' dossier(s)')}
      ${kpi('Capital signé',fmt(capSigne)+' €',signes.length+' dossier(s)')}
      ${kpi('Signatures ce mois',signMois.length,fmt(sum(signMois,x=>x.c.capital))+' €')}
      ${kpi('Taux moyen (pondéré)',tauxMoy===null?'—':tauxMoy.toFixed(2).replace('.',',')+' %')}
      ${kpi("Endettement moyen",teMoy===null?'—':pct2(teMoy))}
      ${kpi('Mensualité moyenne',cs.length?fmt(sum(cs,x=>x.c.mensTout)/cs.length)+' €':'—','tout inclus')}
      ${kpi('Relances en attente',dossiersData.filter(d=>ciRelances(d).length).length)}
    </div>
    <div class="row g-3"><div class="col-md-6"><h6>Dossiers par statut</h6>
      ${parSt.map(([k,w,n])=>`<div class="d-flex align-items-center mb-1 small"><div style="width:140px">${escapeHtml(w[0])}</div><div class="flex-grow-1 bg-light rounded"><div class="bg-${w[1].split(' ')[0]} rounded" style="height:14px;width:${n/maxN*100}%;min-width:${n?'4px':'0'}"></div></div><div class="ms-2" style="width:24px;text-align:right">${n}</div></div>`).join('')}</div>
      <div class="col-md-6"><h6>Délais moyens</h6><table class="table table-sm mb-0"><tbody>
      ${delais.map(([l,v])=>`<tr><td>${l}</td><td class="text-end">${v===null?'<span class="text-muted">—</span>':Math.round(v*10)/10+' j'}</td></tr>`).join('')}</tbody></table></div></div>`;
}
function toggleDash(){
  const w=document.getElementById('ciDash'); const open=w.style.display==='none';
  w.style.display=open?'':'none'; if(open) ciDashboard();
}

// ── COMPARATIF CLIENT (1 page A4 à remettre au client : 2 ou 3 scénarios de financement) ──────
// S'appuie sur la fenêtre « Comparer » : les scénarios sont des dossiers (utiliser « Dupliquer » pour décliner un dossier).
(function(){
  const base=runComparison;
  runComparison=function(){
    base();
    const ids=Array.from(document.querySelectorAll('.compare-chk:checked')).map(c=>parseInt(c.value));
    const box=document.getElementById('compareResults');
    if(!box||ids.length<2) return;
    if(ids.length>3){box.insertAdjacentHTML('beforeend','<div class="alert alert-info py-2 mt-2 small">Le comparatif client est limité à 3 scénarios.</div>');return;}
    const ds=ids.map(id=>dossiersData.find(d=>d.id==id)).filter(Boolean);
    box.insertAdjacentHTML('beforeend',`<div class="card mt-3"><div class="card-header fw-semibold"><i class="fas fa-print"></i> Comparatif à remettre au client</div><div class="card-body">
      <div class="row g-2">${ds.map((d,i)=>`<div class="col-md-4"><label class="form-label small mb-0">Nom du scénario ${i+1}</label><input type="text" class="form-control form-control-sm" id="cmpLabel${i}" value="Scénario ${String.fromCharCode(65+i)}" maxlength="40"></div>`).join('')}</div>
      <label class="form-label small mb-0 mt-2">Commentaire du conseiller (facultatif)</label><textarea class="form-control form-control-sm" id="cmpComment" rows="2" maxlength="400"></textarea>
      <button class="btn btn-ce mt-2" onclick="printComparatif([${ids.join(',')}])"><i class="fas fa-print"></i> Imprimer le comparatif</button>
      <div class="form-text">Ne contient pas d'informations internes (interrogations BdF/DRC, notes, suivi).</div></div></div>`);
  };
})();

function printComparatif(ids){
  const ds=ids.map(id=>dossiersData.find(d=>d.id==id)).filter(Boolean);
  if(ds.length<2) return;
  const e=escapeHtml, cs=ds.map(computeAll);
  const labels=ds.map((d,i)=>(document.getElementById('cmpLabel'+i)||{}).value||('Scénario '+String.fromCharCode(65+i)));
  const comment=((document.getElementById('cmpComment')||{}).value||'').trim();
  const mm=v=>{const x=num(v);return (Number.isInteger(x)?x.toLocaleString('fr-FR'):fmt(x))+' €';};
  const hasPtz=ds.some(d=>d.ptz_actif==1||d.ecoptz_actif==1);
  // Meilleure valeur en gras (min ou max) ; null = pas de mise en valeur
  const row=(lab,vals,txt,best)=>{
    const ok=vals.filter(v=>v!==null&&!isNaN(v)).map(v=>Math.round(v*100)/100);
    const b=best==='min'?Math.min(...ok):best==='max'?Math.max(...ok):null, tie=ok.every(v=>v===ok[0]); // pas de mise en valeur si tous égaux
    return `<tr><td>${lab}</td>${vals.map((v,i)=>`<td class="r${b!==null&&!tie&&Math.round(v*100)/100===b?' sy-best':''}">${txt[i]}</td>`).join('')}</tr>`;
  };
  const ligneTxt=c=>c.lignes.filter(l=>num(l.montant)>0).map(l=>`${mm(l.montant)} sur ${Math.round((parseInt(l.duree)||0)/12*10)/10} ans à ${num(l.taux).toFixed(2).replace('.',',')} %`).join('<br>')||'—';
  const sec=(t)=>`<tr><td colspan="${ds.length+1}" class="sy-sub">${t}</td></tr>`;
  const cond=conseillerTxt();
  document.getElementById('printArea').innerHTML=`<div class="sy-wrap">
    <div class="sy-head"><img src="${LOGO_CE}" class="sy-logo" alt="Caisse d'Épargne">
      <div class="sy-title">COMPARATIF DE FINANCEMENT</div>
      <div class="sy-meta">Édité le ${new Date().toLocaleDateString('fr-FR')}${cond?'<div class="sy-conseiller">'+cond+'</div>':''}</div></div>
    <div class="sy-sec"><div class="sy-sec-t">Votre projet</div><div class="sy-body">
      ${e(ds[0].adresse_bien||'')||'<span class="sy-grey">Adresse du bien non renseignée</span>'}${num(getCoutProjet(ds[0]))>0?' · Coût du projet : <b>'+mm(getCoutProjet(ds[0]))+'</b>':''}${num(ds[0].apport)>0?' · Apport : <b>'+mm(ds[0].apport)+'</b>':''}
    </div></div>
    <div class="sy-sec"><div class="sy-sec-t">Comparaison des scénarios</div><div class="sy-body">
      <table class="sy-t sy-cmp"><thead><tr><th></th>${labels.map(l=>`<th class="r">${e(l)}</th>`).join('')}</tr></thead><tbody>
        ${sec('Le financement')}
        <tr><td>Prêt(s)</td>${cs.map(c=>`<td class="r">${ligneTxt(c)}</td>`).join('')}</tr>
        ${hasPtz?row('Dont PTZ / EcoPTZ',cs.map((c,i)=>ptzMontant(ds[i])+ecoMontant(ds[i])),cs.map((c,i)=>mm(ptzMontant(ds[i])+ecoMontant(ds[i]))),null):''}
        ${row('Capital emprunté',cs.map(c=>c.capital),cs.map(c=>mm(c.capital)),'min')}
        ${sec('Vos remboursements')}
        ${row('Mensualité hors assurance',cs.map(c=>c.mensHorsAssur),cs.map(c=>fmt(c.mensHorsAssur)+' €'),'min')}
        ${row('Assurance emprunteur / mois',cs.map(c=>c.mensAssur),cs.map(c=>fmt(c.mensAssur)+' €'),'min')}
        ${row('<b>Mensualité tout inclus</b>',cs.map(c=>c.mensTout),cs.map(c=>'<b>'+fmt(c.mensTout)+' €</b>'),'min')}
        ${sec('Le coût du crédit')}
        ${row('TAEG',cs.map(c=>c.taegGlobal),cs.map(c=>taegTxt(c.taegGlobal)),'min')}
        ${row('Intérêts',cs.map(c=>c.interets),cs.map(c=>mm(c.interets)),'min')}
        ${row('Assurances (total)',cs.map(c=>c.totAssur),cs.map(c=>mm(c.totAssur)),'min')}
        ${row('Frais de dossier et garantie',cs.map(c=>c.fraisDossier+c.garantie),cs.map(c=>mm(c.fraisDossier+c.garantie)),'min')}
        ${row('<b>Coût total du crédit</b>',cs.map(c=>c.coutCredit),cs.map(c=>'<b>'+mm(c.coutCredit)+'</b>'),'min')}
        ${sec('Votre budget')}
        ${row("Taux d'endettement",cs.map(c=>c.te),cs.map(c=>pct2(c.te)),'min')}
        ${row('Reste à vivre',cs.map(c=>c.reste),cs.map(c=>mm(c.reste)+(c.restePers===null?'':'<br><span class="sy-grey">'+mm(c.restePers)+' / pers.</span>')),'max')}
      </tbody></table>
      <div class="sy-small" style="margin-top:4px">La valeur la plus favorable de chaque ligne est mise en gras.</div>
    </div></div>
    ${comment?`<div class="sy-sec"><div class="sy-sec-t">Le mot du conseiller</div><div class="sy-body">${e(comment).replace(/\n/g,'<br>')}</div></div>`:''}
    <div class="sy-foot">Simulation à titre indicatif, sans valeur contractuelle : l'octroi du crédit reste soumis à l'accord de la Caisse d'Épargne après étude du dossier. Un crédit vous engage et doit être remboursé.</div>
  </div>`;
  printWhenReady();
}

// ── PTZ : maximum autorisé (même calcul que le simulateur PTZ), zone ABC déduite de l'adresse ──────────────
const CI_PTZ_TYPES={NEUF_VEFA:'collectif_neuf',CONSTRUCTION_CCMI:'maison_neuve',CONSTRUCTION_SANS_CCMI:'maison_neuve',ANCIEN_AVEC_TRAVAUX:'ancien_travaux'};
// Renvoie le résultat de ptzCalc pour le dossier d, ou {indispo: 'raison'}
function ciPtzMaxFor(d){
  if(typeof ptzCalc==='undefined'||typeof ciPtzBareme==='undefined') return {indispo:'barème PTZ indisponible'};
  const type=CI_PTZ_TYPES[d.type_projet];
  if(!type) return {indispo:d.type_projet?"un PTZ n'est possible que pour le neuf, la construction ou l'ancien avec travaux":'type de projet non renseigné'};
  if(!d.zone_abc) return {indispo:"zone ABC du bien à renseigner (elle se déduit de l'adresse)"};
  const c=computeAll(d), ps=primoStatut(d);
  return ptzCalc(ciPtzBareme,{pers:Math.max(1,c.nbPers||1),rfr:c.rfr,cout:getCoutProjet(d),zone:d.zone_abc,type,primo:ps!==''&&ps!=='NON',rp:usageChoice(d)==='RP'});
}
function ciPtzMaxHtml(d,px){
  const r=ciPtzMaxFor(d);
  if(r.indispo) return `<div class="alert alert-secondary py-1 small mb-0"><i class="fas fa-circle-info"></i> Maximum PTZ non calculé : ${escapeHtml(r.indispo)}.</div>`;
  if(r.errs.length) return `<div class="alert alert-warning py-1 small mb-0"><i class="fas fa-triangle-exclamation"></i> <strong>PTZ non accessible</strong> : ${escapeHtml(r.errs.join(' ; '))}.</div>`;
  const dur=r.d.total*12, dif=r.d.differe*12, over=num(d.ptz_montant)>r.mont+0.5||(parseInt(d.ptz_duree)||0)>dur||ptzDiffere(d)>dif;
  return `<div class="alert alert-${over?'danger':'info'} py-1 small mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="fas fa-calculator"></i> <strong>PTZ maximum : ${fmt(r.mont)} €</strong> · ${dur} mois maximum · différé ${dif} mois maximum <span class="text-muted">(tranche ${r.tr+1}, ${r.q} % de ${fmt(r.prix)} € ; revenu retenu ${fmt(r.revenu)} € ÷ ${String(r.coeff).replace('.',',')})</span>${over?' — <strong>les valeurs saisies dépassent le maximum</strong>':''}</span>
    ${px?`<button type="button" class="btn btn-sm btn-outline-primary" onclick="ciApplyPtz('${px}')">Appliquer ces valeurs</button>`:''}</div>`;
}
function ciUpdatePtzMax(px,d){
  const box=document.getElementById(px+'_ptz_max'); if(!box) return;
  d=d||readForm(px);
  box.innerHTML=d.ptz_actif==1?ciPtzMaxHtml(d,px):'';
}
function ciApplyPtz(px){
  const d=readForm(px), r=ciPtzMaxFor(d); if(r.indispo||r.errs.length) return;
  document.getElementById(px+'_ptz_mt').value=r.mont;
  document.getElementById(px+'_ptz_dur').value=r.d.total*12;
  document.getElementById(px+'_ptz_diff').value=r.d.differe*12;
  onFormChange(px);
}
// Zone ABC du bien : déduite du code commune de l'adresse (liste importée par l'administrateur), modifiable à la main
async function ciDeduireZone(px){
  const ins=(document.getElementById(px+'_bien_insee')||{}).value, sel=document.getElementById(px+'_zone_abc'), info=document.getElementById(px+'_zone_info');
  if(!ins||!sel||typeof ciZonageUrl==='undefined') return;
  try{
    const r=await fetch(ciZonageUrl+'?insee='+encodeURIComponent(ins),{credentials:'same-origin'}), j=await r.json();
    if(j&&j.zone){sel.value=({Abis:'A'})[j.zone]||j.zone; if(info) info.textContent='(déduite : '+(j.zone==='Abis'?'A bis':j.zone)+' – '+j.commune+')';}
    else if(info) info.textContent='(commune non trouvée : à saisir)';
    onFormChange(px);
  }catch(e){}
}
