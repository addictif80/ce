<?php
// Éditeur de tableur partagé par le portail (documents enregistrés) et /tools (sans enregistrement serveur).
// Variables : $docMode, $docInit, $docApi (voir docbar.php).
$docType = 'sheet';
require __DIR__ . '/docbar.php';
?>
<style>
.sx{--sx-line:#d9dde3}
.sx-tb{display:flex;flex-wrap:wrap;gap:6px;align-items:center;background:#fff;border:1px solid var(--sx-line);border-radius:10px 10px 0 0;padding:6px 8px}
.sx-tb .btn{padding:2px 9px}.sx-tb .btn.on{background:#e8eefc;border-color:#9db4ee}.sx-tb .vr{width:1px;height:22px;background:var(--sx-line);margin:0 2px}
.sx-tb select{width:auto}.sx-tb input[type=color]{width:30px;height:28px;padding:1px;border:1px solid #ced4da;border-radius:6px;background:#fff}
.sx-fb{display:flex;gap:6px;align-items:center;background:#fff;border:1px solid var(--sx-line);border-top:0;padding:4px 8px}.sx-fb .nm{width:70px;text-align:center;font-weight:600}.sx-fb .fx{flex:1}
.sx-wrap{position:relative;overflow:auto;height:60vh;min-height:320px;background:#fff;border:1px solid var(--sx-line);border-top:0;outline:none}
.sx-t{border-collapse:collapse;table-layout:fixed;font-size:13.5px;user-select:none}
.sx-t th{background:#f3f5f8;border:1px solid var(--sx-line);font-weight:600;color:#555;text-align:center;position:sticky;top:0;z-index:2;height:24px;padding:0;cursor:pointer;position:relative}
.sx-t th.rh{position:sticky;left:0;z-index:1;top:auto;width:46px}.sx-t th.corner{left:0;z-index:3}
.sx-t th.hl{background:#dce6fb}.sx-t td{border:1px solid #e6e9ee;height:24px;padding:0 5px;white-space:nowrap;overflow:hidden;text-overflow:clip;cursor:cell}
.sx-t td.sel{background:rgba(66,133,244,.12)}.sx-t td.act{outline:2px solid #1a73e8;outline-offset:-2px;background:#fff}
.sx-t td.num{text-align:right}.sx-t td.err{color:#c40025;font-weight:600}
.sx-rz{position:absolute;right:-3px;top:0;width:7px;height:100%;cursor:col-resize;z-index:4}
.sx-ed{position:absolute;z-index:6;border:2px solid #1a73e8;padding:0 4px;font:inherit;font-size:13.5px;outline:none;background:#fff;box-sizing:border-box}
.sx-tabs{display:flex;gap:2px;align-items:center;background:#f3f5f8;border:1px solid var(--sx-line);border-top:0;border-radius:0 0 10px 10px;padding:4px 8px;flex-wrap:wrap}
.sx-tab{border:1px solid transparent;background:none;border-radius:6px;padding:2px 12px;font-size:13px;cursor:pointer}.sx-tab.on{background:#fff;border-color:var(--sx-line);font-weight:700}
.sx-stat{margin-left:auto;font-size:12.5px;color:#555}
.sx-help{font-size:12.5px;color:#6c757d}
#sx-print{display:none}
@media print{@page{size:A4 landscape;margin:10mm}body *{visibility:hidden}#sx-print,#sx-print *{visibility:visible}#sx-print{display:block;position:absolute;left:0;top:0;width:100%}
#sx-print table{border-collapse:collapse;font:10pt Arial,sans-serif;width:100%}#sx-print td{padding:2px 5px;border:1px solid #ccc}#sx-print h2{font:700 13pt Arial;margin:0 0 6px}}
</style>
<div class="sx" id="sx">
 <div class="sx-tb no-print" role="toolbar" aria-label="Mise en forme">
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="undo" title="Annuler (Ctrl+Z)"><i class="fas fa-rotate-left"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="redo" title="Rétablir (Ctrl+Y)"><i class="fas fa-rotate-right"></i></button><span class="vr"></span>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-st="b" title="Gras (Ctrl+B)"><b>G</b></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-st="i" title="Italique (Ctrl+I)"><i>I</i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-al="left" title="Aligner à gauche"><i class="fas fa-align-left"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-al="center" title="Centrer"><i class="fas fa-align-center"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-al="right" title="Aligner à droite"><i class="fas fa-align-right"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-st="bd" title="Bordures"><i class="fas fa-border-all"></i></button>
  <label class="mb-0" title="Couleur de remplissage"><i class="fas fa-fill-drip"></i> <input type="color" id="sx-bg" value="#ffff99"></label>
  <label class="mb-0" title="Couleur du texte"><i class="fas fa-font"></i> <input type="color" id="sx-fc" value="#c40025"></label>
  <select id="sx-nf" class="form-select form-select-sm" title="Format des nombres" aria-label="Format des nombres"></select><span class="vr"></span>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="insrow" title="Insérer une ligne au-dessus">+ ligne</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="delrow" title="Supprimer la ligne">− ligne</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="inscol" title="Insérer une colonne à gauche">+ col.</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="delcol" title="Supprimer la colonne">− col.</button><span class="vr"></span>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="import" title="Importer un fichier Excel, OpenDocument ou CSV"><i class="fas fa-file-import me-1"></i>Importer</button>
  <div class="dropdown d-inline-block"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-export me-1"></i>Exporter</button>
   <ul class="dropdown-menu"><li><button type="button" class="dropdown-item" data-act="xlsx">Excel (.xlsx)</button></li><li><button type="button" class="dropdown-item" data-act="csv">CSV (feuille active)</button></li><li><button type="button" class="dropdown-item" data-act="print">Imprimer / PDF</button></li></ul></div>
  <input type="file" id="sx-file" accept=".xlsx,.xls,.ods,.csv,.tsv" class="d-none">
 </div>
 <div class="sx-fb no-print"><input id="sx-nm" class="form-control form-control-sm nm" readonly aria-label="Cellule active"><span class="fw-bold text-muted">ƒx</span><input id="sx-fx" class="form-control form-control-sm fx" aria-label="Contenu de la cellule" autocomplete="off" spellcheck="false"></div>
 <div class="sx-wrap" id="sx-wrap" tabindex="0" aria-label="Feuille de calcul"></div>
 <div class="sx-tabs no-print" id="sx-tabs"></div>
 <div class="sx-help mt-2 no-print">Formules : commencez par <code>=</code> et séparez les arguments par <code>;</code> — ex. <code>=SOMME(B2:B10)</code>, <code>=SI(B2&gt;100;"élevé";"normal")</code>, <code>=-VPM(3,5%/12;240;150000)</code>. Recopier vers le bas : Ctrl+D ; vers la droite : Ctrl+R. Le fichier Excel exporté conserve les valeurs et les formules, mais pas la mise en forme.</div>
</div>
<div id="sx-print"></div>
<script><?php readfile(__DIR__ . '/sheet_engine.js'); ?></script>
<script>
(function () {
  'use strict';
  const E = SheetEngine, $ = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const MAXR = 2000, MAXC = 60, COLW = 96;
  window.toolsOwnPrint = true;
  let data = blank(), calc, undo = [], redo = [], tds = [], sel = { ar: 0, ac: 0, r: 0, c: 0 }, editing = null, drag = false, clip = null;
  function blank() { return { v: 1, active: 0, sheets: [{ name: 'Feuille 1', cells: {}, colw: {}, rows: 60, cols: 20 }] }; }
  function normalize(d) {
    if (!d || !Array.isArray(d.sheets) || !d.sheets.length) return blank();
    d.sheets = d.sheets.slice(0, 20).map((s, i) => ({ name: String(s.name || 'Feuille ' + (i + 1)).replace(/['!]/g, '').slice(0, 30), cells: s.cells && typeof s.cells === 'object' ? s.cells : {}, colw: s.colw || {}, rows: Math.min(MAXR, Math.max(10, +s.rows || 60)), cols: Math.min(MAXC, Math.max(5, +s.cols || 20)) }));
    d.active = Math.min(d.sheets.length - 1, Math.max(0, +d.active || 0)); d.v = 1; return d;
  }
  const sh = () => data.sheets[data.active];
  const cellAt = (c, r) => sh().cells[E.addr(c, r)];
  function setRaw(c, r, raw) { const k = E.addr(c, r); raw = String(raw).slice(0, 2000); let x = sh().cells[k]; if (raw === '') { if (x) { delete x.r; if (!x.s) delete sh().cells[k]; } } else { if (!x) x = sh().cells[k] = {}; x.r = raw; } }
  function setStyle(c, r, fn) { const k = E.addr(c, r); const x = sh().cells[k] || (sh().cells[k] = { r: '' }); x.s = x.s || {}; fn(x.s); Object.keys(x.s).forEach(p => { if (x.s[p] === '' || x.s[p] === false || x.s[p] == null || (p === 'nf' && x.s[p] === 'auto')) delete x.s[p]; }); if (!Object.keys(x.s).length) delete x.s; if (x.r === '' && !x.s) delete sh().cells[k]; }
  const rect = () => ({ r1: Math.min(sel.ar, sel.r), r2: Math.max(sel.ar, sel.r), c1: Math.min(sel.ac, sel.c), c2: Math.max(sel.ac, sel.c) });
  function eachSel(fn) { const q = rect(); for (let r = q.r1; r <= q.r2; r++) for (let c = q.c1; c <= q.c2; c++) fn(c, r); }
  const dirty = () => { window.DocIO && DocIO.markDirty(); };
  const snap = () => JSON.stringify({ s: data.sheets, a: data.active });
  function push() { undo.push(snap()); if (undo.length > 40) undo.shift(); redo = []; }
  function restore(js) { const o = JSON.parse(js); data.sheets = o.s; data.active = o.a; rebuild(); }
  function doUndo() { if (!undo.length) return; redo.push(snap()); restore(undo.pop()); dirty(); }
  function doRedo() { if (!redo.length) return; undo.push(snap()); restore(redo.pop()); dirty(); }

  // ----- affichage -----
  function build() {
    const s = sh(); let h = '<table class="sx-t" id="sx-tbl"><colgroup><col style="width:46px">';
    for (let c = 0; c < s.cols; c++) h += '<col style="width:' + (s.colw[c] || COLW) + 'px">';
    h += '</colgroup><thead><tr><th class="corner rh"></th>';
    for (let c = 0; c < s.cols; c++) h += '<th data-hc="' + c + '">' + E.colName(c) + '<span class="sx-rz" data-rz="' + c + '"></span></th>';
    h += '</tr></thead><tbody>';
    for (let r = 0; r < s.rows; r++) { h += '<tr><th class="rh" data-hr="' + r + '">' + (r + 1) + '</th>'; for (let c = 0; c < s.cols; c++) h += '<td data-c="' + c + '" data-r="' + r + '"></td>'; h += '</tr>'; }
    $('sx-wrap').innerHTML = h + '</tbody></table>';
    tds = []; $('sx-wrap').querySelectorAll('td').forEach(td => { (tds[+td.dataset.r] = tds[+td.dataset.r] || [])[+td.dataset.c] = td; });
  }
  function styleCss(st) { if (!st) return ''; return (st.b ? 'font-weight:700;' : '') + (st.i ? 'font-style:italic;' : '') + (st.a ? 'text-align:' + st.a + ';' : '') + (st.bg ? 'background:' + st.bg + ';' : '') + (st.fc ? 'color:' + st.fc + ';' : '') + (st.bd ? 'border:1px solid #444;' : ''); }
  const okColor = v => typeof v === 'string' && /^#[0-9a-fA-F]{6}$/.test(v);
  function refresh() {
    calc = E.Calc(data); const s = sh();
    for (let r = 0; r < s.rows; r++) for (let c = 0; c < s.cols; c++) {
      const td = tds[r][c], x = s.cells[E.addr(c, r)], v = calc.get(data.active, c, r), st = x && x.s ? x.s : null;
      const txt = x && x.r !== undefined && x.r !== '' ? E.format(v, st && st.nf) : '';
      if (td._t !== txt) { td.textContent = txt; td._t = txt; }
      const cls = (typeof v === 'number' || (typeof v === 'boolean') ? 'num ' : '') + (E.isErr(v) ? 'err ' : '') + (td._sel || ''); if (td._c !== cls) { td.className = cls; td._c = cls; }
      const css = styleCss(st && Object.assign({}, st, { bg: okColor(st.bg) ? st.bg : '', fc: okColor(st.fc) ? st.fc : '' })); if (td._s !== css) { td.style.cssText = css; td._s = css; }
    }
    stats();
  }
  function paintSel() {
    const q = rect(), s = sh();
    for (let r = 0; r < s.rows; r++) for (let c = 0; c < s.cols; c++) { const td = tds[r][c], inS = r >= q.r1 && r <= q.r2 && c >= q.c1 && c <= q.c2, a = r === sel.r && c === sel.c; const f = (inS ? 'sel ' : '') + (a ? 'act ' : ''); if (td._sel !== f) { td._sel = f; td.className = ((td._c || '').replace(/\b(sel|act) /g, '')) + f; td._c = td.className; } }
    document.querySelectorAll('#sx-wrap th.hl').forEach(t => t.classList.remove('hl'));
    for (let c = q.c1; c <= q.c2; c++) { const t = document.querySelector('#sx-wrap th[data-hc="' + c + '"]'); t && t.classList.add('hl'); }
    for (let r = q.r1; r <= q.r2; r++) { const t = document.querySelector('#sx-wrap th[data-hr="' + r + '"]'); t && t.classList.add('hl'); }
    $('sx-nm').value = E.addr(sel.c, sel.r); if (!editing) { const x = cellAt(sel.c, sel.r); $('sx-fx').value = x && x.r !== undefined ? x.r : ''; }
    const x = cellAt(sel.c, sel.r), st = (x && x.s) || {};
    document.querySelectorAll('[data-st]').forEach(b => b.classList.toggle('on', !!st[b.dataset.st])); document.querySelectorAll('[data-al]').forEach(b => b.classList.toggle('on', st.a === b.dataset.al)); $('sx-nf').value = st.nf || 'auto';
    stats();
  }
  function stats() { const el = $('sx-stat'); if (!el) return; const q = rect(); let n = 0, sum = 0, cnt = 0; for (let r = q.r1; r <= q.r2; r++) for (let c = q.c1; c <= q.c2; c++) { cnt++; const v = calc.get(data.active, c, r); if (typeof v === 'number') { n++; sum += v; } }
    el.textContent = n > 1 || (n === 1 && cnt > 1) ? 'Somme : ' + E.format(sum) + ' · Moyenne : ' + E.format(sum / n) + ' · Nb : ' + n : ''; }
  function tabs() { $('sx-tabs').innerHTML = data.sheets.map((s, i) => '<button type="button" class="sx-tab' + (i === data.active ? ' on' : '') + '" data-tab="' + i + '">' + esc(s.name) + '</button>').join('') + '<button type="button" class="sx-tab" data-act="addsheet" title="Ajouter une feuille">+</button><button type="button" class="sx-tab" data-act="rensheet" title="Renommer la feuille"><i class="fas fa-pen"></i></button><button type="button" class="sx-tab" data-act="delsheet" title="Supprimer la feuille"><i class="fas fa-trash"></i></button><span class="sx-stat" id="sx-stat"></span>'; }
  function rebuild() { cancelEdit(); const s = sh(); sel.r = Math.min(sel.r, s.rows - 1); sel.c = Math.min(sel.c, s.cols - 1); sel.ar = Math.min(sel.ar, s.rows - 1); sel.ac = Math.min(sel.ac, s.cols - 1); tabs(); build(); refresh(); paintSel(); }
  const NFS = E.NF; $('sx-nf').innerHTML = Object.keys(NFS).map(k => '<option value="' + k + '">' + NFS[k] + '</option>').join('');

  // ----- édition -----
  function startEdit(init, keep) {
    if (editing) return; const td = tds[sel.r][sel.c], wrap = $('sx-wrap'); const x = cellAt(sel.c, sel.r);
    const inp = document.createElement('input'); inp.className = 'sx-ed'; inp.value = init != null ? init : (x && x.r !== undefined ? x.r : ''); inp.style.left = td.offsetLeft + 'px'; inp.style.top = td.offsetTop + 'px'; inp.style.minWidth = td.offsetWidth + 'px'; inp.style.height = td.offsetHeight + 'px'; inp.style.width = Math.max(td.offsetWidth, 160) + 'px';
    inp.setAttribute('aria-label', 'Saisie de la cellule ' + E.addr(sel.c, sel.r)); inp.spellcheck = false; wrap.appendChild(inp); editing = { inp, c: sel.c, r: sel.r }; inp.focus(); if (init == null && !keep) inp.select(); else inp.setSelectionRange(inp.value.length, inp.value.length);
    inp.addEventListener('input', () => { $('sx-fx').value = inp.value; });
    inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); commit(); move(e.shiftKey ? -1 : 1, 0); } else if (e.key === 'Tab') { e.preventDefault(); commit(); move(0, e.shiftKey ? -1 : 1); } else if (e.key === 'Escape') { e.preventDefault(); cancelEdit(); wrap.focus(); } e.stopPropagation(); });
  }
  function commit() { if (!editing) return; const { inp, c, r } = editing; editing = null; const val = inp.value; inp.remove(); const x = sh().cells[E.addr(c, r)], old = x && x.r !== undefined ? x.r : ''; if (val !== old) { push(); setRaw(c, r, val); refresh(); dirty(); } $('sx-wrap').focus(); }
  function cancelEdit() { if (!editing) return; editing.inp.remove(); editing = null; }
  function move(dr, dc, extend) { const s = sh(); sel.r = Math.max(0, Math.min(s.rows - 1, sel.r + dr)); sel.c = Math.max(0, Math.min(s.cols - 1, sel.c + dc)); if (!extend) { sel.ar = sel.r; sel.ac = sel.c; } paintSel(); const td = tds[sel.r][sel.c], w = $('sx-wrap'); const t = td.offsetTop, l = td.offsetLeft; if (t < w.scrollTop + 24) w.scrollTop = t - 24; else if (t + td.offsetHeight > w.scrollTop + w.clientHeight) w.scrollTop = t + td.offsetHeight - w.clientHeight + 4; if (l < w.scrollLeft + 46) w.scrollLeft = l - 46; else if (l + td.offsetWidth > w.scrollLeft + w.clientWidth) w.scrollLeft = l + td.offsetWidth - w.clientWidth + 4; }
  const wantsRef = () => editing && /^=/.test(editing.inp.value) && /[=+\-*\/^(;&<>:]$/.test(editing.inp.value.slice(0, editing.inp.selectionStart));

  const wrap = $('sx-wrap');
  wrap.addEventListener('mousedown', e => {
    const rz = e.target.closest('[data-rz]'); if (rz) { const c = +rz.dataset.rz, x0 = e.clientX, w0 = sh().colw[c] || COLW; const mv = ev => { sh().colw[c] = Math.max(30, Math.min(600, w0 + ev.clientX - x0)); wrap.querySelector('colgroup').children[c + 1].style.width = sh().colw[c] + 'px'; }; const up = () => { document.removeEventListener('mousemove', mv); document.removeEventListener('mouseup', up); dirty(); }; document.addEventListener('mousemove', mv); document.addEventListener('mouseup', up); e.preventDefault(); return; }
    const hc = e.target.closest('[data-hc]'), hr = e.target.closest('[data-hr]'), td = e.target.closest('td');
    if (td && wantsRef()) { e.preventDefault(); const i = editing.inp, p = i.selectionStart; i.value = i.value.slice(0, p) + E.addr(+td.dataset.c, +td.dataset.r) + i.value.slice(i.selectionEnd); $('sx-fx').value = i.value; i.focus(); return; }
    if (editing) commit();
    if (hc) { sel = { ar: 0, ac: +hc.dataset.hc, r: sh().rows - 1, c: +hc.dataset.hc }; paintSel(); wrap.focus(); e.preventDefault(); return; }
    if (hr) { sel = { ar: +hr.dataset.hr, ac: 0, r: +hr.dataset.hr, c: sh().cols - 1 }; paintSel(); wrap.focus(); e.preventDefault(); return; }
    if (!td) return; const c = +td.dataset.c, r = +td.dataset.r;
    if (e.shiftKey) { sel.r = r; sel.c = c; } else sel = { ar: r, ac: c, r, c };
    drag = true; paintSel(); wrap.focus(); e.preventDefault();
  });
  wrap.addEventListener('mouseover', e => { if (!drag) return; const td = e.target.closest('td'); if (td) { sel.r = +td.dataset.r; sel.c = +td.dataset.c; paintSel(); } });
  document.addEventListener('mouseup', () => { drag = false; });
  wrap.addEventListener('dblclick', e => { if (e.target.closest('td')) startEdit(); });
  wrap.addEventListener('keydown', e => {
    if (editing) return; const ctrl = e.ctrlKey || e.metaKey, k = e.key;
    if (ctrl) { const l = k.toLowerCase(); if (l === 'z') { e.preventDefault(); doUndo(); } else if (l === 'y') { e.preventDefault(); doRedo(); } else if (l === 'b') { e.preventDefault(); toggleStyle('b'); } else if (l === 'i') { e.preventDefault(); toggleStyle('i'); } else if (l === 'd') { e.preventDefault(); fill('down'); } else if (l === 'r') { e.preventDefault(); fill('right'); } else if (l === 'a') { e.preventDefault(); sel = { ar: 0, ac: 0, r: sh().rows - 1, c: sh().cols - 1 }; paintSel(); } return; }
    if (k === 'ArrowDown') { e.preventDefault(); move(1, 0, e.shiftKey); } else if (k === 'ArrowUp') { e.preventDefault(); move(-1, 0, e.shiftKey); } else if (k === 'ArrowLeft') { e.preventDefault(); move(0, -1, e.shiftKey); } else if (k === 'ArrowRight') { e.preventDefault(); move(0, 1, e.shiftKey); }
    else if (k === 'Tab') { e.preventDefault(); move(0, e.shiftKey ? -1 : 1); } else if (k === 'Enter') { e.preventDefault(); startEdit(); } else if (k === 'F2') { e.preventDefault(); startEdit(null, true); }
    else if (k === 'Delete' || k === 'Backspace') { e.preventDefault(); push(); eachSel((c, r) => setRaw(c, r, '')); refresh(); paintSel(); dirty(); }
    else if (k.length === 1 && !e.altKey) { e.preventDefault(); startEdit(k); }
  });
  $('sx-fx').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); push(); setRaw(sel.c, sel.r, $('sx-fx').value); refresh(); dirty(); wrap.focus(); move(1, 0); } else if (e.key === 'Escape') { paintSel(); wrap.focus(); } });
  $('sx-fx').addEventListener('focus', () => { if (editing) commit(); });

  // ----- copier / coller / recopier -----
  function rectData() { const q = rect(), rows = []; for (let r = q.r1; r <= q.r2; r++) { const row = []; for (let c = q.c1; c <= q.c2; c++) { const x = cellAt(c, r); row.push(x ? JSON.parse(JSON.stringify(x)) : null); } rows.push(row); } return { q, rows }; }
  const tsv = () => { const q = rect(), lines = []; for (let r = q.r1; r <= q.r2; r++) { const row = []; for (let c = q.c1; c <= q.c2; c++) { const v = calc.get(data.active, c, r); row.push(E.format(v, ((cellAt(c, r) || {}).s || {}).nf).replace(/[\t\n\r]+/g, ' ')); } lines.push(row.join('\t')); } return lines.join('\n'); };
  document.addEventListener('copy', e => { if (editing || document.activeElement !== wrap) return; e.preventDefault(); const t = tsv(); e.clipboardData.setData('text/plain', t); clip = { text: t, ...rectData() }; });
  document.addEventListener('cut', e => { if (editing || document.activeElement !== wrap) return; e.preventDefault(); const t = tsv(); e.clipboardData.setData('text/plain', t); clip = { text: t, ...rectData() }; push(); eachSel((c, r) => setRaw(c, r, '')); refresh(); dirty(); });
  document.addEventListener('paste', e => {
    if (editing || document.activeElement !== wrap) return; e.preventDefault(); const t = (e.clipboardData.getData('text/plain') || '').replace(/\r/g, ''); push(); const s = sh();
    if (clip && clip.text === t.replace(/\n$/, '')) { clip.rows.forEach((row, i) => row.forEach((x, j) => { const r = sel.r + i, c = sel.c + j; if (r >= s.rows || c >= s.cols) return; const k = E.addr(c, r); if (!x) { delete s.cells[k]; return; } const n = JSON.parse(JSON.stringify(x)); if (n.r) n.r = E.shiftFormula(n.r, r - (clip.q.r1 + i), c - (clip.q.c1 + j)); s.cells[k] = n; }));
    } else { const rows = t.replace(/\n$/, '').split('\n').map(l => l.split('\t')); if (rows.length * (rows[0] || []).length > 20000) { alert('Collage trop volumineux.'); return; } rows.forEach((row, i) => row.forEach((v, j) => { const r = sel.r + i, c = sel.c + j; if (r < MAXR && c < MAXC) { if (r >= s.rows) s.rows = Math.min(MAXR, r + 1); if (c >= s.cols) s.cols = Math.min(MAXC, c + 1); setRaw(c, r, v); } })); build(); }
    refresh(); paintSel(); dirty();
  });
  function fill(dir) { const q = rect(); push(); const s = sh();
    if (dir === 'down') for (let c = q.c1; c <= q.c2; c++) { const src = s.cells[E.addr(c, q.r1)]; for (let r = q.r1 + 1; r <= q.r2; r++) { const k = E.addr(c, r); if (!src) delete s.cells[k]; else { const n = JSON.parse(JSON.stringify(src)); if (n.r) n.r = E.shiftFormula(n.r, r - q.r1, 0); s.cells[k] = n; } } }
    else for (let r = q.r1; r <= q.r2; r++) { const src = s.cells[E.addr(q.c1, r)]; for (let c = q.c1 + 1; c <= q.c2; c++) { const k = E.addr(c, r); if (!src) delete s.cells[k]; else { const n = JSON.parse(JSON.stringify(src)); if (n.r) n.r = E.shiftFormula(n.r, 0, c - q.c1); s.cells[k] = n; } } }
    refresh(); dirty(); }

  // ----- mise en forme -----
  function toggleStyle(p) { push(); const x = cellAt(sel.c, sel.r), on = !(x && x.s && x.s[p]); eachSel((c, r) => setStyle(c, r, s => { s[p] = on; })); refresh(); paintSel(); dirty(); }
  document.querySelector('.sx-tb').addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    if (b.dataset.st) return toggleStyle(b.dataset.st);
    if (b.dataset.al) { push(); const x = cellAt(sel.c, sel.r), same = x && x.s && x.s.a === b.dataset.al; eachSel((c, r) => setStyle(c, r, s => { s.a = same ? '' : b.dataset.al; })); refresh(); paintSel(); dirty(); return; }
    if (b.dataset.act) act(b.dataset.act);
  });
  $('sx-bg').addEventListener('input', e => { push(); eachSel((c, r) => setStyle(c, r, s => { s.bg = e.target.value; })); refresh(); dirty(); });
  $('sx-fc').addEventListener('input', e => { push(); eachSel((c, r) => setStyle(c, r, s => { s.fc = e.target.value; })); refresh(); dirty(); });
  $('sx-nf').addEventListener('change', e => { push(); eachSel((c, r) => setStyle(c, r, s => { s.nf = e.target.value; })); refresh(); paintSel(); dirty(); });

  // ----- lignes, colonnes, feuilles -----
  function shiftCells(kind, index, delta) {
    const s = sh(), out = {}, n = kind === 'row' ? s.rows : s.cols;
    if (delta > 0 && n >= (kind === 'row' ? MAXR : MAXC)) { alert('Limite de taille atteinte.'); return false; }
    Object.keys(s.cells).forEach(k => { const a = E.parseAddr(k); const v = kind === 'row' ? a.r : a.c; let nv = v;
      if (delta > 0) { if (v >= index) nv = v + 1; } else { if (v === index) return; if (v > index) nv = v - 1; }
      const c = kind === 'col' ? nv : a.c, r = kind === 'row' ? nv : a.r; const x = s.cells[k]; if (x.r) x.r = E.adjustFormula(x.r, kind, index, delta); out[E.addr(c, r)] = x; });
    s.cells = out;
    if (kind === 'col') { const w = {}; Object.keys(s.colw).forEach(c => { c = +c; if (delta > 0) w[c >= index ? c + 1 : c] = s.colw[c]; else if (c !== index) w[c > index ? c - 1 : c] = s.colw[c]; }); s.colw = w; }
    if (kind === 'row') s.rows = Math.max(5, s.rows + delta); else s.cols = Math.max(3, s.cols + delta);
    return true;
  }
  function act(a) {
    if (a === 'undo') return doUndo(); if (a === 'redo') return doRedo();
    if (a === 'insrow' || a === 'delrow') { push(); if (shiftCells('row', rect().r1, a === 'insrow' ? 1 : -1)) { rebuild(); dirty(); } return; }
    if (a === 'inscol' || a === 'delcol') { push(); if (shiftCells('col', rect().c1, a === 'inscol' ? 1 : -1)) { rebuild(); dirty(); } return; }
    if (a === 'addsheet') { if (data.sheets.length >= 20) return; push(); let n = data.sheets.length + 1; while (data.sheets.some(s => s.name === 'Feuille ' + n)) n++; data.sheets.push({ name: 'Feuille ' + n, cells: {}, colw: {}, rows: 60, cols: 20 }); data.active = data.sheets.length - 1; sel = { ar: 0, ac: 0, r: 0, c: 0 }; rebuild(); dirty(); return; }
    if (a === 'rensheet') { const n = prompt('Nom de la feuille :', sh().name); if (n === null) return; const nm = n.replace(/['!]/g, '').trim().slice(0, 30); if (!nm || data.sheets.some((s, i) => i !== data.active && s.name.toLowerCase() === nm.toLowerCase())) { alert('Nom vide ou déjà utilisé.'); return; } push(); sh().name = nm; tabs(); refresh(); dirty(); return; }
    if (a === 'delsheet') { if (data.sheets.length < 2) { alert('Un tableur contient au moins une feuille.'); return; } if (!confirm('Supprimer la feuille « ' + sh().name + ' » ?')) return; push(); data.sheets.splice(data.active, 1); data.active = Math.max(0, data.active - 1); rebuild(); dirty(); return; }
    if (a === 'import') return $('sx-file').click();
    if (a === 'xlsx') return exportXlsx(); if (a === 'csv') return exportCsv(); if (a === 'print') return window.print();
  }
  $('sx-tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (!b) return; if (b.dataset.tab != null) { commit(); data.active = +b.dataset.tab; sel = { ar: 0, ac: 0, r: 0, c: 0 }; rebuild(); } else if (b.dataset.act) act(b.dataset.act); });
  wrap.addEventListener('scroll', () => { if (!editing && sh().rows < MAXR && wrap.scrollTop + wrap.clientHeight > wrap.scrollHeight - 40) { push(); sh().rows = Math.min(MAXR, sh().rows + 40); build(); refresh(); paintSel(); } });

  // ----- import / export -----
  const lib = (src, test) => new Promise((ok, ko) => { if (test()) return ok(); const s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = () => ko(new Error('chargement impossible')); document.head.appendChild(s); });
  const XLSX_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
  const needX = () => lib(XLSX_SRC, () => window.XLSX).catch(() => { alert('La bibliothèque d\'import/export Excel n\'a pas pu être chargée (connexion ou blocage du réseau). Utilisez l\'export CSV en attendant.'); throw 0; });
  function numFmtOf(z) { z = String(z || ''); if (/%/.test(z)) return 'pct'; if (/€|EUR/.test(z)) return 'eur'; if (/[dmy]/i.test(z) && !/General|0\.0/i.test(z)) return 'date'; if (/0\.00/.test(z)) return z.includes('#,##') ? 'sep' : 'n2'; if (/^0$/.test(z)) return 'n0'; return 'auto'; }
  $('sx-file').addEventListener('change', async e => {
    const f = e.target.files[0]; e.target.value = ''; if (!f) return; if (f.size > 8 * 1048576) { alert('Fichier trop volumineux (8 Mo maximum).'); return; }
    try { await needX(); } catch (er) { return; }
    try {
      const wb = XLSX.read(await f.arrayBuffer(), { type: 'array' }), out = { v: 1, active: 0, sheets: [] };
      wb.SheetNames.slice(0, 20).forEach(name => { const ws = wb.Sheets[name], ref = ws['!ref'] ? XLSX.utils.decode_range(ws['!ref']) : { s: { r: 0, c: 0 }, e: { r: 0, c: 0 } }, s = { name: name.replace(/['!]/g, '').slice(0, 30) || 'Feuille', cells: {}, colw: {}, rows: Math.min(MAXR, Math.max(60, ref.e.r + 20)), cols: Math.min(MAXC, Math.max(20, ref.e.c + 3)) };
        for (let r = ref.s.r; r <= Math.min(ref.e.r, MAXR - 1); r++) for (let c = ref.s.c; c <= Math.min(ref.e.c, MAXC - 1); c++) { const cell = ws[XLSX.utils.encode_cell({ r, c })]; if (!cell) continue; let raw = '';
          if (cell.f) raw = E.fromExcel(cell.f); else if (cell.t === 'n') raw = String(cell.v).replace('.', ','); else if (cell.t === 'b') raw = cell.v ? 'VRAI' : 'FAUX'; else if (cell.v != null) raw = String(cell.v);
          if (raw === '') continue; const x = { r: raw.slice(0, 2000) }, nf = cell.z ? numFmtOf(cell.z) : 'auto'; if (nf !== 'auto') x.s = { nf }; s.cells[E.addr(c - 0, r - 0)] = x; }
        out.sheets.push(s); });
      if (!out.sheets.length) throw 0; push(); data = normalize(out); sel = { ar: 0, ac: 0, r: 0, c: 0 }; rebuild(); dirty();
      const n = window.DocIO && document.getElementById('db-name'); if (n && /sans titre/i.test(n.value)) n.value = f.name.replace(/\.[^.]+$/, '');
    } catch (er) { alert('Ce fichier n\'a pas pu être lu.'); }
  });
  async function exportXlsx() {
    try { await needX(); } catch (er) { return; }
    const wb = XLSX.utils.book_new(), c0 = E.Calc(data);
    data.sheets.forEach((s, si) => { const ws = {}; let maxR = 0, maxC = 0;
      Object.keys(s.cells).forEach(k => { const x = s.cells[k]; if (x.r === undefined || x.r === '') return; const a = E.parseAddr(k), v = c0.get(si, a.c, a.r); const cell = {}; const f = E.toExcel(x.r);
        if (typeof v === 'number') { cell.t = 'n'; cell.v = v; } else if (typeof v === 'boolean') { cell.t = 'b'; cell.v = v; } else if (E.isErr(v)) { cell.t = 'e'; cell.v = 0x07; } else { cell.t = 's'; cell.v = String(v); }
        if (f) cell.f = f; const nf = x.s && x.s.nf; if (nf && typeof v === 'number') cell.z = { n0: '0', n2: '0.00', sep: '#,##0.00', eur: '#,##0.00 "€"', pct: '0.00%', date: 'dd/mm/yyyy' }[nf] || undefined;
        ws[k] = cell; maxR = Math.max(maxR, a.r); maxC = Math.max(maxC, a.c); });
      ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: maxR, c: maxC } }); ws['!cols'] = Array.from({ length: maxC + 1 }, (_, c) => ({ wpx: s.colw[c] || COLW }));
      XLSX.utils.book_append_sheet(wb, ws, s.name.slice(0, 31)); });
    const buf = XLSX.write(wb, { bookType: 'xlsx', type: 'array' }); DocIO.download(DocIO.safeName() + '.xlsx', new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
  }
  function usedRange() { let mr = 0, mc = 0; Object.keys(sh().cells).forEach(k => { const x = sh().cells[k]; if (x.r === undefined || x.r === '') return; const a = E.parseAddr(k); mr = Math.max(mr, a.r); mc = Math.max(mc, a.c); }); return { mr, mc }; }
  function exportCsv() { const { mr, mc } = usedRange(), rows = []; for (let r = 0; r <= mr; r++) { const row = []; for (let c = 0; c <= mc; c++) { let t = E.format(calc.get(data.active, c, r), ((cellAt(c, r) || {}).s || {}).nf); if (/^[=+\-@]/.test(t) && !/^-?[\d\s.,]+(?:\s?[%€])?$/.test(t)) t = "'" + t; row.push('"' + t.replace(/"/g, '""') + '"'); } rows.push(row.join(';')); }
    DocIO.download(DocIO.safeName() + '.csv', new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8' })); }
  window.addEventListener('beforeprint', () => { const { mr, mc } = usedRange(); let h = '<h2>' + esc(DocIO.nom()) + ' — ' + esc(sh().name) + '</h2><table>'; for (let r = 0; r <= mr; r++) { h += '<tr>'; for (let c = 0; c <= mc; c++) { const x = cellAt(c, r), st = x && x.s ? Object.assign({}, x.s, { bg: okColor(x.s.bg) ? x.s.bg : '', fc: okColor(x.s.fc) ? x.s.fc : '' }) : null; const v = calc.get(data.active, c, r); h += '<td style="' + styleCss(st).replace('border:1px solid #444;', 'border:1px solid #444;') + (typeof v === 'number' && !(st && st.a) ? 'text-align:right;' : '') + '">' + esc(E.format(v, st && st.nf)) + '</td>'; } h += '</tr>'; } $('sx-print').innerHTML = h + '</table>'; });
  window.addEventListener('afterprint', () => { $('sx-print').innerHTML = ''; });

  // ----- liaison avec la barre de document -----
  const api = { get: () => ({ v: 1, active: data.active, sheets: data.sheets }), set: d => { data = normalize(d); undo = []; redo = []; sel = { ar: 0, ac: 0, r: 0, c: 0 }; rebuild(); }, blank };
  window.SheetDoc = { api, E, getData: () => data, setCell: (a, raw) => { const p = E.parseAddr(a); push(); setRaw(p.c, p.r, raw); refresh(); dirty(); } };
  rebuild(); DocIO.init(api); wrap.focus();
})();
</script>
