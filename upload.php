<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier reçu']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Erreur de transfert (code ' . $file['error'] . ')']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier trop volumineux (max 10 Mo)']);
    exit;
}

$mimeType = mime_content_type($file['tmp_name']);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
if (!in_array($mimeType, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Type non autorisé. Seuls les images (JPG, PNG, GIF, WEBP) et les PDF sont acceptés.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
if (!in_array($ext, $allowedExts)) {
    http_response_code(400);
    echo json_encode(['error' => 'Extension non autorisée']);
    exit;
}

$uploadsDir = __DIR__ . '/uploads/';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Impossible de créer le dossier uploads']);
        exit;
    }
    // Sécurité : empêcher l'exécution PHP dans uploads/
    file_put_contents($uploadsDir . '.htaccess', "Options -Indexes\nphp_flag engine off\nAddType text/plain .php .php3 .php4 .php5 .phtml\n");
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath = $uploadsDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de sauvegarder le fichier']);
    exit;
}

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/uploads/' . $filename;

echo json_encode([
    'url'  => $url,
    'name' => basename($file['name']),
    'type' => $mimeType,
]);
