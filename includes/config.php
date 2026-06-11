<?php
/**
 * Configuration de la base de données et paramètres globaux
 */

date_default_timezone_set('Europe/Paris');

define('DB_HOST', 'localhost');
define('DB_USER', 'cemp_cemp');
define('DB_PASS', '');
define('DB_NAME', 'cemp_cemp');
define('APP_NAME', 'Portail Gestion d\'Activités');
define('APP_VERSION', '1.1');

// Configuration email pour les notifications
define('MAIL_FROM', 'noreply@portail-ce.local');
define('MAIL_FROM_NAME', 'Portail Gestion d\'Activités');
define('APP_URL', 'http://localhost/ce');

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            $pdo->exec("SET time_zone = '+02:00'");
        } catch (PDOException $e) {
            die("Erreur de connexion : " . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
