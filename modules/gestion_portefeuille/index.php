<?php
$pageTitle = 'Gestion portefeuille';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Migration
$db->exec("CREATE TABLE IF NOT EXISTS demandes_portefeuille (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    conseiller_id INT NOT NULL,
    token VARCHAR(64) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->exec("CREATE TABLE IF NOT EXISTS demandes_portefeuille_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demande_id INT NOT NULL,
    numero_personne VARCHAR(50) NOT NULL,
    identite_client VARCHAR(200) NOT NULL,
    type_demande ENUM('attribution','suppression') NOT NULL DEFAULT 'attribution',
    motif TEXT,
    traitee TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (demande_id) REFERENCES demandes_portefeuille(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Récupérer les destinataires depuis contacts_equipe (même liste que "Rappels clients")
$conseillers = $db->query("SELECT id, prenom, nom, email FROM contacts_equipe ORDER BY nom, prenom")->fetchAll();

// Validation et envoi d'une nouvelle demande (liste de clients)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    $conseillerId = (int)($_POST['conseiller_id'] ?? 0);
    $lignes = json_decode($_POST['lignes'] ?? '[]', true);

    if (!$conseillerId || !is_array($lignes) || count($lignes) === 0) {
        header('Location: index.php?err=invalide');
        exit;
    }

    $db->beginTransaction();

    $token = bin2hex(random_bytes(32));
    $stmtDem = $db->prepare("INSERT INTO demandes_portefeuille (user_id, conseiller_id, token, created_at) VALUES (?, ?, ?, NOW())");
    $stmtDem->execute([$userId, $conseillerId, $token]);
    $demandeId = (int)$db->lastInsertId();

    $stmtLigne = $db->prepare("INSERT INTO demandes_portefeuille_lignes (demande_id, numero_personne, identite_client, type_demande, motif, traitee, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    // Un client ne peut être présent que dans une seule demande d'attribution active à la fois.
    // Les demandes de suppression ne sont pas concernées par cette règle.
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM demandes_portefeuille_lignes WHERE numero_personne = ? AND type_demande = 'attribution' AND traitee = 0");

    $skipped = [];
    $seenAttribution = [];
    $inserted = 0;

    foreach ($lignes as $l) {
        $numero = trim($l['numero_personne'] ?? '');
        $identite = trim($l['identite_client'] ?? '');
        $type = (($l['type_demande'] ?? '') === 'suppression') ? 'suppression' : 'attribution';
        $motif = trim($l['motif'] ?? '');

        if ($numero === '' || $identite === '') continue;

        if ($type === 'attribution') {
            if (isset($seenAttribution[$numero])) {
                $skipped[] = $identite;
                continue;
            }
            $checkStmt->execute([$numero]);
            if ((int)$checkStmt->fetchColumn() > 0) {
                $skipped[] = $identite;
                continue;
            }
            $seenAttribution[$numero] = true;
        }

        $stmtLigne->execute([$demandeId, $numero, $identite, $type, $motif]);
        $inserted++;
    }

    if ($inserted === 0) {
        $db->rollBack();
        header('Location: index.php?err=doublon&skipped=' . urlencode(implode(', ', $skipped)));
        exit;
    }

    $db->commit();

    header('Location: index.php?created=' . $demandeId . '&skipped=' . urlencode(implode(', ', $skipped)));
    exit;
}

// Suppression d'une demande entière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $db->prepare("DELETE FROM demandes_portefeuille WHERE id = ?")->execute([$id]);
    } else {
        $db->prepare("DELETE FROM demandes_portefeuille WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste des demandes
$stmt = $db->prepare("SELECT d.*, c.prenom AS cons_prenom, c.nom AS cons_nom, c.email AS cons_email,
        COUNT(l.id) AS nb_lignes,
        SUM(CASE WHEN l.traitee = 1 THEN 1 ELSE 0 END) AS nb_traitees,
        SUM(CASE WHEN l.type_demande = 'attribution' THEN 1 ELSE 0 END) AS nb_attribution,
        SUM(CASE WHEN l.type_demande = 'suppression' THEN 1 ELSE 0 END) AS nb_suppression
    FROM demandes_portefeuille d
    LEFT JOIN contacts_equipe c ON d.conseiller_id = c.id
    LEFT JOIN demandes_portefeuille_lignes l ON l.demande_id = d.id
    WHERE d.user_id = ?
    GROUP BY d.id
    ORDER BY d.created_at DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();

// Toutes les lignes des demandes de l'utilisateur (pour affichage détaillé côté JS)
$lignesParDemande = [];
if ($demandes) {
    $ids = array_column($demandes, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmtL = $db->prepare("SELECT * FROM demandes_portefeuille_lignes WHERE demande_id IN ($in) ORDER BY id");
    $stmtL->execute($ids);
    foreach ($stmtL->fetchAll() as $l) {
        $lignesParDemande[$l['demande_id']][] = $l;
    }
}

$nbLignesEnAttente = 0;
foreach ($demandes as $d) {
    $nbLignesEnAttente += ((int)$d['nb_lignes'] - (int)$d['nb_traitees']);
}

$createdId = (int)($_GET['created'] ?? 0);
$skippedList = trim($_GET['skipped'] ?? '');
$err = $_GET['err'] ?? '';
?>

<?php if ($err === 'invalide'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Veuillez sélectionner un destinataire et ajouter au moins un client à la liste.</div>
<?php elseif ($err === 'doublon'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Aucun client n'a pu être ajouté : ils sont déjà présents dans une autre demande d'attribution en cours (<?= e($skippedList) ?>).</div>
<?php elseif ($createdId): ?>
    <div class="alert alert-success">
        <i class="fas fa-check"></i> Demande créée.
        <a href="share_mail.php?id=<?= $createdId ?>" class="btn btn-sm btn-ce ms-2"><i class="fas fa-download"></i> Télécharger le fichier .eml</a>
        <?php if ($skippedList): ?>
            <br><i class="fas fa-exclamation-triangle text-warning"></i> Les clients suivants n'ont pas été ajoutés car déjà présents dans une autre demande d'attribution en cours : <strong><?= e($skippedList) ?></strong>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card<?= $nbLignesEnAttente > 0 ? ' stat-warning' : '' ?>">
            <div class="stat-number"><?= $nbLignesEnAttente ?></div>
            <div class="stat-label">Clients en attente de traitement</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <?php if (empty($conseillers)): ?>
            <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Aucun destinataire dans les contacts équipe. <a href="../contacts/index.php">Ajouter des contacts</a></div>
        <?php else: ?>
            <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle demande</button>
        <?php endif; ?>
    </div>
</div>

<!-- Tableau des demandes -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Demandes de gestion portefeuille</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchDemandes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableDemandes">
        <thead>
            <tr>
                <th>Destinataire</th>
                <th>Clients</th>
                <th>Date</th>
                <th>Avancement</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $d): ?>
            <?php
                $nbLignes = (int)$d['nb_lignes'];
                $nbTraitees = (int)$d['nb_traitees'];
                $complet = $nbLignes > 0 && $nbTraitees == $nbLignes;
            ?>
            <tr>
                <td>
                    <?= e($d['cons_prenom'] . ' ' . $d['cons_nom']) ?>
                    <?php if ($d['cons_email']): ?>
                        <br><small class="text-muted"><?= e($d['cons_email']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $nbLignes ?> client<?= $nbLignes > 1 ? 's' : '' ?>
                    <br><small class="text-muted"><?= (int)$d['nb_attribution'] ?> attribution(s) · <?= (int)$d['nb_suppression'] ?> suppression(s)</small>
                </td>
                <td><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
                <td><?= $nbTraitees ?> / <?= $nbLignes ?></td>
                <td>
                    <?php if ($complet): ?>
                        <span class="badge-fait"><i class="fas fa-check"></i> Traité</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="voirDemande(<?= $d['id'] ?>)" title="Voir le détail"><i class="fas fa-eye"></i></button>
                    <?php if ($d['cons_email']): ?>
                        <a href="share_mail.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-ce-outline" title="Renvoyer par mail"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette demande et tous ses clients ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nouvelle demande -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle demande de gestion portefeuille</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEnvoyer" onsubmit="return prepareEnvoi()">
                    <input type="hidden" name="action" value="envoyer">
                    <input type="hidden" name="lignes" id="lignesInput">

                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <select name="conseiller_id" id="conseillerSelect" class="form-select" required>
                            <option value="">— Sélectionner un destinataire —</option>
                            <?php foreach ($conseillers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?><?= $c['email'] ? ' (' . e($c['email']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>
                    <h6>Ajouter un client à la liste</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">N° de personne</label>
                            <input type="text" id="inputNumero" class="form-control" placeholder="N° client">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom Prénom</label>
                            <input type="text" id="inputIdentite" class="form-control" placeholder="Nom Prénom du client">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type de demande</label>
                            <select id="inputType" class="form-select">
                                <option value="attribution">Attribution</option>
                                <option value="suppression">Suppression</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-ce w-100" onclick="ajouterLigne()"><i class="fas fa-plus"></i> Ajouter</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Raison de la demande</label>
                            <textarea id="inputMotif" class="form-control" rows="2" placeholder="Décrivez la raison de la demande de gestion..."></textarea>
                        </div>
                    </div>

                    <hr>
                    <h6>Clients ajoutés à la liste (<span id="nbLignesLabel">0</span>)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="tableLignes">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Motif</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyLignes">
                                <tr id="rowEmpty"><td colspan="5" class="text-muted text-center">Aucun client ajouté pour l'instant</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-ce"><i class="fas fa-paper-plane"></i> Valider et envoyer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list"></i> Détail de la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchDemandes', 'tableDemandes');

const lignesData = <?= json_encode($lignesParDemande) ?>;

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Constructeur de liste côté client
let lignes = [];

function ajouterLigne() {
    const numero = document.getElementById('inputNumero').value.trim();
    const identite = document.getElementById('inputIdentite').value.trim();
    const type = document.getElementById('inputType').value;
    const motif = document.getElementById('inputMotif').value.trim();

    if (!numero || !identite) {
        alert('Veuillez renseigner le n° de personne et le nom du client.');
        return;
    }
    if (lignes.some(l => l.numero_personne === numero && l.type_demande === type)) {
        alert('Ce client est déjà dans la liste pour ce type de demande.');
        return;
    }

    lignes.push({ numero_personne: numero, identite_client: identite, type_demande: type, motif: motif });
    renderLignes();

    document.getElementById('inputNumero').value = '';
    document.getElementById('inputIdentite').value = '';
    document.getElementById('inputMotif').value = '';
    document.getElementById('inputType').value = 'attribution';
    document.getElementById('inputNumero').focus();
}

function retirerLigne(idx) {
    lignes.splice(idx, 1);
    renderLignes();
}

function renderLignes() {
    const tbody = document.getElementById('tbodyLignes');
    document.getElementById('nbLignesLabel').textContent = lignes.length;
    if (lignes.length === 0) {
        tbody.innerHTML = '<tr id="rowEmpty"><td colspan="5" class="text-muted text-center">Aucun client ajouté pour l\'instant</td></tr>';
        return;
    }
    tbody.innerHTML = lignes.map((l, idx) => `
        <tr>
            <td>${escapeHtml(l.numero_personne)}</td>
            <td>${escapeHtml(l.identite_client)}</td>
            <td>${l.type_demande === 'suppression' ? '<span class="badge bg-danger">Suppression</span>' : '<span class="badge bg-success">Attribution</span>'}</td>
            <td>${escapeHtml(l.motif)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="retirerLigne(${idx})"><i class="fas fa-times"></i></button></td>
        </tr>
    `).join('');
}

function prepareEnvoi() {
    if (!document.getElementById('conseillerSelect').value) {
        alert('Veuillez sélectionner un destinataire.');
        return false;
    }
    if (lignes.length === 0) {
        alert('Veuillez ajouter au moins un client à la liste.');
        return false;
    }
    document.getElementById('lignesInput').value = JSON.stringify(lignes);
    return true;
}

function voirDemande(id) {
    const rows = lignesData[id] || [];
    const html = rows.length === 0 ? '<p class="text-muted">Aucun client dans cette demande.</p>' : `
        <table class="table table-sm">
            <thead><tr><th>N°</th><th>Client</th><th>Type</th><th>Motif</th><th>Statut</th></tr></thead>
            <tbody>
                ${rows.map(l => `
                    <tr>
                        <td>${escapeHtml(l.numero_personne)}</td>
                        <td>${escapeHtml(l.identite_client)}</td>
                        <td>${l.type_demande === 'suppression' ? '<span class="badge bg-danger">Suppression</span>' : '<span class="badge bg-success">Attribution</span>'}</td>
                        <td>${escapeHtml(l.motif)}</td>
                        <td>${l.traitee == 1 ? '<span class="badge-fait">Traité</span>' : '<span class="badge bg-warning text-dark">En attente</span>'}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
    document.getElementById('detailContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    voirDemande(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
