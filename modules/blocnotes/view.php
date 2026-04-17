<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$db  = getDB();
$note = null;

if (!empty($_GET['token'])) {
    $stmt = $db->prepare("
        SELECT bn.*, u.nom AS owner_nom, u.prenom AS owner_prenom
        FROM blocnotes bn
        JOIN users u ON bn.user_id = u.id
        WHERE bn.lien_partage = ?
    ");
    $stmt->execute([$_GET['token']]);
    $note = $stmt->fetch();
}

if (!$note) {
    http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note introuvable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container-page { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .card-content { background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
    <div class="container-page">
        <div class="card-content text-center">
            <h2 class="text-danger mb-3">Note introuvable</h2>
            <p class="text-muted">Le lien de partage est invalide ou cette note n'est plus disponible.</p>
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
    <title><?= e($note['nom_note']) ?> – Bloc-notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --ce-red: #e4002b; --ce-red-dark: #c40025; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; color: #333; }
        .page-header { background: linear-gradient(135deg, var(--ce-red) 0%, var(--ce-red-dark) 100%); color: #fff; padding: 30px 0; }
        .page-header .container-page { display: flex; align-items: center; gap: 15px; }
        .page-header h1 { font-size: 24px; margin: 0; }
        .page-header .shared-by { font-size: 13px; opacity: 0.85; margin-top: 4px; }
        .container-page { max-width: 900px; margin: 0 auto; padding: 0 20px; }
        .card-content { background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin: 30px auto; }
        .meta-info { color: #999; font-size: 13px; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; }
        .wysiwyg-content { line-height: 1.8; font-size: 15px; }
        .wysiwyg-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0; display: block; }
        .wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 { color: var(--ce-red); margin-top: 16px; margin-bottom: 8px; }
        .wysiwyg-content ul, .wysiwyg-content ol { padding-left: 24px; }
        .wysiwyg-content object { max-width: 100%; display: block; margin: 8px 0; }
        @media print {
            .page-header { background: #fff !important; color: #333 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-page">
            <i class="fas fa-sticky-note fa-lg"></i>
            <div>
                <h1><?= e($note['nom_note']) ?></h1>
                <div class="shared-by"><i class="fas fa-share-alt me-1"></i> Partagé par <?= e($note['owner_prenom'] . ' ' . $note['owner_nom']) ?></div>
            </div>
        </div>
    </div>

    <div class="container-page">
        <div class="card-content">
            <?php if (preg_match('/<[a-z][\s\S]*>/i', $note['contenu'])): ?>
                <div class="wysiwyg-content"><?= $note['contenu'] ?></div>
            <?php else: ?>
                <div style="white-space:pre-wrap;line-height:1.8;font-size:15px;"><?= e($note['contenu']) ?></div>
            <?php endif; ?>
            <div class="meta-info">
                <i class="fas fa-calendar"></i> Modifiée le <?= formatDate($note['updated_at']) ?>
            </div>
        </div>
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>
</body>
</html>
