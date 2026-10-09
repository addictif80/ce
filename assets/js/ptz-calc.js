// Calcul du prêt à taux zéro (PTZ) à partir d'un barème B (voir modules/ptz/bareme.php) et d'une situation :
// p = {pers, rfr, cout, zone ('A'|'B1'|'B2'|'C'), type (clé de B.types), primo (bool), rp (bool)}.
// Partagé par le simulateur PTZ (portail et /tools) et le module crédit immobilier.
window.ptzCalc=function(B,p){
  const eur=v=>Math.round(v).toLocaleString('fr-FR')+' €';
  const np=Math.max(1,Math.round(p.pers)), z=p.zone, t=B.types[p.type];
  const coeff=B.coeff_familial[Math.min(np,5)-1];
  const revenu=Math.max(p.rfr,p.cout/B.diviseur_cout);          // revenu retenu : le plus élevé du RFR et du coût / 9
  const out={coeff,revenu,errs:[]};
  if(!B.plafonds_ressources[z]){out.errs.push("zone du logement non renseignée");out.mont=0;out.tr=0;out.q=0;out.prix=0;out.mens=0;out.d={total:0,differe:0};out.maxRev=0;out.po=0;out.rpc=revenu/coeff;out.lim=[0,0,0,0];return out;}
  const maxRev=B.plafonds_ressources[z][Math.min(np,8)-1];
  const lim=B.tranches[z], rpc=revenu/coeff;                     // revenu par unité de coefficient familial
  let tr=lim.findIndex(l=>rpc<=l); if(tr<0) tr=3;
  const po=B.plafonds_operation[z][Math.min(np,5)-1];
  Object.assign(out,{rpc,maxRev,lim,po});
  if(!p.primo) out.errs.push("le PTZ est réservé aux primo-accédants");
  if(!p.rp) out.errs.push("le logement doit être la résidence principale");
  if(!t||!t.zones.includes(z)) out.errs.push("ce type de logement n'est pas éligible dans cette zone");
  if(revenu>maxRev) out.errs.push("revenus retenus ("+eur(revenu)+") supérieurs au plafond de la zone ("+eur(maxRev)+")");
  if(rpc>lim[3]) out.errs.push("revenus au-delà de la tranche 4");
  out.tr=tr; out.q=t?t.quotites[tr]:0;
  out.prix=Math.min(p.cout,po); out.mont=out.errs.length?0:Math.round(out.prix*out.q/100);
  out.d=B.durees[tr]; out.mens=out.mont>0&&out.d.total>out.d.differe?out.mont/((out.d.total-out.d.differe)*12):0;
  return out;
};
