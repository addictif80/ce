<?php
// Liste des pièces justificatives, partagée par le module du portail (avec suivi enregistré par client) et la page publique /tools (sans enregistrement).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
if(!function_exists('piecesProfils')) require_once __DIR__ . '/../../includes/pieces.php';
$pcModeles = piecesModelesActifs();
$pcProfils = piecesProfils();
$pcContact = '';
if (!empty($currentUser)) { // portail : coordonnées du conseiller connecté
    $pcContact = trim(implode("\n", array_filter([trim(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '')), $currentUser['email_pro'] ?? '', $currentUser['tel_pro'] ?? ''])));
}
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
    <label class="form-label small mb-0">Vos coordonnées (sur le document remis au client)</label>
    <textarea class="form-control mb-2" id="pc_contact" rows="3" maxlength="300" placeholder="Nom, e-mail, téléphone"><?= e($pcContact) ?></textarea>
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
<div id="pcPrint" aria-hidden="true">
  <div class="pcp-head"><img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png" alt="Caisse d'Épargne" class="pcp-logo"><div class="pcp-title">LISTE DES PIÈCES À FOURNIR</div><div class="pcp-date" id="pcp_date"></div></div>
  <div id="pcPrintBody"></div>
</div>
<style>
    #pcPrint{position:absolute;left:-9999px;top:0;width:180mm;font-family:Arial,Helvetica,sans-serif;font-size:10pt;color:#000;line-height:1.35}
    .pcp-head{display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:2px solid #000;padding-bottom:6px;margin-bottom:10px}
    .pcp-logo{height:44px}.pcp-title{font-size:15pt;font-weight:700;text-align:center;flex:1}.pcp-date{font-size:9pt;text-align:right;min-width:70px}
    .pcp-ident{border:1px solid #000;padding:6px 10px;margin-bottom:10px}
    .pcp-ident div{margin:1px 0}
    .pcp-grp{break-inside:avoid;margin-bottom:8px}
    .pcp-grp-t{font-weight:700;border-bottom:1px solid #000;margin-bottom:3px;text-transform:uppercase;font-size:9pt}
    .pcp-row{display:flex;gap:8px;padding:2px 0;break-inside:avoid}
    .pcp-box{flex:0 0 12px;height:12px;border:1.5px solid #000;margin-top:2px;font-size:10px;line-height:10px;text-align:center}
    .pcp-det{display:block;font-size:8.5pt;color:#333}
    .pcp-recu{font-size:8.5pt;font-style:italic}
    .pcp-contact{margin-top:14px;border:1px solid #000;padding:6px 10px;break-inside:avoid;white-space:pre-line}
    .pcp-foot{margin-top:10px;font-size:8pt;color:#333;border-top:1px solid #999;padding-top:4px}
    @media print{
        @page{size:A4 portrait;margin:14mm 15mm}
        body > *:not(#pcPrint){display:none!important} /* #pcPrint est déplacé à la racine du body : le reste de la page n'occupe plus de place */
        #pcPrint{position:static!important;left:auto!important;width:auto!important}
        html,body{background:#fff!important}
    }
</style>
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
  // Document remis au client : généré à l'impression (bouton « Imprimer » ou Ctrl+P), indépendant de l'affichage de la page
  function renderPrint(){
    const {m,list}=items(), nb=parseInt($('pc_nb').value)||1, cl=((($('pc_client')||document.querySelector('form input[name=nom]')||{}).value)||'').trim(); // portail : nom saisi pour l'enregistrement
    $('pcp_date').textContent=new Date().toLocaleDateString('fr-FR');
    const ident=[`<div><b>Votre demande :</b> ${esc(m.nom)}</div>`,cl?`<div><b>Client :</b> ${esc(cl)}</div>`:'',`<div><b>Situation :</b> ${esc($('pc_profil').selectedOptions[0].textContent)}${nb>1?' · '+nb+' personnes concernées':''}</div>`].join('');
    const groups=[]; list.forEach(i=>{let g=groups.find(x=>x.n===i.groupe);if(!g){g={n:i.groupe,items:[]};groups.push(g);}g.items.push(i);});
    const body=groups.map(g=>`<div class="pcp-grp"><div class="pcp-grp-t">${esc(g.n)}</div>${g.items.map(i=>{const ok=!!checked[key(i)],multi=nb>1&&PERS.some(w=>i.libelle.toLowerCase().includes(w));
      return `<div class="pcp-row"><span class="pcp-box">${ok?'✓':''}</span><span>${esc(i.libelle)}${multi?' <b>(pour chacune des '+nb+' personnes)</b>':''}${ok?' <span class="pcp-recu">— déjà reçue</span>':''}${i.detail?'<span class="pcp-det">'+esc(i.detail)+'</span>':''}</span></div>`;}).join('')}</div>`).join('');
    const contact=(($('pc_contact')||{}).value||'').trim();
    $('pcPrintBody').innerHTML=`<div class="pcp-ident">${ident}</div><p style="margin:0 0 8px">Pour étudier votre dossier, merci de nous remettre les pièces suivantes :</p>${body||'<p>Aucune pièce à fournir.</p>'}${contact?`<div class="pcp-contact"><b>Votre contact</b>\n${esc(contact)}</div>`:''}<div class="pcp-foot">Liste indicative : des pièces complémentaires peuvent vous être demandées selon votre situation. Les pièces cochées ont déjà été reçues.</div>`;
  }
  document.body.appendChild($('pcPrint'));
  window.addEventListener('beforeprint',renderPrint);
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
