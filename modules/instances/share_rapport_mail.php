<?php
/**
 * Génère et télécharge un fichier .eml contenant le rapport d'activité
 * (instances en cours + demandes clients en cours).
 * Le fichier est marqué X-Unsent: 1 pour s'ouvrir en mode brouillon dans Outlook.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();

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

// Données du rapport
$stmt = $db->prepare("SELECT * FROM instances WHERE user_id = ? AND statut = 'a_faire' ORDER BY date_echeance ASC");
$stmt->execute([$userId]);
$instances = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM demandes_clients WHERE user_id = ? AND traitee = 0 ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();

$today      = date('d/m/Y');
$expediteur = trim($user['prenom'] . ' ' . $user['nom']);

// ===================== CORPS HTML =====================
$logoUrl = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';

// -- Tableau instances
$instancesRows = '';
if (empty($instances)) {
    $instancesRows = '<tr><td colspan="5" style="text-align:center;color:#888;font-style:italic;padding:12px;">Aucune instance en cours</td></tr>';
} else {
    foreach ($instances as $inst) {
        $echeance     = $inst['date_echeance'] ? htmlspecialchars(formatDate($inst['date_echeance']), ENT_QUOTES) : '—';
        $echeancStyle = '';
        $cls          = getEcheanceClass($inst['date_echeance']);
        if ($cls === 'bg-danger text-white') {
            $echeancStyle = 'background:#dc3545;color:#fff;border-radius:3px;padding:1px 6px;';
        } elseif ($cls === 'bg-warning') {
            $echeancStyle = 'background:#ffc107;color:#000;border-radius:3px;padding:1px 6px;';
        }

        $instancesRows .= '<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars(formatDate($inst['date_ajout']), ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;font-weight:bold;">' . htmlspecialchars($inst['numero_personne'], ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;"><span style="' . $echeancStyle . '">' . $echeance . '</span></td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($inst['categories'], ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . nl2br(htmlspecialchars(excerpt($inst['details'], 120), ENT_QUOTES)) . '</td>
        </tr>';
    }
}

// -- Tableau demandes clients
$demandesRows = '';
if (empty($demandes)) {
    $demandesRows = '<tr><td colspan="5" style="text-align:center;color:#888;font-style:italic;padding:12px;">Aucune demande en cours</td></tr>';
} else {
    foreach ($demandes as $dem) {
        $demandesRows .= '<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars(formatDate($dem['date_ajout']), ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;font-weight:bold;">' . htmlspecialchars($dem['numero_personne'], ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . nl2br(htmlspecialchars(excerpt($dem['details_demande'], 120), ENT_QUOTES)) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($dem['date_envoi'] ? formatDate($dem['date_envoi']) : '—', ENT_QUOTES) . '</td>
            <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($dem['service'], ENT_QUOTES) . '</td>
        </tr>';
    }
}

$nbInstances = count($instances);
$nbDemandes  = count($demandes);

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
              <img src="{$logoUrl}" alt="Caisse d'Épargne" height="36"
                   style="display:block;border:0;height:36px;">
            </td>
          </tr>
          <tr>
            <td style="background-color:#a50823;padding:7px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                Rapport d'activité
              </p>
            </td>
          </tr>

          <!-- Intro -->
          <tr>
            <td style="padding:28px 32px 12px 32px;">
              <h1 style="margin:0 0 6px 0;font-size:18px;color:#1a1a1a;font-weight:bold;">
                Rapport d&rsquo;activit&eacute; &mdash; {$today}
              </h1>
              <p style="margin:0 0 18px 0;font-size:14px;color:#555555;">
                G&eacute;n&eacute;r&eacute; par <strong>{$expediteur}</strong> &mdash;
                <strong>{$nbInstances}</strong> instance(s) en cours,
                <strong>{$nbDemandes}</strong> demande(s) client en cours.
              </p>
            </td>
          </tr>

          <!-- Section instances -->
          <tr>
            <td style="padding:0 32px 24px 32px;">
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border-collapse:collapse;">
                <thead>
                  <tr style="background:#CF0A2C;">
                    <th colspan="5" style="padding:10px 10px;color:#fff;font-size:13px;
                                           text-align:left;letter-spacing:0.04em;">
                      &#x1F4CB;&nbsp; Instances en cours ({$nbInstances})
                    </th>
                  </tr>
                  <tr style="background:#f8f8f8;font-size:12px;color:#666;">
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Date ajout</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">N° Personne / Nom</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Échéance</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Catégorie</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Détails</th>
                  </tr>
                </thead>
                <tbody style="font-size:13px;color:#333;">
                  {$instancesRows}
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Section demandes clients -->
          <tr>
            <td style="padding:0 32px 28px 32px;">
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border-collapse:collapse;">
                <thead>
                  <tr style="background:#CF0A2C;">
                    <th colspan="5" style="padding:10px 10px;color:#fff;font-size:13px;
                                           text-align:left;letter-spacing:0.04em;">
                      &#x1F3A7;&nbsp; Demandes clients en cours ({$nbDemandes})
                    </th>
                  </tr>
                  <tr style="background:#f8f8f8;font-size:12px;color:#666;">
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Date ajout</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">N° Personne</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Détails</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Date envoi</th>
                    <th style="padding:7px 10px;border-bottom:1px solid #ddd;text-align:left;font-weight:600;">Service</th>
                  </tr>
                </thead>
                <tbody style="font-size:13px;color:#333;">
                  {$demandesRows}
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Pied de page -->
          <tr>
            <td style="padding:14px 32px;background:#fafafa;border-top:1px solid #eee;">
              <p style="margin:0;font-size:11px;color:#aaa;text-align:center;line-height:1.6;">
                Portail Conseiller &mdash; Caisse d&apos;&Eacute;pargne<br>
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
$textBody  = "Rapport d'activité — {$today}\r\n";
$textBody .= "Généré par : {$expediteur}\r\n";
$textBody .= str_repeat('=', 60) . "\r\n\r\n";

$textBody .= "INSTANCES EN COURS ({$nbInstances})\r\n";
$textBody .= str_repeat('-', 40) . "\r\n";
if (empty($instances)) {
    $textBody .= "Aucune instance en cours.\r\n";
} else {
    foreach ($instances as $inst) {
        $textBody .= "• " . $inst['numero_personne'];
        if ($inst['date_echeance']) $textBody .= " — Échéance : " . formatDate($inst['date_echeance']);
        if ($inst['categories'])    $textBody .= " — " . $inst['categories'];
        if ($inst['details'])       $textBody .= "\r\n  " . excerpt($inst['details'], 120);
        $textBody .= "\r\n";
    }
}

$textBody .= "\r\nDEMANDES CLIENTS EN COURS ({$nbDemandes})\r\n";
$textBody .= str_repeat('-', 40) . "\r\n";
if (empty($demandes)) {
    $textBody .= "Aucune demande client en cours.\r\n";
} else {
    foreach ($demandes as $dem) {
        $textBody .= "• " . $dem['numero_personne'];
        if ($dem['service'])         $textBody .= " — Service : " . $dem['service'];
        if ($dem['date_envoi'])      $textBody .= " — Envoi : " . formatDate($dem['date_envoi']);
        if ($dem['details_demande']) $textBody .= "\r\n  " . excerpt($dem['details_demande'], 120);
        $textBody .= "\r\n";
    }
}

$textBody .= "\r\n" . str_repeat('-', 60) . "\r\n";
$textBody .= "Portail Conseiller — Caisse d'Épargne\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary    = 'bound_' . md5(uniqid('emlrapport_', true));
$subjectText = "Rapport d'activité du {$today} — {$expediteur}";
$subjectB64  = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

// Construire l'en-tête To: avec tous les destinataires
$toParts = [];
foreach ($recipients as $r) {
    $toParts[] = '=?UTF-8?B?' . base64_encode(trim($r['nom'] . ' ' . $r['prenom'])) . '?= <' . $r['email'] . '>';
}
$toHeader = implode(",\r\n\t", $toParts);

// Nom du fichier avec date
$filename = 'rapport-activite-' . date('Y-m-d') . '.eml';

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

// Partie texte
$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($textBody) . "\r\n";

// Partie HTML
$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($htmlBody) . "\r\n";

$eml .= "--{$boundary}--\r\n";

echo $eml;
exit;
