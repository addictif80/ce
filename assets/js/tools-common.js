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
