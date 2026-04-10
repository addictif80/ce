<?php
$id      = (int)($_GET['id'] ?? 0);
$isPrint = (isset($_GET['format']) && $_GET['format'] === 'print');

// ============================================================
// Mode impression : page autonome sans template
// ============================================================
if ($isPrint) {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();

    $db     = getDB();
    $userId = getCurrentUserId();
    $user   = getCurrentUser();

    if (!$id) { http_response_code(400); exit('ID manquant.'); }

    $stmt = $db->prepare("SELECT * FROM seances_phoning WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $seance = $stmt->fetch();
    if (!$seance) { http_response_code(404); exit('Séance introuvable.'); }

    $stmt = $db->prepare("SELECT * FROM appels_phoning WHERE seance_id = ? ORDER BY created_at ASC");
    $stmt->execute([$id]);
    $appels = $stmt->fetchAll();

    $totalAppels  = count($appels);
    $repondus = $repondeurs = $indispos = $rdvs = $anvs = 0;
    $rdvS = $rdvS1 = $rdvAutres = 0;
    $motifCounts = [];

    foreach ($appels as $a) {
        switch ($a['resultat']) {
            case 'repondu':      $repondus++;   break;
            case 'repondeur':    $repondeurs++; break;
            case 'indisponible': $indispos++;   break;
            case 'rdv':
                $rdvs++;
                if ($a['is_anv']) $anvs++;
                if ($a['motif_rdv']) {
                    $motifCounts[$a['motif_rdv']] = ($motifCounts[$a['motif_rdv']] ?? 0) + 1;
                }
                if ($a['date_rdv']) {
                    $semRdv   = (int)date('W', strtotime($a['date_rdv']));
                    $anneeRdv = (int)date('Y', strtotime($a['date_rdv']));
                    $semCur   = (int)date('W');
                    $anneeCur = (int)date('Y');
                    if ($anneeRdv === $anneeCur && $semRdv === $semCur)        $rdvS++;
                    elseif ($anneeRdv === $anneeCur && $semRdv === $semCur + 1) $rdvS1++;
                    elseif ($anneeRdv === $anneeCur + 1 && $semCur >= 52 && $semRdv === 1) $rdvS1++;
                    else $rdvAutres++;
                } else {
                    $rdvAutres++;
                }
                break;
        }
    }

    $titrePage  = $seance['titre'] ?: 'Séance du ' . formatDate($seance['date_ajout']);
    $expediteur = trim($user['prenom'] . ' ' . $user['nom']);
    $today      = date('d/m/Y');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titrePage) ?> — Rapport phoning</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 24px; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1a1a1a; }
        h1 { color: #CF0A2C; font-size: 18px; margin: 0 0 4px 0; }
        .meta { font-size: 12px; color: #666; margin-bottom: 20px; }
        .section { margin-bottom: 24px; page-break-inside: avoid; }
        .section-title { color: #CF0A2C; border-bottom: 2px solid #CF0A2C; padding-bottom: 5px; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
        .stat-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 20px; }
        .stat-cell { border: 1px solid #ddd; border-radius: 4px; padding: 8px; text-align: center; }
        .stat-cell .num { font-size: 20px; font-weight: bold; color: #CF0A2C; }
        .stat-cell .lbl { font-size: 11px; color: #666; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background-color: #CF0A2C; color: #fff; padding: 7px 10px; text-align: left; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        td { padding: 6px 10px; border-bottom: 1px solid #eee; }
        .badge-rdv { display:inline-block; background:#0d6efd; color:#fff; border-radius:3px; padding:1px 7px; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .badge-rep { display:inline-block; background:#198754; color:#fff; border-radius:3px; padding:1px 7px; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .badge-rnd { display:inline-block; background:#ffc107; color:#000; border-radius:3px; padding:1px 7px; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .badge-ind { display:inline-block; background:#6c757d; color:#fff; border-radius:3px; padding:1px 7px; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .badge-anv { display:inline-block; background:#0dcaf0; color:#000; border-radius:3px; padding:1px 7px; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .footer { margin-top: 24px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
        .no-print { margin-bottom: 16px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print">
    <button onclick="window.print()" style="padding:6px 14px;background:#CF0A2C;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">Imprimer</button>
    <button onclick="window.close()" style="padding:6px 14px;background:#6c757d;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;margin-left:8px;">Fermer</button>
</div>

<h1><?= e($titrePage) ?></h1>
<div class="meta">
    <?= e($expediteur) ?> &mdash; <?= formatDate($seance['date_ajout']) ?>
    <?php if ($seance['notes']): ?> &mdash; <em><?= e(excerpt($seance['notes'], 120)) ?></em><?php endif; ?>
    &mdash; imprimé le <?= $today ?>
</div>

<div class="stat-grid">
    <div class="stat-cell"><div class="num"><?= $totalAppels ?></div><div class="lbl">Appels</div></div>
    <div class="stat-cell"><div class="num"><?= $repondus ?></div><div class="lbl">Répondus</div></div>
    <div class="stat-cell"><div class="num"><?= $repondeurs ?></div><div class="lbl">Répondeurs</div></div>
    <div class="stat-cell"><div class="num"><?= $indispos ?></div><div class="lbl">Indisponibles</div></div>
    <div class="stat-cell"><div class="num"><?= $rdvs ?></div><div class="lbl">RDV</div></div>
    <div class="stat-cell"><div class="num"><?= $anvs ?></div><div class="lbl">ANV</div></div>
    <div class="stat-cell"><div class="num"><?= $rdvS ?> / <?= $rdvS1 ?> / <?= $rdvAutres ?></div><div class="lbl">S / S+1 / Autres</div></div>
</div>

<?php if (!empty($motifCounts)): ?>
<div class="section">
    <div class="section-title">Répartition des RDV par motif</div>
    <table>
        <thead>
            <tr><?php foreach (array_keys($motifCounts) as $m): ?><th><?= e($m) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
            <tr><?php foreach ($motifCounts as $n): ?><td><strong><?= $n ?></strong></td><?php endforeach; ?></tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="section">
    <div class="section-title">Détail des appels (<?= $totalAppels ?>)</div>
    <?php if (empty($appels)): ?>
        <p style="color:#888;font-style:italic;">Aucun appel enregistré pour cette séance.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Heure</th><th>N° / Nom</th><th>Résultat</th>
                <th>Date RDV</th><th>Motif</th><th>ANV</th><th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($appels as $i => $a): ?>
            <tr>
                <td style="color:#999;"><?= $i + 1 ?></td>
                <td><?= $a['created_at'] ? date('H:i', strtotime($a['created_at'])) : '—' ?></td>
                <td><strong><?= e($a['numero_personne']) ?></strong></td>
                <td>
                    <?php switch ($a['resultat']):
                        case 'rdv':          echo '<span class="badge-rdv">RDV</span>';          break;
                        case 'repondu':      echo '<span class="badge-rep">Répondu</span>';      break;
                        case 'repondeur':    echo '<span class="badge-rnd">Répondeur</span>';    break;
                        case 'indisponible': echo '<span class="badge-ind">Indisponible</span>'; break;
                        default:             echo e($a['resultat']);
                    endswitch; ?>
                </td>
                <td><?= ($a['resultat'] === 'rdv' && $a['date_rdv']) ? formatDate($a['date_rdv']) : '—' ?></td>
                <td><?= ($a['resultat'] === 'rdv' && $a['motif_rdv']) ? e($a['motif_rdv']) : '—' ?></td>
                <td><?php if ($a['resultat'] === 'rdv' && $a['is_anv']): ?><span class="badge-anv">ANV</span><?php else: ?>—<?php endif; ?></td>
                <td><?= e($a['commentaire']) ?: '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php if ($seance['notes']): ?>
<div class="section">
    <div class="section-title">Notes de séance</div>
    <div style="font-size:13px;white-space:pre-wrap;padding:10px;background:#f8f8f8;border-radius:4px;"><?= e($seance['notes']) ?></div>
</div>
<?php endif; ?>

<div class="footer">
    Portail Conseiller &mdash; Caisse d'Épargne &mdash; rapport généré le <?= $today ?> par <?= e($expediteur) ?>
</div>
</body>
</html>
    <?php
    exit;
}

// ============================================================
// Mode normal (page avec template)
// ============================================================
$pageTitle = 'Rapport séance phoning';
require_once __DIR__ . '/../../templates/header.php';
$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();

if (!$id) {
    // Aucun id : afficher la liste des séances pour en choisir une
    $stmt = $db->prepare("SELECT * FROM seances_phoning WHERE user_id = ? ORDER BY date_ajout DESC");
    $stmt->execute([$userId]);
    $seances = $stmt->fetchAll();
?>
<div class="row g-3 mb-4 no-print">
    <div class="col-12">
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Sélectionnez une séance pour afficher son rapport.</div>
    </div>
</div>
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Choisir une séance</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Date</th><th>Titre</th><th>Appels</th><th>RDV</th><th>ANV</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($seances as $s): ?>
            <tr>
                <td><?= formatDate($s['date_ajout']) ?></td>
                <td><strong><?= e($s['titre'] ?: 'Séance du ' . formatDate($s['date_ajout'])) ?></strong></td>
                <td><?= (int)$s['nombre_appels'] ?></td>
                <td><?= (int)$s['nombre_rdv'] ?></td>
                <td><?= (int)$s['nombre_anv'] ?></td>
                <td>
                    <a href="rapport.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ce"><i class="fas fa-print"></i> Rapport</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($seances)): ?>
            <tr><td colspan="6" class="text-center text-muted">Aucune séance enregistrée.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
    require_once __DIR__ . '/../../templates/footer.php';
    exit;
}

// Charger la séance
$stmt = $db->prepare("SELECT * FROM seances_phoning WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$seance = $stmt->fetch();
if (!$seance) {
    echo '<div class="alert alert-danger">Séance introuvable.</div>';
    require_once __DIR__ . '/../../templates/footer.php';
    exit;
}

// Charger les appels
$stmt = $db->prepare("SELECT * FROM appels_phoning WHERE seance_id = ? ORDER BY created_at ASC");
$stmt->execute([$id]);
$appels = $stmt->fetchAll();

// Calculs stats
$totalAppels  = count($appels);
$repondus     = 0;
$repondeurs   = 0;
$indispos     = 0;
$rdvs         = 0;
$anvs         = 0;
$rdvS = $rdvS1 = $rdvAutres = 0;
$motifCounts  = [];

foreach ($appels as $a) {
    switch ($a['resultat']) {
        case 'repondu':      $repondus++;    break;
        case 'repondeur':    $repondeurs++;  break;
        case 'indisponible': $indispos++;    break;
        case 'rdv':
            $rdvs++;
            if ($a['is_anv']) $anvs++;
            if ($a['motif_rdv']) {
                $motifCounts[$a['motif_rdv']] = ($motifCounts[$a['motif_rdv']] ?? 0) + 1;
            }
            if ($a['date_rdv']) {
                $semRdv     = (int)date('W', strtotime($a['date_rdv']));
                $anneeRdv   = (int)date('Y', strtotime($a['date_rdv']));
                $semCur     = (int)date('W');
                $anneeCur   = (int)date('Y');
                if ($anneeRdv === $anneeCur && $semRdv === $semCur)        $rdvS++;
                elseif ($anneeRdv === $anneeCur && $semRdv === $semCur + 1) $rdvS1++;
                elseif ($anneeRdv === $anneeCur + 1 && $semCur >= 52 && $semRdv === 1) $rdvS1++;
                else $rdvAutres++;
            } else {
                $rdvAutres++;
            }
            break;
    }
}

// Contacts équipe pour la sélection mail
$stmtContacts = $db->query(
    "SELECT id, nom, prenom, email_pro AS email, 'auto' AS source FROM users
        WHERE email_pro IS NOT NULL AND email_pro != ''
     UNION ALL
     SELECT id, nom, prenom, email, 'manual' AS source FROM contacts_equipe
        WHERE email IS NOT NULL AND email != ''
     ORDER BY nom, prenom"
);
$teamContacts = $stmtContacts->fetchAll();

$titrePage  = $seance['titre'] ?: 'Séance du ' . formatDate($seance['date_ajout']);
$expediteur = trim($user['prenom'] . ' ' . $user['nom']);
$today      = date('d/m/Y');
?>

<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .rapport-section { page-break-inside: avoid; }
    .stat-grid-print { display: grid !important; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 20px; }
    .stat-cell { border: 1px solid #ddd; border-radius: 4px; padding: 8px; text-align: center; }
    .stat-cell .num { font-size: 22px; font-weight: bold; color: #CF0A2C; }
    .stat-cell .lbl { font-size: 11px; color: #666; }
    .data-table th { background-color: #CF0A2C !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-rdv    { background: #0d6efd !important; color:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-rep    { background: #198754 !important; color:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-rnd    { background: #ffc107 !important; color:#000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-ind    { background: #6c757d !important; color:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-anv    { background: #0dcaf0 !important; color:#000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rapport-footer { margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
}

/* Styles écran et impression */
.stat-grid-print { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 24px; }
.stat-cell { border: 1px solid #dee2e6; border-radius: 6px; padding: 12px 8px; text-align: center; background: #fff; }
.stat-cell .num { font-size: 24px; font-weight: bold; color: #CF0A2C; }
.stat-cell .lbl { font-size: 12px; color: #666; margin-top: 2px; }
.rapport-title  { color: #CF0A2C; font-size: 1.2rem; font-weight: bold; margin-bottom: 4px; }
.rapport-meta   { font-size: 13px; color: #666; margin-bottom: 20px; }
.rapport-section { margin-bottom: 28px; }
.rapport-section h4 { color: #CF0A2C; border-bottom: 2px solid #CF0A2C; padding-bottom: 6px; margin-bottom: 12px; font-size: 1rem; }
.rapport-footer { margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
.badge-rdv { display:inline-block; background:#0d6efd; color:#fff; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-rep { display:inline-block; background:#198754; color:#fff; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-rnd { display:inline-block; background:#ffc107; color:#000; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-ind { display:inline-block; background:#6c757d; color:#fff; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-anv { display:inline-block; background:#0dcaf0; color:#000; border-radius:4px; padding:2px 8px; font-size:12px; }
.contact-checkbox-list { max-height: 320px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 14px; }
.contact-checkbox-list label { display: flex; align-items: center; gap: 10px; padding: 5px 0; cursor: pointer; font-size: 14px; }
.contact-checkbox-list label:not(:last-child) { border-bottom: 1px solid #f0f0f0; }
</style>

<!-- Barre d'actions (masquée à l'impression) -->
<div class="row g-3 mb-4 no-print">
    <div class="col-auto d-flex align-items-center gap-2">
        <button class="btn btn-ce-outline" onclick="window.open('rapport.php?id=<?= $id ?>&format=print', '_blank')"><i class="fas fa-print"></i> Imprimer</button>
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#shareMailModal"><i class="fas fa-envelope"></i> Envoyer par mail</button>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</div>

<!-- En-tête rapport -->
<div class="rapport-section">
    <div class="rapport-title"><i class="fas fa-phone-volume"></i> <?= e($titrePage) ?></div>
    <div class="rapport-meta">
        <?= e($expediteur) ?> &mdash; <?= formatDate($seance['date_ajout']) ?>
        <?php if ($seance['notes']): ?>
            &mdash; <em><?= e(excerpt($seance['notes'], 120)) ?></em>
        <?php endif; ?>
        &mdash; <span class="text-muted">imprimé le <?= $today ?></span>
    </div>

    <!-- Grille stats -->
    <div class="stat-grid-print">
        <div class="stat-cell">
            <div class="num"><?= $totalAppels ?></div>
            <div class="lbl">Appels</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $repondus ?></div>
            <div class="lbl">Répondus</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $repondeurs ?></div>
            <div class="lbl">Répondeurs</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $indispos ?></div>
            <div class="lbl">Indisponibles</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $rdvs ?></div>
            <div class="lbl">RDV</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $anvs ?></div>
            <div class="lbl">ANV</div>
        </div>
        <div class="stat-cell">
            <div class="num"><?= $rdvS ?> / <?= $rdvS1 ?> / <?= $rdvAutres ?></div>
            <div class="lbl">S / S+1 / Autres</div>
        </div>
    </div>
</div>

<!-- RDV par motif -->
<?php if (!empty($motifCounts)): ?>
<div class="rapport-section">
    <h4><i class="fas fa-tag"></i> Répartition des RDV par motif</h4>
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <?php foreach (array_keys($motifCounts) as $m): ?>
                        <th><?= e($m) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($motifCounts as $n): ?>
                        <td><strong><?= $n ?></strong></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Liste des appels -->
<div class="rapport-section">
    <h4><i class="fas fa-list"></i> Détail des appels (<?= $totalAppels ?>)</h4>
    <?php if (empty($appels)): ?>
        <p class="text-muted fst-italic">Aucun appel enregistré pour cette séance.</p>
    <?php else: ?>
    <div class="data-table-container">
        <table class="data-table" id="tableAppels">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Heure</th>
                    <th>N° / Nom</th>
                    <th>Résultat</th>
                    <th>Date RDV</th>
                    <th>Motif</th>
                    <th>ANV</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($appels as $i => $a): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><?= $a['created_at'] ? date('H:i', strtotime($a['created_at'])) : '—' ?></td>
                    <td><strong><?= e($a['numero_personne']) ?></strong></td>
                    <td>
                        <?php switch ($a['resultat']):
                            case 'rdv':          echo '<span class="badge-rdv">RDV</span>';          break;
                            case 'repondu':      echo '<span class="badge-rep">Répondu</span>';      break;
                            case 'repondeur':    echo '<span class="badge-rnd">Répondeur</span>';    break;
                            case 'indisponible': echo '<span class="badge-ind">Indisponible</span>'; break;
                            default:             echo e($a['resultat']);
                        endswitch; ?>
                    </td>
                    <td><?= ($a['resultat'] === 'rdv' && $a['date_rdv']) ? formatDate($a['date_rdv']) : '—' ?></td>
                    <td><?= ($a['resultat'] === 'rdv' && $a['motif_rdv']) ? e($a['motif_rdv']) : '—' ?></td>
                    <td>
                        <?php if ($a['resultat'] === 'rdv' && $a['is_anv']): ?>
                            <span class="badge-anv">ANV</span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= e($a['commentaire']) ?: '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Notes de séance -->
<?php if ($seance['notes']): ?>
<div class="rapport-section">
    <h4><i class="fas fa-sticky-note"></i> Notes de séance</h4>
    <div class="p-3 bg-light rounded" style="font-size:14px;white-space:pre-wrap;"><?= e($seance['notes']) ?></div>
</div>
<?php endif; ?>

<div class="rapport-footer">
    Portail Conseiller &mdash; Caisse d'Épargne &mdash; rapport généré le <?= $today ?> par <?= e($expediteur) ?>
</div>

<!-- ========== MODAL ENVOI MAIL ========== -->
<div class="modal fade modal-fullscreen-custom" id="shareMailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope"></i> Envoyer le rapport par mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="share_rapport_phoning_mail.php" target="_blank">
                <input type="hidden" name="seance_id" value="<?= $id ?>">
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Sélectionnez les membres de l'équipe à qui envoyer ce rapport.<br>
                        Un fichier <strong>.eml</strong> sera téléchargé, prêt à être ouvert et envoyé depuis Outlook.
                    </p>

                    <?php if (empty($teamContacts)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Aucun contact avec adresse mail disponible.
                            Ajoutez des contacts dans <a href="../contacts/index.php">Contacts utiles</a>.
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">Destinataires</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllContacts(true)">Tout sélectionner</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllContacts(false)">Tout désélectionner</button>
                            </div>
                        </div>
                        <div class="contact-checkbox-list">
                            <?php foreach ($teamContacts as $contact): ?>
                            <label>
                                <input type="checkbox" name="contact_ids[]"
                                       value="<?= (int)$contact['id'] ?>_<?= e($contact['source']) ?>"
                                       class="form-check-input contact-check">
                                <span>
                                    <strong><?= e($contact['nom'] . ' ' . $contact['prenom']) ?></strong>
                                    <span class="text-muted ms-2"><?= e($contact['email']) ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-muted" id="selectedCount" style="font-size:13px;">0 destinataire(s) sélectionné(s)</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <?php if (!empty($teamContacts)): ?>
                    <button type="submit" class="btn btn-ce" id="btnGenererEml" disabled>
                        <i class="fas fa-download"></i> Générer le .eml
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.contact-check').forEach(function(cb) {
    cb.addEventListener('change', updateCount);
});
function updateCount() {
    const n = document.querySelectorAll('.contact-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' destinataire(s) sélectionné(s)';
    document.getElementById('btnGenererEml').disabled = (n === 0);
}
function toggleAllContacts(checked) {
    document.querySelectorAll('.contact-check').forEach(cb => { cb.checked = checked; });
    updateCount();
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
