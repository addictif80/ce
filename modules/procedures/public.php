<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $db->prepare("SELECT p.*, u.nom AS author_nom, u.prenom AS author_prenom
        FROM procedures p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.approved = 1 AND (p.nom LIKE ? OR p.texte LIKE ?)
        ORDER BY p.mise_en_avant DESC, p.created_at DESC");
    $stmt->execute([$like, $like]);
} else {
    $stmt = $db->prepare("SELECT p.*, u.nom AS author_nom, u.prenom AS author_prenom
        FROM procedures p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.approved = 1
        ORDER BY p.mise_en_avant DESC, p.created_at DESC");
    $stmt->execute();
}
$procedures = $stmt->fetchAll();
foreach ($procedures as &$proc) {
    $proc['created_at_fr'] = formatDate($proc['created_at']);
}
unset($proc);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procédures publiées - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root { --ce-red: #e4002b; --ce-red-dark: #c40025; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; color: #333; }
        .page-header { background: linear-gradient(135deg, var(--ce-red) 0%, var(--ce-red-dark) 100%); color: #fff; padding: 30px 0; }
        .page-header .container-page { display: flex; align-items: center; gap: 15px; }
        .page-header h1 { font-size: 24px; margin: 0; }
        .container-page { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        .content-wrap { margin: 30px auto; }
        .wysiwyg-content { line-height: 1.8; font-size: 15px; }
        .wysiwyg-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0; display: block; }
        .wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 { color: #e4002b; margin-top: 16px; margin-bottom: 8px; }
        .wysiwyg-content ul, .wysiwyg-content ol { padding-left: 24px; }
        .wysiwyg-content object { max-width: 100%; display: block; margin: 8px 0; }
        @media print { .page-header, .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-page">
            <i class="fas fa-book fa-lg"></i>
            <div>
                <h1>Procédures publiées</h1>
                <span style="opacity:.85;font-size:13px;">Consultation libre, sans connexion</span>
            </div>
        </div>
    </div>
    <div class="container-page content-wrap">
        <div class="mb-3 no-print">
            <a href="../../login.php" class="btn btn-ce-outline btn-sm"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= count($procedures) ?></div>
                    <div class="stat-label">Procédure<?= count($procedures) > 1 ? 's' : '' ?> disponible<?= count($procedures) > 1 ? 's' : '' ?></div>
                </div>
            </div>
        </div>

        <div class="data-table-container">
            <div class="data-table-header">
                <h3>Toutes les procédures</h3>
                <form method="GET" class="search-box no-print">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Rechercher (titre ou contenu)..." value="<?= e($q) ?>">
                </form>
            </div>
            <?php if (empty($procedures)): ?>
                <div class="p-4">
                    <div class="alert alert-info mb-0">
                        <?= $q !== '' ? 'Aucune procédure ne correspond à votre recherche.' : 'Aucune procédure publiée pour le moment.' ?>
                    </div>
                </div>
            <?php else: ?>
            <table class="data-table" id="tableProcedures">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Mise en avant</th>
                        <th>Auteur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($procedures as $proc): ?>
                    <tr>
                        <td><strong><?= e($proc['nom']) ?></strong></td>
                        <td>
                            <?php if ($proc['mise_en_avant']): ?>
                                <span class="badge-fait"><i class="fas fa-star"></i> Oui</span>
                            <?php else: ?>
                                <span class="badge-afaire">Non</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(trim(($proc['author_prenom'] ?? '') . ' ' . ($proc['author_nom'] ?? ''))) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $proc['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la procédure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent"></div>
            </div>
        </div>
    </div>

    <script>
    const proceduresData = <?= json_encode($procedures) ?>;

    function escapeHtml(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function renderContent(html) {
        if (!html) return '<em class="text-muted">Aucun contenu</em>';
        if (/<[a-z][\s\S]*>/i.test(html)) {
            return '<div class="wysiwyg-content">' + html + '</div>';
        }
        const d = document.createElement('div');
        d.textContent = html;
        return '<div style="white-space:pre-wrap;line-height:1.8;">' + d.innerHTML + '</div>';
    }

    function showDetail(id) {
        const proc = proceduresData.find(p => p.id == id);
        if (!proc) return;
        const detailEl = document.getElementById('detailContent');
        detailEl.innerHTML = `
            <div class="row">
                <div class="col-12 mb-3">
                    <h4>${escapeHtml(proc.nom)}</h4>
                    <small class="text-muted">Publiée le ${escapeHtml(proc.created_at_fr)}</small>
                    ${proc.mise_en_avant == 1 ? ' <span class="badge-fait ms-2"><i class="fas fa-star"></i> Mise en avant</span>' : ''}
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded" id="procDetailContent"></div>
                </div>
            </div>`;
        document.getElementById('procDetailContent').innerHTML = renderContent(proc.texte);
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    }
    </script>
</body>
</html>
