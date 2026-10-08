<?php
// Boîte à outils PDF, partagée par le module du portail (avec « Enregistrer dans mes documents ») et la page publique /tools (sans enregistrement).
// Tout le traitement se fait dans le navigateur (bibliothèque pdf-lib) : les fichiers ne sont jamais envoyés, sauf si l'utilisateur connecté demande à enregistrer le résultat.
// Variables attendues : $capSave (bool), $pdfSaveUrl (string, URL d'enregistrement du résultat, si $capSave).
require __DIR__ . '/../_sim/style.php';
?>
<style>
    .pdf-drop{border:2px dashed #bbb;border-radius:12px;padding:22px;text-align:center;color:#666;background:#fafafa;cursor:pointer}
    .pdf-drop.over{border-color:#e4002b;background:#fff5f6}
    .pdf-file{display:flex;align-items:center;gap:8px;padding:6px 10px;border:1px solid #e5e5e5;border-radius:8px;margin-bottom:6px;background:#fff}
    .pdf-file .nm{flex:1;word-break:break-all}
</style>
<ul class="nav nav-pills mb-3 no-print" id="pdfTabs">
    <li class="nav-item"><button type="button" class="nav-link active" data-pdf-tab="merge"><i class="fas fa-object-group me-1"></i>Fusionner</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-pdf-tab="pages"><i class="fas fa-scissors me-1"></i>Extraire / supprimer des pages</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-pdf-tab="rotate"><i class="fas fa-rotate me-1"></i>Pivoter</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-pdf-tab="images"><i class="fas fa-images me-1"></i>Images en PDF</button></li>
</ul>
<div class="row g-3">
 <div class="col-lg-7">
  <div class="cap-card">
    <h2 id="pdf_h">Fusionner des PDF</h2>
    <p class="small text-muted" id="pdf_help">Ajoutez plusieurs PDF, ordonnez-les avec les flèches, puis fusionnez-les en un seul document.</p>
    <div class="pdf-drop" id="pdf_drop"><i class="fas fa-cloud-arrow-up fa-2x mb-2"></i><div>Glissez vos fichiers ici ou <u>cliquez pour choisir</u></div></div>
    <input type="file" id="pdf_input" class="d-none">
    <div id="pdf_files" class="mt-3"></div>
    <div id="pdf_opts" class="mt-2"></div>
    <div class="mt-3"><button type="button" class="btn btn-primary" id="pdf_go"><i class="fas fa-gears me-1"></i>Générer le PDF</button>
        <button type="button" class="btn btn-outline-secondary ms-1" id="pdf_clear">Vider</button></div>
    <div id="pdf_err" class="alert alert-danger py-2 small mt-3 d-none"></div>
  </div>
 </div>
 <div class="col-lg-5">
  <div class="cap-res" id="pdf_res" style="display:none">
    <div class="lbl">Résultat</div><div class="fs-5 fw-bold" id="pdf_info">–</div>
    <label class="form-label small mt-3 mb-0">Nom du fichier</label>
    <input type="text" class="form-control mb-2" id="pdf_name" value="document.pdf" maxlength="100">
    <a class="btn btn-light" id="pdf_dl" href="#" download="document.pdf"><i class="fas fa-download me-1"></i>Télécharger</a>
    <?php if (!empty($capSave)): ?><button type="button" class="btn btn-outline-light ms-1" id="pdf_save"><i class="fas fa-floppy-disk me-1"></i>Enregistrer dans mes documents</button><div class="small mt-2" id="pdf_savemsg"></div><?php endif; ?>
  </div>
  <p class="small text-muted mt-2"><i class="fas fa-user-shield me-1"></i><?= !empty($capSave) ? 'Le traitement se fait dans votre navigateur. Le résultat n\'est enregistré que si vous cliquez sur « Enregistrer dans mes documents ».' : 'Le traitement se fait dans votre navigateur : vos fichiers ne sont jamais envoyés ni conservés.' ?> Les PDF protégés par mot de passe ne sont pas pris en charge. Taille maximale conseillée : 60 Mo au total.</p>
 </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" integrity="sha384-weMABwrltA6jWR8DDe9Jp5blk+tZQh7ugpCsF3JwSA53WZM9/14PjS5LAJNHNjAI" crossorigin="anonymous"></script>
<script>
(function(){
  const $=id=>document.getElementById(id), esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const SAVE_URL=<?= json_encode(!empty($capSave) ? ($pdfSaveUrl ?? 'save.php') : null) ?>;
  const MAX=60*1024*1024;
  const TABS={
    merge:{h:'Fusionner des PDF',help:'Ajoutez plusieurs PDF, ordonnez-les avec les flèches, puis fusionnez-les en un seul document.',accept:'application/pdf',multi:true,out:'fusion.pdf'},
    pages:{h:'Extraire ou supprimer des pages',help:'Choisissez un PDF, indiquez les pages (ex. 1-3,5,8-) et le mode : les garder ou les supprimer.',accept:'application/pdf',multi:false,out:'pages.pdf'},
    rotate:{h:'Pivoter des pages',help:'Choisissez un PDF, l\'angle de rotation et, si besoin, les pages concernées (vide = toutes).',accept:'application/pdf',multi:false,out:'pivote.pdf'},
    images:{h:'Convertir des images en PDF',help:'Ajoutez des images JPG, PNG ou WEBP : une page A4 par image, dans l\'ordre de la liste.',accept:'image/jpeg,image/png,image/webp',multi:true,out:'images.pdf'},
  };
  let tab='merge', files=[], url=null;
  const OPTS={
    merge:()=>'',
    pages:()=>`<div class="row g-2"><div class="col-7"><label class="form-label small mb-0">Pages (ex. 1-3,5,8-)</label><input class="form-control" id="o_range" placeholder="1-3,5,8-"></div>
      <div class="col-5"><label class="form-label small mb-0">Mode</label><select class="form-select" id="o_mode"><option value="keep">Garder ces pages</option><option value="del">Supprimer ces pages</option></select></div></div>`,
    rotate:()=>`<div class="row g-2"><div class="col-6"><label class="form-label small mb-0">Rotation</label><select class="form-select" id="o_angle"><option value="90">90° à droite</option><option value="180">180°</option><option value="270">90° à gauche</option></select></div>
      <div class="col-6"><label class="form-label small mb-0">Pages (vide = toutes)</label><input class="form-control" id="o_range" placeholder="ex. 2,4-6"></div></div>`,
    images:()=>'',
  };
  function setTab(t){
    tab=t; files=[]; hideResult(); $('pdf_err').classList.add('d-none');
    document.querySelectorAll('[data-pdf-tab]').forEach(b=>b.classList.toggle('active',b.dataset.pdfTab===t));
    $('pdf_h').textContent=TABS[t].h; $('pdf_help').textContent=TABS[t].help;
    $('pdf_input').accept=TABS[t].accept; $('pdf_input').multiple=TABS[t].multi; $('pdf_input').value='';
    $('pdf_opts').innerHTML=OPTS[t](); drawFiles();
  }
  const size=n=>n>1048576?(n/1048576).toFixed(1).replace('.',',')+' Mo':Math.max(1,Math.round(n/1024))+' Ko';
  function drawFiles(){
    $('pdf_files').innerHTML=files.map((f,i)=>`<div class="pdf-file"><i class="fas ${tab==='images'?'fa-image':'fa-file-pdf'} text-danger"></i><span class="nm">${esc(f.name)} <span class="text-muted small">(${size(f.size)})</span></span>
      ${TABS[tab].multi?`<button type="button" class="btn btn-sm btn-outline-secondary" data-mv="${i}" data-d="-1" ${i===0?'disabled':''}><i class="fas fa-arrow-up"></i></button><button type="button" class="btn btn-sm btn-outline-secondary" data-mv="${i}" data-d="1" ${i===files.length-1?'disabled':''}><i class="fas fa-arrow-down"></i></button>`:''}
      <button type="button" class="btn btn-sm btn-outline-danger" data-rm="${i}"><i class="fas fa-xmark"></i></button></div>`).join('');
  }
  function addFiles(list){
    const arr=[...list], t=TABS[tab], ok=arr.filter(f=>tab==='images'?/^image\/(jpeg|png|webp)$/.test(f.type):(f.type==='application/pdf'||/\.pdf$/i.test(f.name)));
    if(ok.length<arr.length) showErr('Certains fichiers ont été ignorés : format non pris en charge.'); else $('pdf_err').classList.add('d-none');
    files=t.multi?files.concat(ok):ok.slice(0,1); hideResult(); drawFiles();
  }
  $('pdf_drop').onclick=()=>$('pdf_input').click();
  $('pdf_input').onchange=e=>{addFiles(e.target.files);e.target.value='';};
  ['dragover','dragenter'].forEach(ev=>$('pdf_drop').addEventListener(ev,e=>{e.preventDefault();$('pdf_drop').classList.add('over');}));
  ['dragleave','drop'].forEach(ev=>$('pdf_drop').addEventListener(ev,e=>{e.preventDefault();$('pdf_drop').classList.remove('over');}));
  $('pdf_drop').addEventListener('drop',e=>addFiles(e.dataTransfer.files));
  $('pdf_files').addEventListener('click',e=>{
    const mv=e.target.closest('[data-mv]'), rm=e.target.closest('[data-rm]');
    if(mv){const i=+mv.dataset.mv,j=i+(+mv.dataset.d);[files[i],files[j]]=[files[j],files[i]];drawFiles();hideResult();}
    if(rm){files.splice(+rm.dataset.rm,1);drawFiles();hideResult();}
  });
  document.getElementById('pdfTabs').addEventListener('click',e=>{const b=e.target.closest('[data-pdf-tab]');if(b)setTab(b.dataset.pdfTab);});
  $('pdf_clear').onclick=()=>setTab(tab);
  function showErr(m){const el=$('pdf_err');el.textContent=m;el.classList.remove('d-none');}
  function hideResult(){$('pdf_res').style.display='none';if(url){URL.revokeObjectURL(url);url=null;}}

  // « 1-3,5,8- » -> indices (base 0) ; vide = toutes
  function parseRange(txt,n){
    txt=(txt||'').replace(/\s/g,''); if(!txt) return [...Array(n).keys()];
    const set=new Set();
    for(const part of txt.split(',')){
      const m=part.match(/^(\d*)-(\d*)$/)||part.match(/^(\d+)$/);
      if(!m) throw new Error('Pages non reconnues : « '+part+' ».');
      let a,b; if(m.length===2){a=b=+m[1];} else {a=m[1]===''?1:+m[1]; b=m[2]===''?n:+m[2];}
      if(a<1||b<a||b>n) throw new Error('Pages hors du document (1 à '+n+') : « '+part+' ».');
      for(let i=a;i<=b;i++) set.add(i-1);
    }
    return [...set].sort((x,y)=>x-y);
  }
  const buf=f=>f.arrayBuffer();
  async function load(f){
    try{return await PDFLib.PDFDocument.load(await buf(f));}
    catch(e){throw new Error('« '+f.name+' » : PDF illisible ou protégé par mot de passe.');}
  }
  async function toPngBytes(f){ // WEBP -> PNG via canvas
    const bmp=await createImageBitmap(f), c=document.createElement('canvas'); c.width=bmp.width; c.height=bmp.height;
    c.getContext('2d').drawImage(bmp,0,0);
    return new Uint8Array(await (await new Promise(r=>c.toBlob(r,'image/png'))).arrayBuffer());
  }
  async function build(){
    const {PDFDocument,degrees}=PDFLib;
    if(!files.length) throw new Error(tab==='images'?'Ajoutez au moins une image.':'Ajoutez un PDF.');
    if(files.reduce((t,f)=>t+f.size,0)>MAX) throw new Error('Fichiers trop volumineux (60 Mo maximum au total).');
    if(tab==='merge'){
      if(files.length<2) throw new Error('Ajoutez au moins deux PDF à fusionner.');
      const out=await PDFDocument.create();
      for(const f of files){const src=await load(f);(await out.copyPages(src,src.getPageIndices())).forEach(p=>out.addPage(p));}
      return out;
    }
    if(tab==='pages'){
      const src=await load(files[0]), n=src.getPageCount(), sel=parseRange($('o_range').value,n);
      if(!$('o_range').value.trim()) throw new Error('Indiquez les pages (ex. 1-3,5).');
      const keep=$('o_mode').value==='keep'?sel:[...Array(n).keys()].filter(i=>!sel.includes(i));
      if(!keep.length) throw new Error('Aucune page ne resterait dans le document.');
      const out=await PDFDocument.create(); (await out.copyPages(src,keep)).forEach(p=>out.addPage(p)); return out;
    }
    if(tab==='rotate'){
      const doc=await load(files[0]), n=doc.getPageCount(), sel=parseRange($('o_range').value,n), a=+$('o_angle').value;
      sel.forEach(i=>{const p=doc.getPage(i);p.setRotation(degrees((p.getRotation().angle+a)%360));});
      return doc;
    }
    // images
    const out=await PDFDocument.create(), W=595.28, H=841.89, M=20;
    for(const f of files){
      let img; const bytes=new Uint8Array(await buf(f));
      if(f.type==='image/jpeg') img=await out.embedJpg(bytes); else if(f.type==='image/png') img=await out.embedPng(bytes); else img=await out.embedPng(await toPngBytes(f));
      const land=img.width>img.height, pw=land?H:W, ph=land?W:H, k=Math.min((pw-2*M)/img.width,(ph-2*M)/img.height);
      const page=out.addPage([pw,ph]), w=img.width*k, h=img.height*k;
      page.drawImage(img,{x:(pw-w)/2,y:(ph-h)/2,width:w,height:h});
    }
    return out;
  }
  let lastBlob=null;
  $('pdf_go').onclick=async()=>{
    const b=$('pdf_go'); b.disabled=true; $('pdf_err').classList.add('d-none'); hideResult();
    try{
      if(!window.PDFLib) throw new Error('La bibliothèque PDF n\'a pas pu être chargée : vérifiez votre connexion.');
      const doc=await build(), bytes=await doc.save();
      lastBlob=new Blob([bytes],{type:'application/pdf'}); url=URL.createObjectURL(lastBlob);
      const name=TABS[tab].out; $('pdf_name').value=name;
      $('pdf_info').textContent=doc.getPageCount()+' page(s) · '+size(bytes.length);
      $('pdf_dl').href=url; $('pdf_dl').download=name; $('pdf_res').style.display='';
      if($('pdf_savemsg')) $('pdf_savemsg').textContent='';
    }catch(e){showErr(e.message||'Erreur lors du traitement.');}
    b.disabled=false;
  };
  $('pdf_name').addEventListener('input',()=>{let n=$('pdf_name').value.trim()||'document'; if(!/\.pdf$/i.test(n)) n+='.pdf'; $('pdf_dl').download=n;});
  if($('pdf_save')) $('pdf_save').onclick=async()=>{
    if(!lastBlob||!SAVE_URL) return;
    let n=$('pdf_name').value.trim()||'document'; if(!/\.pdf$/i.test(n)) n+='.pdf';
    const fd=new FormData(); fd.append('file',lastBlob,n); fd.append('nom',n);
    $('pdf_savemsg').textContent='Enregistrement…';
    try{const r=await fetch(SAVE_URL,{method:'POST',body:fd}); const j=await r.json();
      $('pdf_savemsg').textContent=j.ok?'Enregistré dans vos documents.':(j.error||'Échec de l\'enregistrement.');
      if(j.ok) setTimeout(()=>location.reload(),900);
    }catch(e){$('pdf_savemsg').textContent='Échec de l\'enregistrement.';}
  };
  setTab('merge');
})();
</script>
