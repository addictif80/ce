// Passerelle entre les formulaires simplifiés de /tools (plan de financement, comparateur de scénarios, crédits spéciaux)
// et le moteur de calcul du module crédit immobilier (ci.js / ci_extra.js : computeAll, doublissimoMontant, pjMontant, ciPtzMaxFor, ciControles…).
// Le moteur n'est donc écrit qu'une fois : mêmes règles et mêmes résultats que dans les dossiers du portail.
// f = { type_projet, usage, primo ('OUI'|'NON'), zone, canal, acq, trav, travEco, notaire, nego, divers, garantie, fdossier, apport, patronal, enfants,
//       emps:[{rev, charges, rfr, naissance, ass}], principal:{taux, duree}, ptz:{on, montant}, dbl:{on, taux, duree}, pj:{on, duree},
//       pz:{on, montant, taux, duree, differe}, gr:{on, montant, taux, duree, prog}, ptzDuree? }
window.planAdapter=(function(){
  const N=v=>{const x=parseFloat(v);return isNaN(x)?0:x;};
  const mult12=(m,max)=>Math.max(12,Math.floor(Math.min(m,max)/12)*12);
  // Dossier « virtuel » au format du module crédit immobilier
  function base(f){
    const emps=(f.emps||[]).filter(e=>e&&(N(e.rev)>0||N(e.rfr)>0||e.naissance||N(e.charges)>0)).map((e,i)=>({nom:'Emprunteur '+(i+1),num_personne:'S'+(i+1),date_naissance:e.naissance||'',bdf:'OK',drc:'OK',topcc:'OK',rfr:N(e.rfr),
      revenus:[{intitule:'Revenus',montant:N(e.rev)}],charges:N(e.charges)>0?[{intitule:'Charges',montant:N(e.charges)}]:[],epargne:[]}));
    const list=emps.length?emps:[{nom:'Emprunteur 1',num_personne:'S1',bdf:'OK',drc:'OK',topcc:'OK',rfr:0,revenus:[],charges:[],epargne:[]}];
    return {id:0,numero_personne:'SIMU',adresse_bien:'Simulation',emprunteurs_json:JSON.stringify(list),
      type_projet:f.type_projet||'ANCIEN_SANS_TRAVAUX',usage_bien:f.usage||'RP',primo_accedant_statut:f.primo||'NON',zone_abc:f.zone||'',canal_origine:f.canal||'AGENCE',
      montant_acquisition:N(f.acq),montant_travaux:N(f.trav),travaux_ecoptz:N(f.travEco),frais_notaire:N(f.notaire),frais_negociation:N(f.nego),frais_divers:N(f.divers),
      garantie_montant:N(f.garantie),apport:N(f.apport),pret_patronal:N(f.patronal),tva_financee:0,
      nb_personnes_foyer:list.length+Math.max(0,parseInt(f.enfants)||0),nb_enfants:Math.max(0,parseInt(f.enfants)||0),
      garantie_type:'',taeg_assurance:'MIN',ptz_actif:0,ecoptz_actif:0,doublissimo:0,primo_jeune:0,primoz:0,grandioz:0,dpe_etiquette:f.dpe||''};
  }
  // Lignes d'assurance : chaque emprunteur sur chaque ligne (et sur le PTZ), assurance sur capital restant dû
  function assurances(d,f){
    const nl=JSON.parse(d.lignes_credit_json).length+(d.ptz_actif==1?1:0), rows=[];
    const emps=JSON.parse(d.emprunteurs_json);
    emps.forEach((e,ei)=>{const t=N(((f.emps||[]).filter(x=>x&&(N(x.rev)>0||N(x.rfr)>0||x.naissance||N(x.charges)>0))[ei]||{}).ass);
      for(let li=0;li<nl;li++) rows.push({emp:ei,ligne:li,taux:t,base:'CRD',quotite:100});});
    d.ade_json=JSON.stringify(rows);
  }
  function build(f){
    const d=base(f), lignes=[], pr=f.principal||{}, dur=parseInt(pr.duree)||0;
    // 1) PTZ (montant maximum calculé par le moteur, sauf montant saisi)
    let ptzInfo=null;
    if(f.ptz&&f.ptz.on){
      d.ptz_actif=1; const r=ciPtzMaxFor(Object.assign({},d,{ptz_actif:1}));
      ptzInfo=r;
      const mx=(!r.indispo&&!(r.errs&&r.errs.length))?r.mont:0;
      d.ptz_montant=(f.ptz.montant!==undefined&&f.ptz.montant!=='')?N(f.ptz.montant):mx;
      d.ptz_duree=(!r.indispo&&r.d)?r.d.total*12:240; d.ptz_differe=(!r.indispo&&r.d)?r.d.differe*12:0;
      if(f.ptz.duree) d.ptz_duree=parseInt(f.ptz.duree); if(f.ptz.differe!==undefined&&f.ptz.differe!=='') d.ptz_differe=parseInt(f.ptz.differe)||0;
    }
    // 2) Prêts spéciaux (montants calculés sur le dossier courant)
    d.lignes_credit_json='[]';
    const tmp=Object.assign({},d,{lignes_credit_json:JSON.stringify([{libelle:'Prêt principal',montant:0,duree:dur,taux:N(pr.taux),frais_dossier:N(f.fdossier)}])});
    if(f.dbl&&f.dbl.on){
      const p=dblParams(), dd=f.dbl.duree?parseInt(f.dbl.duree):Math.min(dur,N(p.duree_max_mois)||300);
      lignes.push({libelle:'Doublissimo',montant:Math.round(doublissimoMontant(tmp)*100)/100,duree:dd,taux:(f.dbl.taux!==undefined&&f.dbl.taux!=='')?N(f.dbl.taux):(dblCampagneActive()&&N(p.campagne.taux)>0?N(p.campagne.taux):0),frais_dossier:0,doublissimo:true}); d.doublissimo=1;
    }
    if(f.pj&&f.pj.on){
      const p=pjParams();
      lignes.push({libelle:'Primo Jeune 0 %',montant:Math.round(pjMontant(tmp)*100)/100,duree:f.pj.duree?parseInt(f.pj.duree):mult12(dur||N(p.duree_max_mois),N(p.duree_max_mois)),taux:0,frais_dossier:0,primo_jeune:true}); d.primo_jeune=1;
    }
    if(f.pz&&f.pz.on){
      const p=pzParams();
      lignes.push({libelle:'Primoz',montant:(f.pz.montant!==undefined&&f.pz.montant!=='')?N(f.pz.montant):pzMontantDefaut(tmp),duree:parseInt(f.pz.duree)||N(p.duree_min_mois),taux:N(f.pz.taux),frais_dossier:0,primoz:true,differe:(f.pz.differe!==undefined&&f.pz.differe!=='')?parseInt(f.pz.differe):N(p.differe_min_mois)}); d.primoz=1;
    }
    if(f.gr&&f.gr.on){
      const p=grParams();
      lignes.push({libelle:'Grandioz',montant:N(f.gr.montant),duree:parseInt(f.gr.duree)||240,taux:N(f.gr.taux),frais_dossier:0,grandioz:true,progression:(f.gr.prog!==undefined&&f.gr.prog!=='')?N(f.gr.prog):p.progression}); d.grandioz=1;
    }
    // 3) Prêt principal : solde à financer
    const princ={libelle:'Prêt principal',montant:0,duree:dur,taux:N(pr.taux),frais_dossier:N(f.fdossier)};
    d.lignes_credit_json=JSON.stringify([princ].concat(lignes));
    const reste=getResteAFinancer(d); princ.montant=Math.max(0,Math.round(reste*100)/100);
    d.lignes_credit_json=JSON.stringify([princ].concat(lignes));
    assurances(d,f);
    return {d,ptzInfo,lignes:[princ].concat(lignes)};
  }
  // Calcul complet : indicateurs + contrôles pertinents pour une simulation
  const IGNORE=/N° de dossier|N° de personne|Adresse du bien|Pièces non cochées|DPE /;
  function run(f){
    const b=build(f), c=computeAll(b.d);
    const ctl=ciControles(b.d).filter(x=>!IGNORE.test(x.txt));
    return {d:b.d,c,ptzInfo:b.ptzInfo,controles:ctl,lignes:b.lignes};
  }
  return {build,run,N,mult12};
})();
