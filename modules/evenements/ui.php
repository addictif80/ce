<?php
// Parcours « événements de vie » : démarches bancaires, solutions à proposer et pièces à demander, avec une liste à cocher imprimable.
// Contenu modifiable dans l'administration (barèmes). Partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require_once __DIR__ . '/../../includes/baremes.php';
$ev = baremeGet('evenements'); $groupes = $ev['data']['groupes'] ?? [];
require __DIR__ . '/../_sim/style.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['evenements']) ?>
<div class="row g-3">
 <div class="col-lg-4">
  <div class="cap-card"><h2><i class="fas fa-route me-2 text-danger"></i>L'événement</h2>
    <label class="form-label small mb-0" for="ev_sel">Événement de vie</label>
    <select class="form-select mb-2" id="ev_sel"><?php foreach ($groupes as $i => $g): ?><option value="<?= (int)$i ?>"><?= e($g['titre']) ?></option><?php endforeach; ?></select>
    <label class="form-label small mb-0" for="ev_nom">Client (facultatif, pour le document)</label>
    <input type="text" class="form-control" id="ev_nom" maxlength="120" placeholder="Nom du client">
    <div class="form-text">Cochez ce qui est fait ou remis. La liste imprimée peut être donnée au client.</div>
  </div>
  <?php if (!empty($capSave)) simSaveForm($capLoad, 'evPrepare'); ?>
 </div>
 <div class="col-lg-8"><div class="cap-card js-result"><h2 id="ev_titre">–</h2><div id="ev_out"></div></div></div>
</div>
<script>
(function(){
  const G=<?= json_encode($groupes) ?>;
  const $=id=>document.getElementById(id), esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const CATS=[['Démarche','fa-list-check','Démarches à réaliser'],['Proposition','fa-lightbulb','Solutions à proposer'],['Pièce à fournir','fa-folder-open','Pièces à demander']];
  let checked={};
  function render(){
    const g=G[parseInt($('ev_sel').value)]||{titre:'',lignes:[]}; const nom=$('ev_nom').value.trim();
    $('ev_titre').textContent=g.titre+(nom?' — '+nom:'');
    const known=CATS.map(c=>c[0]); let html='';
    const lines=g.lignes.map((l,i)=>Object.assign({},l,{i}));
    CATS.concat(lines.some(l=>!known.includes(l.valeur))?[['__autre','fa-circle-info','Autres']]:[]).forEach(([k,ic,titre])=>{
      const L=lines.filter(l=>k==='__autre'?!known.includes(l.valeur):l.valeur===k); if(!L.length) return;
      html+=`<div class="mb-3"><div class="fw-semibold mb-1"><i class="fas ${ic} me-1 text-danger"></i>${titre}</div>`+L.map(l=>{const id='evc_'+$('ev_sel').value+'_'+l.i;return `<div class="form-check"><input class="form-check-input" type="checkbox" id="${id}" ${checked[id]?'checked':''}><label class="form-check-label" for="${id}">${esc(l.libelle)}${l.note?`<div class="small text-muted">${esc(l.note)}</div>`:''}</label></div>`;}).join('')+'</div>';
    });
    $('ev_out').innerHTML=html||'<div class="text-muted">Aucune ligne renseignée pour cet événement.</div>';
  }
  document.addEventListener('change',e=>{const t=e.target;if(t&&t.id&&t.id.startsWith('evc_')) checked[t.id]=t.checked;});
  $('ev_sel').addEventListener('change',render);$('ev_nom').addEventListener('input',render);
  window.toolsShare={get:()=>({c:Object.keys(checked).filter(k=>checked[k])}),set:o=>{checked={};(o.c||[]).forEach(k=>checked[k]=true);render();}};
  window.evPrepare=function(f){f.params.value=JSON.stringify({sel:$('ev_sel').value,nom:$('ev_nom').value,c:Object.keys(checked).filter(k=>checked[k])});
    const g=G[parseInt($('ev_sel').value)]||{titre:'',lignes:[]};const n=Object.keys(checked).filter(k=>checked[k]&&k.startsWith('evc_'+$('ev_sel').value+'_')).length;
    f.resultat.value=JSON.stringify({evenement:g.titre,fait:n,total:g.lignes.length});return true;};
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){if(load.sel!==undefined)$('ev_sel').value=load.sel;if(load.nom)$('ev_nom').value=load.nom;(load.c||[]).forEach(k=>checked[k]=true);}
  render();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
