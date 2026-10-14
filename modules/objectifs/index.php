<?php
$pageTitle = 'Objectifs vs réalisé';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/objectifs.php';

$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();
ensureObjectifsSchema();

// ---------- Sélection de la période ----------
[$defFrom, $defTo] = objectifsDefaultPeriod();
$from = $_GET['from'] ?? $defFrom;
$to   = $_GET['to'] ?? $defTo;
$dtFrom = DateTime::createFromFormat('Y-m-d', $from);
$dtTo   = DateTime::createFromFormat('Y-m-d', $to);
if (!$dtFrom || !$dtTo || $dtFrom > $dtTo) { $from = $defFrom; $to = $defTo; }

// ---------- Sélection du conseiller ----------
$conseillers = getObjectifsConseillers($db);
$targetUserId = $userId;
if ($isUserAdmin && !empty($_GET['conseiller'])) {
    $targetUserId = (int)$_GET['conseiller'];
}
$canEdit = ($targetUserId === $userId) || $isUserAdmin;

$targetName = 'Moi';
foreach ($conseillers as $c) {
    if ((int)$c['id'] === $targetUserId) { $targetName = $c['prenom'] . ' ' . $c['nom']; break; }
}

// ---------- Enregistrement ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $attendus = (array)($_POST['attendus'] ?? []);
    $manuels  = (array)($_POST['manuels'] ?? []);
    saveObjectifs($db, $targetUserId, $from, $to, $attendus, $manuels);
    header('Location: index.php?' . http_build_query(['from' => $from, 'to' => $to, 'conseiller' => $isUserAdmin ? $targetUserId : null, 'saved' => 1]));
    exit;
}

// ---------- Données ----------
$objectifs = getObjectifs($db, $targetUserId, $from, $to);
$realise   = getKpiRealise($db, $targetUserId, $from, $to);
$sections  = getObjectifsSections();

// ---------- Pré-remplissage depuis les attendus EAI ----------
// Les attendus EAI (onglet « Attendus ») sont des cibles HEBDOMADAIRES globales.
// On les convertit pour la période : les taux (%) sont repris tels quels, les
// volumes et nombres sont multipliés par le nombre de semaines de la période.
$eaiAttendus = getEaiAttendus($db);
$periodDays  = max(1, (int)round((strtotime($to) - strtotime($from)) / 86400) + 1);
$weeks       = max(1, round($periodDays / 7, 1));
$eaiMap      = ['rdv' => 'rdv_s1', 'rdv_anv' => 'rdv_anv_s1']; // RDV agrégés ← « semaine en cours » EAI
$eaiPrefill  = [];
foreach (getKpiDefs() as $def) {
    $srcCle = $eaiMap[$def['cle']] ?? $def['cle'];
    if (empty($eaiAttendus[$srcCle])) continue;
    $v = (float)$eaiAttendus[$srcCle];
    $eaiPrefill[$def['cle']] = ($def['unite'] === '%') ? $v : round($v * $weeks, 2);
}

// Totaux (pour un petit indicateur global)
$nbAtteints = 0; $nbEvalues = 0;
foreach ($objectifs as $cle => $o) {
    $att = (float)$o['valeur_attendue'];
    if ($att > 0) { $nbEvalues++; if ((float)$realise[$cle] >= $att) $nbAtteints++; }
}

function objectifsEcartClass($att, $real) {
    if ($att <= 0) return 'secondary';
    $pct = $real / $att * 100;
    if ($pct >= 100) return 'success';
    if ($pct >= 70) return 'warning text-dark';
    return 'danger';
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1"><i class="fas fa-gauge-high"></i> Objectifs vs réalisé</h4>
        <div class="text-muted">
            Période du <strong><?= e(formatDate($from)) ?></strong> au <strong><?= e(formatDate($to)) ?></strong>
            — Conseiller : <strong><?= e($targetName) ?></strong>
        </div>
    </div>
    <?php if ($nbEvalues > 0): ?>
        <div class="text-end">
            <div class="fs-4 fw-bold <?= $nbAtteints >= $nbEvalues ? 'text-success' : 'text-danger' ?>">
                <?= $nbAtteints ?>/<?= $nbEvalues ?>
            </div>
            <div class="small text-muted">objectif(s) atteint(s)</div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($_GET['saved'])): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check"></i> Enregistré.</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="index.php">
            <div class="col-sm-3">
                <label class="form-label mb-1">Du</label>
                <input type="date" class="form-control" name="from" value="<?= e($from) ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label mb-1">Au</label>
                <input type="date" class="form-control" name="to" value="<?= e($to) ?>">
            </div>
            <?php if ($isUserAdmin): ?>
            <div class="col-sm-3">
                <label class="form-label mb-1">Conseiller</label>
                <select class="form-select" name="conseiller">
                    <option value="<?= (int)$userId ?>">— Moi —</option>
                    <?php foreach ($conseillers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $targetUserId ? 'selected' : '' ?>>
                            <?= e($c['nom'] . ' ' . $c['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-sm-3">
                <button class="btn btn-ce w-100"><i class="fas fa-filter"></i> Afficher</button>
            </div>
        </form>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <?php
            $presets = [
                'Ce mois'      => [date('Y-m-01'), date('Y-m-t')],
                'Mois dernier' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
                'Ce trimestre' => [date('Y-m-01', mktime(0, 0, 0, (int)((ceil((int)date('n') / 3) * 3) - 2), 1, (int)date('Y'))), date('Y-m-t', mktime(0, 0, 0, (int)(ceil((int)date('n') / 3) * 3), 1, (int)date('Y')))],
                'Cette année'  => [date('Y-01-01'), date('Y-12-31')],
            ];
            foreach ($presets as $label => $p):
                $link = 'index.php?' . http_build_query(['from' => $p[0], 'to' => $p[1], 'conseiller' => $isUserAdmin ? $targetUserId : null]);
            ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($link) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<form method="POST" action="index.php?<?= http_build_query(['from' => $from, 'to' => $to, 'conseiller' => $isUserAdmin ? $targetUserId : null]) ?>">
<div class="row g-3">
    <?php foreach ($sections as $secKey => $secLabel):
        $hasRows = false;
        foreach ($objectifs as $o) if ($o['section'] === $secKey) { $hasRows = true; break; }
        if (!$hasRows) continue;
    ?>
    <div class="col-12">
        <div class="data-table-container">
            <div class="data-table-header"><h3><i class="fas fa-chart-simple"></i> <?= e($secLabel) ?></h3></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Indicateur</th>
                            <th style="width:140px">Objectif</th>
                            <th style="width:140px">Réalisé</th>
                            <th style="width:110px">Écart</th>
                            <th style="min-width:160px">Progression</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (getKpiDefs() as $def):
                        if ($def['section'] !== $secKey) continue;
                        $cle = $def['cle'];
                        $att = (float)($objectifs[$cle]['valeur_attendue'] ?? 0);
                        $real = (float)($realise[$cle] ?? 0);
                        $auto = $def['source'] !== 'manuel';
                        $ecart = $real - $att;
                        $pct = objectifsPercent($att, $real);
                        $cls = objectifsEcartClass($att, $real);
                    ?>
                        <tr>
                            <td>
                                <?= e($def['libelle']) ?>
                                <?php if ($auto): ?><i class="fas fa-bolt text-muted small" title="Calculé automatiquement"></i>
                                <?php else: ?><i class="fas fa-pen text-muted small" title="Réalisé saisi manuellement"></i><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0" class="form-control" name="attendus[<?= e($cle) ?>]" value="<?= $att > 0 ? e(rtrim(rtrim(number_format($att, 2, '.', ''), '0'), '.')) : '' ?>" placeholder="—">
                                        <span class="input-group-text"><?= e($def['unite']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <?= $att > 0 ? e(formatKpi($att, $def['unite'])) : '<span class="text-muted">—</span>' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($auto): ?>
                                    <strong><?= e(formatKpi($real, $def['unite'])) ?></strong>
                                <?php elseif ($canEdit): ?>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0" class="form-control" name="manuels[<?= e($cle) ?>]" value="<?= $real != 0 ? e(rtrim(rtrim(number_format($real, 2, '.', ''), '0'), '.')) : '' ?>" placeholder="—">
                                        <span class="input-group-text"><?= e($def['unite']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <?= e(formatKpi($real, $def['unite'])) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($att > 0): ?>
                                    <span class="text-<?= $ecart >= 0 ? 'success' : 'danger' ?>">
                                        <?= $ecart >= 0 ? '+' : '' ?><?= e(formatKpi($ecart, $def['unite'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pct === null): ?>
                                    <span class="text-muted small">objectif non défini</span>
                                <?php else: ?>
                                    <div class="progress" style="height:18px">
                                        <div class="progress-bar bg-<?= explode(' ', $cls)[0] ?>" style="width:<?= min(100, max(0, $pct)) ?>%"></div>
                                    </div>
                                    <div class="small text-<?= explode(' ', $cls)[0] ?>"><?= (int)$pct ?> %</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($canEdit): ?>
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer les objectifs<?= $targetUserId === $userId ? '' : ' de ' . e($targetName) ?></button>
            <button type="button" class="btn btn-outline-secondary" onclick="prefillEai()"><i class="fas fa-wand-magic-sparkles"></i> Pré-remplir depuis les attendus EAI</button>
            <span class="small text-muted">Reprend les cibles EAI de la semaine, converties pour la période (×&nbsp;<?= e((string)$weeks) ?>&nbsp;sem.).</span>
        </div>
    <?php else: ?>
        <div class="col-12"><div class="alert alert-info mb-0">Consultation seule.</div></div>
    <?php endif; ?>
</div>
</form>

<div class="alert alert-light border mt-3 small">
    <i class="fas fa-circle-info"></i>
    Les indicateurs marqués <i class="fas fa-bolt"></i> sont calculés automatiquement sur la période depuis vos modules
    (production, phoning). Ceux marqués <i class="fas fa-pen"></i> (portefeuille, taux de réponse aux mails) sont à saisir manuellement.
    <br>
    <i class="fas fa-bullseye"></i> Les cibles peuvent être reprises du module <strong>EAI</strong> (attendus hebdomadaires,
    définis par un administrateur) via le bouton « Pré-remplir depuis les attendus EAI » : les volumes sont convertis au prorata
    des semaines de la période, les taux sont repris tels quels.
</div>

<script>
const EAI_PREFILL = <?= json_encode($eaiPrefill, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
function prefillEai() {
    let n = 0;
    Object.keys(EAI_PREFILL).forEach(function (cle) {
        const inp = document.querySelector('input[name="attendus[' + cle + ']"]');
        if (inp && inp.value.trim() === '') { inp.value = EAI_PREFILL[cle]; n++; }
    });
    if (n === 0) alert("Aucun champ vide à pré-remplir (ou aucun attendu EAI défini).");
    else alert(n + " objectif(s) pré-rempli(s) depuis les attendus EAI. Vérifiez les montants puis enregistrez.");
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
