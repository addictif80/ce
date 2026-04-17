<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();

$mois = (int)($_GET['mois'] ?? date('m'));
$annee = (int)($_GET['annee'] ?? date('Y'));
$moisLabel = ['', 'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'][$mois] ?? '';

$cond = "WHERE user_id = ? AND MONTH(date_rdv) = ? AND YEAR(date_rdv) = ?";
$params = [$userId, $mois, $annee];

$stmt = $db->prepare("SELECT * FROM suivi_production $cond ORDER BY date_rdv ASC, produit_vendu ASC");
$stmt->execute($params);
$productions = $stmt->fetchAll();

// Regrouper par produit
$byProduit = [];
foreach ($productions as $p) {
    $k = $p['produit_vendu'] ?: 'Autre';
    if (!isset($byProduit[$k])) $byProduit[$k] = ['count' => 0, 'total' => 0, 'unite' => '', 'items' => []];
    $byProduit[$k]['count']++;
    $byProduit[$k]['total'] += (float)($p['montant_nombre'] ?? 0);
    $byProduit[$k]['items'][] = $p;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport production — <?= e($moisLabel . ' ' . $annee) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --ce-red: #e4002b; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .page-header { background: linear-gradient(135deg, var(--ce-red) 0%, #c40025 100%); color: #fff; padding: 24px 0; margin-bottom: 24px; }
        .container-page { max-width: 960px; margin: 0 auto; padding: 0 20px; }
        .card-content { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 24px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .stat-box { background: #fff8f8; border: 1px solid #ffe0e0; border-radius: 8px; padding: 14px; text-align: center; }
        .stat-box .num { font-size: 24px; font-weight: 700; color: var(--ce-red); }
        .stat-box .lbl { font-size: 12px; color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f8f8f8; padding: 9px 12px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555; }
        td { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .group-header { background: #fff0f0; font-weight: 600; color: var(--ce-red); padding: 8px 12px; border-left: 3px solid var(--ce-red); }
        .group-total { background: #fafafa; font-size: 12px; color: #888; padding: 4px 12px; text-align: right; border-bottom: 2px solid #eee; }
        .no-data { color: #bbb; text-align: center; padding: 40px; }
        .no-print { }
        @media print {
            body { background: #fff; }
            .page-header { background: #e4002b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .card-content { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-page" style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h1 style="margin:0;font-size:22px"><i class="fas fa-chart-line me-2"></i>Rapport production</h1>
                <div style="font-size:13px;opacity:.85;margin-top:4px">
                    <i class="fas fa-user me-1"></i><?= e($user['prenom'] . ' ' . $user['nom']) ?>
                    &nbsp;·&nbsp;
                    <i class="fas fa-calendar me-1"></i><?= e($moisLabel . ' ' . $annee) ?>
                </div>
            </div>
            <button onclick="window.print()" class="btn no-print" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.4)">
                <i class="fas fa-print me-1"></i> Imprimer
            </button>
        </div>
    </div>

    <div class="container-page">
        <!-- Filtre mois/année -->
        <form class="filter-bar no-print" method="GET">
            <select name="mois" class="form-select form-select-sm" style="width:auto">
                <?php $labels=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                for ($i=1;$i<=12;$i++): ?>
                    <option value="<?=$i?>" <?=$i==$mois?'selected':''?>><?=$labels[$i]?></option>
                <?php endfor; ?>
            </select>
            <select name="annee" class="form-select form-select-sm" style="width:auto">
                <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?=$y?>" <?=$y==$annee?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm" style="background:var(--ce-red);color:#fff"><i class="fas fa-filter"></i> Filtrer</button>
        </form>

        <!-- Stats globales -->
        <div class="stat-grid">
            <div class="stat-box">
                <div class="num"><?= count($productions) ?></div>
                <div class="lbl">Productions totales</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= count($byProduit) ?></div>
                <div class="lbl">Types de produits</div>
            </div>
            <?php
            $stmtSem = $db->prepare("SELECT COALESCE(SUM(nombre_appels),0) FROM seances_phoning WHERE user_id = ? AND MONTH(date_ajout)=? AND YEAR(date_ajout)=?");
            $stmtSem->execute([$userId,$mois,$annee]);
            $totalAppels = (int)$stmtSem->fetchColumn();
            $stmtRdv = $db->prepare("SELECT COUNT(*) FROM appels_phoning WHERE user_id = ? AND resultat='rdv' AND MONTH(date_rdv)=? AND YEAR(date_rdv)=?");
            $stmtRdv->execute([$userId,$mois,$annee]);
            $totalRdv = (int)$stmtRdv->fetchColumn();
            ?>
            <div class="stat-box">
                <div class="num"><?= $totalAppels ?></div>
                <div class="lbl">Appels phoning</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= $totalRdv ?></div>
                <div class="lbl">RDV phoning</div>
            </div>
        </div>

        <div class="card-content">
            <?php if (empty($productions)): ?>
                <div class="no-data"><i class="fas fa-inbox fa-2x mb-2 d-block"></i> Aucune production pour ce mois</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>Montant / Nombre</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($byProduit as $produit => $groupe): ?>
                    <tr><td colspan="4" class="group-header"><?= e($produit) ?> — <?= $groupe['count'] ?> vente<?= $groupe['count']>1?'s':'' ?></td></tr>
                    <?php foreach ($groupe['items'] as $p): ?>
                    <tr>
                        <td><?= $p['date_rdv'] ? date('d/m/Y', strtotime($p['date_rdv'])) : '-' ?></td>
                        <td><?= e($p['produit_vendu']) ?></td>
                        <td><?= $p['montant_nombre'] ? e($p['montant_nombre']) : '-' ?></td>
                        <td><?= e($p['details'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($groupe['total'] > 0): ?>
                    <tr><td colspan="4" class="group-total">Sous-total : <?= number_format($groupe['total'], 2, ',', ' ') ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="text-center mb-4 no-print" style="color:#bbb;font-size:12px">
            Généré le <?= date('d/m/Y à H:i') ?>
        </div>
    </div>
</body>
</html>
