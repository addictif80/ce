<?php
// Recherche dans les fiches « particuliers » de service-public.fr, partagée par le portail et /tools.
// Variable attendue : $spEndpoint (adresse du service de recherche).
$spEndpoint = $spEndpoint ?? 'sp_search.php';
require __DIR__ . '/../_sim/style.php';
?>
<style>
.sp-ans{font-size:15px;line-height:1.5}.sp-ans p{margin:0 0 .6rem}.sp-ans p:first-child{font-size:16px}.sp-ans ul,.sp-ans ol{margin:.2rem 0 .7rem;padding-left:1.3rem}.sp-ans li{margin-bottom:.25rem}
.sp-ans .sp-table{max-width:520px;margin:.3rem 0 .8rem;font-size:14px}.sp-ans .sp-table th{background:#fde8ec;color:#8f001b}
.sp-note{background:#fff8e1;border-left:4px solid #f0ad00;border-radius:6px;padding:8px 12px;font-size:14px;margin-top:.4rem}
a.sp-ref{display:inline-block;min-width:18px;height:18px;line-height:18px;border-radius:9px;background:#e4002b;color:#fff;font-size:11px;font-weight:700;text-align:center;text-decoration:none;margin-left:3px;vertical-align:text-top}
.sp-fiche:target{background:#fff3cd;border-radius:6px;box-shadow:0 0 0 3px #fff3cd}
@media print{.no-print{display:none!important}}
</style>
<div class="cap-card" id="sp-card">
  <h2><i class="fas fa-magnifying-glass me-2 text-danger"></i>Poser une question</h2>
  <form id="sp-form" class="d-flex gap-2 flex-wrap" autocomplete="off">
    <input type="search" id="sp-q" class="form-control" style="flex:1;min-width:260px" maxlength="300" placeholder="Ex. : âge minimum pour ouvrir un livret A, solde bancaire insaisissable, abattement donation enfant…" aria-label="Question">
    <button class="btn btn-danger" id="sp-go"><i class="fas fa-magnifying-glass me-1"></i>Rechercher</button>
  </form>
  <div class="mt-2 d-flex flex-wrap gap-1" id="sp-ex">
    <?php foreach (['Âge minimum pour ouvrir un livret A', 'Procuration sur un compte bancaire', 'Décès du titulaire d\'un compte', 'Solde bancaire insaisissable', 'Abattement donation à un enfant', 'Mobilité bancaire'] as $ex): ?>
    <button type="button" class="btn btn-sm btn-outline-secondary py-0"><?= e($ex) ?></button>
    <?php endforeach; ?>
  </div>
  <div class="small text-muted mt-2"><i class="fas fa-circle-info me-1"></i>Fiches pratiques « particuliers » de service-public.fr. Informations générales, à vérifier sur le site officiel avant d'être transmises à un client. Ne saisissez aucune donnée personnelle de client : la question est transmise à un service externe de rédaction pour préparer le résumé.</div>
</div>
<div id="sp-out" class="mt-3" aria-live="polite"></div>
<script>
(function () {
  const ENDPOINT = <?= json_encode($spEndpoint) ?>;
  const $ = id => document.getElementById(id), out = $('sp-out');
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  async function run(q) {
    q = q.trim(); if (q.length < 3) { out.innerHTML = '<div class="alert alert-warning">Saisissez une question ou quelques mots.</div>'; return; }
    $('sp-go').disabled = true; out.innerHTML = '<div class="cap-card"><span class="spinner-border spinner-border-sm text-danger me-2"></span>Recherche dans les fiches…</div>';
    let r; try { r = await (await fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' }, body: JSON.stringify({ q }), credentials: 'same-origin' })).json(); } catch (e) { r = { error: 'Connexion impossible. Réessayez.' }; }
    $('sp-go').disabled = false;
    if (!r.ok) { out.innerHTML = '<div class="alert alert-danger">' + esc(r.error || 'Erreur.') + '</div>'; return; }
    let h = '';
    if (r.answer) h += '<div class="cap-card" style="border-left:4px solid #e4002b"><div class="d-flex justify-content-between align-items-center"><h2 class="h6 mb-2 flex-grow-1 me-2"><i class="fas fa-align-left me-2 text-danger"></i>Réponse synthétique</h2><button type="button" class="btn btn-sm btn-outline-secondary no-print" id="sp-copy"><i class="fas fa-copy me-1"></i>Copier</button></div><div class="sp-ans" id="sp-ans">' + r.answer + '</div><div class="small text-muted mt-2">Résumé établi à partir des fiches ci-dessous (les pastilles <a class="sp-ref" href="#sp-f1" style="pointer-events:none">1</a> renvoient à la fiche correspondante) : à vérifier sur service-public.fr avant de répondre à un client.</div></div>';
    if (!r.answer && r.results.length) h += '<div class="small text-muted mt-2"><i class="fas fa-circle-info me-1"></i>Le résumé n\'est pas disponible pour le moment : consultez les fiches ci-dessous.' + (r.detail ? ' <span class="text-danger">(administrateur : ' + esc(r.detail) + ')</span>' : '') + '</div>';
    if (!r.results.length) h += '<div class="alert alert-info mt-3">Aucune fiche ne correspond. Essayez avec d\'autres mots.</div>';
    else {
      h += '<div class="cap-card mt-3"><h2 class="h6"><i class="fas fa-book-open me-2 text-danger"></i>Fiches de service-public.fr</h2>';
      r.results.forEach((f, i) => { h += '<div class="py-2 sp-fiche ' + (i ? 'border-top' : '') + '" id="sp-f' + (i + 1) + '"><div><span class="text-muted me-1">[' + (i + 1) + ']</span><a href="' + esc(f.url) + '" target="_blank" rel="noopener noreferrer"><strong>' + esc(f.titre) + '</strong> <i class="fas fa-arrow-up-right-from-square small"></i></a></div>'
        + '<div class="small text-muted">' + (f.theme ? esc(f.theme) : '') + (f.date ? ' · mise à jour ' + esc(f.date) : '') + '</div><div class="small">' + esc(f.extrait) + '</div></div>'; });
      h += '</div>';
    }
    h += '<div class="small text-muted mt-2">Source : service-public.fr (Direction de l\'information légale et administrative), données ouvertes' + (r.maj ? ', fiches mises à jour jusqu\'au ' + esc(r.maj) : '') + '.</div>';
    out.innerHTML = h;
    const cp = $('sp-copy'); if (cp) cp.addEventListener('click', async () => { const t = $('sp-ans').innerText.replace(/\n{3,}/g, '\n\n').trim(); try { await navigator.clipboard.writeText(t); cp.innerHTML = '<i class="fas fa-check me-1"></i>Copié'; } catch (e) { cp.textContent = 'Copie impossible'; } setTimeout(() => cp.innerHTML = '<i class="fas fa-copy me-1"></i>Copier', 2000); });
  }
  $('sp-form').addEventListener('submit', e => { e.preventDefault(); run($('sp-q').value); });
  $('sp-ex').querySelectorAll('button').forEach(b => b.addEventListener('click', () => { $('sp-q').value = b.textContent; run(b.textContent); }));
})();
</script>
