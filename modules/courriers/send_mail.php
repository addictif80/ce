<?php
/**
 * Génère et télécharge un fichier .eml pour envoyer un courrier au client.
 * X-Unsent: 1 → s'ouvre en mode brouillon dans Outlook.
 * La lettre est jointe en pièce jointe HTML.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
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

$destName   = trim(implode(' ', array_filter([$c['civilite_dest'], $c['prenom_dest'], $c['nom_dest']]))) ?: $c['nom_prenom_dest'];
$civilite   = $c['civilite_dest'] ?: '';
$nomDest    = $c['nom_dest'] ?: '';

$months = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$ts     = strtotime($c['date_courrier'] ?: 'now');
$dateLong = intval(date('j', $ts)) . ' ' . $months[intval(date('n', $ts))] . ' ' . date('Y', $ts);

// Salutation (Bonjour Madame Dupont ou Bonjour,)
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
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;
                      border-radius:8px;overflow:hidden;
                      box-shadow:0 2px 12px rgba(0,0,0,0.08);">

          <!-- En-tête rouge -->
          <tr>
            <td style="background-color:#CF0A2C;padding:22px 32px;">
              <img src="{$logoUrl}" alt="Caisse d'Épargne" height="38"
                   style="display:block;border:0;height:38px;">
            </td>
          </tr>

          <!-- Bandeau sous-titre -->
          <tr>
            <td style="background-color:#a50823;padding:8px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                Courrier disponible
              </p>
            </td>
          </tr>

          <!-- Contenu principal -->
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

          <!-- Pied de page -->
          <tr>
            <td style="padding:16px 32px;background-color:#fafafa;
                       border-top:1px solid #eeeeee;">
              <p style="margin:0;font-size:11px;color:#aaaaaa;text-align:center;
                        line-height:1.6;">
                Caisse d&apos;&Eacute;pargne
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

// ===================== TEXTE FALLBACK =====================
$textBody  = "$salutation\r\n\r\n";
$textBody .= "Un nouveau courrier vous est adressé par votre conseiller $conseiller.\r\n";
$textBody .= "Vous pouvez en prendre connaissance en consultant la pièce jointe à ce présent mail.\r\n\r\n";
$textBody .= "Bien cordialement,\r\n$conseiller\r\n";

// ===================== PIÈCE JOINTE : lettre HTML =====================
$destAdresse  = htmlspecialchars($c['adresse_dest'] ?? '', ENT_QUOTES);
$destCompAdr  = $c['complement_adresse_dest'] ? '<br>' . htmlspecialchars($c['complement_adresse_dest'], ENT_QUOTES) : '';
$destCpVille  = htmlspecialchars($c['cp_ville_dest'] ?? '', ENT_QUOTES);
$destCompDest = $c['complement_dest'] ? '<br>' . htmlspecialchars($c['complement_dest'], ENT_QUOTES) : '';
$destNameEsc  = htmlspecialchars($destName, ENT_QUOTES);
$lieuEsc      = htmlspecialchars($c['lieu'] ?: 'Capdenac-Gare', ENT_QUOTES);
$objetEsc     = htmlspecialchars($c['objet'] ?? '', ENT_QUOTES);
$conseillEsc  = htmlspecialchars($conseiller, ENT_QUOTES);
$telLine      = !empty($user['tel_pro']) ? htmlspecialchars($user['tel_pro'], ENT_QUOTES) . '<br>' : '';
$emailLine    = !empty($user['email_pro']) ? htmlspecialchars($user['email_pro'], ENT_QUOTES) . '<br>' : '';

$letterHtml = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #000; line-height: 1.4; }
  </style>
</head>
<body>
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="vertical-align:top;width:50%;">
        <img src="{$logoUrl}" alt="Caisse d'Épargne" style="max-width:180px;height:auto;"><br><br>
        <span style="font-size:9pt;line-height:1.4;">
          {$conseillEsc}<br>
          5 Avenue Charles de Gaulle<br>
          12700 Capdenac-Gare<br>
          {$telLine}{$emailLine}
        </span>
      </td>
      <td style="vertical-align:top;text-align:left;width:50%;">
        &nbsp;
      </td>
    </tr>
  </table>

  <div style="margin-left:55%;margin-top:10px;line-height:1.5;
              border:1px dashed #ccc;padding:10px 15px;background:#fafafa;
              display:inline-block;min-width:240px;">
    {$destNameEsc}{$destCompDest}<br>
    {$destAdresse}{$destCompAdr}<br>
    {$destCpVille}
  </div>

  <p style="text-align:right;color:#555;">{$lieuEsc}, le {$dateLong}</p>

  <br>
  <p style="font-weight:bold;">Objet&nbsp;: {$objetEsc}</p>
  <br>
  <div style="text-align:justify;line-height:1.6;">{$c['corps']}</div>
  <br><br>
  <p style="text-align:right;">{$conseillEsc}</p>
</body>
</html>
HTML;

// ===================== CONSTRUCTION DU .EML =====================
$boundary1 = 'alt_' . md5(uniqid('emlcourrier_alt_', true));
$boundary0 = 'mix_' . md5(uniqid('emlcourrier_mix_', true));

$subjectText   = '[CEMP] Nouveau courrier disponible';
$subjectHeader = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

$toName   = $destName ?: $c['email_dest'];
$toHeader = '=?UTF-8?B?' . base64_encode($toName) . '?= <' . $c['email_dest'] . '>';

$nomFichier = 'courrier_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $destName) . '_' . date('Ymd', $ts) . '.html';
$attachB64  = chunk_split(base64_encode($letterHtml));

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="courrier-client.eml"');
header('Cache-Control: no-cache, must-revalidate');

$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectHeader}\r\n";
$eml .= "To: {$toHeader}\r\n";
$eml .= "X-Unsent: 1\r\n";
$eml .= "Content-Type: multipart/mixed;\r\n\tboundary=\"{$boundary0}\"\r\n";
$eml .= "\r\n";

// -- Partie corps (alternative text + html)
$eml .= "--{$boundary0}\r\n";
$eml .= "Content-Type: multipart/alternative;\r\n\tboundary=\"{$boundary1}\"\r\n";
$eml .= "\r\n";

$eml .= "--{$boundary1}\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($textBody) . "\r\n";

$eml .= "--{$boundary1}\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
$eml .= "\r\n";
$eml .= quoted_printable_encode($htmlBody) . "\r\n";

$eml .= "--{$boundary1}--\r\n";

// -- Pièce jointe : la lettre
$eml .= "--{$boundary0}\r\n";
$eml .= "Content-Type: text/html; name=\"{$nomFichier}\"\r\n";
$eml .= "Content-Transfer-Encoding: base64\r\n";
$eml .= "Content-Disposition: attachment; filename=\"{$nomFichier}\"\r\n";
$eml .= "\r\n";
$eml .= $attachB64 . "\r\n";

$eml .= "--{$boundary0}--\r\n";

echo $eml;
exit;
