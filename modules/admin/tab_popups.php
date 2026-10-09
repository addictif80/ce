<?php
// Onglet « Popups » de l'administration : deux fenêtres d'information indépendantes (tableau de bord et/ou /tools).
require_once __DIR__ . '/../../includes/popups.php';
$freqs = popupFrequencies();
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> Comment ça marche ?</strong>
    Deux popups indépendantes, chacune avec son propre message, ses dates et sa fréquence d'affichage. Pour chacune, choisissez <strong>où elle s'affiche</strong> :
    sur le <strong>tableau de bord</strong> des utilisateurs connectés, sur <strong>/tools</strong> (après validation du code d'accès, ou directement si /tools n'est pas protégé), ou sur <strong>les deux</strong>.
    Si les deux popups s'affichent sur la même page, elles apparaissent l'une après l'autre.
</div>
<?php require_once __DIR__ . '/../../includes/intro_tools.php'; ?>
<div class="card mb-4" id="intro-card"><div class="card-header"><strong><i class="fas fa-play-circle"></i> Diaporama d'accueil de /tools</strong></div>
    <div class="card-body">
        <form method="post" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="action" value="save_intro">
            <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" name="enabled" id="introEnabled" <?= toolsIntroEnabled() ? 'checked' : '' ?>><label class="form-check-label" for="introEnabled">Afficher la présentation animée à la première visite de /tools (une fois par session de navigation, après le code d'accès)</label></div>
            <button class="btn btn-ce btn-sm">Enregistrer</button>
        </form>
        <div class="form-text mt-2">Les visiteurs peuvent la revoir à tout moment avec le lien « Revoir la présentation » en haut de /tools.</div>
    </div></div>
<?php foreach (popupSlots() as $slot => $label): $p = getPopupSettings($slot); ?>
<div class="data-table-container mb-4" id="<?= e($slot) ?>-card">
    <div class="data-table-header"><h3><i class="fas fa-window-restore"></i> <?= e($label) ?></h3>
        <?php $on = $p['enabled'] && trim(strip_tags($p['html'])) !== ''; ?>
        <span class="badge bg-<?= $on ? 'success' : 'secondary' ?>"><?= $on ? 'Active' : 'Désactivée' ?></span></div>
    <div class="p-3">
        <form method="post" class="popup-form" data-slot="<?= e($slot) ?>">
            <input type="hidden" name="action" value="save_popup"><input type="hidden" name="slot" value="<?= e($slot) ?>">
            <input type="hidden" name="html" class="popup-html">
            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="enabled" id="<?= e($slot) ?>_en" <?= $p['enabled'] ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="<?= e($slot) ?>_en">Popup activée</label></div></div>
                <div class="col-md-8">
                    <span class="me-3 fw-semibold">Afficher sur :</span>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dashboard" id="<?= e($slot) ?>_db" <?= $p['dashboard'] ? 'checked' : '' ?>><label class="form-check-label" for="<?= e($slot) ?>_db">Tableau de bord (utilisateurs connectés)</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="tools" id="<?= e($slot) ?>_tl" <?= $p['tools'] ? 'checked' : '' ?>><label class="form-check-label" for="<?= e($slot) ?>_tl">/tools (après validation du code)</label></div>
                </div>
                <div class="col-md-6"><label class="form-label">Titre de la fenêtre (facultatif)</label><input type="text" name="title" class="form-control" maxlength="150" value="<?= e($p['title']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Texte du bouton</label><input type="text" name="button" class="form-control" maxlength="40" value="<?= e($p['button']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Fréquence</label>
                    <select name="frequency" class="form-select"><?php foreach ($freqs as $k => $v): ?><option value="<?= e($k) ?>" <?= $p['frequency'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Afficher à partir du</label><input type="date" name="start" class="form-control" value="<?= e($p['start']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Jusqu'au (inclus)</label><input type="date" name="end" class="form-control" value="<?= e($p['end']) ?>"></div>
                <div class="col-md-6 d-flex align-items-end"><div class="form-text">Dates facultatives : en dehors de la période, la popup n'est pas affichée. Laissez vide pour un affichage permanent.</div></div>
            </div>
            <label class="form-label">Message</label>
            <div class="popup-toolbar" style="background:#f8f9fa;border:1px solid #dee2e6;border-bottom:none;border-radius:6px 6px 0 0;padding:6px 10px;display:flex;gap:4px;flex-wrap:wrap;align-items:center">
                <button type="button" class="btn btn-sm btn-light border" data-cmd="bold" title="Gras"><i class="fas fa-bold"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-cmd="italic" title="Italique"><i class="fas fa-italic"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-cmd="underline" title="Souligné"><i class="fas fa-underline"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-block="h3" title="Titre"><i class="fas fa-heading"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-block="p" title="Paragraphe normal"><i class="fas fa-paragraph"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-cmd="insertUnorderedList" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-cmd="insertOrderedList" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
                <button type="button" class="btn btn-sm btn-light border popup-link" title="Insérer un lien"><i class="fas fa-link"></i></button>
                <button type="button" class="btn btn-sm btn-light border" data-cmd="unlink" title="Retirer le lien"><i class="fas fa-unlink"></i></button>
                <input type="color" class="popup-color" value="#cc0000" title="Couleur du texte" style="width:30px;height:30px;border:none;padding:0;cursor:pointer">
                <button type="button" class="btn btn-sm btn-light border" data-cmd="removeFormat" title="Effacer la mise en forme"><i class="fas fa-eraser"></i></button>
            </div>
            <div class="popup-editor" contenteditable="true" style="min-height:170px;border:1px solid #dee2e6;border-radius:0 0 6px 6px;padding:12px;background:#fff"><?= $p['html'] /* assaini à l'enregistrement */ ?></div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
                <button type="button" class="btn btn-outline-secondary btn-sm popup-preview"><i class="fas fa-eye"></i> Aperçu</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="popupPreview" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="ppvTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="ppvBody"></div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="ppvBtn">Fermer</button></div>
</div></div></div>
<script>
(function () {
    document.querySelectorAll('.popup-form').forEach(form => {
        const ed = form.querySelector('.popup-editor');
        const run = (cmd, val) => { ed.focus(); document.execCommand(cmd, false, val || null); };
        form.querySelectorAll('[data-cmd]').forEach(b => b.onclick = () => run(b.dataset.cmd));
        form.querySelectorAll('[data-block]').forEach(b => b.onclick = () => run('formatBlock', b.dataset.block));
        form.querySelector('.popup-color').oninput = function () { run('foreColor', this.value); };
        form.querySelector('.popup-link').onclick = () => {
            const url = prompt('Adresse du lien (https://… ou mailto:…)');
            if (!url) return;
            if (!/^(https?:\/\/|mailto:)/i.test(url)) { alert('Le lien doit commencer par http://, https:// ou mailto:'); return; }
            run('createLink', url);
        };
        ed.addEventListener('paste', e => { e.preventDefault(); document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text/plain')); });
        form.addEventListener('submit', () => { form.querySelector('.popup-html').value = ed.innerHTML; });
        form.querySelector('.popup-preview').onclick = () => {
            document.getElementById('ppvTitle').textContent = form.querySelector('[name=title]').value;
            document.getElementById('ppvBody').innerHTML = ed.innerHTML;
            document.getElementById('ppvBtn').textContent = form.querySelector('[name=button]').value || 'Fermer';
            new bootstrap.Modal(document.getElementById('popupPreview')).show();
        };
    });
})();
</script>
