<?php
require_once __DIR__ . '/tour.php';
/**
 * Diaporama d'accueil de /tools : plein écran, défilement automatique, charte Caisse d'Épargne (rouge, blanc, gris).
 * Affiché une seule fois par session de navigation, après validation du code d'accès (la page /tools n'est servie qu'après).
 * Activable / désactivable par l'administrateur (réglage tools_intro_enabled, activé par défaut).
 */
function toolsIntroEnabled() {
    return getToolsSetting('tools_intro_enabled', '1') !== '0';
}

/**
 * @param array $tools  outils visibles : [['label' => ..., 'icon' => ...], ...]
 * @param bool  $withCta  proposer la demande d'accès au portail (si l'appel à action est actif)
 */
function renderToolsIntro(array $tools, $withCta = false) {
    if (!toolsIntroEnabled()) return;
    $logo = 'https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png';
    $grid = array_slice($tools, 0, 8);
    ?>
<div id="ciIntro" class="ci-intro" role="dialog" aria-modal="true" aria-label="Présentation des outils" hidden>
    <div class="ci-bg" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="ci-bar" aria-hidden="true"><i></i></div>
    <button type="button" class="ci-skip"><span>Passer</span> <i class="fas fa-xmark"></i></button>

    <div class="ci-stage">
        <section class="ci-slide ci-hero">
            <div class="ci-logo r1"><img src="<?= e($logo) ?>" alt="Caisse d'Épargne Midi-Pyrénées" onerror="this.parentNode.style.display='none'"></div>
            <h2 class="r2">Bienvenue dans la boîte à outils</h2>
            <p class="r3">Des outils pratiques, accessibles à tout moment, pour préparer et mener vos échanges avec vos clients.</p>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-toolbox"></i> Un outil pour chaque projet</h2>
            <div class="ci-grid">
                <?php foreach ($grid as $i => $t): ?>
                <div class="ci-tool" style="--i:<?= (int)$i ?>"><i class="fas <?= e($t['icon']) ?>"></i><span><?= e($t['label']) ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php if (count($tools) > count($grid)): ?><p class="r4 ci-more">… et <?= count($tools) - count($grid) ?> autre<?= count($tools) - count($grid) > 1 ? 's' : '' ?> à découvrir.</p><?php endif; ?>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-user-shield"></i> Aucune donnée enregistrée</h2>
            <p class="r2 ci-big">Tout se calcule dans votre navigateur.</p>
            <p class="r3">Rien de ce que vous saisissez n'est stocké par ce portail : tout disparaît à la fermeture de la page.</p>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-bolt"></i> Gagnez du temps</h2>
            <ul class="ci-list">
                <li class="r2"><i class="fas fa-link"></i><div><b>Partagez par lien</b><span>Un lien reprend la simulation, sans rien enregistrer.</span></div></li>
                <li class="r3"><i class="fas fa-print"></i><div><b>Imprimez, copiez, envoyez</b><span>Un document propre à remettre à votre client.</span></div></li>
                <li class="r4"><i class="fas fa-star"></i><div><b>Retrouvez vos outils</b><span>Favoris et outils récemment utilisés, sur cet appareil.</span></div></li>
            </ul>
        </section>

        <section class="ci-slide">
            <h2 class="r1"><i class="fas fa-circle-info"></i> Des aides, pas des substituts</h2>
            <p class="r2 ci-big">Ces outils complètent, sans les remplacer, les outils internes du groupe BPCE.</p>
            <p class="r3">Vérifiez toujours l'exactitude des données avant toute communication à un client.</p>
        </section>

        <section class="ci-slide ci-last">
            <?php if ($withCta): ?>
            <h2 class="r1"><i class="fas fa-key"></i> Besoin d'aller plus loin ?</h2>
            <p class="r2 ci-big">Demandez l'accès complet au portail d'activité.</p>
            <div class="r3 ci-actions">
                <button type="button" class="ci-btn ci-btn-light ci-access"><i class="fas fa-user-plus"></i> Demander l'accès</button>
                <button type="button" class="ci-btn ci-btn-line ci-go">Découvrir les outils <i class="fas fa-arrow-right"></i></button>
            </div>
            <?php else: ?>
            <h2 class="r1"><i class="fas fa-rocket"></i> C'est parti !</h2>
            <p class="r2 ci-big">Choisissez un outil pour commencer.</p>
            <div class="r3 ci-actions"><button type="button" class="ci-btn ci-btn-light ci-go">Découvrir les outils <i class="fas fa-arrow-right"></i></button></div>
            <?php endif; ?>
        </section>
    </div>

    <button type="button" class="ci-nav ci-prev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
    <button type="button" class="ci-nav ci-next" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
    <div class="ci-dots" role="tablist"></div>
</div>

<?php slideshowAssets(); ?>
<script>ciSlideshow(document.getElementById('ciIntro'),{seenKey:'toolsIntroSeen',autoOpen:true,duration:7000,replaySelector:'[data-intro-replay]',exportAs:'toolsIntroOpen'});</script>
<?php
}
