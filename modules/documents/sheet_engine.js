/* Moteur de tableur : analyse et calcul des formules (noms de fonctions français ou anglais, arguments séparés par « ; »). */
const SheetEngine = (function () {
  'use strict';
  class CellErr { constructor(code) { this.code = code; } toString() { return this.code; } }
  const E = c => new CellErr(c);
  const colName = n => { let s = ''; n++; while (n > 0) { const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
  const colIndex = s => { let n = 0; for (const ch of s) n = n * 26 + ch.charCodeAt(0) - 64; return n - 1; };
  const addr = (c, r) => colName(c) + (r + 1);
  const parseAddr = a => { const m = /^\$?([A-Z]{1,3})\$?(\d{1,6})$/.exec(a); return m ? { c: colIndex(m[1]), r: +m[2] - 1 } : null; };

  // ----- noms de fonctions : français -> nom canonique anglais -----
  const FR = { 'SOMME': 'SUM', 'MOYENNE': 'AVERAGE', 'NB': 'COUNT', 'NBVAL': 'COUNTA', 'SI': 'IF', 'ET': 'AND', 'OU': 'OR', 'NON': 'NOT', 'ARRONDI': 'ROUND', 'ARRONDI.SUP': 'ROUNDUP', 'ARRONDI.INF': 'ROUNDDOWN',
    'ENT': 'INT', 'RACINE': 'SQRT', 'PUISSANCE': 'POWER', 'SOMME.SI': 'SUMIF', 'NB.SI': 'COUNTIF', 'CONCATENER': 'CONCAT', 'GAUCHE': 'LEFT', 'DROITE': 'RIGHT', 'STXT': 'MID', 'NBCAR': 'LEN',
    'MAJUSCULE': 'UPPER', 'MINUSCULE': 'LOWER', 'SIERREUR': 'IFERROR', 'AUJOURDHUI': 'TODAY', 'MAINTENANT': 'NOW', 'VPM': 'PMT', 'VA': 'PV', 'VC': 'FV', 'RECHERCHEV': 'VLOOKUP', 'MEDIANE': 'MEDIAN',
    'PRODUIT': 'PRODUCT', 'VRAI': 'TRUE', 'FAUX': 'FALSE', 'SUPPRESPACE': 'TRIM', 'ANNEE': 'YEAR', 'MOIS': 'MONTH', 'JOUR': 'DAY', 'SIGNE': 'SIGN', 'MOYENNE.SI': 'AVERAGEIF', 'NB.VIDE': 'COUNTBLANK' };
  const EN2FR = {}; Object.keys(FR).forEach(k => { if (!['VRAI', 'FAUX'].includes(k)) EN2FR[FR[k]] = k; });
  const canon = n => { n = n.toUpperCase(); return FR[n] || n; };

  // ----- analyse syntaxique -----
  const astCache = new Map();
  function tokenize(src) {
    const t = []; let i = 0;
    while (i < src.length) {
      const ch = src[i];
      if (/\s/.test(ch)) { i++; continue; }
      if (ch === '"') { let j = i + 1, s = ''; while (j < src.length) { if (src[j] === '"') { if (src[j + 1] === '"') { s += '"'; j += 2; continue; } break; } s += src[j++]; } if (j >= src.length) throw E('#NOM?'); t.push({ k: 'str', v: s }); i = j + 1; continue; }
      if (ch === "'") { const j = src.indexOf("'", i + 1); if (j < 0) throw E('#NOM?'); t.push({ k: 'sheet', v: src.slice(i + 1, j) }); i = j + 1; if (src[i] === '!') i++; continue; }
      let m;
      if ((m = /^(?:\d+(?:[.,]\d+)?|[.]\d+)(?:[eE][+-]?\d+)?/.exec(src.slice(i)))) { t.push({ k: 'num', v: parseFloat(m[0].replace(',', '.')) }); i += m[0].length; continue; }
      if ((m = /^[A-Za-zÀ-ÿ_\$][A-Za-zÀ-ÿ0-9_.\$]*/.exec(src.slice(i)))) { t.push({ k: 'id', v: m[0] }); i += m[0].length; if (src[i] === '!') { t[t.length - 1].k = 'sheet'; i++; } continue; }
      if ((m = /^(<>|<=|>=|[-+*\/^&=<>%();:])/.exec(src.slice(i)))) { t.push({ k: 'op', v: m[0] }); i += m[0].length; continue; }
      throw E('#NOM?');
    }
    return t;
  }
  function parse(src) {
    if (astCache.has(src)) { const c = astCache.get(src); if (c instanceof CellErr) throw c; return c; }
    let ast;
    try { ast = parseTokens(tokenize(src)); } catch (e) { if (e instanceof CellErr) { astCache.set(src, e); throw e; } astCache.set(src, E('#NOM?')); throw E('#NOM?'); }
    if (astCache.size > 5000) astCache.clear();
    astCache.set(src, ast); return ast;
  }
  function parseTokens(t) {
    let p = 0; const peek = () => t[p], next = () => t[p++], isOp = v => t[p] && t[p].k === 'op' && t[p].v === v;
    function cmp() { let a = cat(); while (t[p] && t[p].k === 'op' && ['=', '<>', '<', '>', '<=', '>='].includes(t[p].v)) { const op = next().v; a = { t: 'bin', op, a, b: cat() }; } return a; }
    function cat() { let a = add(); while (isOp('&')) { next(); a = { t: 'bin', op: '&', a, b: add() }; } return a; }
    function add() { let a = mul(); while (isOp('+') || isOp('-')) { const op = next().v; a = { t: 'bin', op, a, b: mul() }; } return a; }
    function mul() { let a = pow(); while (isOp('*') || isOp('/')) { const op = next().v; a = { t: 'bin', op, a, b: pow() }; } return a; }
    function pow() { let a = unary(); while (isOp('^')) { next(); a = { t: 'bin', op: '^', a, b: unary() }; } return a; }
    function unary() { if (isOp('-')) { next(); return { t: 'un', op: '-', a: unary() }; } if (isOp('+')) { next(); return unary(); } return post(); }
    function post() { let a = prim(); while (isOp('%')) { next(); a = { t: 'bin', op: '/', a, b: { t: 'num', v: 100 } }; } return a; }
    function prim() {
      const k = next(); if (!k) throw E('#NOM?');
      if (k.k === 'num') return { t: 'num', v: k.v };
      if (k.k === 'str') return { t: 'str', v: k.v };
      if (k.k === 'op' && k.v === '(') { const e = cmp(); if (!isOp(')')) throw E('#NOM?'); next(); return e; }
      let sheet = null, id = k;
      if (k.k === 'sheet') { sheet = k.v; id = next(); if (!id || id.k !== 'id') throw E('#NOM?'); }
      if (id.k !== 'id') throw E('#NOM?');
      if (isOp('(') && !sheet) { next(); const args = []; if (isOp(')')) { next(); return { t: 'call', name: canon(id.v), args }; }
        for (;;) { if (isOp(';') || isOp(')')) args.push({ t: 'empty' }); else args.push(cmp()); if (isOp(';')) { next(); continue; } if (isOp(')')) { next(); break; } throw E('#NOM?'); }
        return { t: 'call', name: canon(id.v), args }; }
      const a1 = parseAddr(id.v);
      if (a1) { if (isOp(':')) { next(); const b = next(); const a2 = b && b.k === 'id' ? parseAddr(b.v) : null; if (!a2) throw E('#NOM?'); return { t: 'range', sheet, c1: Math.min(a1.c, a2.c), r1: Math.min(a1.r, a2.r), c2: Math.max(a1.c, a2.c), r2: Math.max(a1.r, a2.r) }; } return { t: 'ref', sheet, c: a1.c, r: a1.r }; }
      const u = id.v.toUpperCase();
      if (u === 'VRAI' || u === 'TRUE') return { t: 'bool', v: true };
      if (u === 'FAUX' || u === 'FALSE') return { t: 'bool', v: false };
      throw E('#NOM?');
    }
    const r = cmp(); if (p < t.length) throw E('#NOM?'); return r;
  }

  // ----- valeurs -----
  const NUM_RE = /^\s*[-+]?\d+(?:[  ]?\d{3})*(?:[.,]\d+)?\s*(%|€)?\s*$|^\s*[-+]?[.,]\d+\s*(%|€)?\s*$/;
  function parseRaw(raw) { // texte saisi -> nombre | texte (hors formule)
    if (raw === '' || raw == null) return '';
    if (typeof raw === 'number') return raw;
    const m = NUM_RE.exec(raw);
    if (m) { const s = raw.replace(/[\s €%]/g, '').replace(',', '.'); const n = parseFloat(s); if (!isNaN(n)) return m[1] === '%' || m[2] === '%' ? n / 100 : n; }
    return raw;
  }
  const isErr = v => v instanceof CellErr;
  const toNum = v => { if (isErr(v)) throw v; if (typeof v === 'number') return v; if (v === '' || v == null) return 0; if (typeof v === 'boolean') return v ? 1 : 0; const n = parseRaw(v); if (typeof n === 'number') return n; throw E('#VALEUR!'); };
  const toStr = v => { if (isErr(v)) throw v; if (v == null) return ''; if (typeof v === 'boolean') return v ? 'VRAI' : 'FAUX'; if (typeof v === 'number') return fmtNum(v); return String(v); };
  const toBool = v => { if (isErr(v)) throw v; if (typeof v === 'boolean') return v; if (typeof v === 'number') return v !== 0; if (v === '' || v == null) return false; const u = String(v).toUpperCase(); if (u === 'VRAI' || u === 'TRUE') return true; if (u === 'FAUX' || u === 'FALSE') return false; throw E('#VALEUR!'); };
  const fmtNum = n => { if (!isFinite(n)) return '#NOMBRE!'; const r = Math.round(n * 1e10) / 1e10; return String(r).replace('.', ','); };

  // ----- calcul -----
  function Calc(data) {
    const memo = new Map(), stack = new Set();
    const sheetIdx = name => { if (name == null) return -1; const i = data.sheets.findIndex(s => s.name.toLowerCase() === String(name).toLowerCase()); if (i < 0) throw E('#REF!'); return i; };
    const rawOf = (si, c, r) => { const x = data.sheets[si].cells[addr(c, r)]; return x ? x.r : ''; };
    function cell(si, c, r) { // valeur calculée d'une cellule
      const key = si + '|' + c + '|' + r;
      if (memo.has(key)) { const v = memo.get(key); return v; }
      const raw = rawOf(si, c, r);
      let v;
      if (typeof raw === 'string' && raw[0] === '=' && raw.length > 1) {
        if (stack.has(key)) return E('#CYCLE!');
        stack.add(key);
        try { v = evalNode(parse(raw.slice(1)), si); if (Array.isArray(v)) v = v.length && v[0].length ? v[0][0] : ''; }
        catch (e) { if (e instanceof CellErr) v = e; else v = E('#VALEUR!'); }
        finally { stack.delete(key); }
      } else v = parseRaw(raw);
      memo.set(key, v); return v;
    }
    function rangeVals(n, si) { const s = n.sheet == null ? si : sheetIdx(n.sheet), rows = []; for (let r = n.r1; r <= n.r2; r++) { const row = []; for (let c = n.c1; c <= n.c2; c++) row.push(cell(s, c, r)); rows.push(row); } return rows; }
    const flat = (args, si) => { const out = []; for (const a of args) { const v = a.t === 'range' ? rangeVals(a, si) : evalNode(a, si); if (Array.isArray(v)) v.forEach(row => row.forEach(x => out.push({ v: x, rng: true }))); else out.push({ v, rng: false }); } return out; };
    const nums = (args, si) => { const o = []; for (const { v, rng } of flat(args, si)) { if (isErr(v)) throw v; if (typeof v === 'number') o.push(v); else if (!rng && typeof v === 'boolean') o.push(v ? 1 : 0); else if (!rng && v !== '' && v != null) o.push(toNum(v)); } return o; };
    const cmpVals = (a, b) => { if (typeof a === 'string' && typeof b === 'string') { const x = a.toLowerCase(), y = b.toLowerCase(); return x < y ? -1 : x > y ? 1 : 0; } if (typeof a === 'string' && typeof b !== 'string') return 1; if (typeof b === 'string' && typeof a !== 'string') return -1; const x = toNum(a), y = toNum(b); return x < y ? -1 : x > y ? 1 : 0; };
    const critTest = crit => { let op = '=', val = crit; if (typeof crit === 'string') { const m = /^(<>|<=|>=|=|<|>)(.*)$/.exec(crit); if (m) { op = m[1]; val = parseRaw(m[2]); } else val = parseRaw(crit); }
      return x => { if (isErr(x)) return false; let c; if (typeof val === 'number' && typeof x === 'number') c = x < val ? -1 : x > val ? 1 : 0; else if (typeof val === 'string' || typeof x === 'string') { const a = String(x == null ? '' : x).toLowerCase(), b = String(val).toLowerCase(); c = a < b ? -1 : a > b ? 1 : 0; if (typeof x !== typeof val) c = c === 0 ? 0 : (op === '<>' ? 1 : 2); } else c = 0;
        switch (op) { case '=': return c === 0; case '<>': return c !== 0; case '<': return c === -1; case '>': return c === 1; case '<=': return c === -1 || c === 0; case '>=': return c === 1 || c === 0; } return false; }; };
    const sheetDays = () => Math.floor((Date.now() - new Date(1899, 11, 30).getTime()) / 864e5);
    const FN = {
      SUM: (a, si) => nums(a, si).reduce((x, y) => x + y, 0), PRODUCT: (a, si) => nums(a, si).reduce((x, y) => x * y, 1),
      AVERAGE: (a, si) => { const n = nums(a, si); if (!n.length) throw E('#DIV/0!'); return n.reduce((x, y) => x + y, 0) / n.length; },
      MIN: (a, si) => { const n = nums(a, si); return n.length ? Math.min(...n) : 0; }, MAX: (a, si) => { const n = nums(a, si); return n.length ? Math.max(...n) : 0; },
      MEDIAN: (a, si) => { const n = nums(a, si).sort((x, y) => x - y); if (!n.length) throw E('#NOMBRE!'); const m = n.length >> 1; return n.length % 2 ? n[m] : (n[m - 1] + n[m]) / 2; },
      COUNT: (a, si) => flat(a, si).filter(x => typeof x.v === 'number').length, COUNTA: (a, si) => flat(a, si).filter(x => x.v !== '' && x.v != null).length, COUNTBLANK: (a, si) => flat(a, si).filter(x => x.v === '' || x.v == null).length,
      ABS: (a, si) => Math.abs(toNum(ev(a[0], si))), SQRT: (a, si) => { const x = toNum(ev(a[0], si)); if (x < 0) throw E('#NOMBRE!'); return Math.sqrt(x); }, INT: (a, si) => Math.floor(toNum(ev(a[0], si))), SIGN: (a, si) => Math.sign(toNum(ev(a[0], si))),
      POWER: (a, si) => Math.pow(toNum(ev(a[0], si)), toNum(ev(a[1], si))), MOD: (a, si) => { const d = toNum(ev(a[1], si)); if (d === 0) throw E('#DIV/0!'); const x = toNum(ev(a[0], si)); return x - d * Math.floor(x / d); }, PI: () => Math.PI,
      ROUND: (a, si) => rnd(a, si, Math.round), ROUNDUP: (a, si) => rnd(a, si, x => x < 0 ? -Math.ceil(-x) : Math.ceil(x)), ROUNDDOWN: (a, si) => rnd(a, si, x => x < 0 ? -Math.floor(-x) : Math.floor(x)),
      IF: (a, si) => { if (a.length < 2) throw E('#VALEUR!'); const c = toBool(ev(a[0], si)); return c ? ev(a[1], si) : (a[2] ? ev(a[2], si) : false); },
      IFERROR: (a, si) => { try { const v = ev(a[0], si); if (isErr(v)) return ev(a[1], si); return v; } catch (e) { if (e instanceof CellErr) return ev(a[1], si); throw e; } },
      AND: (a, si) => flat(a, si).every(x => toBool(x.v)), OR: (a, si) => flat(a, si).some(x => toBool(x.v)), NOT: (a, si) => !toBool(ev(a[0], si)), TRUE: () => true, FALSE: () => false,
      CONCAT: (a, si) => flat(a, si).map(x => toStr(x.v)).join(''), LEFT: (a, si) => toStr(ev(a[0], si)).slice(0, a[1] ? toNum(ev(a[1], si)) : 1), RIGHT: (a, si) => { const s = toStr(ev(a[0], si)), n = a[1] ? toNum(ev(a[1], si)) : 1; return n ? s.slice(-n) : ''; },
      MID: (a, si) => toStr(ev(a[0], si)).substr(toNum(ev(a[1], si)) - 1, toNum(ev(a[2], si))), LEN: (a, si) => toStr(ev(a[0], si)).length, UPPER: (a, si) => toStr(ev(a[0], si)).toUpperCase(), LOWER: (a, si) => toStr(ev(a[0], si)).toLowerCase(), TRIM: (a, si) => toStr(ev(a[0], si)).trim().replace(/\s+/g, ' '),
      SUMIF: (a, si) => { const t = critTest(ev(a[1], si)), r = rangeVals(a[0], si), s = a[2] ? rangeVals(a[2], si) : r; let tot = 0; r.forEach((row, i) => row.forEach((x, j) => { if (t(x)) { const y = (s[i] || [])[j]; if (typeof y === 'number') tot += y; } })); return tot; },
      COUNTIF: (a, si) => { const t = critTest(ev(a[1], si)); let n = 0; rangeVals(a[0], si).forEach(row => row.forEach(x => { if (t(x)) n++; })); return n; },
      AVERAGEIF: (a, si) => { const t = critTest(ev(a[1], si)), r = rangeVals(a[0], si), s = a[2] ? rangeVals(a[2], si) : r; let tot = 0, n = 0; r.forEach((row, i) => row.forEach((x, j) => { if (t(x)) { const y = (s[i] || [])[j]; if (typeof y === 'number') { tot += y; n++; } } })); if (!n) throw E('#DIV/0!'); return tot / n; },
      TODAY: () => sheetDays(), NOW: () => (Date.now() - new Date(1899, 11, 30).getTime()) / 864e5,
      YEAR: (a, si) => new Date(new Date(1899, 11, 30).getTime() + toNum(ev(a[0], si)) * 864e5).getFullYear(), MONTH: (a, si) => new Date(new Date(1899, 11, 30).getTime() + toNum(ev(a[0], si)) * 864e5).getMonth() + 1, DAY: (a, si) => new Date(new Date(1899, 11, 30).getTime() + toNum(ev(a[0], si)) * 864e5).getDate(),
      PMT: (a, si) => { const r = toNum(ev(a[0], si)), n = toNum(ev(a[1], si)), pv = toNum(ev(a[2], si)), fv = a[3] && a[3].t !== 'empty' ? toNum(ev(a[3], si)) : 0, ty = a[4] ? toNum(ev(a[4], si)) : 0; if (n === 0) throw E('#NOMBRE!'); if (r === 0) return -(pv + fv) / n; const f = Math.pow(1 + r, n); return -(pv * f + fv) * r / ((f - 1) * (1 + r * ty)); },
      PV: (a, si) => { const r = toNum(ev(a[0], si)), n = toNum(ev(a[1], si)), pm = toNum(ev(a[2], si)), fv = a[3] && a[3].t !== 'empty' ? toNum(ev(a[3], si)) : 0, ty = a[4] ? toNum(ev(a[4], si)) : 0; if (r === 0) return -(fv + pm * n); const f = Math.pow(1 + r, n); return -(fv + pm * (1 + r * ty) * (f - 1) / r) / f; },
      FV: (a, si) => { const r = toNum(ev(a[0], si)), n = toNum(ev(a[1], si)), pm = toNum(ev(a[2], si)), pv = a[3] && a[3].t !== 'empty' ? toNum(ev(a[3], si)) : 0, ty = a[4] ? toNum(ev(a[4], si)) : 0; if (r === 0) return -(pv + pm * n); const f = Math.pow(1 + r, n); return -(pv * f + pm * (1 + r * ty) * (f - 1) / r); },
      VLOOKUP: (a, si) => { const v = ev(a[0], si), t = rangeVals(a[1], si), col = toNum(ev(a[2], si)); const exact = a[3] && a[3].t !== 'empty' ? !toBool(ev(a[3], si)) : false; if (col < 1 || col > (t[0] || []).length) throw E('#REF!');
        if (exact) { for (const row of t) if (typeof row[0] === typeof v ? (typeof v === 'string' ? row[0].toLowerCase() === v.toLowerCase() : row[0] === v) : false) return row[col - 1]; throw E('#N/A'); }
        let best = null; for (const row of t) { if (cmpVals(row[0], v) <= 0) best = row; else break; } if (!best) throw E('#N/A'); return best[col - 1]; }
    };
    function rnd(a, si, f) { const x = toNum(ev(a[0], si)), d = a[1] ? Math.trunc(toNum(ev(a[1], si))) : 0, k = Math.pow(10, d); return f(x * k + (f === Math.round ? Math.sign(x) * 1e-9 : 0)) / k; }
    function ev(n, si) { if (!n || n.t === 'empty') return ''; const v = evalNode(n, si); if (isErr(v)) throw v; if (Array.isArray(v)) return v.length && v[0].length ? v[0][0] : ''; return v; }
    function evalNode(n, si) {
      switch (n.t) {
        case 'num': case 'str': case 'bool': return n.v;
        case 'empty': return '';
        case 'ref': { const s = n.sheet == null ? si : sheetIdx(n.sheet); return cell(s, n.c, n.r); }
        case 'range': return rangeVals(n, si);
        case 'un': return -toNum(ev(n.a, si));
        case 'bin': {
          const a = ev(n.a, si), b = ev(n.b, si);
          switch (n.op) {
            case '+': return toNum(a) + toNum(b); case '-': return toNum(a) - toNum(b); case '*': return toNum(a) * toNum(b);
            case '/': { const d = toNum(b); if (d === 0) throw E('#DIV/0!'); return toNum(a) / d; }
            case '^': { const r = Math.pow(toNum(a), toNum(b)); if (!isFinite(r)) throw E('#NOMBRE!'); return r; }
            case '&': return toStr(a) + toStr(b);
            default: { const c = cmpVals(a, b); return { '=': c === 0, '<>': c !== 0, '<': c < 0, '>': c > 0, '<=': c <= 0, '>=': c >= 0 }[n.op]; }
          }
        }
        case 'call': { const f = FN[n.name]; if (!f) throw E('#NOM?'); const r = f(n.args, si); return typeof r === 'number' && !isFinite(r) ? (() => { throw E('#NOMBRE!'); })() : r; }
      }
      throw E('#VALEUR!');
    }
    return { get: (si, c, r) => cell(si, c, r), reset: () => memo.clear() };
  }

  // ----- affichage -----
  const NF = { auto: 'Standard', n0: '0', n2: '0,00', sep: '1 234,56', eur: 'Euro (€)', pct: 'Pourcentage', date: 'Date' };
  function format(v, nf) {
    if (isErr(v)) return v.code;
    if (typeof v === 'boolean') return v ? 'VRAI' : 'FAUX';
    if (typeof v !== 'number') return v == null ? '' : String(v);
    const f = (d, grp) => v.toLocaleString('fr-FR', { minimumFractionDigits: d, maximumFractionDigits: d, useGrouping: grp });
    switch (nf) {
      case 'n0': return f(0, false); case 'n2': return f(2, false); case 'sep': return f(2, true); case 'eur': return f(2, true) + ' €';
      case 'pct': return (v * 100).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' %';
      case 'date': { const d = new Date(new Date(1899, 11, 30).getTime() + Math.round(v) * 864e5); return d.toLocaleDateString('fr-FR'); }
      default: return fmtNum(v);
    }
  }

  // ----- références : décalage (recopie) et insertion/suppression de lignes ou colonnes -----
  const REF_RE = /(\$?)([A-Z]{1,3})(\$?)(\d{1,6})(?![\w(!])/g;
  function mapRefs(formula, fn) { // applique fn à chaque référence hors des chaînes entre guillemets
    return formula.split(/("(?:[^"]|"")*")/).map((part, i) => i % 2 ? part : part.replace(/(^|[^A-Za-z0-9_.])(\$?[A-Z]{1,3}\$?\d{1,6})(?::(\$?[A-Z]{1,3}\$?\d{1,6}))?(?![\w(!])/g, (m, pre, a, b) => pre + fn(a, b))).join('');
  }
  function shiftFormula(raw, dr, dc) {
    if (typeof raw !== 'string' || raw[0] !== '=') return raw;
    const sh = a => { const m = /^(\$?)([A-Z]{1,3})(\$?)(\d{1,6})$/.exec(a); const c = m[1] ? colIndex(m[2]) : colIndex(m[2]) + dc, r = m[3] ? +m[4] - 1 : +m[4] - 1 + dr; if (c < 0 || r < 0) return null; return (m[1] || '') + colName(c) + (m[3] || '') + (r + 1); };
    return mapRefs(raw, (a, b) => { const x = sh(a), y = b ? sh(b) : ''; if (x === null || y === null) return '#REF!'; return b ? x + ':' + y : x; });
  }
  function adjustFormula(raw, kind, index, delta) { // kind : 'row' | 'col' ; delta : +1 insertion, -1 suppression
    if (typeof raw !== 'string' || raw[0] !== '=') return raw;
    const pt = (a, isEnd, inRange) => { const m = /^(\$?)([A-Z]{1,3})(\$?)(\d{1,6})$/.exec(a); let c = colIndex(m[2]), r = +m[4] - 1; const v = kind === 'row' ? r : c;
      let nv = v; if (delta > 0) { if (v >= index) nv = v + 1; } else { if (v === index) { if (!inRange) return null; nv = isEnd ? index - 1 : index; } else if (v > index) nv = v - 1; }
      if (nv < 0) return null; if (kind === 'row') r = nv; else c = nv; return m[1] + colName(c) + m[3] + (r + 1); };
    return mapRefs(raw, (a, b) => { if (!b) { const x = pt(a, false, false); return x === null ? '#REF!' : x; } const x = pt(a, false, true), y = pt(b, true, true); if (x === null || y === null) return '#REF!'; return x + ':' + y; });
  }
  // formules : export vers Excel (anglais, séparateur « , ») et import (inverse)
  function splitTop(f, fn) { return f.split(/("(?:[^"]|"")*")/).map((p, i) => i % 2 ? p : fn(p)).join(''); }
  function toExcel(raw) { if (typeof raw !== 'string' || raw[0] !== '=') return null; return splitTop(raw.slice(1), p => p.replace(/[A-Za-zÀ-ÿ_][A-Za-zÀ-ÿ0-9_.]*(?=\()/g, n => FR[n.toUpperCase()] || n.toUpperCase()).replace(/(\d),(\d)/g, '$1.$2').replace(/;/g, ',')); }
  function fromExcel(f) { return '=' + splitTop(f, p => p.replace(/[A-Za-zÀ-ÿ_][A-Za-zÀ-ÿ0-9_.]*(?=\()/g, n => EN2FR[n.toUpperCase()] || n).replace(/,/g, ';')); }
  return { Calc, parse, parseRaw, format, NF, colName, colIndex, addr, parseAddr, shiftFormula, adjustFormula, toExcel, fromExcel, CellErr, isErr, FR, EN2FR };
})();
if (typeof module !== 'undefined') module.exports = SheetEngine;
