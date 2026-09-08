<?php
$pageTitle = 'Séances phoning';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Auto-create appels_phoning table
$db->exec("CREATE TABLE IF NOT EXISTS appels_phoning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seance_id INT NOT NULL,
    user_id INT NOT NULL,
    numero_personne VARCHAR(100) DEFAULT NULL,
    resultat ENUM('repondu','repondeur','indisponible','rdv') NOT NULL DEFAULT 'repondu',
    date_rdv DATE DEFAULT NULL,
    motif_rdv ENUM('Banca','Epargne','Placement','Crédit','Assurances') DEFAULT NULL,
    commentaire TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seance_id) REFERENCES seances_phoning(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE seances_phoning ADD COLUMN titre VARCHAR(255) DEFAULT NULL AFTER date_ajout"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE seances_phoning ADD COLUMN notes TEXT DEFAULT NULL AFTER titre"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE seances_phoning ADD COLUMN nombre_anv INT DEFAULT 0 AFTER nombre_repondeur"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE appels_phoning ADD COLUMN is_anv TINYINT(1) DEFAULT 0 AFTER motif_rdv"); } catch (Exception $e) {}

// === ACTIONS ===

// Ajout séance (dossier)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO seances_phoning (user_id, titre) VALUES (?, ?)");
    $stmt->execute([$userId, trim($_POST['titre'] ?? '') ?: 'Séance du ' . date('d/m/Y')]);
    $newId = $db->lastInsertId();
    header('Location: index.php?open=' . $newId);
    exit;
}

// Edition séance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE seances_phoning SET titre = ?, notes = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([trim($_POST['titre']), trim($_POST['notes'] ?? ''), $id, $userId]);
    header('Location: index.php?open=' . $id);
    exit;
}

// Suppression séance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM seances_phoning WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Ajout appel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_appel') {
    $seanceId = (int)$_POST['seance_id'];
    $resultat = $_POST['resultat'];
    $dateRdv = ($resultat === 'rdv' && !empty($_POST['date_rdv'])) ? $_POST['date_rdv'] : null;
    $motifRdv = ($resultat === 'rdv' && !empty($_POST['motif_rdv'])) ? $_POST['motif_rdv'] : null;
    $isAnv = ($resultat === 'rdv' && isset($_POST['is_anv'])) ? 1 : 0;
    $stmt = $db->prepare("INSERT INTO appels_phoning (seance_id, user_id, numero_personne, resultat, date_rdv, motif_rdv, is_anv, commentaire) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$seanceId, $userId, trim($_POST['numero_personne'] ?? ''), $resultat, $dateRdv, $motifRdv, $isAnv, trim($_POST['commentaire'] ?? '')]);
    // Recalculer les compteurs
    recalcSeance($db, $seanceId, $userId);
    header('Location: index.php?open=' . $seanceId);
    exit;
}

// Suppression appel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_appel') {
    $appelId = (int)$_POST['appel_id'];
    $seanceId = (int)$_POST['seance_id'];
    $db->prepare("DELETE FROM appels_phoning WHERE id = ? AND user_id = ?")->execute([$appelId, $userId]);
    recalcSeance($db, $seanceId, $userId);
    header('Location: index.php?open=' . $seanceId);
    exit;
}

/**
 * Recalcule les compteurs d'une séance à partir des appels
 */
function recalcSeance($db, $seanceId, $userId) {
    $stmt = $db->prepare("SELECT
        COUNT(*) as total,
        SUM(resultat = 'repondeur') as repondeurs,
        SUM(resultat = 'rdv') as rdv,
        SUM(resultat = 'rdv' AND is_anv = 1) as anv,
        SUM(resultat = 'rdv' AND date_rdv IS NOT NULL AND YEARWEEK(date_rdv, 1) = YEARWEEK(CURDATE(), 1)) as rdv_s,
        SUM(resultat = 'rdv' AND date_rdv IS NOT NULL AND YEARWEEK(date_rdv, 1) = YEARWEEK(CURDATE(), 1) + 1) as rdv_s1,
        SUM(resultat = 'rdv' AND (date_rdv IS NULL OR (YEARWEEK(date_rdv, 1) != YEARWEEK(CURDATE(), 1) AND YEARWEEK(date_rdv, 1) != YEARWEEK(CURDATE(), 1) + 1))) as rdv_autres
        FROM appels_phoning WHERE seance_id = ?");
    $stmt->execute([$seanceId]);
    $c = $stmt->fetch();
    $db->prepare("UPDATE seances_phoning SET nombre_appels = ?, nombre_repondeur = ?, nombre_anv = ?, nombre_rdv = ?, dont_s = ?, dont_s1 = ?, dont_anv = ? WHERE id = ? AND user_id = ?")
        ->execute([(int)$c['total'], (int)$c['repondeurs'], (int)$c['anv'], (int)$c['rdv'], (int)$c['rdv_s'], (int)$c['rdv_s1'], (int)$c['rdv_autres'], $seanceId, $userId]);
}

// Objectifs
$objectifAppels = 60;
$objectifRDV = 12;
$objectifANV = 2;

// Totaux semaine en cours (toutes séances confondues)
$stmt = $db->prepare("SELECT
    COALESCE(SUM(nombre_appels),0) as appels,
    COALESCE(SUM(nombre_rdv),0) as rdv,
    COALESCE(SUM(nombre_anv),0) as anv_total,
    COALESCE(SUM(dont_s),0) as s,
    COALESCE(SUM(dont_s1),0) as s1,
    COALESCE(SUM(dont_anv),0) as anv,
    COALESCE(SUM(nombre_repondeur),0) as repondeur
    FROM seances_phoning WHERE user_id = ? AND YEARWEEK(date_ajout, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$userId]);
$semaine = $stmt->fetch();

$resteAppels = max(0, $objectifAppels - $semaine['appels']);
$resteRDV = max(0, $objectifRDV - $semaine['rdv']);
$resteANV = max(0, $objectifANV - $semaine['anv_total']);
$pctAppels = $objectifAppels > 0 ? min(100, round(($semaine['appels'] / $objectifAppels) * 100)) : 0;
$pctRDV = $objectifRDV > 0 ? min(100, round(($semaine['rdv'] / $objectifRDV) * 100)) : 0;
$pctANV = $objectifANV > 0 ? min(100, round(($semaine['anv_total'] / $objectifANV) * 100)) : 0;

// Liste des séances
$stmt = $db->prepare("SELECT * FROM seances_phoning WHERE user_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$seances = $stmt->fetchAll();

// Appels par séance (pour le JS)
$appelsParSeance = [];
foreach ($seances as $s) {
    $stmt = $db->prepare("SELECT * FROM appels_phoning WHERE seance_id = ? ORDER BY created_at DESC");
    $stmt->execute([$s['id']]);
    $appelsParSeance[$s['id']] = $stmt->fetchAll();
}
?>

<!-- Stats semaine -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['appels'] ?> / <?= $objectifAppels ?></div>
            <div class="stat-label">Appels cette semaine</div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar progress-ce" role="progressbar" style="width: <?= $pctAppels ?>%"></div>
            </div>
            <small class="text-muted mt-1 d-block">Reste : <strong><?= $resteAppels ?></strong></small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['rdv'] ?> / <?= $objectifRDV ?></div>
            <div class="stat-label">RDV cette semaine</div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar progress-ce" role="progressbar" style="width: <?= $pctRDV ?>%"></div>
            </div>
            <small class="text-muted mt-1 d-block">Reste : <strong><?= $resteRDV ?></strong></small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['anv_total'] ?> / <?= $objectifANV ?></div>
            <div class="stat-label">ANV cette semaine</div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar <?= $pctANV >= 100 ? 'bg-success' : 'progress-ce' ?>" role="progressbar" style="width: <?= $pctANV ?>%"></div>
            </div>
            <small class="text-muted mt-1 d-block">Reste : <strong><?= $resteANV ?></strong></small>
        </div>
    </div>
    <div class="col-md-1">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['repondeur'] ?></div>
            <div class="stat-label">Répondeurs</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-number"><?= $semaine['s'] ?> / <?= $semaine['s1'] ?> / <?= $semaine['anv'] ?></div>
            <div class="stat-label">RDV S / S+1 / Autres</div>
        </div>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle séance</button>
    </div>
</div>

<!-- Tableau des séances -->
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
                <th>Titre</th>
                <th>Appels</th>
                <th>Répondeurs</th>
                <th>RDV</th>
                <th>S / S+1 / Autres</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($seances as $seance): ?>
            <tr>
                <td><?= formatDate($seance['date_ajout']) ?></td>
                <td><strong><?= e($seance['titre'] ?: 'Séance du ' . formatDate($seance['date_ajout'])) ?></strong></td>
                <td><?= (int)$seance['nombre_appels'] ?></td>
                <td><?= (int)$seance['nombre_repondeur'] ?></td>
                <td><strong><?= (int)$seance['nombre_rdv'] ?></strong></td>
                <td><?= (int)$seance['dont_s'] ?> / <?= (int)$seance['dont_s1'] ?> / <?= (int)$seance['dont_anv'] ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="openDossier(<?= $seance['id'] ?>)" title="Ouvrir le dossier"><i class="fas fa-folder-open"></i></button>
                    <a href="rapport.php?id=<?= $seance['id'] ?>" target="_blank" class="btn btn-sm btn-ce-outline" title="Imprimer le rapport"><i class="fas fa-print"></i></a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette séance et tous ses appels ?')">
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

<!-- Modal Nouvelle Séance -->
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
                        <div class="col-md-8">
                            <label class="form-label">Titre (optionnel)</label>
                            <input type="text" name="titre" class="form-control" placeholder="Séance du <?= date('d/m/Y') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-folder-plus"></i> Créer la séance</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dossier (détail séance) -->
<div class="modal fade modal-fullscreen-custom" id="dossierModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dossierTitle"><i class="fas fa-phone-volume"></i> Séance phoning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dossierContent"></div>
        </div>
    </div>
</div>

<!-- Modal Edit Séance -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la séance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchPhoning', 'tablePhoning');

const seancesData = <?= json_encode($seances) ?>;
const appelsData = <?= json_encode($appelsParSeance) ?>;
const motifs = ['Banca','Epargne','Placement','Crédit','Assurances'];

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

const resultatLabels = {
    'repondu': '<span class="badge bg-success">Répondu</span>',
    'repondeur': '<span class="badge bg-warning text-dark">Répondeur</span>',
    'indisponible': '<span class="badge bg-secondary">Indisponible</span>',
    'rdv': '<span class="badge bg-primary">RDV programmé</span>'
};

function openDossier(id) {
    const s = seancesData.find(x => x.id == id);
    if (!s) return;
    const appels = appelsData[id] || [];

    // Stats du dossier
    const totalAppels = appels.length;
    const repondeurs = appels.filter(a => a.resultat === 'repondeur').length;
    const indispos = appels.filter(a => a.resultat === 'indisponible').length;
    const rdvs = appels.filter(a => a.resultat === 'rdv').length;
    const anvs = appels.filter(a => a.resultat === 'rdv' && a.is_anv == 1).length;
    const repondus = appels.filter(a => a.resultat === 'repondu').length;

    // Calculer S / S+1 / autres côté JS
    const now = new Date();
    const getWeek = (d) => {
        const dt = new Date(d);
        const onejan = new Date(dt.getFullYear(), 0, 1);
        return Math.ceil(((dt - onejan) / 86400000 + onejan.getDay() + 1) / 7);
    };
    const currentWeek = getWeek(now);
    const currentYear = now.getFullYear();
    let rdvS = 0, rdvS1 = 0, rdvAutres = 0;
    appels.filter(a => a.resultat === 'rdv' && a.date_rdv).forEach(a => {
        const rd = new Date(a.date_rdv);
        const w = getWeek(rd);
        const y = rd.getFullYear();
        if (y === currentYear && w === currentWeek) rdvS++;
        else if ((y === currentYear && w === currentWeek + 1) || (currentWeek >= 52 && y === currentYear + 1 && w === 1)) rdvS1++;
        else rdvAutres++;
    });

    // Liste des appels
    let appelsHtml = '';
    if (appels.length > 0) {
        appelsHtml = `<table class="table table-sm table-bordered">
            <thead class="table-light"><tr>
                <th>Heure</th><th>N° / Nom</th><th>Résultat</th><th>Date RDV</th><th>Motif</th><th>ANV</th><th>Commentaire</th><th></th>
            </tr></thead><tbody>`;
        appels.forEach(a => {
            const time = a.created_at ? new Date(a.created_at.replace(' ', 'T')).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '';
            appelsHtml += `<tr>
                <td>${time}</td>
                <td><strong>${esc(a.numero_personne)}</strong></td>
                <td>${resultatLabels[a.resultat] || a.resultat}</td>
                <td>${a.resultat === 'rdv' && a.date_rdv ? a.date_rdv.split('-').reverse().join('/') : '-'}</td>
                <td>${a.resultat === 'rdv' && a.motif_rdv ? esc(a.motif_rdv) : '-'}</td>
                <td>${a.resultat === 'rdv' && a.is_anv == 1 ? '<span class="badge bg-info">ANV</span>' : '-'}</td>
                <td>${esc(a.commentaire) || '-'}</td>
                <td>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet appel ?')">
                        <input type="hidden" name="action" value="delete_appel">
                        <input type="hidden" name="appel_id" value="${a.id}">
                        <input type="hidden" name="seance_id" value="${id}">
                        <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fas fa-times"></i></button>
                    </form>
                </td>
            </tr>`;
        });
        appelsHtml += '</tbody></table>';
    } else {
        appelsHtml = '<p class="text-muted">Aucun appel enregistré. Utilisez le formulaire ci-dessous pour ajouter des appels.</p>';
    }

    let motifOptions = motifs.map(m => `<option value="${m}">${m}</option>`).join('');

    document.getElementById('dossierTitle').innerHTML = `<i class="fas fa-phone-volume"></i> ${esc(s.titre || 'Séance du ' + (s.date_ajout || '').split('-').reverse().join('/'))}`;
    document.getElementById('dossierContent').innerHTML = `
        <!-- Stats du dossier -->
        <div class="row g-3 mb-4">
            <div class="col-md-2"><div class="stat-card text-center"><div class="stat-number">${totalAppels}</div><div class="stat-label">Appels</div></div></div>
            <div class="col-md-2"><div class="stat-card text-center"><div class="stat-number">${repondus}</div><div class="stat-label">Répondus</div></div></div>
            <div class="col-md-2"><div class="stat-card text-center"><div class="stat-number">${repondeurs}</div><div class="stat-label">Répondeurs</div></div></div>
            <div class="col-md-2"><div class="stat-card text-center"><div class="stat-number">${indispos}</div><div class="stat-label">Indisponibles</div></div></div>
            <div class="col-md-1"><div class="stat-card text-center"><div class="stat-number">${rdvs}</div><div class="stat-label">RDV</div></div></div>
            <div class="col-md-1"><div class="stat-card text-center"><div class="stat-number">${anvs}</div><div class="stat-label">ANV</div></div></div>
            <div class="col-md-2"><div class="stat-card text-center"><div class="stat-number">${rdvS} / ${rdvS1} / ${rdvAutres}</div><div class="stat-label">S / S+1 / Autres</div></div></div>
        </div>

        <!-- Formulaire ajout appel -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-plus"></i> Nouvel appel</div>
            <div class="card-body">
                <form method="POST" id="formAppel">
                    <input type="hidden" name="action" value="add_appel">
                    <input type="hidden" name="seance_id" value="${id}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">N° / Nom</label>
                            <input type="text" name="numero_personne" class="form-control" placeholder="Client">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Résultat *</label>
                            <select name="resultat" class="form-select" id="selectResultat" onchange="toggleRdvFields()" required>
                                <option value="repondu">Répondu</option>
                                <option value="repondeur">Répondeur</option>
                                <option value="indisponible">Indisponible</option>
                                <option value="rdv">RDV programmé</option>
                            </select>
                        </div>
                        <div class="col-md-2 rdv-fields" style="display:none">
                            <label class="form-label">Date RDV</label>
                            <input type="date" name="date_rdv" class="form-control">
                        </div>
                        <div class="col-md-2 rdv-fields" style="display:none">
                            <label class="form-label">Motif RDV</label>
                            <select name="motif_rdv" class="form-select">
                                <option value="">--</option>
                                ${motifOptions}
                            </select>
                        </div>
                        <div class="col-md-1 rdv-fields align-items-end" style="display:none">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_anv" class="form-check-input" id="checkAnv" value="1">
                                <label class="form-check-label" for="checkAnv"><strong>ANV</strong></label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Commentaire</label>
                            <input type="text" name="commentaire" class="form-control" placeholder="Optionnel">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-ce w-100"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des appels -->
        <h5 class="mb-3"><i class="fas fa-list"></i> Appels (${totalAppels})</h5>
        ${appelsHtml}

        <hr>
        <div class="d-flex gap-2">
            <button class="btn btn-ce-outline" onclick="editSeance(${id})"><i class="fas fa-edit"></i> Modifier le titre / notes</button>
        </div>
    `;

    new bootstrap.Modal(document.getElementById('dossierModal')).show();

    // Re-bind toggle après injection
    setTimeout(() => {
        const sel = document.getElementById('selectResultat');
        if (sel) toggleRdvFields();
    }, 100);
}

function toggleRdvFields() {
    const sel = document.getElementById('selectResultat');
    if (!sel) return;
    const show = sel.value === 'rdv';
    document.querySelectorAll('.rdv-fields').forEach(el => el.style.display = show ? 'flex' : 'none');
}

function editSeance(id) {
    const s = seancesData.find(x => x.id == id);
    if (!s) return;
    // Fermer le dossier modal
    const dossierModalEl = document.getElementById('dossierModal');
    const dossierModal = bootstrap.Modal.getInstance(dossierModalEl);
    if (dossierModal) dossierModal.hide();

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Titre</label>
                    <input type="text" name="titre" class="form-control" value="${esc(s.titre || '')}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4">${esc(s.notes || '')}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }, 300);
}

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    openDossier(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
