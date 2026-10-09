<?php
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/agences.php';
require_once __DIR__ . '/../includes/popups.php';
require_once __DIR__ . '/../includes/terms.php';
require_once __DIR__ . '/../includes/intro_tools.php';

$catalog = getPublicToolsCatalog();
$status = getPublicToolsStatus();
$visible = array_keys(array_filter($status, fn($st) => $st['state'] !== 'masque'));

$message = getToolsMessageForVisitor();
$procCounts = (isset($status['procedures']) && $status['procedures']['state'] !== 'masque') ? getPublicProcedureCategoryCounts() : [];
// « Nouveau » : les 6 outils les plus récemment ajoutés (60 jours maximum) ; la liste complète est dans « Quoi de neuf »
$recents = array_values(array_filter($visible, fn($k) => !empty($catalog[$k]['added']) && strtotime($catalog[$k]['added']) >= strtotime('-60 days')));
usort($recents, fn($a, $b) => strcmp($catalog[$b]['added'], $catalog[$a]['added']));
$newKeys = array_slice($recents, 0, 6);
$isNew = fn($k) => in_array($k, $newKeys, true);
$cta = (!toolsVisitorIsLoggedIn()) ? getAccessCtaSettings() : null;
$showCta = $cta && $cta['enabled'] && $cta['email'] !== '';
$agences = $showCta ? getAgences() : [];
toolsHeader('Outils en libre accès');
?>
<style>.tool-desc{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em}.tool-card{transition:transform .12s,box-shadow .12s}a:hover > .tool-card{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.12)!important}html.f2f .tool-desc{-webkit-line-clamp:3}</style>
<div class="container my-4">
    <?php if ($visible): ?><div class="row g-3 mb-3"><div class="col-md-6">
    <?php endif; ?>
    <div class="privacy-banner h-100" style="padding:12px 18px;">
        <div class="d-flex align-items-center gap-3">
            <i class="fas fa-user-shield" style="font-size:1.8rem"></i>
            <div>
                <div class="fw-bold">Aucune donnée n'est enregistrée</div>
                <div class="small">Ces outils sont accessibles sans connexion ni compte : ce que vous saisissez reste dans votre navigateur et disparaît à la fermeture de la page.
                    <details class="d-inline"><summary class="d-inline" style="cursor:pointer;text-decoration:underline">Précisions</summary>
                    <span class="d-block mt-1">Seules exceptions, volontaires : envoyer un retour à l\'administrateur, ou proposer un ajout ou une modification (procédures, codes utiles, contacts utiles), qui est transmis pour validation. Le portail tient en outre des compteurs anonymes de fréquentation (nombre d\'ouvertures par outil), sans identifiant ni cookie.</span></details></div>
            </div>
        </div>
        <?php if ($message !== ''): ?>
        <hr style="border-color:#2e7d32;opacity:.5;margin:16px 0">
        <div class="tools-message"><?= $message /* HTML assaini à l'enregistrement par l'admin */ ?></div>
        <style>.tools-message{background:#fff;color:#212529;border:1px solid #c8e6c9;border-radius:8px;padding:14px 18px}.tools-message a{color:#0d6efd}.tools-message > :last-child{margin-bottom:0}.tools-message h2,.tools-message h3{font-size:1.15rem}</style>
        <?php endif; ?>
    </div>
    <?php if ($visible): ?></div>
    <?php renderTermsToolsCard('half'); // avertissement : toujours la première carte (2e colonne de la première ligne) ?>
    </div><?php endif; ?>


    <?php if ($showCta): ?>
    <div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,#e4002b 0%,#b30022 100%);color:#fff">
        <div class="card-body d-md-flex align-items-center justify-content-between gap-3 py-3 px-4">
            <div>
                <h2 class="h5 mb-1 fw-bold"><i class="fas fa-user-plus me-2"></i><?= e($cta['title']) ?></h2>
                <div class="mb-0 small" style="opacity:.95"><?= nl2br(e($cta['text'])) ?></div>
            </div>
            <button type="button" class="btn btn-light flex-shrink-0 mt-2 mt-md-0 fw-bold px-3" style="color:#b30022" data-bs-toggle="modal" data-bs-target="#accessModal"><i class="fas fa-key me-2"></i>Demander un accès</button>
        </div>
    </div>

    <div class="modal fade" id="accessModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <form id="accessForm" method="post" action="demande_acces.php" novalidate>
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Demande d'accès au portail d'activité</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nom <span class="text-danger">*</span></label><input id="ar_nom" name="nom" class="form-control" required maxlength="100" autocomplete="family-name"></div>
                    <div class="col-md-6"><label class="form-label">Prénom <span class="text-danger">*</span></label><input id="ar_prenom" name="prenom" class="form-control" required maxlength="100" autocomplete="given-name"></div>
                    <div class="col-md-6"><label class="form-label">Numéro de téléphone <span class="text-danger">*</span></label><input id="ar_tel" name="telephone" type="tel" class="form-control" required maxlength="30" autocomplete="tel"></div>
                    <div class="col-md-6"><label class="form-label">Numéro interne <span class="text-danger">*</span></label><input id="ar_interne" name="numero_interne" class="form-control" required maxlength="30"></div>
                    <div class="col-md-6"><label class="form-label">Adresse e-mail <span class="text-danger">*</span></label><input id="ar_email" name="email" type="email" class="form-control" required maxlength="150" autocomplete="email"></div>
                    <div class="col-md-6"><label class="form-label">Agence de rattachement <span class="text-danger">*</span></label>
                        <select id="ar_agence" name="agence" class="form-select" required><option value="">Sélectionner une agence…</option>
                            <?php foreach ($agences as $ag): ?><option><?= e($ag) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><div class="alert alert-info small mb-0"><i class="fas fa-info-circle me-1"></i>Le bouton ci-dessous crée un fichier de message (.eml) adressé à l'administrateur : ouvrez-le avec votre messagerie et envoyez-le. Les informations saisies servent uniquement à fabriquer ce fichier : elles ne sont pas enregistrées.</div></div>
                    <div class="col-12 text-danger small d-none" id="ar_err"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-danger"><i class="fas fa-download me-1"></i>Télécharger la demande (.eml)</button></div>
        </form>
    </div></div></div>
    <script>
    (function() {
        const v = id => document.getElementById(id).value.trim();
        const err = document.getElementById('ar_err');
        // Validation côté navigateur ; si tout est correct, le formulaire part normalement et le serveur renvoie le fichier .eml
        document.getElementById('accessForm').addEventListener('submit', e => {
            const fail = m => { e.preventDefault(); err.textContent = m; err.classList.remove('d-none'); };
            if (['ar_nom', 'ar_prenom', 'ar_tel', 'ar_interne', 'ar_email', 'ar_agence'].some(id => !v(id))) return fail('Tous les champs sont obligatoires.');
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v('ar_email'))) return fail('Adresse e-mail invalide.');
            err.classList.add('d-none');
        });
    })();
    </script>
    <?php endif; ?>


    <div id="recentBar" class="mb-3 small" style="display:none"><i class="fas fa-clock-rotate-left text-secondary me-1"></i><strong>Utilisés récemment :</strong> <span id="recentList"></span></div>
    <div id="favSection" class="mb-4" style="display:none"><h2 class="h5 mb-3"><i class="fas fa-star text-warning me-1"></i>Mes favoris</h2><div class="row g-3" id="favRow"></div><hr class="mt-4"></div>
    <?php if (!$visible): ?>
        <div class="row g-3 mb-3"><?php renderTermsToolsCard('full'); ?></div>
        
        <div class="alert alert-info">Aucun outil n'est disponible pour le moment.</div>
    <?php else: ?>
    <?php
    // Regroupement par intention ; un outil absent de la liste va dans « Autres outils »
    $groupes = [
        'Financer un projet' => ['calculateur', 'capacite', 'notaire', 'ptz', 'creditsspeciaux', 'plan', 'scenarios', 'relais', 'rachat', 'usure'],
        'Épargne et patrimoine' => ['epargne', 'assurancevie', 'per', 'epargnecredit'],
        'Conseil et conformité' => ['signataires', 'evenements', 'pieces', 'saisie', 'memo_plafonds', 'memo_delais'],
        'Boîte à outils' => ['dates', 'validateurs', 'calculs', 'pdf', 'courrier', 'bureau_dom'],
        'Références' => ['dpe', 'rge', 'procedures', 'codes', 'contacts'],
    ];
    $placed = array_merge(...array_values($groupes));
    $autres = array_values(array_diff($visible, $placed));
    if ($autres) $groupes['Autres outils'] = $autres;
    ?>
    <div class="mb-3 no-print d-flex flex-wrap gap-3 align-items-center justify-content-between"><div style="width:min(420px,100%)"><label class="visually-hidden" for="toolFilter">Filtrer les outils</label><input type="search" id="toolFilter" class="form-control" placeholder="Filtrer les outils de cette page…" autocomplete="off"></div><?php if (toolsIntroEnabled()): ?><a href="#" data-intro-replay class="small text-decoration-none"><i class="fas fa-play-circle me-1"></i>Revoir la présentation</a><?php endif; ?></div>
    <?php $gi = 0; $gActifs = []; foreach ($groupes as $gTitre => $gKeys) { $gKeys = array_values(array_filter($gKeys, fn($k) => in_array($k, $visible, true))); if ($gKeys) $gActifs[$gTitre] = $gKeys; } ?>
    <div class="d-flex flex-wrap gap-2 mb-3 no-print" role="tablist" aria-label="Catégories d'outils" id="toolTabs">
        <button type="button" class="btn btn-sm btn-danger" role="tab" data-g="all" aria-selected="true">Tous <span class="badge text-bg-light"><?= count($visible) ?></span></button>
        <?php foreach (array_keys($gActifs) as $n => $gTitre): ?><button type="button" class="btn btn-sm btn-outline-secondary" role="tab" data-g="<?= $n ?>" aria-selected="false"><?= e($gTitre) ?> <span class="badge text-bg-light"><?= count($gActifs[$gTitre]) ?></span></button><?php endforeach; ?>
    </div>
    <?php foreach ($gActifs as $gTitre => $gKeys): ?>
    <section class="tool-group" data-g="<?= $gi++ ?>"><h2 class="h6 text-uppercase text-muted mt-3 mb-2 pb-1 border-bottom" style="letter-spacing:.5px"><?= e($gTitre) ?></h2>
    <div class="row g-3">
        <?php foreach ($gKeys as $key): $t = $catalog[$key]; $off = $status[$key]['state'] === 'indisponible'; ?>
        <div class="col-sm-6 col-lg-4 col-xl-3 tool-col" data-key="<?= e($key) ?>">
            <<?= $off ? 'div' : 'a href="' . e($t['url']) . '"' ?> class="text-decoration-none text-dark d-block h-100" <?= $off ? 'aria-disabled="true"' : '' ?> title="<?= e($t['description']) ?>">
                <div class="card h-100 shadow-sm border-0 tool-card" style="<?= $off ? 'opacity:.6;filter:grayscale(1);cursor:not-allowed' : '' ?>">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <span style="color:#e4002b;font-size:1.35rem;width:1.7rem;text-align:center;flex:0 0 auto"><i class="fas <?= e($t['icon']) ?>"></i></span>
                            <h3 class="h6 mb-0 flex-grow-1 lh-sm"><?= e($t['label']) ?></h3>
                            <?php if ($off): ?><span class="badge bg-secondary">Indisponible</span><?php endif; ?>
                            <?php if (!$off && $isNew($key)): ?><span class="badge bg-danger">Nouveau</span><?php endif; ?>
                            <button type="button" class="btn btn-link p-0 fav-btn text-secondary" title="Ajouter aux favoris" aria-label="Ajouter aux favoris" aria-pressed="false"><i class="far fa-star"></i></button>
                        </div>
                        <p class="text-muted small mb-0 mt-1 tool-desc"><?= e($t['description']) ?></p>
                        <?php if ($key === 'procedures' && $procCounts): ?><div class="small text-muted mt-1"><?= array_sum(array_column($procCounts, 'nb')) ?> procédure(s)</div><?php endif; ?>
                        <?php if ($off): ?><p class="small mb-0 mt-1 fw-semibold"><i class="fas fa-ban me-1"></i><?= nl2br(e($status[$key]['motif'] !== '' ? $status[$key]['motif'] : 'Temporairement indisponible.')) ?></p><?php endif; ?>
                    </div>
                </div>
            </<?= $off ? 'div' : 'a' ?>>
        </div>
        <?php endforeach; ?>
    </div></section>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="feedback.php" class="btn btn-outline-secondary"><i class="fas fa-comment-dots me-1"></i>Envoyer un retour à l'administrateur</a>
    </div>
</div>
<script>
(function(){
  const get=k=>{try{return JSON.parse(localStorage.getItem(k)||'[]');}catch(e){return [];}};
  const set=(k,v)=>{try{localStorage.setItem(k,JSON.stringify(v));}catch(e){}};
  const cols=[...document.querySelectorAll('.tool-col')], main=cols.length?cols[0].parentNode:null;
  const order=cols.map(c=>c.dataset.key);
  // repère de chaque carte dans son groupe : retour à la place d'origine quand un favori est retiré
  const ph=cols.map(c=>{const p=document.createElement('span');p.hidden=true;c.parentNode.insertBefore(p,c);return p;});
  function render(){
    const fav=get('toolsFav').filter(k=>order.includes(k));
    cols.forEach(c=>{
      const on=fav.includes(c.dataset.key), b=c.querySelector('.fav-btn');
      if(b){b.querySelector('i').className=(on?'fas text-warning':'far')+' fa-star';b.setAttribute('aria-pressed',on?'true':'false');b.title=on?'Retirer des favoris':'Ajouter aux favoris';}
    });
    const favRow=document.getElementById('favRow');
    // favoris en tête (dans l'ordre choisi), les autres à leur place d'origine
    fav.forEach(k=>favRow.appendChild(cols[order.indexOf(k)]));
    cols.forEach((c,i)=>{if(!fav.includes(c.dataset.key)) ph[i].parentNode.insertBefore(c,ph[i].nextSibling);}); // retour à la place d'origine
    document.getElementById('favSection').style.display=fav.length?'':'none';
  }
  document.addEventListener('click',e=>{
    const b=e.target.closest('.fav-btn'); if(!b) return;
    e.preventDefault(); e.stopPropagation();
    const k=b.closest('.tool-col').dataset.key, f=get('toolsFav');
    set('toolsFav',f.includes(k)?f.filter(x=>x!==k):f.concat(k)); render();
  });
  if(main) render();
  const rec=get('toolsRecent').filter(k=>order.includes(k)).slice(0,5), list=document.getElementById('recentList');
  rec.forEach(k=>{
    const a=cols[order.indexOf(k)].querySelector('a'); if(!a&&!cols[order.indexOf(k)].closest('a')) return;
    const href=(a||cols[order.indexOf(k)].closest('a')).getAttribute('href'); if(!href) return;
    const el=document.createElement('a'); el.href=href; el.className='badge text-bg-light border text-decoration-none me-1';
    el.textContent=(cols[order.indexOf(k)].querySelector('h3')||cols[order.indexOf(k)]).textContent.trim(); list.appendChild(el);
  });
  if(list.children.length) document.getElementById('recentBar').style.display='';
  // onglets de catégories (dernier choix mémorisé dans ce navigateur)
  const tabs=[...document.querySelectorAll('#toolTabs [data-g]')], groups=[...document.querySelectorAll('.tool-group')];
  function showTab(g,save){
    tabs.forEach(t=>{const on=t.dataset.g===g;t.classList.toggle('btn-danger',on);t.classList.toggle('btn-outline-secondary',!on);t.setAttribute('aria-selected',on?'true':'false');});
    groups.forEach(x=>{x.style.display=(g==='all'||x.dataset.g===g)?'':'none';});
    if(save){try{localStorage.setItem('toolsTab',g);}catch(e){}}
  }
  tabs.forEach(t=>t.addEventListener('click',()=>{if(flt) flt.value=''; cols.forEach(c=>c.style.display=''); showTab(t.dataset.g,true);}));
  try{const g=localStorage.getItem('toolsTab'); if(g&&tabs.some(x=>x.dataset.g===g)) showTab(g,false);}catch(e){}
  // filtre instantané des cartes (les groupes vides se masquent)
  const flt=document.getElementById('toolFilter'), norm=s=>s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  if(flt) flt.addEventListener('input',()=>{
    const t=norm(flt.value.trim()); if(t) showTab('all',false); else {let g='all';try{g=localStorage.getItem('toolsTab')||'all';}catch(e){} showTab(tabs.some(x=>x.dataset.g===g)?g:'all',false);}
    cols.forEach(c=>{c.style.display=!t||norm(c.textContent).includes(t)?'':'none';});
    document.querySelectorAll('.tool-group').forEach(g=>{g.style.display=[...g.querySelectorAll('.tool-col')].some(c=>c.style.display!=='none')?'':'none';});
  });
})();
</script>
<?php
// Diaporama d'accueil (une fois par session de navigation, après validation du code d'accès)
$introTools = array_map(fn($k) => ['label' => $catalog[$k]['label'], 'icon' => $catalog[$k]['icon']], array_values(array_filter($visible, fn($k) => isset($catalog[$k]['label'], $catalog[$k]['icon']))));
renderToolsIntro($introTools, $showCta);
?>
<?php renderPopups('tools'); // popups de l'administrateur, après validation du code d'accès éventuel ?>
<?php toolsFooter();
