<?php
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/agences.php';
require_once __DIR__ . '/../includes/popups.php';
require_once __DIR__ . '/../includes/terms.php';

$catalog = getPublicToolsCatalog();
$status = getPublicToolsStatus();
$visible = array_keys(array_filter($status, fn($st) => $st['state'] !== 'masque'));

$message = getToolsMessageForVisitor();
$procCounts = (isset($status['procedures']) && $status['procedures']['state'] !== 'masque') ? getPublicProcedureCategoryCounts() : [];
$isNew = fn($k) => !empty($catalog[$k]['added']) && strtotime($catalog[$k]['added']) >= strtotime('-60 days');
$newKeys = array_values(array_filter($visible, $isNew));
$cta = (!toolsVisitorIsLoggedIn()) ? getAccessCtaSettings() : null;
$showCta = $cta && $cta['enabled'] && $cta['email'] !== '';
$agences = $showCta ? getAgences() : [];
toolsHeader('Outils en libre accès');
?>
<div class="container my-4">
    <div class="privacy-banner mb-4" style="padding:22px 26px;">
        <div class="d-flex align-items-center gap-3">
            <i class="fas fa-user-shield" style="font-size:2.4rem"></i>
            <div>
                <div class="h4 mb-1">Aucune donnée n'est enregistrée</div>
                <div>Ces outils sont accessibles sans connexion ni création de compte. Rien de ce que vous saisissez n'est stocké par ce portail :
                    tout reste dans votre navigateur et disparaît à la fermeture de la page.
                    <span class="small d-block mt-1">Seules exceptions, volontaires : envoyer un retour à l'administrateur, ou proposer un ajout ou une modification (procédures, codes utiles, contacts utiles), qui est transmis pour validation.</span></div>
            </div>
        </div>
        <?php if ($message !== ''): ?>
        <hr style="border-color:#2e7d32;opacity:.5;margin:16px 0">
        <div class="tools-message"><?= $message /* HTML assaini à l'enregistrement par l'admin */ ?></div>
        <style>.tools-message{background:#fff;color:#212529;border:1px solid #c8e6c9;border-radius:8px;padding:14px 18px}.tools-message a{color:#0d6efd}.tools-message > :last-child{margin-bottom:0}.tools-message h2,.tools-message h3{font-size:1.15rem}</style>
        <?php endif; ?>
    </div>

    <?php if ($showCta): ?>
    <div class="card border-0 shadow mb-4" style="background:linear-gradient(135deg,#e4002b 0%,#b30022 100%);color:#fff">
        <div class="card-body d-md-flex align-items-center justify-content-between gap-4 p-4 p-md-5">
            <div>
                <h2 class="h3 mb-2 fw-bold"><i class="fas fa-user-plus me-2"></i><?= e($cta['title']) ?></h2>
                <div class="mb-0 fs-6" style="opacity:.95"><?= nl2br(e($cta['text'])) ?></div>
            </div>
            <button type="button" class="btn btn-light btn-lg flex-shrink-0 mt-3 mt-md-0 fw-bold px-4" style="color:#b30022" data-bs-toggle="modal" data-bs-target="#accessModal"><i class="fas fa-key me-2"></i>Demander un accès</button>
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


    <?php if ($newKeys): ?>
    <div class="mb-3 small"><i class="fas fa-sparkles text-danger me-1"></i><strong>Récemment ajoutés :</strong>
        <?php foreach ($newKeys as $k): ?><a href="<?= e($catalog[$k]['url']) ?>" class="badge text-bg-light border text-decoration-none me-1"><i class="fas <?= e($catalog[$k]['icon']) ?> me-1"></i><?= e($catalog[$k]['label']) ?></a><?php endforeach; ?></div>
    <?php endif; ?>
    <div id="recentBar" class="mb-3 small" style="display:none"><i class="fas fa-clock-rotate-left text-secondary me-1"></i><strong>Utilisés récemment :</strong> <span id="recentList"></span></div>
    <div id="favSection" class="mb-4" style="display:none"><h2 class="h5 mb-3"><i class="fas fa-star text-warning me-1"></i>Mes favoris</h2><div class="row g-4" id="favRow"></div><hr class="mt-4"></div>
    <?php if (!$visible): ?>
        <div class="row g-4 mb-3"><?php renderTermsToolsCard(); ?></div>
        <div class="alert alert-info">Aucun outil n'est disponible pour le moment.</div>
    <?php else: ?>
    <div class="row g-4">
        <?php renderTermsToolsCard(); // avertissement : toujours la première carte ?>
        <?php foreach ($visible as $key): $t = $catalog[$key]; $off = $status[$key]['state'] === 'indisponible'; ?>
        <div class="col-md-6 col-lg-4 tool-col" data-key="<?= e($key) ?>">
            <<?= $off ? 'div' : 'a href="' . e($t['url']) . '"' ?> class="text-decoration-none text-dark d-block h-100" <?= $off ? 'aria-disabled="true"' : '' ?>>
                <div class="card h-100 shadow-sm border-0" style="<?= $off ? 'opacity:.6;filter:grayscale(1);cursor:not-allowed' : '' ?>">
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-start">
                            <span style="color:#e4002b;font-size:2rem"><i class="fas <?= e($t['icon']) ?>"></i></span>
                            <span>
                            <?php if ($off): ?><span class="badge bg-secondary">Indisponible</span><?php endif; ?>
                            <?php if (!$off && $isNew($key)): ?><span class="badge bg-danger">Nouveau</span><?php endif; ?>
                            <button type="button" class="btn btn-link p-0 ms-1 fav-btn text-secondary" title="Ajouter aux favoris" aria-label="Ajouter aux favoris" aria-pressed="false"><i class="far fa-star"></i></button>
                            </span>
                        </div>
                        <h2 class="h5"><?= e($t['label']) ?></h2>
                        <p class="text-muted mb-2"><?= e($t['description']) ?></p>
                        <?php if ($key === 'procedures' && $procCounts): ?>
                            <div class="mb-2"><div class="small text-muted mb-1"><?= array_sum(array_column($procCounts, 'nb')) ?> procédure(s) :</div>
                            <?php foreach ($procCounts as $pc): ?><span class="badge bg-light text-dark border me-1 mb-1"><?= e($pc['nom']) ?> <strong>(<?= (int)$pc['nb'] ?>)</strong></span><?php endforeach; ?></div>
                        <?php endif; ?>
                        <?php if ($off): ?>
                            <p class="small mb-0 fw-semibold"><i class="fas fa-ban me-1"></i><?= nl2br(e($status[$key]['motif'] !== '' ? $status[$key]['motif'] : 'Temporairement indisponible.')) ?></p>
                        <?php else: ?>
                            <p class="small text-success mb-0"><i class="fas fa-check-circle me-1"></i><?= e($t['note']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </<?= $off ? 'div' : 'a' ?>>
        </div>
        <?php endforeach; ?>
    </div>
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
  function render(){
    const fav=get('toolsFav').filter(k=>order.includes(k));
    cols.forEach(c=>{
      const on=fav.includes(c.dataset.key), b=c.querySelector('.fav-btn');
      if(b){b.querySelector('i').className=(on?'fas text-warning':'far')+' fa-star';b.setAttribute('aria-pressed',on?'true':'false');b.title=on?'Retirer des favoris':'Ajouter aux favoris';}
    });
    const favRow=document.getElementById('favRow');
    // favoris en tête (dans l'ordre choisi), les autres à leur place d'origine
    fav.forEach(k=>favRow.appendChild(cols[order.indexOf(k)]));
    cols.filter(c=>!fav.includes(c.dataset.key)).forEach(c=>main.appendChild(c)); // ordre du catalogue conservé
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
    el.textContent=cols[order.indexOf(k)].querySelector('h2').textContent; list.appendChild(el);
  });
  if(list.children.length) document.getElementById('recentBar').style.display='';
})();
</script>
<?php renderPopups('tools'); // popups de l'administrateur, après validation du code d'accès éventuel ?>
<?php toolsFooter();
