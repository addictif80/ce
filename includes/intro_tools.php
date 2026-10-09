<?php
/**
 * Diaporama d'accueil de /tools : plein écran, défilement automatique, charte Caisse d'Épargne (rouge, blanc, gris).
 * Affiché une seule fois par session de navigation, après validation du code d'accès (la page /tools n'est servie qu'après).
 * Activable / désactivable par l'administrateur (réglage tools_intro_enabled, activé par défaut).
 */
function toolsIntroEnabled() {
    return getToolsSetting('tools_intro_enabled', '1') !== '0';
}

/**
 * @param array $tools  outils visibles : [['label' => ..., 'icon' => ...], ...]
 * @param bool  $withCta  proposer la demande d'accès au portail (si l'appel à action est actif)
 */
function renderToolsIntro(array $tools, $withCta = false) {
    if (!toolsIntroEnabled()) return;
    $logo = 'https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png';
    $grid = array_slice($tools, 0, 8);
    ?>
<div id="ciIntro" class="ci-intro" role="dialog" aria-modal="true" aria-label="Présentation des outils" hidden>
    <div class="ci-bg" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="ci-bar" aria-hidden="true"><i id="ciBar"></i></div>
    <button type="button" class="ci-skip" id="ciSkip"><span>Passer</span> <i class="fas fa-xmark"></i></button>

    <div class="ci-stage">
        <section class="ci-slide ci-hero">
            <div class="ci-logo r1"><img src="<?= e($logo) ?>" alt="Caisse d'Épargne Midi-Pyrénées" onerror="this.parentNode.style.display='none'"></div>
            <h2 class="r2">Bienvenue dans la boîte à outils</h2>
            <p class="r3">Des outils pratiques, accessibles à tout moment, pour préparer et mener vos échanges avec vos clients.</p>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-toolbox"></i> Un outil pour chaque projet</h2>
            <div class="ci-grid">
                <?php foreach ($grid as $i => $t): ?>
                <div class="ci-tool" style="--i:<?= (int)$i ?>"><i class="fas <?= e($t['icon']) ?>"></i><span><?= e($t['label']) ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php if (count($tools) > count($grid)): ?><p class="r4 ci-more">… et <?= count($tools) - count($grid) ?> autre<?= count($tools) - count($grid) > 1 ? 's' : '' ?> à découvrir.</p><?php endif; ?>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-user-shield"></i> Aucune donnée enregistrée</h2>
            <p class="r2 ci-big">Tout se calcule dans votre navigateur.</p>
            <p class="r3">Rien de ce que vous saisissez n'est stocké par ce portail : tout disparaît à la fermeture de la page.</p>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-bolt"></i> Gagnez du temps</h2>
            <ul class="ci-list">
                <li class="r2"><i class="fas fa-link"></i><div><b>Partagez par lien</b><span>Un lien reprend la simulation, sans rien enregistrer.</span></div></li>
                <li class="r3"><i class="fas fa-print"></i><div><b>Imprimez, copiez, envoyez</b><span>Un document propre à remettre à votre client.</span></div></li>
                <li class="r4"><i class="fas fa-star"></i><div><b>Retrouvez vos outils</b><span>Favoris et outils récemment utilisés, sur cet appareil.</span></div></li>
            </ul>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-circle-info"></i> Des aides, pas des substituts</h2>
            <p class="r2 ci-big">Ces outils complètent, sans les remplacer, les outils internes du groupe BPCE.</p>
            <p class="r3">Vérifiez toujours l'exactitude des données avant toute communication à un client.</p>
        </section>

        <section class="ci-slide ci-last">
            <?php if ($withCta): ?>
            <h2 class="r1"><i class="fas fa-key"></i> Besoin d'aller plus loin ?</h2>
            <p class="r2 ci-big">Demandez l'accès complet au portail d'activité.</p>
            <div class="r3 ci-actions">
                <button type="button" class="ci-btn ci-btn-light" id="ciAccess"><i class="fas fa-user-plus"></i> Demander l'accès</button>
                <button type="button" class="ci-btn ci-btn-line" id="ciGo">Découvrir les outils <i class="fas fa-arrow-right"></i></button>
            </div>
            <?php else: ?>
            <h2 class="r1"><i class="fas fa-rocket"></i> C'est parti !</h2>
            <p class="r2 ci-big">Choisissez un outil pour commencer.</p>
            <div class="r3 ci-actions"><button type="button" class="ci-btn ci-btn-light" id="ciGo">Découvrir les outils <i class="fas fa-arrow-right"></i></button></div>
            <?php endif; ?>
        </section>
    </div>

    <button type="button" class="ci-nav ci-prev" id="ciPrev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
    <button type="button" class="ci-nav ci-next" id="ciNext" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
    <div class="ci-dots" id="ciDots" role="tablist"></div>
</div>

<style>
.ci-intro{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;color:#fff;overflow:hidden;
    background:linear-gradient(135deg,#e4002b 0%,#b30022 55%,#7d0018 100%);font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    opacity:0;transition:opacity .5s ease}
.ci-intro[hidden]{display:none}
.ci-intro.on{opacity:1}
.ci-intro.out{opacity:0;transform:scale(1.03);transition:opacity .45s ease,transform .45s ease}
.ci-bg span{position:absolute;border-radius:50%;filter:blur(60px);opacity:.35;animation:ciFloat 18s ease-in-out infinite}
.ci-bg span:nth-child(1){width:46vmax;height:46vmax;background:#ff3b5c;left:-12vmax;top:-14vmax}
.ci-bg span:nth-child(2){width:38vmax;height:38vmax;background:#fff;opacity:.12;right:-10vmax;bottom:-12vmax;animation-delay:-6s}
.ci-bg span:nth-child(3){width:30vmax;height:30vmax;background:#ff6b83;left:40%;top:55%;animation-delay:-11s}
@keyframes ciFloat{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(4vmax,3vmax) scale(1.12)}}
.ci-bar{position:absolute;left:0;right:0;top:0;height:4px;background:rgba(255,255,255,.2)}
.ci-bar i{display:block;height:100%;width:0;background:#fff}
.ci-skip{position:absolute;top:18px;right:20px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.4);color:#fff;border-radius:999px;padding:7px 16px;font-size:.9rem;cursor:pointer;backdrop-filter:blur(6px);transition:background .2s}
.ci-skip:hover{background:rgba(255,255,255,.28)}
.ci-stage{position:relative;width:min(960px,92vw);min-height:min(520px,70vh);display:flex;align-items:center}
.ci-slide{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;opacity:0;visibility:hidden;transform:translateX(60px) scale(.98);filter:blur(8px);
    transition:opacity .7s cubic-bezier(.22,.8,.3,1),transform .7s cubic-bezier(.22,.8,.3,1),filter .7s,visibility 0s .7s;padding:0 14px}
.ci-slide.active{opacity:1;visibility:visible;transform:none;filter:none;transition-delay:0s}
.ci-slide.before{transform:translateX(-60px) scale(.98)}
.ci-slide h2{font-size:clamp(1.8rem,4.6vw,3.1rem);font-weight:800;letter-spacing:-.5px;margin:0 0 18px;line-height:1.1}
.ci-slide h2 i{margin-right:.45em;opacity:.9}
.ci-slide p{font-size:clamp(1rem,2vw,1.3rem);margin:0 0 12px;max-width:760px;opacity:.95}
.ci-slide p.ci-big{font-size:clamp(1.25rem,2.9vw,1.9rem);font-weight:600;opacity:1}
.ci-hero{align-items:center;text-align:center}
.ci-hero p{margin-left:auto;margin-right:auto}
.ci-logo{background:#fff;border-radius:14px;padding:12px 22px;margin-bottom:26px;box-shadow:0 12px 40px rgba(0,0,0,.25)}
.ci-logo img{height:52px;display:block}
.r1,.r2,.r3,.r4{opacity:0;transform:translateY(24px)}
.ci-slide.active .r1,.ci-slide.active .r2,.ci-slide.active .r3,.ci-slide.active .r4{animation:ciUp .7s cubic-bezier(.22,.8,.3,1) forwards}
.ci-slide.active .r1{animation-delay:.15s}.ci-slide.active .r2{animation-delay:.35s}.ci-slide.active .r3{animation-delay:.55s}.ci-slide.active .r4{animation-delay:.75s}
@keyframes ciUp{to{opacity:1;transform:none}}
.ci-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:6px}
.ci-tool{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);border-radius:14px;padding:18px 10px;text-align:center;backdrop-filter:blur(6px);opacity:0;transform:scale(.85) translateY(14px)}
.ci-tool i{display:block;font-size:1.9rem;margin-bottom:8px}
.ci-tool span{font-size:.95rem;font-weight:600;line-height:1.2;display:block}
.ci-slide.active .ci-tool{animation:ciPop .55s cubic-bezier(.3,1.4,.5,1) forwards;animation-delay:calc(.3s + var(--i) * .09s)}
@keyframes ciPop{to{opacity:1;transform:none}}
.ci-more{margin-top:22px!important}
.ci-list{list-style:none;margin:0;padding:0;display:grid;gap:14px;max-width:760px}
.ci-list li{display:flex;gap:16px;align-items:center;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.28);border-radius:14px;padding:14px 18px}
.ci-list li>i{font-size:1.6rem;width:46px;height:46px;border-radius:50%;background:#fff;color:#e4002b;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.ci-list b{display:block;font-size:1.15rem}
.ci-list span{opacity:.92}
.ci-actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:10px}
.ci-btn{border-radius:999px;padding:13px 28px;font-size:1.05rem;font-weight:700;cursor:pointer;border:2px solid #fff;transition:transform .15s,background .2s,color .2s}
.ci-btn:hover{transform:translateY(-2px)}
.ci-btn-light{background:#fff;color:#c40025}
.ci-btn-line{background:transparent;color:#fff}
.ci-btn-line:hover{background:rgba(255,255,255,.18)}
.ci-nav{position:absolute;top:50%;transform:translateY(-50%);width:46px;height:46px;border-radius:50%;border:1px solid rgba(255,255,255,.4);background:rgba(255,255,255,.12);color:#fff;cursor:pointer;transition:background .2s}
.ci-nav:hover{background:rgba(255,255,255,.3)}
.ci-prev{left:18px}.ci-next{right:18px}
.ci-dots{position:absolute;bottom:26px;left:0;right:0;display:flex;justify-content:center;gap:10px}
.ci-dots button{width:11px;height:11px;border-radius:999px;border:0;background:rgba(255,255,255,.4);padding:0;cursor:pointer;transition:width .35s,background .35s}
.ci-dots button.active{width:34px;background:#fff}
@media (max-width:700px){.ci-grid{grid-template-columns:repeat(2,1fr)}.ci-nav{display:none}.ci-stage{min-height:78vh}}
@media (prefers-reduced-motion:reduce){
  .ci-intro *,.ci-intro *::before{animation:none!important;transition:none!important}
  .r1,.r2,.r3,.r4,.ci-tool{opacity:1!important;transform:none!important}
  .ci-slide{filter:none}
}
@media print{.ci-intro{display:none!important}}
</style>
<script>
(function(){
  const root=document.getElementById('ciIntro'); if(!root) return;
  const slides=Array.from(root.querySelectorAll('.ci-slide')), dots=document.getElementById('ciDots'), bar=document.getElementById('ciBar');
  const KEY='toolsIntroSeen', DURATION=7000, reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let cur=-1, timer=null, started=0, remaining=DURATION, paused=false, raf=null;
  const seen=()=>{try{return sessionStorage.getItem(KEY)==='1';}catch(e){return false;}};
  const mark=()=>{try{sessionStorage.setItem(KEY,'1');}catch(e){}};
  slides.forEach((s,i)=>{const b=document.createElement('button');b.type='button';b.setAttribute('aria-label','Diapositive '+(i+1));b.onclick=()=>go(i,true);dots.appendChild(b);});
  function tick(){
    if(paused||reduce) return;
    const p=Math.min(1,(performance.now()-started)/remaining);
    bar.style.width=(p*100)+'%';
    if(p>=1){ if(cur<slides.length-1) go(cur+1); else {bar.style.width='100%';} return; }
    raf=requestAnimationFrame(tick);
  }
  function arm(ms){cancelAnimationFrame(raf);remaining=ms;started=performance.now();bar.style.transition='none';bar.style.width='0';
    if(!reduce&&cur<slides.length-1){raf=requestAnimationFrame(tick);}else{bar.style.width='0';}}
  function go(i,manual){
    if(i<0||i>=slides.length) return;
    slides.forEach((s,k)=>{s.classList.toggle('active',k===i);s.classList.toggle('before',k<i);});
    Array.from(dots.children).forEach((d,k)=>d.classList.toggle('active',k===i));
    cur=i; arm(DURATION);
  }
  function open(){
    root.hidden=false; document.body.style.overflow='hidden';
    requestAnimationFrame(()=>{root.classList.add('on'); go(0);});
    const f=root.querySelector('#ciSkip'); f&&f.focus({preventScroll:true});
  }
  function close(){
    mark(); cancelAnimationFrame(raf); root.classList.remove('on'); root.classList.add('out');
    setTimeout(()=>{root.hidden=true;root.classList.remove('out');document.body.style.overflow='';},450);
  }
  document.getElementById('ciSkip').onclick=close;
  document.getElementById('ciPrev').onclick=()=>go(cur-1,true);
  document.getElementById('ciNext').onclick=()=>{ if(cur<slides.length-1) go(cur+1,true); else close(); };
  const go1=document.getElementById('ciGo'); if(go1) go1.onclick=close;
  const acc=document.getElementById('ciAccess');
  if(acc) acc.onclick=()=>{close(); setTimeout(()=>{const m=document.getElementById('accessModal'); if(m&&window.bootstrap) bootstrap.Modal.getOrCreateInstance(m).show();},500);};
  const stage=root.querySelector('.ci-stage');
  stage.addEventListener('mouseenter',()=>{if(paused) return;paused=true;cancelAnimationFrame(raf);remaining=Math.max(300,remaining-(performance.now()-started));});
  stage.addEventListener('mouseleave',()=>{if(!paused) return;paused=false;started=performance.now();raf=requestAnimationFrame(tick);});
  document.addEventListener('keydown',e=>{
    if(root.hidden) return;
    if(e.key==='Escape') close();
    else if(e.key==='ArrowRight') go(Math.min(cur+1,slides.length-1),true);
    else if(e.key==='ArrowLeft') go(cur-1,true);
  });
  window.toolsIntroOpen=open;
  document.querySelectorAll('[data-intro-replay]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();open();}));
  if(!seen()) setTimeout(open,350);
})();
</script>
<?php
}
