<?php
/**
 * Génère et télécharge un fichier .eml « Demande d'accès au portail d'activité » (même principe que les autres modules :
 * en-têtes message/rfc822 + attachment, X-Unsent: 1 pour s'ouvrir en brouillon dans Outlook).
 * Les informations saisies servent uniquement à fabriquer le fichier : rien n'est enregistré.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/agences.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
requireToolsAccess(); // même protection que la page /tools (code d'accès éventuel)

$cta = getAccessCtaSettings();
if (!$cta['enabled'] || $cta['email'] === '') { http_response_code(404); exit; }

// Champs : une ligne, longueur bornée
$clean = fn($k, $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST[$k] ?? ''))), 0, $max);
$nom = $clean('nom', 100); $prenom = $clean('prenom', 100); $tel = $clean('telephone', 30);
$interne = $clean('numero_interne', 30); $email = $clean('email', 150); $agence = $clean('agence', 100);

$valid = $nom !== '' && $prenom !== '' && $tel !== '' && $interne !== '' && $agence !== ''
    && filter_var($email, FILTER_VALIDATE_EMAIL) && in_array($agence, getAgences(), true);
if (!$valid) { http_response_code(422); header('Content-Type: text/plain; charset=utf-8'); exit('Formulaire incomplet ou invalide.'); }

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$identite = "$prenom $nom";

// ===================== CORPS HTML =====================
$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">
<p>Bonjour,</p>
<p>Je souhaite obtenir un compte sur le portail d'activité. Voici mes informations :</p>
<table style="border-collapse:collapse;">
<tr><td style="padding:4px 14px 4px 0;color:#666;">Nom</td><td><strong>{$h($nom)}</strong></td></tr>
<tr><td style="padding:4px 14px 4px 0;color:#666;">Prénom</td><td><strong>{$h($prenom)}</strong></td></tr>
<tr><td style="padding:4px 14px 4px 0;color:#666;">Téléphone</td><td>{$h($tel)}</td></tr>
<tr><td style="padding:4px 14px 4px 0;color:#666;">Numéro interne</td><td>{$h($interne)}</td></tr>
<tr><td style="padding:4px 14px 4px 0;color:#666;">Adresse e-mail</td><td>{$h($email)}</td></tr>
<tr><td style="padding:4px 14px 4px 0;color:#666;">Agence de rattachement</td><td>{$h($agence)}</td></tr>
</table>
<p>Cordialement,<br>{$h($identite)}</p>
</body></html>
HTML;

// ===================== CORPS TEXTE (fallback) =====================
$textBody  = "Bonjour,\r\n\r\n";
$textBody .= "Je souhaite obtenir un compte sur le portail d'activité. Voici mes informations :\r\n\r\n";
$textBody .= "Nom : $nom\r\nPrénom : $prenom\r\nTéléphone : $tel\r\nNuméro interne : $interne\r\n";
$textBody .= "Adresse e-mail : $email\r\nAgence de rattachement : $agence\r\n\r\n";
$textBody .= "Cordialement,\r\n$identite\r\n";

// ===================== CONSTRUCTION DU .EML =====================
$boundary      = 'bound_' . md5(uniqid('emlacces_', true));
$subjectHeader = '=?UTF-8?B?' . base64_encode("Demande d'accès au portail d'activité") . '?=';

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="demande-acces-portail.eml"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$eml  = "MIME-Version: 1.0\r\n";
$eml .= "Subject: {$subjectHeader}\r\n";
$eml .= "To: {$cta['email']}\r\n";
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
