<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
ensureProcedureProposalsSchema();

$flash = null;

// Proposition d'ajout ou de modification (public, sans authentification)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['propose_create', 'propose_edit'], true)) {
    $nom = trim($_POST['nom'] ?? '');
    $texte = trim($_POST['texte'] ?? '');
    $contributorPrenom = trim($_POST['contributor_prenom'] ?? '');
    $contributorNom = trim($_POST['contributor_nom'] ?? '');

    if ($nom === '' || $texte === '' || $contributorPrenom === '' || $contributorNom === '') {
        $flash = ['type' => 'danger', 'message' => 'Merci de renseigner tous les champs (nom, prénom, titre et contenu de la procédure).'];
    } elseif ($_POST['action'] === 'propose_create') {
        $stmt = $db->prepare("INSERT INTO procedure_proposals (type, procedure_id, nom, texte, contributor_prenom, contributor_nom) VALUES ('create', NULL, ?, ?, ?, ?)");
        $stmt->execute([$nom, $texte, $contributorPrenom, $contributorNom]);
        $flash = ['type' => 'success', 'message' => 'Merci ! Votre proposition de nouvelle procédure a été envoyée et sera examinée par un administrateur.'];
    } else {
        $procedureId = (int)($_POST['procedure_id'] ?? 0);
        $stmtCheck = $db->prepare("SELECT id FROM procedures WHERE id = ? AND approved = 1");
        $stmtCheck->execute([$procedureId]);
        if ($stmtCheck->fetch()) {
            $stmt = $db->prepare("INSERT INTO procedure_proposals (type, procedure_id, nom, texte, contributor_prenom, contributor_nom) VALUES ('edit', ?, ?, ?, ?, ?)");
            $stmt->execute([$procedureId, $nom, $texte, $contributorPrenom, $contributorNom]);
            $flash = ['type' => 'success', 'message' => 'Merci ! Votre proposition de modification a été envoyée et sera examinée par un administrateur.'];
        } else {
            $flash = ['type' => 'danger', 'message' => "La procédure à modifier est introuvable."];
        }
    }
}

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
        <div class="mb-3 no-print d-flex justify-content-between flex-wrap gap-2">
            <a href="../../login.php" class="btn btn-ce-outline btn-sm"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
            <button class="btn btn-ce btn-sm" data-bs-toggle="modal" data-bs-target="#proposeAddModal"><i class="fas fa-plus"></i> Proposer une nouvelle procédure</button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> no-print"><?= e($flash['message']) ?></div>
        <?php endif; ?>

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
                        <th>Auteur / Contributeur</th>
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
                        <td>
                            <?php if (!empty($proc['author_nom']) || !empty($proc['author_prenom'])): ?>
                                <?= e(trim(($proc['author_prenom'] ?? '') . ' ' . ($proc['author_nom'] ?? ''))) ?>
                            <?php elseif (!empty($proc['contributor_nom']) || !empty($proc['contributor_prenom'])): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-user-edit"></i> <?= e(trim($proc['contributor_prenom'] . ' ' . $proc['contributor_nom'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
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

    <!-- Modal Proposer un ajout -->
    <div class="modal fade" id="proposeAddModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Proposer une nouvelle procédure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="propose_create">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Votre prénom</label>
                                <input type="text" name="contributor_prenom" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Votre nom</label>
                                <input type="text" name="contributor_nom" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nom de la procédure</label>
                                <input type="text" name="nom" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contenu de la procédure</label>
                                <textarea name="texte" class="form-control" rows="8" required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Votre proposition sera soumise à validation par un administrateur avant d'être publiée. En cas d'approbation, votre nom et prénom seront mentionnés comme contributeur.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ce"><i class="fas fa-paper-plane"></i> Envoyer la proposition</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Proposer une modification -->
    <div class="modal fade" id="proposeEditModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Proposer une modification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="propose_edit">
                        <input type="hidden" name="procedure_id" id="proposeEditProcedureId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Votre prénom</label>
                                <input type="text" name="contributor_prenom" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Votre nom</label>
                                <input type="text" name="contributor_nom" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nom de la procédure</label>
                                <input type="text" name="nom" id="proposeEditNom" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contenu proposé</label>
                                <textarea name="texte" id="proposeEditTexte" class="form-control" rows="8" required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Votre proposition de modification sera soumise à validation par un administrateur. En cas d'approbation, votre nom et prénom seront mentionnés comme contributeur.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ce"><i class="fas fa-paper-plane"></i> Envoyer la proposition</button>
                            </div>
                        </div>
                    </form>
                </div>
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
        const hasContributor = proc.contributor_prenom || proc.contributor_nom;
        const detailEl = document.getElementById('detailContent');
        detailEl.innerHTML = `
            <div class="row">
                <div class="col-12 mb-3">
                    <h4>${escapeHtml(proc.nom)}</h4>
                    <small class="text-muted">Publiée le ${escapeHtml(proc.created_at_fr)}</small>
                    ${proc.mise_en_avant == 1 ? ' <span class="badge-fait ms-2"><i class="fas fa-star"></i> Mise en avant</span>' : ''}
                    ${hasContributor ? '<div class="text-muted mt-1"><i class="fas fa-user-edit"></i> Contributeur : ' + escapeHtml((proc.contributor_prenom || '') + ' ' + (proc.contributor_nom || '')) + '</div>' : ''}
                </div>
                <div class="col-12 mb-3">
                    <div class="p-3 bg-light rounded" id="procDetailContent"></div>
                </div>
                <div class="col-12 no-print">
                    <button type="button" class="btn btn-ce-outline btn-sm" onclick="openProposeEdit(${proc.id})"><i class="fas fa-edit"></i> Proposer une modification</button>
                </div>
            </div>`;
        document.getElementById('procDetailContent').innerHTML = renderContent(proc.texte);
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    }

    function openProposeEdit(id) {
        const proc = proceduresData.find(p => p.id == id);
        if (!proc) return;
        document.getElementById('proposeEditProcedureId').value = proc.id;
        document.getElementById('proposeEditNom').value = proc.nom;
        document.getElementById('proposeEditTexte').value = proc.texte || '';
        bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
        new bootstrap.Modal(document.getElementById('proposeEditModal')).show();
    }
    </script>
</body>
</html>
