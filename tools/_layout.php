<?php
/**
 * Gabarit commun des pages publiques /tools (aucune connexion, aucune donnée enregistrée).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/nouveautes.php';
require_once __DIR__ . '/../includes/tool_tours.php';
require_once __DIR__ . '/../includes/tour.php';
require_once __DIR__ . '/../includes/stats.php';

function toolsHeader($title, $key = null, $extraHead = '') {
    $GLOBALS['toolsCurrentKey'] = $key;
    if ($key !== null) requirePublicTool($key);
    elseif (empty($GLOBALS['toolsNoGate'])) requireToolsAccess();
    if ($key !== null) statsHit($key, 'view'); elseif (empty($GLOBALS['toolsNoGate'])) statsHit('accueil', 'view');
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
        .tools-hbtn { position:relative; }
        .tools-hbtn .nb { position:absolute; top:-7px; right:-7px; background:#fff; color:#e4002b; border-radius:999px; font-size:.65rem; font-weight:700; padding:1px 6px; display:none; box-shadow:0 1px 4px rgba(0,0,0,.3); }
        /* Mode face-à-face client : texte agrandi, éléments internes masqués */
        html.f2f { font-size:118%; }
        html.f2f .no-client { display:none !important; }
        html.f2f body { background:#fff; }
        #f2fBar { display:none; background:#212529; color:#fff; font-size:.85rem; padding:5px 12px; text-align:center; }
        html.f2f #f2fBar { display:block; }
        #f2fBar button { background:transparent; border:1px solid rgba(255,255,255,.5); color:#fff; border-radius:999px; padding:1px 12px; margin-left:10px; font-size:.8rem; }
        @media print { #f2fBar { display:none !important; } }
    </style>
    <script>window.toolsStatHit=function(ev){try{const f=new FormData();f.append('k',<?= json_encode($key ?? 'accueil') ?>);f.append('e',ev);navigator.sendBeacon('stat.php',f);}catch(e){}};</script>
    <?= $extraHead ?>
</head>
<body>
<div id="f2fBar" class="no-print"><i class="fas fa-handshake me-1"></i>Mode face-à-face client activé : éléments internes masqués<button type="button" id="f2fExit">Quitter</button></div>
<div class="tools-header no-print">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="h3 mb-0"><?= e($title) ?></h1>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php if (empty($GLOBALS['toolsNoGate'])): ?>
            <form class="no-client" action="<?= $key === null || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/modules/') === false ? '' : '../../tools/' ?>search.php" method="get" class="d-flex" role="search">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Rechercher dans les outils…" minlength="2" required value="<?= e($_GET['q'] ?? '') ?>" style="min-width:200px" aria-label="Recherche">
                <button class="btn btn-sm btn-light ms-1" aria-label="Rechercher"><i class="fas fa-search"></i></button>
            </form>
            <?php endif; ?>
            <?php if ($key !== null): ?>
            <a href="./" class="btn btn-sm btn-light"><i class="fas fa-th-large me-1"></i>Tous les outils</a>
            <?php endif; ?>
            <?php if ($key !== null && function_exists('toolTours') && isset(toolTours()[$key])): ?>
            <button type="button" class="btn btn-sm btn-light no-client" data-tour="<?= e($key) ?>"><i class="fas fa-circle-question me-1"></i>Comment ça marche ?</button>
            <?php endif; ?>
            <?php if (empty($GLOBALS['toolsNoGate'])): ?>
            <a href="<?= strpos($_SERVER['SCRIPT_NAME'] ?? '', '/modules/') === false ? '' : '../../tools/' ?>nouveautes.php" class="btn btn-sm btn-light tools-hbtn no-client" id="newsBtn" data-latest="<?= e(function_exists('newsLatestDate') ? newsLatestDate() : '') ?>"><i class="fas fa-bullhorn me-1"></i>Quoi de neuf<span class="nb">nouveau</span></a>
            <button type="button" class="btn btn-sm btn-light" id="f2fBtn" title="Agrandit l'affichage et masque les éléments internes pour montrer l'écran au client"><i class="fas fa-handshake me-1"></i>Mode client</button>
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
    if ($key) { ?>
<script>try{const k=<?= json_encode($key) ?>;let r=JSON.parse(localStorage.getItem('toolsRecent')||'[]').filter(x=>x!==k);r.unshift(k);localStorage.setItem('toolsRecent',JSON.stringify(r.slice(0,8)));}catch(e){}</script>
<?php }
    if ($key) { ?>
<div class="container text-center small my-3 no-print no-client" style="color:#8a5a00"><i class="fas fa-triangle-exclamation me-1"></i>Outil d'aide : il ne se substitue pas aux outils internes du groupe BPCE. Vérifiez les résultats avant toute communication à un client.</div>
<?php }
    if ($key) renderToolTour($key);
    ?>
<?php // Impression : pas d'en-tête (date, titre de la page) ni de pied de page (adresse) ajoutés par le navigateur ?>
<style>@page{margin:0}@media print{.tp-frame{width:100%;border-collapse:collapse}.tp-frame>thead>tr>td,.tp-frame>tfoot>tr>td{height:14mm;padding:0!important;border:0!important}.tp-frame>tbody>tr>td{padding:0 15mm!important;border:0!important;text-align:left!important;vertical-align:top}}</style>
<script>
(function(){
  // La marge de page est à 0 (c'est ce qui supprime l'en-tête et le pied du navigateur) : les marges sont recréées par un tableau
  // dont l'en-tête et le pied vides se répètent sur chaque page imprimée.
  const ROOTS=['#tdoc','#bgPrint','#pcPrint','#letter','.dom-print-zone'], done=[];
  window.addEventListener('beforeprint',()=>{
    ROOTS.forEach(sel=>{
      const el=document.querySelector(sel); if(!el||el.dataset.tpFramed) return;
      const t=document.createElement('table'); t.className='tp-frame';
      t.innerHTML='<thead><tr><td></td></tr></thead><tfoot><tr><td></td></tr></tfoot><tbody><tr><td></td></tr></tbody>';
      const cell=t.querySelector('tbody td'); while(el.firstChild) cell.appendChild(el.firstChild);
      el.appendChild(t); el.dataset.tpFramed='1'; done.push(el);
    });
  });
  window.addEventListener('afterprint',()=>{
    while(done.length){const el=done.pop(); delete el.dataset.tpFramed; const t=el.querySelector(':scope > .tp-frame'); if(!t) continue;
      const cell=t.querySelector('tbody td'); while(cell.firstChild) el.insertBefore(cell.firstChild,t); t.remove();}
  });
})();
</script>
<?php
    ?>
<script>
(function(){
  const root=document.documentElement, K='toolsF2f';
  const get=()=>{try{return sessionStorage.getItem(K)==='1';}catch(e){return false;}};
  const set=v=>{root.classList.toggle('f2f',v);try{sessionStorage.setItem(K,v?'1':'0');}catch(e){}};
  set(get());
  const b=document.getElementById('f2fBtn'); if(b) b.onclick=()=>{const on=!root.classList.contains('f2f');set(on);if(on&&window.toolsStatHit) window.toolsStatHit('f2f');};
  const x=document.getElementById('f2fExit'); if(x) x.onclick=()=>set(false);
  const n=document.getElementById('newsBtn');
  if(n){try{const l=n.dataset.latest, s=localStorage.getItem('toolsNewsSeen')||''; if(l&&l>s) n.querySelector('.nb').style.display='block';}catch(e){}}
})();
</script>
<?php
    if (!empty($GLOBALS['toolsNoFeedbackLink'])) { echo "</body>\n</html>\n"; return; } ?>
<div class="container text-center text-muted small my-4 no-print no-client">
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
