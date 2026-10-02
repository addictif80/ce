<?php
$pageTitle = 'Recherche DPE par adresse';
require_once __DIR__ . '/../../templates/header.php';
?>
<style>
    .dpe-badge { display:inline-block; min-width:34px; text-align:center; font-weight:700; color:#fff; border-radius:6px; padding:3px 8px; }
    .dpe-A{background:#319834}.dpe-B{background:#33cc31}.dpe-C{background:#cbfc34;color:#333}
    .dpe-D{background:#fbfe06;color:#333}.dpe-E{background:#fccc05;color:#333}
    .dpe-F{background:#fc9935}.dpe-G{background:#fc0205}
    #dpe-suggestions { position:absolute; z-index:20; left:0; right:0; max-height:260px; overflow:auto; }
</style>

<div class="container-fluid">
    <h2 class="mb-1"><i class="fas fa-leaf me-2"></i>Recherche DPE par adresse</h2>
    <p class="text-muted">Interroge la base des DPE de l'ADEME (logements existants, depuis juillet 2021). Saisissez une adresse puis choisissez une suggestion.</p>

    <div class="card mb-3">
        <div class="card-body">
            <div class="position-relative">
                <div class="input-group">
                    <input type="text" id="dpe-addr" class="form-control" placeholder="Ex. : 174 rue de Rivoli 75001 Paris" autocomplete="off">
                    <button class="btn btn-primary" id="dpe-go" type="button"><i class="fas fa-search me-1"></i>Rechercher</button>
                </div>
                <div id="dpe-suggestions" class="list-group shadow" style="display:none"></div>
            </div>
        </div>
    </div>

    <div id="dpe-status"></div>
    <div id="dpe-results"></div>
</div>

<script>
(function() {
    const input = document.getElementById('dpe-addr');
    const sugg = document.getElementById('dpe-suggestions');
    const status = document.getElementById('dpe-status');
    const out = document.getElementById('dpe-results');
    let timer = null;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtDate = d => d ? d.split('-').reverse().join('/') : '';
    const badge = l => l ? `<span class="dpe-badge dpe-${esc(l)}">${esc(l)}</span>` : '–';

    // Autocomplétion via la Base Adresse Nationale
    input.addEventListener('input', function() {
        clearTimeout(timer);
        const v = this.value.trim();
        if (v.length < 4) { sugg.style.display = 'none'; return; }
        timer = setTimeout(async () => {
            try {
                const r = await fetch('https://api-adresse.data.gouv.fr/search/?type=housenumber&limit=6&q=' + encodeURIComponent(v));
                const j = await r.json();
                sugg.innerHTML = '';
                (j.features || []).forEach(f => {
                    const a = document.createElement('button');
                    a.type = 'button';
                    a.className = 'list-group-item list-group-item-action';
                    a.textContent = f.properties.label;
                    a.onclick = () => { input.value = f.properties.label; sugg.style.display = 'none'; search(); };
                    sugg.appendChild(a);
                });
                sugg.style.display = sugg.children.length ? 'block' : 'none';
            } catch (e) { sugg.style.display = 'none'; }
        }, 250);
    });
    document.addEventListener('click', e => { if (!sugg.contains(e.target) && e.target !== input) sugg.style.display = 'none'; });
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); sugg.style.display = 'none'; search(); } });
    document.getElementById('dpe-go').onclick = search;

    function row(r) {
        const compl = [r.complement_adresse_logement, r.complement_adresse_batiment].filter(Boolean).join(' – ');
        const etage = (r.numero_etage_appartement !== undefined && r.numero_etage_appartement !== null) ? ' · étage ' + r.numero_etage_appartement : '';
        const pdf = 'https://observatoire-dpe-audit.ademe.fr/afficher-dpe/' + encodeURIComponent(r.numero_dpe);
        return `<tr>
            <td>${fmtDate(r.date_etablissement_dpe)}<div class="small text-muted">valide jusqu'au ${fmtDate(r.date_fin_validite_dpe)}</div></td>
            <td class="text-center">${badge(r.etiquette_dpe)}</td>
            <td class="text-center">${badge(r.etiquette_ges)}</td>
            <td>${esc(r.type_batiment)}${etage}<div class="small text-muted">${esc(compl)}</div></td>
            <td>${r.surface_habitable_logement ?? ''} m²</td>
            <td>${r.conso_5_usages_par_m2_ep ?? ''} kWh/m²/an<div class="small text-muted">${r.emission_ges_5_usages_par_m2 ?? ''} kgCO₂/m²/an</div></td>
            <td>${r.cout_total_5_usages != null ? Math.round(r.cout_total_5_usages) + ' €/an' : ''}<div class="small text-muted">${esc(r.type_energie_principale_chauffage)}</div></td>
            <td>${esc(r.annee_construction || r.periode_construction)}</td>
            <td><a href="${pdf}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Voir le DPE officiel"><i class="fas fa-external-link-alt"></i></a>
                <div class="small text-muted">${esc(r.numero_dpe)}</div></td>
        </tr>`;
    }

    function table(title, rows, showAddr) {
        if (!rows.length) return '';
        return `<h5 class="mt-3">${title} (${rows.length})</h5>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
        <thead><tr><th>Date</th><th>DPE</th><th>GES</th><th>Logement</th><th>Surface</th><th>Conso. (EP)</th><th>Coût</th><th>Construction</th><th>Réf.</th></tr></thead>
        <tbody>${rows.map(r => (showAddr ? `<tr class="table-light"><td colspan="9" class="small"><i class="fas fa-map-marker-alt me-1"></i>${esc(r.adresse_ban)}</td></tr>` : '') + row(r)).join('')}</tbody></table></div>`;
    }

    async function search() {
        const q = input.value.trim();
        if (q.length < 5) return;
        out.innerHTML = '';
        status.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Recherche en cours…</div>';
        try {
            const r = await fetch('search_ajax.php?q=' + encodeURIComponent(q));
            const j = await r.json();
            if (j.error) { status.innerHTML = `<div class="alert alert-danger">${esc(j.error)}</div>`; return; }
            if (!j.exact.length && !j.approx.length) {
                status.innerHTML = '<div class="alert alert-warning">Aucun DPE trouvé pour cette adresse.</div>';
                return;
            }
            status.innerHTML = '';
            out.innerHTML = table('DPE à cette adresse', j.exact, false)
                + (j.exact.length ? '' : '<div class="alert alert-info mt-3">Aucune correspondance exacte : voici les adresses les plus proches.</div>')
                + table('Adresses approchantes', j.approx, true);
        } catch (e) {
            status.innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche.</div>';
        }
    }
})();
</script>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
