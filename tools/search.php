<?php
require_once __DIR__ . '/_layout.php';
$q = trim($_GET['q'] ?? '');
$results = $q === '' ? [] : searchToolsGlobal($q);
toolsHeader('Recherche');
?>
<div class="container my-3" style="max-width:860px">
    <h2 class="h5 mb-3">Résultats pour « <?= e($q) ?> »</h2>
    <div class="text-muted small mb-3">La recherche porte uniquement sur les outils disponibles ici.</div>
    <?php if (mb_strlen($q) < 2): ?>
        <div class="alert alert-info">Saisissez au moins 2 caractères.</div>
    <?php elseif (!$results): ?>
        <div class="alert alert-warning">Aucun résultat.</div>
    <?php else: ?>
        <div class="list-group">
        <?php foreach ($results as $r): ?>
            <a href="<?= e($r['url']) ?>" class="list-group-item list-group-item-action">
                <span class="badge bg-secondary me-2"><?= e($r['type']) ?></span><strong><?= e($r['titre']) ?></strong>
                <?php if (!empty($r['detail'])): ?><div class="small text-muted mt-1"><?= e($r['detail']) ?></div><?php endif; ?>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php toolsFooter();
