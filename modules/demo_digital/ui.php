<?php // Démonstration du parcours client en banque en ligne (site web et appli mobile), entièrement côté navigateur, avec des données fictives. ?>
<style>
.dg-banner{background:#fff3cd;border:1px solid #ffe08a;color:#6b5200;border-radius:8px;padding:8px 12px;font-size:13px}
.dg-tabs .btn{border-radius:20px}.dg-note{background:#fff;border:1px solid #e9ecef;border-left:4px solid #e4002b;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:8px}
.dg-note b{color:#c40025}.dg-client .dg-note,.dg-client .dg-advisor{display:none!important}
.dg-browser{background:#fff;border:1px solid #cfd4da;border-radius:10px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,.08)}
.dg-bar{background:#e9ecef;padding:6px 10px;display:flex;gap:8px;align-items:center;font-size:12px;color:#555}.dg-bar i{color:#adb5bd}.dg-url{flex:1;background:#fff;border-radius:12px;padding:2px 12px}
.dg-web{display:flex;min-height:560px}.dg-side{width:210px;background:#f8f9fa;border-right:1px solid #e9ecef;padding:12px 8px;flex:0 0 auto}
.dg-side a{display:block;padding:9px 12px;border-radius:8px;color:#333;text-decoration:none;font-size:14px;cursor:pointer;margin-bottom:2px}.dg-side a.on,.dg-side a:hover{background:#fde8ec;color:#c40025;font-weight:600}
.dg-main{flex:1;padding:18px;min-width:0}.dg-brand{font-weight:800;color:#e4002b;letter-spacing:.3px}.dg-card{background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:14px;margin-bottom:12px}
.dg-acc{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px;border:1px solid #e9ecef;border-radius:12px;margin-bottom:8px;cursor:pointer;background:#fff}.dg-acc:hover{border-color:#e4002b}
.dg-amt{font-weight:700;white-space:nowrap}.dg-neg{color:#c40025}.dg-pos{color:#1b7f3b}.dg-op{display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #f0f0f0;font-size:14px}.dg-op small{color:#6c757d}
.dg-ccard{background:linear-gradient(135deg,#e4002b,#8f001b);color:#fff;border-radius:14px;padding:16px;width:100%;max-width:320px;aspect-ratio:1.6;display:flex;flex-direction:column;justify-content:space-between}
.dg-phone{width:330px;height:660px;margin:0 auto;border:10px solid #212529;border-radius:36px;background:#fff;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 10px 30px rgba(0,0,0,.2)}
.dg-ptop{background:#e4002b;color:#fff;padding:14px 16px 10px;font-weight:700;display:flex;justify-content:space-between;align-items:center}.dg-pbody{flex:1;overflow:auto;padding:12px;background:#f5f5f7}
.dg-pnav{display:flex;border-top:1px solid #e5e5e5;background:#fff}.dg-pnav button{flex:1;border:0;background:#fff;padding:8px 2px;font-size:11px;color:#777}.dg-pnav button i{display:block;font-size:18px;margin-bottom:2px}.dg-pnav button.on{color:#e4002b;font-weight:700}
.dg-login{max-width:360px;margin:30px auto;text-align:center}.dg-fp{width:84px;height:84px;border-radius:50%;border:3px solid #e4002b;color:#e4002b;display:flex;align-items:center;justify-content:center;font-size:36px;margin:10px auto;cursor:pointer}.dg-fp:hover{background:#fde8ec}
.dg-step{display:inline-block;width:26px;height:26px;border-radius:50%;background:#e4002b;color:#fff;text-align:center;line-height:26px;font-size:13px;margin-right:6px}
.dg-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#212529;color:#fff;padding:10px 18px;border-radius:20px;font-size:14px;z-index:3000;display:none}
@media(max-width:768px){.dg-web{flex-direction:column}.dg-side{width:auto;display:flex;overflow:auto;border-right:0;border-bottom:1px solid #e9ecef}.dg-side a{white-space:nowrap}}
</style>
<div id="dg" class="row g-3">
  <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
    <div class="dg-tabs btn-group"><button class="btn btn-sm btn-danger" data-mode="web"><i class="fas fa-desktop me-1"></i>Site web</button><button class="btn btn-sm btn-outline-secondary" data-mode="mob"><i class="fas fa-mobile-screen me-1"></i>Appli mobile</button></div>
    <div class="form-check form-switch ms-2 mb-0"><input class="form-check-input" type="checkbox" id="dgClient"><label class="form-check-label small" for="dgClient">Mode client (masque les conseils)</label></div>
    <button class="btn btn-sm btn-outline-secondary ms-auto" id="dgReset"><i class="fas fa-rotate-left me-1"></i>Recommencer la démo</button>
  </div>
  <div class="col-12"><div class="dg-banner"><i class="fas fa-triangle-exclamation me-1"></i><strong>Démonstration — données fictives.</strong> Ceci n'est pas le vrai site ni la vraie application : n'y saisissez aucun identifiant ni code réel, rien n'est enregistré ni transmis.</div></div>
  <div class="col-lg-3 dg-advisor">
    <div class="small text-uppercase text-muted mb-2">Parcours à montrer</div>
    <div class="dg-note"><b>1. Se connecter</b><br>Identification forte (Sécur'Pass) : rassurez sur la sécurité. <a href="#" data-go="login">Aller à l'écran</a></div>
    <div class="dg-note"><b>2. Voir ses comptes</b><br>Soldes, dernières opérations, recherche. <a href="#" data-go="home">Aller à l'écran</a></div>
    <div class="dg-note"><b>3. Faire un virement</b><br>Bénéficiaire, montant, validation. <a href="#" data-go="virement">Aller à l'écran</a></div>
    <div class="dg-note"><b>4. Gérer sa carte</b><br>Plafonds, blocage temporaire, opposition. <a href="#" data-go="cartes">Aller à l'écran</a></div>
    <div class="dg-note"><b>5. Contacter son conseiller</b><br>Demande de rendez-vous. <a href="#" data-go="rdv">Aller à l'écran</a></div>
  </div>
  <div class="col-lg-9" id="dgStage"></div>
</div>
<div class="dg-toast" id="dgToast"></div>
<script>
(function () {
  'use strict';
  const $ = s => document.querySelector(s), esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const eur = n => n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
  const initial = () => ({
    client: 'Camille Martin',
    accounts: [
      { id: 'cc', nom: 'Compte courant', num: '•••• 0421', solde: 1842.36, ops: [
        ['Carte — Supermarché', -64.18, '14/10'], ['Virement reçu — Salaire', 2150.00, '01/10'], ['Prélèvement — Loyer', -720.00, '05/10'], ['Carte — Boulangerie', -6.40, '12/10'], ['Prélèvement — Assurance habitation', -23.90, '08/10'], ['Carte — Station-service', -58.20, '09/10'], ['Retrait DAB', -40.00, '10/10']] },
      { id: 'la', nom: 'Livret A', num: '•••• 7733', solde: 6120.00, ops: [['Virement — épargne mensuelle', 150.00, '02/10'], ['Intérêts', 41.20, '01/01']] },
      { id: 'pel', nom: 'Plan d\'épargne logement', num: '•••• 1905', solde: 9800.00, ops: [['Versement', 100.00, '03/10']] },
      { id: 'pr', nom: 'Prêt immobilier (capital restant dû)', num: '•••• 5502', solde: -128450.75, ops: [['Échéance', -812.30, '05/10']] }],
    benef: ['Jean Dupont (compte exemple)', 'Marie Lambert (compte exemple)', 'Agence immobilière Les Tilleuls (exemple)'],
    card: { bloquee: false, etranger: true, sans_contact: true, plafPaiement: 2500, plafRetrait: 500 }
  });
  let S = initial(), mode = 'web', page = 'login', authed = false, accSel = 'cc', vir = { de: 'cc', a: 0, montant: '', motif: '', date: 'immediat', etape: 1 };
  const toast = m => { const t = $('#dgToast'); t.textContent = m; t.style.display = 'block'; clearTimeout(toast.h); toast.h = setTimeout(() => t.style.display = 'none', 2600); };
  const total = () => S.accounts.filter(a => a.solde > 0).reduce((t, a) => t + a.solde, 0);
  const NAV = [['home', 'Comptes', 'fa-wallet'], ['virement', 'Virement', 'fa-money-bill-transfer'], ['cartes', 'Cartes', 'fa-credit-card'], ['docs', 'Documents', 'fa-file-lines'], ['rdv', 'Mon conseiller', 'fa-user-tie']];

  // ----- écrans (le même contenu s'affiche dans le site et dans l'appli) -----
  function viewLogin() {
    return `<div class="dg-login"><div class="dg-brand" style="font-size:26px">Banque en ligne</div><div class="text-muted small mb-3">Démonstration</div>
      <div class="text-start"><label class="form-label small mb-0">Identifiant</label><input class="form-control mb-2" value="Identifiant de démonstration" readonly>
      <label class="form-label small mb-0">Code secret</label><input class="form-control mb-3" value="••••••" readonly></div>
      <div class="small text-muted mb-1">Pour valider votre connexion, utilisez Sécur'Pass sur votre téléphone</div>
      <div class="dg-fp" data-act="auth" title="Valider avec l'empreinte"><i class="fas fa-fingerprint"></i></div>
      <div class="small text-muted">Touchez l'empreinte pour simuler la validation</div></div>`;
  }
  function viewHome() {
    return `<h5 class="mb-1">Bonjour ${esc(S.client)}</h5><div class="text-muted small mb-3">Vos avoirs : <b>${eur(total())}</b></div>
      ${S.accounts.map(a => `<div class="dg-acc" data-act="acc" data-id="${a.id}"><div><b>${esc(a.nom)}</b><div class="small text-muted">${esc(a.num)}</div></div><div class="dg-amt ${a.solde < 0 ? 'dg-neg' : ''}">${eur(a.solde)}</div></div>`).join('')}
      <div class="mt-3"><button class="btn btn-sm btn-danger" data-go="virement"><i class="fas fa-money-bill-transfer me-1"></i>Faire un virement</button></div>`;
  }
  function viewAccount() {
    const a = S.accounts.find(x => x.id === accSel) || S.accounts[0];
    return `<button class="btn btn-sm btn-link p-0 mb-2" data-go="home"><i class="fas fa-chevron-left"></i> Mes comptes</button>
      <div class="dg-card"><div class="small text-muted">${esc(a.nom)} ${esc(a.num)}</div><div class="h3 mb-0 ${a.solde < 0 ? 'dg-neg' : ''}">${eur(a.solde)}</div></div>
      <input class="form-control form-control-sm mb-2" id="dgSearch" placeholder="Rechercher une opération" autocomplete="off">
      <div id="dgOps">${a.ops.map(o => `<div class="dg-op"><div>${esc(o[0])}<br><small>${esc(o[2])}</small></div><div class="dg-amt ${o[1] < 0 ? 'dg-neg' : 'dg-pos'}">${eur(o[1])}</div></div>`).join('')}</div>`;
  }
  function viewVirement() {
    const from = S.accounts.filter(a => a.id !== 'pr'), fa = S.accounts.find(a => a.id === vir.de);
    if (vir.etape === 3) return `<div class="text-center py-4"><div style="font-size:54px;color:#1b7f3b"><i class="fas fa-circle-check"></i></div><h5>Virement enregistré</h5><div class="text-muted">${eur(parseFloat(vir.montant))} vers ${esc(S.benef[vir.a])}<br>Référence DEMO-${String(Date.now()).slice(-6)}</div><button class="btn btn-sm btn-danger mt-3" data-go="home">Retour à mes comptes</button></div>`;
    if (vir.etape === 2) return `<h5><span class="dg-step">2</span>Confirmer</h5><div class="dg-card">De <b>${esc(fa.nom)}</b><br>Vers <b>${esc(S.benef[vir.a])}</b><br>Montant <b>${eur(parseFloat(vir.montant))}</b><br>${vir.date === 'immediat' ? 'Exécution immédiate' : 'Exécution différée'}${vir.motif ? '<br>Motif : ' + esc(vir.motif) : ''}</div>
      <div class="text-center"><div class="small text-muted">Validez avec Sécur'Pass</div><div class="dg-fp" data-act="vir-ok" title="Valider avec l'empreinte"><i class="fas fa-fingerprint"></i></div><button class="btn btn-sm btn-outline-secondary" data-act="vir-back">Modifier</button></div>`;
    return `<h5><span class="dg-step">1</span>Nouveau virement</h5><div class="dg-card">
      <label class="form-label small mb-0">Compte à débiter</label><select class="form-select mb-2" id="vDe">${from.map(a => `<option value="${a.id}" ${a.id === vir.de ? 'selected' : ''}>${esc(a.nom)} — ${eur(a.solde)}</option>`).join('')}</select>
      <label class="form-label small mb-0">Bénéficiaire</label><select class="form-select mb-2" id="vA">${S.benef.map((b, i) => `<option value="${i}" ${i === vir.a ? 'selected' : ''}>${esc(b)}</option>`).join('')}</select>
      <label class="form-label small mb-0">Montant (€)</label><input class="form-control mb-2" id="vM" type="number" min="0.01" step="0.01" value="${esc(vir.montant)}" placeholder="0,00">
      <label class="form-label small mb-0">Date</label><select class="form-select mb-2" id="vD"><option value="immediat" ${vir.date === 'immediat' ? 'selected' : ''}>Immédiat</option><option value="differe" ${vir.date === 'differe' ? 'selected' : ''}>Différé</option></select>
      <label class="form-label small mb-0">Motif (facultatif)</label><input class="form-control mb-3" id="vO" maxlength="40" value="${esc(vir.motif)}">
      <button class="btn btn-danger w-100" data-act="vir-next">Continuer</button></div><div class="dg-advisor small text-muted">Conseil : montrez que le montant est vérifié par rapport au solde disponible.</div>`;
  }
  function viewCartes() {
    const c = S.card;
    return `<h5>Ma carte</h5><div class="dg-ccard mb-3" style="${c.bloquee ? 'filter:grayscale(1);opacity:.7' : ''}"><div class="d-flex justify-content-between"><b>Carte Visa — démo</b><i class="fas fa-wifi"></i></div><div style="font-size:18px;letter-spacing:2px">•••• •••• •••• 0421</div><div class="d-flex justify-content-between small"><span>CAMILLE MARTIN</span><span>${c.bloquee ? 'BLOQUÉE' : '12/28'}</span></div></div>
      ${[['bloquee', 'Blocage temporaire', 'Bloque tous les paiements et retraits'], ['etranger', 'Paiements à l\'étranger', 'Autoriser les paiements hors de France'], ['sans_contact', 'Paiement sans contact', 'Autoriser le sans contact']].map(([k, t, d]) => `<div class="dg-card d-flex justify-content-between align-items-center gap-2"><div><b>${t}</b><div class="small text-muted">${d}</div></div><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" data-act="tog" data-k="${k}" ${c[k] ? 'checked' : ''}></div></div>`).join('')}
      <div class="dg-card"><b>Plafonds</b><div class="small text-muted mb-2">Sur 30 jours glissants (exemple)</div>
        <label class="small">Paiements : <b id="pp">${eur(c.plafPaiement)}</b></label><input type="range" class="form-range" min="500" max="6000" step="100" value="${c.plafPaiement}" data-act="plaf" data-k="plafPaiement" data-out="pp">
        <label class="small">Retraits sur 7 jours : <b id="pr2">${eur(c.plafRetrait)}</b></label><input type="range" class="form-range" min="100" max="1000" step="50" value="${c.plafRetrait}" data-act="plaf" data-k="plafRetrait" data-out="pr2"></div>
      <button class="btn btn-outline-danger btn-sm" data-act="opposition"><i class="fas fa-ban me-1"></i>Faire opposition (simulation)</button>`;
  }
  function viewDocs() {
    return `<h5>Mes documents</h5>${['Relevé de compte — septembre 2026', 'Relevé de compte — août 2026', 'Tableau d\'amortissement — prêt immobilier', 'Avis d\'opéré — exemple'].map(d => `<div class="dg-acc" data-act="doc" data-n="${esc(d)}"><div><i class="fas fa-file-pdf text-danger me-2"></i>${esc(d)}</div><span class="small text-muted">Consulter</span></div>`).join('')}`;
  }
  function viewRdv() {
    return `<h5>Contacter mon conseiller</h5><div class="dg-card"><label class="form-label small mb-0">Objet du rendez-vous</label><select class="form-select mb-2" id="rO"><option>Faire le point sur mes comptes</option><option>Projet immobilier</option><option>Épargne et placements</option><option>Assurances</option></select>
      <label class="form-label small mb-0">Mode</label><select class="form-select mb-2" id="rM"><option>En agence</option><option>Par téléphone</option><option>En visioconférence</option></select>
      <label class="form-label small mb-0">Créneau</label><select class="form-select mb-3" id="rC"><option>Mardi 09h30</option><option>Mercredi 14h00</option><option>Jeudi 16h30</option></select>
      <button class="btn btn-danger w-100" data-act="rdv-ok">Demander ce rendez-vous</button></div>`;
  }
  const VIEWS = { login: viewLogin, home: viewHome, account: viewAccount, virement: viewVirement, cartes: viewCartes, docs: viewDocs, rdv: viewRdv };
  const body = () => (authed || page === 'login') ? VIEWS[page]() : VIEWS.login();

  // ----- cadres : navigateur (site) et téléphone (appli) -----
  function render() {
    if (!authed && page !== 'login') page = 'login';
    const stage = $('#dgStage'), nav = NAV.map(([k, t, i]) => ({ k, t, i }));
    const active = k => page === k || (k === 'home' && page === 'account');
    if (mode === 'web') {
      stage.innerHTML = `<div class="dg-browser"><div class="dg-bar"><i class="fas fa-lock"></i><span class="dg-url">banque-en-ligne.demo — démonstration</span></div>
        <div class="dg-web">${authed ? `<div class="dg-side"><div class="dg-brand px-2 mb-2">Banque en ligne</div>${nav.map(n => `<a class="${active(n.k) ? 'on' : ''}" data-go="${n.k}"><i class="fas ${n.i} me-2"></i>${n.t}</a>`).join('')}<a data-act="logout" class="mt-2"><i class="fas fa-right-from-bracket me-2"></i>Se déconnecter</a></div>` : ''}<div class="dg-main">${body()}</div></div></div>`;
    } else {
      stage.innerHTML = `<div class="dg-phone"><div class="dg-ptop"><span>${authed ? esc((nav.find(n => active(n.k)) || { t: 'Comptes' }).t) : 'Banque en ligne'}</span><i class="fas fa-bell"></i></div><div class="dg-pbody">${body()}</div>
        ${authed ? `<div class="dg-pnav">${nav.slice(0, 4).map(n => `<button class="${active(n.k) ? 'on' : ''}" data-go="${n.k}"><i class="fas ${n.i}"></i>${n.t}</button>`).join('')}<button data-go="rdv" class="${page === 'rdv' ? 'on' : ''}"><i class="fas fa-user-tie"></i>Conseiller</button></div>` : ''}</div>`;
    }
    const s = $('#dgSearch'); if (s) s.addEventListener('input', () => { const q = s.value.toLowerCase(), a = S.accounts.find(x => x.id === accSel);
      $('#dgOps').innerHTML = a.ops.filter(o => o[0].toLowerCase().includes(q)).map(o => `<div class="dg-op"><div>${esc(o[0])}<br><small>${esc(o[2])}</small></div><div class="dg-amt ${o[1] < 0 ? 'dg-neg' : 'dg-pos'}">${eur(o[1])}</div></div>`).join('') || '<div class="text-muted small py-2">Aucune opération.</div>'; });
    document.querySelectorAll('.dg-tabs [data-mode]').forEach(b => { const on = b.dataset.mode === mode; b.className = 'btn btn-sm ' + (on ? 'btn-danger' : 'btn-outline-secondary'); });
  }
  const go = p => { page = p; if (p === 'virement') vir.etape = 1; render(); };
  function readVir() { const g = id => { const e = $('#' + id); return e ? e.value : ''; }; if ($('#vM')) { vir.de = g('vDe'); vir.a = parseInt(g('vA')) || 0; vir.montant = g('vM'); vir.date = g('vD'); vir.motif = g('vO'); } }

  document.addEventListener('click', e => {
    if (!e.target.closest('#dg')) return;
    const m = e.target.closest('[data-mode]'); if (m) { mode = m.dataset.mode; render(); return; }
    const g = e.target.closest('[data-go]'); if (g) { e.preventDefault(); if (!authed && g.dataset.go !== 'login') { toast('Connectez-vous d\'abord (étape 1)'); page = 'login'; } else page = g.dataset.go; if (page === 'virement') vir.etape = 1; render(); return; }
    const a = e.target.closest('[data-act]'); if (!a) return;
    switch (a.dataset.act) {
      case 'auth': authed = true; page = 'home'; toast('Connexion validée'); render(); break;
      case 'logout': authed = false; page = 'login'; render(); break;
      case 'acc': accSel = a.dataset.id; page = 'account'; render(); break;
      case 'vir-next': { readVir(); const m = parseFloat(vir.montant), fa = S.accounts.find(x => x.id === vir.de);
        if (!(m > 0)) { toast('Saisissez un montant'); break; } if (m > fa.solde) { toast('Solde insuffisant sur ce compte'); break; } vir.etape = 2; render(); break; }
      case 'vir-back': vir.etape = 1; render(); break;
      case 'vir-ok': { const m = parseFloat(vir.montant), fa = S.accounts.find(x => x.id === vir.de); fa.solde = Math.round((fa.solde - m) * 100) / 100;
        fa.ops.unshift(['Virement émis — ' + S.benef[vir.a].split(' (')[0], -m, new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })]); vir.etape = 3; render(); break; }
      case 'doc': alert('Aperçu de démonstration : « ' + a.dataset.n + ' ».\nAucun document réel n\'est affiché.'); break;
      case 'opposition': if (confirm('Simulation : faire opposition à la carte ? (aucun effet réel)')) { S.card.bloquee = true; render(); toast('Opposition simulée : carte bloquée'); } break;
      case 'rdv-ok': toast('Demande envoyée (simulation) : ' + $('#rC').value); break;
    }
  });
  document.addEventListener('input', e => { const a = e.target.closest('[data-act="plaf"]'); if (a) { S.card[a.dataset.k] = parseInt(a.value); $('#' + a.dataset.out).textContent = eur(S.card[a.dataset.k]); } });
  document.addEventListener('change', e => { const t = e.target.closest('[data-act="tog"]'); if (t) { S.card[t.dataset.k] = t.checked; render(); } });
  $('#dgClient').addEventListener('change', e => $('#dg').classList.toggle('dg-client', e.target.checked));
  $('#dgReset').addEventListener('click', () => { S = initial(); authed = false; page = 'login'; vir = { de: 'cc', a: 0, montant: '', motif: '', date: 'immediat', etape: 1 }; render(); toast('Démonstration réinitialisée'); });
  render();
})();
</script>
