/* Amorce injectée dans chaque outil intégré (fichier autonome) : navigation, liens, absence de serveur, impression. */
(function () {
  var SA = window.TOOLS_SA, P = window.parent;
  document.addEventListener('click', function (e) {
    var t = e.target.closest && e.target.closest('[data-sa]');
    if (t) {
      e.preventDefault(); var a = t.getAttribute('data-sa');
      if (a === 'home') P.CE.home(); else if (a === 'open') P.CE.open(t.getAttribute('data-tool')); else if (a === 'feedback') P.CE.feedback(t.getAttribute('data-tool') || SA.tool);
      return;
    }
    var l = e.target.closest && e.target.closest('a[href]');
    if (!l) return;
    var h = l.getAttribute('href') || '';
    if (/^https?:/i.test(h)) { l.target = '_blank'; l.rel = 'noopener noreferrer'; }
    else if (!/^(mailto:|tel:|#|javascript:|blob:|data:)/i.test(h)) e.preventDefault();
  }, true);
  // aucun serveur : les appels relatifs échouent proprement (ou sont servis par une route de remplacement, voir TOOLS_SA.routes)
  var nativeFetch = window.fetch ? window.fetch.bind(window) : null;
  window.fetch = function (u, o) {
    var s = String((u && u.url) || u);
    if (/^(https?:|data:|blob:)/i.test(s) && nativeFetch) return nativeFetch(u, o);
    var r = SA.routes || {};
    for (var k in r) if (s.indexOf(k) === 0 || s.indexOf('/' + k) !== -1) return Promise.resolve(r[k](s, o));
    return Promise.resolve(new Response(JSON.stringify({ error: 'Cette fonction nécessite le serveur et n\'est pas disponible dans le fichier autonome.' }), { status: 503, headers: { 'Content-Type': 'application/json' } }));
  };
  document.addEventListener('submit', function (e) { e.preventDefault(); }, true);
  document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'p') { e.preventDefault(); window.print(); } });
})();

/* Zonage A/B/C des communes : données embarquées à la génération du fichier (sinon le choix de la zone reste manuel). */
(function () {
  var SA = window.TOOLS_SA, resp = function (o) { return new Response(JSON.stringify(o), { status: 200, headers: { 'Content-Type': 'application/json; charset=utf-8' } }); };
  SA.routes['zonage.php'] = function (url) {
    var Z = SA.zonage; if (!Z) return resp({ available: false });
    var u = new URL(url, 'http://x/'), insee = (u.searchParams.get('insee') || '').toUpperCase(), dep = (u.searchParams.get('dep') || '').toUpperCase();
    if (insee) { var f = null; Object.keys(Z.communes).some(function (d) { return Z.communes[d].some(function (c) { if (c[0] === insee) { f = c; return true; } return false; }); }); return resp(f ? { available: true, code: insee, commune: f[1], zone: f[2] } : { available: true, zone: null }); }
    if (!dep) return resp({ available: true, count: Z.count, date: Z.date, libelle: Z.libelle, departements: Z.departements });
    return resp({ available: true, communes: Z.communes[dep] || [] });
  };
})();

/* Remplacement du serveur pour les recherches RGE et DPE : appels directs aux API publiques de l'ADEME (nécessite un accès à internet). */
(function () {
  var SA = window.TOOLS_SA; if (!SA || (SA.tool !== 'rge' && SA.tool !== 'dpe')) return;
  var BASE = 'https://data.ademe.fr/data-fair/api/v1/datasets/';
  var json = function (o, code) { return new Response(JSON.stringify(o), { status: code || 200, headers: { 'Content-Type': 'application/json; charset=utf-8' } }); };
  var fail = function (m, c) { return json({ error: m }, c || 400); };
  var qs = function (u) { var p = {}; new URL(u, 'http://x/').searchParams.forEach(function (v, k) { p[k] = v; }); return p; };
  var nativeFetch = window.fetch.bind(window);
  async function ademe(dataset, params) {
    try {
      var r = await nativeFetch(BASE + dataset + '/lines?' + new URLSearchParams(params).toString(), { headers: { Accept: 'application/json' } });
      if (!r.ok) return null; var d = await r.json(); return d && Array.isArray(d.results) ? d : null;
    } catch (e) { return null; }
  }
  var HS = 'Impossible de joindre l\'API de l\'ADEME (accès à internet requis pour cette recherche).';

  // ----- RGE -----
  async function rge(url) {
    var q = (qs(url).q || '').trim(), digits = q.replace(/[\s.\-]/g, ''), params = { size: 300, sort: 'nom_entreprise' };
    if (digits !== '' && /^\d+$/.test(digits)) {
      if (digits.length === 14) params.qs = 'siret:"' + digits + '"'; else if (digits.length === 9) params.qs = 'siret:' + digits + '*';
      else return fail('Un SIREN compte 9 chiffres et un SIRET 14 chiffres.');
    } else {
      if (q.length < 3 || q.length > 100) return fail('Saisissez au moins 3 caractères du nom de l\'entreprise.');
      params = { q: q, q_fields: 'nom_entreprise', q_mode: 'complete', size: 300 };
    }
    params.select = 'siret,nom_entreprise,adresse,code_postal,commune,telephone,email,site_internet,code_qualification,nom_qualification,url_qualification,nom_certificat,domaine,meta_domaine,organisme,particulier,lien_date_debut,lien_date_fin';
    var data = await ademe('liste-des-entreprises-rge-2', params);
    if (!data) return fail(HS, 502);
    var today = new Date().toISOString().slice(0, 10), companies = {}, order = [];
    data.results.forEach(function (r) {
      var siret = r.siret || ''; if (!siret) return;
      if (!companies[siret]) { companies[siret] = { siret: siret, siren: siret.slice(0, 9), nom: r.nom_entreprise || '', adresse: [(r.adresse || ''), (r.code_postal || ''), (r.commune || '')].join(' ').replace(/\s+/g, ' ').trim(), telephone: r.telephone || '', email: r.email || '', site: r.site_internet || '', qualifications: [], valide: false }; order.push(siret); }
      var d = r.lien_date_debut || null, f = r.lien_date_fin || null, ok = (!d || d <= today) && (!f || f >= today);
      if (ok) companies[siret].valide = true;
      companies[siret].qualifications.push({ domaine: r.domaine || '', meta_domaine: r.meta_domaine || '', qualification: r.nom_qualification || '', code: r.code_qualification || '', organisme: r.organisme || '', certificat: r.nom_certificat || '', debut: d, fin: f, valide: ok, url: r.url_qualification || '', particulier: r.particulier == null ? null : r.particulier });
    });
    order.forEach(function (s) { companies[s].qualifications.sort(function (a, b) { return (b.valide - a.valide) || String(a.domaine).localeCompare(String(b.domaine)); }); });
    var meta = null; try { var ir = await nativeFetch(BASE + 'liste-des-entreprises-rge-2'); if (ir.ok) meta = (await ir.json()).dataUpdatedAt || null; } catch (e) {}
    return json({ companies: order.slice(0, 30).map(function (s) { return companies[s]; }), nb_companies: order.length, truncated: data.results.length >= 300 || order.length > 30, updated: meta });
  }

  // ----- DPE -----
  var NEW_FIELDS = 'numero_dpe,adresse_ban,complement_adresse_logement,complement_adresse_batiment,etiquette_dpe,etiquette_ges,date_etablissement_dpe,date_fin_validite_dpe,type_batiment,surface_habitable_logement,annee_construction,periode_construction,conso_5_usages_par_m2_ep,emission_ges_5_usages_par_m2,cout_total_5_usages,type_energie_principale_chauffage,numero_etage_appartement,_geopoint';
  var OLD_FIELDS = 'numero_dpe,geo_adresse,date_etablissement_dpe,classe_consommation_energie,classe_estimation_ges,consommation_energie,estimation_ges,surface_thermique_lot,annee_construction,tr002_type_batiment_description,_geopoint';
  var norm = function (s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/œ/g, 'oe').replace(/æ/g, 'ae').replace(/[^a-z0-9]+/g, ' ').trim(); };
  var nz = function (v) { return v === undefined ? null : v; };
  function mapNew(r) { return { source: 'new', numero_dpe: r.numero_dpe || '', adresse: r.adresse_ban || '', complement: [r.complement_adresse_logement, r.complement_adresse_batiment].filter(Boolean).join(' – '), date: nz(r.date_etablissement_dpe), validite: nz(r.date_fin_validite_dpe), etiquette_dpe: nz(r.etiquette_dpe), etiquette_ges: nz(r.etiquette_ges), type: r.type_batiment || '', etage: nz(r.numero_etage_appartement), surface: nz(r.surface_habitable_logement), conso: nz(r.conso_5_usages_par_m2_ep), ges: nz(r.emission_ges_5_usages_par_m2), cout: nz(r.cout_total_5_usages), energie: r.type_energie_principale_chauffage || '', construction: r.annee_construction != null ? r.annee_construction : (r.periode_construction || ''), geo: nz(r._geopoint) }; }
  function mapOld(r) { var cls = function (v) { return v && /^[A-G]$/.test(v) ? v : null; }; return { source: 'old', numero_dpe: r.numero_dpe || '', adresse: r.geo_adresse || '', complement: '', date: nz(r.date_etablissement_dpe), validite: null, etiquette_dpe: cls(r.classe_consommation_energie), etiquette_ges: cls(r.classe_estimation_ges), type: r.tr002_type_batiment_description || '', etage: null, surface: nz(r.surface_thermique_lot), conso: r.consommation_energie ? r.consommation_energie : null, ges: r.estimation_ges ? r.estimation_ges : null, cout: null, energie: '', construction: r.annee_construction || '', geo: nz(r._geopoint) }; }
  var byDate = function (a, b) { return String(b.date || '').localeCompare(String(a.date || '')); };
  var schemas = {};
  async function schemaGroups(ds) {
    if (schemas[ds]) return schemas[ds];
    var g = {}; try { var r = await nativeFetch(BASE + ds + '/schema?mimeType=application/json'); (await r.json()).forEach(function (f) { g[f.key] = f['x-group'] || 'Informations'; }); } catch (e) {}
    return (schemas[ds] = g);
  }
  async function dpe(url) {
    var P = qs(url), mode = P.mode || 'address';
    if (mode === 'detail') {
      var n = P.n || ''; if (!/^[0-9A-Za-z]{10,20}$/.test(n)) return fail('Numéro de DPE invalide.');
      var isNew = (P.source || 'new') !== 'old', ds = isNew ? 'dpe03existant' : 'dpe-france';
      var d = await ademe(ds, { qs: 'numero_dpe:"' + n + '"', size: 1 }); if (!d) return fail(HS, 502); if (!d.results.length) return fail('DPE introuvable.', 404);
      var rec = d.results[0], gof = await schemaGroups(ds), groups = {}, names = [];
      Object.keys(rec).forEach(function (k) { var v = rec[k]; if (k[0] === '_' || v === null || v === '') return; var g = gof[k] || 'Informations'; if (!groups[g]) { groups[g] = []; names.push(g); } groups[g].push({ key: k, value: v }); });
      var order = ['Bilan DPE', 'Caractéristiques bâtiment', 'Consommation en énergie primaire', 'Consommation en énergie finale', 'Emissions de GES', 'Coûts', 'Isolation', 'Chauffage', 'ECS'];
      names.sort(function (a, b) { var ia = order.indexOf(a), ib = order.indexOf(b); return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib); });
      return json({ summary: isNew ? mapNew(rec) : mapOld(rec), groups: names.map(function (nm) { return { name: nm, fields: groups[nm] }; }) });
    }
    var sources = (P.sources || 'new,old').split(',').filter(function (s) { return s === 'new' || s === 'old'; });
    if (!sources.length) return fail('Source inconnue.');
    var all = [], errors = 0, i, s, isN, dd;
    if (mode === 'address') {
      var q = (P.q || '').trim(); if (q.length < 5 || q.length > 200) return fail('Adresse invalide.');
      for (i = 0; i < sources.length; i++) { s = sources[i]; isN = s === 'new';
        dd = await ademe(isN ? 'dpe03existant' : 'dpe-france', { q: q, q_fields: isN ? 'adresse_ban' : 'geo_adresse', q_mode: 'complete', size: 100, sort: '-date_etablissement_dpe', select: isN ? NEW_FIELDS : OLD_FIELDS });
        if (!dd) { errors++; continue; } dd.results.forEach(function (r) { all.push(isN ? mapNew(r) : mapOld(r)); }); }
      if (errors === sources.length) return fail(HS, 502);
      var target = norm(q), exact = [], approx = [];
      all.forEach(function (r) { (norm(r.adresse) === target ? exact : approx).push(r); });
      return json({ exact: exact.sort(byDate), approx: approx.sort(byDate), partial: errors > 0 });
    }
    if (mode === 'map') {
      if (!/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/.test(P.bbox || '')) return fail('Zone invalide.');
      var b = P.bbox.split(',').map(parseFloat); if (Math.abs(b[2] - b[0]) > 0.05 || Math.abs(b[3] - b[1]) > 0.05) return fail('Zone trop étendue : zoomez sur la carte.');
      for (i = 0; i < sources.length; i++) { s = sources[i]; isN = s === 'new';
        dd = await ademe(isN ? 'dpe03existant' : 'dpe-france', { bbox: b.join(','), size: 500, sort: '-date_etablissement_dpe', select: isN ? NEW_FIELDS : OLD_FIELDS });
        if (!dd) { errors++; continue; } dd.results.forEach(function (r) { all.push(isN ? mapNew(r) : mapOld(r)); }); }
      if (errors === sources.length) return fail(HS, 502);
      var grp = {}, keys = []; all.forEach(function (r) { if (!r.geo) return; if (!grp[r.geo]) { grp[r.geo] = []; keys.push(r.geo); } grp[r.geo].push(r); });
      var pts = keys.map(function (g) { var rows = grp[g].sort(byDate), ll = g.split(',').map(parseFloat); return { lat: ll[0], lon: ll[1], adresse: rows[0].adresse, count: rows.length, dpe: rows.slice(0, 15) }; });
      return json({ points: pts, truncated: all.length >= 500, partial: errors > 0 });
    }
    return fail('Mode inconnu.');
  }
  SA.routes['api.php'] = function (url) { return SA.tool === 'rge' ? rge(url) : dpe(url); };
})();
