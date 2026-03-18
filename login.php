<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <svg viewBox="0 0 200 50" width="250" xmlns="http://www.w3.org/2000/svg" class="login-logo">
                    <rect x="0" y="5" width="40" height="40" rx="8" fill="#e4002b"/>
                    <text x="8" y="35" font-family="Arial,sans-serif" font-weight="bold" font-size="28" fill="white">CE</text>
                    <text x="50" y="22" font-family="Arial,sans-serif" font-weight="bold" font-size="13" fill="#333">CAISSE D'EPARGNE</text>
                    <text x="50" y="38" font-family="Arial,sans-serif" font-size="10" fill="#666">Midi-Pyrénées</text>
                </svg>
                <h2>Portail de Gestion d'Activités</h2>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Identifiant</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-ce w-100">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>
