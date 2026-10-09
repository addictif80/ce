// Boutons communs des outils (portail et /tools) : copier le lien de partage, copier le résultat, imprimer, envoyer par e-mail.
// Le lien de partage encode les valeurs saisies dans la partie « # » de l'adresse : elle n'est jamais envoyée au serveur.
(function(){
  if(window.__toolsCommon) return; window.__toolsCommon=true;
  const SKIP='[data-noshare],.modal input,.modal select,.modal textarea,form[role=search] input';
  function fields(){
    return [...document.querySelectorAll('input[id],select[id],textarea[id]')].filter(el=>
      !['file','password','hidden','submit','button'].includes(el.type)&&!el.matches(SKIP)&&!el.id.startsWith('search'));
  }
  const b64=s=>btoa(unescape(encodeURIComponent(s))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
  const unb64=s=>decodeURIComponent(escape(atob(s.replace(/-/g,'+').replace(/_/g,'/'))));
  function collect(){
    const o={f:{}};
    fields().forEach(el=>{o.f[el.id]=(el.type==='checkbox'||el.type==='radio')?el.checked:el.value;});
    if(window.toolsShare&&window.toolsShare.get) o.x=window.toolsShare.get();
    return o;
  }
  function shareUrl(){return location.origin+location.pathname+location.search+'#s='+b64(JSON.stringify(collect()));}
  function restore(){
    const m=location.hash.match(/^#s=([\w-]+)$/); if(!m) return;
    let o; try{o=JSON.parse(unb64(m[1]));}catch(e){return;}
    if(o.x&&window.toolsShare&&window.toolsShare.set){try{window.toolsShare.set(o.x);}catch(e){}}
    Object.keys(o.f||{}).forEach(id=>{
      const el=document.getElementById(id); if(!el||el.matches(SKIP)) return;
      if(el.type==='checkbox'||el.type==='radio') el.checked=!!o.f[id]; else el.value=o.f[id];
      el.dispatchEvent(new Event('input',{bubbles:true})); el.dispatchEvent(new Event('change',{bubbles:true}));
    });
  }
  function resultText(){
    if(window.toolsResultText) return window.toolsResultText();
    const title=(document.querySelector('h1,h4')||{}).textContent||document.title;
    const parts=[...document.querySelectorAll('.js-result')].map(el=>el.innerText.trim()).filter(Boolean);
    return title.trim()+'\n\n'+parts.join('\n\n');
  }
  async function copy(text){
    try{await navigator.clipboard.writeText(text);return true;}catch(e){
      const t=document.createElement('textarea');t.value=text;document.body.appendChild(t);t.select();
      let ok=false;try{ok=document.execCommand('copy');}catch(_){} t.remove();return ok;}
  }
  function flash(btn,txt){const h=btn.innerHTML;btn.innerHTML='<i class="fas fa-check me-1"></i>'+txt;setTimeout(()=>btn.innerHTML=h,1600);}
  document.addEventListener('click',async e=>{
    const b=e.target.closest('[data-tool-action]'); if(!b) return;
    const a=b.dataset.toolAction;
    if(a==='link'){history.replaceState(null,'',shareUrl().slice(location.origin.length)); flash(b,(await copy(shareUrl()))?'Lien copié':'Copie impossible');}
    else if(a==='copy'){flash(b,(await copy(resultText()))?'Résultat copié':'Copie impossible');}
    else if(a==='print'){window.print();}
    else if(a==='mail'){
      const body=(resultText()+'\n\nLien : '+shareUrl()).slice(0,1800);
      location.href='mailto:?subject='+encodeURIComponent((document.querySelector('h1,h4')||{}).textContent||document.title)+'&body='+encodeURIComponent(body);
    }
  });
  window.addEventListener('load',restore);
})();

// ── Document client unifié ───────────────────────────────────────────────────
// À l'impression (bouton « Imprimer » ou Ctrl+P), les outils sans document propre impriment un document standard :
// en-tête Caisse d'Épargne, titre, date, données saisies, résultats, mentions légales et barèmes utilisés.
// Un outil peut fournir son propre document (window.toolsOwnPrint = true) ou compléter celui-ci (window.toolsDoc = () => ({title, inputs:[[libellé, valeur]], html})).
(function(){
  if(window.__toolsDoc) return; window.__toolsDoc=true;
  const LOGO='https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png';
  const esc=t=>String(t).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const CSS=`
  #tdoc{display:none}
  @media print{
    html.tdoc-on body > *:not(#tdoc){display:none!important}
    html.tdoc-on body{background:#fff!important}
    html.tdoc-on #tdoc{display:block;font-family:Arial,Helvetica,sans-serif;color:#000;font-size:10.5pt;line-height:1.35}
    #tdoc .td-head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:3px solid #e4002b;padding-bottom:8px;margin-bottom:14px}
    #tdoc .td-head img{height:38px}
    #tdoc .td-head .td-date{font-size:9pt;color:#555;text-align:right}
    #tdoc h1{font-size:17pt;margin:0 0 4px;color:#000}
    #tdoc .td-sub{color:#e4002b;font-weight:700;font-size:9pt;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px}
    #tdoc h2{font-size:11.5pt;margin:14px 0 6px;padding-bottom:3px;border-bottom:1px solid #999;break-after:avoid}
    #tdoc table{border-collapse:collapse;width:100%;font-size:10pt}
    #tdoc td,#tdoc th{border-bottom:1px solid #ddd;padding:3px 6px;text-align:left;vertical-align:top}
    #tdoc td:last-child{text-align:right}
    #tdoc .td-res{border:1px solid #999;border-radius:4px;padding:10px 12px;margin-bottom:8px;break-inside:avoid;background:#fff!important;color:#000!important}
    #tdoc .td-res *{color:#000!important;background:transparent!important;box-shadow:none!important;opacity:1!important}
    #tdoc .td-res .big{font-size:15pt;font-weight:700}
    #tdoc .td-res button,#tdoc .td-res .no-print{display:none!important}
    #tdoc .td-foot{margin-top:18px;border-top:1px solid #999;padding-top:6px;font-size:8pt;color:#444}
    #tdoc .td-foot p{margin:0 0 3px}
  }`;
  function label(el){
    let t='';
    if(el.id){const l=document.querySelector('label[for="'+el.id+'"]'); if(l) t=l.textContent;}
    if(!t){const c=el.closest('.col-6,.col-md-3,.col-md-4,.col-md-6,.col-12,.mb-3,div'); const l=c&&c.querySelector('label'); if(l) t=l.textContent;}
    if(!t) t=el.getAttribute('aria-label')||el.getAttribute('placeholder')||'';
    return t.replace(/\s+/g,' ').replace(/\*/g,'').trim();
  }
  function visible(el){return !el.closest('[hidden],.d-none,.no-print-doc')&&(el.offsetParent!==null||el.type==='hidden'&&false);}
  function inputs(){
    const rows=[], seen=new Set();
    document.querySelectorAll('input[id],select[id],textarea[id]').forEach(el=>{
      if(['file','password','hidden','submit','button'].includes(el.type)||el.matches('[data-noshare],.modal *,form[role=search] *')||el.id.startsWith('search')) return;
      if(!visible(el)) return;
      let v;
      if(el.type==='checkbox') {if(!el.checked) return; v='Oui';}
      else if(el.type==='radio') {if(!el.checked) return; v=label(el)||'Oui';}
      else if(el.tagName==='SELECT') {v=el.selectedIndex>=0?el.options[el.selectedIndex].text:''; if(!el.value) return;}
      else v=el.value;
      v=String(v).trim(); if(v==='') return;
      const l=label(el); if(!l) return;
      const key=l+'|'+v; if(seen.has(key)) return; seen.add(key);
      rows.push([l,v+(el.dataset&&el.dataset.unit?' '+el.dataset.unit:'')]);
    });
    return rows;
  }
  function build(){
    const extra=window.toolsDoc?window.toolsDoc():{};
    const title=extra.title||((document.querySelector('h1')||document.querySelector('h4')||{}).textContent||document.title).trim();
    const rows=extra.inputs||inputs();
    const res=[...document.querySelectorAll('.js-result,.js-print')].filter(el=>el.offsetParent!==null).map(el=>{const c=el.cloneNode(true);c.querySelectorAll('button,.no-print,[data-print-skip],script,style').forEach(x=>x.remove());return '<div class="td-res">'+c.innerHTML+'</div>';}).join('');
    const notes=[...document.querySelectorAll('[data-bareme-note]')].map(n=>'<p>'+esc(n.dataset.baremeNote)+'</p>').join('');
    const d=new Date().toLocaleDateString('fr-FR',{day:'2-digit',month:'long',year:'numeric'});
    return '<div class="td-head"><img src="'+LOGO+'" alt="Caisse d\'Épargne" onerror="this.style.display=\'none\'"><div class="td-date">Édité le '+esc(d)+'</div></div>'
      +'<h1>'+esc(title)+'</h1><div class="td-sub">Simulation non contractuelle</div>'
      +(rows.length?'<h2>Données saisies</h2><table>'+rows.map(r=>'<tr><td>'+esc(r[0])+'</td><td>'+esc(r[1])+'</td></tr>').join('')+'</table>':'')
      +(res||extra.html?'<h2>Résultat</h2>'+(res||'')+(extra.html||''):'')
      +'<div class="td-foot"><p>Simulation indicative établie à partir des informations saisies, sans valeur contractuelle ni précontractuelle : elle ne constitue pas une offre de prêt ni un conseil personnalisé. Les résultats sont à vérifier avant toute décision.</p>'+notes+'<p>Caisse d\'Épargne et de Prévoyance de Midi-Pyrénées — document généré par un outil d\'aide, qui ne se substitue pas aux outils internes du groupe BPCE.</p></div>';
  }
  const st=document.createElement('style'); st.textContent=CSS; document.head.appendChild(st);
  window.addEventListener('beforeprint',()=>{
    if(window.toolsOwnPrint) return;
    let box=document.getElementById('tdoc'); if(!box){box=document.createElement('div');box.id='tdoc';document.body.appendChild(box);}
    document.documentElement.classList.remove('tdoc-on');   // la page doit être visible pour lire les champs et les résultats
    box.innerHTML=build(); document.documentElement.classList.add('tdoc-on');
  });
  window.addEventListener('afterprint',()=>{document.documentElement.classList.remove('tdoc-on'); const b=document.getElementById('tdoc'); if(b) b.innerHTML='';});
})();
