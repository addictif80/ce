<?php
// Calculateur de dates (jours ouvrés, ouvrables, calendaires, mois, années), partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<div class="row g-3">
 <div class="col-lg-6">
  <div class="cap-card"><h2><i class="fas fa-calendar-plus me-2 text-danger"></i>Ajouter ou retrancher une durée</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Date de départ</label><input type="date" class="form-control" id="dt_start"></div>
      <div class="col-6"><label class="form-label small mb-0">Sens</label><select class="form-select" id="dt_sens"><option value="1">Ajouter</option><option value="-1">Retrancher</option></select></div>
      <div class="col-6"><label class="form-label small mb-0">Durée</label><input type="number" min="0" step="1" class="form-control" id="dt_n" value="10"></div>
      <div class="col-6"><label class="form-label small mb-0">Unité</label><select class="form-select" id="dt_unit">
        <option value="cal">Jours calendaires</option><option value="ouvre">Jours ouvrés (lundi-vendredi, hors fériés)</option><option value="ouvrable">Jours ouvrables (lundi-samedi, hors fériés)</option><option value="mois">Mois</option><option value="an">Années</option></select></div>
    </div>
    <div class="form-text">Jours fériés pris en compte : jours fériés légaux de France métropolitaine (fixes et mobiles).</div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-calendar-days me-2 text-danger"></i>Écart entre deux dates</h2>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Du</label><input type="date" class="form-control" id="dt_a"></div>
      <div class="col-6"><label class="form-label small mb-0">Au</label><input type="date" class="form-control" id="dt_b"></div>
    </div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'dtPrepare'); ?>
 </div>
 <div class="col-lg-6">
  <div class="cap-res js-result">
    <div class="lbl">Date obtenue</div><div class="big" id="dr_date">–</div><div class="lbl mt-1" id="dr_note"></div>
    <hr style="border-color:rgba(255,255,255,.25)">
    <div class="lbl">Écart entre les deux dates</div>
    <table class="table table-sm table-borderless text-white mb-0 mt-1" style="--bs-table-color:#fff;--bs-table-bg:transparent"><tbody id="dr_diff"><tr><td colspan="2">–</td></tr></tbody></table>
  </div>
  <div class="cap-card"><h2><i class="fas fa-stopwatch me-2 text-danger"></i>Délais usuels à partir d'une date</h2>
    <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small mb-0">Date de réception / de départ</label><input type="date" class="form-control" id="dt_pre"></div></div>
    <table class="table table-sm mb-1"><tbody id="dr_pre"></tbody></table>
    <div class="form-text">Délais courants, calculés en jours calendaires. À confirmer selon le contrat et la réglementation applicables au dossier (voir le mémo des délais légaux).</div>
  </div>
  <div class="cap-card"><h2><i class="fas fa-flag me-2 text-danger"></i>Jours fériés de l'année</h2>
    <div class="row g-2 mb-2"><div class="col-6"><input type="number" min="1900" max="2200" class="form-control" id="dt_year" aria-label="Année des jours fériés"></div></div>
    <div id="dr_ferie" class="small"></div>
  </div>
 </div>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id);
  const J=['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'], M=['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
  const iso=d=>d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
  const parse=s=>{if(!/^\d{4}-\d{2}-\d{2}$/.test(s||'')) return null;const [y,m,d]=s.split('-').map(Number);return new Date(y,m-1,d);};
  const fmt=d=>J[d.getDay()]+' '+d.getDate()+(d.getDate()===1?'er':'')+' '+M[d.getMonth()]+' '+d.getFullYear();
  const addDays=(d,n)=>{const x=new Date(d.getFullYear(),d.getMonth(),d.getDate()+n);return x;};
  const cache={};
  function paques(y){const a=y%19,b=Math.floor(y/100),c=y%100,d=Math.floor(b/4),e=b%4,f=Math.floor((b+8)/25),g=Math.floor((b-f+1)/3),h=(19*a+b-d-g+15)%30,i=Math.floor(c/4),k=c%4,l=(32+2*e+2*i-h-k)%7,m=Math.floor((a+11*h+22*l)/451),mo=Math.floor((h+l-7*m+114)/31),da=((h+l-7*m+114)%31)+1;return new Date(y,mo-1,da);}
  function feries(y){
    if(cache[y]) return cache[y];
    const p=paques(y), L=[[new Date(y,0,1),'Jour de l\'An'],[addDays(p,1),'Lundi de Pâques'],[new Date(y,4,1),'Fête du Travail'],[new Date(y,4,8),'Victoire 1945'],[addDays(p,39),'Ascension'],[addDays(p,50),'Lundi de Pentecôte'],[new Date(y,6,14),'Fête nationale'],[new Date(y,7,15),'Assomption'],[new Date(y,10,1),'Toussaint'],[new Date(y,10,11),'Armistice 1918'],[new Date(y,11,25),'Noël']];
    return cache[y]={list:L,set:new Set(L.map(x=>iso(x[0])))};
  }
  const isFerie=d=>feries(d.getFullYear()).set.has(iso(d));
  const isOuvre=d=>d.getDay()!==0&&d.getDay()!==6&&!isFerie(d);
  const isOuvrable=d=>d.getDay()!==0&&!isFerie(d);
  function addWork(d,n,test){const s=n<0?-1:1;let k=Math.abs(n),x=d;while(k>0){x=addDays(x,s);if(test(x)) k--;}return x;}
  function addMonths(d,n){const x=new Date(d.getFullYear(),d.getMonth()+n,1);const last=new Date(x.getFullYear(),x.getMonth()+1,0).getDate();x.setDate(Math.min(d.getDate(),last));return x;}
  function countWork(a,b,test){let n=0,x=a;const s=a<=b?1:-1;while(iso(x)!==iso(b)){x=addDays(x,s);if(test(x)) n++;}return n;}
  function run(){
    const st=parse($('dt_start').value), n=parseInt($('dt_n').value)||0, sg=parseInt($('dt_sens').value), u=$('dt_unit').value;
    if(st){
      let r;
      if(u==='cal') r=addDays(st,sg*n); else if(u==='ouvre') r=addWork(st,sg*n,isOuvre); else if(u==='ouvrable') r=addWork(st,sg*n,isOuvrable);
      else if(u==='mois') r=addMonths(st,sg*n); else r=addMonths(st,sg*12*n);
      $('dr_date').textContent=fmt(r);
      const nt=[]; if(isFerie(r)) nt.push('jour férié'); else if(r.getDay()===0) nt.push('dimanche'); else if(r.getDay()===6) nt.push('samedi');
      $('dr_note').textContent=nt.length?'Attention : la date obtenue tombe un '+nt.join(', ')+'.':'';
    } else {$('dr_date').textContent='–';$('dr_note').textContent='Renseignez une date de départ.';}
    const a=parse($('dt_a').value), b=parse($('dt_b').value);
    if(a&&b){
      const sgn=a<=b?1:-1, lo=sgn>0?a:b, hi=sgn>0?b:a;
      let y=hi.getFullYear()-lo.getFullYear(), m=hi.getMonth()-lo.getMonth(), dd=hi.getDate()-lo.getDate();
      if(dd<0){m--;dd+=new Date(hi.getFullYear(),hi.getMonth(),0).getDate();} if(m<0){y--;m+=12;}
      const cal=Math.round((hi-lo)/864e5), ouv=countWork(lo,hi,isOuvre), oub=countWork(lo,hi,isOuvrable);
      $('dr_diff').innerHTML=[['Jours calendaires',cal],['Jours ouvrés (lun-ven)',ouv],['Jours ouvrables (lun-sam)',oub],['Durée',(y?y+' an'+(y>1?'s':'')+' ':'')+(m?m+' mois ':'')+dd+' jour'+(dd>1?'s':'')]].map(x=>`<tr><td>${x[0]}</td><td class="text-end">${x[1]}</td></tr>`).join('');
    } else $('dr_diff').innerHTML='<tr><td colspan="2">Renseignez les deux dates.</td></tr>';
    const pre=parse($('dt_pre').value);
    const PRE=[['Offre de prêt immobilier : acceptation possible à partir du',d=>addDays(d,11),'11e jour après la réception (délai de réflexion de 10 jours)'],['Offre de prêt immobilier : validité minimale jusqu\'au',d=>addDays(d,30),'30 jours minimum'],['Crédit à la consommation : fin du délai de rétractation',d=>addDays(d,14),'14 jours calendaires après l\'acceptation'],['Offre de crédit à la consommation : maintenue jusqu\'au',d=>addDays(d,15),'15 jours minimum'],['Prélèvement autorisé : contestation possible jusqu\'au',d=>addDays(d,56),'8 semaines'],['Opération non autorisée : contestation possible jusqu\'au',d=>addMonths(d,13),'13 mois']];
    $('dr_pre').innerHTML=pre?PRE.map(x=>{const r=x[1](pre);return `<tr><td>${x[0]}<div class="text-muted small">${x[2]}</div></td><td class="text-end text-nowrap">${fmt(r)}</td></tr>`;}).join(''):'<tr><td class="text-muted">Renseignez une date.</td></tr>';
    const yr=parseInt($('dt_year').value)||new Date().getFullYear();
    $('dr_ferie').innerHTML='<div class="row">'+feries(yr).list.sort((a,b)=>a[0]-b[0]).map(x=>`<div class="col-6 d-flex justify-content-between"><span>${x[1]}</span><span class="text-muted">${J[x[0].getDay()].slice(0,3)}. ${x[0].getDate()} ${M[x[0].getMonth()].slice(0,4)}.</span></div>`).join('')+'</div>';
    return {st:$('dt_start').value,n,sg,u,a:$('dt_a').value,b:$('dt_b').value,pre:$('dt_pre').value,res:$('dr_date').textContent};
  }
  const IDS=['start','sens','n','unit','a','b','pre','year'];
  IDS.forEach(k=>{const el=$('dt_'+k);el.addEventListener('input',run);el.addEventListener('change',run);});
  const today=iso(new Date());
  $('dt_start').value=today; $('dt_pre').value=today; $('dt_year').value=new Date().getFullYear();
  window.dtPrepare=function(f){const x=run();const p={};IDS.forEach(k=>p[k]=$('dt_'+k).value);f.params.value=JSON.stringify(p);f.resultat.value=JSON.stringify({date:x.res,depart:x.st});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load) IDS.forEach(k=>{if(load[k]!==undefined) $('dt_'+k).value=load[k];});
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
