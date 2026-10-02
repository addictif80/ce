<?php
require_once __DIR__ . '/_layout.php';

$catalog = getPublicToolsCatalog();
$enabled = getEnabledPublicTools();

toolsHeader('Outils en libre accès');
?>
<div class="container my-4">
    <div class="privacy-banner d-flex align-items-center gap-3 mb-4" style="padding:22px 26px;">
        <i class="fas fa-user-shield" style="font-size:2.4rem"></i>
        <div>
            <div class="h4 mb-1">Aucune donnée n'est enregistrée</div>
            <div>Ces outils sont accessibles sans connexion ni création de compte. Rien de ce que vous saisissez n'est stocké par ce portail :
                tout reste dans votre navigateur et disparaît à la fermeture de la page.</div>
        </div>
    </div>

    <?php if (!$enabled): ?>
        <div class="alert alert-info">Aucun outil n'est disponible pour le moment.</div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($enabled as $key): $t = $catalog[$key]; ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= e($t['url']) ?>" class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="mb-3" style="color:#e4002b;font-size:2rem"><i class="fas <?= e($t['icon']) ?>"></i></div>
                        <h2 class="h5"><?= e($t['label']) ?></h2>
                        <p class="text-muted mb-2"><?= e($t['description']) ?></p>
                        <p class="small text-success mb-0"><i class="fas fa-check-circle me-1"></i><?= e($t['note']) ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="text-center text-muted mt-5 small">
        <a href="../login.php">Accès au portail (connexion)</a>
    </p>
</div>
<?php toolsFooter();
