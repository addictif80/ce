<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';

$db     = getDB();
$userId = getCurrentUserId();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

$stmt = $db->prepare("
    SELECT m.*, u.nom AS cons_nom, u.prenom AS cons_prenom
    FROM mobilites m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ? AND m.user_id = ?
");
$stmt->execute([$id, $userId]);
$m = $stmt->fetch();
if (!$m) { http_response_code(404); exit; }

$etapeLabels = [
    'rdv'       => '1. RDV',
    'synthese'  => '2. Synthèse',
    'ouverture' => '3. Ouverture',
    'mobilite'  => '4. Mobilité',
    'termine'   => 'Terminé',
];
$etapeLabel = $etapeLabels[$m['etape']] ?? $m['etape'];

$typeLabel  = $m['is_ce_hors_mp'] ? 'CE hors Midi-Pyrénées (Mobiliz)' : 'Standard';
$dateOuvert = date('d/m/Y', strtotime($m['date_ajout']));
$conseiller = trim($m['cons_prenom'] . ' ' . $m['cons_nom']);

// Documents
$docs = [
    'doc_carte_identite'   => "Carte d'identité",
    'doc_justif_domicile'  => 'Justificatif de domicile',
    'doc_avis_imposition'  => "Avis d'imposition",
    'doc_releves_externes' => 'Relevés comptes externes',
    'doc_rib'              => 'RIB',
];

// Comptes Mobiliz cochés
$comptesMap = [
    'mobiliz_compte_cdd'            => 'CDD',
    'mobiliz_compte_livret_a'       => 'Livret A',
    'mobiliz_compte_livret_b'       => 'Livret B',
    'mobiliz_compte_lep'            => 'LEP',
    'mobiliz_compte_ldds'           => 'LDDS',
    'mobiliz_compte_assurance_vie'  => 'Assurance vie',
    'mobiliz_compte_pea'            => 'PEA',
    'mobiliz_compte_parts_sociales' => 'Parts sociales',
];
$comptesCoches = array_values(array_filter($comptesMap, fn($_, $col) => !empty($m[$col]), ARRAY_FILTER_USE_BOTH));

$logoUrl = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pochette – <?= e($m['nom_client']) ?></title>
    <style>
        /* ── Réinitialisation ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Page A4 paysage ── */
        @page { size: A4 landscape; margin: 0; }

        html, body {
            width: 297mm;
            height: 210mm;
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #222;
        }

        /* ── Layout deux colonnes ── */
        .sheet {
            display: flex;
            width: 297mm;
            height: 210mm;
        }

        /* Moitié gauche : vierge (verso intérieur) */
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

        /* ── En-tête rouge ── */
        .cover-head {
            background: #CF0A2C;
            padding: 5mm 8mm 4mm 8mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 6mm;
        }
        .cover-head img {
            height: 10mm;
            display: block;
        }
        .cover-head-divider {
            width: 0.3mm;
            height: 8mm;
            background: rgba(255,255,255,0.4);
        }
        .cover-head-label {
            font-size: 7.5pt;
            color: rgba(255,255,255,0.85);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            line-height: 1.3;
        }

        /* ── Bandeau type dossier ── */
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

        /* ── Corps ── */
        .cover-body {
            flex: 1;
            padding: 5mm 8mm 4mm 8mm;
            display: flex;
            flex-direction: column;
            gap: 4mm;
        }

        /* Nom client */
        .client-name {
            font-size: 18pt;
            font-weight: 700;
            color: #111;
            line-height: 1.15;
            border-bottom: 0.3mm solid #e0e0e0;
            padding-bottom: 3mm;
        }
        .client-numero {
            font-size: 8pt;
            color: #888;
            margin-top: 1mm;
            letter-spacing: 0.04em;
        }

        /* Grille infos */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5mm 4mm;
        }
        .info-item { font-size: 8pt; line-height: 1.5; }
        .info-label { color: #999; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.07em; display: block; }
        .info-value { color: #222; font-weight: 600; }

        /* Badge étape */
        .etape-badge {
            display: inline-block;
            background: #CF0A2C;
            color: #fff;
            font-size: 7pt;
            font-weight: 700;
            padding: 1mm 3mm;
            border-radius: 2mm;
            letter-spacing: 0.05em;
        }

        /* Docs */
        .docs-section {
            border-top: 0.3mm solid #e8e8e8;
            padding-top: 3mm;
        }
        .docs-title {
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #aaa;
            letter-spacing: 0.1em;
            margin-bottom: 2mm;
        }
        .docs-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5mm 4mm;
        }
        .doc-item {
            font-size: 7.5pt;
            display: flex;
            align-items: center;
            gap: 1.5mm;
        }
        .doc-item .ic {
            width: 3mm;
            height: 3mm;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .ic-ok  { background: #22a06b; }
        .ic-nok { background: #e0e0e0; border: 0.3mm solid #bbb; }

        /* Comptes Mobiliz */
        .mobiliz-pill {
            background: #fff3cd;
            border: 0.3mm solid #ffc107;
            color: #6d4a00;
            font-size: 7pt;
            padding: 0.8mm 2.5mm;
            border-radius: 2mm;
            display: inline-block;
        }

        /* ── Pied de page ── */
        .cover-foot {
            background: #CF0A2C;
            padding: 2mm 8mm;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cover-foot span {
            font-size: 6.5pt;
            color: rgba(255,255,255,0.75);
        }

        /* ── Bouton impression (masqué à l'impression) ── */
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

<button class="print-btn" onclick="window.print()">
    &#x1F5A8; Imprimer
</button>

<div class="sheet">

    <!-- Moitié gauche : vierge -->
    <div class="left">
        <div class="fold-label">pli</div>
    </div>

    <!-- Moitié droite : pochette -->
    <div class="right">

        <!-- En-tête -->
        <div class="cover-head">
            <img src="<?= $logoUrl ?>" alt="Caisse d'Épargne">
            <div class="cover-head-divider"></div>
            <div class="cover-head-label">
                Mobilité<br>bancaire
            </div>
        </div>

        <!-- Sous-titre type -->
        <div class="cover-sub">
            <span><?= e($typeLabel) ?></span>
        </div>

        <!-- Corps -->
        <div class="cover-body">

            <!-- Nom client -->
            <div>
                <div class="client-name"><?= e($m['nom_client']) ?></div>
                <?php if ($m['numero_personne']): ?>
                    <div class="client-numero">N° personne : <?= e($m['numero_personne']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Infos -->
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Banque de départ</span>
                    <span class="info-value"><?= $m['banque_depart'] ? e($m['banque_depart']) : '—' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dossier ouvert le</span>
                    <span class="info-value"><?= $dateOuvert ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Étape</span>
                    <span class="etape-badge"><?= e($etapeLabel) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Conseiller</span>
                    <span class="info-value"><?= e($conseiller) ?></span>
                </div>
                <?php if ($m['type_compte']): ?>
                <div class="info-item">
                    <span class="info-label">Type de compte</span>
                    <span class="info-value"><?= e($m['type_compte']) ?><?= $m['compte_joint'] ? ' — Joint' : '' ?></span>
                </div>
                <?php endif; ?>
                <?php if ($m['mandat_signe']): ?>
                <div class="info-item">
                    <span class="info-label">Mandat</span>
                    <span class="info-value" style="color:#22a06b">&#10003; Signé</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Documents RDV -->
            <div class="docs-section">
                <div class="docs-title">Documents</div>
                <div class="docs-list">
                    <?php foreach ($docs as $col => $label): ?>
                        <div class="doc-item">
                            <div class="ic <?= $m[$col] ? 'ic-ok' : 'ic-nok' ?>"></div>
                            <span style="color:<?= $m[$col] ? '#333' : '#aaa' ?>"><?= e($label) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comptes Mobiliz (si CE hors MP) -->
            <?php if ($m['is_ce_hors_mp'] && !empty($comptesCoches)): ?>
            <div>
                <div class="docs-title">Comptes à transférer</div>
                <div style="display:flex;flex-wrap:wrap;gap:1.5mm;">
                    <?php foreach ($comptesCoches as $c): ?>
                        <span class="mobiliz-pill"><?= e($c) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.cover-body -->

        <!-- Pied de page -->
        <div class="cover-foot">
            <span>Caisse d'Épargne Midi-Pyrénées</span>
            <span>Dossier n°<?= $id ?> &mdash; <?= $dateOuvert ?></span>
        </div>

    </div><!-- /.right -->
</div><!-- /.sheet -->

<script>
// Impression automatique à l'ouverture
window.addEventListener('load', () => window.print());
</script>
</body>
</html>
