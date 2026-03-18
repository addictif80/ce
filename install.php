<?php
/**
 * Script d'installation - Portail Gestion d'Activités
 * Crée la base de données et l'utilisateur admin
 */

require_once __DIR__ . '/includes/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

// Tenter la connexion
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h2>Erreur de connexion à la base de données</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Lire et exécuter le SQL
        $sql = file_get_contents(__DIR__ . '/install.sql');
        $pdo->exec($sql);

        // Créer l'utilisateur admin
        $adminPassword = password_hash('70748483Arifa80=', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'adrien'");
        $stmt->execute([$adminPassword]);

        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nom, prenom, is_admin) VALUES ('adrien', ?, 'Admin', 'Adrien', 1)");
            $stmt->execute([$adminPassword]);
        }

        $message = "Installation réussie ! La base de données a été créée avec succès.";
    } catch (PDOException $e) {
        $error = "Erreur lors de l'installation : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Portail Gestion d'Activités</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); max-width: 500px; width: 100%; text-align: center; }
        h1 { color: #e4002b; }
        .btn { background: #e4002b; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #c40025; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
        a { color: #e4002b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation</h1>
        <p>Portail de Gestion d'Activités</p>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
            <p><a href="index.php">Accéder au portail</a></p>
        <?php elseif ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
            <form method="POST"><button type="submit" class="btn">Réessayer</button></form>
        <?php else: ?>
            <p>Cliquez sur le bouton ci-dessous pour créer les tables et l'utilisateur administrateur.</p>
            <form method="POST">
                <button type="submit" class="btn">Installer la base de données</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
