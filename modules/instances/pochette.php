<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';

$db     = getDB();
$userId = getCurrentUserId();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

$stmt = $db->prepare("
    SELECT i.*, u.nom AS cons_nom, u.prenom AS cons_prenom
    FROM instances i
    JOIN users u ON i.user_id = u.id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$id, $userId]);
$inst = $stmt->fetch();
if (!$inst) { http_response_code(404); exit; }

$notes      = getNotes('instances', $id);
$conseiller = trim($inst['cons_prenom'] . ' ' . $inst['cons_nom']);
$dateAjout  = date('d/m/Y', strtotime($inst['date_ajout'] . ' UTC'));
$dateEch    = $inst['date_echeance'] ? date('d/m/Y', strtotime($inst['date_echeance'])) : null;
$statut     = $inst['statut'] === 'fait' ? 'Traité' : 'En cours';
$logoUrl    = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pochette instance – <?= e($inst['numero_personne']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        @page { size: A4 landscape; margin: 0; }

        html, body {
            width: 297mm;
            height: 210mm;
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #222;
        }

        .sheet {
            display: flex;
            width: 297mm;
            height: 210mm;
        }

        /* Moitié gauche : vierge */
        .left {
            width: 148.5mm;
            height: 210mm;
            border-right: 0.4mm dashed #bbb;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 4mm;
        }
        .fold-label {
            font-size: 6.5pt;
            color: #bbb;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transform: rotate(-90deg);
            white-space: nowrap;
        }

        /* Moitié droite : pochette */
        .right {
            width: 148.5mm;
            height: 210mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cover-head {
            background: #CF0A2C;
            padding: 5mm 8mm 4mm 8mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 6mm;
        }
        .cover-head img { height: 10mm; display: block; }
        .cover-head-divider { width: 0.3mm; height: 8mm; background: rgba(255,255,255,0.4); }
        .cover-head-label {
            font-size: 7.5pt;
            color: rgba(255,255,255,0.85);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            line-height: 1.3;
        }

        .cover-sub {
            background: #a50823;
            padding: 1.8mm 8mm;
            flex-shrink: 0;
        }
        .cover-sub span {
            font-size: 6.5pt;
            color: #f9c9c9;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .cover-body {
            flex: 1;
            padding: 5mm 8mm 4mm 8mm;
            display: flex;
            flex-direction: column;
            gap: 3.5mm;
            overflow: hidden;
        }

        .client-name {
            font-size: 17pt;
            font-weight: 700;
            color: #111;
            line-height: 1.15;
            border-bottom: 0.3mm solid #e0e0e0;
            padding-bottom: 3mm;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5mm 4mm;
        }
        .info-item { font-size: 8pt; line-height: 1.5; }
        .info-label { color: #999; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.07em; display: block; }
        .info-value { color: #222; font-weight: 600; }

        .statut-badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: 700;
            padding: 1mm 3mm;
            border-radius: 2mm;
            letter-spacing: 0.05em;
        }
        .statut-encours { background: #CF0A2C; color: #fff; }
        .statut-traite  { background: #22a06b; color: #fff; }

        .details-section {
            border-top: 0.3mm solid #e8e8e8;
            padding-top: 3mm;
            flex-shrink: 0;
        }
        .section-title {
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #aaa;
            letter-spacing: 0.1em;
            margin-bottom: 1.5mm;
        }
        .details-text {
            font-size: 7.5pt;
            color: #333;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 28mm;
            overflow: hidden;
        }

        .notes-section {
            border-top: 0.3mm solid #e8e8e8;
            padding-top: 3mm;
            flex: 1;
            overflow: hidden;
        }
        .note-item {
            font-size: 7pt;
            margin-bottom: 2mm;
            padding-bottom: 2mm;
            border-bottom: 0.2mm solid #f0f0f0;
            line-height: 1.4;
        }
        .note-meta { color: #999; margin-bottom: 0.5mm; }
        .note-text { color: #333; }

        .cover-foot {
            background: #CF0A2C;
            padding: 2mm 8mm;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cover-foot span { font-size: 6.5pt; color: rgba(255,255,255,0.75); }

        .print-btn {
            position: fixed;
            bottom: 10mm;
            right: 10mm;
            padding: 3mm 6mm;
            background: #CF0A2C;
            color: #fff;
            border: none;
            border-radius: 2mm;
            font-size: 10pt;
            cursor: pointer;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        @media print {
            .print-btn  { display: none; }
            .fold-label { display: none; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">&#x1F5A8; Imprimer</button>

<div class="sheet">

    <!-- Moitié gauche : vierge -->
    <div class="left">
        <div class="fold-label">pli</div>
    </div>

    <!-- Moitié droite : pochette -->
    <div class="right">

        <div class="cover-head">
            <img src="<?= $logoUrl ?>" alt="Caisse d'Épargne">
            <div class="cover-head-divider"></div>
            <div class="cover-head-label">
                Instances<br>en cours
            </div>
        </div>

        <div class="cover-sub">
            <span><?= $inst['categories'] ? e($inst['categories']) : 'Sans catégorie' ?></span>
        </div>

        <div class="cover-body">

            <div class="client-name"><?= e($inst['numero_personne']) ?></div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Créé le</span>
                    <span class="info-value"><?= $dateAjout ?></span>
                </div>
                <?php if ($dateEch): ?>
                <div class="info-item">
                    <span class="info-label">Échéance</span>
                    <span class="info-value"><?= $dateEch ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <span class="statut-badge <?= $inst['statut'] === 'fait' ? 'statut-traite' : 'statut-encours' ?>"><?= $statut ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Conseiller</span>
                    <span class="info-value"><?= e($conseiller) ?></span>
                </div>
            </div>

            <?php if (!empty($inst['details'])): ?>
            <div class="details-section">
                <div class="section-title">Détails</div>
                <div class="details-text"><?= e($inst['details']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($notes)): ?>
            <div class="notes-section">
                <div class="section-title">Notes (<?= count($notes) ?>)</div>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-meta"><?= e($note['prenom'] . ' ' . $note['nom']) ?> &mdash; <?= date('d/m/Y H:i', strtotime($note['created_at'] . ' UTC')) ?></div>
                        <div class="note-text"><?= e($note['message']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /.cover-body -->

        <div class="cover-foot">
            <span>Caisse d&#39;&Eacute;pargne Midi-Pyr&eacute;n&eacute;es</span>
            <span>Instance n&deg;<?= $id ?> &mdash; <?= $dateAjout ?></span>
        </div>

    </div><!-- /.right -->
</div><!-- /.sheet -->

<script>
window.addEventListener('load', () => window.print());
</script>
</body>
</html>
