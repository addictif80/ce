/**
 * Journal d'audit de /tools : enregistre les clics, les saisies et quelques actions (voir includes/audit.php).
 * Les événements sont regroupés puis envoyés au serveur ; l'horodatage est celui du serveur.
 * Jamais journalisés : champs mot de passe, fichiers, et numéros sensibles (IBAN, RIB, carte, sécurité sociale, SIREN/SIRET) dont la valeur est masquée.
 */
(function () {
  if (window.__toolsAudit) return; window.__toolsAudit = true;
  const TOOL = (window.TOOLS_AUDIT && window.TOOLS_AUDIT.tool) || '';
  const AUDIT_URL = (window.TOOLS_AUDIT && window.TOOLS_AUDIT.url) || 'audit.php';
  const SENSITIVE = /iban|rib|bic|carte|card|nir|secu|siren|siret|password|passwd|mdp|cvv|cvc/i;
  const noValueTool = TOOL === 'validateurs';
  let sid = ''; try { sid = sessionStorage.getItem('toolsAuditSid') || ''; if (!sid) { sid = Array.from(crypto.getRandomValues(new Uint8Array(8)), b => b.toString(16).padStart(2, '0')).join(''); sessionStorage.setItem('toolsAuditSid', sid); } } catch (e) { sid = String(Date.now()); }
  const q = []; let timer = null;
  const clip = (s, n) => String(s == null ? '' : s).replace(/\s+/g, ' ').trim().slice(0, n);

  function push(ev, target, value) {
    q.push({ sid, tool: TOOL, page: location.pathname + location.search, ev, target: clip(target, 250), value: value == null ? undefined : String(value).slice(0, 500) });
    if (q.length >= 20) flush(); else if (!timer) timer = setTimeout(flush, 2500);
  }
  function flush(beacon) {
    if (timer) { clearTimeout(timer); timer = null; }
    if (!q.length) return;
    const body = JSON.stringify({ events: q.splice(0, 100) });
    try {
      if (beacon && navigator.sendBeacon) navigator.sendBeacon(AUDIT_URL, new Blob([body], { type: 'application/json' }));
      else fetch(AUDIT_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body, credentials: 'same-origin', keepalive: true }).catch(() => {});
    } catch (e) {}
    if (q.length) flush(beacon);
  }

  function labelOf(el) {
    if (!el) return '';
    const aria = el.getAttribute && (el.getAttribute('aria-label') || el.getAttribute('title'));
    if (el.id) { const l = document.querySelector('label[for="' + (window.CSS && CSS.escape ? CSS.escape(el.id) : el.id) + '"]'); if (l) return clip(l.textContent, 80); }
    const wrap = el.closest && el.closest('label'); if (wrap) return clip(wrap.textContent, 80);
    const cell = el.closest && el.closest('.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-6,.col-12,.col,.mb-2,.mb-3,.form-group'); const lab = cell && cell.querySelector('label'); if (lab) return clip(lab.textContent, 80);
    return clip(aria || el.placeholder || el.name || el.id || el.tagName, 80);
  }
  function describe(el) {
    const tag = el.tagName.toLowerCase();
    const txt = clip(el.getAttribute('aria-label') || el.title || el.value && (tag === 'input' && /button|submit/.test(el.type) ? el.value : '') || el.textContent, 80);
    const id = el.id ? '#' + el.id : '';
    const href = tag === 'a' ? ' → ' + clip(el.getAttribute('href') || '', 100) : '';
    return tag + id + (txt ? ' « ' + txt + ' »' : '') + href;
  }
  const isSensitive = el => noValueTool || el.type === 'password' || SENSITIVE.test((el.id || '') + ' ' + (el.name || '') + ' ' + (el.placeholder || '') + ' ' + labelOf(el));

  // Clics (phase de capture : même si la page arrête la propagation)
  document.addEventListener('click', e => {
    const el = e.target.closest && e.target.closest('button,a,[role=tab],[role=button],summary,input[type=button],input[type=submit],input[type=checkbox],input[type=radio],.tool-card,[data-audit]');
    if (!el || el.closest('[data-audit-skip]')) return;
    if (el.matches('input[type=checkbox],input[type=radio]')) return; // consignés par l'événement « change »
    push('clic', describe(el));
  }, true);

  // Saisies et choix (à la validation du champ : changement de valeur ou perte du focus)
  document.addEventListener('change', e => {
    const el = e.target; if (!el || !el.matches || !el.matches('input,select,textarea') || el.closest('[data-audit-skip]')) return;
    if (el.type === 'file') { push('saisie', labelOf(el), '(fichier choisi)'); return; }
    let v;
    if (el.type === 'checkbox') v = el.checked ? 'coché' : 'décoché';
    else if (el.type === 'radio') v = el.checked ? 'sélectionné : ' + (el.value || '') : '';
    else if (el.tagName === 'SELECT') v = clip(el.selectedOptions[0] ? el.selectedOptions[0].textContent : el.value, 200);
    else v = el.value;
    if (v === '') return;
    push('saisie', labelOf(el) + (el.id ? ' [#' + el.id + ']' : ''), isSensitive(el) ? '(valeur masquée)' : v);
  }, true);

  // Zones de texte riches (éditeur du courrier) : contenu à la sortie du champ
  document.addEventListener('focusout', e => {
    const el = e.target; if (!el || !el.isContentEditable || el.closest('[data-audit-skip]')) return;
    const t = clip(el.innerText, 500); if (t) push('saisie', labelOf(el) + (el.id ? ' [#' + el.id + ']' : ' (texte)'), noValueTool ? '(valeur masquée)' : t);
  }, true);

  window.addEventListener('beforeprint', () => push('impression', 'impression de la page'));
  document.addEventListener('copy', () => push('copie', 'copie dans le presse-papiers'));
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') flush(true); });
  window.addEventListener('pagehide', () => flush(true));
  window.toolsAuditPush = push; // pour les scripts des outils (ex. résultat affiché)
})();
