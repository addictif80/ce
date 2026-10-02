<?php
require_once __DIR__ . '/_layout.php';

toolsHeader('Générateur de courrier', 'courrier', '<style>
    .wy-toolbar{background:#f8f9fa;border:1px solid #dee2e6;border-bottom:none;border-radius:6px 6px 0 0;padding:6px 10px;display:flex;gap:4px;flex-wrap:wrap;align-items:center}
    .wy-toolbar button{background:#fff;border:1px solid #dee2e6;border-radius:4px;width:32px;height:28px;font-size:12px;color:#6c757d}
    .wy-editor{min-height:220px;border:1px solid #dee2e6;border-radius:0 0 6px 6px;padding:12px;background:#fff}
    .var-row{display:flex;gap:8px;margin-bottom:6px}.var-row .var-name{flex:0 0 180px}.var-row .var-value{flex:1}
    #letter{display:none}
    @media print{
        body{background:#fff}.no-print{display:none!important}
        #letter{display:block!important;position:relative;font-family:Arial,Helvetica,sans-serif;font-size:11pt;line-height:1.4;color:#000}
        @page{size:A4;margin:15mm 20mm}
    }
</style>');
?>
<div class="container my-3 no-print">
    <?php privacyBanner('Le courrier est rédigé et mis en page dans votre navigateur : rien n\'est envoyé, et aucun modèle n\'est enregistré. Si vous actualisez la page, tout est effacé.') ?>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 text-uppercase text-muted">Expéditeur</h2>
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label">Nom et prénom</label><input id="s-nom" class="form-control" autocomplete="off"></div>
            <div class="col-md-4"><label class="form-label">Adresse</label><input id="s-adresse" class="form-control" autocomplete="off"></div>
            <div class="col-md-4"><label class="form-label">Code postal et ville</label><input id="s-cpville" class="form-control" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Téléphone</label><input id="s-tel" class="form-control" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input id="s-email" class="form-control" autocomplete="off"></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 text-uppercase text-muted">Destinataire</h2>
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label">Civilité</label>
                <select id="d-civ" class="form-select"><option value="">--</option><option>Madame</option><option>Monsieur</option></select></div>
            <div class="col-md-4"><label class="form-label">Nom</label><input id="d-nom" class="form-control" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Prénom</label><input id="d-prenom" class="form-control" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Complément (titre, service…)</label><input id="d-compl" class="form-control" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Adresse</label><input id="d-adresse" class="form-control" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Complément d'adresse</label><input id="d-adresse2" class="form-control" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Code postal et ville</label><input id="d-cpville" class="form-control" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Lieu</label><input id="c-lieu" class="form-control" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Date</label><input id="c-date" type="date" class="form-control"></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 text-uppercase text-muted">Courrier</h2>
        <label class="form-label">Objet</label>
        <input id="c-objet" class="form-control mb-3" autocomplete="off">

        <div class="border rounded p-2 mb-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong><i class="fas fa-code me-1"></i>Variables dynamiques</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-var"><i class="fas fa-plus"></i> Ajouter une variable</button>
            </div>
            <div class="small text-muted mb-2">Cliquez sur « Insérer » pour placer <code>{{nom}}</code> dans le texte ; il sera remplacé par la valeur au moment de l'impression.
                Variables fixes : <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-ins="civilite"><code>{{civilite}}</code></button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-ins="nom_dest"><code>{{nom_dest}}</code></button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-ins="prenom_dest"><code>{{prenom_dest}}</code></button></div>
            <div id="vars"></div>
        </div>

        <label class="form-label">Corps du courrier</label>
        <div class="wy-toolbar">
            <button type="button" data-cmd="bold" title="Gras"><i class="fas fa-bold"></i></button>
            <button type="button" data-cmd="italic" title="Italique"><i class="fas fa-italic"></i></button>
            <button type="button" data-cmd="underline" title="Souligné"><i class="fas fa-underline"></i></button>
            <button type="button" data-cmd="insertUnorderedList" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
            <button type="button" data-cmd="insertOrderedList" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
        </div>
        <div id="editor" class="wy-editor" contenteditable="true"></div>
    </div></div>

    <div class="d-flex gap-2 mb-4">
        <button type="button" class="btn btn-danger" id="print"><i class="fas fa-print me-1"></i>Imprimer / enregistrer en PDF</button>
        <button type="button" class="btn btn-outline-secondary" id="reset"><i class="fas fa-eraser me-1"></i>Tout effacer</button>
    </div>
</div>

<div id="letter"></div>

<script>
(function() {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const val = id => $(id).value.trim();
    const editor = $('editor');
    const today = () => new Date().toISOString().slice(0, 10);
    $('c-date').value = today();

    document.querySelectorAll('[data-cmd]').forEach(b => b.onclick = () => { editor.focus(); document.execCommand(b.dataset.cmd, false, null); });
    document.querySelectorAll('[data-ins]').forEach(b => b.onclick = () => { editor.focus(); document.execCommand('insertText', false, '{{' + b.dataset.ins + '}}'); });

    function addVar() {
        const row = document.createElement('div');
        row.className = 'var-row';
        row.innerHTML = `<input class="form-control form-control-sm var-name" placeholder="Nom (ex : date-rdv)" autocomplete="off">
            <input class="form-control form-control-sm var-value" placeholder="Valeur (ex : 25/03 à 14h30)" autocomplete="off">
            <button type="button" class="btn btn-sm btn-outline-secondary ins"><i class="fas fa-arrow-down"></i> Insérer</button>
            <button type="button" class="btn btn-sm btn-outline-danger del"><i class="fas fa-times"></i></button>`;
        row.querySelector('.ins').onclick = () => {
            const n = row.querySelector('.var-name').value.trim();
            if (!n) { row.querySelector('.var-name').focus(); return; }
            editor.focus(); document.execCommand('insertText', false, '{{' + n + '}}');
        };
        row.querySelector('.del').onclick = () => row.remove();
        $('vars').appendChild(row);
    }
    $('add-var').onclick = addVar;

    function variables() {
        const v = {civilite: val('d-civ'), nom_dest: val('d-nom'), prenom_dest: val('d-prenom')};
        document.querySelectorAll('#vars .var-row').forEach(r => {
            const n = r.querySelector('.var-name').value.trim();
            if (n) v[n] = r.querySelector('.var-value').value;
        });
        return v;
    }
    // Les valeurs sont échappées avant d'être injectées dans le HTML du corps
    function replaceVars(html, vars) {
        for (const [n, v] of Object.entries(vars)) {
            html = html.split('{{' + n + '}}').join(esc(v));
        }
        return html;
    }
    const fmtDate = d => d ? d.split('-').reverse().join('/') : '';
    const lines = arr => arr.filter(Boolean).map(esc).join('<br>');

    $('print').onclick = () => {
        if (!editor.innerText.trim()) { alert('Veuillez rédiger le corps du courrier.'); return; }
        const dest = lines([[val('d-civ'), val('d-prenom'), val('d-nom')].filter(Boolean).join(' '), val('d-compl'), val('d-adresse'), val('d-adresse2'), val('d-cpville')]);
        const exp = lines([val('s-nom'), val('s-adresse'), val('s-cpville'), val('s-tel'), val('s-email')]);
        const lieuDate = [val('c-lieu'), 'le ' + fmtDate(val('c-date'))].filter(Boolean).join(', ');
        $('letter').innerHTML = `
            <div style="font-size:9pt;line-height:1.3;min-height:30mm;">${exp}</div>
            <div style="position:absolute;top:32mm;left:100mm;width:85mm;font-size:11pt;line-height:1.5;">${dest}</div>
            <div style="margin-top:25mm;text-align:right;">${esc(lieuDate)}</div>
            <br><br>
            <div style="font-weight:bold;">${val('c-objet') ? 'Objet : ' + esc(val('c-objet')) : ''}</div>
            <br><br>
            <div style="text-align:justify;">${replaceVars(editor.innerHTML, variables())}</div>
            <div style="text-align:right;margin-top:40px;">${esc(val('s-nom'))}</div>`;
        window.print();
    };

    $('reset').onclick = () => {
        if (!confirm('Effacer tout le contenu du courrier ?')) return;
        document.querySelectorAll('input.form-control, select').forEach(i => i.value = '');
        $('c-date').value = today();
        editor.innerHTML = ''; $('vars').innerHTML = '';
    };
})();
</script>
<?php toolsFooter();
