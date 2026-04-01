<?php
$pageTitle = 'Accueil';
require_once __DIR__ . '/templates/header.php';
$stats = getDashboardStats(getCurrentUserId());
$db = getDB();
$userId = getCurrentUserId();

// Recherche globale
$searchResults = [];
if (!empty($_GET['q'])) {
    $searchResults = searchGlobal($_GET['q'], $userId);
}

// Procédures mises en avant
$stmt = $db->prepare("SELECT * FROM procedures WHERE mise_en_avant = 1 ORDER BY nom");
$stmtProc = $stmt;
$stmtProc->execute();
$proceduresEnAvant = $stmtProc->fetchAll();
?>

<?php if (!empty($_GET['q'])): ?>
    <h4 class="mb-3">Résultats de recherche pour "<?= e($_GET['q']) ?>"</h4>
    <?php if (empty($searchResults)): ?>
        <div class="alert alert-info">Aucun résultat trouvé.</div>
    <?php else: ?>
        <?php
        $typeLabels = [
            'instances' => 'Instance', 'rappels' => 'Rappel', 'offres' => 'Offre',
            'codes' => 'Code utile', 'contacts' => 'Contact', 'demandes_clients' => 'Demande client',
            'blocnotes' => 'Bloc-notes', 'procedures' => 'Procédure', 'courriers' => 'Courrier',
            'formations' => 'Formation', 'equipe' => 'Équipe', 'phoning' => 'Phoning'
        ];
        $typeLinks = [
            'instances' => 'modules/instances/index.php', 'rappels' => 'modules/rappels/index.php',
            'offres' => 'modules/offres/index.php', 'codes' => 'modules/codes/index.php',
            'contacts' => 'modules/contacts/index.php', 'demandes_clients' => 'modules/demandes_clients/index.php',
            'blocnotes' => 'modules/blocnotes/index.php', 'procedures' => 'modules/procedures/index.php',
            'courriers' => 'modules/courriers/index.php', 'formations' => 'modules/formations/index.php',
            'equipe' => 'modules/contacts/index.php', 'phoning' => 'modules/phoning/index.php'
        ];
        foreach ($searchResults as $r): ?>
            <div class="search-result-item">
                <div>
                    <span class="search-result-type"><?= $typeLabels[$r['type']] ?? $r['type'] ?></span>
                    <strong class="ms-2"><?= e($r['titre']) ?></strong>
                    <span class="text-muted ms-2"><?= e(excerpt($r['detail'], 100)) ?></span>
                </div>
                <a href="<?= $typeLinks[$r['type']] ?? '#' ?><?= !empty($r['id']) ? '?open=' . (int)$r['id'] : '' ?>" class="btn btn-sm btn-ce-outline">Voir</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['instances_a_faire'] ?></div>
            <div class="stat-label">Instances à traiter</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $stats['instances_retard'] ?></div>
            <div class="stat-label">Instances en retard</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-number"><?= $stats['rappels_en_cours'] ?></div>
            <div class="stat-label">Clients à rappeler</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-number"><?= $stats['demandes_non_traitees'] ?></div>
            <div class="stat-label">Demandes clients en cours</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['offres_en_cours'] ?></div>
            <div class="stat-label">Offres en cours</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $stats['offres_terminees'] ?></div>
            <div class="stat-label">Offres terminées</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-number"><?= $stats['phoning_reste_appels'] ?></div>
            <div class="stat-label">Appels restants (semaine)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $stats['phoning_reste_rdv'] ?></div>
            <div class="stat-label">RDV restants (semaine)</div>
        </div>
    </div>
</div>

<!-- Formations -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-number"><?= $stats['formations_a_venir'] ?></div>
            <div class="stat-label">Formations à venir</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $stats['formations_non_expansya'] ?></div>
            <div class="stat-label">Notes de frais non envoyées</div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <a href="modules/formations/index.php" class="btn btn-ce-outline"><i class="fas fa-graduation-cap"></i> Mes formations</a>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <a href="modules/formations/calendrier.php" class="btn btn-ce-outline"><i class="fas fa-calendar-alt"></i> Calendrier formations</a>
    </div>
</div>

<!-- Phoning Progress -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="dashboard-card">
            <h4><i class="fas fa-phone-volume"></i> Appels cette semaine (<?= $stats['phoning_appels_semaine'] ?>/60)</h4>
            <div class="progress progress-ce" style="height: 30px;">
                <div class="progress-bar" role="progressbar" style="width: <?= min(100, ($stats['phoning_appels_semaine']/60)*100) ?>%">
                    <?= $stats['phoning_appels_semaine'] ?>/60
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="dashboard-card">
            <h4><i class="fas fa-calendar-check"></i> RDV cette semaine (<?= $stats['phoning_rdv_semaine'] ?>/12)</h4>
            <div class="progress progress-ce" style="height: 30px;">
                <div class="progress-bar" role="progressbar" style="width: <?= min(100, ($stats['phoning_rdv_semaine']/12)*100) ?>%">
                    <?= $stats['phoning_rdv_semaine'] ?>/12
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Procédures mises en avant -->
<?php if (!empty($proceduresEnAvant)): ?>
<div class="dashboard-card">
    <h4><i class="fas fa-star"></i> Procédures à la une</h4>
    <?php foreach ($proceduresEnAvant as $proc): ?>
        <div class="procedure-highlight">
            <span><i class="fas fa-book me-2"></i><?= e($proc['nom']) ?></span>
            <a href="modules/procedures/view.php?id=<?= $proc['id'] ?>" class="btn btn-sm btn-ce-outline">Lire</a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
