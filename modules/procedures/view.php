<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$procedure = null;
$accessViaToken = false;

// Acces via token (public, sans authentification)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $db->prepare("SELECT * FROM procedures WHERE lien_partage = ?");
    $stmt->execute([$token]);
    $procedure = $stmt->fetch();
    $accessViaToken = true;
}
// Acces via id (authentification requise)
elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    require_once __DIR__ . '/../../includes/auth.php';
    requireLogin();
    $userId = getCurrentUserId();
    $stmt = $db->prepare("SELECT * FROM procedures WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['id'], $userId]);
    $procedure = $stmt->fetch();
}

// Si acces via token : page standalone
if ($accessViaToken) {
    if (!$procedure) {
        http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procédure introuvable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; }
        .container-page { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .card-content { background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
    <div class="container-page">
        <div class="card-content text-center">
            <h2 class="text-danger mb-3">Procédure introuvable</h2>
            <p class="text-muted">Le lien de partage est invalide ou cette procédure n'existe plus.</p>
        </div>
    </div>
</body>
</html>
<?php
        exit;
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($procedure['nom']) ?> - Procédure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --ce-red: #e4002b; --ce-red-dark: #c40025; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; color: #333; }
        .page-header { background: linear-gradient(135deg, var(--ce-red) 0%, var(--ce-red-dark) 100%); color: #fff; padding: 30px 0; }
        .page-header .container-page { display: flex; align-items: center; gap: 15px; }
        .page-header h1 { font-size: 24px; margin: 0; }
        .page-header .badge-mea { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 12px; font-size: 13px; }
        .container-page { max-width: 900px; margin: 0 auto; padding: 0 20px; }
        .card-content { background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin: 30px auto; }
        .procedure-text { white-space: pre-wrap; line-height: 1.8; font-size: 15px; }
        .wysiwyg-content { line-height: 1.8; font-size: 15px; }
        .wysiwyg-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0; display: block; }
        .wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 { color: #e4002b; margin-top: 16px; margin-bottom: 8px; }
        .wysiwyg-content ul, .wysiwyg-content ol { padding-left: 24px; }
        .wysiwyg-content object { max-width: 100%; display: block; margin: 8px 0; }
        .meta-info { color: #999; font-size: 13px; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
        @media print { .page-header { background: #fff !important; color: #333 !important; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-page">
            <i class="fas fa-book fa-lg"></i>
            <div>
                <h1><?= e($procedure['nom']) ?></h1>
                <?php if ($procedure['mise_en_avant']): ?>
                    <span class="badge-mea"><i class="fas fa-star"></i> Mise en avant</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container-page">
        <div class="card-content">
            <?php if (preg_match('/<[a-z][\s\S]*>/i', $procedure['texte'])): ?>
            <div class="wysiwyg-content"><?= $procedure['texte'] ?></div>
        <?php else: ?>
            <div class="procedure-text"><?= e($procedure['texte']) ?></div>
        <?php endif; ?>
            <div class="meta-info">
                <i class="fas fa-calendar"></i> Publiée le <?= formatDate($procedure['created_at']) ?>
                <?php if (!empty($procedure['contributor_prenom']) || !empty($procedure['contributor_nom'])): ?>
                    <br><i class="fas fa-user-edit"></i> Contributeur : <?= e(trim($procedure['contributor_prenom'] . ' ' . $procedure['contributor_nom'])) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print"></i> Imprimer</button>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// Acces via id : utiliser le layout standard
if (!$procedure) {
    $pageTitle = 'Procédure introuvable';
    require_once __DIR__ . '/../../templates/header.php';
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Cette procédure n\'existe pas ou vous n\'y avez pas accès.</div>';
    require_once __DIR__ . '/../../templates/footer.php';
    exit;
}

$pageTitle = e($procedure['nom']);
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="mb-3">
    <a href="index.php" class="btn btn-ce-outline btn-sm"><i class="fas fa-arrow-left"></i> Retour aux procédures</a>
    <button onclick="window.print()" class="btn btn-ce-outline btn-sm no-print"><i class="fas fa-print"></i> Imprimer</button>
</div>

<div class="data-table-container">
    <div class="p-4">
        <h3><?= e($procedure['nom']) ?>
            <?php if ($procedure['mise_en_avant']): ?>
                <span class="badge-fait ms-2"><i class="fas fa-star"></i> Mise en avant</span>
            <?php endif; ?>
        </h3>
        <small class="text-muted">Créée le <?= formatDate($procedure['created_at']) ?></small>
        <?php if (!empty($procedure['contributor_prenom']) || !empty($procedure['contributor_nom'])): ?>
            <div class="text-muted small"><i class="fas fa-user-edit"></i> Contributeur : <?= e(trim($procedure['contributor_prenom'] . ' ' . $procedure['contributor_nom'])) ?></div>
        <?php endif; ?>
        <hr>
        <?php if (preg_match('/<[a-z][\s\S]*>/i', $procedure['texte'])): ?>
            <div class="wysiwyg-content" style="font-size:15px;"><?= $procedure['texte'] ?></div>
        <?php else: ?>
            <div style="white-space:pre-wrap; line-height:1.8; font-size:15px;"><?= e($procedure['texte']) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
