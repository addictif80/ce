<?php
// Interface de vérification RGE, partagée par le module du portail et la page publique /tools.
// Variable attendue : $rgeApiUrl (URL de api.php, relative à la page appelante).
?>
<style>
    .rge-badge-ok { background:#2e7d32; } .rge-badge-ko { background:#c62828; }
</style>
<div class="card mb-3"><div class="card-body">
    <label class="form-label fw-semibold">Nom de l'entreprise, SIREN (9 chiffres) ou SIRET (14 chiffres)</label>
    <div class="input-group">
        <input type="text" id="rge-q" class="form-control" placeholder="Ex. : EST ISOLATION, 318720224 ou 31872022400027" autocomplete="off" maxlength="100">
        <button class="btn btn-primary" id="rge-go" type="button"><i class="fas fa-search me-1"></i>Vérifier</button>
    </div>
    <div class="form-text">Source : liste officielle des entreprises RGE publiée par l'ADEME<span id="rge-updated"></span>. Une entreprise est « RGE » lorsqu'au moins une qualification est en cours de validité.</div>
</div></div>
<div id="rge-status"></div>
<div id="rge-results"></div>

<script>
(function() {
    const api = <?= json_encode($rgeApiUrl) ?>;
    const input = document.getElementById('rge-q'), status = document.getElementById('rge-status'), out = document.getElementById('rge-results');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = d => d ? d.split('-').reverse().join('/') : '–';
    const safeUrl = u => /^https?:\/\//i.test(u || '') ? u : '';
    const fmtSiret = s => s.replace(/^(\d{3})(\d{3})(\d{3})(\d{5})$/, '$1 $2 $3 $4');

    function card(c) {
        const site = safeUrl(c.site);
        const rows = c.qualifications.map(q => `<tr class="${q.valide ? '' : 'text-muted'}">
            <td>${esc(q.domaine)}<div class="small text-muted">${esc(q.meta_domaine)}</div></td>
            <td>${esc(q.qualification)}</td>
            <td>${esc(q.organisme)}<div class="small text-muted">${esc(q.certificat)}</div></td>
            <td>${fmt(q.debut)} → ${fmt(q.fin)}</td>
            <td><span class="badge ${q.valide ? 'rge-badge-ok' : 'rge-badge-ko'}">${q.valide ? 'En cours' : 'Expirée'}</span></td>
            <td>${safeUrl(q.url) ? `<a href="${esc(q.url)}" target="_blank" rel="noopener noreferrer" title="Certificat"><i class="fas fa-file-pdf"></i></a>` : ''}</td></tr>`).join('');
        return `<div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div><h2 class="h5 mb-1">${esc(c.nom)}</h2>
                    <div class="text-muted small">SIRET ${esc(fmtSiret(c.siret))} · SIREN ${esc(c.siren)}</div>
                    <div class="small"><i class="fas fa-map-marker-alt me-1"></i>${esc(c.adresse)}</div>
                    <div class="small">${c.telephone ? `<i class="fas fa-phone-alt me-1"></i>${esc(c.telephone)} ` : ''}${c.email ? `<i class="fas fa-envelope ms-2 me-1"></i><a href="mailto:${esc(c.email)}">${esc(c.email)}</a> ` : ''}${site ? `<i class="fas fa-globe ms-2 me-1"></i><a href="${esc(site)}" target="_blank" rel="noopener noreferrer">${esc(site)}</a>` : ''}</div></div>
                <div class="text-end"><span class="badge fs-6 ${c.valide ? 'rge-badge-ok' : 'rge-badge-ko'}">${c.valide ? '<i class="fas fa-check-circle me-1"></i>RGE valide' : '<i class="fas fa-times-circle me-1"></i>Qualifications expirées'}</span>
                    <div class="mt-2"><button class="btn btn-sm btn-outline-secondary rge-detail" data-siret="${esc(c.siret)}" title="Afficher toutes les qualifications de cet établissement">Détail</button></div></div>
            </div>
            <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Domaine de travaux</th><th>Qualification</th><th>Organisme</th><th>Validité</th><th>État</th><th></th></tr></thead>
                <tbody>${rows}</tbody></table></div>
        </div></div>`;
    }

    async function search(q) {
        q = (q ?? input.value).trim();
        if (!q) return;
        input.value = q;
        out.innerHTML = '';
        status.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Recherche en cours…</div>';
        try {
            const r = await fetch(api + '?q=' + encodeURIComponent(q));
            const j = await r.json();
            if (j.error) { status.innerHTML = `<div class="alert alert-danger">${esc(j.error)}</div>`; return; }
            if (j.updated) document.getElementById('rge-updated').textContent = ' (données du ' + fmt(j.updated.slice(0, 10)) + ')';
            if (!j.companies.length) {
                status.innerHTML = '<div class="alert alert-warning"><i class="fas fa-times-circle me-1"></i><strong>Aucune qualification RGE trouvée</strong> pour cette recherche. Vérifiez l\'orthographe ou essayez avec le SIREN / SIRET : l\'entreprise n\'est peut-être pas (ou plus) référencée RGE.</div>';
                return;
            }
            status.innerHTML = `<div class="mb-2 text-muted">${j.nb_companies} établissement(s) trouvé(s)${j.truncated ? ' — résultats limités : affinez avec le nom complet, le SIREN ou le SIRET' : ''}</div>`;
            out.innerHTML = j.companies.map(card).join('');
        } catch (e) { status.innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche.</div>'; }
    }
    document.getElementById('rge-go').onclick = () => search();
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });
    out.addEventListener('click', e => { const b = e.target.closest('.rge-detail'); if (b) search(b.dataset.siret); });
    const initial = new URLSearchParams(location.search).get('q');
    if (initial) search(initial);
})();
</script>
