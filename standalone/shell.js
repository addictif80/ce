(function () {
  'use strict';
  var FEEDBACK_TO = '';

  var $ = function (id) { return document.getElementById(id); };
  var D = JSON.parse($('sa-data').textContent), TOOLS = {}, ORDER = [];

  /* ----- configuration : zone de texte au début du fichier (listes, réglages, outils affichés) ----- */
  function cfgLines(id) { var e = $(id); return e ? e.textContent.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(function (l) { return l && l.charAt(0) !== '#'; }) : []; }
  function cfgFields(l) { return l.split(';').map(function (x) { return x.trim(); }); }
  function cfgKV(id) { var o = {}; cfgLines(id).forEach(function (l) { var m = /^(\w+)\s*:\s*(.*)$/.exec(l); if (m) o[m[1]] = m[2].replace(/\s+#.*$/, '').trim(); }); return o; }
  var GEN = cfgKV('cfg-general'), OFF = {};
  FEEDBACK_TO = 'adresse_retours' in GEN ? GEN.adresse_retours : (D.feedbackTo || '');
  cfgLines('cfg-outils').forEach(function (l) { var m = /^(\w+)\s*:\s*(\S+)/.exec(l); if (m && /^(false|faux|non|0|off)$/i.test(m[2])) OFF[m[1]] = true; });
  var GROUPES = { 'revenus': 'Revenus', 'charges fixes': 'Charges fixes' };
  window.CFG = {
    agences: cfgLines('cfg-agences').map(cfgFields).filter(function (f) { return f[0]; }).map(function (f) { return { nom: f[0], num: f[1] || '', a1: f[2] || '', a2: f[3] || '', tel: f[4] || '' }; }),
    conseillers: cfgLines('cfg-conseillers').map(cfgFields).filter(function (f) { return f[0] || f[1]; }).map(function (f) { return { prenom: f[0] || '', nom: f[1] || '', poste: f[2] || '', tel: f[3] || '', mail: f[4] || '' }; }),
    politesses: cfgLines('cfg-politesses').map(cfgFields).filter(function (f) { return f[0] && f[1]; }).map(function (f) { return [f[0], f.slice(1).join(' ; ')]; }),
    ponderation: cfgLines('cfg-ponderation').map(cfgFields).filter(function (f) { return GROUPES[(f[0] || '').toLowerCase()] && f[1]; }).map(function (f) {
      var n = parseFloat((f[2] || '').replace(',', '.')), r = [GROUPES[f[0].toLowerCase()], f[1], isNaN(n) ? 100 : Math.max(0, Math.min(200, n)), f[3] || ''];
      if (/^cr[ée]dit$/i.test(f[4] || '')) r.push(true); return r; }),
    lignes: { rev: [], cf: [] },
    seuil: parseFloat(String(GEN.seuil_endettement || '').replace(',', '.')) || 0
  };
  cfgLines('cfg-lignes').map(cfgFields).forEach(function (f) { var g = GROUPES[(f[0] || '').toLowerCase()]; if (g && f[1]) window.CFG.lignes[g === 'Revenus' ? 'rev' : 'cf'].push(f[1]); });
  // listes déroulantes du générateur de courrier, construites à partir de la configuration
  (function () {
    var sa = $('f-agence'), sc = $('f-conseiller');
    function opt(sel, value, label, data) { var o = document.createElement('option'); o.value = value; o.textContent = label; Object.keys(data).forEach(function (k) { o.setAttribute('data-' + k, data[k]); }); sel.appendChild(o); }
    if (sa) window.CFG.agences.forEach(function (a) { opt(sa, a.nom, a.nom, { a1: a.a1, a2: a.a2, tel: a.tel }); });
    if (sc) window.CFG.conseillers.forEach(function (c) { var n = (c.prenom + ' ' + c.nom).trim(); opt(sc, n, n, { prenom: c.prenom, nom: c.nom, poste: c.poste, tel: c.tel, mail: c.mail }); });
  })();
  // outils masqués : retirés de l'accueil, des listes et de la navigation
  D.tools = D.tools.filter(function (t) { return !OFF[t.key]; });
  Object.keys(OFF).forEach(function (k) {
    document.querySelectorAll('.carte-outil[data-open="' + k + '"], #top-select option[value="' + k + '"], #fb-outil option[value="' + k + '"]').forEach(function (n) { n.remove(); });
  });
  document.querySelectorAll('.groupe-accueil').forEach(function (g) { if (!g.querySelector('.carte-outil')) g.remove(); });
  document.querySelectorAll('#top-select optgroup, #fb-outil optgroup').forEach(function (g) { if (!g.querySelector('option')) g.remove(); });

  if (!FEEDBACK_TO && D.feedbackTo && !('adresse_retours' in GEN)) FEEDBACK_TO = D.feedbackTo;
  D.tools.forEach(function (t) { TOOLS[t.key] = t; ORDER.push(t.key); });
  var zonageObj = null;
  var accueil = $('accueil'), topbar = $('topbar'), frameMod = $('mod-frame'), natives = { budget: $('mod-budget'), courrier: $('mod-courrier') }, frames = {}, current = null;

  function parseHash() {
    var o = {}, h = location.hash.replace(/^#/, '');
    h.split('&').forEach(function (p) { var i = p.indexOf('='); if (i > 0) o[p.slice(0, i)] = p.slice(i + 1); });
    return o;
  }
  function buildDoc(key, hash) {
    var t = TOOLS[key], S = D.shared; S.fa = $('sa-fa').textContent; if (!S.themeFull) S.themeFull = $('sa-font').textContent + S.theme;
    var agc = key === 'bureau_dom' ? 'window.TOOLS_SA.agencies=parent.CE.agencyLabels();' : '';
    var zon = (S.zonage && /^(ptz|plan|scenarios|creditsspeciaux)$/.test(key)) ? 'window.TOOLS_SA.zonage=parent.CE.zonage();' : '';
    var boot = '<script>window.TOOLS_SA={tool:' + JSON.stringify(key) + ',hash:' + JSON.stringify(hash || '') + ',routes:{},share:function(p){return parent.CE.share(' + JSON.stringify(key) + ',p);}};' + zon + agc + S.shim.replace(/<\//g, '<\\/') + '<\/script>';
    return t.html.replace('<!--SA-BOOT-->', function () { return boot; })
      .replace(/<(style|script) data-shared="([\w-]+)"><\/\1>/g, function (m, tag, name) { var c = name === 'theme' ? S.themeFull : S[name]; return c === undefined ? m : '<' + tag + '>' + c + '</' + tag + '>'; });
  }
  function ensureFrame(key, hash) {
    if (frames[key]) return frames[key];
    var f = document.createElement('iframe');
    f.title = TOOLS[key].title; f.setAttribute('loading', 'eager'); f.style.display = 'none';
    f.srcdoc = buildDoc(key, hash);
    frameMod.appendChild(f); frames[key] = f; return f;
  }
  function hideAll() {
    Object.keys(natives).forEach(function (k) { natives[k].hidden = true; });
    Object.keys(frames).forEach(function (k) { frames[k].style.display = 'none'; });
    frameMod.hidden = true;
  }
  function home(push) {
    hideAll(); topbar.hidden = true; accueil.hidden = false; current = null;
    if (push !== false) history.pushState(null, '', location.pathname + location.search);
    document.title = 'Outils conseiller';
    window.scrollTo(0, 0);
  }
  function open(key, hash, push) {
    var t = TOOLS[key]; if (!t) return home(false);
    hideAll(); accueil.hidden = true; topbar.hidden = false; current = key;
    if (t.native) natives[t.native].hidden = false;
    else { var f = ensureFrame(key, hash); frameMod.hidden = false; f.style.display = 'block'; }
    $('top-titre').textContent = t.title; $('top-select').value = key;
    document.title = t.title + ' - Outils conseiller';
    if (push !== false) history.pushState(null, '', '#t=' + key);
    window.scrollTo(0, 0);
  }
  function route() { var h = parseHash(); if (h.t && TOOLS[h.t]) open(h.t, h.s ? '#s=' + h.s : '', false); else home(false); }

  // ----- retour par e-mail : génération d'un fichier .eml -----
  function b64utf8(s) { return btoa(unescape(encodeURIComponent(s))); }
  function wrap(s, n) { var o = []; for (var i = 0; i < s.length; i += n) o.push(s.slice(i, i + n)); return o.join('\r\n'); }
  function encodedWords(s) { // objet : mots encodés (RFC 2047), par morceaux de 36 caractères pour rester sous 75 caractères par ligne
    var parts = [], cur = '';
    Array.from(s).forEach(function (c) { if (unescape(encodeURIComponent(cur + c)).length > 36) { parts.push(cur); cur = c; } else cur += c; });
    if (cur) parts.push(cur);
    return parts.map(function (p) { return '=?UTF-8?B?' + b64utf8(p) + '?='; }).join('\r\n ');
  }
  function buildEml(type, outil, message, contact) {
    var titre = outil && TOOLS[outil] ? TOOLS[outil].title : '';
    var objet = '[Outils conseiller] ' + type + (titre ? ' - ' + titre : '');
    var ligne = '\r\n';
    var corps = ['Nature du retour : ' + type, 'Outil concerné : ' + (titre || 'Général'), 'Contact : ' + (contact || 'non renseigné'), '', 'Message :', message, '', '--', 'Retour écrit depuis le fichier « Outils conseiller » (version du ' + D.date + ').'].join(ligne);
    var h = ['X-Unsent: 1'];
    if (FEEDBACK_TO) h.push('To: ' + FEEDBACK_TO);
    if (/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(contact)) h.push('Reply-To: ' + contact);
    h.push('Subject: ' + encodedWords(objet), 'Date: ' + new Date().toUTCString().replace('GMT', '+0000'), 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64');
    return h.join(ligne) + ligne + ligne + wrap(b64utf8(corps), 76) + ligne;
  }
  function openFeedback(key) {
    $('fb-outil').value = key && TOOLS[key] ? key : (current && TOOLS[current] ? current : '');
    $('fb-erreur').hidden = true; $('fb-apres').hidden = true; $('fb-fond').hidden = false; $('fb-msg').focus(); count();
  }
  function closeFeedback() { $('fb-fond').hidden = true; }
  function count() { $('fb-compteur').textContent = $('fb-msg').value.length + ' / 2000'; }
  function generate() {
    var msg = $('fb-msg').value.trim(), contact = $('fb-contact').value.trim(), err = $('fb-erreur');
    if (msg.length < 5 || msg.length > 2000) { err.textContent = 'Votre message doit contenir entre 5 et 2000 caractères.'; err.hidden = false; return; }
    if (contact.length > 255) { err.textContent = 'Le contact est trop long (255 caractères maximum).'; err.hidden = false; return; }
    err.hidden = true;
    var eml = buildEml($('fb-type').value, $('fb-outil').value, msg, contact), d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
    var name = 'retour-outils-' + d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + '-' + p(d.getHours()) + p(d.getMinutes()) + '.eml';
    var a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([eml], { type: 'message/rfc822' })); a.download = name; document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 800);
    var ap = $('fb-apres'); ap.textContent = 'Fichier « ' + name + ' » téléchargé. Ouvrez-le (double-clic) : votre messagerie affiche le message prêt à envoyer' + (FEEDBACK_TO ? ' à ' + FEEDBACK_TO : ' ; indiquez le destinataire avant de l\'envoyer') + '.'; ap.hidden = false;
  }

  // ----- API appelée par les outils intégrés -----
  window.CE = {
    home: function () { home(); },
    open: function (k) { open(k); },
    feedback: function (k) { openFeedback(k); },
    agencyLabels: function () { return window.CFG.agences.map(function (a) { return a.nom + (a.num ? ' (' + a.num + ')' : ''); }); },
    zonage: function () { if (!zonageObj && D.shared.zonage) zonageObj = JSON.parse(D.shared.zonage); return zonageObj; },
    share: function (key, p) { return location.href.split('#')[0] + '#t=' + key + '&' + String(p).replace(/^#/, ''); }
  };

  // ----- liaison de l'interface -----
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-open]'); if (c) { open(c.getAttribute('data-open')); return; }
    var f = e.target.closest('[data-sa-act="feedback"]'); if (f) openFeedback();
  });
  $('top-home').addEventListener('click', function () { home(); });
  $('top-feedback').addEventListener('click', function () { openFeedback(current); });
  // ----- menu « Accès rapide » (barre du haut), piloté par le bloc de configuration cfg-rapide -----
  (function () {
    var wrap = $('top-rapide'), btn = $('top-rapide-btn'), liste = $('top-rapide-liste'); if (!wrap) return;
    var keys = [];
    cfgLines('cfg-rapide').forEach(function (l) { var m = /^(\w+)\s*:\s*(\S+)/.exec(l); if (m && /^(true|vrai|oui|1|on)$/i.test(m[2]) && TOOLS[m[1]] && keys.indexOf(m[1]) < 0) keys.push(m[1]); });
    if (!keys.length) return;
    keys.forEach(function (k) {
      var b = document.createElement('button'); b.type = 'button'; b.setAttribute('role', 'menuitem'); b.setAttribute('data-rapide', k);
      var i = document.createElement('i'); i.className = 'fas ' + (TOOLS[k].icon || 'fa-wrench'); b.appendChild(i);
      b.appendChild(document.createTextNode(' ' + TOOLS[k].title)); liste.appendChild(b);
    });
    wrap.hidden = false;
    function fermer() { liste.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    btn.addEventListener('click', function (e) { e.stopPropagation(); var ouvert = liste.hidden; liste.hidden = !ouvert; btn.setAttribute('aria-expanded', ouvert ? 'true' : 'false'); });
    liste.addEventListener('click', function (e) { var b = e.target.closest('[data-rapide]'); if (b) { fermer(); open(b.getAttribute('data-rapide')); } });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) fermer(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fermer(); });
  })();
  $('top-select').addEventListener('change', function () { if (this.value) open(this.value); });
  $('fb-annuler').addEventListener('click', closeFeedback);
  $('fb-generer').addEventListener('click', generate);
  $('fb-msg').addEventListener('input', count);
  $('fb-fond').addEventListener('click', function (e) { if (e.target === this) closeFeedback(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !$('fb-fond').hidden) closeFeedback();
    if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'p' && current && TOOLS[current] && !TOOLS[current].native && frames[current]) { e.preventDefault(); frames[current].contentWindow.print(); }
  });
  $('rech').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('.carte-outil').forEach(function (c) { var ok = !q || c.getAttribute('data-txt').indexOf(q) !== -1; c.style.display = ok ? '' : 'none'; if (ok) n++; });
    document.querySelectorAll('.groupe-accueil').forEach(function (g) { g.style.display = g.querySelector('.carte-outil:not([style*="none"])') ? '' : 'none'; });
    $('accueil-vide').hidden = n > 0;
  });
  window.addEventListener('popstate', route);
  window.addEventListener('hashchange', route);
  // ----- présentation de démarrage et fenêtres d'information -----
  (function () {
    var st = document.createElement('style'); st.textContent = D.shared['slides-css']; document.head.appendChild(st);
    var sc = document.createElement('script'); sc.textContent = D.shared['slides-js']; document.head.appendChild(sc);
  })();
  function esc(t) { return String(t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function buildIntro() {
    var logo = (document.querySelector('.ab-nav img') || {}).src || '', icons = {}, tiles = '';
    document.querySelectorAll('.carte-outil').forEach(function (c) { icons[c.getAttribute('data-open')] = c.querySelector('.ic i').className; });
    var keys = Object.keys(TOOLS).filter(function (k) { return icons[k]; }), shown = keys.slice(0, 8);
    shown.forEach(function (k, i) { tiles += '<div class="ci-tool" style="--i:' + i + '"><i class="' + esc(icons[k]) + '"></i><span>' + esc(TOOLS[k].title) + '</span></div>'; });
    var more = keys.length - shown.length;
    var root = document.createElement('div'); root.id = 'ciIntro'; root.className = 'ci-intro'; root.setAttribute('role', 'dialog'); root.setAttribute('aria-modal', 'true'); root.setAttribute('aria-label', 'Présentation des outils'); root.hidden = true;
    root.innerHTML = '<div class="ci-bg" aria-hidden="true"><span></span><span></span><span></span></div><div class="ci-bar" aria-hidden="true"><i></i></div>'
      + '<button type="button" class="ci-skip"><span>Passer</span> <i class="fas fa-xmark"></i></button><div class="ci-stage">'
      + '<section class="ci-slide ci-hero"><div class="ci-logo r1"><img src="' + esc(logo) + '" alt="ABhD"></div><div class="ci-kicker r2">Bienvenue</div><h2 class="r2">Tous vos outils, au même endroit.</h2><p class="r3">Simulateurs, calculateurs, mémos et documents : de quoi préparer vos entretiens, sans rien installer.</p></section>'
      + '<section class="ci-slide"><h2 class="r1"><i class="fas fa-toolbox"></i> Un outil pour chaque projet</h2><div class="ci-grid">' + tiles + '</div>' + (more > 0 ? '<p class="r4 ci-more">… et ' + more + ' autre' + (more > 1 ? 's' : '') + ' à découvrir.</p>' : '') + '</section>'
      + '<section class="ci-slide"><h2 class="r1"><i class="fas fa-user-shield"></i> Rien n\'est enregistré</h2><p class="r2 ci-big">Tout se passe dans votre navigateur.</p><p class="r3">Aucune donnée ne peut être enregistrée, et aucune donnée n\'est stockée informatiquement : tout disparaît à la fermeture de la page.</p></section>'
      + '<section class="ci-slide"><h2 class="r1"><i class="fas fa-bolt"></i> Gagnez du temps</h2><ul class="ci-list">'
      + '<li class="r2"><i class="fas fa-link"></i><div><b>Partagez par lien</b><span>Un lien reprend la simulation, sans rien enregistrer.</span></div></li>'
      + '<li class="r3"><i class="fas fa-print"></i><div><b>Imprimez, copiez, envoyez</b><span>Un document propre à remettre à votre client.</span></div></li>'
      + '<li class="r4"><i class="fas fa-comment-dots"></i><div><b>Dites-nous ce qui manque</b><span>Le bouton « Un retour ? » prépare un e-mail prêt à envoyer.</span></div></li></ul></section>'
      + '<section class="ci-slide"><h2 class="r1"><i class="fas fa-circle-info"></i> Des aides, pas des substituts</h2><p class="r2 ci-big">Ces outils complètent vos outils internes, sans les remplacer.</p><p class="r3">Vérifiez toujours l\'exactitude des données avant toute communication à un client.</p></section>'
      + '<section class="ci-slide ci-last"><h2 class="r1"><i class="fas fa-rocket"></i> C\'est parti !</h2><p class="r2 ci-big">Choisissez un outil pour commencer.</p><div class="r3 ci-actions"><button type="button" class="ci-btn ci-btn-light ci-go">Découvrir les outils <i class="fas fa-arrow-right"></i></button></div></section></div>'
      + '<button type="button" class="ci-nav ci-prev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button><button type="button" class="ci-nav ci-next" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button><div class="ci-dots" role="tablist"></div>';
    document.body.appendChild(root); return root;
  }
  // HTML des fenêtres d'information : liste blanche de balises, sans script ni gestionnaire d'événement
  var OK_TAGS = /^(P|BR|B|STRONG|I|EM|U|UL|OL|LI|A|H2|H3|H4|SPAN|DIV|HR|BLOCKQUOTE|SMALL|MARK)$/;
  function clean(html) {
    var doc = new DOMParser().parseFromString('<body>' + html + '</body>', 'text/html');
    (function walk(n) {
      Array.prototype.slice.call(n.childNodes).forEach(function (c) {
        if (c.nodeType === 8) { n.removeChild(c); return; }
        if (c.nodeType !== 1) return;
        if (/^(SCRIPT|STYLE|IFRAME|OBJECT|EMBED|FORM|LINK|META)$/.test(c.tagName)) { n.removeChild(c); return; }
        walk(c);
        if (!OK_TAGS.test(c.tagName)) { while (c.firstChild) n.insertBefore(c.firstChild, c); n.removeChild(c); return; }
        var href = c.tagName === 'A' ? c.getAttribute('href') : null;
        Array.prototype.slice.call(c.attributes).forEach(function (a) { c.removeAttribute(a.name); });
        if (href && /^(https?:|mailto:|tel:)/i.test(href.trim())) c.setAttribute('href', href.trim());
      });
    })(doc.body);
    return doc.body.innerHTML;
  }
  function popupConfig(id) {
    var e = $(id); if (!e) return null;
    var txt = e.textContent.replace(/\r/g, ''), i = txt.search(/^---\s*$/m); if (i < 0) return null;
    var o = {}; txt.slice(0, i).split('\n').forEach(function (l) { l = l.trim(); if (!l || l[0] === '#') return; var m = /^(\w+)\s*:\s*(.*)$/.exec(l); if (m) o[m[1]] = m[2].trim(); });
    o.html = clean(txt.slice(i).replace(/^---\s*/, '')); o.id = id; return o;
  }
  function popupsDue() {
    var today = new Date().toISOString().slice(0, 10), out = [];
    var store = function (w) { try { return window[w]; } catch (e) { return null; } };
    var get = function (s, k) { try { return s && s.getItem(k); } catch (e) { return null; } }, set = function (s, k, v) { try { s && s.setItem(k, v); } catch (e) {} };
    ['cfg-popup1', 'cfg-popup2'].forEach(function (id) {
      var p = popupConfig(id); if (!p || !/^(true|vrai|oui|1)$/i.test(p.actif || '') || !p.html.replace(/<[^>]*>/g, '').trim()) return;
      if (p.debut && today < p.debut) return; if (p.fin && today > p.fin) return;
      var key = 'popup_' + id + '_' + (p.version || '1'), f = (p.frequence || 'session').toLowerCase(), ss = store('sessionStorage'), ls = store('localStorage'), due;
      if (f === 'toujours') due = true; else if (f === 'jour') due = get(ls, key) !== today; else if (f === 'une_fois') due = !get(ls, key); else due = !get(ss, key);
      if (!due) return;
      out.push({ p: p, seen: function () { if (f === 'jour') set(ls, key, today); else if (f === 'une_fois') set(ls, key, '1'); else if (f !== 'toujours') set(ss, key, '1'); } });
    });
    return out;
  }
  function showPopups() {
    var queue = popupsDue();
    (function next() {
      var q = queue.shift(); if (!q) return; var p = q.p;
      var f = document.createElement('div'); f.className = 'pp-fond'; f.setAttribute('role', 'dialog'); f.setAttribute('aria-modal', 'true');
      f.innerHTML = '<div class="pp-boite">' + (p.titre ? '<div class="pp-tete"><h2>' + esc(p.titre) + '</h2><button type="button" class="pp-x" aria-label="Fermer">&times;</button></div>' : '') + '<div class="pp-corps">' + p.html + '</div><div class="pp-pied"><button type="button" class="pp-ok">' + esc(p.bouton || 'Fermer') + '</button></div></div>';
      f.querySelectorAll('a[href]').forEach(function (a) { a.target = '_blank'; a.rel = 'noopener noreferrer'; if (!/^(https?:|mailto:|tel:)/i.test(a.getAttribute('href'))) a.removeAttribute('href'); });
      var close = function () { f.remove(); setTimeout(next, 250); };
      f.addEventListener('click', function (e) { if (e.target === f || e.target.closest('.pp-ok, .pp-x')) close(); });
      document.addEventListener('keydown', function esc2(e) { if (e.key === 'Escape' && document.body.contains(f)) { document.removeEventListener('keydown', esc2); close(); } });
      document.body.appendChild(f); q.seen(); f.querySelector('.pp-ok').focus();
    })();
  }
  function startup() {
    var onHome = !accueil.hidden, introOn = !/^(false|faux|non|0|off)$/i.test(GEN.presentation_demarrage || 'true');
    if (!window.ciSlideshow) return;
    var root = introOn ? buildIntro() : null, ctl = null, seenIntro = false;
    if (root) {
      try { seenIntro = sessionStorage.getItem('toolsIntroSeen') === '1'; } catch (e) {}
      ctl = window.ciSlideshow(root, { seenKey: 'toolsIntroSeen', autoOpen: onHome, duration: 7000, replaySelector: '[data-intro-replay]' });
    }
    if (!root) document.querySelectorAll('[data-intro-replay]').forEach(function (b) { b.hidden = true; });
    if (!onHome) return;
    if (root && !seenIntro) {
      new MutationObserver(function (m, ob) { if (root.hidden) { ob.disconnect(); showPopups(); } }).observe(root, { attributes: true, attributeFilter: ['hidden'] });
    } else showPopups();
  }
  route();
  startup();
})();
