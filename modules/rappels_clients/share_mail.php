<?php
/**
 * Génère et télécharge un fichier .eml prêt à envoyer dans Outlook.
 * Le fichier est marqué "non envoyé" (X-Unsent: 1) : il s'ouvre en mode rédaction.
 * Le champ To: contient l'email du conseiller concerné.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();
$id     = (int)($_GET['id'] ?? 0);

if (!$id) { http_response_code(400); exit; }

// Récupérer la demande (propre à l'utilisateur)
$stmt = $db->prepare("SELECT d.*, c.prenom AS cons_prenom, c.nom AS cons_nom, c.email AS cons_email
    FROM demandes_rappel_client d
    LEFT JOIN contacts_equipe c ON d.conseiller_id = c.id
    WHERE d.id = ? AND d.user_id = ?");
$stmt->execute([$id, $userId]);
$demande = $stmt->fetch();
if (!$demande) { http_response_code(404); exit; }
if (!$demande['cons_email']) { http_response_code(422); exit; }

// Générer un token si la demande n'en a pas encore
if (empty($demande['token'])) {
    $token = bin2hex(random_bytes(32));
    $db->prepare("UPDATE demandes_rappel_client SET token = ? WHERE id = ?")->execute([$token, $id]);
    $demande['token'] = $token;
}

// URL du bouton "Marquer comme traité"
$traiterUrl = rtrim(APP_URL, '/') . '/modules/rappels_clients/traiter.php?token=' . urlencode($demande['token']);

$expediteur    = trim($user['prenom'] . ' ' . $user['nom']);
$conseillerNom = trim($demande['cons_prenom'] . ' ' . $demande['cons_nom']);
$conseillerTo  = trim($demande['cons_nom'] . ' ' . $demande['cons_prenom']) . ' <' . $demande['cons_email'] . '>';
$clientIdent   = $demande['identite_client'];
$motif         = $demande['motif'] ?? '';

// ===================== CORPS HTML =====================
$logoUrl       = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';
$clientEsc     = htmlspecialchars($clientIdent, ENT_QUOTES);
$motifEsc      = nl2br(htmlspecialchars($motif, ENT_QUOTES));
$expEsc        = htmlspecialchars($expediteur, ENT_QUOTES);
$consEsc       = htmlspecialchars($conseillerNom, ENT_QUOTES);

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
                Demande de rappel client
              </p>
            </td>
          </tr>

          <!-- ── Contenu principal ── -->
          <tr>
            <td style="padding:36px 32px 28px 32px;">

              <h1 style="margin:0 0 6px 0;font-size:20px;color:#1a1a1a;
                          font-weight:bold;line-height:1.3;">
                Rappel demandé : {$clientEsc}
              </h1>

              <p style="margin:0 0 20px 0;font-size:15px;color:#444444;line-height:1.7;">
                <strong>{$expEsc}</strong> vous transmet une demande de rappel
                d&rsquo;un client vous concernant.
              </p>

              <!-- Bloc client -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:24px;background:#f8f8f8;
                            border-left:3px solid #CF0A2C;
                            border-radius:0 4px 4px 0;">
                <tr>
                  <td style="padding:14px 16px;">
                    <p style="margin:0 0 8px 0;font-size:12px;color:#888888;
                               text-transform:uppercase;letter-spacing:0.06em;">
                      Client
                    </p>
                    <p style="margin:0;font-size:16px;color:#1a1a1a;font-weight:bold;">
                      {$clientEsc}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Motif -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:28px;">
                <tr>
                  <td style="padding:14px 16px;background:#ffffff;
                              border:1px solid #e8e8e8;border-radius:4px;">
                    <p style="margin:0 0 6px 0;font-size:12px;color:#888888;
                               text-transform:uppercase;letter-spacing:0.06em;">
                      Motif de la demande
                    </p>
                    <p style="margin:0;font-size:14px;color:#333333;line-height:1.6;">
                      {$motifEsc}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Bouton Marquer comme traité -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    <a href="{$traiterUrl}"
                       style="display:inline-block;padding:13px 28px;
                              background-color:#28A745;color:#ffffff;
                              font-size:15px;font-weight:bold;text-decoration:none;
                              border-radius:5px;letter-spacing:0.02em;">
                      &#10003;&nbsp; Marquer comme trait&eacute;e
                    </a>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-top:8px;">
                    <p style="margin:0;font-size:11px;color:#aaaaaa;">
                      Ou copiez ce lien&nbsp;: <span style="color:#555555;">{$traiterUrl}</span>
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#666666;font-style:italic;">
                Ce message a &eacute;t&eacute; g&eacute;n&eacute;r&eacute; depuis le Portail Conseiller par <strong>{$expEsc}</strong>.
              </p>

            </td>
          </tr>

          <!-- ── Pied de page ── -->
          <tr>
            <td style="padding:16px 32px;background-color:#fafafa;
                       border-top:1px solid #eeeeee;">
              <p style="margin:0;font-size:11px;color:#aaaaaa;text-align:center;
                        line-height:1.6;">
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
$textBody  = "Demande de rappel client\r\n";
$textBody .= "========================\r\n\r\n";
$textBody .= "De la part de : $expediteur\r\n";
$textBody .= "Client : $clientIdent\r\n\r\n";
$textBody .= "Motif :\r\n$motif\r\n\r\n";
$textBody .= ">>> Marquer comme traitée : $traiterUrl\r\n\r\n";
$textBody .= "---\r\nPortail Conseiller — Caisse d'Épargne\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary      = 'bound_' . md5(uniqid('emlrappel_', true));
$subjectText   = "$expediteur vous transmet une demande de rappel : $clientIdent";
$subjectB64    = base64_encode($subjectText);
$subjectHeader = "=?UTF-8?B?{$subjectB64}?=";

// Envoi des en-têtes HTTP
header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="demande-rappel-client.eml"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Construction du fichier EML
$toHeader = '=?UTF-8?B?' . base64_encode($conseillerNom) . '?= <' . $demande['cons_email'] . '>';

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
