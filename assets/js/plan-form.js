// Formulaire commun « projet / coût / foyer » du plan de financement et du comparateur de scénarios (ids pl_*).
window.planForm=(function(){
  const $=id=>document.getElementById(id), v=id=>$(id).value, N=window.planAdapter.N;
  const IDS=['type','usage','primo','canal','zone','acq','trav','traveco','nego','notaire','divers','garantie','fd','apport','patr','enf','rev1','chg1','rfr1','nais1','ass1','rev2','chg2','rfr2','nais2','ass2'];
  const cbs=[]; const fire=()=>cbs.forEach(f=>f());
  function read(){
    const emp=i=>({rev:v('pl_rev'+i),charges:v('pl_chg'+i),rfr:v('pl_rfr'+i),naissance:v('pl_nais'+i),ass:v('pl_ass'+i)});
    return {type_projet:v('pl_type'),usage:v('pl_usage'),primo:v('pl_primo'),canal:v('pl_canal'),zone:v('pl_zone'),acq:N(v('pl_acq')),trav:N(v('pl_trav')),travEco:N(v('pl_traveco')),nego:N(v('pl_nego')),
      notaire:N(v('pl_notaire')),divers:N(v('pl_divers')),garantie:N(v('pl_garantie')),fdossier:N(v('pl_fd')),apport:N(v('pl_apport')),patronal:N(v('pl_patr')),enfants:N(v('pl_enf')),emps:[emp(1),emp(2)]};
  }
  function snapshot(){const o={};IDS.forEach(k=>o[k]=v('pl_'+k));return o;}
  function apply(o){IDS.forEach(k=>{if(o&&o[k]!==undefined) $('pl_'+k).value=o[k];});}
  IDS.forEach(k=>{$('pl_'+k).addEventListener('input',fire);$('pl_'+k).addEventListener('change',fire);});
  $('pl_est_not').onclick=e=>{e.preventDefault();const neuf=['NEUF_VEFA','CONSTRUCTION_CCMI','CONSTRUCTION_SANS_CCMI'].includes(v('pl_type'));$('pl_notaire').value=Math.round(N(v('pl_acq'))*(neuf?0.025:0.075));fire();};
  $('pl_est_gar').onclick=e=>{e.preventDefault();const t=N(v('pl_acq'))+N(v('pl_trav'))+N(v('pl_nego'))+N(v('pl_notaire'))+N(v('pl_divers'))-N(v('pl_apport'))-N(v('pl_patr'));$('pl_garantie').value=Math.round(Math.max(0,t)*0.012);fire();};
  // Zone déduite du département et de la commune (liste importée par l'administrateur)
  let communes=new Map(), pending=null, ZURL=null;
  const ZMAP={Abis:'A',A:'A',B1:'B1',B2:'B2',C:'C'}, ZLIB={Abis:'A bis',A:'A',B1:'B1',B2:'B2',C:'C'};
  async function jget(u){const r=await fetch(u,{credentials:'same-origin'});if(!r.ok)throw new Error(r.status);return r.json();}
  async function loadDeps(){if(!ZURL)return;try{const j=await jget(ZURL);if(!j.available)return;
    $('pl_dep').innerHTML='<option value="">Choisir…</option>'+j.departements.map(d=>`<option value="${d[0]}">${d[0]} – ${d[1]||''}</option>`).join('');$('pl_geo').style.display='';if(pending)applyPending();}catch(e){}}
  async function loadCommunes(dep){communes=new Map();$('pl_communes').innerHTML='';$('pl_commune').value='';$('pl_zinfo').textContent='';$('pl_commune').disabled=!dep;if(!dep)return;
    try{const j=await jget(ZURL+(ZURL.includes('?')?'&':'?')+'dep='+encodeURIComponent(dep));(j.communes||[]).forEach(c=>communes.set(c[1].toLowerCase(),c));$('pl_communes').innerHTML=(j.communes||[]).map(c=>`<option value="${String(c[1]).replace(/"/g,'&quot;')}">`).join('');}catch(e){}}
  function pick(){const c=communes.get(v('pl_commune').trim().toLowerCase());if(c){$('pl_zone').value=ZMAP[c[2]]||'C';$('pl_zinfo').textContent='(déduite : '+ZLIB[c[2]]+')';fire();}else $('pl_zinfo').textContent='';}
  async function applyPending(){const g=pending;pending=null;if(!g||!g.dep)return;$('pl_dep').value=g.dep;await loadCommunes(g.dep);if(g.commune){$('pl_commune').value=g.commune;pick();}}
  $('pl_dep').addEventListener('change',()=>loadCommunes(v('pl_dep')));$('pl_commune').addEventListener('input',pick);$('pl_zone').addEventListener('change',()=>{$('pl_zinfo').textContent='';});
  return {read,snapshot,apply,onChange:f=>cbs.push(f),initGeo:url=>{ZURL=url;loadDeps();},geoGet:()=>({dep:v('pl_dep'),commune:v('pl_commune')}),geoSet:o=>{pending=o;if($('pl_geo').style.display!=='none')applyPending();}};
})();
