<?php
// Liste des pièces justificatives, partagée par le module du portail (avec suivi enregistré par client) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
if(!function_exists('piecesProfils')) require_once __DIR__ . '/../../includes/pieces.php';
$pcModeles = piecesModelesActifs();
$pcProfils = piecesProfils();
?>
<?php if (!$pcModeles): ?>
<div class="alert alert-info">Aucun modèle de liste n'est disponible pour le moment.</div>
<?php return; endif; ?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<style>
    .pc-groupe{font-weight:700;margin:14px 0 6px;padding-bottom:3px;border-bottom:2px solid #e8e8e8}
    .pc-item{display:flex;gap:10px;align-items:flex-start;padding:5px 0;border-bottom:1px dashed #eee}
    .pc-item input{margin-top:4px;width:18px;height:18px;flex:0 0 auto}
    .pc-item.done label{color:#888;text-decoration:line-through}
    .pc-detail{display:block;font-size:.8rem;color:#777}
    @media print{.pc-item input{appearance:none;border:1.5px solid #000;border-radius:3px}.pc-item input:checked{background:#000}}
</style>
<div class="row g-3">
 <div class="col-lg-4 no-print">
  <div class="cap-card"><h2><i class="fas fa-list-check me-2 text-danger"></i>La demande</h2>
    <label class="form-label small mb-0">Type de demande</label>
    <select class="form-select mb-2" id="pc_modele"><?php foreach ($pcModeles as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['nom']) ?></option><?php endforeach; ?></select>
    <label class="form-label small mb-0">Situation professionnelle</label>
    <select class="form-select mb-2" id="pc_profil"><?php foreach ($pcProfils as $k => $v): if ($k === 'tous') continue; ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select>
    <label class="form-label small mb-0">Nombre de personnes concernées</label>
    <select class="form-select mb-2" id="pc_nb"><option value="1">1</option><option value="2">2 (pièces personnelles pour chacun)</option></select>
    <?php if (!$capSave): ?><label class="form-label small mb-0">Nom du client (facultatif, pour l'impression)</label>
    <input type="text" class="form-control" id="pc_client" maxlength="100" autocomplete="off"><?php endif; ?>
    <?php if ($capSave): ?><label class="form-label small mb-0">Relance prévue le</label>
    <input type="date" class="form-control" id="pc_relance"><?php endif; ?>
  </div>
  <?php if ($capSave) simSaveForm($capLoad, 'pcPrepare'); ?>
 </div>
 <div class="col-lg-8">
  <div class="cap-card js-result">
    <h2 class="d-flex justify-content-between align-items-center flex-wrap gap-2"><span id="pc_titre">Pièces à fournir</span><span class="badge bg-secondary" id="pc_prog">0 / 0</span></h2>
    <div class="small text-muted mb-2" id="pc_sous"></div>
    <div id="pc_liste"></div>
    <p class="small text-muted mt-3 mb-0">Liste indicative : des pièces complémentaires peuvent être demandées selon la situation. Pour deux personnes, les pièces d'identité, de revenus et de domicile sont à fournir pour chacune.</p>
  </div>
 </div>
</div>
<script>
(function(){
  const M=<?= json_encode($pcModeles, JSON_UNESCAPED_UNICODE) ?>, PERS=['identité','revenus','domicile','bulletin','avis d\'imposition','pièce d\'identité','relevé'];
  const $=id=>document.getElementById(id), esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let checked={}; // clé de pièce -> true
  const key=i=>i.groupe+'|'+i.libelle;
  function items(){
    const m=M.find(x=>x.id==$('pc_modele').value)||M[0], prof=$('pc_profil').value;
    return {m,list:m.items.filter(i=>i.profil==='tous'||i.profil===prof)};
  }
  function draw(){
    const {m,list}=items(), nb=parseInt($('pc_nb').value)||1;
    let html='',last=null;
    list.forEach((i,idx)=>{
      if(i.groupe!==last){html+=`<div class="pc-groupe">${esc(i.groupe)}</div>`;last=i.groupe;}
      const k=key(i), ok=!!checked[k];
      html+=`<div class="pc-item ${ok?'done':''}"><input type="checkbox" id="pc_c${idx}" data-k="${esc(k)}" ${ok?'checked':''}><label for="pc_c${idx}" class="mb-0">${esc(i.libelle)}${nb>1&&PERS.some(w=>i.libelle.toLowerCase().includes(w))?' <span class="badge text-bg-light border">× '+nb+'</span>':''}${i.detail?'<span class="pc-detail">'+esc(i.detail)+'</span>':''}</label></div>`;
    });
    $('pc_liste').innerHTML=html||'<div class="text-muted">Aucune pièce pour cette situation.</div>';
    const done=list.filter(i=>checked[key(i)]).length;
    $('pc_prog').textContent=done+' / '+list.length+' reçue(s)';
    const cl=$('pc_client')&&$('pc_client').value.trim();
    $('pc_titre').textContent='Pièces à fournir — '+m.nom;
    $('pc_sous').textContent=(cl?'Client : '+cl+' · ':'')+'Situation : '+$('pc_profil').selectedOptions[0].textContent+' · '+new Date().toLocaleDateString('fr-FR');
    return {m,list,done};
  }
  $('pc_liste').addEventListener('change',e=>{const c=e.target.closest('input[data-k]');if(!c)return;if(c.checked)checked[c.dataset.k]=true;else delete checked[c.dataset.k];draw();});
  ['pc_modele','pc_profil','pc_nb','pc_client'].forEach(id=>{const el=$(id);if(el)el.addEventListener('input',draw);});
  // texte copié / envoyé : coches [x] et [ ]
  const orig=draw;
  window.pcText=function(){const {list}=items();return list.map(i=>(checked[key(i)]?'[x] ':'[ ] ')+i.libelle+(i.detail?' ('+i.detail+')':'')).join('\n');};
  window.toolsResultText=()=>$('pc_titre').textContent+'\n'+$('pc_sous').textContent+'\n\n'+window.pcText();
  window.toolsShare={get:()=>({c:Object.keys(checked)}),set:o=>{checked={};(o.c||[]).forEach(k=>checked[k]=true);draw();}};
  window.pcPrepare=function(f){
    const x=draw(), relance=$('pc_relance')?$('pc_relance').value:'';
    f.params.value=JSON.stringify({modele:+$('pc_modele').value,profil:$('pc_profil').value,nb:$('pc_nb').value,checked:Object.keys(checked),relance,client:f.nom.value});
    f.resultat.value=JSON.stringify({modele:x.m.nom,recues:x.done,total:x.list.length,relance});
    return true;
  };
  const load=<?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  if(load){if(load.modele)$('pc_modele').value=load.modele;if(load.profil)$('pc_profil').value=load.profil;if(load.nb)$('pc_nb').value=load.nb;checked={};(load.checked||[]).forEach(k=>checked[k]=true);if($('pc_relance')&&load.relance)$('pc_relance').value=load.relance;}
  draw();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
