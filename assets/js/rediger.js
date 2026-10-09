/**
 * Aide à la rédaction pour les éditeurs de courrier : « Corriger », « Reformuler », « Répondre à un mail ».
 * redigerAttach(editorEl, {endpoint, objet: inputEl|null, ctx: () => ({civilite, nom})})
 * Les propositions sont toujours soumises à validation (comparaison, puis Appliquer / Ignorer) : rien n'est modifié sans accord.
 */
(function () {
  const TAGS = ['P', 'BR', 'B', 'STRONG', 'I', 'EM', 'U', 'UL', 'OL', 'LI', 'DIV'];
  function clean(html) {
    const d = document.createElement('div'); d.innerHTML = html;
    (function walk(n) {
      [...n.childNodes].forEach(c => {
        if (c.nodeType === 8) c.remove();
        else if (c.nodeType === 1) {
          if (!TAGS.includes(c.tagName)) { c.replaceWith(...c.childNodes); } else { [...c.attributes].forEach(a => c.removeAttribute(a.name)); walk(c); }
        }
      });
    })(d);
    return d.innerHTML;
  }
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const txt = html => { const d = document.createElement('div'); d.innerHTML = html.replace(/<\/(p|div|li)>|<br\s*\/?>/gi, '\n'); return d.textContent; };

  // Comparaison mot à mot (plus longue sous-suite commune) : les mots modifiés ou ajoutés sont surlignés dans la proposition.
  function diffHtml(a, b) {
    const A = a.split(/(\s+)/).filter(x => x !== ''), B = b.split(/(\s+)/).filter(x => x !== '');
    if (A.length * B.length > 4e6) return esc(b).replace(/\n/g, '<br>');
    const n = A.length, m = B.length, L = Array.from({ length: n + 1 }, () => new Uint16Array(m + 1));
    for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--) L[i][j] = A[i] === B[j] ? L[i + 1][j + 1] + 1 : Math.max(L[i + 1][j], L[i][j + 1]);
    let i = 0, j = 0, out = '';
    while (j < m) {
      if (i < n && A[i] === B[j]) { out += esc(B[j]); i++; j++; }
      else if (i < n && L[i + 1][j] >= L[i][j + 1]) i++;
      else { out += /^\s+$/.test(B[j]) ? esc(B[j]) : '<mark class="rd-mark">' + esc(B[j]) + '</mark>'; j++; }
    }
    return out.replace(/\n/g, '<br>');
  }

  let css = false;
  function addCss() {
    if (css) return; css = true;
    const s = document.createElement('style');
    s.textContent = '.rd-btn{background:#fff;border:1px solid #dee2e6;border-radius:4px;height:28px;padding:0 9px;font-size:12px;color:#495057;width:auto!important;white-space:nowrap}.rd-btn:hover{background:#fff5f6;border-color:#e4002b;color:#e4002b}.rd-btn[disabled]{opacity:.55;pointer-events:none}'
      + '.rd-panel{border:1px solid #dee2e6;border-top:3px solid #e4002b;border-radius:6px;background:#fff;padding:12px;margin-top:8px;font-size:.9rem}.rd-panel h6{font-size:.85rem;text-transform:uppercase;color:#6c757d;margin-bottom:8px}'
      + '.rd-prop{border:1px solid #e9ecef;border-radius:6px;background:#fafafa;padding:10px 12px;max-height:320px;overflow:auto}.rd-mark{background:#fff3cd;border-radius:2px;padding:0 1px}.rd-spin{display:inline-block;width:14px;height:14px;border:2px solid #e4002b;border-right-color:transparent;border-radius:50%;animation:rdspin .7s linear infinite;vertical-align:-2px;margin-right:6px}@keyframes rdspin{to{transform:rotate(360deg)}}'
      + '.rd-sep{display:inline-block;width:1px;height:18px;background:#dee2e6;margin:0 4px;vertical-align:middle}';
    document.head.appendChild(s);
  }

  window.redigerAttach = function (editor, opts) {
    if (!editor || editor.dataset.rdOn) return; editor.dataset.rdOn = '1'; addCss(); opts = opts || {};
    const bar = editor.previousElementSibling; if (!bar) return;
    const panel = document.createElement('div'); panel.className = 'rd-panel'; panel.hidden = true; editor.after(panel);
    const mk = (icon, label, title, fn) => { const b = document.createElement('button'); b.type = 'button'; b.className = 'rd-btn'; b.title = title; b.innerHTML = '<i class="fas ' + icon + ' me-1"></i>' + label; b.addEventListener('mousedown', e => e.preventDefault()); b.addEventListener('click', fn); return b; };
    const sep = document.createElement('span'); sep.className = 'rd-sep';
    const bCorr = mk('fa-spell-check', 'Corriger', 'Corriger l\'orthographe et la grammaire', () => run('correct'));
    const bRew = mk('fa-pen-nib', 'Reformuler', 'Proposer une reformulation', () => ask());
    const bRep = mk('fa-reply', 'Répondre à un mail', 'Rédiger une réponse à un message reçu', () => reply());
    bar.append(sep, bCorr, bRew, bRep);

    let range = null;
    function target() { // sélection dans l'éditeur ? sinon tout le corps
      range = null;
      const s = getSelection();
      if (s.rangeCount && !s.isCollapsed && editor.contains(s.anchorNode) && editor.contains(s.focusNode)) {
        range = s.getRangeAt(0).cloneRange(); const d = document.createElement('div'); d.appendChild(range.cloneContents()); return { html: d.innerHTML, part: true };
      }
      return { html: editor.innerHTML, part: false };
    }
    function busy(on, msg) { [bCorr, bRew, bRep].forEach(b => b.disabled = on); if (on) { panel.hidden = false; panel.innerHTML = '<span class="rd-spin"></span>' + esc(msg || 'Analyse du texte…'); } }
    function fail(m) { panel.hidden = false; panel.innerHTML = '<div class="text-danger"><i class="fas fa-circle-exclamation me-1"></i>' + esc(m) + '</div><div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary rd-close">Fermer</button></div>'; panel.querySelector('.rd-close').onclick = close; }
    function close() { panel.hidden = true; panel.innerHTML = ''; }
    async function call(body) {
      try {
        const r = await fetch(opts.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' }, body: JSON.stringify(body), credentials: 'same-origin' });
        return await r.json();
      } catch (e) { return { error: 'Connexion impossible. Réessayez.' }; }
    }

    async function run(mode, tone) {
      const t = target();
      if (!txt(t.html).trim()) { fail('Rédigez d\'abord le texte du courrier.'); return; }
      busy(true, mode === 'correct' ? 'Vérification du texte…' : 'Préparation d\'une proposition…');
      const res = await call({ mode, tone, text: t.html }); busy(false);
      if (!res.ok) { fail(res.error || 'Erreur.'); return; }
      const prop = clean(res.html), a = txt(t.html).trim(), b = txt(prop).trim();
      if (mode === 'correct' && a === b) { panel.hidden = false; panel.innerHTML = '<div class="text-success"><i class="fas fa-circle-check me-1"></i>Aucune faute détectée.</div><div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary rd-close">Fermer</button></div>'; panel.querySelector('.rd-close').onclick = close; return; }
      show(mode === 'correct' ? 'Corrections proposées' : 'Reformulation proposée', prop, diffHtml(a, b), t.part, null);
    }

    function show(title, propHtml, view, part, objet) {
      panel.hidden = false;
      panel.innerHTML = '<h6>' + esc(title) + (part ? ' <span class="text-muted text-lowercase">(passage sélectionné)</span>' : '') + '</h6><div class="rd-prop">' + view + '</div>'
        + (objet ? '<div class="small mt-2"><strong>Objet proposé :</strong> ' + esc(objet) + '</div>' : '')
        + '<div class="small text-muted mt-1">' + (view === propHtml ? 'Relisez avant d\'appliquer.' : 'Les passages surlignés sont modifiés ou ajoutés. Relisez avant d\'appliquer.') + '</div>'
        + '<div class="d-flex gap-2 mt-2 flex-wrap"><button type="button" class="btn btn-sm btn-danger rd-apply"><i class="fas fa-check me-1"></i>Appliquer</button>'
        + '<button type="button" class="btn btn-sm btn-outline-secondary rd-close">Ignorer</button></div>';
      panel.querySelector('.rd-close').onclick = close;
      panel.querySelector('.rd-apply').onclick = () => {
        editor.focus();
        if (part && range) { const s = getSelection(); s.removeAllRanges(); s.addRange(range); } else { document.execCommand('selectAll'); }
        document.execCommand('insertHTML', false, propHtml);
        if (objet && opts.objet && !opts.objet.value.trim()) opts.objet.value = objet;
        editor.dispatchEvent(new Event('input', { bubbles: true }));
        close();
      };
      panel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function ask() {
      const t = target();
      if (!txt(t.html).trim()) { fail('Rédigez d\'abord le texte du courrier.'); return; }
      panel.hidden = false;
      panel.innerHTML = '<h6>Reformuler' + (t.part ? ' le passage sélectionné' : ' le courrier') + '</h6><div class="d-flex flex-wrap gap-2 align-items-center">'
        + '<label class="mb-0">Style souhaité</label><select class="form-select form-select-sm rd-tone" style="width:auto"><option value="neutre">Professionnel</option><option value="formel">Très formel</option><option value="chaleureux">Chaleureux</option><option value="concis">Concis</option><option value="pedagogue">Pédagogue (sans jargon)</option></select>'
        + '<button type="button" class="btn btn-sm btn-danger rd-go">Proposer</button><button type="button" class="btn btn-sm btn-outline-secondary rd-close">Annuler</button></div>';
      panel.querySelector('.rd-close').onclick = close;
      panel.querySelector('.rd-go').onclick = () => { const tone = panel.querySelector('.rd-tone').value; run('rewrite', tone); };
    }

    function reply() {
      target(); range = null; // la réponse remplace / complète le corps entier
      panel.hidden = false;
      panel.innerHTML = '<h6>Répondre à un mail</h6>'
        + '<label class="form-label mb-1">Message reçu (collez-le ici)</label><textarea class="form-control form-control-sm rd-src" rows="6" placeholder="Collez le mail ou le courrier du client…"></textarea>'
        + '<div class="small text-muted mb-2">Évitez de coller des coordonnées bancaires complètes ou des données sensibles : le texte est envoyé à un service externe pour la rédaction.</div>'
        + '<label class="form-label mb-1">Que souhaitez-vous répondre ? <span class="text-muted">(facultatif)</span></label>'
        + '<div class="mb-1 d-flex flex-wrap gap-1">' + ['Accepter la demande', 'Refuser poliment', 'Demander des précisions', 'Confirmer un rendez-vous', 'Informer d\'un délai de traitement', 'Demander des pièces justificatives'].map(x => '<button type="button" class="btn btn-sm btn-outline-secondary py-0 rd-chip">' + x + '</button>').join('') + '</div>'
        + '<textarea class="form-control form-control-sm rd-ins" rows="2" placeholder="Ex. : confirmer le rendez-vous du 12/05 à 14h et demander les 3 derniers relevés de compte"></textarea>'
        + '<div class="d-flex flex-wrap gap-2 align-items-center mt-2"><label class="mb-0">Style</label><select class="form-select form-select-sm rd-tone" style="width:auto"><option value="neutre">Professionnel</option><option value="formel">Très formel</option><option value="chaleureux">Chaleureux</option><option value="concis">Concis</option><option value="pedagogue">Pédagogue</option></select>'
        + '<label class="mb-0 ms-2">Longueur</label><select class="form-select form-select-sm rd-len" style="width:auto"><option value="court">Courte</option><option value="" selected>Moyenne</option><option value="long">Détaillée</option></select>'
        + '<button type="button" class="btn btn-sm btn-danger rd-go ms-auto"><i class="fas fa-reply me-1"></i>Rédiger la réponse</button><button type="button" class="btn btn-sm btn-outline-secondary rd-close">Annuler</button></div>';
      const ins = panel.querySelector('.rd-ins');
      panel.querySelectorAll('.rd-chip').forEach(c => c.onclick = () => { ins.value = (ins.value.trim() ? ins.value.trim().replace(/[.;]?$/, '; ') : '') + c.textContent.toLowerCase(); ins.focus(); });
      panel.querySelector('.rd-close').onclick = close;
      panel.querySelector('.rd-go').onclick = async () => {
        const src = panel.querySelector('.rd-src').value, tone = panel.querySelector('.rd-tone').value, length = panel.querySelector('.rd-len').value, instr = ins.value;
        const c = (opts.ctx && opts.ctx()) || {};
        busy(true, 'Rédaction de la réponse…');
        const res = await call({ mode: 'reply', text: src, tone, length, instructions: instr, civilite: c.civilite || '', nom: c.nom || '' }); busy(false);
        if (!res.ok) { fail(res.error || 'Erreur.'); return; }
        const prop = clean(res.html);
        const had = txt(editor.innerHTML).trim() !== '';
        show('Réponse proposée' + (had ? ' (remplacera le texte actuel)' : ''), prop, prop, false, res.objet || null);
      };
    }
  };
})();
