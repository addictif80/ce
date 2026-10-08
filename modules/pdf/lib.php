<?php
// Stockage des PDF enregistrés par les utilisateurs (hors de portée d'un accès direct : téléchargement via index.php).
const PDF_MAX_BYTES = 25 * 1048576;
const PDF_MAX_FILES = 100;

function pdfEnsureSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS pdf_fichiers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nom VARCHAR(150) NOT NULL,
        fichier VARCHAR(64) NOT NULL,
        taille INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pdfBaseDir() {
    $dir = __DIR__ . '/../../uploads/pdf_outils';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\nOptions -Indexes\nphp_flag engine off\n");
    }
    return $dir;
}
function pdfUserDir($userId) {
    $dir = pdfBaseDir() . '/' . (int)$userId;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}
function pdfCleanName($n) {
    $n = preg_replace('/[^\p{L}\p{N} ._()\-]/u', '_', basename((string)$n));
    $n = trim(mb_substr($n, 0, 120));
    if ($n === '' || $n[0] === '.') $n = 'document';
    return preg_match('/\.pdf$/i', $n) ? $n : $n . '.pdf';
}
