<?php
// Assistant IA : conversation généraliste, partagée par le portail et /tools (masqué par défaut dans /tools). Variable attendue : $iaEndpoint.
$iaEndpoint = $iaEndpoint ?? 'ia_chat.php';
?>
<style>
.ia-wrap{display:flex;flex-direction:column;height:calc(100vh - 250px);min-height:460px;background:#fff;border:1px solid #e9ecef;border-radius:12px;overflow:hidden}
.ia-log{flex:1;overflow:auto;padding:16px;background:#f8f9fa}.ia-row{display:flex;margin-bottom:12px}.ia-row.me{justify-content:flex-end}
.ia-bub{max-width:min(760px,92%);padding:10px 14px;border-radius:14px;font-size:15px;line-height:1.5;word-wrap:break-word}.ia-row.me .ia-bub{background:#e4002b;color:#fff;border-bottom-right-radius:4px;white-space:pre-wrap}
.ia-row.bot .ia-bub{background:#fff;border:1px solid #e3e6ea;border-bottom-left-radius:4px}.ia-bub p{margin:0 0 .55rem}.ia-bub p:last-child,.ia-bub ul:last-child{margin-bottom:0}.ia-bub ul,.ia-bub ol{margin:.2rem 0 .6rem;padding-left:1.3rem}
.ia-bub .sp-table{margin:.3rem 0 .7rem;font-size:14px}.ia-bub .sp-table th{background:#fde8ec;color:#8f001b}.ia-code{background:#212529;color:#f8f9fa;border-radius:8px;padding:10px 12px;font-size:13px;overflow:auto;margin:.3rem 0 .7rem}
.ia-tools{margin-top:6px;opacity:.7}.ia-tools button{border:0;background:transparent;color:#6c757d;font-size:12px;padding:0 8px 0 0}.ia-tools button:hover{color:#e4002b}
.ia-in{border-top:1px solid #e9ecef;padding:10px;display:flex;gap:8px;align-items:flex-end;background:#fff}.ia-in textarea{resize:none;max-height:160px}
.ia-dots span{display:inline-block;width:7px;height:7px;border-radius:50%;background:#adb5bd;margin-right:4px;animation:iad 1s infinite}.ia-dots span:nth-child(2){animation-delay:.15s}.ia-dots span:nth-child(3){animation-delay:.3s}@keyframes iad{0%,80%,100%{opacity:.3}40%{opacity:1}}
.ia-empty{text-align:center;color:#6c757d;margin:40px auto;max-width:560px}.ia-chips{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:12px}
@media print{.no-print{display:none!important}}
</style>
<div class="ia-wrap" id="ia">
  <div class="ia-log" id="iaLog" aria-live="polite"></div>
  <form class="ia-in no-print" id="iaForm">
    <textarea id="iaMsg" class="form-control" rows="1" maxlength="6000" placeholder="Posez votre question ou collez un texte à retravailler…" aria-label="Votre message"></textarea>
    <button class="btn btn-danger" id="iaSend" title="Envoyer (Entrée)"><i class="fas fa-paper-plane"></i></button>
    <button type="button" class="btn btn-outline-secondary" id="iaNew" title="Nouvelle conversation"><i class="fas fa-rotate-left"></i></button>
  </form>
</div>
<div class="small text-muted mt-2"><i class="fas fa-circle-info me-1"></i>Les réponses sont générées automatiquement et peuvent contenir des erreurs : vérifiez-les, surtout les chiffres, taux et règles. Vos messages sont transmis à un service externe : <strong>ne saisissez aucune donnée personnelle de client</strong>. La conversation reste dans votre navigateur (elle s'efface à la fermeture de l'onglet) ; seule la taille des échanges est consignée.</div>
<script>
(function () {
  const ENDPOINT = <?= json_encode($iaEndpoint) ?>, KEY = 'iaChat';
  const $ = id => document.getElementById(id), log = $('iaLog'), msg = $('iaMsg');
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let hist = []; try { hist = JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) {}
  const save = () => { try { sessionStorage.setItem(KEY, JSON.stringify(hist.slice(-30))); } catch (e) {} };
  const SUGG = ['Rédige un mail de relance pour un client qui n\'a pas renvoyé ses pièces', 'Explique simplement la différence entre TAEG et taux nominal', 'Reformule ce texte de façon plus professionnelle :', 'Quels points vérifier avant de présenter un projet immobilier à un client ?'];
  function bubble(m, i) {
    if (m.role === 'user') return '<div class="ia-row me"><div class="ia-bub">' + esc(m.content) + '</div></div>';
    return '<div class="ia-row bot"><div class="ia-bub"><div>' + m.html + '</div><div class="ia-tools no-print"><button data-copy="' + i + '"><i class="far fa-copy me-1"></i>Copier</button></div></div></div>';
  }
  function draw(extra) {
    log.innerHTML = hist.length ? hist.map(bubble).join('') : '<div class="ia-empty"><div style="font-size:34px;color:#e4002b"><i class="fas fa-comments"></i></div><h5 class="mt-2">Comment puis-je vous aider ?</h5><div>Rédaction, explications, calculs, préparation d\'un entretien… Posez votre question.</div><div class="ia-chips">' + SUGG.map(s => '<button type="button" class="btn btn-sm btn-outline-secondary" data-s="' + esc(s) + '">' + esc(s) + '</button>').join('') + '</div></div>';
    if (extra) log.insertAdjacentHTML('beforeend', extra);
    log.scrollTop = log.scrollHeight;
  }
  async function send(text) {
    text = text.trim(); if (!text) return;
    hist.push({ role: 'user', content: text }); save(); msg.value = ''; msg.style.height = 'auto';
    draw('<div class="ia-row bot" id="iaWait"><div class="ia-bub"><span class="ia-dots"><span></span><span></span><span></span></span></div></div>');
    $('iaSend').disabled = true;
    let r; try { r = await (await fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' }, body: JSON.stringify({ messages: hist.map(m => ({ role: m.role, content: m.content })) }), credentials: 'same-origin' })).json(); } catch (e) { r = { error: 'Connexion impossible. Réessayez.' }; }
    $('iaSend').disabled = false;
    if (r.ok) { hist.push({ role: 'assistant', content: r.text, html: r.html }); save(); draw(); }
    else { draw('<div class="ia-row bot"><div class="ia-bub text-danger"><i class="fas fa-circle-exclamation me-1"></i>' + esc(r.error || 'Erreur.') + (r.detail ? '<div class="small text-muted mt-1">(administrateur : ' + esc(r.detail) + ')</div>' : '') + '</div></div>'); }
    msg.focus();
  }
  $('iaForm').addEventListener('submit', e => { e.preventDefault(); send(msg.value); });
  msg.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(msg.value); } });
  msg.addEventListener('input', () => { msg.style.height = 'auto'; msg.style.height = Math.min(160, msg.scrollHeight) + 'px'; });
  $('iaNew').addEventListener('click', () => { if (hist.length && !confirm('Commencer une nouvelle conversation ? L\'actuelle sera effacée.')) return; hist = []; save(); draw(); msg.focus(); });
  log.addEventListener('click', async e => {
    const s = e.target.closest('[data-s]'); if (s) { msg.value = s.dataset.s; msg.focus(); return; }
    const c = e.target.closest('[data-copy]'); if (c) { const m = hist[parseInt(c.dataset.copy)]; try { await navigator.clipboard.writeText(m.content); c.innerHTML = '<i class="fas fa-check me-1"></i>Copié'; } catch (err) { c.textContent = 'Copie impossible'; } setTimeout(() => c.innerHTML = '<i class="far fa-copy me-1"></i>Copier', 1800); }
  });
  draw();
})();
</script>
