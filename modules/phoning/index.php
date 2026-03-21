<?php
$pageTitle = 'Séances phoning';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO seances_phoning (user_id, nombre_appels, nombre_rdv, dont_s, dont_s1, dont_anv, nombre_repondeur) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        (int)$_POST['nombre_appels'],
        (int)$_POST['nombre_rdv'],
        (int)$_POST['dont_s'],
        (int)$_POST['dont_s1'],
        (int)$_POST['dont_anv'],
        (int)$_POST['nombre_repondeur']
    ]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE seances_phoning SET nombre_appels = ?, nombre_rdv = ?, dont_s = ?, dont_s1 = ?, dont_anv = ?, nombre_repondeur = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([
        (int)$_POST['nombre_appels'],
        (int)$_POST['nombre_rdv'],
        (int)$_POST['dont_s'],
        (int)$_POST['dont_s1'],
        (int)$_POST['dont_anv'],
        (int)$_POST['nombre_repondeur'],
        (int)$_POST['id'],
        $userId
    ]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM seances_phoning WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Objectifs
$objectifAppels = 60;
$objectifRDV = 12;

// Totaux semaine en cours
$stmt = $db->prepare("SELECT COALESCE(SUM(nombre_appels),0) as appels, COALESCE(SUM(nombre_rdv),0) as rdv, COALESCE(SUM(dont_s),0) as s, COALESCE(SUM(dont_s1),0) as s1, COALESCE(SUM(dont_anv),0) as anv, COALESCE(SUM(nombre_repondeur),0) as repondeur FROM seances_phoning WHERE user_id = ? AND YEARWEEK(date_ajout, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$userId]);
$semaine = $stmt->fetch();

$resteAppels = max(0, $objectifAppels - $semaine['appels']);
$resteRDV = max(0, $objectifRDV - $semaine['rdv']);
$pctAppels = min(100, round(($semaine['appels'] / $objectifAppels) * 100));
$pctRDV = min(100, round(($semaine['rdv'] / $objectifRDV) * 100));

// Liste complète
$stmt = $db->prepare("SELECT * FROM seances_phoning WHERE user_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$seances = $stmt->fetchAll();
?>

<!-- Stats semaine -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['appels'] ?> / <?= $objectifAppels ?></div>
            <div class="stat-label">Appels cette semaine</div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar progress-ce" role="progressbar" style="width: <?= $pctAppels ?>%"></div>
            </div>
            <small class="text-muted mt-1 d-block">Reste : <strong><?= $resteAppels ?></strong> appels</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['rdv'] ?> / <?= $objectifRDV ?></div>
            <div class="stat-label">RDV cette semaine</div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar progress-ce" role="progressbar" style="width: <?= $pctRDV ?>%"></div>
            </div>
            <small class="text-muted mt-1 d-block">Reste : <strong><?= $resteRDV ?></strong> RDV</small>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle séance</button>
    </div>
</div>

<!-- Résumé semaine détaillé -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['s'] ?></div>
            <div class="stat-label">Dont S (semaine)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['s1'] ?></div>
            <div class="stat-label">Dont S+1 (semaine)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['anv'] ?></div>
            <div class="stat-label">Dont ANV (semaine)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['repondeur'] ?></div>
            <div class="stat-label">Répondeurs (semaine)</div>
        </div>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les séances</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchPhoning" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tablePhoning">
        <thead>
            <tr>
                <th>Date</th>
                <th>Appels</th>
                <th>RDV</th>
                <th>Dont S</th>
                <th>Dont S+1</th>
                <th>Dont ANV</th>
                <th>Répondeurs</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($seances as $seance): ?>
            <tr>
                <td><?= formatDate($seance['date_ajout']) ?></td>
                <td><strong><?= (int)$seance['nombre_appels'] ?></strong></td>
                <td><strong><?= (int)$seance['nombre_rdv'] ?></strong></td>
                <td><?= (int)$seance['dont_s'] ?></td>
                <td><?= (int)$seance['dont_s1'] ?></td>
                <td><?= (int)$seance['dont_anv'] ?></td>
                <td><?= (int)$seance['nombre_repondeur'] ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="editSeance(<?= $seance['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette séance ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $seance['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout -->
<div class="modal fade modal-fullscreen-custom" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle séance phoning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre d'appels</label>
                            <input type="number" name="nombre_appels" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de RDV obtenus</label>
                            <input type="number" name="nombre_rdv" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dont S</label>
                            <input type="number" name="dont_s" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dont S+1</label>
                            <input type="number" name="dont_s1" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dont ANV</label>
                            <input type="number" name="dont_anv" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de répondeurs</label>
                            <input type="number" name="nombre_repondeur" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la séance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<script>
filterTable('searchPhoning', 'tablePhoning');

const seancesData = <?= json_encode($seances) ?>;

function editSeance(id) {
    const s = seancesData.find(x => x.id == id);
    if (!s) return;

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre d'appels</label>
                    <input type="number" name="nombre_appels" class="form-control" min="0" value="${s.nombre_appels}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre de RDV obtenus</label>
                    <input type="number" name="nombre_rdv" class="form-control" min="0" value="${s.nombre_rdv}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dont S</label>
                    <input type="number" name="dont_s" class="form-control" min="0" value="${s.dont_s}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dont S+1</label>
                    <input type="number" name="dont_s1" class="form-control" min="0" value="${s.dont_s1}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dont ANV</label>
                    <input type="number" name="dont_anv" class="form-control" min="0" value="${s.dont_anv}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre de répondeurs</label>
                    <input type="number" name="nombre_repondeur" class="form-control" min="0" value="${s.nombre_repondeur}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    editSeance(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
