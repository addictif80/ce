<?php
$pageTitle = 'EAI';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isAdmin = isAdmin();

// ========= AUTO-MIGRATION =========
try {
    $db->exec("CREATE TABLE IF NOT EXISTS eai_attendus (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cle VARCHAR(50) NOT NULL UNIQUE,
        libelle VARCHAR(255) NOT NULL,
        section VARCHAR(50) NOT NULL,
        valeur_attendue DECIMAL(15,2) DEFAULT 0,
        unite VARCHAR(20) DEFAULT '',
        ordre INT DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS eai_rapports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        semaine_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_semaine (user_id, semaine_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS eai_valeurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rapport_id INT NOT NULL,
        cle VARCHAR(50) NOT NULL,
        valeur DECIMAL(15,2) DEFAULT 0,
        auto_filled TINYINT(1) DEFAULT 0,
        UNIQUE KEY unique_rapport_cle (rapport_id, cle)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ========= DÉFINITION DES KPIs =========
$kpiDefs = [
    // Section Ventes
    ['cle' => 'ventes_brut_anv', 'libelle' => 'Ventes brut ANV', 'section' => 'ventes', 'unite' => '', 'ordre' => 1],
    ['cle' => 'volume_pret_perso', 'libelle' => 'Volume Prêt Personnel', 'section' => 'ventes', 'unite' => '€', 'ordre' => 2],
    ['cle' => 'cartes_hdg_dd', 'libelle' => 'Cartes HDG DD', 'section' => 'ventes', 'unite' => '', 'ordre' => 3],
    ['cle' => 'izicartes', 'libelle' => 'Izicartes', 'section' => 'ventes', 'unite' => '', 'ordre' => 4],
    ['cle' => 'forfaits', 'libelle' => 'Forfaits', 'section' => 'ventes', 'unite' => '', 'ordre' => 5],
    ['cle' => 'volume_collecte', 'libelle' => 'Volume Collecte', 'section' => 'ventes', 'unite' => '€', 'ordre' => 6],
    ['cle' => 'livrets', 'libelle' => 'Livrets', 'section' => 'ventes', 'unite' => '', 'ordre' => 7],
    ['cle' => 'pel_quadreto', 'libelle' => 'PEL / Quadreto', 'section' => 'ventes', 'unite' => '', 'ordre' => 8],
    ['cle' => 'assvie_peri', 'libelle' => 'AssVie / PERi', 'section' => 'ventes', 'unite' => '', 'ordre' => 9],
    ['cle' => 'volume_parts_sociales', 'libelle' => 'Volume Parts Sociales', 'section' => 'ventes', 'unite' => '€', 'ordre' => 10],
    ['cle' => 'nouveau_societaire', 'libelle' => 'Nouveau Sociétaire', 'section' => 'ventes', 'unite' => '', 'ordre' => 11],
    ['cle' => 'bp_jbp', 'libelle' => 'BP / jBP', 'section' => 'ventes', 'unite' => '', 'ordre' => 12],
    ['cle' => 'equip_jequip', 'libelle' => 'Equip / jEquip', 'section' => 'ventes', 'unite' => '', 'ordre' => 13],
    // Section Activité
    ['cle' => 'taux_decroche', 'libelle' => 'Taux décroché', 'section' => 'activite', 'unite' => '%', 'ordre' => 14],
    ['cle' => 'taux_reponse_mails', 'libelle' => 'Taux réponse aux mails', 'section' => 'activite', 'unite' => '%', 'ordre' => 15],
    ['cle' => 'volume_appels_sortants', 'libelle' => 'Volume appels sortants', 'section' => 'activite', 'unite' => '', 'ordre' => 16],
    // Section RDV — S = sem. passée, S+1 = sem. en cours, S+2 = sem. prochaine
    ['cle' => 'rdv_s', 'libelle' => 'Nombre de RDV - Sem. passée', 'section' => 'rdv', 'unite' => '', 'ordre' => 17],
    ['cle' => 'rdv_s1', 'libelle' => 'Nombre de RDV - Sem. en cours', 'section' => 'rdv', 'unite' => '', 'ordre' => 18],
    ['cle' => 'rdv_s2', 'libelle' => 'Nombre de RDV - Sem. prochaine', 'section' => 'rdv', 'unite' => '', 'ordre' => 19],
    ['cle' => 'rdv_proactifs_s', 'libelle' => 'RDV Proactifs - Sem. passée', 'section' => 'rdv', 'unite' => '', 'ordre' => 20],
    ['cle' => 'rdv_proactifs_s1', 'libelle' => 'RDV Proactifs - Sem. en cours', 'section' => 'rdv', 'unite' => '', 'ordre' => 21],
    ['cle' => 'rdv_proactifs_s2', 'libelle' => 'RDV Proactifs - Sem. prochaine', 'section' => 'rdv', 'unite' => '', 'ordre' => 22],
    ['cle' => 'rdv_anv_s', 'libelle' => 'RDV ANV - Sem. passée', 'section' => 'rdv', 'unite' => '', 'ordre' => 23],
    ['cle' => 'rdv_anv_s1', 'libelle' => 'RDV ANV - Sem. en cours', 'section' => 'rdv', 'unite' => '', 'ordre' => 24],
    ['cle' => 'rdv_anv_s2', 'libelle' => 'RDV ANV - Sem. prochaine', 'section' => 'rdv', 'unite' => '', 'ordre' => 25],
    // Section Portefeuille
    ['cle' => 'myflow_en_cours', 'libelle' => 'MyFlow en cours', 'section' => 'portefeuille', 'unite' => '', 'ordre' => 26],
    ['cle' => 'vigiclients_en_cours', 'libelle' => 'Vigiclients en cours', 'section' => 'portefeuille', 'unite' => '', 'ordre' => 27],
    ['cle' => 'rpm_taux_traitement', 'libelle' => 'RPM - Taux de traitement', 'section' => 'portefeuille', 'unite' => '%', 'ordre' => 28],
    ['cle' => 'rpm_clients_15j', 'libelle' => 'RPM - Clients > 15j', 'section' => 'portefeuille', 'unite' => '', 'ordre' => 29],
    ['cle' => 'rpm_montant_total', 'libelle' => 'RPM - Montant total', 'section' => 'portefeuille', 'unite' => '€', 'ordre' => 30],
];

// Seed attendus si vide
$count = $db->query("SELECT COUNT(*) FROM eai_attendus")->fetchColumn();
if ($count == 0) {
    $stmt = $db->prepare("INSERT INTO eai_attendus (cle, libelle, section, unite, ordre) VALUES (?, ?, ?, ?, ?)");
    foreach ($kpiDefs as $kpi) {
        try { $stmt->execute([$kpi['cle'], $kpi['libelle'], $kpi['section'], $kpi['unite'], $kpi['ordre']]); } catch (Exception $e) {}
    }
}

// Migration : mise à jour des libellés RDV pour refléter sem. passée / en cours / prochaine
$rdvLabels = [
    'rdv_s'           => 'Nombre de RDV - Sem. passée',
    'rdv_s1'          => 'Nombre de RDV - Sem. en cours',
    'rdv_s2'          => 'Nombre de RDV - Sem. prochaine',
    'rdv_proactifs_s' => 'RDV Proactifs - Sem. passée',
    'rdv_proactifs_s1'=> 'RDV Proactifs - Sem. en cours',
    'rdv_proactifs_s2'=> 'RDV Proactifs - Sem. prochaine',
    'rdv_anv_s'       => 'RDV ANV - Sem. passée',
    'rdv_anv_s1'      => 'RDV ANV - Sem. en cours',
    'rdv_anv_s2'      => 'RDV ANV - Sem. prochaine',
];
$stmtLabel = $db->prepare("UPDATE eai_attendus SET libelle = ? WHERE cle = ? AND libelle != ?");
foreach ($rdvLabels as $cle => $libelle) {
    try { $stmtLabel->execute([$libelle, $cle, $libelle]); } catch (Exception $e) {}
}

// ========= FONCTION AUTO-FILL =========
function getEaiAutoFillData($db, $userId, $tuesdayDate) {
    // Calcul du lundi de la semaine en cours (contenant le mardi de génération)
    $dt = new DateTime($tuesdayDate);
    $dow = (int)$dt->format('N');
    $mondayDt = clone $dt;
    $mondayDt->modify('-' . ($dow - 1) . ' days');

    // Semaine passée (S-1) : activité + RDV de la sem. dernière
    $prevMon = (clone $mondayDt)->modify('-7 days')->format('Y-m-d');
    $prevSun = (clone $mondayDt)->modify('-1 day')->format('Y-m-d');

    // Semaine en cours (S) : RDV de cette semaine (dont ANV)
    $curMon = $mondayDt->format('Y-m-d');
    $curSun = (clone $mondayDt)->modify('+6 days')->format('Y-m-d');

    // Semaine prochaine (S+1) : RDV de la semaine prochaine (dont ANV)
    $nextMon = (clone $mondayDt)->modify('+7 days')->format('Y-m-d');
    $nextSun = (clone $mondayDt)->modify('+13 days')->format('Y-m-d');

    $data = [];

    // ========= ACTIVITÉ (semaine passée) =========

    // Volume appels sortants — sem. passée
    $stmt = $db->prepare("SELECT COALESCE(SUM(nombre_appels), 0) FROM seances_phoning WHERE user_id = ? AND date_ajout BETWEEN ? AND ?");
    $stmt->execute([$userId, $prevMon, $prevSun]);
    $data['volume_appels_sortants'] = (float)$stmt->fetchColumn();

    // Taux décroché — sem. passée
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(resultat IN ('repondu','rdv')) as decroche FROM appels_phoning WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$userId, $prevMon, $prevSun]);
    $r = $stmt->fetch();
    $total = (int)$r['total'];
    $data['taux_decroche'] = $total > 0 ? round((float)$r['decroche'] / $total * 100, 1) : 0;

    // ========= RDV =========
    // S = sem. passée | S+1 = sem. en cours | S+2 = sem. prochaine
    $rdvStmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(is_anv = 1), 0) as anv FROM appels_phoning WHERE user_id = ? AND resultat = 'rdv' AND date_rdv BETWEEN ? AND ?");

    // S (sem. passée)
    $rdvStmt->execute([$userId, $prevMon, $prevSun]);
    $r = $rdvStmt->fetch();
    $data['rdv_s'] = (int)$r['total'];
    $data['rdv_proactifs_s'] = (int)$r['total'];
    $data['rdv_anv_s'] = (int)$r['anv'];

    // S+1 (sem. en cours)
    $rdvStmt->execute([$userId, $curMon, $curSun]);
    $r = $rdvStmt->fetch();
    $data['rdv_s1'] = (int)$r['total'];
    $data['rdv_proactifs_s1'] = (int)$r['total'];
    $data['rdv_anv_s1'] = (int)$r['anv'];

    // S+2 (sem. prochaine)
    $rdvStmt->execute([$userId, $nextMon, $nextSun]);
    $r = $rdvStmt->fetch();
    $data['rdv_s2'] = (int)$r['total'];
    $data['rdv_proactifs_s2'] = (int)$r['total'];
    $data['rdv_anv_s2'] = (int)$r['anv'];

    // ========= VENTES depuis suivi_production (semaine passée) =========

    // Clés comptées (COUNT des lignes)
    $clesCount = ['ventes_brut_anv', 'cartes_hdg_dd', 'izicartes', 'forfaits', 'livrets',
                  'pel_quadreto', 'assvie_peri', 'nouveau_societaire', 'equip_jequip', 'bp_jbp'];
    $stmtCount = $db->prepare("SELECT COALESCE(COUNT(*), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
    foreach ($clesCount as $cle) {
        $stmtCount->execute([$userId, $cle, $prevMon, $prevSun]);
        $data[$cle] = (int)$stmtCount->fetchColumn();
    }

    // Clés monétaires (SUM du montant_nombre) — sem. passée
    $clesSum = ['volume_pret_perso', 'volume_collecte', 'volume_parts_sociales'];
    $stmtSum = $db->prepare("SELECT COALESCE(SUM(CAST(montant_nombre AS DECIMAL(15,2))), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
    foreach ($clesSum as $cle) {
        $stmtSum->execute([$userId, $cle, $prevMon, $prevSun]);
        $data[$cle] = (float)$stmtSum->fetchColumn();
    }

    // Si aucun ANV en production, fallback sur les RDV ANV sem. passée (phoning)
    if ($data['ventes_brut_anv'] === 0) {
        $data['ventes_brut_anv'] = $data['rdv_anv_s'];
    }

    return $data;
}

// ========= POST HANDLERS =========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_attendus' && $isAdmin) {
        foreach ($_POST['attendus'] as $cle => $val) {
            $db->prepare("UPDATE eai_attendus SET valeur_attendue = ? WHERE cle = ?")
                ->execute([(float)$val, $cle]);
        }
        header('Location: ?tab=attendus&saved=1');
        exit;
    }

    if ($action === 'generate_eai') {
        $semaine = $_POST['semaine'];
        // Créer ou récupérer le rapport
        $stmt = $db->prepare("SELECT id FROM eai_rapports WHERE user_id = ? AND semaine_date = ?");
        $stmt->execute([$userId, $semaine]);
        $rapportId = $stmt->fetchColumn();

        if (!$rapportId) {
            $db->prepare("INSERT INTO eai_rapports (user_id, semaine_date) VALUES (?, ?)")
                ->execute([$userId, $semaine]);
            $rapportId = $db->lastInsertId();
        }

        // Auto-fill
        $autoData = getEaiAutoFillData($db, $userId, $semaine);

        // Seed toutes les valeurs
        $allCles = $db->query("SELECT cle FROM eai_attendus ORDER BY ordre")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($allCles as $cle) {
            $isAuto = isset($autoData[$cle]);
            $val = $isAuto ? $autoData[$cle] : 0;

            $existing = $db->prepare("SELECT id, auto_filled FROM eai_valeurs WHERE rapport_id = ? AND cle = ?");
            $existing->execute([$rapportId, $cle]);
            $row = $existing->fetch();

            if (!$row) {
                $db->prepare("INSERT INTO eai_valeurs (rapport_id, cle, valeur, auto_filled) VALUES (?, ?, ?, ?)")
                    ->execute([$rapportId, $cle, $val, $isAuto ? 1 : 0]);
            } elseif ($isAuto && $row['auto_filled']) {
                // Mettre à jour uniquement les valeurs auto (pas les manuelles)
                $db->prepare("UPDATE eai_valeurs SET valeur = ? WHERE id = ?")
                    ->execute([$val, $row['id']]);
            }
        }

        header('Location: ?semaine=' . $semaine);
        exit;
    }

    if ($action === 'save_valeurs') {
        $rapportId = (int)$_POST['rapport_id'];
        $owner = $db->prepare("SELECT user_id FROM eai_rapports WHERE id = ?");
        $owner->execute([$rapportId]);
        if ($owner->fetchColumn() == $userId) {
            foreach ($_POST['valeurs'] as $cle => $val) {
                $db->prepare("INSERT INTO eai_valeurs (rapport_id, cle, valeur, auto_filled) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE valeur = ?, auto_filled = 0")
                    ->execute([$rapportId, $cle, (float)$val, (float)$val]);
            }
        }
        $semaine = $_POST['semaine'] ?? '';
        header('Location: ?semaine=' . $semaine . '&saved=1');
        exit;
    }
}

// ========= DATA LOADING =========
$tab = $_GET['tab'] ?? 'rapport';

// Calculer le mardi de la semaine
$selectedSemaine = $_GET['semaine'] ?? null;
if ($selectedSemaine) {
    $tuesdayDt = new DateTime($selectedSemaine);
} else {
    $tuesdayDt = new DateTime();
    $dow = (int)$tuesdayDt->format('N');
    $diff = 2 - $dow;
    $tuesdayDt->modify("$diff days");
}
$tuesdayStr = $tuesdayDt->format('Y-m-d');
$tuesdayLabel = $tuesdayDt->format('d/m/Y');

// Semaine précédente / suivante
$prevTuesday = (clone $tuesdayDt)->modify('-7 days')->format('Y-m-d');
$nextTuesday = (clone $tuesdayDt)->modify('+7 days')->format('Y-m-d');

// Numéro de semaine
$weekNum = $tuesdayDt->format('W');
$yearNum = $tuesdayDt->format('o');

// Charger les attendus
$attendus = [];
$stmt = $db->query("SELECT * FROM eai_attendus ORDER BY ordre");
foreach ($stmt->fetchAll() as $row) {
    $attendus[$row['cle']] = $row;
}

// Charger le rapport de la semaine sélectionnée
$rapport = null;
$valeurs = [];
$stmt = $db->prepare("SELECT * FROM eai_rapports WHERE user_id = ? AND semaine_date = ?");
$stmt->execute([$userId, $tuesdayStr]);
$rapport = $stmt->fetch();

if ($rapport) {
    $stmt = $db->prepare("SELECT * FROM eai_valeurs WHERE rapport_id = ?");
    $stmt->execute([$rapport['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $valeurs[$row['cle']] = $row;
    }
}

// Historique des rapports
$stmt = $db->prepare("SELECT semaine_date FROM eai_rapports WHERE user_id = ? ORDER BY semaine_date DESC LIMIT 10");
$stmt->execute([$userId]);
$historique = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Helpers
$sections = [
    'ventes' => ['label' => 'Ventes', 'icon' => 'fa-shopping-cart', 'color' => 'primary'],
    'activite' => ['label' => 'Activité', 'icon' => 'fa-phone-volume', 'color' => 'info'],
    'rdv' => ['label' => 'Rendez-vous', 'icon' => 'fa-calendar-check', 'color' => 'success'],
    'portefeuille' => ['label' => 'Portefeuille', 'icon' => 'fa-briefcase', 'color' => 'warning'],
];

// KPIs auto-fillables
$autoFillKeys = ['volume_appels_sortants', 'taux_decroche',
    'ventes_brut_anv', 'volume_pret_perso', 'cartes_hdg_dd', 'izicartes', 'forfaits',
    'volume_collecte', 'livrets', 'pel_quadreto', 'assvie_peri', 'volume_parts_sociales',
    'nouveau_societaire', 'equip_jequip', 'bp_jbp',
    'rdv_s', 'rdv_s1', 'rdv_s2', 'rdv_proactifs_s', 'rdv_proactifs_s1', 'rdv_proactifs_s2',
    'rdv_anv_s', 'rdv_anv_s1', 'rdv_anv_s2'];

function getValeur($valeurs, $cle) {
    return isset($valeurs[$cle]) ? (float)$valeurs[$cle]['valeur'] : 0;
}
function getAttendu($attendus, $cle) {
    return isset($attendus[$cle]) ? (float)$attendus[$cle]['valeur_attendue'] : 0;
}
function ecartClass($realise, $attendu, $inverse = false) {
    if ($attendu == 0) return '';
    $ok = $inverse ? $realise <= $attendu : $realise >= $attendu;
    return $ok ? 'text-success' : 'text-danger';
}
function formatVal($val, $unite) {
    if ($unite === '€') return number_format($val, 0, ',', ' ') . ' €';
    if ($unite === '%') return number_format($val, 1, ',', ' ') . ' %';
    return number_format($val, 0, ',', ' ');
}
?>

<!-- Onglets -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'rapport' ? 'active' : '' ?>" href="?tab=rapport&semaine=<?= $tuesdayStr ?>">
            <i class="fas fa-chart-bar"></i> Rapport EAI
        </a>
    </li>
    <?php if ($isAdmin): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'attendus' ? 'active' : '' ?>" href="?tab=attendus">
            <i class="fas fa-bullseye"></i> Attendus
        </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'historique' ? 'active' : '' ?>" href="?tab=historique">
            <i class="fas fa-history"></i> Historique
        </a>
    </li>
</ul>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> Enregistré avec succès.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($tab === 'attendus' && $isAdmin): ?>
<!-- ========= ONGLET ATTENDUS (Admin) ========= -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-bullseye"></i> Définir les attendus hebdomadaires</h3>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="save_attendus">
        <?php foreach ($sections as $sectionKey => $sectionInfo): ?>
        <h5 class="mt-4 mb-3">
            <i class="fas <?= $sectionInfo['icon'] ?>"></i>
            <span class="badge bg-<?= $sectionInfo['color'] ?>"><?= $sectionInfo['label'] ?></span>
        </h5>
        <table class="data-table mb-3">
            <thead>
                <tr>
                    <th style="width:50%">Indicateur</th>
                    <th style="width:20%">Unité</th>
                    <th style="width:30%">Attendu</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($attendus as $cle => $att):
                if ($att['section'] !== $sectionKey) continue;
            ?>
                <tr>
                    <td><?= e($att['libelle']) ?></td>
                    <td><span class="text-muted"><?= $att['unite'] ?: 'Nombre' ?></span></td>
                    <td>
                        <input type="number" step="0.01" name="attendus[<?= $cle ?>]"
                               class="form-control form-control-sm" style="max-width:150px"
                               value="<?= $att['valeur_attendue'] ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
        <div class="text-end mt-3 mb-3">
            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer les attendus</button>
        </div>
    </form>
</div>

<?php elseif ($tab === 'historique'): ?>
<!-- ========= ONGLET HISTORIQUE ========= -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3><i class="fas fa-history"></i> Historique des EAI</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Semaine</th><th>Date du mardi</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($historique)): ?>
            <tr><td colspan="3" class="text-center text-muted">Aucun EAI généré</td></tr>
        <?php else: ?>
            <?php foreach ($historique as $date):
                $d = new DateTime($date);
            ?>
            <tr>
                <td><strong>S<?= $d->format('W') ?> - <?= $d->format('o') ?></strong></td>
                <td><?= $d->format('d/m/Y') ?></td>
                <td><a href="?tab=rapport&semaine=<?= $date ?>" class="btn btn-sm btn-ce-outline"><i class="fas fa-eye"></i> Voir</a></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<!-- ========= ONGLET RAPPORT EAI ========= -->

<!-- Navigation semaine -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="?semaine=<?= $prevTuesday ?>" class="btn btn-ce-outline">
        <i class="fas fa-chevron-left"></i> Semaine précédente
    </a>
    <div class="text-center">
        <h4 class="mb-0">EAI - Semaine <?= $weekNum ?> (<?= $yearNum ?>)</h4>
        <small class="text-muted">Mardi <?= $tuesdayLabel ?></small>
    </div>
    <a href="?semaine=<?= $nextTuesday ?>" class="btn btn-ce-outline">
        Semaine suivante <i class="fas fa-chevron-right"></i>
    </a>
</div>

<!-- Boutons d'action -->
<div class="d-flex gap-2 mb-4">
    <form method="post" class="d-inline">
        <input type="hidden" name="action" value="generate_eai">
        <input type="hidden" name="semaine" value="<?= $tuesdayStr ?>">
        <button type="submit" class="btn btn-ce">
            <i class="fas fa-sync-alt"></i> <?= $rapport ? 'Actualiser les données auto' : 'Générer l\'EAI' ?>
        </button>
    </form>
    <?php if ($rapport): ?>
    <span class="badge bg-success align-self-center"><i class="fas fa-check"></i> EAI généré</span>
    <?php endif; ?>
</div>

<?php if (!$rapport): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Cliquez sur <strong>"Générer l'EAI"</strong> pour créer le rapport de cette semaine.
    Les données seront pré-remplies automatiquement depuis les modules Phoning et Mobilités.
</div>
<?php else: ?>

<!-- Formulaire de valeurs -->
<form method="post" id="formEai">
    <input type="hidden" name="action" value="save_valeurs">
    <input type="hidden" name="rapport_id" value="<?= $rapport['id'] ?>">
    <input type="hidden" name="semaine" value="<?= $tuesdayStr ?>">

    <?php foreach ($sections as $sectionKey => $sectionInfo): ?>
    <div class="data-table-container mb-4">
        <div class="data-table-header">
            <h3>
                <i class="fas <?= $sectionInfo['icon'] ?>"></i> <?= $sectionInfo['label'] ?>
            </h3>
        </div>

        <?php if ($sectionKey === 'rdv'): ?>
        <!-- Section RDV en format grille -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:28%"></th>
                    <th style="width:24%" class="text-center">S</th>
                    <th style="width:24%" class="text-center">S+1</th>
                    <th style="width:24%" class="text-center">S+2</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rdvGroups = [
                    'Nombre de RDV' => ['rdv_s', 'rdv_s1', 'rdv_s2'],
                    'RDV Proactifs' => ['rdv_proactifs_s', 'rdv_proactifs_s1', 'rdv_proactifs_s2'],
                    'RDV ANV' => ['rdv_anv_s', 'rdv_anv_s1', 'rdv_anv_s2'],
                ];
                foreach ($rdvGroups as $groupLabel => $keys):
                ?>
                <tr>
                    <td><strong><?= $groupLabel ?></strong></td>
                    <?php foreach ($keys as $cle):
                        $realise = getValeur($valeurs, $cle);
                        $attendu = getAttendu($attendus, $cle);
                        $unite = $attendus[$cle]['unite'] ?? '';
                        $ecart = $realise - $attendu;
                        $cls = ecartClass($realise, $attendu);
                        $isAuto = in_array($cle, $autoFillKeys);
                    ?>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <input type="number" step="0.01" name="valeurs[<?= $cle ?>]"
                                   class="form-control form-control-sm text-center" style="width:70px"
                                   value="<?= $realise ?>"
                                   title="Réalisé">
                            <span class="text-muted">/</span>
                            <span class="text-muted" title="Attendu"><?= formatVal($attendu, $unite) ?></span>
                        </div>
                        <?php if ($attendu > 0): ?>
                        <small class="<?= $cls ?>">
                            <?= $ecart >= 0 ? '+' : '' ?><?= formatVal($ecart, $unite) ?>
                        </small>
                        <?php endif; ?>
                        <?php if ($isAuto && isset($valeurs[$cle]) && $valeurs[$cle]['auto_filled']): ?>
                        <br><span class="badge bg-light text-muted" style="font-size:0.65rem"><i class="fas fa-robot"></i> auto</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
        <!-- Sections standard -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:35%">Indicateur</th>
                    <th style="width:20%" class="text-center">Attendu</th>
                    <th style="width:25%" class="text-center">Réalisé</th>
                    <th style="width:20%" class="text-center">Écart</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($attendus as $cle => $att):
                if ($att['section'] !== $sectionKey) continue;
                $realise = getValeur($valeurs, $cle);
                $attenduVal = (float)$att['valeur_attendue'];
                $unite = $att['unite'];
                $ecart = $realise - $attenduVal;
                $cls = ecartClass($realise, $attenduVal);
                $isAuto = in_array($cle, $autoFillKeys);
                // Pour RPM clients > 15j, inversé (moins = mieux)
                if ($cle === 'rpm_clients_15j') $cls = ecartClass($realise, $attenduVal, true);
            ?>
                <tr>
                    <td>
                        <?= e($att['libelle']) ?>
                        <?php if ($isAuto && isset($valeurs[$cle]) && $valeurs[$cle]['auto_filled']): ?>
                        <span class="badge bg-light text-muted ms-1" style="font-size:0.65rem"><i class="fas fa-robot"></i> auto</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-muted"><?= formatVal($attenduVal, $unite) ?></td>
                    <td class="text-center">
                        <input type="number" step="0.01" name="valeurs[<?= $cle ?>]"
                               class="form-control form-control-sm text-center mx-auto" style="width:120px"
                               value="<?= $realise ?>">
                    </td>
                    <td class="text-center">
                        <?php if ($attenduVal > 0): ?>
                        <strong class="<?= $cls ?>">
                            <?= $ecart >= 0 ? '+' : '' ?><?= formatVal($ecart, $unite) ?>
                        </strong>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="submit" class="btn btn-ce btn-lg">
            <i class="fas fa-save"></i> Enregistrer les valeurs
        </button>
    </div>
</form>

<?php endif; // fin if rapport ?>

<?php endif; // fin tabs ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
