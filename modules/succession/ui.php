<?php
// Simulateur de droits de succession et de donation, partagé par le portail (avec enregistrement) et /tools (sans).
// Variables attendues : $capSave (bool), $capLoad (array|null). Les barèmes se règlent dans l'administration (Barèmes : « Droits de succession et de donation » et « Fiscalité de l'épargne »).
require_once __DIR__ . '/../../includes/baremes.php';
$capLoad = $capLoad ?? null;
$scB = baremeGet('succession')['data'];
$scF = baremeGet('fiscalite')['data'];
$scAv = ['abat' => (float)$scF['av_990i_abattement'], 't1' => (float)$scF['av_990i_taux1'], 'seuil' => (float)$scF['av_990i_seuil'], 't2' => (float)$scF['av_990i_taux2']];
require __DIR__ . '/../_sim/style.php';
?>
<?= baremeNotice(['succession', 'fiscalite']) ?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<style>
.sc-tabs .btn{border-radius:20px}.sc-table{width:100%;font-size:13.5px}.sc-table th{font-weight:600;color:#6c757d;border-bottom:2px solid #e9ecef;padding:4px 6px;white-space:nowrap}.sc-table td{padding:4px 6px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
.sc-table input[type=number]{width:100px;text-align:right}.sc-table input.sc-nom{width:130px}.sc-r{text-align:right;white-space:nowrap}.sc-tot td{font-weight:700;border-top:2px solid #212529;border-bottom:0}
.sc-warn{background:#fff8e1;border-left:4px solid #f0ad00;border-radius:6px;padding:8px 12px;font-size:13.5px;margin-top:8px}.sc-best{display:inline-block;background:#2e9b57;color:#fff;border-radius:10px;font-size:11px;padding:1px 8px;margin-left:6px}
.sc-big{font-size:1.9rem;font-weight:800;color:#c40025}.sc-det td{font-size:12.5px;color:#555;padding:1px 6px}details.sc-d summary{cursor:pointer;color:#6c757d;font-size:13px}
</style>
<div class="sc-tabs btn-group mb-3 no-print"><button type="button" class="btn btn-sm btn-danger" data-tab="succ"><i class="fas fa-scale-balanced me-1"></i>Succession</button><button type="button" class="btn btn-sm btn-outline-secondary" data-tab="don"><i class="fas fa-gift me-1"></i>Donation</button></div>

<div id="sc-succ" class="row g-3">
 <div class="col-lg-7">
  <div class="cap-card"><h2><i class="fas fa-people-group me-2 text-danger"></i>Situation du défunt</h2>
   <div class="row g-2">
    <div class="col-md-6"><label class="form-label small mb-0" for="s_sit">Situation familiale</label><select id="s_sit" class="form-select"><option value="marie">Marié(e)</option><option value="pacs">Pacsé(e)</option><option value="seul">Célibataire, veuf(ve), divorcé(e) ou concubin(e)</option></select></div>
    <div class="col-md-3 sc-ifmarie"><label class="form-label small mb-0" for="s_age">Âge du conjoint survivant</label><input type="number" id="s_age" class="form-control" min="18" max="110" value="65"></div>
    <div class="col-md-3"><label class="form-label small mb-0" for="s_enf">Nombre d'enfants</label><input type="number" id="s_enf" class="form-control" min="0" max="10" value="2"></div>
    <div class="col-12 sc-ifmarie sc-ifenf"><div class="form-check"><input type="checkbox" class="form-check-input" id="s_communs" checked><label class="form-check-label small" for="s_communs">Tous les enfants sont communs avec le conjoint survivant (le conjoint peut alors choisir l'usufruit de la totalité)</label></div></div>
    <div class="col-12 sc-ifnoenf"><div class="d-flex flex-wrap gap-3 align-items-center small"><span class="text-muted">Sans enfant :</span>
      <div class="form-check"><input type="checkbox" class="form-check-input" id="s_pere" checked><label class="form-check-label" for="s_pere">Père vivant</label></div>
      <div class="form-check"><input type="checkbox" class="form-check-input" id="s_mere" checked><label class="form-check-label" for="s_mere">Mère vivante</label></div>
      <label class="mb-0" for="s_fs">Frères et sœurs</label><input type="number" id="s_fs" class="form-control form-control-sm" style="width:70px" min="0" max="12" value="0"></div></div>
    <div class="col-md-8"><label class="form-label small mb-0" for="s_mode">Répartition entre les héritiers</label><select id="s_mode" class="form-select"><option value="auto">Légale (sans testament)</option><option value="manuel">Personnalisée (testament, legs, donation au dernier vivant)</option></select></div>
   </div></div>
  <div class="cap-card"><h2><i class="fas fa-house-chimney me-2 text-danger"></i>Patrimoine du défunt <span class="small fw-normal text-muted ms-2">(sa part, s'il y a des biens communs)</span></h2>
   <div class="row g-2">
    <?php foreach (['p_rp' => 'Résidence principale', 'p_immo' => 'Autres biens immobiliers', 'p_fin' => 'Placements (hors assurance-vie)', 'p_cpt' => 'Comptes et liquidités', 'p_aut' => 'Autres biens (véhicules, meubles…)', 'p_dettes' => 'Dettes à déduire'] as $id => $lab): ?>
    <div class="col-md-4"><label class="form-label small mb-0" for="<?= $id ?>"><?= $lab ?> (€)</label><input type="number" id="<?= $id ?>" class="form-control" min="0" step="100" value="0"></div>
    <?php endforeach; ?>
    <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" id="p_rpocc"><label class="form-check-label small" for="p_rpocc">La résidence principale est occupée par le conjoint, le partenaire de Pacs ou un enfant mineur (abattement de <?= (float)$scB['residence_principale_pct'] ?> %)</label></div>
     <div class="form-check"><input type="checkbox" class="form-check-input" id="p_fun" checked><label class="form-check-label small" for="p_fun">Déduire le forfait de frais funéraires (<?= number_format((float)$scB['frais_funeraires'], 0, ',', ' ') ?> €)</label></div></div>
   </div></div>
  <div class="cap-card"><h2><i class="fas fa-users me-2 text-danger"></i>Héritiers et bénéficiaires</h2>
   <div id="sc-opt" class="mb-2"></div><div id="sc-heirs" style="overflow-x:auto"></div>
   <div class="small text-muted mt-2">Donations : celles reçues du défunt dans les <?= (int)$scB['rappel_ans'] ?> dernières années (elles réduisent l'abattement).</div></div>
  <div class="cap-card"><h2><i class="fas fa-shield-heart me-2 text-danger"></i>Assurance-vie <span class="small fw-normal text-muted ms-2">(hors succession, taxée à part)</span></h2>
   <details class="mb-2 small"><summary class="text-primary" style="cursor:pointer">Comment c'est taxé ?</summary>
    <div class="mt-2"><b>Primes versées avant 70 ans (article 990 I du CGI).</b> Le capital décès (primes <u>et</u> gains) est taxé par bénéficiaire, tous contrats du même assuré confondus : abattement de <?= number_format($scAv['abat'], 0, ',', ' ') ?> € par bénéficiaire, puis <?= $scAv['t1'] ?> % jusqu'à <?= number_format($scAv['seuil'], 0, ',', ' ') ?> € taxables, puis <?= $scAv['t2'] ?> %. Le conjoint ou partenaire de Pacs est exonéré. Les contrats souscrits avant le 20/11/1991 ou dénoués par un versement avant le 13/10/1998 suivent d'autres règles non calculées ici.</div>
    <div class="mt-2"><b>Primes versées à partir de 70 ans (article 757 B du CGI).</b> Seules les <u>primes</u> sont retenues (les gains sont exonérés) : après un abattement de <?= number_format((float)$scB['av_757b_abattement'], 0, ',', ' ') ?> € <u>partagé entre tous les bénéficiaires</u>, l'excédent s'ajoute à la part successorale du bénéficiaire et subit les droits de succession selon son lien de parenté (abattement personnel, barème).</div></div></details>
   <div id="sc-av" style="overflow-x:auto"></div></div>
 </div>
 <div class="col-lg-5"><div style="position:sticky;top:20px"><div id="sc-res" class="cap-card js-result"></div></div></div>
</div>

<div id="sc-don" class="row g-3" style="display:none">
 <div class="col-lg-7"><div class="cap-card"><h2><i class="fas fa-gift me-2 text-danger"></i>Donation</h2><div class="row g-2">
   <div class="col-md-6"><label class="form-label small mb-0" for="d_lien">Lien entre le donateur et le bénéficiaire</label><select id="d_lien" class="form-select"></select></div>
   <div class="col-md-6"><label class="form-label small mb-0" for="d_mont">Valeur donnée, en pleine propriété (€)</label><input type="number" id="d_mont" class="form-control" min="0" step="1000" value="100000"></div>
   <div class="col-md-6"><label class="form-label small mb-0" for="d_nu">Forme de la donation</label><select id="d_nu" class="form-select"><option value="pp">Pleine propriété</option><option value="np">Nue-propriété (le donateur garde l'usufruit)</option></select></div>
   <div class="col-md-6 sc-ifnp"><label class="form-label small mb-0" for="d_age">Âge du donateur (usufruitier)</label><input type="number" id="d_age" class="form-control" min="18" max="110" value="65"></div>
   <div class="col-md-6"><label class="form-label small mb-0" for="d_prev">Donations déjà faites au même bénéficiaire (<?= (int)$scB['rappel_ans'] ?> ans, €)</label><input type="number" id="d_prev" class="form-control" min="0" step="1000" value="0"></div>
   <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" id="d_handi"><label class="form-check-label small" for="d_handi">Bénéficiaire en situation de handicap</label></div></div>
   <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" id="d_fam"><label class="form-check-label small" for="d_fam">Il s'agit d'un don familial de somme d'argent (donateur de moins de <?= (int)$scB['don_familial_age_donateur'] ?> ans, bénéficiaire majeur : abattement supplémentaire de <?= number_format((float)$scB['abattements']['don_familial'], 0, ',', ' ') ?> €)</label></div></div>
   <div class="col-md-6 sc-iffam"><label class="form-label small mb-0" for="d_famprev">Dons familiaux déjà utilisés (<?= (int)$scB['rappel_ans'] ?> ans, €)</label><input type="number" id="d_famprev" class="form-control" min="0" step="1000" value="0"></div>
  </div></div></div>
 <div class="col-lg-5"><div style="position:sticky;top:20px"><div id="sd-res" class="cap-card js-result"></div></div></div>
</div>
<?php if (!empty($capSave)) simSaveForm($capLoad, 'scPrepare'); ?>
<div class="small text-muted mt-3"><i class="fas fa-circle-info me-1"></i>Estimation fiscale indicative, fondée sur les règles générales : elle ne remplace pas l'avis d'un notaire. Ne sont pas pris en compte, entre autres : le régime matrimonial, les biens à l'étranger, les exonérations particulières (entreprises, bois et forêts, œuvres d'art…), les legs à des associations, la fiscalité du démembrement des contrats d'assurance-vie, la représentation des héritiers prédécédés et la réserve héréditaire.</div>
<script>
(function () {
  'use strict';
  const B = <?= json_encode($scB, JSON_UNESCAPED_UNICODE) ?>, AV = <?= json_encode($scAv) ?>, LOAD = <?= json_encode($capLoad ? json_decode($capLoad['params'] ?? '{}', true) : null) ?>;
  const $ = id => document.getElementById(id), esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = v => Math.round(v).toLocaleString('fr-FR') + ' €', pc = v => (Math.round(v * 10) / 10).toLocaleString('fr-FR') + ' %', num = v => { const n = parseFloat(v); return isNaN(n) || n < 0 ? 0 : n; };
  // ----- liens de parenté : abattement, barème, exonération -----
  const LIENS = {
    conjoint: { lab: 'Conjoint', exo: 1 }, pacs: { lab: 'Partenaire de Pacs', exo: 1 },
    enfant: { lab: 'Enfant', ab: 'enfant', bar: 'direct', abD: 'enfant' }, petit_enfant: { lab: 'Petit-enfant', ab: 'petit_enfant', bar: 'direct', abD: 'petit_enfant_donation' },
    arriere_petit_enfant: { lab: 'Arrière-petit-enfant', ab: 'arriere_petit_enfant', bar: 'direct', abD: 'arriere_petit_enfant_donation' }, parent: { lab: 'Parent', ab: 'parent', bar: 'direct', abD: 'parent' },
    frere_soeur: { lab: 'Frère ou sœur', ab: 'frere_soeur', bar: 'fratrie', abD: 'frere_soeur' }, neveu_niece: { lab: 'Neveu ou nièce', ab: 'neveu_niece', bar: 'p4', abD: 'neveu_niece' },
    parent4: { lab: 'Autre parent jusqu\'au 4e degré', ab: 'autre', bar: 'p4', abD: 'autre' }, tiers: { lab: 'Tiers, concubin ou parent au-delà du 4e degré', ab: 'autre', bar: 'autres', abD: 'autre' }
  };
  const tr = (base, bar) => { // barème progressif : { droits, detail }
    let d = 0, prev = 0; const det = [];
    for (let i = 0; i <= bar.seuils.length; i++) {
      const top = i < bar.seuils.length ? bar.seuils[i] : Infinity, part = Math.max(0, Math.min(base, top) - prev);
      if (part > 0) { const dr = part * bar.taux[i] / 100; d += dr; det.push([prev, top, bar.taux[i], part, dr]); }
      prev = top; if (base <= top) break;
    }
    return { droits: d, detail: det };
  };
  const barFor = (lien, don) => { const t = LIENS[lien].bar; if (t === 'direct') return B.bareme_direct; if (t === 'fratrie') return B.bareme_fratrie; return { seuils: [], taux: [t === 'p4' ? B.taux_4e_degre : B.taux_autres] }; };
  const taxOn = (base, bar) => tr(base, bar).droits;
  /** Droits de succession ou de donation sur une part, avec rappel fiscal des donations antérieures (seul l'accroissement est taxé). */
  function droits(lien, part, prev, handi, don, exoFs) {
    const L = LIENS[lien]; if (L.exo || (lien === 'frere_soeur' && exoFs && !don)) return { exo: true, abat: 0, base: 0, droits: 0, detail: [] };
    let abat = don && lien === 'conjoint' ? 0 : B.abattements[don ? L.abD : L.ab] || 0; if (handi) abat += B.abattements.handicap;
    const bar = barFor(lien, don), base = Math.max(0, prev + part - abat), prior = Math.max(0, prev - abat);
    const dr = Math.max(0, taxOn(base, bar) - taxOn(prior, bar));
    return { exo: false, abat, base: Math.max(0, base - prior), droits: dr, detail: tr(base, bar).detail };
  }
  const usu = age => { for (let i = 0; i < B.usufruit_ages.length; i++) if (age <= B.usufruit_ages[i]) return B.usufruit_pct[i]; return B.usufruit_pct[B.usufruit_pct.length - 1]; };

  // ----- état -----
  const S = { tab: 'succ', opt: 'quart', extra: {}, avx: [], manuel: [{ k: 'm0', nom: 'Héritier 1', lien: 'enfant', part: 0 }] };
  const val = id => { const e = $(id); return e ? (e.type === 'checkbox' ? e.checked : e.value) : ''; };
  const STATIC = ['s_sit', 's_age', 's_enf', 's_communs', 's_pere', 's_mere', 's_fs', 's_mode', 'p_rp', 'p_immo', 'p_fin', 'p_cpt', 'p_aut', 'p_dettes', 'p_rpocc', 'p_fun', 'd_lien', 'd_mont', 'd_nu', 'd_age', 'd_prev', 'd_handi', 'd_fam', 'd_famprev'];
  const getEx = k => S.extra[k] || (S.extra[k] = { handi: false, dons: 0, av990: 0, av757: 0, exo: false });

  function patrimoine() {
    const rp = num(val('p_rp')), gross = rp + num(val('p_immo')) + num(val('p_fin')) + num(val('p_cpt')) + num(val('p_aut')), abatRp = val('p_rpocc') ? rp * B.residence_principale_pct / 100 : 0;
    const fun = val('p_fun') ? B.frais_funeraires : 0, dettes = num(val('p_dettes'));
    return { gross, abatRp, fun, dettes, net: Math.max(0, gross - abatRp - fun - dettes) };
  }
  /** Dévolution légale : [{ k, lab, lien, part, nuprop? }] selon la situation et l'option du conjoint ('usufruit' | 'quart'). */
  function devolution(net, opt) {
    const sit = val('s_sit'), n = Math.max(0, Math.floor(num(val('s_enf')))), age = num(val('s_age')), out = [], notes = [];
    const marie = sit === 'marie', communs = val('s_communs');
    if (sit === 'pacs') notes.push('Le partenaire de Pacs n\'hérite pas sans testament : choisissez la répartition personnalisée s\'il est légataire (il est alors exonéré de droits).');
    if (n > 0) {
      if (marie) {
        if (opt === 'usufruit' && communs) { const u = usu(age) / 100; out.push({ k: 'conj', lab: 'Conjoint (usufruit de la totalité)', lien: 'conjoint', part: net * u }); for (let i = 0; i < n; i++) out.push({ k: 'enf' + i, lab: 'Enfant ' + (i + 1) + ' (nue-propriété)', lien: 'enfant', part: net * (1 - u) / n }); }
        else { out.push({ k: 'conj', lab: 'Conjoint (un quart en pleine propriété)', lien: 'conjoint', part: net / 4 }); for (let i = 0; i < n; i++) out.push({ k: 'enf' + i, lab: 'Enfant ' + (i + 1), lien: 'enfant', part: net * 0.75 / n }); if (opt === 'usufruit' && !communs) notes.push('Avec des enfants non communs, le conjoint ne peut recevoir qu\'un quart en pleine propriété.'); }
      } else for (let i = 0; i < n; i++) out.push({ k: 'enf' + i, lab: 'Enfant ' + (i + 1), lien: 'enfant', part: net / n });
      return { out, notes };
    }
    const pere = val('s_pere'), mere = val('s_mere'), fs = Math.max(0, Math.floor(num(val('s_fs')))), np = (pere ? 1 : 0) + (mere ? 1 : 0);
    if (marie) { // conjoint sans enfant : les frères et sœurs sont écartés
      const cp = np === 2 ? 0.5 : np === 1 ? 0.75 : 1; out.push({ k: 'conj', lab: 'Conjoint', lien: 'conjoint', part: net * cp });
      if (pere) out.push({ k: 'pere', lab: 'Père', lien: 'parent', part: net * 0.25 }); if (mere) out.push({ k: 'mere', lab: 'Mère', lien: 'parent', part: net * 0.25 }); return { out, notes };
    }
    const sPart = np === 2 ? 0.5 : np === 1 ? 0.75 : 1;
    if (pere) out.push({ k: 'pere', lab: 'Père', lien: 'parent', part: net * 0.25 }); if (mere) out.push({ k: 'mere', lab: 'Mère', lien: 'parent', part: net * 0.25 });
    if (fs > 0) for (let i = 0; i < fs; i++) out.push({ k: 'fs' + i, lab: 'Frère ou sœur ' + (i + 1), lien: 'frere_soeur', part: net * sPart / fs });
    else if (np === 1) out.forEach(h => h.part = net); else if (np === 0) notes.push('Sans conjoint, enfant, parent ni frère ou sœur : la succession revient à d\'autres parents (grands-parents, oncles, cousins…). Utilisez la répartition personnalisée.');
    return { out, notes };
  }
  /** Calcule droits de succession + assurance-vie pour une liste d'héritiers [{k, lab, lien, part}]. */
  function calcHeirs(list, net) {
    const rows = list.map(h => { const x = getEx(h.k), L = LIENS[h.lien], exo = L.exo || (h.lien === 'frere_soeur' && x.exo); return { h, x, exo }; });
    // assurance-vie, primes après 70 ans : abattement global puis réintégration dans la part de chaque bénéficiaire
    const tax757 = rows.reduce((t, r) => t + num(r.x.av757), 0), exc = Math.max(0, tax757 - B.av_757b_abattement);
    rows.forEach(r => { r.p757 = num(r.x.av757); r.abat757 = tax757 > 0 ? Math.min(r.p757, B.av_757b_abattement * r.p757 / tax757) : 0; r.reint = tax757 > 0 && !r.exo ? exc * r.p757 / tax757 : 0; });
    rows.forEach(r => {
      const part = r.h.part + r.reint, d = droits(r.h.lien, part, num(r.x.dons), r.x.handi, false, r.x.exo);
      r.part = part; r.d = d; const b = r.exo ? 0 : Math.max(0, num(r.x.av990) - AV.abat);
      r.av = Math.min(b, AV.seuil) * AV.t1 / 100 + Math.max(0, b - AV.seuil) * AV.t2 / 100; r.avBase = b; r.av990 = r.exo ? 0 : num(r.x.av990);
      r.net = r.h.part + num(r.x.av990) + num(r.x.av757) - d.droits - r.av;
    });
    return { rows, total: rows.reduce((t, r) => t + r.d.droits + r.av, 0), recu: rows.reduce((t, r) => t + r.h.part + num(r.x.av990) + num(r.x.av757), 0) };
  }

  function listHeirs(net) {
    const xs = S.avx.map(m => ({ k: m.k, lab: m.nom || LIENS[m.lien].lab, lien: m.lien, part: 0, extra: true }));
    if (val('s_mode') === 'manuel') return { list: S.manuel.map(m => ({ k: m.k, lab: m.nom || LIENS[m.lien].lab, lien: m.lien, part: num(m.part) })).concat(xs), notes: [] };
    const o = S.opt, dv = devolution(net, o); return { list: dv.out.concat(xs), notes: dv.notes };
  }
  // ----- affichage -----
  function drawHeirs(list) {
    const man = val('s_mode') === 'manuel';
    const cols = '<th>Héritier</th>' + (man ? '<th>Lien</th><th>Part reçue (€)</th>' : '<th class="sc-r">Part</th>') + '<th title="Situation de handicap">Handicap</th><th>Donations reçues (€)</th><th title="Frère ou sœur remplissant les conditions d\'exonération">Exo. fratrie</th>' + (man ? '<th></th>' : '');
    const hl = list.filter(h => !h.extra);
    $('sc-heirs').innerHTML = '<table class="sc-table"><thead><tr>' + cols + '</tr></thead><tbody>' + hl.map((h, i) => { const x = getEx(h.k), L = LIENS[h.lien];
      return '<tr data-k="' + h.k + '"><td>' + (man ? '<input class="sc-nom form-control form-control-sm" data-m="nom" value="' + esc(S.manuel[i].nom) + '" aria-label="Nom">' : esc(h.lab)) + '</td>'
        + (man ? '<td><select class="form-select form-select-sm" data-m="lien" aria-label="Lien">' + Object.keys(LIENS).map(k => '<option value="' + k + '"' + (k === h.lien ? ' selected' : '') + '>' + LIENS[k].lab + '</option>').join('') + '</select></td><td><input type="number" min="0" step="1000" class="form-control form-control-sm" data-m="part" value="' + (S.manuel[i].part || '') + '" aria-label="Part reçue"></td>' : '<td class="sc-r">' + fmt(h.part) + '</td>')
        + '<td class="text-center"><input type="checkbox" data-x="handi"' + (x.handi ? ' checked' : '') + (L.exo ? ' disabled' : '') + ' aria-label="Handicap"></td>'
        + ['dons'].map(f => '<td><input type="number" min="0" step="1000" class="form-control form-control-sm" data-x="' + f + '" value="' + (x[f] || '') + '"' + (L.exo && f !== 'dons' ? ' ' : '') + ' aria-label="' + f + '"></td>').join('')
        + '<td class="text-center">' + (h.lien === 'frere_soeur' ? '<input type="checkbox" data-x="exo"' + (x.exo ? ' checked' : '') + ' aria-label="Exonération fratrie">' : '') + '</td>' + (man ? '<td><button type="button" class="btn btn-sm btn-link text-danger p-0" data-delm="' + i + '" title="Retirer"><i class="fas fa-trash"></i></button></td>' : '') + '</tr>'; }).join('') + '</tbody></table>'
      + (man ? '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="sc-addm"><i class="fas fa-plus me-1"></i>Ajouter un héritier ou un légataire</button><div class="small text-muted mt-1">Les parts saisies sont celles que chacun reçoit, avant droits (le total ne doit pas dépasser l\'actif net).</div>' : '');
    $('sc-av').innerHTML = '<table class="sc-table"><thead><tr><th>Bénéficiaire du contrat</th><th title="Article 990 I : capital décès (primes et gains) des contrats alimentés avant 70 ans">Primes versées avant 70 ans : capital décès (€)</th><th title="Article 757 B : seules les primes comptent, pas les gains">Primes versées après 70 ans, hors gains (€)</th><th></th></tr></thead><tbody>' + list.map(h => { const x = getEx(h.k), L = LIENS[h.lien], ex = !!h.extra, i = S.avx.findIndex(m => m.k === h.k);
      return '<tr data-k="' + h.k + '"><td>' + (ex ? '<input class="sc-nom form-control form-control-sm mb-1" data-a="nom" value="' + esc(S.avx[i].nom) + '" aria-label="Nom du bénéficiaire"><select class="form-select form-select-sm" data-a="lien" aria-label="Lien">' + Object.keys(LIENS).map(k => '<option value="' + k + '"' + (k === h.lien ? ' selected' : '') + '>' + LIENS[k].lab + '</option>').join('') + '</select>' : esc(h.lab))
        + (L.exo ? '<div class="small text-success">exonéré</div>' : '') + '</td>'
        + ['av990', 'av757'].map(f => '<td><input type="number" min="0" step="1000" class="form-control form-control-sm" data-x="' + f + '" value="' + (x[f] || '') + '" aria-label="' + f + '"></td>').join('')
        + '<td>' + (ex ? '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-delav="' + i + '" title="Retirer"><i class="fas fa-trash"></i></button>' : '') + '</td></tr>'; }).join('') + '</tbody></table>'
      + '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="sc-addav"><i class="fas fa-plus me-1"></i>Ajouter un bénéficiaire qui n\'est pas héritier (tiers, petit-enfant, concubin…)</button>';
  }
  const ORDRE = { conjoint: 'Conjoint survivant (hors ordres)', pacs: 'Partenaire de Pacs (hors ordres)', enfant: '1er ordre : descendants', petit_enfant: '1er ordre : descendants', arriere_petit_enfant: '1er ordre : descendants', parent: '2e ordre : parents', frere_soeur: '2e ordre : frères et sœurs', neveu_niece: 'Collatéral privilégié par représentation', parent4: '3e et 4e ordres : ascendants et collatéraux ordinaires', tiers: 'Hors famille ou au-delà du 4e degré' };
  const barTxt = lien => { const L = LIENS[lien]; if (L.exo) return 'exonéré'; const b = barFor(lien, false); return b.seuils.length ? b.taux.map((t, i) => t + ' % ' + (i < b.seuils.length ? (i ? 'de ' + fmt(b.seuils[i - 1]) + ' à ' + fmt(b.seuils[i]) : 'jusqu\'à ' + fmt(b.seuils[0])) : 'au-delà de ' + fmt(b.seuils[i - 1]))).join(' · ') : b.taux[0] + ' % dès le 1er euro taxable'; };
  const rangHtml = r => { const L = LIENS[r.h.lien]; const tot = r.d.droits + r.av, det = r.d.detail, marg = det.length ? det[det.length - 1][2] : 0, base = r.h.part + num(r.x.av990) + num(r.x.av757);
    return '<div class="small"><span class="badge bg-secondary me-1">' + ORDRE[r.h.lien] + '</span>' + (L.exo ? 'aucune imposition' : 'abattement ' + fmt(r.d.abat) + ' · taux marginal ' + (marg ? marg + ' %' : '0 %') + ' · taux effectif ' + pc(base > 0 ? tot / base * 100 : 0)) + '</div>'; };
  const refTable = () => '<details class="mt-3 sc-d"><summary>Barème appliqué selon le rang de parenté</summary><table class="sc-table mt-1"><thead><tr><th>Lien</th><th class="sc-r">Abattement</th><th>Barème des droits</th></tr></thead><tbody>' + Object.keys(LIENS).map(k => '<tr><td>' + LIENS[k].lab + '<div class="small text-muted">' + ORDRE[k] + '</div></td><td class="sc-r">' + (LIENS[k].exo ? '–' : fmt(B.abattements[LIENS[k].ab])) + '</td><td class="small">' + barTxt(k) + '</td></tr>').join('') + '</tbody></table><div class="small text-muted">Abattements applicables à une succession ; le handicap ajoute ' + fmt(B.abattements.handicap) + '. Les frères et sœurs vivant avec le défunt (célibataires, veufs ou divorcés, de plus de 50 ans ou invalides, domiciliés ensemble les 5 dernières années) sont exonérés : cochez « Exo. fratrie ».</div></details>';
  const avHtml = r => { let t = ''; const a = num(r.x.av990), p = num(r.x.av757); if (!a && !p) return '';
    if (r.exo) return '<div class="small text-success">Assurance-vie : ' + fmt(a + p) + ' transmis sans taxe (conjoint ou partenaire de Pacs).</div>';
    if (a) t += '<div class="small text-muted"><b>Art. 990 I</b> : capital ' + fmt(a) + ' − abattement ' + fmt(AV.abat) + ' = ' + fmt(r.avBase) + ' taxables' + (r.avBase ? ' → ' + (r.avBase > AV.seuil ? fmt(AV.seuil) + ' × ' + AV.t1 + ' % + ' + fmt(r.avBase - AV.seuil) + ' × ' + AV.t2 + ' %' : AV.t1 + ' %') + ' = <b>' + fmt(r.av) + '</b>' : ' : aucune taxe') + '</div>';
    if (p) t += '<div class="small text-muted"><b>Art. 757 B</b> : primes ' + fmt(p) + ' − quote-part de l\'abattement de ' + fmt(B.av_757b_abattement) + ' (' + fmt(r.abat757) + ') = ' + fmt(r.reint) + ' ajoutés à la part successorale' + (r.reint ? ' et taxés avec elle' : ' : aucune taxe') + '</div>';
    return '<div>' + t + '</div>'; };
  const detHtml = d => d.length ? '<details class="sc-d"><summary>Détail du calcul</summary><table class="sc-det">' + d.map(t => '<tr><td>' + (t[1] === Infinity ? 'au-delà de ' + fmt(t[0]) : 'de ' + fmt(t[0]) + ' à ' + fmt(t[1])) + '</td><td class="sc-r">' + fmt(t[3]) + ' × ' + t[2] + ' %</td><td class="sc-r">' + fmt(t[4]) + '</td></tr>').join('') + '</table></details>' : '';

  function runSucc() {
    const P = patrimoine(), man = val('s_mode') === 'manuel';
    $('sc-opt').innerHTML = '';
    const marie = val('s_sit') === 'marie', enf = Math.floor(num(val('s_enf'))) > 0;
    let cmp = null;
    if (!man && marie && enf) {
      const mk = o => { const dv = devolution(P.net, o); return calcHeirs(dv.out, P.net); };
      cmp = { quart: mk('quart'), usufruit: val('s_communs') ? mk('usufruit') : null };
      $('sc-opt').innerHTML = '<label class="form-label small mb-0" for="s_opt">Option choisie par le conjoint survivant</label><select id="s_opt" class="form-select form-select-sm" style="max-width:420px"><option value="quart"' + (S.opt === 'quart' ? ' selected' : '') + '>Un quart en pleine propriété (les enfants reçoivent les trois quarts)</option>' + (val('s_communs') ? '<option value="usufruit"' + (S.opt === 'usufruit' ? ' selected' : '') + '>Usufruit sur la totalité des biens</option>' : '') + '</select>';
      if (!val('s_communs')) S.opt = 'quart';
    }
    const { list, notes } = listHeirs(P.net);
    const res = calcHeirs(list, P.net);
    if (!noDraw) drawHeirs(list);
    const warn = [...notes]; if (man) { const sum = list.reduce((t, h) => t + h.part, 0); if (sum > P.net + 1) warn.push('Le total des parts (' + fmt(sum) + ') dépasse l\'actif net (' + fmt(P.net) + ').'); else if (P.net - sum > 1) warn.push('Il reste ' + fmt(P.net - sum) + ' non attribués dans l\'actif net.'); }
    if (res.rows.some(r => r.x.handi)) warn.push('Le supplément d\'abattement pour handicap suppose que la situation de handicap soit établie au sens fiscal.');
    let h = '<h2><i class="fas fa-chart-pie me-2 text-danger"></i>Résultat</h2>'
      + '<div class="d-flex justify-content-between"><span>Actif brut</span><b>' + fmt(P.gross) + '</b></div>' + (P.abatRp ? '<div class="d-flex justify-content-between"><span>Abattement résidence principale</span><span>− ' + fmt(P.abatRp) + '</span></div>' : '') + (P.dettes + P.fun ? '<div class="d-flex justify-content-between"><span>Dettes et frais funéraires</span><span>− ' + fmt(P.dettes + P.fun) + '</span></div>' : '')
      + '<div class="d-flex justify-content-between border-top mt-1 pt-1"><span>Actif net successoral</span><b>' + fmt(P.net) + '</b></div>'
      + '<div class="text-center my-3"><div class="small text-muted">Droits totaux à payer</div><div class="sc-big">' + fmt(res.total) + '</div><div class="small text-muted">soit ' + pc(res.recu > 0 ? res.total / res.recu * 100 : 0) + ' de ce qui est transmis</div></div>'
      + '<table class="sc-table"><thead><tr><th>Héritier</th><th class="sc-r">Reçoit</th><th class="sc-r">Droits</th><th class="sc-r">Net</th></tr></thead><tbody>'
      + res.rows.map(r => '<tr><td>' + esc(r.h.lab) + (r.exo ? ' <span class="text-success small">exonéré</span>' : '') + '</td><td class="sc-r">' + fmt(r.h.part + num(r.x.av990) + num(r.x.av757)) + '</td><td class="sc-r">' + fmt(r.d.droits + r.av) + '</td><td class="sc-r">' + fmt(r.net) + '</td></tr>'
        + (r.exo ? (true ? '<tr><td colspan="4">' + rangHtml(r) + avHtml(r) + '</td></tr>' : '') : '<tr><td colspan="4">' + rangHtml(r) + (r.d.abat ? '<div class="small text-muted">Abattement ' + fmt(r.d.abat) + (num(r.x.dons) ? ' (donations antérieures ' + fmt(num(r.x.dons)) + ' prises en compte)' : '') + ' · part taxable ' + fmt(r.d.base) + (r.reint ? ' · dont ' + fmt(r.reint) + ' d\'assurance-vie après 70 ans' : '') + '</div>' : '') + avHtml(r) + detHtml(r.d.detail) + '</td></tr>')).join('')
      + '<tr class="sc-tot"><td>Total</td><td class="sc-r">' + fmt(res.recu) + '</td><td class="sc-r">' + fmt(res.total) + '</td><td class="sc-r">' + fmt(res.recu - res.total) + '</td></tr></tbody></table>';
    if (cmp && cmp.usufruit) { const a = cmp.quart.total, b = cmp.usufruit.total, best = a <= b ? 'quart' : 'usufruit';
      h += '<div class="mt-3"><b>Option du conjoint survivant</b><table class="sc-table"><thead><tr><th>Option</th><th class="sc-r">Droits des enfants</th></tr></thead><tbody><tr><td>Un quart en pleine propriété' + (best === 'quart' ? '<span class="sc-best">moins de droits</span>' : '') + '</td><td class="sc-r">' + fmt(a) + '</td></tr><tr><td>Usufruit de la totalité' + (best === 'usufruit' ? '<span class="sc-best">moins de droits</span>' : '') + '</td><td class="sc-r">' + fmt(b) + '</td></tr></tbody></table><div class="small text-muted">À la fin de l\'usufruit, la pleine propriété est reconstituée chez les enfants sans nouveaux droits (valeur de l\'usufruit selon l\'âge du conjoint). Le choix dépend aussi des besoins du conjoint.</div></div>'; }
    h += refTable();
    h += warn.map(w => '<div class="sc-warn"><i class="fas fa-triangle-exclamation me-1"></i>' + esc(w) + '</div>').join('');
    $('sc-res').innerHTML = h;
    window.__scLast = { P, res, cmp, warn };
    sync();
  }
  function runDon() {
    const lien = val('d_lien'), L = LIENS[lien], mont = num(val('d_mont')), np = val('d_nu') === 'np', age = num(val('d_age')), u = np ? usu(age) / 100 : 0;
    const valeur = mont * (1 - u), prev = num(val('d_prev')), handi = val('d_handi');
    let fam = 0; if (val('d_fam') && !(lien === 'conjoint' || lien === 'pacs')) fam = Math.max(0, B.abattements.don_familial - num(val('d_famprev')));
    const conj = lien === 'conjoint' || lien === 'pacs';
    const calc = () => { let abat = conj ? B.abattements.conjoint_donation : (B.abattements[L.abD] || 0); if (handi) abat += B.abattements.handicap; abat += Math.min(fam, valeur); const bar = conj ? B.bareme_conjoint_donation : barFor(lien, true); const base = Math.max(0, prev + valeur - abat), prior = Math.max(0, prev - abat); return { abat, base: base - prior, droits: Math.max(0, taxOn(base, bar) - taxOn(prior, bar)), detail: tr(base, bar).detail }; };
    const d = calc(), s = droits(conj ? 'conjoint' : lien, valeur, prev, handi, false, false), sd = conj ? 0 : s.droits;
    const eco = sd - d.droits;
    $('sd-res').innerHTML = '<h2><i class="fas fa-chart-pie me-2 text-danger"></i>Résultat</h2>'
      + '<div class="d-flex justify-content-between"><span>Valeur donnée (' + (np ? 'nue-propriété' : 'pleine propriété') + ')</span><b>' + fmt(valeur) + '</b></div>' + (np ? '<div class="small text-muted">Usufruit conservé : ' + pc(u * 100) + ' de la valeur (donateur de ' + age + ' ans).</div>' : '')
      + '<div class="d-flex justify-content-between"><span>Abattements applicables</span><span>' + fmt(d.abat) + '</span></div><div class="d-flex justify-content-between"><span>Part taxable (après rappel des donations)</span><span>' + fmt(d.base) + '</span></div>'
      + '<div class="text-center my-3"><div class="small text-muted">Droits de donation</div><div class="sc-big">' + fmt(d.droits) + '</div><div class="small text-muted">Net reçu par le bénéficiaire : ' + fmt(valeur - d.droits) + '</div></div>' + detHtml(d.detail)
      + (conj ? '<div class="sc-warn">Entre époux ou partenaires de Pacs, la succession est exonérée de droits : la donation sert surtout à transmettre un bien à la valeur du jour ou à anticiper.</div>'
        : '<div class="mt-3 border-top pt-2"><div class="d-flex justify-content-between"><span>Droits si cette valeur était transmise au décès</span><b>' + fmt(sd) + '</b></div><div class="d-flex justify-content-between"><span>Économie liée à la donation</span><b class="' + (eco > 0 ? 'text-success' : '') + '">' + fmt(Math.max(0, eco)) + '</b></div><div class="small text-muted mt-1">Les abattements de donation se renouvellent tous les ' + B.rappel_ans + ' ans : donner tôt et de façon échelonnée permet de les utiliser plusieurs fois. L\'économie est réelle si le donateur survit à la donation de plus de ' + B.rappel_ans + ' ans (sinon la donation est rappelée à la succession).</div></div>');
    window.__sdLast = { valeur, d, sd, eco };
    sync();
  }
  function sync() { $('sc-succ').querySelectorAll('.sc-ifmarie').forEach(e => e.style.display = val('s_sit') === 'marie' ? '' : 'none'); const enf = num(val('s_enf')) > 0;
    $('sc-succ').querySelectorAll('.sc-ifenf').forEach(e => { if (val('s_sit') === 'marie') e.style.display = enf ? '' : 'none'; }); $('sc-succ').querySelectorAll('.sc-ifnoenf').forEach(e => e.style.display = enf ? 'none' : '');
    document.querySelectorAll('.sc-ifnp').forEach(e => e.style.display = val('d_nu') === 'np' ? '' : 'none'); document.querySelectorAll('.sc-iffam').forEach(e => e.style.display = val('d_fam') ? '' : 'none'); }
  let noDraw = false;
  const run = () => { S.tab === 'succ' ? runSucc() : runDon(); };

  // liens de la donation
  $('d_lien').innerHTML = Object.keys(LIENS).map(k => '<option value="' + k + '"' + (k === 'enfant' ? ' selected' : '') + '>' + LIENS[k].lab + '</option>').join('');
  document.addEventListener('click', e => {
    const t = e.target.closest('[data-tab]'); if (t) { S.tab = t.dataset.tab; $('sc-succ').style.display = S.tab === 'succ' ? '' : 'none'; $('sc-don').style.display = S.tab === 'don' ? '' : 'none'; document.querySelectorAll('.sc-tabs [data-tab]').forEach(b => b.className = 'btn btn-sm ' + (b.dataset.tab === S.tab ? 'btn-danger' : 'btn-outline-secondary')); run(); return; }
    if (e.target.closest('#sc-addm')) { S.manuel.push({ k: 'm' + Date.now(), nom: 'Héritier ' + (S.manuel.length + 1), lien: 'enfant', part: 0 }); run(); return; }
    if (e.target.closest('#sc-addav')) { S.avx.push({ k: 'a' + Date.now(), nom: 'Bénéficiaire ' + (S.avx.length + 1), lien: 'tiers' }); run(); return; }
    const da = e.target.closest('[data-delav]'); if (da) { delete S.extra[S.avx[+da.dataset.delav].k]; S.avx.splice(+da.dataset.delav, 1); run(); return; }
    const d = e.target.closest('[data-delm]'); if (d) { S.manuel.splice(+d.dataset.delm, 1); if (!S.manuel.length) S.manuel.push({ k: 'm' + Date.now(), nom: 'Héritier 1', lien: 'enfant', part: 0 }); run(); }
  });
  document.addEventListener('input', e => {
    const x = e.target.closest('[data-x]'), m = e.target.closest('[data-m],[data-a]');
    if (x || m) { const tr0 = e.target.closest('tr'), k = tr0.dataset.k;
      if (x) { const f = x.dataset.x; getEx(k)[f] = x.type === 'checkbox' ? x.checked : num(x.value); }
      else { const arr = m.dataset.a ? S.avx : S.manuel, f = m.dataset.a || m.dataset.m, i = arr.findIndex(h => h.k === k); arr[i][f] = f === 'part' ? num(m.value) : m.value; if (f === 'lien') { run(); return; } }
      noDraw = true; try { run(); } finally { noDraw = false; } return; }
    if (e.target.id === 's_opt') { S.opt = e.target.value; run(); return; }
    if (STATIC.includes(e.target.id)) run();
  });
  document.addEventListener('change', e => { if (e.target.id === 's_opt') { S.opt = e.target.value; run(); } else if (e.target.closest('#sc-heirs') && e.target.type === 'checkbox') run(); else if (STATIC.includes(e.target.id) && (e.target.tagName === 'SELECT' || e.target.type === 'checkbox')) run(); });

  // enregistrement (portail), lien de partage et document imprimé
  const snapshot = () => { const o = { S: { tab: S.tab, opt: S.opt, extra: S.extra, avx: S.avx, manuel: S.manuel }, f: {} }; STATIC.forEach(id => o.f[id] = val(id)); return o; };
  const restore = o => { if (!o || !o.S) return; Object.assign(S, o.S); Object.keys(o.f || {}).forEach(id => { const e = $(id); if (!e) return; if (e.type === 'checkbox') e.checked = !!o.f[id]; else e.value = o.f[id]; }); document.querySelector('.sc-tabs [data-tab="' + S.tab + '"]').click(); };
  window.toolsShare = { get: snapshot, set: restore };
  window.scPrepare = f => { run(); const p = JSON.stringify(snapshot()); if (p.length > 5800) { alert('Cette simulation est trop volumineuse pour être enregistrée (réduisez le nombre d\'héritiers).'); return false; } f.params.value = p;
    const r = S.tab === 'succ' ? window.__scLast : window.__sdLast; f.resultat.value = JSON.stringify(S.tab === 'succ' ? { actif: Math.round(r.P.net), droits: Math.round(r.res.total) } : { actif: Math.round(r.valeur), droits: Math.round(r.d.droits) }); return true; };
  window.toolsDoc = () => { const rows = []; const lab = (id) => (document.querySelector('label[for="' + id + '"]') || {}).textContent || id;
    if (S.tab === 'succ') { rows.push(['Situation', $('s_sit').selectedOptions[0].textContent], ['Enfants', val('s_enf')], ['Répartition', $('s_mode').selectedOptions[0].textContent]); if (window.__scLast) rows.push(['Actif net successoral', fmt(window.__scLast.P.net)]);
      (window.__scLast ? window.__scLast.res.rows : []).forEach(r => rows.push([r.h.lab, 'part ' + fmt(r.h.part) + (r.x.handi ? ', handicap' : '') + (num(r.x.dons) ? ', donations ' + fmt(num(r.x.dons)) : '') + (num(r.x.av990) + num(r.x.av757) ? ', assurance-vie ' + fmt(num(r.x.av990) + num(r.x.av757)) : '')])); }
    else rows.push(['Lien', $('d_lien').selectedOptions[0].textContent], ['Valeur donnée', fmt(num(val('d_mont')))], ['Forme', $('d_nu').selectedOptions[0].textContent]);
    return { title: S.tab === 'succ' ? 'Simulation de droits de succession' : 'Simulation de droits de donation', inputs: rows }; };
  if (LOAD) restore(LOAD); else sync();
  run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
