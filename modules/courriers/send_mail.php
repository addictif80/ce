<?php
/**
 * Génère et télécharge un fichier .eml pour envoyer un courrier au client.
 * X-Unsent: 1 → s'ouvre en mode brouillon dans Outlook/Thunderbird.
 * La lettre est jointe en PDF A4 généré par DOMPDF.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

$stmt = $db->prepare("SELECT * FROM courriers WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); exit; }
if (empty($c['email_dest'])) { http_response_code(422); exit; }

// Journaliser l'envoi
$db->exec("CREATE TABLE IF NOT EXISTS courriers_envoi_mail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courrier_id INT NOT NULL,
    user_id INT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(courrier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->prepare("INSERT INTO courriers_envoi_mail (courrier_id, user_id) VALUES (?, ?)")
   ->execute([$id, $userId]);

// ---- Données ----
$conseiller = trim($user['prenom'] . ' ' . $user['nom']);
$logoUrl    = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';

$destName = trim(implode(' ', array_filter([$c['civilite_dest'], $c['prenom_dest'], $c['nom_dest']]))) ?: $c['nom_prenom_dest'];
$civilite = $c['civilite_dest'] ?: '';
$nomDest  = $c['nom_dest'] ?: '';

$months   = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$ts       = strtotime($c['date_courrier'] ?: 'now');
$dateLong = intval(date('j', $ts)) . ' ' . $months[intval(date('n', $ts))] . ' ' . date('Y', $ts);

$salutation = ($civilite && $nomDest)
    ? 'Bonjour ' . htmlspecialchars($civilite, ENT_QUOTES) . ' ' . htmlspecialchars($nomDest, ENT_QUOTES) . ','
    : 'Bonjour,';

$consEsc = htmlspecialchars($conseiller, ENT_QUOTES);

// ===================== CORPS HTML (email d'accompagnement) =====================
$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#f5f5f5;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0"
             style="max-width:600px;width:100%;background-color:#ffffff;
                    border-radius:8px;overflow:hidden;
                    box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <tr>
          <td style="background-color:#CF0A2C;padding:22px 32px;">
            <img src="{$logoUrl}" alt="Caisse d'Épargne" height="38"
                 style="display:block;border:0;height:38px;">
          </td>
        </tr>
        <tr>
          <td style="background-color:#a50823;padding:8px 32px;">
            <p style="margin:0;font-size:11px;color:#f9c9c9;
                      text-transform:uppercase;letter-spacing:0.08em;">
              Courrier disponible
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 32px 28px 32px;">
            <p style="margin:0 0 18px 0;font-size:15px;color:#333333;line-height:1.7;">
              {$salutation}
            </p>
            <p style="margin:0 0 18px 0;font-size:15px;color:#444444;line-height:1.7;">
              Un nouveau courrier vous est adress&eacute; par votre conseiller
              <strong>{$consEsc}</strong>.
              Vous pouvez en prendre connaissance en consultant la pi&egrave;ce
              jointe &agrave; ce pr&eacute;sent mail.
            </p>
            <p style="margin:0 0 6px 0;font-size:15px;color:#333333;line-height:1.7;">
              Bien cordialement,<br>
              <strong>{$consEsc}</strong>
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 32px;background-color:#fafafa;border-top:1px solid #eeeeee;">
            <p style="margin:0;font-size:11px;color:#aaaaaa;text-align:center;line-height:1.6;">
              Caisse d&apos;&Eacute;pargne
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

// ===================== TEXTE FALLBACK =====================
$textBody  = "$salutation\r\n\r\n";
$textBody .= "Un nouveau courrier vous est adressé par votre conseiller $conseiller.\r\n";
$textBody .= "Vous pouvez en prendre connaissance en consultant la pièce jointe à ce présent mail.\r\n\r\n";
$textBody .= "Bien cordialement,\r\n$conseiller\r\n";

// ===================== PIÈCE JOINTE : lettre PDF via DOMPDF =====================
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$destLines  = $h($destName);
if ($c['complement_dest'])       $destLines .= '<br>' . $h($c['complement_dest']);
$destLines .= '<br>' . $h($c['adresse_dest']);
if ($c['complement_adresse_dest']) $destLines .= '<br>' . $h($c['complement_adresse_dest']);
$destLines .= '<br>' . $h($c['cp_ville_dest']);

$telLine   = !empty($user['tel_pro'])   ? $h($user['tel_pro'])   . '<br>' : '';
$emailLine = !empty($user['email_pro']) ? $h($user['email_pro']) . '<br>' : '';

$letterHtml = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    @page { size: A4 portrait; margin: 20mm 20mm 20mm 20mm; }
    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 10pt;
      color: #000;
      line-height: 1.45;
      margin: 0; padding: 0;
    }
    table { border-collapse: collapse; width: 100%; }
    .sender-info { font-size: 8.5pt; line-height: 1.35; color: #333; margin-top: 6mm; }
    .recipient-cell {
      border: 1px dashed #999;
      padding: 6px 10px;
      font-size: 10pt;
      line-height: 1.55;
      vertical-align: top;
    }
    .lieu-date { text-align: right; color: #444; margin-top: 14mm; margin-bottom: 10mm; }
    .objet { font-weight: bold; font-size: 10.5pt; margin-bottom: 6mm; }
    .corps { text-align: justify; line-height: 1.65; }
    .signature { text-align: right; margin-top: 22mm; }
  </style>
</head>
<body>
  <!-- En-tête : expéditeur à gauche, destinataire à droite -->
  <table>
    <tr>
      <td width="48%" style="vertical-align:top; padding-right:6mm;">
        <img src="{$logoUrl}" alt="Caisse d'Épargne" style="height:32px; width:auto;">
        <div class="sender-info">
          {$h($conseiller)}<br>
          5 Avenue Charles de Gaulle<br>
          12700 Capdenac-Gare<br>
          {$telLine}{$emailLine}
        </div>
      </td>
      <td width="4%">&nbsp;</td>
      <td width="48%" style="vertical-align:bottom;">
        <table style="width:100%;">
          <tr>
            <td class="recipient-cell">{$destLines}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Lieu et date -->
  <div class="lieu-date">{$h($c['lieu'] ?: 'Capdenac-Gare')}, le {$dateLong}</div>

  <!-- Objet -->
  <div class="objet">Objet&nbsp;: {$h($c['objet'])}</div>

  <br>

  <!-- Corps -->
  <div class="corps">{$c['corps']}</div>

  <!-- Signature -->
  <div class="signature">{$h($conseiller)}</div>
</body>
</html>
HTML;

// Génération PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($letterHtml, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfContent = $dompdf->output();

// ===================== CONSTRUCTION DU .EML =====================
$boundary1 = 'alt_' . md5(uniqid('', true));
$boundary0 = 'mix_' . md5(uniqid('', true));

$subjectHeader = '=?UTF-8?B?' . base64_encode('[CEMP] Nouveau courrier disponible') . '?=';
$toHeader      = '=?UTF-8?B?' . base64_encode($destName ?: $c['email_dest']) . '?= <' . $c['email_dest'] . '>';

$nomFichier = 'courrier_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $destName) . '_' . date('Ymd', $ts) . '.pdf';
$attachB64  = chunk_split(base64_encode($pdfContent));

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="courrier-client.eml"');
header('Cache-Control: no-cache, must-revalidate');

$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectHeader}\r\n";
$eml .= "To: {$toHeader}\r\n";
$eml .= "X-Unsent: 1\r\n";
$eml .= "Content-Type: multipart/mixed;\r\n\tboundary=\"{$boundary0}\"\r\n\r\n";

// Corps (text + html)
$eml .= "--{$boundary0}\r\n";
$eml .= "Content-Type: multipart/alternative;\r\n\tboundary=\"{$boundary1}\"\r\n\r\n";

$eml .= "--{$boundary1}\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$eml .= quoted_printable_encode($textBody) . "\r\n";

$eml .= "--{$boundary1}\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$eml .= quoted_printable_encode($htmlBody) . "\r\n";

$eml .= "--{$boundary1}--\r\n";

// Pièce jointe PDF
$eml .= "--{$boundary0}\r\n";
$eml .= "Content-Type: application/pdf; name=\"{$nomFichier}\"\r\n";
$eml .= "Content-Transfer-Encoding: base64\r\n";
$eml .= "Content-Disposition: attachment; filename=\"{$nomFichier}\"\r\n\r\n";
$eml .= $attachB64 . "\r\n";

$eml .= "--{$boundary0}--\r\n";

echo $eml;
exit;
