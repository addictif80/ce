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
  route();
})();
