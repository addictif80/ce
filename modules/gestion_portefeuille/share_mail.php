<?php
/**
 * Génère et télécharge un fichier .eml prêt à envoyer dans Outlook.
 * Le fichier est marqué "non envoyé" (X-Unsent: 1) : il s'ouvre en mode rédaction.
 * Le champ To: contient l'email du destinataire concerné.
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
    FROM demandes_portefeuille d
    LEFT JOIN contacts_equipe c ON d.conseiller_id = c.id
    WHERE d.id = ? AND d.user_id = ?");
$stmt->execute([$id, $userId]);
$demande = $stmt->fetch();
if (!$demande) { http_response_code(404); exit; }
if (!$demande['cons_email']) { http_response_code(422); exit; }

$stmtL = $db->prepare("SELECT * FROM demandes_portefeuille_lignes WHERE demande_id = ? ORDER BY id");
$stmtL->execute([$id]);
$lignesListe = $stmtL->fetchAll();
if (!$lignesListe) { http_response_code(422); exit; }

// Générer un token si la demande n'en a pas encore
if (empty($demande['token'])) {
    $token = bin2hex(random_bytes(32));
    $db->prepare("UPDATE demandes_portefeuille SET token = ? WHERE id = ?")->execute([$token, $id]);
    $demande['token'] = $token;
}

// URL du bouton "Consulter la demande"
$consulterUrl = rtrim(APP_URL, '/') . '/modules/gestion_portefeuille/traiter.php?token=' . urlencode($demande['token']);

$expediteur    = trim($user['prenom'] . ' ' . $user['nom']);
$conseillerNom = trim($demande['cons_prenom'] . ' ' . $demande['cons_nom']);

$nbAttribution = count(array_filter($lignesListe, fn($l) => $l['type_demande'] === 'attribution'));
$nbSuppression = count(array_filter($lignesListe, fn($l) => $l['type_demande'] === 'suppression'));

// ===================== CORPS HTML =====================
$logoUrl  = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';
$expEsc   = htmlspecialchars($expediteur, ENT_QUOTES);
$consEsc  = htmlspecialchars($conseillerNom, ENT_QUOTES);

$lignesHtml = '';
foreach ($lignesListe as $l) {
    $typeLabel = $l['type_demande'] === 'suppression' ? 'Suppression' : 'Attribution';
    $typeColor = $l['type_demande'] === 'suppression' ? '#CF0A2C' : '#28A745';
    $numEsc    = htmlspecialchars($l['numero_personne'], ENT_QUOTES);
    $identEsc  = htmlspecialchars($l['identite_client'], ENT_QUOTES);
    $motifEsc  = nl2br(htmlspecialchars($l['motif'] ?? '', ENT_QUOTES));
    $lignesHtml .= <<<HTML
                <tr>
                  <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;color:#333;">{$identEsc}<br><span style="color:#999;font-size:11px;">N° {$numEsc}</span></td>
                  <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:12px;">
                    <span style="display:inline-block;padding:2px 10px;border-radius:12px;background:{$typeColor};color:#fff;font-weight:bold;">{$typeLabel}</span>
                  </td>
                  <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;color:#555;">{$motifEsc}</td>
                </tr>
HTML;
}

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
        <table width="640" cellpadding="0" cellspacing="0" border="0"
               style="max-width:640px;width:100%;background-color:#ffffff;
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
                Demande de gestion de portefeuille
              </p>
            </td>
          </tr>

          <!-- ── Contenu principal ── -->
          <tr>
            <td style="padding:36px 32px 28px 32px;">

              <h1 style="margin:0 0 6px 0;font-size:20px;color:#1a1a1a;
                          font-weight:bold;line-height:1.3;">
                Demande de suppression ou d'attribution de clients
              </h1>

              <p style="margin:0 0 20px 0;font-size:15px;color:#444444;line-height:1.7;">
                <strong>{$expEsc}</strong> vous transmet une demande de gestion de portefeuille
                concernant {$nbAttribution} client(s) à attribuer et {$nbSuppression} client(s) à supprimer.
              </p>

              <!-- Tableau des clients -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:24px;border:1px solid #e8e8e8;border-radius:4px;overflow:hidden;">
                <thead>
                  <tr>
                    <th align="left" style="padding:10px 12px;background:#f8f8f8;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Client</th>
                    <th align="left" style="padding:10px 12px;background:#f8f8f8;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Type</th>
                    <th align="left" style="padding:10px 12px;background:#f8f8f8;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.06em;">Motif</th>
                  </tr>
                </thead>
                <tbody>
{$lignesHtml}
                </tbody>
              </table>

              <!-- Bouton Consulter la demande -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    <a href="{$consulterUrl}"
                       style="display:inline-block;padding:13px 28px;
                              background-color:#28A745;color:#ffffff;
                              font-size:15px;font-weight:bold;text-decoration:none;
                              border-radius:5px;letter-spacing:0.02em;">
                      &#128269;&nbsp; Consulter la demande
                    </a>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-top:8px;">
                    <p style="margin:0;font-size:11px;color:#aaaaaa;">
                      Ou copiez ce lien&nbsp;: <span style="color:#555555;">{$consulterUrl}</span>
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
$textBody  = "Demande de gestion de portefeuille\r\n";
$textBody .= "===================================\r\n\r\n";
$textBody .= "De la part de : $expediteur\r\n";
$textBody .= "Destinataire : $conseillerNom\r\n\r\n";
$textBody .= "Clients concernés ($nbAttribution attribution(s), $nbSuppression suppression(s)) :\r\n";
foreach ($lignesListe as $l) {
    $typeLabel = $l['type_demande'] === 'suppression' ? 'Suppression' : 'Attribution';
    $textBody .= "- {$l['identite_client']} (N° {$l['numero_personne']}) — $typeLabel";
    if (!empty($l['motif'])) {
        $textBody .= " — {$l['motif']}";
    }
    $textBody .= "\r\n";
}
$textBody .= "\r\n>>> Consulter la demande : $consulterUrl\r\n\r\n";
$textBody .= "---\r\nPortail Conseiller — Caisse d'Épargne\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary      = 'bound_' . md5(uniqid('emlportefeuille_', true));
$subjectText   = "$expediteur vous transmet une demande de gestion de portefeuille";
$subjectB64    = base64_encode($subjectText);
$subjectHeader = "=?UTF-8?B?{$subjectB64}?=";

// Envoi des en-têtes HTTP
header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="demande-gestion-portefeuille.eml"');
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
// Encodage base64 (plutôt que quoted-printable) pour éviter que les longues URLs
// (lien "Consulter la demande") ne soient coupées par un retour à la ligne "souple"
// mal réassemblé par certains clients mails.
$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: base64\r\n";
$eml .= "\r\n";
$eml .= chunk_split(base64_encode($textBody));

// Partie HTML
$eml .= "--{$boundary}\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: base64\r\n";
$eml .= "\r\n";
$eml .= chunk_split(base64_encode($htmlBody));

$eml .= "--{$boundary}--\r\n";

echo $eml;
exit;
