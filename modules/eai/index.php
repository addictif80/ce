<?php
$pageTitle = 'EAI';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Productions par catégorie (pour l'utilisateur courant)
$stmt = $db->prepare("SELECT categorie, COUNT(*) as nb FROM suivi_production WHERE user_id = ? GROUP BY categorie ORDER BY nb DESC");
$stmt->execute([$userId]);
$productionsParCategorie = $stmt->fetchAll();

$totalProductions = 0;
foreach ($productionsParCategorie as $row) {
    $totalProductions += $row['nb'];
}

// Productions du mois en cours
$stmt = $db->prepare("SELECT categorie, COUNT(*) as nb FROM suivi_production WHERE user_id = ? AND MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE()) GROUP BY categorie ORDER BY nb DESC");
$stmt->execute([$userId]);
$productionsMois = $stmt->fetchAll();

$totalMois = 0;
foreach ($productionsMois as $row) {
    $totalMois += $row['nb'];
}

// RDV de la semaine en cours (depuis seances_phoning)
$stmt = $db->prepare("SELECT
    COALESCE(SUM(dont_s), 0) as total_s,
    COALESCE(SUM(dont_s1), 0) as total_s1,
    COALESCE(SUM(dont_anv), 0) as total_anv
    FROM seances_phoning
    WHERE user_id = ? AND YEARWEEK(date_ajout, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$userId]);
$rdvSemaine = $stmt->fetch();

$totalRdvSemaine = $rdvSemaine['total_s'] + $rdvSemaine['total_s1'] + $rdvSemaine['total_anv'];

// Phoning semaine en cours (appels et rdv)
$stmt = $db->prepare("SELECT
    COALESCE(SUM(nombre_appels), 0) as appels,
    COALESCE(SUM(nombre_rdv), 0) as rdv
    FROM seances_phoning
    WHERE user_id = ? AND YEARWEEK(date_ajout, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$userId]);
$phoningSemaine = $stmt->fetch();

// Couleurs pour les catégories
$catColors = [
    'Banca' => 'primary',
    'Epargne' => 'success',
    'Placement' => 'info',
    'Credit' => 'warning',
    'Assurance' => 'danger',
];
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $totalProductions ?></div>
            <div class="stat-label">Productions totales</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $totalMois ?></div>
            <div class="stat-label">Productions ce mois</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $totalRdvSemaine ?></div>
            <div class="stat-label">RDV cette semaine</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$phoningSemaine['appels'] ?></div>
            <div class="stat-label">Appels cette semaine</div>
        </div>
    </div>
</div>

<!-- Détail RDV semaine -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$rdvSemaine['total_s'] ?></div>
            <div class="stat-label">Dont S (semaine)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$rdvSemaine['total_s1'] ?></div>
            <div class="stat-label">Dont S+1 (semaine)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$rdvSemaine['total_anv'] ?></div>
            <div class="stat-label">Dont ANV (semaine)</div>
        </div>
    </div>
</div>

<!-- Tableau récapitulatif : Productions par catégorie -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-chart-pie"></i> Productions par catégorie</h3>
    </div>
    <table class="data-table" id="tableEaiCategories">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Nombre de productions</th>
                <th>Part</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($productionsParCategorie)): ?>
            <tr><td colspan="3" class="text-center text-muted">Aucune production enregistrée</td></tr>
        <?php else: ?>
            <?php foreach ($productionsParCategorie as $row): ?>
                <tr>
                    <td>
                        <span class="badge bg-<?= $catColors[$row['categorie']] ?? 'secondary' ?>">
                            <?= e($row['categorie']) ?>
                        </span>
                    </td>
                    <td><strong><?= (int)$row['nb'] ?></strong></td>
                    <td>
                        <div class="progress" style="height: 20px; min-width: 100px;">
                            <div class="progress-bar bg-<?= $catColors[$row['categorie']] ?? 'secondary' ?>"
                                 role="progressbar"
                                 style="width: <?= $totalProductions > 0 ? round($row['nb'] / $totalProductions * 100) : 0 ?>%">
                                <?= $totalProductions > 0 ? round($row['nb'] / $totalProductions * 100) : 0 ?>%
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-active">
                <td><strong>Total</strong></td>
                <td><strong><?= $totalProductions ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Tableau récapitulatif : Productions du mois par catégorie -->
<div class="data-table-container mt-4">
    <div class="data-table-header">
        <h3><i class="fas fa-calendar-alt"></i> Productions du mois en cours par catégorie</h3>
    </div>
    <table class="data-table" id="tableEaiMois">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Nombre de productions</th>
                <th>Part</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($productionsMois)): ?>
            <tr><td colspan="3" class="text-center text-muted">Aucune production ce mois-ci</td></tr>
        <?php else: ?>
            <?php foreach ($productionsMois as $row): ?>
                <tr>
                    <td>
                        <span class="badge bg-<?= $catColors[$row['categorie']] ?? 'secondary' ?>">
                            <?= e($row['categorie']) ?>
                        </span>
                    </td>
                    <td><strong><?= (int)$row['nb'] ?></strong></td>
                    <td>
                        <div class="progress" style="height: 20px; min-width: 100px;">
                            <div class="progress-bar bg-<?= $catColors[$row['categorie']] ?? 'secondary' ?>"
                                 role="progressbar"
                                 style="width: <?= $totalMois > 0 ? round($row['nb'] / $totalMois * 100) : 0 ?>%">
                                <?= $totalMois > 0 ? round($row['nb'] / $totalMois * 100) : 0 ?>%
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-active">
                <td><strong>Total</strong></td>
                <td><strong><?= $totalMois ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Tableau récapitulatif : Phoning semaine -->
<div class="data-table-container mt-4">
    <div class="data-table-header">
        <h3><i class="fas fa-phone-volume"></i> Récapitulatif phoning - Semaine en cours</h3>
    </div>
    <table class="data-table" id="tableEaiPhoning">
        <thead>
            <tr>
                <th>Indicateur</th>
                <th>Réalisé</th>
                <th>Objectif</th>
                <th>Reste</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><i class="fas fa-phone"></i> Appels</td>
                <td><strong><?= (int)$phoningSemaine['appels'] ?></strong></td>
                <td>60</td>
                <td>
                    <?php $resteAppels = max(0, 60 - (int)$phoningSemaine['appels']); ?>
                    <span class="badge bg-<?= $resteAppels === 0 ? 'success' : 'warning' ?>"><?= $resteAppels ?></span>
                </td>
            </tr>
            <tr>
                <td><i class="fas fa-calendar-check"></i> RDV obtenus</td>
                <td><strong><?= (int)$phoningSemaine['rdv'] ?></strong></td>
                <td>12</td>
                <td>
                    <?php $resteRdv = max(0, 12 - (int)$phoningSemaine['rdv']); ?>
                    <span class="badge bg-<?= $resteRdv === 0 ? 'success' : 'warning' ?>"><?= $resteRdv ?></span>
                </td>
            </tr>
            <tr>
                <td><i class="fas fa-bullseye"></i> RDV détail (S + S1 + ANV)</td>
                <td><strong><?= $totalRdvSemaine ?></strong></td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
