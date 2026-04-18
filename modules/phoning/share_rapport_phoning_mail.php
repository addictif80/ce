<?php
/**
 * Génère et télécharge un fichier .eml contenant le rapport d'une séance phoning.
 * Le fichier est marqué X-Unsent: 1 pour s'ouvrir en mode brouillon dans Outlook.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();

// Valider la séance
$seanceId = (int)($_POST['seance_id'] ?? 0);
if (!$seanceId) {
    http_response_code(400);
    echo 'ID de séance manquant.';
    exit;
}

$stmt = $db->prepare("SELECT * FROM seances_phoning WHERE id = ? AND user_id = ?");
$stmt->execute([$seanceId, $userId]);
$seance = $stmt->fetch();
if (!$seance) {
    http_response_code(404);
    echo 'Séance introuvable.';
    exit;
}

// Valider les destinataires sélectionnés
$rawIds = $_POST['contact_ids'] ?? [];
if (empty($rawIds)) {
    http_response_code(400);
    echo 'Aucun destinataire sélectionné.';
    exit;
}

// Résoudre les emails pour chaque entrée "id_source"
$recipients = [];
foreach ($rawIds as $entry) {
    $parts = explode('_', $entry, 2);
    if (count($parts) !== 2) continue;
    [$id, $source] = $parts;
    $id = (int)$id;

    if ($source === 'auto') {
        $stmt = $db->prepare("SELECT nom, prenom, email_pro AS email FROM users WHERE id = ?");
    } else {
        $stmt = $db->prepare("SELECT nom, prenom, email FROM contacts_equipe WHERE id = ?");
    }
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if ($c && !empty($c['email'])) {
        $recipients[] = $c;
    }
}

if (empty($recipients)) {
    http_response_code(422);
    echo 'Aucun e-mail valide trouvé pour les destinataires sélectionnés.';
    exit;
}

// Charger les appels de la séance
$stmt = $db->prepare("SELECT * FROM appels_phoning WHERE seance_id = ? ORDER BY created_at ASC");
$stmt->execute([$seanceId]);
$appels = $stmt->fetchAll();

// Calculs stats
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

$today      = date('d/m/Y');
$expediteur = trim($user['prenom'] . ' ' . $user['nom']);
$titrePage  = $seance['titre'] ?: 'Séance du ' . formatDate($seance['date_ajout']);

// ===================== GRILLE STATS HTML =====================
$statsHtml = '
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;">
  <tr>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $totalAppels . '</div>
      <div style="font-size:11px;color:#666;">Appels</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $repondus . '</div>
      <div style="font-size:11px;color:#666;">Répondus</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $repondeurs . '</div>
      <div style="font-size:11px;color:#666;">Répondeurs</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $indispos . '</div>
      <div style="font-size:11px;color:#666;">Indisponibles</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $rdvs . '</div>
      <div style="font-size:11px;color:#666;">RDV</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:22px;font-weight:bold;color:#CF0A2C;">' . $anvs . '</div>
      <div style="font-size:11px;color:#666;">ANV</div>
    </td>
    <td style="width:1%;"></td>
    <td style="width:14%;text-align:center;padding:10px;border:1px solid #ddd;border-radius:4px;">
      <div style="font-size:18px;font-weight:bold;color:#CF0A2C;">' . $rdvS . '&nbsp;/&nbsp;' . $rdvS1 . '&nbsp;/&nbsp;' . $rdvAutres . '</div>
      <div style="font-size:11px;color:#666;">S / S+1 / Autres</div>
    </td>
  </tr>
</table>';

// ===================== LIGNES APPELS HTML =====================
$appelsRows = '';
if (empty($appels)) {
    $appelsRows = '<tr><td colspan="8" style="text-align:center;color:#888;font-style:italic;padding:12px;">Aucun appel enregistré</td></tr>';
} else {
    foreach ($appels as $i => $a) {
        $resultatHtml = htmlspecialchars($a['resultat'], ENT_QUOTES);
        $resultatStyle = '';
        switch ($a['resultat']) {
            case 'rdv':
                $resultatHtml  = 'RDV';
                $resultatStyle = 'background:#0d6efd;color:#fff;border-radius:3px;padding:1px 6px;font-size:11px;';
                break;
            case 'repondu':
                $resultatHtml  = 'Répondu';
                $resultatStyle = 'background:#198754;color:#fff;border-radius:3px;padding:1px 6px;font-size:11px;';
                break;
            case 'repondeur':
                $resultatHtml  = 'Répondeur';
                $resultatStyle = 'background:#ffc107;color:#000;border-radius:3px;padding:1px 6px;font-size:11px;';
                break;
            case 'indisponible':
                $resultatHtml  = 'Indisponible';
                $resultatStyle = 'background:#6c757d;color:#fff;border-radius:3px;padding:1px 6px;font-size:11px;';
                break;
        }
        $anvHtml = ($a['resultat'] === 'rdv' && $a['is_anv'])
            ? '<span style="background:#0dcaf0;color:#000;border-radius:3px;padding:1px 6px;font-size:11px;">ANV</span>'
            : '—';

        $appelsRows .= '<tr>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;color:#999;">' . ($i + 1) . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;">' . ($a['created_at'] ? date('H:i', strtotime($a['created_at'])) : '—') . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;font-weight:bold;">' . htmlspecialchars($a['numero_personne'], ENT_QUOTES) . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;"><span style="' . $resultatStyle . '">' . $resultatHtml . '</span></td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;">' . (($a['resultat'] === 'rdv' && $a['date_rdv']) ? htmlspecialchars(formatDate($a['date_rdv']), ENT_QUOTES) : '—') . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;">' . (($a['resultat'] === 'rdv' && $a['motif_rdv']) ? htmlspecialchars($a['motif_rdv'], ENT_QUOTES) : '—') . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;">' . $anvHtml . '</td>
            <td style="padding:6px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($a['commentaire'] ?: '—', ENT_QUOTES) . '</td>
        </tr>';
    }
}

// ===================== CORPS HTML =====================
$notesHtml = '';
if ($seance['notes']) {
    $notesHtml = '
          <tr>
            <td style="padding:0 32px 24px 32px;">
              <p style="margin:0 0 8px 0;font-size:13px;font-weight:bold;color:#CF0A2C;">Notes de séance</p>
              <div style="font-size:13px;white-space:pre-wrap;padding:10px;background:#f8f8f8;border-radius:4px;border:1px solid #eee;">'
              . htmlspecialchars($seance['notes'], ENT_QUOTES)
              . '</div>
            </td>
          </tr>';
}

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#f5f5f5;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="700" cellpadding="0" cellspacing="0" border="0"
               style="max-width:700px;width:100%;background:#ffffff;
                      border-radius:8px;overflow:hidden;
                      box-shadow:0 2px 12px rgba(0,0,0,0.08);">

          <!-- En-tête -->
          <tr>
            <td style="background-color:#CF0A2C;padding:20px 32px;">
              <span style="color:#fff;font-size:20px;font-weight:bold;letter-spacing:0.02em;">Caisse d'Épargne</span>
            </td>
          </tr>
          <tr>
            <td style="background-color:#a50823;padding:7px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                Rapport séance phoning
              </p>
            </td>
          </tr>

          <!-- Intro -->
          <tr>
            <td style="padding:28px 32px 12px 32px;">
              <h1 style="margin:0 0 6px 0;font-size:18px;color:#1a1a1a;font-weight:bold;">
                {$titrePage}
              </h1>
              <p style="margin:0 0 18px 0;font-size:14px;color:#555555;">
                G&eacute;n&eacute;r&eacute; par <strong>{$expediteur}</strong> &mdash;
                S&eacute;ance du <strong>{$today}</strong> &mdash;
                <strong>{$totalAppels}</strong> appel(s),
                <strong>{$rdvs}</strong> RDV,
                <strong>{$anvs}</strong> ANV
              </p>
            </td>
          </tr>

          <!-- Grille stats -->
          <tr>
            <td style="padding:0 32px 20px 32px;">
              {$statsHtml}
            </td>
          </tr>

          <!-- Tableau appels -->
          <tr>
            <td style="padding:0 32px 24px 32px;">
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border-collapse:collapse;">
                <thead>
                  <tr style="background:#CF0A2C;">
                    <th colspan="8" style="padding:10px;color:#fff;font-size:13px;
                                           text-align:left;letter-spacing:0.04em;">
                      &#x1F4DE;&nbsp; D&eacute;tail des appels ({$totalAppels})
                    </th>
                  </tr>
                  <tr style="background:#f8f8f8;font-size:12px;color:#666;">
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">#</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Heure</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">N° / Nom</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Résultat</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Date RDV</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Motif</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">ANV</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Commentaire</th>
                  </tr>
                </thead>
                <tbody style="font-size:12px;color:#333;">
                  {$appelsRows}
                </tbody>
              </table>
            </td>
          </tr>

          {$notesHtml}

          <!-- Pied de page -->
          <tr>
            <td style="padding:14px 32px;background:#fafafa;border-top:1px solid #eee;">
              <p style="margin:0;font-size:11px;color:#aaa;text-align:center;line-height:1.6;">
                Portail Conseiller &mdash; Caisse d&#39;&Eacute;pargne<br>
                Ce message a &eacute;t&eacute; g&eacute;n&eacute;r&eacute; automatiquement &mdash; merci de ne pas y r&eacute;pondre.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

// ===================== CORPS TEXTE (fallback) =====================
$textBody  = "Rapport séance phoning — {$titrePage}\r\n";
$textBody .= "Généré par : {$expediteur} — {$today}\r\n";
$textBody .= str_repeat('=', 60) . "\r\n\r\n";

$textBody .= "STATISTIQUES\r\n";
$textBody .= str_repeat('-', 40) . "\r\n";
$textBody .= "Appels : {$totalAppels} | Répondus : {$repondus} | Répondeurs : {$repondeurs} | Indisponibles : {$indispos}\r\n";
$textBody .= "RDV : {$rdvs} | ANV : {$anvs} | S/S+1/Autres : {$rdvS}/{$rdvS1}/{$rdvAutres}\r\n\r\n";

$textBody .= "DÉTAIL DES APPELS ({$totalAppels})\r\n";
$textBody .= str_repeat('-', 40) . "\r\n";
if (empty($appels)) {
    $textBody .= "Aucun appel enregistré.\r\n";
} else {
    foreach ($appels as $i => $a) {
        $textBody .= ($i + 1) . ". " . $a['numero_personne'] . " — " . strtoupper($a['resultat']);
        if ($a['resultat'] === 'rdv') {
            if ($a['date_rdv']) $textBody .= " — RDV le " . formatDate($a['date_rdv']);
            if ($a['motif_rdv']) $textBody .= " — " . $a['motif_rdv'];
            if ($a['is_anv']) $textBody .= " [ANV]";
        }
        if ($a['commentaire']) $textBody .= "\r\n   " . $a['commentaire'];
        $textBody .= "\r\n";
    }
}

if ($seance['notes']) {
    $textBody .= "\r\nNOTES DE SÉANCE\r\n";
    $textBody .= str_repeat('-', 40) . "\r\n";
    $textBody .= $seance['notes'] . "\r\n";
}

$textBody .= "\r\n" . str_repeat('-', 60) . "\r\n";
$textBody .= "Portail Conseiller — Caisse d'Épargne\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary    = 'bound_' . md5(uniqid('emlphoning_', true));
$subjectText = "Rapport phoning — {$titrePage} — {$expediteur}";
$subjectB64  = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

$toParts = [];
foreach ($recipients as $r) {
    $toParts[] = '=?UTF-8?B?' . base64_encode(trim($r['nom'] . ' ' . $r['prenom'])) . '?= <' . $r['email'] . '>';
}
$toHeader = implode(",\r\n\t", $toParts);

$filename = 'rapport-phoning-' . date('Y-m-d') . '.eml';

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectB64}\r\n";
$eml .= "To: {$toHeader}\r\n";
$eml .= "X-Unsent: 1\r\n";
$eml .= "Content-Type: multipart/alternative;\r\n\tboundary=\"{$boundary}\"\r\n";
$eml .= "\r\n";

$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($textBody) . "\r\n";

$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($htmlBody) . "\r\n";

$eml .= "--{$boundary}--\r\n";

echo $eml;
exit;
