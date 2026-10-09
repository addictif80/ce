<?php
// Onglet « Statistiques /tools » : compteurs anonymes de vues et d'actions par outil (voir includes/stats.php).
require_once __DIR__ . '/../../includes/stats.php';
$jours = in_array((int)($_GET['j'] ?? 30), [7, 30, 90], true) ? (int)$_GET['j'] : 30;
[$tot, $serie] = statsSummary($jours);
$catalog = getPublicToolsCatalog();
$events = statsEvents();
$days = []; for ($i = $jours - 1; $i >= 0; $i--) $days[] = date('Y-m-d', strtotime("-$i day"));
uksort($tot, fn($a, $b) => ($tot[$b]['view'] ?? 0) <=> ($tot[$a]['view'] ?? 0));
$totalViews = array_sum(array_map(fn($r) => $r['view'] ?? 0, $tot));
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> Compteurs anonymes</strong>
    Chaque ouverture d'un outil de /tools ajoute 1 à un total du jour ; quelques actions (présentation, impression, copie du lien, mode client) sont comptées de la même façon.
    Aucun identifiant, adresse IP ou cookie n'est enregistré : seulement des totaux par jour et par outil. Ils servent à savoir quels outils sont utiles.
</div>
<div class="mb-3 d-flex gap-2 align-items-center"><span class="text-muted">Période :</span>
    <?php foreach ([7, 30, 90] as $j): ?><a class="btn btn-sm <?= $j === $jours ? 'btn-ce' : 'btn-outline-secondary' ?>" href="?tab=stats&j=<?= $j ?>"><?= $j ?> jours</a><?php endforeach; ?>
    <span class="ms-auto"><strong><?= number_format($totalViews, 0, ',', ' ') ?></strong> vues au total</span></div>
<div class="data-table-container"><table class="data-table">
    <thead><tr><th>Outil</th><?php foreach ($events as $ek => $el): ?><th class="text-end" title="<?= e($el) ?>"><?= e($ek === 'view' ? 'Vues' : $el) ?></th><?php endforeach; ?><th style="width:200px">Vues par jour</th></tr></thead>
    <tbody>
    <?php foreach ($tot as $key => $row): $max = max(1, ...array_map(fn($d) => $serie[$key][$d] ?? 0, $days)); ?>
    <tr><td><?= e($catalog[$key]['label'] ?? ($key === 'accueil' ? 'Page d\'accueil de /tools' : $key)) ?></td>
        <?php foreach ($events as $ek => $el): ?><td class="text-end"><?= (int)($row[$ek] ?? 0) ?: '<span class="text-muted">–</span>' ?></td><?php endforeach; ?>
        <td><div class="d-flex align-items-end gap-px" style="height:28px;gap:1px" aria-label="Vues par jour"><?php foreach ($days as $d): $v = $serie[$key][$d] ?? 0; ?><div title="<?= e(date('d/m', strtotime($d)) . ' : ' . $v) ?>" style="flex:1;min-width:2px;background:#e4002b;opacity:<?= $v ? '1' : '.12' ?>;height:<?= $v ? max(8, round($v / $max * 100)) : 6 ?>%"></div><?php endforeach; ?></div></td></tr>
    <?php endforeach; if (!$tot): ?><tr><td colspan="<?= count($events) + 2 ?>" class="text-muted text-center py-3">Aucune donnée sur la période.</td></tr><?php endif; ?>
    </tbody></table></div>
