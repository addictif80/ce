<?php
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/nouveautes.php';
$news = newsList();
$types = newsTypes();
$catalog = getPublicToolsCatalog();
$bar = newsBaremes();
$stLabel = ['ok' => ['success', 'Contrôlé'], 'provisoire' => ['warning text-dark', 'À contrôler'], 'ancien' => ['danger', 'À revoir']];
toolsHeader('Quoi de neuf');
$latest = newsLatestDate();
?>
<div class="container my-4" style="max-width:900px">
    <p class="text-muted">Les nouveaux outils, les évolutions et les barèmes mis à jour.</p>
    <?php if (!$news): ?><div class="alert alert-light border">Aucune nouveauté pour le moment.</div><?php endif; ?>
    <div class="vstack gap-3">
    <?php foreach ($news as $n): [$tl, $tc, $ti] = $types[$n['type']] ?? $types['evolution'];
        $link = ($n['tool_key'] && isset($catalog[$n['tool_key']]['url'])) ? $catalog[$n['tool_key']]['url'] : ''; ?>
        <div class="card border-0 shadow-sm"><div class="card-body d-flex gap-3">
            <div class="flex-shrink-0 text-center" style="width:64px"><div class="rounded-circle bg-<?= e($tc) ?> text-white d-flex align-items-center justify-content-center mx-auto" style="width:46px;height:46px"><i class="fas <?= e($ti) ?>"></i></div>
                <div class="small text-muted mt-1"><?= e(date('d/m/Y', strtotime($n['date_news']))) ?></div></div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between flex-wrap gap-2"><h2 class="h6 mb-1 fw-bold"><?= e($n['titre']) ?></h2><span class="badge text-bg-<?= e($tc) ?> align-self-start"><?= e($tl) ?></span></div>
                <?php if ($n['texte'] !== ''): ?><div class="mb-2"><?= nl2br(e($n['texte'])) ?></div><?php endif; ?>
                <?php if ($link): ?><a class="btn btn-sm btn-outline-danger" href="<?= e($link) ?>"><i class="fas fa-arrow-right me-1"></i>Ouvrir l'outil</a><?php endif; ?>
            </div></div></div>
    <?php endforeach; ?>
    </div>

    <h2 class="h5 mt-5 mb-3"><i class="fas fa-scale-balanced me-2"></i>Barèmes utilisés par les simulateurs</h2>
    <div class="table-responsive"><table class="table table-sm align-middle bg-white">
        <thead><tr><th>Barème</th><th>Dernier contrôle</th><th>État</th></tr></thead><tbody>
        <?php foreach ($bar as $b): [$bc, $bt] = $stLabel[$b['statut']]; ?>
        <tr><td><i class="fas <?= e($b['icon']) ?> me-2 text-muted"></i><?= e($b['label']) ?></td><td><?= $b['date'] ? e(date('d/m/Y', strtotime($b['date']))) : '—' ?></td><td><span class="badge bg-<?= $bc ?>"><?= $bt ?></span></td></tr>
        <?php endforeach; ?></tbody></table></div>
</div>
<script>try{localStorage.setItem('toolsNewsSeen',<?= json_encode($latest) ?>);}catch(e){}</script>
<?php toolsFooter();
