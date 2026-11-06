<?php
// Fiches vierges à imprimer sur une feuille A4 (instances, production, phoning), partagées par le portail et /tools.
// Maquettes papier pour remplacer les modules du portail ; les instances peuvent aussi être transmises par e-mail à un collaborateur.
// Variable attendue : $ficheTypes (liste parmi instances, production, phoning ; une seule dans /tools).
$ficheTypes = $ficheTypes ?? ['instances', 'production', 'phoning'];
require __DIR__ . '/../_sim/style.php';
$actionsOpts = ['link' => true, 'copy' => false, 'print' => true, 'mail' => false];
?>
<style>
.fe-ctl .form-label{margin-bottom:2px}
#fe-sheet{background:#fff;border:1px solid #cfd4dc;border-radius:6px;padding:10mm;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow-x:auto;color:#111}
.fe-t{width:100%;border-collapse:collapse;table-layout:fixed;font-size:9.5pt}
.fe-t th{background:#eceff4;border:1px solid #444;padding:3px 4px;font-size:8.5pt;text-transform:uppercase;letter-spacing:.2px;text-align:left}
.fe-t td{border:1px solid #444;padding:0;vertical-align:top;height:var(--rh,9.5mm)}
.fe-t input[type=text],.fe-t input[type=date],.fe-t textarea{width:100%;height:100%;border:0;background:transparent;font:inherit;padding:2px 4px;resize:none;color:#111;outline:none}
.fe-t input:focus,.fe-t textarea:focus{background:#fffbe6}
.fe-t textarea{display:block;overflow:hidden;line-height:1.15}
.fe-cb{display:flex;flex-wrap:wrap;gap:1px 7px;padding:2px 4px;font-size:7.5pt;line-height:1.25}.fe-cb label{display:inline-flex;align-items:center;gap:2px;margin:0;white-space:nowrap;cursor:pointer}.fe-cb input{margin:0;width:11px;height:11px}
.fe-head{display:flex;justify-content:space-between;align-items:flex-end;gap:10mm;border-bottom:3px solid #24285b;padding-bottom:3mm;margin-bottom:4mm}
.fe-head h2{font-size:17pt;font-weight:800;margin:0;color:#24285b}.fe-head .sub{font-size:8.5pt;color:#555}
.fe-ident{display:grid;grid-template-columns:repeat(4,1fr);gap:3mm 6mm;margin-bottom:4mm}
.fe-ident label{font-size:7.5pt;text-transform:uppercase;color:#555;display:block;margin:0}
.fe-ident input{width:100%;border:0;border-bottom:1px solid #444;background:transparent;font:inherit;padding:2px 0;height:6.5mm}
.fe-sum{display:grid;grid-template-columns:repeat(4,1fr);gap:3mm;margin-top:5mm}
.fe-sum div{border:1px solid #444;padding:2mm 3mm;font-size:8.5pt;min-height:13mm}.fe-sum b{display:block;font-size:7.5pt;text-transform:uppercase;color:#555}
.fe-memo{margin-top:5mm;font-size:7.5pt;columns:4;column-gap:5mm;color:#333}.fe-memo div{break-inside:avoid;line-height:1.5}
.fe-foot{margin-top:4mm;font-size:7.5pt;color:#666;display:flex;justify-content:space-between}
.fe-tabs .btn{border-radius:20px}
#fe-mail textarea{min-height:90px}
@media print{
  body{background:#fff!important;margin:0}
  body > *:not(.container-fluid):not(.container){display:none!important}
  .container-fluid > *:not(#fe-sheet),.container > *:not(#fe-sheet){display:none!important}
  #fe-sheet{border:0;border-radius:0;box-shadow:none;padding:0;overflow:visible}
  .fe-t input:focus,.fe-t textarea:focus{background:transparent}
  .fe-t th,.fe-sum div{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
<div class="fe-ctl no-print">
 <?php if (count($ficheTypes) > 1): ?>
 <div class="fe-tabs btn-group mb-3" role="tablist">
  <?php $lib = ['instances' => ['Instances', 'fa-hourglass-half'], 'production' => ['Production', 'fa-chart-line'], 'phoning' => ['Phoning', 'fa-phone-volume']]; foreach ($ficheTypes as $i => $t): ?>
  <button type="button" class="btn btn-sm <?= $i === 0 ? 'btn-danger' : 'btn-outline-secondary' ?>" data-fe-type="<?= e($t) ?>"><i class="fas <?= $lib[$t][1] ?> me-1"></i><?= e($lib[$t][0]) ?></button>
  <?php endforeach; ?>
 </div>
 <?php endif; ?>
 <?php require __DIR__ . '/../_sim/actions.php'; ?>
 <div class="cap-card"><h2><i class="fas fa-sliders me-2 text-danger"></i>Réglages de la feuille</h2>
  <div class="row g-2 align-items-end">
   <div class="col-md-3"><label class="form-label small" for="fe_cons">Conseiller</label><input type="text" id="fe_cons" class="form-control" maxlength="80" placeholder="Prénom NOM"></div>
   <div class="col-md-3"><label class="form-label small" for="fe_ag">Agence</label><input type="text" id="fe_ag" class="form-control" maxlength="80"></div>
   <div class="col-md-3"><label class="form-label small" for="fe_per" id="fe_per_lab">Période</label><input type="text" id="fe_per" class="form-control" maxlength="80" placeholder="Ex. semaine 46, novembre 2026"></div>
   <div class="col-6 col-md-1"><label class="form-label small" for="fe_rows">Lignes</label><input type="number" id="fe_rows" class="form-control" min="3" max="40"></div>
   <div class="col-6 col-md-2"><label class="form-label small" for="fe_or">Format</label><select id="fe_or" class="form-select"><option value="landscape">Paysage</option><option value="portrait">Portrait</option></select></div>
   <div class="col-12"><div class="form-check d-inline-block me-3" id="fe_memo_box"><input type="checkbox" class="form-check-input" id="fe_memo" checked><label class="form-check-label small" for="fe_memo">Aide-mémoire des produits en bas de feuille</label></div>
    <button type="button" class="btn btn-danger btn-sm" id="fe_print"><i class="fas fa-print me-1"></i>Imprimer la feuille (A4)</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="fe_clear"><i class="fas fa-eraser me-1"></i>Vider la feuille</button></div>
  </div>
  <div class="form-text mt-2">La feuille s'imprime telle quelle, vierge ou remplie à l'écran : tapez directement dans les cases. Dans la fenêtre d'impression, choisissez l'échelle 100 % (et non « Ajuster à la page »). Rien n'est enregistré.</div>
 </div>
</div>
<div id="fe-sheet" class="js-print" aria-label="Aperçu de la feuille A4"></div>
<div id="fe-mail" class="cap-card no-print mt-3" style="display:none"><h2><i class="fas fa-paper-plane me-2 text-danger"></i><span id="fm_titre">Transmettre mes instances à un collaborateur</span></h2>
 <p class="small text-muted mb-2"><span id="fm_intro">Avant un congé, envoyez la liste de vos instances à la personne qui vous remplace.</span> Seules les lignes renseignées sont transmises. Le bouton prépare un message prêt à envoyer : à vous de l'ouvrir dans votre messagerie et de cliquer sur « Envoyer ».</p>
 <div class="row g-2">
  <div class="col-md-6"><label class="form-label small" for="fm_collab">Collaborateur</label><select id="fm_collab" data-noshare class="form-select"></select></div>
  <div class="col-md-6"><label class="form-label small" for="fm_to">Adresse e-mail du destinataire</label><input type="email" id="fm_to" data-noshare class="form-control" maxlength="150" placeholder="prenom.nom@exemple.fr"></div>
  <div class="col-6 col-md-3 fm-abs"><label class="form-label small" for="fm_du">Absent du</label><input type="date" id="fm_du" data-noshare class="form-control"></div>
  <div class="col-6 col-md-3 fm-abs"><label class="form-label small" for="fm_au">au</label><input type="date" id="fm_au" data-noshare class="form-control"></div>
  <div class="col-12"><label class="form-label small" for="fm_msg">Message d'accompagnement</label><textarea id="fm_msg" data-noshare class="form-control" maxlength="1500"></textarea></div>
  <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
   <button type="button" class="btn btn-danger btn-sm" id="fm_eml"><i class="fas fa-file-arrow-down me-1"></i>Préparer l'e-mail (fichier .eml)</button>
   <button type="button" class="btn btn-outline-secondary btn-sm" id="fm_mailto"><i class="fas fa-envelope me-1"></i>Ouvrir dans ma messagerie</button>
   <span class="small text-muted" id="fm_info"></span></div>
 </div>
 <div class="form-text mt-2">Le fichier .eml s'ouvre d'un double-clic dans Outlook ou votre messagerie, avec le message déjà rédigé. « Ouvrir dans ma messagerie » convient aux listes courtes ; au-delà, utilisez le fichier .eml.</div>
</div>
<script>
(function () {
  'use strict';
  const $ = id => document.getElementById(id), esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  window.toolsOwnPrint = true;
  const TYPES = <?= json_encode(array_values($ficheTypes)) ?>;
  const CATS_I = ['Bancarisation', 'Épargne', 'IARD', 'Prévoyance', 'Placement', 'Crédit conso', 'Crédit immo'], CATS_I_C = ['Banca.', 'Épargne', 'IARD', 'Prév.', 'Placement', 'Cr. conso', 'Cr. immo'];
  const CATS_P = ['Banca', 'Épargne', 'Placement', 'Crédit', 'Assurance'];
  const PRODUITS = ['ANV', 'Prêt personnel (€)', 'Carte HDG', 'Carte DD', 'Izicarte', 'Forfait', 'Collecte (€)', 'Ouverture livret', 'Versement programmé livret', 'PEL / Quadreto', 'Versement assurance vie', 'Versement PERi', 'Ouverture assurance vie', 'Ouverture PERi', 'Abonnement assurance vie', 'Abonnement PERi', 'Versement parts sociales (€)', 'Nouveau sociétaire', 'Équipement', 'Équipement jeune', 'Bancarisé principal', 'Jeune bancarisé principal'];
  const RES = ['Répondu', 'Répondeur', 'Indisponible', 'RDV'], MOTIFS = ['Banca', 'Épargne', 'Placement', 'Crédit', 'Assurances'];
  const CFG = {
    instances: { titre: 'Suivi des instances', sub: 'Liste des instances à traiter', orient: 'landscape', rows: 12, per: 'Période', rh: '12mm' },
    production: { titre: 'Suivi de production', sub: 'Rendez-vous et ventes', orient: 'landscape', rows: 11, per: 'Mois ou semaine', rh: '9.5mm' },
    phoning: { titre: 'Séance de phoning', sub: 'Appels passés et rendez-vous obtenus', orient: 'portrait', rows: 12, per: 'Opération / titre de la séance', rh: '8.5mm' }
  };
  const S = { type: TYPES[0], head: {}, rows: {}, opts: {} };
  try { const q = new URLSearchParams(window.location.search).get('type'); if (q && TYPES.indexOf(q) >= 0) S.type = q; } catch (e) {}
  TYPES.forEach(t => { S.opts[t] = { rows: CFG[t].rows, orient: CFG[t].orient, memo: true }; S.rows[t] = []; });
  const today = () => new Date().toLocaleDateString('fr-FR');
  const cb = (name, list, sel, cls) => '<div class="fe-cb">' + list.map((l, k) => '<label><input type="checkbox" data-f="' + name + '" value="' + k + '"' + (sel && sel.indexOf(k) >= 0 ? ' checked' : '') + '>' + esc((cls || list)[k]) + '</label>').join('') + '</div>';
  const inp = (f, v, t) => '<input type="' + (t || 'text') + '" data-f="' + f + '" value="' + esc(v || '') + '">';
  const rowData = (i) => S.rows[S.type][i] || (S.rows[S.type][i] = {});

  function head() {
    const c = CFG[S.type], h = S.head;
    return '<div class="fe-head"><div><h2>' + esc(c.titre) + '</h2><div class="sub">' + esc(c.sub) + '</div></div><div class="sub">Édité le ' + today() + '</div></div>'
      + '<div class="fe-ident"><div><label>Conseiller</label><input type="text" value="' + esc(h.cons) + '" readonly tabindex="-1"></div><div><label>Agence</label><input type="text" value="' + esc(h.ag) + '" readonly tabindex="-1"></div><div style="grid-column:span 2"><label>' + esc(c.per) + '</label><input type="text" value="' + esc(h.per) + '" readonly tabindex="-1"></div></div>';
  }
  function body() {
    const n = S.opts[S.type].rows, R = i => rowData(i); let h = '<table class="fe-t" style="--rh:' + CFG[S.type].rh + '"><colgroup>';
    if (S.type === 'instances') {
      h += '<col style="width:12%"><col style="width:18%"><col style="width:11%"><col style="width:23%"><col style="width:26%"><col style="width:10%"></colgroup><thead><tr><th>Date d\'ajout</th><th>N° personne / Nom</th><th>Échéance</th><th>Catégorie</th><th>Détails</th><th>Statut</th></tr></thead><tbody>';
      for (let i = 0; i < n; i++) { const r = R(i); h += '<tr data-i="' + i + '"><td>' + inp('date', r.date, 'text') + '</td><td>' + inp('num', r.num) + '</td><td>' + inp('ech', r.ech, 'text') + '</td><td>' + cb('cats', CATS_I, r.cats, CATS_I_C) + '</td><td><textarea data-f="det" rows="2">' + esc(r.det) + '</textarea></td><td><div class="fe-cb" style="flex-direction:column"><label><input type="checkbox" data-f="stat" value="a_faire"' + (r.stat === 'a_faire' ? ' checked' : '') + '>À faire</label><label><input type="checkbox" data-f="stat" value="fait"' + (r.stat === 'fait' ? ' checked' : '') + '>Fait</label></div></td></tr>'; }
      h += '</tbody></table>';
    } else if (S.type === 'production') {
      h += '<col style="width:11%"><col style="width:23%"><col style="width:26%"><col style="width:12%"><col style="width:28%"></colgroup><thead><tr><th>Date du RDV</th><th>Catégorie</th><th>Produit vendu</th><th>Montant / nombre</th><th>Détails</th></tr></thead><tbody>';
      for (let i = 0; i < n; i++) { const r = R(i); h += '<tr data-i="' + i + '"><td>' + inp('date', r.date) + '</td><td>' + cb('cat', CATS_P, r.cat) + '</td><td>' + inp('prod', r.prod) + '</td><td>' + inp('mont', r.mont) + '</td><td><textarea data-f="det" rows="2">' + esc(r.det) + '</textarea></td></tr>'; }
      h += '</tbody></table>';
      if (S.opts.production.memo) h += '<div class="fe-memo"><b>Aide-mémoire des produits</b>' + PRODUITS.map(p => '<div>☐ ' + esc(p) + '</div>').join('') + '</div>';
    } else {
      h += '<col style="width:7%"><col style="width:20%"><col style="width:23%"><col style="width:10%"><col style="width:20%"><col style="width:5%"><col style="width:15%"></colgroup><thead><tr><th>Heure</th><th>N° / Nom</th><th>Résultat</th><th>Date RDV</th><th>Motif</th><th>ANV</th><th>Commentaire</th></tr></thead><tbody>';
      for (let i = 0; i < n; i++) { const r = R(i); h += '<tr data-i="' + i + '"><td>' + inp('heure', r.heure) + '</td><td>' + inp('num', r.num) + '</td><td>' + cb('res', RES, r.res) + '</td><td>' + inp('rdv', r.rdv) + '</td><td>' + cb('motif', MOTIFS, r.motif) + '</td><td><div class="fe-cb" style="justify-content:center"><label><input type="checkbox" data-f="anv"' + (r.anv ? ' checked' : '') + '></label></div></td><td><textarea data-f="com" rows="2">' + esc(r.com) + '</textarea></td></tr>'; }
      h += '</tbody></table><div class="fe-sum"><div><b>Appels passés</b></div><div><b>Répondeurs</b></div><div><b>RDV obtenus</b></div><div><b>dont ANV</b></div><div><b>RDV semaine en cours (S)</b></div><div><b>RDV semaine suivante (S+1)</b></div><div><b>RDV autres</b></div><div><b>Objectif ANV de la semaine : 2</b></div></div><div class="fe-sum" style="grid-template-columns:1fr"><div style="min-height:20mm"><b>Notes</b></div></div>';
    }
    return h + '<div class="fe-foot"><span>' + esc(CFG[S.type].titre) + '</span><span>Feuille à remplir à la main ou à l\'écran</span></div>';
  }
  function render() {
    const o = S.opts[S.type];
    $('fe-sheet').innerHTML = head() + body();
    let st = $('fe-pg'); if (!st) { st = document.createElement('style'); st.id = 'fe-pg'; document.head.appendChild(st); }
    st.textContent = '@page{size:A4 ' + o.orient + ';margin:8mm}';
    $('fe_rows').value = o.rows; $('fe_or').value = o.orient; $('fe_memo').checked = o.memo; $('fe_memo_box').style.display = S.type === 'production' ? '' : 'none';
    $('fe_per_lab').textContent = CFG[S.type].per; $('fe-mail').style.display = ''; mailSwitch();
    document.querySelectorAll('[data-fe-type]').forEach(b => b.className = 'btn btn-sm ' + (b.dataset.feType === S.type ? 'btn-danger' : 'btn-outline-secondary'));
    $('fe-sheet').querySelectorAll('textarea').forEach(t => { t.style.height = '100%'; });
  }
  // saisie à l'écran : on garde les valeurs pour ne pas les perdre quand la feuille est reconstruite
  $('fe-sheet').addEventListener('input', e => {
    const f = e.target.dataset.f, tr = e.target.closest('tr[data-i]'); if (!f || !tr) return; const r = rowData(+tr.dataset.i);
    if (e.target.type === 'checkbox') { if (f === 'anv') r.anv = e.target.checked; else if (f === 'stat') { r.stat = e.target.checked ? e.target.value : ''; tr.querySelectorAll('[data-f=stat]').forEach(c => { if (c !== e.target) c.checked = false; }); } else { r[f] = Array.from(tr.querySelectorAll('[data-f=' + f + ']:checked')).map(c => +c.value); } }
    else r[f] = e.target.value;
  });
  ['fe_cons:cons', 'fe_ag:ag', 'fe_per:per'].forEach(p => { const [id, k] = p.split(':'); $(id).addEventListener('input', () => { S.head[k] = $(id).value; const idx = { cons: 0, ag: 1, per: 2 }[k]; const f = $('fe-sheet').querySelectorAll('.fe-ident input')[idx]; if (f) f.value = $(id).value; }); });
  $('fe_rows').addEventListener('change', () => { S.opts[S.type].rows = Math.max(3, Math.min(40, parseInt($('fe_rows').value, 10) || CFG[S.type].rows)); render(); });
  $('fe_or').addEventListener('change', () => { S.opts[S.type].orient = $('fe_or').value; render(); });
  $('fe_memo').addEventListener('change', () => { S.opts.production.memo = $('fe_memo').checked; render(); });
  $('fe_print').addEventListener('click', () => window.print());
  $('fe_clear').addEventListener('click', () => { if (!confirm('Vider toutes les cases de cette feuille ?')) return; S.rows[S.type] = []; render(); });
  document.querySelectorAll('[data-fe-type]').forEach(b => b.addEventListener('click', () => { S.type = b.dataset.feType; render(); }));

  // ----- instances : envoi à un collaborateur -----
  const collabs = (() => { try { return (window.parent && window.parent !== window && window.parent.CFG && window.parent.CFG.conseillers) || []; } catch (e) { return []; } })().filter(c => c.mail);
  $('fm_collab').innerHTML = '<option value="">Saisir une adresse…</option>' + collabs.map((c, i) => '<option value="' + i + '">' + esc((c.prenom + ' ' + c.nom).trim()) + '</option>').join('');
  $('fm_collab').addEventListener('change', () => { const c = collabs[$('fm_collab').value]; if (c) $('fm_to').value = c.mail; });
  const MAIL = {
    instances: { titre: 'Transmettre mes instances à un collaborateur', intro: 'Avant un congé, envoyez la liste de vos instances à la personne qui vous remplace.', fichier: 'instances', objet: 'Instances en cours', liste: 'Instances en cours', vide: '(aucune instance renseignée)', unite: 'instance(s)', abs: true,
      msg: 'Bonjour,\n\nJe serai absent(e) prochainement. Voici la liste de mes instances en cours, afin que vous puissiez assurer leur suivi pendant mon absence.\n\nMerci d\'avance.' },
    production: { titre: 'Envoyer ma production', intro: 'Transmettez votre suivi de production à votre manager ou à un collègue.', fichier: 'production', objet: 'Suivi de production', liste: 'Production', vide: '(aucune ligne renseignée)', unite: 'ligne(s)', abs: false,
      msg: 'Bonjour,\n\nVoici mon suivi de production.\n\nBien cordialement.' },
    phoning: { titre: 'Envoyer le bilan de ma séance de phoning', intro: 'Transmettez le bilan de votre séance d\'appels à votre manager ou à un collègue.', fichier: 'phoning', objet: 'Bilan de séance de phoning', liste: 'Appels de la séance', vide: '(aucun appel renseigné)', unite: 'appel(s)', abs: false,
      msg: 'Bonjour,\n\nVoici le bilan de ma séance de phoning.\n\nBien cordialement.' }
  };
  const MSG = {}; Object.keys(MAIL).forEach(k => { MSG[k] = MAIL[k].msg; });
  $('fm_msg').addEventListener('input', () => { MSG[S.type] = $('fm_msg').value; });
  function mailSwitch() {
    const m = MAIL[S.type]; $('fm_titre').textContent = m.titre; $('fm_intro').textContent = m.intro; $('fm_msg').value = MSG[S.type];
    document.querySelectorAll('.fm-abs').forEach(e => { e.style.display = m.abs ? '' : 'none'; });
  }
  const frDate = v => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : (v || ''); };
  function instancesTexte() {
    const rows = (S.rows.instances || []).map(r => r || {}).filter(r => (r.num || '').trim() || (r.det || '').trim());
    const lines = rows.map((r, i) => (i + 1) + '. ' + ((r.num || '').trim() || '(sans nom)') + (r.date ? ' — ajoutée le ' + frDate(r.date) : '') + (r.ech ? ' — échéance : ' + frDate(r.ech) : '')
      + ((r.cats || []).length ? ' — ' + r.cats.map(k => CATS_I[k]).join(', ') : '') + (r.stat ? ' — ' + (r.stat === 'fait' ? 'fait' : 'à faire') : '') + ((r.det || '').trim() ? '\n   Détails : ' + r.det.trim().replace(/\n/g, '\n   ') : ''));
    return { n: rows.length, texte: lines.join('\n') };
  }
  const listeCoches = (l, a) => (a || []).map(k => l[k]).join(', ');
  function productionTexte() {
    const rows = (S.rows.production || []).map(r => r || {}).filter(r => ['date', 'prod', 'mont', 'det'].some(k => (r[k] || '').trim()) || (r.cat || []).length);
    const lines = rows.map((r, i) => (i + 1) + '. ' + (r.date ? frDate(r.date) + ' — ' : '') + ((r.prod || '').trim() || '(produit non précisé)') + ((r.cat || []).length ? ' — ' + listeCoches(CATS_P, r.cat) : '') + ((r.mont || '').trim() ? ' — ' + r.mont.trim() : '') + ((r.det || '').trim() ? '\n   Détails : ' + r.det.trim().replace(/\n/g, '\n   ') : ''));
    return { n: rows.length, texte: lines.join('\n') };
  }
  function phoningTexte() {
    const rows = (S.rows.phoning || []).map(r => r || {}).filter(r => ['heure', 'num', 'rdv', 'com'].some(k => (r[k] || '').trim()) || (r.res || []).length || (r.motif || []).length || r.anv);
    const lines = rows.map((r, i) => (i + 1) + '. ' + (r.heure ? r.heure + ' — ' : '') + ((r.num || '').trim() || '(sans nom)') + ((r.res || []).length ? ' — ' + listeCoches(RES, r.res) : '') + ((r.rdv || '').trim() ? ' — RDV : ' + r.rdv.trim() : '') + ((r.motif || []).length ? ' — ' + listeCoches(MOTIFS, r.motif) : '') + (r.anv ? ' — ANV' : '') + ((r.com || '').trim() ? '\n   ' + r.com.trim().replace(/\n/g, '\n   ') : ''));
    const nb = (k) => rows.filter(r => (r.res || []).indexOf(k) >= 0).length;
    const bilan = rows.length ? '\n\nBilan : ' + rows.length + ' appel(s), ' + nb(1) + ' répondeur(s), ' + nb(3) + ' RDV, dont ' + rows.filter(r => r.anv).length + ' ANV.' : '';
    return { n: rows.length, texte: lines.join('\n') + bilan };
  }
  function mailContent() {
    const M = MAIL[S.type], t = S.type === 'instances' ? instancesTexte() : S.type === 'production' ? productionTexte() : phoningTexte(), cons = S.head.cons || '';
    const du = M.abs ? frDate($('fm_du').value) : '', au = M.abs ? frDate($('fm_au').value) : '', per = (S.head.per || '').trim();
    const abs = du || au ? '\n\nAbsence : ' + (du ? 'du ' + du : '') + (au ? ' au ' + au : '') : '';
    const corps = $('fm_msg').value.trim() + abs + '\n\n' + M.liste + ' (' + t.n + ') :\n\n' + (t.n ? t.texte : M.vide) + (cons ? '\n\nCordialement,\n' + cons : '');
    return { n: t.n, objet: M.objet + (per && !M.abs ? ' - ' + per : '') + (cons ? ' - ' + cons : '') + (du ? ' (absence du ' + du + (au ? ' au ' + au : '') + ')' : ''), corps };
  }
  const b64 = s => btoa(unescape(encodeURIComponent(s)));
  function words(s) { const parts = []; let cur = ''; Array.from(s).forEach(c => { if (unescape(encodeURIComponent(cur + c)).length > 36) { parts.push(cur); cur = c; } else cur += c; }); if (cur) parts.push(cur); return parts.map(p => '=?UTF-8?B?' + b64(p) + '?=').join('\r\n '); }
  function check() { const to = $('fm_to').value.trim(); if (!/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(to)) { $('fm_info').textContent = 'Indiquez l\'adresse e-mail du destinataire.'; $('fm_info').className = 'small text-danger'; return null; } $('fm_info').className = 'small text-muted'; return to; }
  $('fm_eml').addEventListener('click', () => {
    const to = check(); if (!to) return; const m = mailContent();
    const eml = ['X-Unsent: 1', 'To: ' + to, 'Subject: ' + words(m.objet), 'Date: ' + new Date().toUTCString().replace('GMT', '+0000'), 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', '', b64(m.corps.replace(/\n/g, '\r\n')).replace(/.{1,76}/g, '$&\r\n')].join('\r\n');
    const a = document.createElement('a'), d = new Date(), p = n => (n < 10 ? '0' : '') + n; a.href = URL.createObjectURL(new Blob([eml], { type: 'message/rfc822' })); a.download = MAIL[S.type].fichier + '-' + d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + '.eml'; document.body.appendChild(a); a.click(); setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 800);
    $('fm_info').textContent = m.n + ' ' + MAIL[S.type].unite + ' dans le message. Ouvrez le fichier téléchargé, puis cliquez sur « Envoyer ».';
  });
  $('fm_mailto').addEventListener('click', () => {
    const to = check(); if (!to) return; const m = mailContent(), url = 'mailto:' + encodeURIComponent(to) + '?subject=' + encodeURIComponent(m.objet) + '&body=' + encodeURIComponent(m.corps);
    if (url.length > 1900) { $('fm_info').textContent = 'La liste est trop longue pour ce mode : utilisez « Préparer l\'e-mail (fichier .eml) ».'; $('fm_info').className = 'small text-danger'; return; }
    location.href = url;
  });

  // ----- lien de partage : réglages et lignes saisies -----
  window.toolsShare = { get: () => ({ t: S.type, h: S.head, o: S.opts, r: S.rows }), set: s => { if (!s) return; if (TYPES.indexOf(s.t) >= 0) S.type = s.t; Object.assign(S.head, s.h || {}); Object.keys(s.o || {}).forEach(k => { if (S.opts[k]) Object.assign(S.opts[k], s.o[k]); }); Object.keys(s.r || {}).forEach(k => { if (S.rows[k]) S.rows[k] = s.r[k]; }); $('fe_cons').value = S.head.cons || ''; $('fe_ag').value = S.head.ag || ''; $('fe_per').value = S.head.per || ''; render(); } };
  // impression : masque tout ce qui entoure la feuille, quel que soit le gabarit de la page (portail ou /tools)
  const hid = [];
  window.addEventListener('beforeprint', () => { let n = $('fe-sheet'); while (n && n !== document.body) { Array.from(n.parentNode.children).forEach(c => { if (c !== n && c.nodeType === 1 && !/^(SCRIPT|STYLE|LINK)$/.test(c.tagName) && c.style.display !== 'none') { hid.push([c, c.style.display]); c.style.setProperty('display', 'none', 'important'); } }); n = n.parentNode; } });
  window.addEventListener('afterprint', () => { while (hid.length) { const [c, d] = hid.pop(); c.style.removeProperty('display'); if (d) c.style.display = d; } });
  window.toolsDoc = () => ({ title: CFG[S.type].titre, inputs: [], html: '' });
  render();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
