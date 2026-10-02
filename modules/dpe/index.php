<?php
// Page publique : accessible sans connexion
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche DPE par adresse - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <style>
        body { background:#f5f5f5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        .page-header { background:linear-gradient(135deg,#e4002b 0%,#c40025 100%); color:#fff; padding:20px 0; }
        .dpe-badge { display:inline-block; min-width:30px; text-align:center; font-weight:700; color:#fff; border-radius:6px; padding:2px 8px; }
        .dpe-A{background:#319834}.dpe-B{background:#33cc31}.dpe-C{background:#cbfc34;color:#333}
        .dpe-D{background:#fbfe06;color:#333}.dpe-E{background:#fccc05;color:#333}
        .dpe-F{background:#fc9935}.dpe-G{background:#fc0205}.dpe-none{background:#999}
        #dpe-suggestions { position:absolute; z-index:1000; left:0; right:0; max-height:260px; overflow:auto; }
        #dpe-map { height:480px; border-radius:8px; }
        .dpe-pin { width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 1px 4px rgba(0,0,0,.5);
                   color:#fff; font-weight:700; font-size:12px; line-height:22px; text-align:center; }
        .leaflet-popup-content { max-height:260px; overflow:auto; }
    </style>
</head>
<body>
<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="h3 mb-0"><i class="fas fa-leaf me-2"></i>Recherche DPE par adresse</h1>
        <a href="../../index.php" class="btn btn-sm btn-light"><i class="fas fa-home me-1"></i>Application</a>
    </div>
</div>

<div class="container my-3">
    <p class="text-muted mb-2">Base open data de l'ADEME. Saisissez une adresse, ou déplacez la carte (zoom ≥ 16) pour voir les DPE des immeubles alentour.</p>

    <div class="card mb-3"><div class="card-body">
        <div class="position-relative">
            <div class="input-group">
                <input type="text" id="dpe-addr" class="form-control" placeholder="Ex. : 174 rue de Rivoli 75001 Paris" autocomplete="off">
                <button class="btn btn-primary" id="dpe-go" type="button"><i class="fas fa-search me-1"></i>Rechercher</button>
            </div>
            <div id="dpe-suggestions" class="list-group shadow" style="display:none"></div>
        </div>
        <div class="mt-2">
            <div class="form-check form-check-inline"><input class="form-check-input src" type="checkbox" id="src-new" value="new" checked><label class="form-check-label" for="src-new">DPE depuis juillet 2021</label></div>
            <div class="form-check form-check-inline"><input class="form-check-input src" type="checkbox" id="src-old" value="old" checked><label class="form-check-label" for="src-old">DPE avant juillet 2021</label></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body p-2">
        <div id="dpe-map"></div>
        <div id="map-status" class="small text-muted mt-1"></div>
    </div></div>

    <div id="dpe-status"></div>
    <div id="dpe-results"></div>
</div>

<script>
(function() {
    const input = document.getElementById('dpe-addr');
    const sugg = document.getElementById('dpe-suggestions');
    const status = document.getElementById('dpe-status');
    const out = document.getElementById('dpe-results');
    const mapStatus = document.getElementById('map-status');
    const COLORS = {A:'#319834',B:'#33cc31',C:'#9ccf1f',D:'#d9d900',E:'#fccc05',F:'#fc9935',G:'#fc0205'};
    let timer = null, mapTimer = null, markers = L.layerGroup();

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtDate = d => d ? d.split('-').reverse().join('/') : '';
    const badge = l => l ? `<span class="dpe-badge dpe-${esc(l)}">${esc(l)}</span>` : '<span class="dpe-badge dpe-none" title="Non classé">–</span>';
    const sources = () => [...document.querySelectorAll('.src:checked')].map(c => c.value).join(',') || 'new';
    const officialUrl = n => 'https://observatoire-dpe-audit.ademe.fr/afficher-dpe/' + encodeURIComponent(n);

    // ---------- Carte ----------
    const map = L.map('dpe-map').setView([46.6, 2.5], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© OpenStreetMap'}).addTo(map);
    markers.addTo(map);

    async function loadMap() {
        if (map.getZoom() < 16) { markers.clearLayers(); mapStatus.textContent = 'Zoomez davantage (niveau 16 minimum) pour afficher les DPE.'; return; }
        const b = map.getBounds();
        const bbox = [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].map(v => v.toFixed(6)).join(',');
        mapStatus.textContent = 'Chargement…';
        try {
            const r = await fetch('api.php?mode=map&sources=' + sources() + '&bbox=' + bbox);
            const j = await r.json();
            if (j.error) { mapStatus.textContent = j.error; return; }
            markers.clearLayers();
            j.points.forEach(p => {
                const l = p.dpe[0].etiquette_dpe;
                const icon = L.divIcon({className: '', iconSize: [26, 26],
                    html: `<div class="dpe-pin" style="background:${COLORS[l] || '#999'}">${l || '–'}</div>`});
                const lines = p.dpe.map(d => `<li>${fmtDate(d.date)} ${badge(d.etiquette_dpe)} ${d.surface ? Math.round(d.surface) + ' m²' : ''}
                    ${d.source === 'old' ? '<span class="badge bg-secondary">avant 07/2021</span>' : ''}</li>`).join('');
                const m = L.marker([p.lat, p.lon], {icon}).bindPopup(
                    `<b>${esc(p.adresse)}</b><br>${p.count} DPE<ul class="ps-3 mb-1">${lines}</ul>
                     <button class="btn btn-sm btn-primary" data-addr="${esc(p.adresse)}">Voir le détail</button>`);
                markers.addLayer(m);
            });
            mapStatus.textContent = j.points.length + ' adresse(s) affichée(s)' + (j.truncated ? ' (résultats tronqués, zoomez pour affiner)' : '') + (j.partial ? ' – une source est indisponible' : '');
        } catch (e) { mapStatus.textContent = 'Erreur de chargement de la carte.'; }
    }
    map.on('moveend', () => { clearTimeout(mapTimer); mapTimer = setTimeout(loadMap, 400); });
    document.getElementById('dpe-map').addEventListener('click', e => {
        const a = e.target.closest('button[data-addr]');
        if (a) { input.value = a.dataset.addr; search(false); }
    });
    document.querySelectorAll('.src').forEach(c => c.addEventListener('change', loadMap));

    // ---------- Autocomplétion BAN ----------
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
                    a.onclick = () => {
                        input.value = f.properties.label; sugg.style.display = 'none';
                        const [lon, lat] = f.geometry.coordinates;
                        map.setView([lat, lon], 18);
                        search(false);
                    };
                    sugg.appendChild(a);
                });
                sugg.style.display = sugg.children.length ? 'block' : 'none';
            } catch (e) { sugg.style.display = 'none'; }
        }, 250);
    });
    document.addEventListener('click', e => { if (!sugg.contains(e.target) && e.target !== input) sugg.style.display = 'none'; });
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); sugg.style.display = 'none'; search(true); } });
    document.getElementById('dpe-go').onclick = () => search(true);

    // ---------- Résultats ----------
    function row(r) {
        const old = r.source === 'old';
        const etage = (r.etage !== null && r.etage !== undefined) ? ' · étage ' + r.etage : '';
        return `<tr>
            <td>${fmtDate(r.date)}${r.validite ? `<div class="small text-muted">valide jusqu'au ${fmtDate(r.validite)}</div>` : ''}
                ${old ? '<span class="badge bg-secondary">avant 07/2021</span>' : ''}</td>
            <td class="text-center">${badge(r.etiquette_dpe)}</td>
            <td class="text-center">${badge(r.etiquette_ges)}</td>
            <td>${esc(r.type)}${etage}<div class="small text-muted">${esc(r.complement)}</div></td>
            <td>${r.surface != null ? Math.round(r.surface * 10) / 10 + ' m²' : ''}</td>
            <td>${r.conso != null ? r.conso + ' kWh/m²/an' : ''}<div class="small text-muted">${r.ges != null ? r.ges + ' kgCO₂/m²/an' : ''}</div></td>
            <td>${r.cout != null ? Math.round(r.cout) + ' €/an' : ''}<div class="small text-muted">${esc(r.energie)}</div></td>
            <td>${esc(r.construction)}</td>
            <td><a href="${officialUrl(r.numero_dpe)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Voir le DPE officiel"><i class="fas fa-external-link-alt"></i></a>
                <div class="small text-muted">${esc(r.numero_dpe)}</div></td></tr>`;
    }
    function table(title, rows, showAddr) {
        if (!rows.length) return '';
        return `<h5 class="mt-3">${title} (${rows.length})</h5>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle bg-white">
        <thead><tr><th>Date</th><th>DPE</th><th>GES</th><th>Logement</th><th>Surface</th><th>Conso. (EP)</th><th>Coût</th><th>Construction</th><th>Réf.</th></tr></thead>
        <tbody>${rows.map(r => (showAddr ? `<tr class="table-light"><td colspan="9" class="small"><i class="fas fa-map-marker-alt me-1"></i>${esc(r.adresse)}</td></tr>` : '') + row(r)).join('')}</tbody></table></div>`;
    }

    async function search(geocode) {
        const q = input.value.trim();
        if (q.length < 5) return;
        out.innerHTML = '';
        status.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Recherche en cours…</div>';
        if (geocode) { // saisie libre : on centre la carte via la BAN si possible
            fetch('https://api-adresse.data.gouv.fr/search/?limit=1&q=' + encodeURIComponent(q)).then(r => r.json()).then(j => {
                const f = (j.features || [])[0];
                if (f) map.setView([f.geometry.coordinates[1], f.geometry.coordinates[0]], 18);
            }).catch(() => {});
        }
        try {
            const r = await fetch('api.php?mode=address&sources=' + sources() + '&q=' + encodeURIComponent(q));
            const j = await r.json();
            if (j.error) { status.innerHTML = `<div class="alert alert-danger">${esc(j.error)}</div>`; return; }
            if (!j.exact.length && !j.approx.length) { status.innerHTML = '<div class="alert alert-warning">Aucun DPE trouvé pour cette adresse.</div>'; return; }
            status.innerHTML = j.partial ? '<div class="alert alert-warning">Une des deux bases est momentanément indisponible : résultats partiels.</div>' : '';
            out.innerHTML = table('DPE à cette adresse', j.exact, false)
                + (j.exact.length ? '' : '<div class="alert alert-info mt-3">Aucune correspondance exacte : voici les adresses les plus proches.</div>')
                + table('Adresses approchantes', j.approx, true);
            out.scrollIntoView({behavior: 'smooth'});
        } catch (e) { status.innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche.</div>'; }
    }
})();
</script>
</body>
</html>
