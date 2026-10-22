<?php
// Test DISC interactif (24 choix forcés « le plus moi / le moins moi »), partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null).
$capLoad = $capLoad ?? null;
require __DIR__ . '/../_sim/style.php';
?>
<style>
.dk{--dk-d:#d62839;--dk-i:#e8a200;--dk-s:#2e9b57;--dk-c:#2b6cb0}
.dk-stage{position:relative;overflow:hidden;min-height:430px}
.dk-screen{animation:dkIn .45s cubic-bezier(.2,.8,.2,1) both}
.dk-screen.out{animation:dkOut .28s ease-in both}.dk-screen.back{animation-name:dkInBack}
@keyframes dkIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:none}}
@keyframes dkInBack{from{opacity:0;transform:translateX(-40px)}to{opacity:1;transform:none}}
@keyframes dkOut{to{opacity:0;transform:translateX(-40px)}}
@keyframes dkPulse{0%,100%{box-shadow:0 0 0 0 rgba(196,0,37,.45)}50%{box-shadow:0 0 0 14px rgba(196,0,37,0)}}
@keyframes dkPop{0%{transform:scale(1)}50%{transform:scale(1.18)}100%{transform:scale(1)}}
@keyframes dkFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.dk-hero{text-align:center;padding:18px 8px}.dk-hero .dk-logo{display:inline-flex;gap:8px;margin-bottom:14px}
.dk-hero .dk-logo span{width:54px;height:54px;border-radius:14px;color:#fff;font-weight:800;font-size:26px;display:flex;align-items:center;justify-content:center;animation:dkFloat 3s ease-in-out infinite}
.dk-hero .dk-logo span:nth-child(2){animation-delay:.25s}.dk-hero .dk-logo span:nth-child(3){animation-delay:.5s}.dk-hero .dk-logo span:nth-child(4){animation-delay:.75s}
.dk-start{animation:dkPulse 2s infinite}
.dk-prog{height:10px;background:#eceff3;border-radius:6px;overflow:hidden}.dk-prog>div{height:100%;width:0;background:linear-gradient(90deg,#c40025,#ff6b57);border-radius:6px;transition:width .5s cubic-bezier(.2,.8,.2,1)}
.dk-opt{display:flex;align-items:center;gap:10px;border:2px solid #e6e9ee;border-radius:12px;padding:10px 12px;margin-bottom:10px;background:#fff;transition:border-color .2s,background .2s,transform .2s}
.dk-opt.most{border-color:#2e9b57;background:#eefaf2;transform:scale(1.01)}.dk-opt.least{border-color:#d62839;background:#fdeff1;transform:scale(.99)}
.dk-opt .dk-txt{flex:1;font-weight:600}.dk-btns{display:flex;gap:6px}
.dk-b{border:2px solid #d5d9e0;background:#fff;border-radius:22px;padding:5px 12px;font-size:13px;font-weight:600;color:#555;cursor:pointer;transition:all .18s;white-space:nowrap}
.dk-b:hover{border-color:#999}.dk-b:focus-visible{outline:3px solid #8ab4f8;outline-offset:2px}
.dk-b.m.on{background:#2e9b57;border-color:#2e9b57;color:#fff;animation:dkPop .3s}.dk-b.l.on{background:#d62839;border-color:#d62839;color:#fff;animation:dkPop .3s}
.dk-res h3{font-size:1.05rem;margin:18px 0 6px}.dk-big{display:inline-flex;gap:6px;margin:6px 0}.dk-big span{width:46px;height:46px;border-radius:12px;color:#fff;font-weight:800;font-size:22px;display:flex;align-items:center;justify-content:center}
.dk-bar{display:flex;align-items:center;gap:10px;margin:8px 0}.dk-bar .n{width:130px;font-weight:600}.dk-bar .t{flex:1;height:22px;background:#eceff3;border-radius:11px;overflow:hidden}.dk-bar .t>div{height:100%;width:0;border-radius:11px}
.dk-bar .v{width:44px;text-align:right;font-weight:700}
.dk-list{padding-left:18px;margin:0}.dk-list li{margin-bottom:3px}
.dk-box{border-radius:10px;padding:10px 14px;margin-top:10px;font-size:14px}.dk-ok{background:#eefaf2;border-left:4px solid #2e9b57}.dk-mid{background:#fff8e1;border-left:4px solid #f0ad00}.dk-ko{background:#fdeff1;border-left:4px solid #d62839}
.dk-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
.dk-radar{max-width:280px;width:100%;display:block;margin:0 auto}
@media(max-width:576px){.dk-opt{flex-wrap:wrap}.dk-btns{width:100%}.dk-b{flex:1}.dk-bar .n{width:96px}}
@media(prefers-reduced-motion:reduce){.dk *{animation:none!important;transition:none!important}}
@media print{.dk-stage{min-height:0}.dk-bar .t>div{-webkit-print-color-adjust:exact;print-color-adjust:exact}.dk-big span{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<?php $actionsOpts = ['link' => true, 'copy' => false, 'print' => true, 'mail' => false]; ?>
<div class="dk">
 <div class="cap-card dk-stage" id="dk-stage" aria-live="polite"></div>
 <div id="dk-actions" style="display:none"><?php require __DIR__ . '/../_sim/actions.php'; ?></div>
 <?php if (!empty($capSave)): ?><div id="dk-save" style="display:none"><?php simSaveForm($capLoad, 'dkPrepare'); ?></div><?php endif; ?>
 <div class="small text-muted mt-3"><i class="fas fa-circle-info me-1"></i>Le modèle DISC est un outil de connaissance de soi et de communication, pas un diagnostic psychologique ni un test d'aptitude. Il décrit des préférences de comportement, qui varient selon le contexte : il ne doit pas servir à décider d'un recrutement, d'une évolution ou d'une affectation.</div>
</div>
<script>
(function () {
  'use strict';
  const LOAD = <?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  const $ = id => document.getElementById(id), esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const DIM = {
    D: { nom: 'Dominant', col: '#d62839', mot: 'Action et résultats' },
    I: { nom: 'Influent', col: '#e8a200', mot: 'Relations et enthousiasme' },
    S: { nom: 'Stable', col: '#2e9b57', mot: 'Constance et coopération' },
    C: { nom: 'Consciencieux', col: '#2b6cb0', mot: 'Précision et méthode' }
  };
  const KEYS = ['D', 'I', 'S', 'C'];
  const ITEMS = {
    D: ['décidé', 'direct', 'ambitieux', 'compétiteur', 'audacieux', 'autonome', 'exigeant', 'déterminé', 'tranchant', 'orienté résultats', 'prend les commandes', 'aime relever des défis', 'rapide à agir', 'franc', 'persévérant', 'volontaire', 'fonceur', 'énergique', 'entreprenant', 'impatient', 'aime décider seul', 'prêt à prendre des risques', 'sûr de soi', 'affirmé'],
    I: ['enthousiaste', 'sociable', 'persuasif', 'expressif', 'optimiste', 'chaleureux', 'communicatif', 'charismatique', 'spontané', 'convivial', 'inspirant', 'amical', 'démonstratif', 'plein d\'entrain', 'à l\'aise en public', 'créatif', 'enjoué', 'ouvert aux autres', 'aime convaincre', 'bavard', 'motive les autres', 'aime les nouvelles rencontres', 'plein d\'humour', 'fait facilement confiance'],
    S: ['patient', 'loyal', 'à l\'écoute', 'calme', 'conciliant', 'fiable', 'posé', 'coopératif', 'rassurant', 'constant', 'modeste', 'serviable', 'stable', 'attentionné', 'tolérant', 'discret', 'paisible', 'dévoué', 'régulier', 'apaisant', 'évite les conflits', 'aime la routine', 'préfère le travail d\'équipe', 'prend son temps'],
    C: ['précis', 'rigoureux', 'méthodique', 'organisé', 'analytique', 'prudent', 'perfectionniste', 'factuel', 'logique', 'soigneux', 'structuré', 'réfléchi', 'discipliné', 'vérifie les détails', 'respecte les règles', 'objectif', 'minutieux', 'cartésien', 'exact', 'documenté', 'cherche à comprendre avant d\'agir', 'aime les procédures', 'exigeant envers soi-même', 'réservé']
  };
  const N = 24;
  const BLOCKS = Array.from({ length: N }, (_, j) => ({ D: ITEMS.D[j], I: ITEMS.I[(j * 5 + 3) % N], S: ITEMS.S[(j * 7 + 1) % N], C: ITEMS.C[(j * 11 + 2) % N] }));
  const TXT = {
    D: { resume: 'Vous allez droit au but. Vous aimez décider, relever des défis et obtenir des résultats concrets, et vous supportez mal la lenteur ou l\'hésitation.',
      forces: ['Prise de décision rapide', 'Sens de l\'objectif et de l\'initiative', 'Courage face aux difficultés', 'Capacité à entraîner vers un résultat'],
      vigil: ['Peut paraître brusque ou trop pressé', 'Écoute parfois insuffisante des avis différents', 'Risque de négliger les détails ou les délais d\'adaptation des autres'],
      motiv: 'Autonomie, défis, résultats visibles, liberté d\'agir.', stress: 'Sous pression, devient autoritaire et impatient.',
      equipe: 'Pour travailler avec vous : être bref, factuel, orienté solution, laisser le choix des moyens.',
      client: 'Client de style D : allez à l\'essentiel, donnez les chiffres clés et deux options claires, respectez son temps, laissez-le décider.' },
    I: { resume: 'Vous êtes tourné vers les autres. Vous communiquez avec enthousiasme, créez facilement du lien et savez convaincre et motiver.',
      forces: ['Aisance relationnelle et communication', 'Optimisme et énergie communicative', 'Créativité et capacité à convaincre', 'Facilité à fédérer'],
      vigil: ['Peut manquer de rigueur dans le suivi et les détails', 'Tendance à trop parler ou à s\'éparpiller', 'Sensible au manque de reconnaissance'],
      motiv: 'Reconnaissance, contacts, ambiance positive, liberté d\'expression.', stress: 'Sous pression, devient dispersé, parle trop ou s\'emporte.',
      equipe: 'Pour travailler avec vous : prévoir du temps d\'échange, valoriser vos idées, cadrer les échéances par écrit.',
      client: 'Client de style I : soignez la relation et l\'accueil, laissez-le s\'exprimer, restez positif, puis fixez par écrit les points concrets.' },
    S: { resume: 'Vous êtes calme, loyal et fiable. Vous privilégiez l\'harmonie, la constance et l\'entraide, et vous écoutez vraiment.',
      forces: ['Écoute et patience', 'Loyauté et régularité', 'Esprit d\'équipe et médiation', 'Capacité à rassurer'],
      vigil: ['Peut éviter le conflit et hésiter à dire non', 'Résiste aux changements brusques', 'Prend parfois du temps pour décider'],
      motiv: 'Sécurité, stabilité, relations de confiance, reconnaissance de la fiabilité.', stress: 'Sous pression, se replie, cède ou garde ses réserves pour lui.',
      equipe: 'Pour travailler avec vous : annoncer les changements à l\'avance, laisser le temps de réfléchir, montrer de la considération.',
      client: 'Client de style S : rassurez, prenez le temps, expliquez les étapes, évitez la pression commerciale, bâtissez la confiance dans la durée.' },
    C: { resume: 'Vous êtes précis et réfléchi. Vous aimez comprendre, vérifier et bien faire, et vous vous appuyez sur des faits et des méthodes.',
      forces: ['Rigueur et qualité du travail', 'Analyse et esprit critique', 'Organisation et fiabilité des informations', 'Prudence dans les risques'],
      vigil: ['Peut tomber dans le perfectionnisme ou l\'excès d\'analyse', 'Décision lente faute de certitude', 'Peut paraître distant ou critique'],
      motiv: 'Exactitude, expertise, règles claires, temps pour bien faire.', stress: 'Sous pression, se focalise sur les détails, critique ou se bloque.',
      equipe: 'Pour travailler avec vous : fournir des données exactes, laisser le temps d\'analyser, expliquer les raisons des décisions.',
      client: 'Client de style C : préparez des chiffres exacts et des documents, répondez précisément, laissez-lui le temps de comparer, ne forcez pas la décision.' }
  };
  const NUANCE = { D: 'apporte de l\'affirmation et le goût de l\'action', I: 'apporte de la sociabilité et de l\'enthousiasme', S: 'apporte de la constance et de l\'écoute', C: 'apporte de la rigueur et de l\'esprit d\'analyse' };

  // ----- état : pour chaque bloc, la lettre « le plus moi », la lettre « le moins moi » et l'ordre d'affichage -----
  const S = { i: -1, most: Array(N).fill(null), least: Array(N).fill(null), order: [], pos: Array(N).fill(-1), t0: 0, secs: 0, pb: 0, done: false };
  const shuffle = a => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  const stage = $('dk-stage');
  let busy = false;
  const mount = (html, cls) => { stage.innerHTML = '<div class="dk-screen ' + (cls || '') + '">' + html + '</div>'; stage.scrollIntoView && stage.getBoundingClientRect().top < 0 && stage.scrollIntoView({ block: 'start', behavior: 'smooth' }); };
  const swap = (html, back) => { if (busy) return; busy = true; const cur = stage.firstElementChild; if (cur) { cur.classList.add('out'); setTimeout(() => { mount(html, back ? 'back' : ''); busy = false; bind(); }, 260); } else { mount(html); busy = false; bind(); } };

  function intro() {
    S.i = -1; S.most.fill(null); S.least.fill(null); S.done = false; S.order = BLOCKS.map(() => shuffle(KEYS));
    $('dk-actions').style.display = 'none'; const sv = $('dk-save'); if (sv) sv.style.display = 'none';
    swap('<div class="dk-hero"><div class="dk-logo">' + KEYS.map(k => '<span style="background:' + DIM[k].col + '">' + k + '</span>').join('') + '</div>'
      + '<h2 class="mb-2">Quel est votre style de comportement ?</h2>'
      + '<p class="text-muted mb-3" style="max-width:640px;margin:0 auto">Pour chacune des ' + N + ' séries, quatre qualificatifs vous sont proposés. Choisissez celui qui vous <b class="text-success">ressemble le plus</b> et celui qui vous <b class="text-danger">ressemble le moins</b>, en pensant à votre façon d\'être <b>au travail</b>. Il n\'y a pas de bonne ou de mauvaise réponse : répondez spontanément.</p>'
      + '<p class="small text-muted">Environ 6 à 8 minutes · résultat immédiat · rien n\'est envoyé hors de votre navigateur</p>'
      + '<button type="button" class="btn btn-danger btn-lg dk-start" data-go="start"><i class="fas fa-play me-2"></i>Commencer le test</button></div>');
  }
  function question(i, back) {
    S.i = i; if (i === 0 && !S.t0) S.t0 = Date.now();
    const pct = Math.round(i / N * 100), ord = S.order[i], m = S.most[i], l = S.least[i];
    swap('<div class="d-flex justify-content-between small text-muted mb-1"><span>Question ' + (i + 1) + ' sur ' + N + '</span><span>' + pct + ' %</span></div><div class="dk-prog mb-3"><div id="dk-bar" style="width:' + pct + '%"></div></div>'
      + '<h3 class="h5 mb-1">Parmi ces quatre qualificatifs…</h3><p class="small text-muted mb-3">Choisissez celui qui vous <b class="text-success">ressemble le plus</b> et celui qui vous <b class="text-danger">ressemble le moins</b>.</p>'
      + ord.map(k => '<div class="dk-opt ' + (m === k ? 'most' : l === k ? 'least' : '') + '" data-k="' + k + '"><span class="dk-txt">' + esc(BLOCKS[i][k]) + '</span><span class="dk-btns">'
        + '<button type="button" class="dk-b m' + (m === k ? ' on' : '') + '" data-pick="m" data-k="' + k + '" aria-pressed="' + (m === k) + '"><i class="fas fa-thumbs-up me-1"></i>Plus moi</button>'
        + '<button type="button" class="dk-b l' + (l === k ? ' on' : '') + '" data-pick="l" data-k="' + k + '" aria-pressed="' + (l === k) + '"><i class="fas fa-thumbs-down me-1"></i>Moins moi</button></span></div>').join('')
      + '<div class="d-flex justify-content-between mt-3"><button type="button" class="btn btn-outline-secondary btn-sm" data-go="prev"' + (i === 0 ? ' disabled' : '') + '><i class="fas fa-arrow-left me-1"></i>Précédent</button>'
      + '<button type="button" class="btn btn-sm btn-outline-secondary" data-go="reset"><i class="fas fa-rotate-left me-1"></i>Recommencer</button></div>', back);
    requestAnimationFrame(() => { const b = $('dk-bar'); if (b) b.style.width = Math.round((i + (m && l ? 1 : 0)) / N * 100) + '%'; });
  }
  function pick(kind, k) {
    const i = S.i, o = kind === 'm' ? S.most : S.least, other = kind === 'm' ? S.least : S.most;
    if (o[i] === k) o[i] = null; else { o[i] = k; if (other[i] === k) other[i] = null; }
    if (kind === 'm' && S.most[i]) S.pos[i] = S.order[i].indexOf(S.most[i]);
    stage.querySelectorAll('.dk-opt').forEach(r => { const kk = r.dataset.k; r.classList.toggle('most', S.most[i] === kk); r.classList.toggle('least', S.least[i] === kk);
      r.querySelector('.m').classList.toggle('on', S.most[i] === kk); r.querySelector('.l').classList.toggle('on', S.least[i] === kk);
      r.querySelector('.m').setAttribute('aria-pressed', S.most[i] === kk); r.querySelector('.l').setAttribute('aria-pressed', S.least[i] === kk); });
    const b = $('dk-bar'); if (b) b.style.width = Math.round((i + (S.most[i] && S.least[i] ? 1 : 0)) / N * 100) + '%';
    if (S.most[i] && S.least[i]) setTimeout(() => { if (S.i === i && S.most[i] && S.least[i]) next(); }, 650);
  }
  const next = () => { if (S.i + 1 >= N) finish(); else question(S.i + 1); };

  // ----- calcul -----
  function scores(from, to) { const r = { D: 0, I: 0, S: 0, C: 0 }; for (let i = from; i < to; i++) { if (S.most[i]) r[S.most[i]]++; if (S.least[i]) r[S.least[i]]--; } return r; }
  function pearson(a, b) { const n = a.length, ma = a.reduce((x, y) => x + y, 0) / n, mb = b.reduce((x, y) => x + y, 0) / n; let sa = 0, sb = 0, sab = 0; for (let i = 0; i < n; i++) { sa += (a[i] - ma) ** 2; sb += (b[i] - mb) ** 2; sab += (a[i] - ma) * (b[i] - mb); } return sa === 0 || sb === 0 ? null : sab / Math.sqrt(sa * sb); }
  function analyse() {
    const raw = scores(0, N), pct = {}; KEYS.forEach(k => pct[k] = Math.round((raw[k] + N) / (2 * N) * 100));
    const rank = KEYS.slice().sort((a, b) => raw[b] - raw[a] || KEYS.indexOf(a) - KEYS.indexOf(b));
    const prim = rank[0], sec = raw[prim] - raw[rank[1]] <= 6 ? rank[1] : null;
    const h1 = scores(0, N / 2), h2 = scores(N / 2, N), r = pearson(KEYS.map(k => h1[k]), KEYS.map(k => h2[k]));
    const spread = raw[rank[0]] - raw[rank[3]], mx = Math.max(...Object.values(raw));
    const warns = []; let level = 0;
    if (r === null) { warns.push('La cohérence entre la première et la seconde moitié du test ne peut pas être mesurée (réponses très uniformes).'); level++; }
    else if (r < 0.2) { warns.push('Vos réponses de la première et de la seconde moitié du test se ressemblent peu : votre profil peut varier selon les situations auxquelles vous pensiez.'); level++; }
    if (S.secs && S.secs / N < 3) { warns.push('Vous avez répondu très vite (moins de 3 secondes par question en moyenne) : les réponses spontanées sont utiles, mais le résultat est moins sûr.'); level++; }
    if (S.pb > 0.6) { warns.push('Vous avez souvent choisi le qualificatif situé à la même place à l\'écran : cela peut indiquer une réponse automatique.'); level++; }
    if (spread <= 8) warns.push('Votre profil est peu contrasté : aucun style ne se détache nettement, ce qui est fréquent chez les personnes adaptables.');
    const fiab = level === 0 ? ['ok', 'Bonne', r === null ? '' : 'cohérence ' + (r >= 0.6 ? 'élevée' : 'moyenne')] : level === 1 ? ['mid', 'Moyenne', ''] : ['ko', 'À prendre avec prudence', ''];
    return { raw, pct, rank, prim, sec, r, warns, fiab, spread, mx };
  }
  // ----- résultat -----
  function resultHtml(a) {
    const T = TXT[a.prim], prof = a.prim + (a.sec || '');
    const li = arr => '<ul class="dk-list">' + arr.map(x => '<li>' + esc(x) + '</li>').join('') + '</ul>';
    return '<div class="dk-res js-result"><h2 class="mb-1"><i class="fas fa-chart-pie me-2 text-danger"></i>Votre profil DISC</h2>'
      + '<div class="dk-big">' + [a.prim, a.sec].filter(Boolean).map(k => '<span style="background:' + DIM[k].col + '">' + k + '</span>').join('') + '</div>'
      + '<div class="h5 mb-1">' + (a.sec ? 'Profil mixte : ' + DIM[a.prim].nom + ' et ' + DIM[a.sec].nom.toLowerCase() : 'Profil ' + DIM[a.prim].nom.toLowerCase()) + '</div>'
      + '<p>' + esc(T.resume) + (a.sec ? ' Votre second style, ' + DIM[a.sec].nom.toLowerCase() + ', ' + NUANCE[a.sec] + '.' : '') + '</p>'
      + '<div class="dk-cols"><div><svg class="dk-radar" viewBox="0 0 240 240" role="img" aria-label="Radar des quatre dimensions">'
      + [25, 50, 75, 100].map(p => '<polygon points="' + pts(KEYS.map(() => p)) + '" fill="none" stroke="#dfe3e8" stroke-width="1"/>').join('')
      + '<line x1="120" y1="30" x2="120" y2="210" stroke="#dfe3e8"/><line x1="30" y1="120" x2="210" y2="120" stroke="#dfe3e8"/>'
      + '<polygon id="dk-poly" points="' + pts(KEYS.map(() => 0)) + '" fill="rgba(196,0,37,.18)" stroke="#c40025" stroke-width="2.5" stroke-linejoin="round"/>'
      + KEYS.map((k, n) => { const p = axis(n, 108); return '<text x="' + p[0] + '" y="' + (p[1] + 5) + '" text-anchor="middle" font-size="16" font-weight="800" fill="' + DIM[k].col + '">' + k + '</text>'; }).join('') + '</svg></div>'
      + '<div>' + KEYS.map(k => '<div class="dk-bar"><span class="n" style="color:' + DIM[k].col + '">' + k + ' · ' + DIM[k].nom + '</span><span class="t"><div data-bar="' + k + '" style="background:' + DIM[k].col + '"></div></span><span class="v" data-val="' + k + '">0</span></div>').join('')
      + '<div class="small text-muted">Score de 0 à 100 : 50 = niveau moyen ; au-dessus de 60, le style est marqué.</div></div></div>'
      + '<div class="dk-cols"><div><h3 class="text-success"><i class="fas fa-circle-check me-1"></i>Vos points forts</h3>' + li(T.forces) + '</div><div><h3 class="text-warning"><i class="fas fa-triangle-exclamation me-1"></i>Points de vigilance</h3>' + li(T.vigil) + '</div></div>'
      + '<h3><i class="fas fa-bolt me-1 text-danger"></i>Ce qui vous motive et ce qui vous tend</h3><p class="mb-1"><b>Motivations :</b> ' + esc(T.motiv) + '</p><p><b>Sous pression :</b> ' + esc(T.stress) + '</p>'
      + '<h3><i class="fas fa-comments me-1 text-danger"></i>Communiquer avec vous et avec vos clients</h3><p class="mb-1">' + esc(T.equipe) + '</p><p><b>En conseil :</b> adaptez-vous au style de vos interlocuteurs. ' + esc(T.client) + '</p>'
      + '<details class="mt-2"><summary class="small text-primary" style="cursor:pointer">Adapter son discours aux trois autres styles</summary>' + KEYS.filter(k => k !== a.prim).map(k => '<p class="small mb-1 mt-2"><b style="color:' + DIM[k].col + '">' + k + ' · ' + DIM[k].nom + '</b> — ' + esc(TXT[k].client) + '</p>').join('') + '</details>'
      + '<div class="dk-box dk-' + a.fiab[0] + '"><b>Fiabilité des réponses : ' + esc(a.fiab[1]) + '</b>' + (a.fiab[2] ? ' (' + esc(a.fiab[2]) + ')' : '') + (a.warns.length ? '<ul class="mb-0 mt-1">' + a.warns.map(w => '<li>' + esc(w) + '</li>').join('') + '</ul>' : '<div class="small">Vos réponses sont cohérentes entre le début et la fin du test.</div>') + '</div>'
      + '<div class="no-print mt-3 d-flex gap-2 flex-wrap"><button type="button" class="btn btn-outline-secondary btn-sm" data-go="reset"><i class="fas fa-rotate-left me-1"></i>Refaire le test</button></div></div>';
  }
  const axis = (n, r) => { const ang = -Math.PI / 2 + n * Math.PI / 2; return [(120 + r * Math.cos(ang)).toFixed(1), (120 + r * Math.sin(ang)).toFixed(1)]; };
  const pts = arr => arr.map((v, n) => axis(n, 90 * v / 100).join(',')).join(' ');
  function animate(a) {
    const reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches, dur = reduce ? 0 : 1100, t0 = performance.now();
    const poly = $('dk-poly'), ease = t => 1 - Math.pow(1 - t, 3);
    const draw = t => { const e = ease(t); poly && poly.setAttribute('points', pts(KEYS.map(k => a.pct[k] * e)));
      KEYS.forEach(k => { const b = stage.querySelector('[data-bar="' + k + '"]'), v = stage.querySelector('[data-val="' + k + '"]'); if (b) b.style.width = (a.pct[k] * e) + '%'; if (v) v.textContent = Math.round(a.pct[k] * e); }); };
    const step = now => { const t = dur ? Math.min(1, (now - t0) / dur) : 1; draw(t); if (t < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  }
  let LAST = null;
  function show(a, animated) {
    LAST = a; S.done = true; swap(resultHtml(a)); setTimeout(() => animate(a), 450);
    $('dk-actions').style.display = ''; const sv = $('dk-save'); if (sv) sv.style.display = '';
  }
  function finish() {
    S.secs = Math.round((Date.now() - S.t0) / 1000);
    const c = [0, 0, 0, 0]; S.pos.forEach(p => { if (p >= 0) c[p]++; }); S.pb = Math.max(...c) / N;
    show(analyse());
  }
  function bind() {}
  stage.addEventListener('click', e => {
    const g = e.target.closest('[data-go]'), p = e.target.closest('[data-pick]');
    if (p) { pick(p.dataset.pick, p.dataset.k); return; }
    if (!g) return;
    if (g.dataset.go === 'start') { S.t0 = Date.now(); question(0); }
    else if (g.dataset.go === 'prev' && S.i > 0) question(S.i - 1, true);
    else if (g.dataset.go === 'reset') { if (!S.done && S.most.filter(Boolean).length && !confirm('Recommencer le test depuis le début ?')) return; S.t0 = 0; intro(); }
  });

  // ----- partage, enregistrement, impression -----
  const snapshot = () => S.done ? { a: Array.from({ length: N }, (_, i) => S.most[i] + S.least[i]).join(''), t: S.secs, pb: Math.round(S.pb * 100) } : null;
  const restore = o => { if (!o || typeof o.a !== 'string' || o.a.length !== 2 * N || !/^[DISC]+$/.test(o.a)) return false;
    for (let i = 0; i < N; i++) { S.most[i] = o.a[2 * i]; S.least[i] = o.a[2 * i + 1]; if (S.most[i] === S.least[i]) return false; }
    S.secs = +o.t || 0; S.pb = (+o.pb || 0) / 100; show(analyse()); return true; };
  window.toolsShare = { get: snapshot, set: restore };
  window.dkPrepare = f => { if (!S.done || !LAST) { alert('Terminez le test avant d\'enregistrer.'); return false; } f.params.value = JSON.stringify(snapshot());
    f.resultat.value = JSON.stringify({ profil: LAST.prim + (LAST.sec || ''), D: LAST.pct.D, I: LAST.pct.I, S: LAST.pct.S, C: LAST.pct.C }); return true; };
  window.toolsDoc = () => ({ title: 'Test DISC : profil de comportement', sub: 'Outil de connaissance de soi, sans valeur de diagnostic', inputs: LAST ? [['Profil', LAST.prim + (LAST.sec || '') + ' (' + DIM[LAST.prim].nom + (LAST.sec ? ' / ' + DIM[LAST.sec].nom : '') + ')'], ['Scores (0 à 100)', KEYS.map(k => k + ' ' + LAST.pct[k]).join(' · ')], ['Fiabilité des réponses', LAST.fiab[1]]] : [],
    foot: 'Le modèle DISC décrit des préférences de comportement ; ce n\'est ni un diagnostic psychologique ni un test d\'aptitude. Il ne doit pas servir à décider d\'un recrutement ou d\'une évolution professionnelle.' });
  if (!(LOAD && restore(LOAD))) intro();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
