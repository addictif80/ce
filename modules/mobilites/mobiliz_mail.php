<?php
/**
 * Génère et télécharge un fichier .eml à destination de contact@servicemobiliz.fr.
 * Le fichier est marqué "non envoyé" (X-Unsent: 1) : il s'ouvre en mode rédaction.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

$stmt = $db->prepare("SELECT * FROM mobilites WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$m = $stmt->fetch();
if (!$m) { http_response_code(404); exit; }

// ---- Identité client et banque ----
$nomClient   = trim($m['nom_client'] ?? '');
$banqueDepart = trim($m['banque_depart'] ?? '');

// ---- Liste des comptes cochés ----
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
$comptesCochesTxt  = [];
$comptesCochesHtml = [];
foreach ($comptesMap as $col => $label) {
    if (!empty($m[$col])) {
        $comptesCochesTxt[]  = "- $label";
        $comptesCochesHtml[] = '<li>' . htmlspecialchars($label, ENT_QUOTES) . '</li>';
    }
}
$listeTxt  = $comptesCochesTxt  ? implode("\r\n", $comptesCochesTxt)  : '(aucun compte sélectionné)';
$listeHtml = $comptesCochesHtml ? '<ul style="margin:8px 0 0 0;padding-left:20px;">' . implode('', $comptesCochesHtml) . '</ul>'
                                 : '<p style="color:#888;font-style:italic;">Aucun compte sélectionné.</p>';

// ---- Rendez-vous ----
$rdvDate   = $m['mobiliz_rdv_date'] ?? '';
$rdvHeure  = $m['mobiliz_rdv_heure'] ?? '';
$rdvHonore = !empty($m['mobiliz_rdv_honore']);

$rdvDateFmt  = $rdvDate  ? date('d/m/Y', strtotime($rdvDate))            : '';
$rdvHeureFmt = $rdvHeure ? str_replace(':', 'h', substr($rdvHeure, 0, 5)) : '';

if ($rdvDateFmt) {
    if ($rdvHonore) {
        $rdvPhraseTxt  = "Le rendez-vous a déjà eu lieu le $rdvDateFmt.";
        $rdvPhraseHtml = "Le rendez-vous <strong>a déjà eu lieu le&nbsp;$rdvDateFmt</strong>.";
    } else {
        $rdvPhraseTxt  = "Le rendez-vous est prévu le $rdvDateFmt" . ($rdvHeureFmt ? " à $rdvHeureFmt" : '') . ".";
        $rdvPhraseHtml = "Le rendez-vous <strong>est prévu le&nbsp;$rdvDateFmt" . ($rdvHeureFmt ? " à&nbsp;$rdvHeureFmt" : '') . "</strong>.";
    }
} else {
    $rdvPhraseTxt  = "Le rendez-vous n'a pas encore de date fixée.";
    $rdvPhraseHtml = "Le rendez-vous <em>n&rsquo;a pas encore de date fix&eacute;e</em>.";
}

// ---- Échappements pour HTML ----
$nomClientEsc    = htmlspecialchars($nomClient,    ENT_QUOTES);
$banqueDepartEsc = htmlspecialchars($banqueDepart, ENT_QUOTES);

// ===================== CORPS TEXTE =====================
$textBody  = "Bonjour,\r\n\r\n";
$textBody .= "$nomClient demande une mobilité depuis $banqueDepart vers la Caisse d'Epargne de Midi Pyrénées.\r\n";
$textBody .= "Les comptes à transférer sont les suivants :\r\n";
$textBody .= "$listeTxt\r\n\r\n";
$textBody .= "$rdvPhraseTxt\r\n\r\n";
$textBody .= "Vous trouverez en pièces jointes le justificatif d'identité ainsi que le RIB de la banque de départ du client.\r\n";

// ===================== CORPS HTML =====================
$logoUrl = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';

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

        <!-- Carte centrale -->
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;
                      border-radius:8px;overflow:hidden;
                      box-shadow:0 2px 12px rgba(0,0,0,0.08);">

          <!-- En-tête rouge -->
          <tr>
            <td style="background-color:#CF0A2C;padding:22px 32px;">
              <img src="{$logoUrl}"
                   alt="Caisse d'Épargne" height="38"
                   style="display:block;border:0;height:38px;">
            </td>
          </tr>

          <!-- Bandeau sous-titre -->
          <tr>
            <td style="background-color:#a50823;padding:8px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                Demande de mobilité bancaire
              </p>
            </td>
          </tr>

          <!-- Contenu principal -->
          <tr>
            <td style="padding:36px 32px 28px 32px;">

              <p style="margin:0 0 20px 0;font-size:15px;color:#333333;line-height:1.7;">
                Bonjour,
              </p>

              <p style="margin:0 0 20px 0;font-size:15px;color:#333333;line-height:1.7;">
                <strong>{$nomClientEsc}</strong> demande une mobilit&eacute; depuis
                <strong>{$banqueDepartEsc}</strong> vers la Caisse d&rsquo;&Eacute;pargne de Midi Pyr&eacute;n&eacute;es.
              </p>

              <!-- Comptes à transférer -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:24px;background:#f8f8f8;
                            border-left:3px solid #CF0A2C;
                            border-radius:0 4px 4px 0;">
                <tr>
                  <td style="padding:14px 16px;">
                    <p style="margin:0 0 8px 0;font-size:12px;color:#888888;
                               text-transform:uppercase;letter-spacing:0.06em;">
                      Comptes &agrave; transf&eacute;rer
                    </p>
                    {$listeHtml}
                  </td>
                </tr>
              </table>

              <!-- Rendez-vous -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:24px;">
                <tr>
                  <td style="padding:14px 16px;background:#ffffff;
                              border:1px solid #e8e8e8;border-radius:4px;">
                    <p style="margin:0;font-size:14px;color:#333333;line-height:1.6;">
                      {$rdvPhraseHtml}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 28px 0;font-size:14px;color:#333333;line-height:1.7;">
                Vous trouverez en pi&egrave;ces jointes le justificatif d&rsquo;identit&eacute; ainsi que le RIB de la banque de d&eacute;part du client.
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

// ===================== CONSTRUCTION DU .EML =====================
$boundary    = 'bound_' . md5(uniqid('emlmobiliz_', true));
$subjectText = "Demande de mobilité de $nomClient";
$subjectHeader = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

$toAddress = 'contact@servicemobiliz.fr';
$toHeader  = $toAddress;

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="demande-mobilite-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $nomClient) . '.eml"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectHeader}\r\n";
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
