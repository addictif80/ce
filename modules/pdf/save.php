<?php
// Enregistre un PDF produit dans le navigateur dans les documents de l'utilisateur connecté.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/lib.php';
requireLogin();
header('Content-Type: application/json');
$fail = function ($m, $code = 400) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $m]); exit; };

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) $fail('Aucun fichier reçu.');
$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) $fail('Erreur de transfert.');
if ($f['size'] > PDF_MAX_BYTES) $fail('Fichier trop volumineux (' . (PDF_MAX_BYTES / 1048576) . ' Mo maximum).');
if (file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-') $fail('Le fichier n\'est pas un PDF.');

$userId = getCurrentUserId();
pdfEnsureSchema();
$db = getDB();
$st = $db->prepare("SELECT COUNT(*) FROM pdf_fichiers WHERE user_id = ?");
$st->execute([$userId]);
if ((int)$st->fetchColumn() >= PDF_MAX_FILES) $fail('Limite de ' . PDF_MAX_FILES . ' documents atteinte : supprimez-en avant d\'en ajouter.');

$nom = pdfCleanName($_POST['nom'] ?? $f['name']);
$dir = pdfUserDir($userId);
$stock = bin2hex(random_bytes(16)) . '.pdf';
if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stock)) $fail('Enregistrement impossible.', 500);
$db->prepare("INSERT INTO pdf_fichiers (user_id, nom, fichier, taille) VALUES (?, ?, ?, ?)")->execute([$userId, $nom, $stock, (int)$f['size']]);
echo json_encode(['ok' => true]);
