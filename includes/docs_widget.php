<?php
/**
 * Documents rattachés à un enregistrement : à inclure une fois dans la page d'un module, puis appeler
 *   DocsWidget.mount(element, 'cle_du_module', numeroEnregistrement)
 * Le module n'a rien d'autre à faire ; les documents sont gérés dans Outils > Documents.
 */
require_once __DIR__ . '/documents.php';
$docsBase = $docsBase ?? '../documents/';
?>
<script>
window.DocsWidget = (function () {
  'use strict';
  const BASE = <?= json_encode($docsBase) ?>, esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const call = async (action, p) => { const fd = new FormData(); fd.append('action', action); Object.keys(p || {}).forEach(k => fd.append(k, p[k])); try { const r = await fetch(BASE + 'api.php', { method: 'POST', body: fd, headers: { 'X-Doc-Api': '1' }, credentials: 'same-origin' }); return await r.json(); } catch (e) { return { ok: false, error: 'Connexion impossible.' }; } };
  const BLANK = { sheet: { v: 1, active: 0, sheets: [{ name: 'Feuille 1', cells: {}, colw: {}, rows: 60, cols: 20 }] },
    slides: { v: 1, theme: 'ce', trans: 'fade', slides: [{ bg: '#ffffff', notes: '', els: [{ id: 't1', t: 'text', x: 80, y: 170, w: 800, h: 110, text: 'Titre de la présentation', size: 52, b: true, i: false, color: '#c40025', al: 'center', bul: false, font: 'Arial' }, { id: 't2', t: 'text', x: 80, y: 300, w: 800, h: 60, text: 'Sous-titre', size: 26, b: false, i: false, color: '#222222', al: 'center', bul: false, font: 'Arial' }] }] } };
  const icon = t => t === 'sheet' ? 'fa-table text-success' : 'fa-display text-danger';
  async function mount(el, module, rid) {
    if (!el) return; el.innerHTML = '<div class="text-muted small">Chargement…</div>';
    const r = await call('links_for', { module, record_id: rid });
    const docs = r.ok ? r.docs : [];
    el.innerHTML = '<div class="d-flex flex-wrap gap-2 mb-2"><button type="button" class="btn btn-sm btn-outline-success" data-n="sheet"><i class="fas fa-table me-1"></i>Nouveau tableur</button><button type="button" class="btn btn-sm btn-outline-danger" data-n="slides"><i class="fas fa-display me-1"></i>Nouvelle présentation</button><button type="button" class="btn btn-sm btn-outline-secondary" data-n="attach"><i class="fas fa-paperclip me-1"></i>Rattacher un document existant</button></div>'
      + '<div data-pick></div>' + (r.ok ? (docs.length ? docs.map(d => '<div class="d-flex align-items-center gap-2 border-bottom py-1"><i class="fas ' + icon(d.type) + '"></i><a href="' + BASE + (d.type === 'sheet' ? 'sheet' : 'slides') + '.php?id=' + d.id + '">' + esc(d.nom) + '</a><span class="text-muted small ms-auto">' + new Date(d.updated_at.replace(' ', 'T')).toLocaleDateString('fr-FR') + '</span><button type="button" class="btn btn-sm btn-link text-danger p-0" data-un="' + d.id + '" title="Détacher de cet enregistrement"><i class="fas fa-link-slash"></i></button></div>').join('') : '<div class="text-muted small">Aucun document rattaché.</div>') : '<div class="text-danger small">' + esc(r.error) + '</div>');
    el.onclick = async e => {
      const b = e.target.closest('button'); if (!b) return;
      if (b.dataset.n === 'sheet' || b.dataset.n === 'slides') { const t = b.dataset.n, nom = prompt('Nom du document :', t === 'sheet' ? 'Tableur' : 'Présentation'); if (nom === null) return; const x = await call('create', { type: t, nom, data: JSON.stringify(BLANK[t]), module, record_id: rid }); if (x.ok) location.href = BASE + (t === 'sheet' ? 'sheet' : 'slides') + '.php?id=' + x.id; else alert(x.error); }
      else if (b.dataset.n === 'attach') { const l = await call('list'); const here = new Set(docs.map(d => +d.id)); const cand = l.ok ? l.docs.filter(d => !here.has(+d.id)) : []; el.querySelector('[data-pick]').innerHTML = cand.length ? '<div class="border rounded p-2 mb-2" style="max-height:200px;overflow:auto">' + cand.map(d => '<button type="button" class="btn btn-sm btn-light d-block w-100 text-start mb-1" data-lk="' + d.id + '"><i class="fas ' + icon(d.type) + ' me-1"></i>' + esc(d.nom) + '</button>').join('') + '</div>' : '<div class="text-muted small mb-2">Aucun autre document à rattacher.</div>'; }
      else if (b.dataset.lk) { const x = await call('link', { id: b.dataset.lk, module, record_id: rid }); if (x.ok) mount(el, module, rid); else alert(x.error); }
      else if (b.dataset.un) { const x = await call('unlink', { id: b.dataset.un, module, record_id: rid }); if (x.ok) mount(el, module, rid); }
    };
  }
  return { mount };
})();
</script>
