<?php
$pageTitle = 'Vue Kanban';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Ajout rapide - instance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_instance') {
    $cats = isset($_POST['categories']) ? implode(', ', $_POST['categories']) : '';
    $stmt = $db->prepare("INSERT INTO instances (user_id, numero_personne, date_echeance, categories, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['date_echeance'] ?: null, $cats, $_POST['details'] ?? '']);
    header('Location: index.php');
    exit;
}

// Ajout rapide - demande de rappel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_rappel') {
    $stmt = $db->prepare("INSERT INTO demandes_rappel (user_id, numero_personne, motif) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['motif'] ?? '']);
    header('Location: index.php');
    exit;
}

// Ajout rapide - demande client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_demande_client') {
    $stmt = $db->prepare("INSERT INTO demandes_clients (user_id, numero_personne, details_demande, date_envoi, service) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $_POST['numero_personne'], $_POST['details_demande'] ?? '', $_POST['date_envoi'] ?: null, $_POST['service'] ?? '']);
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM instances WHERE user_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$instances = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM demandes_rappel WHERE user_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$rappels = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM demandes_clients WHERE user_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$demandesClients = $stmt->fetchAll();

$cards = [];
foreach ($instances as $i) {
    $cards[] = [
        'type' => 'instances', 'id' => (int)$i['id'],
        'label' => 'Instance', 'icon' => 'fa-tasks', 'color' => '#0d6efd',
        'title' => $i['numero_personne'], 'subtitle' => $i['categories'] ?: '',
        'excerpt' => excerpt($i['details'] ?? '', 90),
        'echeance' => $i['date_echeance'], 'echeanceClass' => getEcheanceClass($i['date_echeance']),
        'date_ajout' => $i['date_ajout'],
        'done' => $i['statut'] === 'fait',
        'link' => '../instances/index.php?open=' . (int)$i['id'],
    ];
}
foreach ($rappels as $r) {
    $cards[] = [
        'type' => 'rappels', 'id' => (int)$r['id'],
        'label' => 'Rappel', 'icon' => 'fa-phone-alt', 'color' => '#6f42c1',
        'title' => $r['numero_personne'], 'subtitle' => '',
        'excerpt' => excerpt($r['motif'] ?? '', 90),
        'echeance' => null, 'echeanceClass' => '',
        'date_ajout' => $r['date_ajout'],
        'done' => (bool)$r['traitee'],
        'link' => '../rappels/index.php?open=' . (int)$r['id'],
    ];
}
foreach ($demandesClients as $d) {
    $cards[] = [
        'type' => 'demandes_clients', 'id' => (int)$d['id'],
        'label' => 'Demande client', 'icon' => 'fa-headset', 'color' => '#20c997',
        'title' => $d['numero_personne'], 'subtitle' => $d['service'] ?: '',
        'excerpt' => excerpt($d['details_demande'] ?? '', 90),
        'echeance' => null, 'echeanceClass' => '',
        'date_ajout' => $d['date_ajout'],
        'done' => (bool)$d['traitee'],
        'link' => '../demandes_clients/index.php?open=' . (int)$d['id'],
    ];
}

usort($cards, fn($a, $b) => strcmp($b['date_ajout'], $a['date_ajout']));
$cardsTodo = array_values(array_filter($cards, fn($c) => !$c['done']));
$cardsDone = array_values(array_filter($cards, fn($c) => $c['done']));

$categories = ['Bancarisation', 'Epargne', 'IARD', 'Prévoyance', 'Placement', 'Crédit conso', 'Crédit immo'];
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($cardsTodo) ?></div>
            <div class="stat-label">À traiter</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-number"><?= count($cardsDone) ?></div>
            <div class="stat-label">Terminé</div>
        </div>
    </div>
    <div class="col-md-6 d-flex align-items-center flex-wrap gap-2">
        <button class="btn btn-ce-outline" data-bs-toggle="modal" data-bs-target="#addInstanceModal"><i class="fas fa-plus"></i> Instance</button>
        <button class="btn btn-ce-outline" data-bs-toggle="modal" data-bs-target="#addRappelModal"><i class="fas fa-plus"></i> Demande de rappel</button>
        <button class="btn btn-ce-outline" data-bs-toggle="modal" data-bs-target="#addDemandeModal"><i class="fas fa-plus"></i> Demande client</button>
    </div>
</div>

<div class="kanban-board">
    <div class="kanban-column" id="col-todo" data-done="0">
        <div class="kanban-column-header"><i class="fas fa-clock"></i> À traiter <span class="badge bg-secondary"><?= count($cardsTodo) ?></span></div>
        <div class="kanban-column-body" id="body-todo">
            <?php foreach ($cardsTodo as $c): ?>
                <?= renderKanbanCard($c) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="kanban-column" id="col-done" data-done="1">
        <div class="kanban-column-header"><i class="fas fa-check-circle"></i> Terminé <span class="badge bg-secondary"><?= count($cardsDone) ?></span></div>
        <div class="kanban-column-body" id="body-done">
            <?php foreach ($cardsDone as $c): ?>
                <?= renderKanbanCard($c) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
function renderKanbanCard($c) {
    ob_start();
    ?>
    <div class="kanban-card" draggable="true" data-type="<?= e($c['type']) ?>" data-id="<?= $c['id'] ?>">
        <div class="kanban-card-type" style="color:<?= e($c['color']) ?>"><i class="fas <?= e($c['icon']) ?>"></i> <?= e($c['label']) ?></div>
        <div class="kanban-card-title"><?= e($c['title']) ?></div>
        <?php if ($c['subtitle']): ?><div class="kanban-card-subtitle"><?= e($c['subtitle']) ?></div><?php endif; ?>
        <?php if ($c['excerpt']): ?><div class="kanban-card-excerpt"><?= e($c['excerpt']) ?></div><?php endif; ?>
        <?php if ($c['echeance']): ?>
            <div class="mt-1"><span class="<?= $c['echeanceClass'] ?> px-2 py-1 rounded" style="font-size:12px;"><i class="fas fa-calendar"></i> <?= formatDate($c['echeance']) ?></span></div>
        <?php endif; ?>
        <div class="kanban-card-footer">
            <a href="<?= e($c['link']) ?>" class="btn btn-sm btn-ce-outline"><i class="fas fa-eye"></i> Ouvrir</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!-- Modal Ajout Instance -->
<div class="modal fade" id="addInstanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tasks"></i> Nouvelle instance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_instance">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° Personne / Nom</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'échéance</label>
                            <input type="date" name="date_echeance" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Catégorie</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat ?>" id="kanban_cat_<?= str_replace(' ', '_', $cat) ?>">
                                        <label class="form-check-label" for="kanban_cat_<?= str_replace(' ', '_', $cat) ?>"><?= $cat ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Détails</label>
                            <textarea name="details" class="form-control" rows="4"></textarea>
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

<!-- Modal Ajout Rappel -->
<div class="modal fade" id="addRappelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-phone-alt"></i> Nouvelle demande de rappel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_rappel">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">N° Personne / Nom</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif</label>
                            <textarea name="motif" class="form-control" rows="4" placeholder="Décrivez le motif du rappel..."></textarea>
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

<!-- Modal Ajout Demande client -->
<div class="modal fade" id="addDemandeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-headset"></i> Nouvelle demande client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_demande_client">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° Personne</label>
                            <input type="text" name="numero_personne" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'envoi</label>
                            <input type="date" name="date_envoi" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <input type="text" name="service" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Détails de la demande</label>
                            <textarea name="details_demande" class="form-control" rows="4" required></textarea>
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

<style>
.kanban-board { display: flex; gap: 18px; align-items: flex-start; overflow-x: auto; padding-bottom: 10px; }
.kanban-column { flex: 1 1 0; min-width: 320px; background: #f4f5f7; border-radius: 10px; display: flex; flex-direction: column; max-height: calc(100vh - 260px); }
.kanban-column-header { padding: 12px 16px; font-weight: 600; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; gap: 8px; }
.kanban-column-body { padding: 10px; overflow-y: auto; flex: 1; min-height: 120px; }
.kanban-column-body.drag-over { background: #e9ecef; border-radius: 8px; }
.kanban-card { background: #fff; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: grab; border-left: 3px solid transparent; }
.kanban-card:active { cursor: grabbing; }
.kanban-card.dragging { opacity: 0.4; }
.kanban-card-type { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px; }
.kanban-card-title { font-weight: 600; font-size: 14px; }
.kanban-card-subtitle { font-size: 12px; color: #666; }
.kanban-card-excerpt { font-size: 12px; color: #888; margin-top: 4px; }
.kanban-card-footer { margin-top: 8px; }
</style>

<script>
document.querySelectorAll('.kanban-card').forEach(card => {
    card.addEventListener('dragstart', () => card.classList.add('dragging'));
    card.addEventListener('dragend', () => card.classList.remove('dragging'));
});

document.querySelectorAll('.kanban-column-body').forEach(body => {
    body.addEventListener('dragover', e => {
        e.preventDefault();
        body.classList.add('drag-over');
    });
    body.addEventListener('dragleave', () => body.classList.remove('drag-over'));
    body.addEventListener('drop', e => {
        e.preventDefault();
        body.classList.remove('drag-over');
        const dragging = document.querySelector('.kanban-card.dragging');
        if (!dragging) return;
        const column = body.closest('.kanban-column');
        const done = column.dataset.done === '1';
        const type = dragging.dataset.type;
        const id = dragging.dataset.id;

        body.appendChild(dragging);
        updateColumnCounts();

        fetch('toggle_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '&done=' + (done ? '1' : '0')
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                alert('Impossible de mettre à jour cet élément.');
                location.reload();
            }
        }).catch(() => location.reload());
    });
});

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const count = col.querySelectorAll('.kanban-card').length;
        col.querySelector('.kanban-column-header .badge').textContent = count;
    });
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
