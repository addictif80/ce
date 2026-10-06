<?php
require_once __DIR__ . '/_layout.php';

$catalog = getPublicToolsCatalog();
$status = getPublicToolsStatus();
$visible = array_keys(array_filter($status, fn($st) => $st['state'] !== 'masque'));

$message = getToolsMessageForVisitor();
$procCounts = (isset($status['procedures']) && $status['procedures']['state'] !== 'masque') ? getPublicProcedureCategoryCounts() : [];
toolsHeader('Outils en libre accès');
?>
<div class="container my-4">
    <div class="privacy-banner d-flex align-items-center gap-3 mb-4" style="padding:22px 26px;">
        <i class="fas fa-user-shield" style="font-size:2.4rem"></i>
        <div>
            <div class="h4 mb-1">Aucune donnée n'est enregistrée</div>
            <div>Ces outils sont accessibles sans connexion ni création de compte. Rien de ce que vous saisissez n'est stocké par ce portail :
                tout reste dans votre navigateur et disparaît à la fermeture de la page.
                <span class="small d-block mt-1">Seules exceptions, volontaires : envoyer un retour à l'administrateur, ou proposer un ajout ou une modification (procédures, codes utiles, contacts utiles), qui est transmis pour validation.</span></div>
        </div>
    </div>

    <?php if ($message !== ''): ?>
    <div class="alert alert-info border-2 mb-4 tools-message"><?= $message /* HTML assaini à l'enregistrement par l'admin */ ?></div>
    <style>.tools-message > :last-child{margin-bottom:0}.tools-message h2,.tools-message h3{font-size:1.15rem}</style>
    <?php endif; ?>

    <?php if (!$visible): ?>
        <div class="alert alert-info">Aucun outil n'est disponible pour le moment.</div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($visible as $key): $t = $catalog[$key]; $off = $status[$key]['state'] === 'indisponible'; ?>
        <div class="col-md-6 col-lg-4">
            <<?= $off ? 'div' : 'a href="' . e($t['url']) . '"' ?> class="text-decoration-none text-dark d-block h-100" <?= $off ? 'aria-disabled="true"' : '' ?>>
                <div class="card h-100 shadow-sm border-0" style="<?= $off ? 'opacity:.6;filter:grayscale(1);cursor:not-allowed' : '' ?>">
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-start">
                            <span style="color:#e4002b;font-size:2rem"><i class="fas <?= e($t['icon']) ?>"></i></span>
                            <?php if ($off): ?><span class="badge bg-secondary">Indisponible</span><?php endif; ?>
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
<?php toolsFooter();
