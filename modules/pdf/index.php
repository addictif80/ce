<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/lib.php';
requireLogin();
pdfEnsureSchema();
$db = getDB();
$userId = getCurrentUserId();

// Téléchargement (avant tout affichage) : seul le propriétaire accède au fichier
if (isset($_GET['dl'])) {
    $st = $db->prepare("SELECT nom, fichier FROM pdf_fichiers WHERE id = ? AND user_id = ?");
    $st->execute([(int)$_GET['dl'], $userId]);
    $row = $st->fetch();
    $path = $row ? pdfUserDir($userId) . '/' . basename($row['fichier']) : '';
    if (!$row || !is_file($path)) { http_response_code(404); exit('Document introuvable.'); }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $row['nom']) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $st = $db->prepare("SELECT fichier FROM pdf_fichiers WHERE id = ? AND user_id = ?");
    $st->execute([(int)($_POST['id'] ?? 0), $userId]);
    if ($row = $st->fetch()) {
        @unlink(pdfUserDir($userId) . '/' . basename($row['fichier']));
        $db->prepare("DELETE FROM pdf_fichiers WHERE id = ? AND user_id = ?")->execute([(int)$_POST['id'], $userId]);
    }
    header('Location: index.php');
    exit;
}

$pageTitle = 'Boîte à outils PDF';
require_once __DIR__ . '/../../templates/header.php';
$st = $db->prepare("SELECT * FROM pdf_fichiers WHERE user_id = ? ORDER BY created_at DESC");
$st->execute([$userId]);
$docs = $st->fetchAll();
$capSave = true;
$pdfSaveUrl = 'save.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-file-pdf"></i> Boîte à outils PDF</h4>
    <p class="text-muted mb-0">Fusionnez, découpez, faites pivoter des PDF ou convertissez des images, puis enregistrez le résultat dans vos documents.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<div class="card mt-3"><div class="card-header fw-semibold">Mes documents <span class="text-muted fw-normal small">(<?= count($docs) ?> / <?= PDF_MAX_FILES ?>)</span></div>
<div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle">
    <thead><tr><th>Nom</th><th class="text-end">Taille</th><th>Date</th><th></th></tr></thead><tbody>
    <?php foreach ($docs as $d): ?>
        <tr><td><i class="fas fa-file-pdf text-danger me-1"></i><?= e($d['nom']) ?></td>
            <td class="text-end"><?= $d['taille'] > 1048576 ? number_format($d['taille'] / 1048576, 1, ',', ' ') . ' Mo' : max(1, round($d['taille'] / 1024)) . ' Ko' ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($d['created_at']))) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="?dl=<?= (int)$d['id'] ?>"><i class="fas fa-download"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce document ?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>
    <?php endforeach; if (!$docs): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucun document enregistré.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
