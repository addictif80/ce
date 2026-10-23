<?php
// Éditeur de présentations partagé par le portail (documents enregistrés) et /tools (sans enregistrement serveur).
// Variables : $docMode, $docInit, $docApi (voir docbar.php).
$docType = 'slides';
require __DIR__ . '/docbar.php';
?>
<style>
.sl-app{display:grid;grid-template-columns:170px minmax(0,1fr) 250px;gap:10px;align-items:start}
.sl-panel{background:#fff;border:1px solid #d9dde3;border-radius:10px;padding:8px}
.sl-thumbs{max-height:72vh;overflow:auto}.sl-th{position:relative;border:2px solid #d9dde3;border-radius:6px;margin-bottom:8px;cursor:pointer;overflow:hidden;background:#fff}.sl-th.on{border-color:#c40025}
.sl-th .n{position:absolute;left:4px;top:2px;font-size:11px;font-weight:700;color:#555;background:rgba(255,255,255,.8);border-radius:4px;padding:0 4px;z-index:2}
.sl-th .sl-box{pointer-events:none}
.sl-tb{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;align-items:center}.sl-tb .btn{padding:2px 9px}
.sl-stage{background:#e9ecf1;border:1px solid #d9dde3;border-radius:10px;padding:12px;display:flex;justify-content:center;overflow:hidden}
.sl-box{position:relative;overflow:hidden;background:#fff}.sl-box .sl{position:absolute;left:0;top:0;width:960px;height:540px;transform-origin:0 0;overflow:hidden}
.sl-el{position:absolute;box-sizing:border-box;overflow:hidden}.sl-el.txt{white-space:pre-wrap;word-break:break-word;line-height:1.2}.sl-el.txt .bl{position:relative;padding-left:.9em}.sl-el.txt .bl::before{content:"•";position:absolute;left:0}
.sl-el img{width:100%;height:100%;object-fit:contain;display:block}.sl-el table{width:100%;height:100%;border-collapse:collapse}.sl-el td{border:1px solid #9aa3af;padding:3px 6px;vertical-align:middle;overflow:hidden}
.sl-ov{position:absolute;inset:0;pointer-events:none;z-index:5}.sl-sel{position:absolute;border:2px solid #1a73e8;pointer-events:none;box-sizing:border-box}
.sl-h{position:absolute;width:11px;height:11px;background:#fff;border:2px solid #1a73e8;border-radius:3px;pointer-events:auto;box-sizing:border-box}
.sl-ed{position:absolute;z-index:8;border:2px solid #1a73e8;resize:none;margin:0;padding:0;background:rgba(255,255,255,.92);outline:none;box-sizing:border-box;overflow:auto;line-height:1.2}
.sl-prop label{font-size:12px;color:#555;margin-bottom:1px;display:block}.sl-prop .row{--bs-gutter-x:.5rem;--bs-gutter-y:.4rem}.sl-prop h6{font-size:13px;margin:10px 0 6px;border-bottom:1px solid #eee;padding-bottom:3px}
.sl-grid input{width:100%;font-size:12px;padding:1px 3px}.sl-grid td{padding:1px}
.sl-pres{position:fixed;inset:0;background:#000;z-index:3000;display:none;align-items:center;justify-content:center}.sl-pres.on{display:flex}
.sl-pres .sl-box{background:#fff}.sl-pres .ctl{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);color:#fff;background:rgba(0,0,0,.55);border-radius:20px;padding:3px 14px;font-size:13px;opacity:.0;transition:opacity .3s}.sl-pres:hover .ctl{opacity:1}
.sl-pres .sl.fade-in{animation:slFade .55s both}.sl-pres .sl.slide-in{animation:slSlide .5s cubic-bezier(.2,.8,.2,1) both}.sl-pres .sl.zoom-in{animation:slZoom .5s cubic-bezier(.2,.8,.2,1) both}
@keyframes slFade{from{opacity:0}to{opacity:1}}@keyframes slSlide{from{transform:translateX(var(--d,60px));opacity:0}to{transform:none;opacity:1}}@keyframes slZoom{from{transform:scale(.92);opacity:0}to{transform:none;opacity:1}}
#sl-print{display:none}
@media print{@page{size:A4 landscape;margin:0}body *{visibility:hidden}#sl-print,#sl-print *{visibility:visible}#sl-print{display:block;position:absolute;left:0;top:0}.sl-pg{width:297mm;height:210mm;position:relative;overflow:hidden;page-break-after:always;display:flex;align-items:center;justify-content:center}.sl-pg .sl-box{width:277mm;height:155.8mm}}
@media(max-width:1000px){.sl-app{grid-template-columns:1fr}.sl-thumbs{max-height:none;display:flex;gap:8px;overflow:auto}.sl-th{min-width:140px;margin:0}}
@media(prefers-reduced-motion:reduce){.sl-pres .sl{animation:none!important}}
</style>
<div class="sl-tb no-print" role="toolbar" aria-label="Insertion">
 <button type="button" class="btn btn-sm btn-outline-secondary" data-act="undo" title="Annuler (Ctrl+Z)"><i class="fas fa-rotate-left"></i></button>
 <button type="button" class="btn btn-sm btn-outline-secondary" data-act="redo" title="Rétablir (Ctrl+Y)"><i class="fas fa-rotate-right"></i></button>
 <button type="button" class="btn btn-sm btn-outline-secondary" data-add="text"><i class="fas fa-font me-1"></i>Texte</button>
 <button type="button" class="btn btn-sm btn-outline-secondary" data-add="img"><i class="fas fa-image me-1"></i>Image</button>
 <div class="dropdown d-inline-block"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-shapes me-1"></i>Forme</button>
  <ul class="dropdown-menu"><li><button type="button" class="dropdown-item" data-add="rect">Rectangle</button></li><li><button type="button" class="dropdown-item" data-add="ellipse">Ellipse</button></li><li><button type="button" class="dropdown-item" data-add="line">Ligne</button></li></ul></div>
 <button type="button" class="btn btn-sm btn-outline-secondary" data-add="table"><i class="fas fa-table me-1"></i>Tableau</button>
 <button type="button" class="btn btn-sm btn-outline-secondary" data-add="chart"><i class="fas fa-chart-column me-1"></i>Graphique</button>
 <select id="sl-theme" class="form-select form-select-sm" style="width:auto" aria-label="Thème"></select>
 <select id="sl-trans" class="form-select form-select-sm" style="width:auto" aria-label="Transition de présentation"><option value="fade">Transition : fondu</option><option value="slide">Transition : glissement</option><option value="zoom">Transition : zoom</option><option value="none">Transition : aucune</option></select>
 <button type="button" class="btn btn-sm btn-danger" data-act="present"><i class="fas fa-play me-1"></i>Présenter</button>
 <div class="dropdown d-inline-block"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-export me-1"></i>Exporter</button>
  <ul class="dropdown-menu"><li><button type="button" class="dropdown-item" data-act="pptx">PowerPoint (.pptx)</button></li><li><button type="button" class="dropdown-item" data-act="print">PDF / Imprimer</button></li></ul></div>
 <input type="file" id="sl-img" accept="image/png,image/jpeg,image/gif,image/webp" class="d-none">
</div>
<div class="sl-app">
 <div class="sl-panel no-print"><div class="d-flex gap-1 mb-2 flex-wrap"><div class="dropdown"><button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" title="Ajouter une diapositive"><i class="fas fa-plus"></i></button><ul class="dropdown-menu" id="sl-layouts"></ul></div>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="dup" title="Dupliquer la diapositive"><i class="fas fa-clone"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="up" title="Monter"><i class="fas fa-arrow-up"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="down" title="Descendre"><i class="fas fa-arrow-down"></i></button>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-act="delslide" title="Supprimer la diapositive"><i class="fas fa-trash"></i></button></div>
  <div class="sl-thumbs" id="sl-thumbs"></div></div>
 <div><div class="sl-stage" id="sl-stage"><div class="sl-box" id="sl-canvas"><div class="sl-ov" id="sl-ov"></div></div></div>
  <div class="small text-muted mt-1 no-print">Double-clic sur un texte pour le modifier · glissez pour déplacer · poignées pour redimensionner · flèches pour ajuster · Suppr pour effacer · Ctrl+D pour dupliquer.</div></div>
 <div class="sl-panel sl-prop no-print" id="sl-props"></div>
</div>
<div class="sl-pres" id="sl-pres" role="dialog" aria-label="Présentation"><div class="sl-box" id="sl-pbox"></div><div class="ctl" id="sl-pctl"></div></div>
<div id="sl-print"></div>
<script>
(function () {
  'use strict';
  const $ = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const W = 960, H = 540, MAXS = 200, MAXE = 60;
  window.toolsOwnPrint = true;
  const THEMES = {
    ce: { nom: 'Caisse d\'Épargne', bg: '#ffffff', title: '#c40025', text: '#222222', accent: '#c40025' },
    sobre: { nom: 'Sobre', bg: '#ffffff', title: '#1f2937', text: '#374151', accent: '#2b6cb0' },
    nuit: { nom: 'Nuit', bg: '#111827', title: '#fbbf24', text: '#f3f4f6', accent: '#fbbf24' },
    nature: { nom: 'Nature', bg: '#f3faf3', title: '#1b5e20', text: '#25402a', accent: '#2e9b57' }
  };
  const FONTS = ['Arial', 'Georgia', 'Courier New', 'Verdana'];
  const PAL = a => [a, '#2b6cb0', '#2e9b57', '#e8a200', '#8e44ad', '#6b7280'];
  const okC = v => typeof v === 'string' && /^#[0-9a-fA-F]{6}$/.test(v);
  const num = (v, d, lo, hi) => { v = +v; if (!isFinite(v)) v = d; return Math.min(hi, Math.max(lo, v)); };
  const uid = () => 'e' + Math.random().toString(36).slice(2, 9);
  let D = blank(), cur = 0, selId = null, undo = [], redo = [], clip = null, editing = null, sc = 1;
  const th = () => THEMES[D.theme] || THEMES.ce;
  function mkText(x, y, w, h, text, o) { return Object.assign({ id: uid(), t: 'text', x, y, w, h, text, size: 28, b: false, i: false, color: th().text, al: 'left', bul: false, font: 'Arial' }, o || {}); }
  function layout(kind) {
    const T = th(), els = [];
    if (kind === 'title') els.push(mkText(80, 170, 800, 110, 'Titre de la présentation', { size: 52, b: true, color: T.title, al: 'center' }), mkText(80, 300, 800, 60, 'Sous-titre', { size: 26, al: 'center' }));
    else if (kind === 'content') els.push(mkText(50, 30, 860, 80, 'Titre', { size: 38, b: true, color: T.title }), mkText(50, 130, 860, 370, 'Premier point\nDeuxième point\nTroisième point', { size: 26, bul: true }));
    else if (kind === 'two') els.push(mkText(50, 30, 860, 80, 'Titre', { size: 38, b: true, color: T.title }), mkText(50, 130, 415, 370, 'Colonne de gauche\nPoint A\nPoint B', { size: 24, bul: true }), mkText(495, 130, 415, 370, 'Colonne de droite\nPoint C\nPoint D', { size: 24, bul: true }));
    else if (kind === 'titleonly') els.push(mkText(50, 30, 860, 80, 'Titre', { size: 38, b: true, color: T.title }));
    return { bg: T.bg, notes: '', els };
  }
  function blank() { const T = THEMES.ce; return { v: 1, theme: 'ce', trans: 'fade', slides: [{ bg: T.bg, notes: '', els: [mkTextT(T, 80, 170, 800, 110, 'Titre de la présentation', { size: 52, b: true, color: T.title, al: 'center' }), mkTextT(T, 80, 300, 800, 60, 'Sous-titre', { size: 26, al: 'center' })] }] }; }
  function mkTextT(T, x, y, w, h, text, o) { return Object.assign({ id: uid(), t: 'text', x, y, w, h, text, size: 28, b: false, i: false, color: T.text, al: 'left', bul: false, font: 'Arial' }, o || {}); }
  function normEl(e) {
    if (!e || typeof e !== 'object') return null; const o = { id: String(e.id || uid()).replace(/[^\w-]/g, '').slice(0, 12) || uid(), t: e.t, x: num(e.x, 0, -W, 2 * W), y: num(e.y, 0, -H, 2 * H), w: num(e.w, 100, 10, 2 * W), h: num(e.h, 50, 4, 2 * H) };
    if (e.t === 'text') Object.assign(o, { text: String(e.text || '').slice(0, 5000), size: num(e.size, 24, 6, 200), b: !!e.b, i: !!e.i, color: okC(e.color) ? e.color : '#222222', al: ['left', 'center', 'right'].includes(e.al) ? e.al : 'left', bul: !!e.bul, font: FONTS.includes(e.font) ? e.font : 'Arial' });
    else if (e.t === 'img') { if (typeof e.src !== 'string' || !/^data:image\/(png|jpeg|gif|webp);base64,/.test(e.src)) return null; o.src = e.src; }
    else if (e.t === 'shape') Object.assign(o, { shape: ['rect', 'ellipse', 'line'].includes(e.shape) ? e.shape : 'rect', fill: e.fill === '' ? '' : (okC(e.fill) ? e.fill : '#c40025'), stroke: e.stroke === '' ? '' : (okC(e.stroke) ? e.stroke : ''), sw: num(e.sw, 2, 0, 30) });
    else if (e.t === 'table') { let rows = Array.isArray(e.rows) ? e.rows.slice(0, 30).map(r => (Array.isArray(r) ? r : []).slice(0, 12).map(c => String(c == null ? '' : c).slice(0, 200))) : []; const n = Math.max(1, ...rows.map(r => r.length)); rows = rows.length ? rows.map(r => { while (r.length < n) r.push(''); return r; }) : [['']]; Object.assign(o, { rows, hdr: e.hdr !== false, size: num(e.size, 18, 6, 80), hfill: okC(e.hfill) ? e.hfill : '#c40025', color: okC(e.color) ? e.color : '#222222' }); }
    else if (e.t === 'chart') { const labels = (Array.isArray(e.labels) ? e.labels : []).slice(0, 24).map(x => String(x).slice(0, 40)); const series = (Array.isArray(e.series) ? e.series : []).slice(0, 6).map((s, i) => ({ name: String((s && s.name) || 'Série ' + (i + 1)).slice(0, 40), values: labels.map((_, j) => num(s && s.values && s.values[j], 0, -1e12, 1e12)) })); Object.assign(o, { type: ['bar', 'line', 'pie'].includes(e.type) ? e.type : 'bar', labels, series: series.length ? series : [{ name: 'Série 1', values: labels.map(() => 0) }], color: okC(e.color) ? e.color : '#222222' }); }
    else return null; return o;
  }
  function normalize(d) {
    if (!d || !Array.isArray(d.slides) || !d.slides.length) return blank();
    const out = { v: 1, theme: THEMES[d.theme] ? d.theme : 'ce', trans: ['fade', 'slide', 'zoom', 'none'].includes(d.trans) ? d.trans : 'fade', slides: [] };
    d.slides.slice(0, MAXS).forEach(s => out.slides.push({ bg: okC(s && s.bg) ? s.bg : '#ffffff', notes: String((s && s.notes) || '').slice(0, 3000), els: (Array.isArray(s && s.els) ? s.els : []).slice(0, MAXE).map(normEl).filter(Boolean) }));
    return out.slides.length ? out : blank();
  }

  // ----- rendu -----
  function chartSvg(e) {
    const w = e.w, h = e.h, P = PAL(th().accent), n = e.labels.length, tc = e.color; let s = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" style="font-family:Arial;font-size:13px;fill:' + tc + '">';
    const legend = e.series.length > 1 || e.type === 'pie';
    if (e.type === 'pie') { const vals = e.series[0].values.map(v => Math.max(0, v)), tot = vals.reduce((a, b) => a + b, 0) || 1, r = Math.min(w * .35, h / 2 - 10), cx = r + 12, cy = h / 2; let a0 = -Math.PI / 2;
      vals.forEach((v, i) => { const a1 = a0 + v / tot * 2 * Math.PI, big = a1 - a0 > Math.PI ? 1 : 0; if (v / tot > .9999) s += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="' + P[i % 6] + '"/>'; else if (v > 0) s += '<path d="M' + cx + ' ' + cy + ' L' + (cx + r * Math.cos(a0)).toFixed(1) + ' ' + (cy + r * Math.sin(a0)).toFixed(1) + ' A' + r + ' ' + r + ' 0 ' + big + ' 1 ' + (cx + r * Math.cos(a1)).toFixed(1) + ' ' + (cy + r * Math.sin(a1)).toFixed(1) + ' Z" fill="' + P[i % 6] + '" stroke="#fff"/>'; a0 = a1; });
      e.labels.forEach((l, i) => { const y = 20 + i * 20; s += '<rect x="' + (cx + r + 24) + '" y="' + (y - 11) + '" width="12" height="12" fill="' + P[i % 6] + '"/><text x="' + (cx + r + 42) + '" y="' + y + '">' + esc(l) + ' (' + Math.round(vals[i] / tot * 100) + ' %)</text>'; });
      return s + '</svg>'; }
    const L = 46, B = legend ? 54 : 30, T = 10, R = 10, pw = w - L - R, ph = h - B - T; let mx = 0, mn = 0; e.series.forEach(se => se.values.forEach(v => { mx = Math.max(mx, v); mn = Math.min(mn, v); })); if (mx === mn) mx = mn + 1;
    const y = v => T + ph - (v - mn) / (mx - mn) * ph;
    for (let k = 0; k <= 4; k++) { const v = mn + (mx - mn) * k / 4, yy = y(v); s += '<line x1="' + L + '" x2="' + (w - R) + '" y1="' + yy + '" y2="' + yy + '" stroke="' + tc + '" stroke-opacity=".18"/><text x="' + (L - 6) + '" y="' + (yy + 4) + '" text-anchor="end">' + (Math.round(v * 100) / 100).toLocaleString('fr-FR') + '</text>'; }
    const slot = pw / Math.max(1, n);
    e.labels.forEach((l, i) => { s += '<text x="' + (L + slot * (i + .5)) + '" y="' + (T + ph + 18) + '" text-anchor="middle">' + esc(l.length > 14 ? l.slice(0, 13) + '…' : l) + '</text>'; });
    if (e.type === 'bar') { const bw = slot * .7 / e.series.length; e.series.forEach((se, si) => se.values.forEach((v, i) => { const x = L + slot * i + slot * .15 + bw * si, y0 = y(Math.max(0, mn)), y1 = y(v); s += '<rect x="' + x.toFixed(1) + '" y="' + Math.min(y0, y1).toFixed(1) + '" width="' + bw.toFixed(1) + '" height="' + Math.max(1, Math.abs(y0 - y1)).toFixed(1) + '" fill="' + P[si % 6] + '"/>'; })); }
    else e.series.forEach((se, si) => { const pts = se.values.map((v, i) => (L + slot * (i + .5)).toFixed(1) + ',' + y(v).toFixed(1)); s += '<polyline points="' + pts.join(' ') + '" fill="none" stroke="' + P[si % 6] + '" stroke-width="3" stroke-linejoin="round"/>' + pts.map(p => '<circle cx="' + p.split(',')[0] + '" cy="' + p.split(',')[1] + '" r="4" fill="' + P[si % 6] + '"/>').join(''); });
    if (legend) e.series.forEach((se, si) => { const x = L + si * 130; s += '<rect x="' + x + '" y="' + (h - 18) + '" width="12" height="12" fill="' + P[si % 6] + '"/><text x="' + (x + 17) + '" y="' + (h - 8) + '">' + esc(se.name) + '</text>'; });
    return s + '</svg>';
  }
  function elHtml(e) {
    const pos = 'left:' + e.x + 'px;top:' + e.y + 'px;width:' + e.w + 'px;height:' + e.h + 'px;', d = ' data-id="' + e.id + '"';
    if (e.t === 'text') { const body = e.bul ? e.text.split('\n').map(l => '<div class="bl">' + esc(l) + '</div>').join('') : esc(e.text); return '<div class="sl-el txt"' + d + ' style="' + pos + 'font-size:' + e.size + 'px;font-family:' + e.font + ';color:' + e.color + ';text-align:' + e.al + ';' + (e.b ? 'font-weight:700;' : '') + (e.i ? 'font-style:italic;' : '') + '">' + body + '</div>'; }
    if (e.t === 'img') return '<div class="sl-el"' + d + ' style="' + pos + '"><img src="' + e.src + '" alt="" draggable="false"></div>';
    if (e.t === 'shape') { if (e.shape === 'line') return '<div class="sl-el"' + d + ' style="' + pos + 'overflow:visible"><svg width="100%" height="100%" viewBox="0 0 ' + e.w + ' ' + e.h + '" preserveAspectRatio="none" style="overflow:visible"><line x1="0" y1="0" x2="' + e.w + '" y2="' + e.h + '" stroke="' + (e.stroke || e.fill || '#c40025') + '" stroke-width="' + Math.max(1, e.sw) + '"/></svg></div>';
      return '<div class="sl-el"' + d + ' style="' + pos + 'background:' + (e.fill || 'transparent') + ';' + (e.stroke ? 'border:' + e.sw + 'px solid ' + e.stroke + ';' : '') + (e.shape === 'ellipse' ? 'border-radius:50%;' : '') + '"></div>'; }
    if (e.t === 'table') return '<div class="sl-el"' + d + ' style="' + pos + 'font-size:' + e.size + 'px;color:' + e.color + '"><table>' + e.rows.map((r, i) => '<tr>' + r.map(c => '<td style="' + (i === 0 && e.hdr ? 'background:' + e.hfill + ';color:#fff;font-weight:700' : '') + '">' + esc(c) + '</td>').join('') + '</tr>').join('') + '</table></div>';
    if (e.t === 'chart') return '<div class="sl-el"' + d + ' style="' + pos + '">' + chartSvg(e) + '</div>';
    return '';
  }
  const slideHtml = s => '<div class="sl" style="background:' + s.bg + '">' + s.els.map(elHtml).join('') + '</div>';
  function box(el, s, width) { const k = width / W; el.style.width = width + 'px'; el.style.height = (H * k) + 'px'; el.innerHTML = slideHtml(s); el.firstChild.style.transform = 'scale(' + k + ')'; }

  // ----- affichage de l'éditeur -----
  function fitCanvas() { const st = $('sl-stage'), avail = st.clientWidth - 24; sc = Math.max(.3, Math.min(avail / W, 1.3)); const c = $('sl-canvas'); c.style.width = (W * sc) + 'px'; c.style.height = (H * sc) + 'px'; }
  function drawCanvas() { const c = $('sl-canvas'), ov = $('sl-ov'); c.innerHTML = slideHtml(D.slides[cur]); c.firstChild.style.transform = 'scale(' + sc + ')'; c.appendChild(ov); drawSel(); }
  function drawSel() {
    const ov = $('sl-ov'); ov.innerHTML = ''; const e = selEl(); if (!e || editing) return;
    const L = e.x * sc, T = e.y * sc, w = e.w * sc, h = e.h * sc;
    ov.innerHTML = '<div class="sl-sel" style="left:' + L + 'px;top:' + T + 'px;width:' + w + 'px;height:' + h + 'px"></div>' + [['nw', L, T], ['ne', L + w, T], ['sw', L, T + h], ['se', L + w, T + h]].map(p => '<div class="sl-h" data-h="' + p[0] + '" style="left:' + (p[1] - 6) + 'px;top:' + (p[2] - 6) + 'px;cursor:' + (p[0] === 'nw' || p[0] === 'se' ? 'nwse-resize' : 'nesw-resize') + '"></div>').join('');
  }
  function drawThumbs() { const t = $('sl-thumbs'); t.innerHTML = D.slides.map((s, i) => '<div class="sl-th' + (i === cur ? ' on' : '') + '" data-i="' + i + '"><span class="n">' + (i + 1) + '</span><div class="sl-box" data-th="' + i + '"></div></div>').join('');
    const w = Math.max(100, t.clientWidth - 8 || 130); t.querySelectorAll('[data-th]').forEach(b => box(b, D.slides[+b.dataset.th], Math.min(w, 220))); }
  const selEl = () => D.slides[cur].els.find(e => e.id === selId) || null;
  function refreshAll() { fitCanvas(); drawCanvas(); drawThumbs(); drawProps(); $('sl-theme').value = D.theme; $('sl-trans').value = D.trans; }
  function redrawKeep() { drawCanvas(); const th0 = $('sl-thumbs').querySelector('[data-th="' + cur + '"]'); if (th0) box(th0, D.slides[cur], th0.clientWidth || 130); }

  // ----- annuler / rétablir (les images volumineuses sont mises en commun pour ne pas saturer la mémoire) -----
  const pool = new Map(); const poolArr = [];
  const ser = () => JSON.stringify({ D, cur }, (k, v) => { if (k === 'src' && typeof v === 'string' && v.length > 200) { if (!pool.has(v)) { pool.set(v, poolArr.length); poolArr.push(v); } return '@' + pool.get(v); } return v; });
  const unser = js => JSON.parse(js, (k, v) => (k === 'src' && typeof v === 'string' && v[0] === '@') ? poolArr[+v.slice(1)] : v);
  const dirty = () => { window.DocIO && DocIO.markDirty(); };
  function push() { undo.push(ser()); if (undo.length > 40) undo.shift(); redo = []; }
  function restore(js) { const o = unser(js); D = o.D; cur = Math.min(o.cur, D.slides.length - 1); selId = null; refreshAll(); }
  const doUndo = () => { if (!undo.length) return; redo.push(ser()); restore(undo.pop()); dirty(); };
  const doRedo = () => { if (!redo.length) return; undo.push(ser()); restore(redo.pop()); dirty(); };

  // ----- propriétés -----
  const colorIn = (id, v) => '<input type="color" class="form-control form-control-color form-control-sm w-100" data-p="' + id + '" value="' + v + '">';
  const numIn = (id, v, lo, hi) => '<input type="number" class="form-control form-control-sm" data-p="' + id + '" value="' + Math.round(v * 10) / 10 + '" min="' + lo + '" max="' + hi + '">';
  function drawProps() {
    const p = $('sl-props'), e = selEl(), s = D.slides[cur]; let h = '';
    if (!e) h = '<h6>Diapositive ' + (cur + 1) + '</h6><label>Couleur de fond</label><input type="color" class="form-control form-control-color form-control-sm w-100" data-s="bg" value="' + s.bg + '"><label class="mt-2">Notes du présentateur</label><textarea class="form-control form-control-sm" rows="5" data-s="notes" maxlength="3000">' + esc(s.notes) + '</textarea><div class="small text-muted mt-2">Sélectionnez un élément pour modifier ses propriétés.</div>';
    else {
      h = '<h6>' + ({ text: 'Texte', img: 'Image', shape: 'Forme', table: 'Tableau', chart: 'Graphique' }[e.t]) + '</h6><div class="row"><div class="col-6"><label>X</label>' + numIn('x', e.x, -W, 2 * W) + '</div><div class="col-6"><label>Y</label>' + numIn('y', e.y, -H, 2 * H) + '</div><div class="col-6"><label>Largeur</label>' + numIn('w', e.w, 10, 2 * W) + '</div><div class="col-6"><label>Hauteur</label>' + numIn('h', e.h, 4, 2 * H) + '</div></div>';
      if (e.t === 'text') h += '<h6>Texte</h6><div class="row"><div class="col-6"><label>Taille (px)</label>' + numIn('size', e.size, 6, 200) + '</div><div class="col-6"><label>Couleur</label>' + colorIn('color', e.color) + '</div><div class="col-12"><label>Police</label><select class="form-select form-select-sm" data-p="font">' + FONTS.map(f => '<option' + (f === e.font ? ' selected' : '') + '>' + f + '</option>').join('') + '</select></div></div>'
        + '<div class="btn-group btn-group-sm mt-2 w-100"><button type="button" class="btn btn-outline-secondary' + (e.b ? ' active' : '') + '" data-tg="b"><b>G</b></button><button type="button" class="btn btn-outline-secondary' + (e.i ? ' active' : '') + '" data-tg="i"><i>I</i></button><button type="button" class="btn btn-outline-secondary' + (e.bul ? ' active' : '') + '" data-tg="bul" title="Puces"><i class="fas fa-list-ul"></i></button></div>'
        + '<div class="btn-group btn-group-sm mt-1 w-100">' + ['left', 'center', 'right'].map(a => '<button type="button" class="btn btn-outline-secondary' + (e.al === a ? ' active' : '') + '" data-al="' + a + '"><i class="fas fa-align-' + a + '"></i></button>').join('') + '</div><label class="mt-2">Contenu</label><textarea class="form-control form-control-sm" rows="4" data-p="text" maxlength="5000">' + esc(e.text) + '</textarea>';
      if (e.t === 'shape') h += '<h6>Forme</h6><div class="row"><div class="col-6"><label>Remplissage</label>' + colorIn('fill', okC(e.fill) ? e.fill : '#ffffff') + '</div><div class="col-6"><label>Contour</label>' + colorIn('stroke', okC(e.stroke) ? e.stroke : '#000000') + '</div><div class="col-6"><label>Épaisseur</label>' + numIn('sw', e.sw, 0, 30) + '</div></div><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="sl-nofill" data-nf' + (e.fill === '' ? ' checked' : '') + '><label class="form-check-label" for="sl-nofill">Sans remplissage</label></div><div class="form-check"><input class="form-check-input" type="checkbox" id="sl-nostroke" data-ns' + (e.stroke === '' ? ' checked' : '') + '><label class="form-check-label" for="sl-nostroke">Sans contour</label></div>';
      if (e.t === 'table') h += '<h6>Tableau</h6><div class="sl-grid" style="overflow:auto"><table>' + e.rows.map((r, i) => '<tr>' + r.map((c, j) => '<td><input data-cell="' + i + ',' + j + '" value="' + esc(c) + '" maxlength="200"></td>').join('') + '</tr>').join('') + '</table></div><div class="btn-group btn-group-sm mt-2 w-100"><button type="button" class="btn btn-outline-secondary" data-tb="ar">+ ligne</button><button type="button" class="btn btn-outline-secondary" data-tb="dr">− ligne</button><button type="button" class="btn btn-outline-secondary" data-tb="ac">+ col.</button><button type="button" class="btn btn-outline-secondary" data-tb="dc">− col.</button></div><div class="row mt-2"><div class="col-6"><label>Taille</label>' + numIn('size', e.size, 6, 80) + '</div><div class="col-6"><label>En-tête</label>' + colorIn('hfill', e.hfill) + '</div></div><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="sl-hdr" data-hdr' + (e.hdr ? ' checked' : '') + '><label class="form-check-label" for="sl-hdr">Première ligne = en-tête</label></div>';
      if (e.t === 'chart') h += '<h6>Graphique</h6><select class="form-select form-select-sm mb-2" data-p="type"><option value="bar"' + (e.type === 'bar' ? ' selected' : '') + '>Histogramme</option><option value="line"' + (e.type === 'line' ? ' selected' : '') + '>Courbe</option><option value="pie"' + (e.type === 'pie' ? ' selected' : '') + '>Secteurs</option></select><div class="sl-grid" style="overflow:auto"><table><tr><td></td>' + e.series.map((se, j) => '<td><input data-cs="' + j + '" value="' + esc(se.name) + '" maxlength="40" aria-label="Nom de la série"></td>').join('') + '</tr>' + e.labels.map((l, i) => '<tr><td><input data-cl="' + i + '" value="' + esc(l) + '" maxlength="40" aria-label="Catégorie"></td>' + e.series.map((se, j) => '<td><input type="number" step="any" data-cv="' + j + ',' + i + '" value="' + se.values[i] + '"></td>').join('') + '</tr>').join('') + '</table></div><div class="btn-group btn-group-sm mt-2 w-100"><button type="button" class="btn btn-outline-secondary" data-ch="ar">+ catégorie</button><button type="button" class="btn btn-outline-secondary" data-ch="dr">−</button><button type="button" class="btn btn-outline-secondary" data-ch="as">+ série</button><button type="button" class="btn btn-outline-secondary" data-ch="ds">−</button></div>';
      h += '<div class="btn-group btn-group-sm mt-3 w-100"><button type="button" class="btn btn-outline-secondary" data-z="front" title="Mettre au premier plan"><i class="fas fa-arrow-up-wide-short"></i></button><button type="button" class="btn btn-outline-secondary" data-z="back" title="Mettre en arrière-plan"><i class="fas fa-arrow-down-wide-short"></i></button><button type="button" class="btn btn-outline-secondary" data-z="dup" title="Dupliquer"><i class="fas fa-clone"></i></button><button type="button" class="btn btn-outline-danger" data-z="del" title="Supprimer"><i class="fas fa-trash"></i></button></div>';
    }
    p.innerHTML = h;
  }
  const propsEl = $('sl-props');
  function mutate(fn, full) { push(); fn(); dirty(); if (full) { drawCanvas(); drawThumbs(); drawProps(); } else redrawKeep(); }
  let pt = null; // regroupe les frappes d'un même champ en une seule étape d'annulation
  const mutateTyped = (key, fn) => { if (pt !== key) { push(); pt = key; } fn(); dirty(); redrawKeep(); clearTimeout(mutateTyped.t); mutateTyped.t = setTimeout(() => { pt = null; }, 1200); };
  propsEl.addEventListener('input', ev => {
    const t = ev.target, e = selEl(), s = D.slides[cur];
    if (t.dataset.s) return mutateTyped('s' + t.dataset.s, () => { s[t.dataset.s] = t.value; });
    if (!e) return;
    if (t.dataset.p) { const k = t.dataset.p; return mutateTyped(e.id + k, () => { if (['x', 'y', 'w', 'h', 'size', 'sw'].includes(k)) e[k] = num(t.value, e[k], -2 * W, 2 * W); else if (['color', 'fill', 'stroke', 'hfill'].includes(k)) e[k] = t.value; else e[k] = t.value; drawSel(); }); }
    if (t.dataset.cell) { const [i, j] = t.dataset.cell.split(',').map(Number); return mutateTyped(e.id + 'c' + i + j, () => { e.rows[i][j] = t.value; }); }
    if (t.dataset.cs != null) return mutateTyped(e.id + 'cs' + t.dataset.cs, () => { e.series[+t.dataset.cs].name = t.value; });
    if (t.dataset.cl != null) return mutateTyped(e.id + 'cl' + t.dataset.cl, () => { e.labels[+t.dataset.cl] = t.value; });
    if (t.dataset.cv != null) { const [j, i] = t.dataset.cv.split(',').map(Number); return mutateTyped(e.id + 'cv' + j + i, () => { e.series[j].values[i] = num(t.value, 0, -1e12, 1e12); }); }
  });
  propsEl.addEventListener('change', ev => { const t = ev.target, e = selEl(); if (!e) return;
    if (t.dataset.p === 'type') mutate(() => { e.type = t.value; }, true);
    if (t.dataset.nf != null) mutate(() => { e.fill = t.checked ? '' : '#c40025'; }, true);
    if (t.dataset.ns != null) mutate(() => { e.stroke = t.checked ? '' : '#444444'; }, true);
    if (t.dataset.hdr != null) mutate(() => { e.hdr = t.checked; }, true); });
  propsEl.addEventListener('click', ev => {
    const b = ev.target.closest('button'); if (!b) return; const e = selEl(); if (!e) return;
    if (b.dataset.tg) return mutate(() => { e[b.dataset.tg] = !e[b.dataset.tg]; }, true);
    if (b.dataset.al) return mutate(() => { e.al = b.dataset.al; }, true);
    if (b.dataset.tb) return mutate(() => { const n = e.rows[0].length; if (b.dataset.tb === 'ar' && e.rows.length < 30) e.rows.push(Array(n).fill('')); if (b.dataset.tb === 'dr' && e.rows.length > 1) e.rows.pop(); if (b.dataset.tb === 'ac' && n < 12) e.rows.forEach(r => r.push('')); if (b.dataset.tb === 'dc' && n > 1) e.rows.forEach(r => r.pop()); }, true);
    if (b.dataset.ch) return mutate(() => { const a = b.dataset.ch; if (a === 'ar' && e.labels.length < 24) { e.labels.push('Cat. ' + (e.labels.length + 1)); e.series.forEach(s => s.values.push(0)); } if (a === 'dr' && e.labels.length > 1) { e.labels.pop(); e.series.forEach(s => s.values.pop()); } if (a === 'as' && e.series.length < 6) e.series.push({ name: 'Série ' + (e.series.length + 1), values: e.labels.map(() => 0) }); if (a === 'ds' && e.series.length > 1) e.series.pop(); }, true);
    if (b.dataset.z) zop(b.dataset.z);
  });
  function zop(z) { const s = D.slides[cur], i = s.els.findIndex(x => x.id === selId); if (i < 0) return; push(); dirty();
    if (z === 'front') s.els.push(s.els.splice(i, 1)[0]); else if (z === 'back') s.els.unshift(s.els.splice(i, 1)[0]);
    else if (z === 'dup') { const c = JSON.parse(JSON.stringify(s.els[i])); c.id = uid(); c.x += 20; c.y += 20; s.els.push(c); selId = c.id; } else if (z === 'del') { s.els.splice(i, 1); selId = null; }
    refreshAll(); }

  // ----- interaction sur la diapositive -----
  const canvas = $('sl-canvas');
  canvas.addEventListener('mousedown', ev => {
    if (editing) { if (!ev.target.closest('.sl-ed')) endEdit(); else return; }
    const hd = ev.target.closest('[data-h]'), e0 = selEl();
    if (hd && e0) { startDrag(ev, 'resize', e0, hd.dataset.h); return; }
    const el = ev.target.closest('.sl-el');
    if (el) { selId = el.dataset.id; drawSel(); drawProps(); startDrag(ev, 'move', selEl()); } else { selId = null; drawSel(); drawProps(); }
    ev.preventDefault(); canvas.focus && canvas.setAttribute('tabindex', '0') ; canvas.focus();
  });
  function startDrag(ev, mode, e, h) {
    ev.preventDefault(); const x0 = ev.clientX, y0 = ev.clientY, o = { x: e.x, y: e.y, w: e.w, h: e.h }; let moved = false;
    const mv = m => { const dx = (m.clientX - x0) / sc, dy = (m.clientY - y0) / sc; if (!moved && Math.abs(dx) + Math.abs(dy) < 2) return; if (!moved) { push(); moved = true; }
      if (mode === 'move') { e.x = Math.round(o.x + dx); e.y = Math.round(o.y + dy); } else { let { x, y, w, h: hh } = o; if (h.includes('e')) w = Math.max(10, o.w + dx); if (h.includes('s')) hh = Math.max(4, o.h + dy); if (h.includes('w')) { w = Math.max(10, o.w - dx); x = o.x + o.w - w; } if (h.includes('n')) { hh = Math.max(4, o.h - dy); y = o.y + o.h - hh; } Object.assign(e, { x: Math.round(x), y: Math.round(y), w: Math.round(w), h: Math.round(hh) }); }
      drawCanvas(); };
    const up = () => { document.removeEventListener('mousemove', mv); document.removeEventListener('mouseup', up); if (moved) { dirty(); drawThumbs(); drawProps(); } };
    document.addEventListener('mousemove', mv); document.addEventListener('mouseup', up);
  }
  canvas.addEventListener('dblclick', ev => { const el = ev.target.closest('.sl-el'); if (!el) return; const e = D.slides[cur].els.find(x => x.id === el.dataset.id); if (e && e.t === 'text') startEdit(e); });
  function startEdit(e) {
    selId = e.id; drawSel(); const ta = document.createElement('textarea'); ta.className = 'sl-ed'; ta.value = e.text; ta.style.cssText = 'left:' + e.x * sc + 'px;top:' + e.y * sc + 'px;width:' + e.w * sc + 'px;height:' + e.h * sc + 'px;font-size:' + e.size * sc + 'px;font-family:' + e.font + ';color:' + e.color + ';text-align:' + e.al + ';' + (e.b ? 'font-weight:700;' : '') + (e.i ? 'font-style:italic;' : '');
    canvas.appendChild(ta); editing = { ta, e }; ta.focus(); ta.addEventListener('keydown', k => { if (k.key === 'Escape') { k.preventDefault(); endEdit(); } k.stopPropagation(); }); ta.addEventListener('blur', () => setTimeout(endEdit, 0)); $('sl-ov').innerHTML = '';
  }
  function endEdit() { if (!editing) return; const { ta, e } = editing; editing = null; const v = ta.value.slice(0, 5000); ta.remove(); if (v !== e.text) { push(); e.text = v; dirty(); } drawCanvas(); drawThumbs(); drawProps(); }
  document.addEventListener('keydown', ev => {
    if (editing || !document.getElementById('sl-canvas') || ['INPUT', 'TEXTAREA', 'SELECT'].includes((ev.target || {}).tagName)) return; if ($('sl-pres').classList.contains('on')) return;
    const ctrl = ev.ctrlKey || ev.metaKey, k = ev.key, e = selEl(), l = k.toLowerCase();
    if (ctrl && l === 'z') { ev.preventDefault(); doUndo(); } else if (ctrl && l === 'y') { ev.preventDefault(); doRedo(); }
    else if (ctrl && l === 'd' && e) { ev.preventDefault(); zop('dup'); } else if (ctrl && l === 'c' && e) { clip = JSON.parse(JSON.stringify(e)); }
    else if (ctrl && l === 'v' && clip) { ev.preventDefault(); push(); const c = JSON.parse(JSON.stringify(clip)); c.id = uid(); c.x += 20; c.y += 20; clip.x += 20; clip.y += 20; D.slides[cur].els.push(c); selId = c.id; dirty(); refreshAll(); }
    else if ((k === 'Delete' || k === 'Backspace') && e) { ev.preventDefault(); zop('del'); }
    else if (e && k.startsWith('Arrow')) { ev.preventDefault(); const st = ev.shiftKey ? 10 : 1; push(); if (k === 'ArrowLeft') e.x -= st; if (k === 'ArrowRight') e.x += st; if (k === 'ArrowUp') e.y -= st; if (k === 'ArrowDown') e.y += st; dirty(); drawCanvas(); drawProps(); }
    else if (!e && k === 'ArrowRight') { if (cur < D.slides.length - 1) { cur++; selId = null; refreshAll(); } } else if (!e && k === 'ArrowLeft') { if (cur > 0) { cur--; selId = null; refreshAll(); } }
  });

  // ----- barre d'outils -----
  $('sl-layouts').innerHTML = [['title', 'Titre'], ['content', 'Titre et puces'], ['two', 'Deux colonnes'], ['titleonly', 'Titre seul'], ['blank', 'Vide']].map(a => '<li><button type="button" class="dropdown-item" data-lay="' + a[0] + '">' + a[1] + '</button></li>').join('');
  $('sl-theme').innerHTML = Object.keys(THEMES).map(k => '<option value="' + k + '">Thème : ' + THEMES[k].nom + '</option>').join('');
  $('sl-layouts').addEventListener('click', ev => { const b = ev.target.closest('[data-lay]'); if (!b || D.slides.length >= MAXS) return; push(); D.slides.splice(cur + 1, 0, layout(b.dataset.lay)); cur++; selId = null; dirty(); refreshAll(); });
  $('sl-thumbs').addEventListener('click', ev => { const t = ev.target.closest('[data-i]'); if (!t) return; endEdit(); cur = +t.dataset.i; selId = null; refreshAll(); });
  $('sl-theme').addEventListener('change', ev => { const old = th(), nw = THEMES[ev.target.value]; push(); const map = { [old.bg]: nw.bg, [old.title]: nw.title, [old.text]: nw.text, [old.accent]: nw.accent };
    D.theme = ev.target.value; D.slides.forEach(s => { if (s.bg === old.bg) s.bg = nw.bg; s.els.forEach(e => { ['color', 'fill', 'hfill', 'stroke'].forEach(k => { if (e[k] && map[e[k]]) e[k] = map[e[k]]; }); }); }); dirty(); refreshAll(); });
  $('sl-trans').addEventListener('change', ev => { D.trans = ev.target.value; dirty(); });
  $('sl-img').addEventListener('change', async ev => { const f = ev.target.files[0]; ev.target.value = ''; if (!f) return; if (f.size > 12 * 1048576) { alert('Image trop volumineuse (12 Mo maximum).'); return; }
    try { const src = await shrink(f); push(); const im = new Image(); im.src = src; await im.decode(); const r = Math.min(600 / im.width, 400 / im.height, 1); const e = { id: uid(), t: 'img', x: 100, y: 80, w: Math.round(im.width * r), h: Math.round(im.height * r), src }; D.slides[cur].els.push(e); selId = e.id; dirty(); refreshAll(); } catch (er) { alert('Cette image n\'a pas pu être ajoutée.'); } });
  function shrink(f) { return new Promise((ok, ko) => { const im = new Image(), u = URL.createObjectURL(f); im.onload = () => { URL.revokeObjectURL(u); const k = Math.min(1, 1600 / Math.max(im.width, im.height)), c = document.createElement('canvas'); c.width = Math.round(im.width * k); c.height = Math.round(im.height * k); c.getContext('2d').drawImage(im, 0, 0, c.width, c.height); const png = f.type === 'image/png' || f.type === 'image/gif'; let d = c.toDataURL(png ? 'image/png' : 'image/jpeg', .85); if (png && d.length > 700000) d = c.toDataURL('image/jpeg', .85); ok(d); }; im.onerror = ko; im.src = u; }); }
  function addEl(kind) {
    if (D.slides[cur].els.length >= MAXE) { alert('Cette diapositive contient déjà le maximum d\'éléments.'); return; } const T = th(); let e;
    if (kind === 'img') return $('sl-img').click();
    if (kind === 'text') e = mkText(120, 200, 400, 80, 'Votre texte');
    else if (kind === 'rect' || kind === 'ellipse') e = { id: uid(), t: 'shape', shape: kind, x: 200, y: 180, w: 260, h: 160, fill: T.accent, stroke: '', sw: 2 };
    else if (kind === 'line') e = { id: uid(), t: 'shape', shape: 'line', x: 200, y: 260, w: 300, h: 4, fill: '', stroke: T.accent, sw: 4 };
    else if (kind === 'table') e = { id: uid(), t: 'table', x: 100, y: 150, w: 600, h: 180, rows: [['Libellé', 'Valeur'], ['Ligne 1', '0'], ['Ligne 2', '0']], hdr: true, size: 20, hfill: T.accent, color: T.text };
    else if (kind === 'chart') e = { id: uid(), t: 'chart', x: 120, y: 120, w: 520, h: 320, type: 'bar', labels: ['T1', 'T2', 'T3', 'T4'], series: [{ name: 'Série 1', values: [12, 19, 8, 15] }], color: T.text };
    push(); D.slides[cur].els.push(e); selId = e.id; dirty(); refreshAll();
  }
  document.querySelector('.sl-tb').addEventListener('click', ev => { const b = ev.target.closest('button'); if (!b) return; if (b.dataset.add) addEl(b.dataset.add); else if (b.dataset.act) act(b.dataset.act); });
  document.querySelector('.sl-panel').addEventListener('click', ev => { const b = ev.target.closest('[data-act]'); if (b) act(b.dataset.act); });
  function act(a) {
    if (a === 'undo') return doUndo(); if (a === 'redo') return doRedo(); if (a === 'present') return present(0); if (a === 'print') return window.print(); if (a === 'pptx') return exportPptx();
    if (a === 'dup') { if (D.slides.length >= MAXS) return; push(); D.slides.splice(cur + 1, 0, normalize({ slides: [JSON.parse(JSON.stringify(D.slides[cur]))] }).slides[0]); D.slides[cur + 1].els.forEach(e => e.id = uid()); cur++; selId = null; }
    else if (a === 'delslide') { if (D.slides.length < 2) { alert('Une présentation contient au moins une diapositive.'); return; } if (!confirm('Supprimer cette diapositive ?')) return; push(); D.slides.splice(cur, 1); cur = Math.min(cur, D.slides.length - 1); selId = null; }
    else if (a === 'up') { if (cur < 1) return; push(); D.slides.splice(cur - 1, 0, D.slides.splice(cur, 1)[0]); cur--; }
    else if (a === 'down') { if (cur >= D.slides.length - 1) return; push(); D.slides.splice(cur + 1, 0, D.slides.splice(cur, 1)[0]); cur++; }
    dirty(); refreshAll();
  }

  // ----- présentation -----
  let pi = 0;
  function showPres(back) { const s = D.slides[pi], pb = $('sl-pbox'), k = Math.min(innerWidth / W, innerHeight / H); box(pb, s, W * k); const sl = pb.firstChild; if (D.trans !== 'none') { sl.style.setProperty('--d', (back ? -60 : 60) + 'px'); sl.classList.add(D.trans + '-in'); } $('sl-pctl').textContent = (pi + 1) + ' / ' + D.slides.length + ' — ← → pour naviguer, Échap pour quitter'; }
  function present(from) { endEdit(); pi = from; $('sl-pres').classList.add('on'); showPres(); try { $('sl-pres').requestFullscreen && $('sl-pres').requestFullscreen(); } catch (e) {} }
  function leave() { $('sl-pres').classList.remove('on'); if (document.fullscreenElement) document.exitFullscreen().catch(() => {}); }
  const go = d => { const n = pi + d; if (n < 0) return; if (n >= D.slides.length) return leave(); pi = n; showPres(d < 0); };
  $('sl-pres').addEventListener('click', e => { go(e.clientX < innerWidth * .2 ? -1 : 1); });
  document.addEventListener('keydown', e => { if (!$('sl-pres').classList.contains('on')) return; if (['ArrowRight', 'ArrowDown', 'PageDown', ' ', 'Enter'].includes(e.key)) { e.preventDefault(); go(1); } else if (['ArrowLeft', 'ArrowUp', 'PageUp', 'Backspace'].includes(e.key)) { e.preventDefault(); go(-1); } else if (e.key === 'Escape') leave(); else if (e.key === 'Home') { pi = 0; showPres(); } else if (e.key === 'End') { pi = D.slides.length - 1; showPres(); } });
  document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement && $('sl-pres').classList.contains('on')) leave(); });
  addEventListener('resize', () => { if ($('sl-pres').classList.contains('on')) showPres(); else { fitCanvas(); drawCanvas(); } });
  let t0; const touch = {}; $('sl-pres').addEventListener('touchstart', e => { touch.x = e.touches[0].clientX; }, { passive: true }); $('sl-pres').addEventListener('touchend', e => { const dx = e.changedTouches[0].clientX - touch.x; if (Math.abs(dx) > 50) { e.preventDefault(); go(dx < 0 ? 1 : -1); } });

  // ----- PDF (impression) et PowerPoint -----
  addEventListener('beforeprint', () => { const p = $('sl-print'); p.innerHTML = D.slides.map((s, i) => '<div class="sl-pg"><div class="sl-box" data-pr="' + i + '"></div></div>').join(''); p.querySelectorAll('[data-pr]').forEach(b => { box(b, D.slides[+b.dataset.pr], 1047); b.style.width = '277mm'; b.style.height = '155.8mm'; b.firstChild.style.transform = 'scale(' + (1047 / W) + ')'; }); });
  addEventListener('afterprint', () => { $('sl-print').innerHTML = ''; });
  const PPTX_SRC = 'https://cdn.jsdelivr.net/npm/pptxgenjs@3.12.0/dist/pptxgen.bundle.js';
  const loadLib = () => new Promise((ok, ko) => { if (window.PptxGenJS) return ok(); const s = document.createElement('script'); s.src = PPTX_SRC; s.onload = ok; s.onerror = () => ko(); document.head.appendChild(s); });
  async function exportPptx() {
    try { await loadLib(); } catch (e) { alert('La bibliothèque d\'export PowerPoint n\'a pas pu être chargée (connexion ou blocage du réseau). Utilisez « PDF / Imprimer » en attendant.'); return; }
    const pptx = new PptxGenJS(); pptx.layout = 'LAYOUT_16x9'; pptx.title = DocIO.nom(); const inch = v => v / 96, hex = c => String(c || '#000000').replace('#', '').toUpperCase(), P = PAL(th().accent).map(hex);
    D.slides.forEach(s => { const sl = pptx.addSlide(); sl.background = { color: hex(s.bg) }; if (s.notes) sl.addNotes(s.notes);
      s.els.forEach(e => { const g = { x: inch(e.x), y: inch(e.y), w: inch(e.w), h: inch(e.h) };
        if (e.t === 'text') { const lines = e.text.split('\n'); sl.addText(e.bul ? lines.map(l => ({ text: l, options: { bullet: true, breakLine: true } })) : e.text, Object.assign({}, g, { fontSize: Math.round(e.size * 0.75 * 10) / 10, bold: e.b, italic: e.i, color: hex(e.color), align: e.al, valign: 'top', fontFace: e.font, margin: 0, fit: 'none' })); }
        else if (e.t === 'img') sl.addImage({ data: e.src, x: g.x, y: g.y, w: g.w, h: g.h, sizing: { type: 'contain', w: g.w, h: g.h } });
        else if (e.t === 'shape') { const o = { x: g.x, y: g.y, w: g.w, h: g.h, fill: e.fill ? { color: hex(e.fill) } : { type: 'none' }, line: e.stroke || e.shape === 'line' ? { color: hex(e.stroke || e.fill), width: Math.max(0.5, e.sw * 0.75) } : { type: 'none' } }; sl.addShape(e.shape === 'ellipse' ? pptx.ShapeType.ellipse : e.shape === 'line' ? pptx.ShapeType.line : pptx.ShapeType.rect, o); }
        else if (e.t === 'table') sl.addTable(e.rows.map((r, i) => r.map(c => ({ text: c, options: i === 0 && e.hdr ? { bold: true, color: 'FFFFFF', fill: { color: hex(e.hfill) } } : { color: hex(e.color) } }))), { x: g.x, y: g.y, w: g.w, fontSize: Math.round(e.size * 0.75), border: { type: 'solid', pt: 0.75, color: '9AA3AF' }, valign: 'middle' });
        else if (e.t === 'chart') { const T = pptx.ChartType, ty = e.type === 'pie' ? T.pie : e.type === 'line' ? T.line : T.bar; const ser = (e.type === 'pie' ? [e.series[0]] : e.series).map(se => ({ name: se.name, labels: e.labels, values: se.values }));
          sl.addChart(ty, ser, { x: g.x, y: g.y, w: g.w, h: g.h, chartColors: e.type === 'pie' ? P : P.slice(0, ser.length), showLegend: e.type === 'pie' || ser.length > 1, legendPos: 'b', catAxisLabelColor: hex(e.color), valAxisLabelColor: hex(e.color), dataLabelColor: hex(e.color) }); } }); });
    try { await pptx.writeFile({ fileName: DocIO.safeName() + '.pptx' }); } catch (e) { alert('Export impossible : ' + (e && e.message || e)); }
  }

  const api = { get: () => D, set: d => { D = normalize(d); cur = 0; selId = null; undo = []; redo = []; refreshAll(); }, blank };
  window.SlidesDoc = { api, getData: () => D, THEMES };
  refreshAll(); DocIO.init(api); new ResizeObserver(() => { fitCanvas(); drawCanvas(); }).observe($('sl-stage'));
})();
</script>
