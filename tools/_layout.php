<?php
/**
 * Gabarit commun des pages publiques /tools (aucune connexion, aucune donnée enregistrée).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

function toolsHeader($title, $key = null, $extraHead = '') {
    $GLOBALS['toolsCurrentKey'] = $key;
    if ($key !== null) requirePublicTool($key);
    elseif (empty($GLOBALS['toolsNoGate'])) requireToolsAccess();
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background:#f5f5f5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        .tools-header { background:linear-gradient(135deg,#e4002b 0%,#c40025 100%); color:#fff; padding:20px 0; }
        .privacy-banner { background:#e8f5e9; border:2px solid #2e7d32; color:#1b5e20; border-radius:10px; padding:14px 18px; }
        .privacy-banner i { font-size:1.6rem; }
        @media print { .no-print { display:none !important; } }
    </style>
    <?= $extraHead ?>
</head>
<body>
<div class="tools-header no-print">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="h3 mb-0"><?= e($title) ?></h1>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php if (empty($GLOBALS['toolsNoGate'])): ?>
            <form action="<?= $key === null || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/modules/') === false ? '' : '../../tools/' ?>search.php" method="get" class="d-flex" role="search">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Rechercher dans les outils…" minlength="2" required value="<?= e($_GET['q'] ?? '') ?>" style="min-width:200px" aria-label="Recherche">
                <button class="btn btn-sm btn-light ms-1" aria-label="Rechercher"><i class="fas fa-search"></i></button>
            </form>
            <?php endif; ?>
            <?php if ($key !== null): ?>
            <a href="./" class="btn btn-sm btn-light"><i class="fas fa-th-large me-1"></i>Tous les outils</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php }

function privacyBanner($text = null) { ?>
<div class="privacy-banner d-flex align-items-center gap-3 my-3 no-print">
    <i class="fas fa-user-shield"></i>
    <div><strong>Aucune donnée n'est enregistrée.</strong>
        <?= $text ?? 'Ce qui est saisi sur cette page reste dans votre navigateur et disparaît quand vous la fermez ou l\'actualisez.' ?></div>
</div>
<?php }

function toolsFooter() {
    $key = $GLOBALS['toolsCurrentKey'] ?? null;
    if (!empty($GLOBALS['toolsNoFeedbackLink'])) { echo "</body>\n</html>\n"; return; } ?>
<div class="container text-center text-muted small my-4 no-print">
    <a href="feedback.php<?= $key ? '?tool=' . urlencode($key) : '' ?>" class="text-muted"><i class="fas fa-comment-dots me-1"></i>Un problème, une idée ? Envoyer un retour à l'administrateur</a>
</div>
</body>
</html>
<?php }

/**
 * Fenêtre de proposition (ajout ou modification) pour les codes utiles / contacts utiles.
 * Les boutons .js-propose-new et .js-propose-edit[data-row] ouvrent la fenêtre.
 */
function toolsProposalModal($kind) {
    $fields = getToolsProposalFields($kind);
    $long = ['fonction', 'a_contacter_pour'];
    $noun = $kind === 'code' ? 'un code' : 'un contact'; ?>
<div class="modal fade" id="proposalModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="post">
        <div class="modal-header"><h5 class="modal-title" id="proposalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="type" id="proposalType" value="create">
            <input type="hidden" name="target_id" id="proposalTarget" value="">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Votre prénom <span class="text-danger">*</span></label><input name="contributor_prenom" class="form-control" required maxlength="100"></div>
                <div class="col-md-6"><label class="form-label">Votre nom <span class="text-danger">*</span></label><input name="contributor_nom" class="form-control" required maxlength="100"></div>
                <?php foreach ($fields as $name => [$label, $max]): ?>
                <div class="col-12"><label class="form-label"><?= e($label) ?><?= in_array($name, ['code', 'service'], true) ? ' <span class="text-danger">*</span>' : '' ?></label>
                    <?php if (in_array($name, $long, true)): ?><textarea name="<?= e($name) ?>" id="pf-<?= e($name) ?>" class="form-control" rows="3" maxlength="<?= (int)$max ?>"></textarea>
                    <?php else: ?><input name="<?= e($name) ?>" id="pf-<?= e($name) ?>" class="form-control" maxlength="<?= (int)$max ?>"><?php endif; ?></div>
                <?php endforeach; ?>
                <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="col-12"><div class="alert alert-warning mb-0 small"><i class="fas fa-info-circle me-1"></i>Votre proposition est enregistrée avec vos nom et prénom, puis soumise à validation par un administrateur avant publication.</div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i>Envoyer la proposition</button></div>
    </form>
</div></div></div>
<script>
(function() {
    const modal = new bootstrap.Modal(document.getElementById('proposalModal'));
    const names = <?= json_encode(array_keys($fields)) ?>;
    function open(mode, row) {
        document.getElementById('proposalType').value = mode;
        document.getElementById('proposalTarget').value = row ? row.id : '';
        document.getElementById('proposalTitle').textContent = mode === 'edit' ? 'Suggérer une modification' : 'Proposer <?= $noun ?>';
        names.forEach(n => document.getElementById('pf-' + n).value = row ? (row[n] || '') : '');
        modal.show();
    }
    document.addEventListener('click', e => {
        if (e.target.closest('.js-propose-new')) open('create', null);
        const b = e.target.closest('.js-propose-edit');
        if (b) open('edit', JSON.parse(b.dataset.row));
    });
})();
</script>
<?php }
