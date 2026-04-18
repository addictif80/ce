<?php
/**
 * Génère et télécharge un fichier .eml prêt à envoyer dans Outlook.
 * Le fichier est marqué "non envoyé" (X-Unsent: 1) : il s'ouvre en mode rédaction.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db  = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

// Récupérer la procédure (propre ou approuvée)
$stmt = $db->prepare("SELECT * FROM procedures WHERE id = ? AND (user_id = ? OR approved = 1)");
$stmt->execute([$id, $userId]);
$proc = $stmt->fetch();
if (!$proc) { http_response_code(404); exit; }

// URL de partage publique
$scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$basePath = dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$shareUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/view.php?token=' . urlencode($proc['lien_partage']);

$expediteur = trim($user['prenom'] . ' ' . $user['nom']);
$nomProc    = $proc['nom'];

// ===================== CORPS HTML =====================
$logoUrl  = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';
$urlEsc   = htmlspecialchars($shareUrl, ENT_QUOTES);
$nomEsc   = htmlspecialchars($nomProc, ENT_QUOTES);
$expEsc   = htmlspecialchars($expediteur, ENT_QUOTES);

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

          <!-- ── En-tête rouge ── -->
          <tr>
            <td style="background-color:#CF0A2C;padding:22px 32px;">
              <img src="{$logoUrl}"
                   alt="Caisse d'Épargne" height="38"
                   style="display:block;border:0;height:38px;">
            </td>
          </tr>

          <!-- ── Bandeau sous-titre ── -->
          <tr>
            <td style="background-color:#a50823;padding:8px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                Partage de procédure
              </p>
            </td>
          </tr>

          <!-- ── Contenu principal ── -->
          <tr>
            <td style="padding:36px 32px 28px 32px;">

              <h1 style="margin:0 0 6px 0;font-size:20px;color:#1a1a1a;
                          font-weight:bold;line-height:1.3;">
                {$nomEsc}
              </h1>

              <p style="margin:0 0 28px 0;font-size:15px;color:#444444;line-height:1.7;">
                <strong>{$expEsc}</strong> a partagé la procédure
                <strong>&laquo;&nbsp;{$nomEsc}&nbsp;&raquo;</strong>
                avec vous via le Portail Conseiller.
              </p>

              <!-- Bouton CTA -->
              <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:30px;">
                <tr>
                  <td style="background-color:#CF0A2C;border-radius:6px;
                              box-shadow:0 2px 6px rgba(207,10,44,0.35);">
                    <a href="{$urlEsc}"
                       style="display:inline-block;padding:14px 36px;
                              color:#ffffff;text-decoration:none;
                              font-size:15px;font-weight:bold;
                              letter-spacing:0.03em;">
                      Lire la proc&eacute;dure &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Lien de secours -->
              <table cellpadding="0" cellspacing="0" border="0"
                     style="background:#f8f8f8;border-left:3px solid #CF0A2C;
                            border-radius:0 4px 4px 0;padding:12px 16px;width:100%;">
                <tr>
                  <td>
                    <p style="margin:0 0 4px 0;font-size:11px;color:#888888;">
                      Si le bouton ne s&#39;affiche pas, copiez ce lien dans votre navigateur&nbsp;:
                    </p>
                    <a href="{$urlEsc}"
                       style="font-size:12px;color:#CF0A2C;word-break:break-all;">
                      {$urlEsc}
                    </a>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- ── Pied de page ── -->
          <tr>
            <td style="padding:16px 32px;background-color:#fafafa;
                       border-top:1px solid #eeeeee;">
              <p style="margin:0;font-size:11px;color:#aaaaaa;text-align:center;
                        line-height:1.6;">
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
$textBody  = "$expediteur a partagé la procédure « $nomProc » avec vous via le Portail Conseiller.\r\n\r\n";
$textBody .= "Lire la procédure : $shareUrl\r\n\r\n";
$textBody .= "---\r\nPortail Conseiller — Caisse d'Épargne\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary      = 'bound_' . md5(uniqid('emlshare_', true));
$subjectB64    = base64_encode("$expediteur a partagé une procédure avec vous");
$subjectHeader = "=?UTF-8?B?{$subjectB64}?=";

// Envoi des en-têtes HTTP
header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="partage-procedure.eml"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Construction du fichier EML
$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectHeader}\r\n";
$eml .= "X-Unsent: 1\r\n";                  // Outlook : ouvre en mode rédaction
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
