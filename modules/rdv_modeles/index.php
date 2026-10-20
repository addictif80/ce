<?php
$pageTitle = 'Trames d\'entretien (modèles de RDV)';
require_once __DIR__ . '/../../templates/header.php';

$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

$db->exec("CREATE TABLE IF NOT EXISTS modeles_rdv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_modele VARCHAR(255) NOT NULL,
    categorie VARCHAR(100) DEFAULT '',
    objectif VARCHAR(500) DEFAULT '',
    duree_min INT DEFAULT 30,
    trame LONGTEXT,
    checklist TEXT,
    usage_public TINYINT(1) DEFAULT 0,
    approved TINYINT(1) DEFAULT 0,
    approved_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mrdv_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/** Nettoyage HTML de la trame (balises de mise en forme uniquement, sans attributs). */
function rdvClean($html) {
    $h = trim((string)$html);
    $h = preg_replace('#<(script|style)\b.*?</\1>#is', '', $h);
    $h = strip_tags($h, '<p><br><b><strong><i><em><u><ul><ol><li><div><h4><h5><h6>');
    return preg_replace('/<(\w+)\s[^>]*>/', '<$1>', $h); // aucun attribut (pas de onclick, style, etc.)
}

function rdvChecklistArray($json) {
    $arr = json_decode((string)$json, true);
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $v) {
        $v = trim((string)$v);
        if ($v !== '') $out[] = $v;
    }
    return $out;
}

$rdvCategories = array_merge(getCategoriesProduction(), ['Découverte', 'Suivi', 'Réclamation', 'Crédit immobilier', 'Autre']);

// ---------- POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim((string)($_POST['nom_modele'] ?? ''));
        $categorie = trim((string)($_POST['categorie'] ?? ''));
        $objectif = mb_substr(trim((string)($_POST['objectif'] ?? '')), 0, 500);
        $duree = max(0, (int)($_POST['duree_min'] ?? 30));
        $trame = rdvClean($_POST['trame'] ?? '');
        $checklist = json_encode(array_values(array_filter(array_map('trim', (array)($_POST['checklist'] ?? [])), fn($v) => $v !== '')), JSON_UNESCAPED_UNICODE);
        $public = isset($_POST['usage_public']) ? 1 : 0;

        if ($nom !== '' && trim(strip_tags($trame)) !== '') {
            if ($id > 0) {
                if ($isUserAdmin) {
                    $db->prepare("UPDATE modeles_rdv SET nom_modele=?, categorie=?, objectif=?, duree_min=?, trame=?, checklist=?, usage_public=? WHERE id=?")
                       ->execute([$nom, $categorie, $objectif, $duree, $trame, $checklist, $public, $id]);
                } else {
                    $db->prepare("UPDATE modeles_rdv SET nom_modele=?, categorie=?, objectif=?, duree_min=?, trame=?, checklist=?, usage_public=? WHERE id=? AND user_id=?")
                       ->execute([$nom, $categorie, $objectif, $duree, $trame, $checklist, $public, $id, $userId]);
                }
            } else {
                $db->prepare("INSERT INTO modeles_rdv (user_id, nom_modele, categorie, objectif, duree_min, trame, checklist, usage_public) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$userId, $nom, $categorie, $objectif, $duree, $trame, $checklist, $public]);
            }
        }
    } elseif ($act === 'delete') {
        $db->prepare("DELETE FROM modeles_rdv WHERE id = ? AND (user_id = ? OR ? = 1)")
           ->execute([(int)($_POST['id'] ?? 0), $userId, $isUserAdmin ? 1 : 0]);
    } elseif ($act === 'approve' && $isUserAdmin) {
        $db->prepare("UPDATE modeles_rdv SET approved = IF(approved=1,0,1), approved_by = ? WHERE id = ?")
           ->execute([$userId, (int)($_POST['id'] ?? 0)]);
    }
    header('Location: index.php?saved=1');
    exit;
}

// ---------- Données ----------
$stmt = $db->prepare("SELECT m.*, u.prenom, u.nom AS auteur_nom FROM modeles_rdv m LEFT JOIN users u ON u.id = m.user_id
    WHERE m.user_id = ? OR m.approved = 1 ORDER BY m.updated_at DESC");
$stmt->execute([$userId]);
$modeles = $stmt->fetchAll();

$jsModels = [];
foreach ($modeles as $m) {
    $jsModels[(int)$m['id']] = [
        'id' => (int)$m['id'],
        'nom' => $m['nom_modele'],
        'categorie' => $m['categorie'],
        'objectif' => $m['objectif'],
        'duree' => (int)$m['duree_min'],
        'trame' => $m['trame'],
        'checklist' => rdvChecklistArray($m['checklist']),
        'owner' => ((int)$m['user_id'] === $userId),
        'approved' => (int)$m['approved'],
    ];
}
?>

<style>
.wysiwyg-toolbar { display:flex; flex-wrap:wrap; gap:3px; padding:6px; border:1px solid #dee2e6; border-bottom:none; border-radius:6px 6px 0 0; background:#f8f9fa; }
.wysiwyg-toolbar button { border:1px solid transparent; background:transparent; border-radius:4px; width:30px; height:30px; cursor:pointer; color:#495057; }
.wysiwyg-toolbar button:hover { background:#e9ecef; border-color:#adb5bd; }
.wysiwyg-toolbar .toolbar-separator { width:1px; height:24px; background:#dee2e6; margin:0 2px; align-self:center; }
.wysiwyg-editor { min-height:220px; max-height:440px; overflow-y:auto; border:1px solid #dee2e6; border-radius:0 0 6px 6px; padding:12px; background:#fff; outline:none; }
.wysiwyg-editor:focus { border-color:var(--ce-red); box-shadow:0 0 0 .2rem rgba(220,53,69,.12); }
.rdv-trame { line-height:1.7; }
.rdv-trame ul, .rdv-trame ol { padding-left:1.4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-1"><i class="fas fa-clipboard-list"></i> Trames d'entretien</h4>
        <div class="text-muted">Bibliothèque de modèles de rendez-vous : objectif, trame d'entretien et points de contrôle.</div>
    </div>
    <button class="btn btn-ce" onclick="openForm(0)"><i class="fas fa-plus"></i> Nouvelle trame</button>
</div>

<?php if (!empty($_GET['saved'])): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check"></i> Enregistré.</div>
<?php endif; ?>

<?php if (!$modeles): ?>
    <div class="alert alert-info">Aucune trame pour le moment. Créez la première avec le bouton « Nouvelle trame ».</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($modeles as $m): $own = ((int)$m['user_id'] === $userId); ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <h5 class="mb-1"><?= e($m['nom_modele']) ?></h5>
                            <?php if ((int)$m['approved']): ?><span class="badge bg-success" title="Validée par un administrateur"><i class="fas fa-check"></i> partagée</span><?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <?php if ($m['categorie'] !== ''): ?><span class="badge bg-secondary"><?= e($m['categorie']) ?></span><?php endif; ?>
                            <span class="badge bg-light text-dark border"><i class="fas fa-clock"></i> <?= (int)$m['duree_min'] ?> min</span>
                            <?php if (!$own): ?><span class="badge bg-light text-dark border">par <?= e(($m['prenom'] ?? '') . ' ' . ($m['auteur_nom'] ?? '')) ?></span><?php endif; ?>
                        </div>
                        <?php if ($m['objectif'] !== ''): ?>
                            <p class="small text-muted mb-2"><?= e(excerpt($m['objectif'], 140)) ?></p>
                        <?php endif; ?>
                        <div class="small text-muted mb-3">
                            <i class="fas fa-list-check"></i> <?= count(rdvChecklistArray($m['checklist'])) ?> point(s) de contrôle
                        </div>
                        <div class="mt-auto d-flex flex-wrap gap-1">
                            <button class="btn btn-sm btn-ce" onclick="openPrep(<?= (int)$m['id'] ?>)"><i class="fas fa-play"></i> Préparer</button>
                            <?php if ($own || $isUserAdmin): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="openForm(<?= (int)$m['id'] ?>)"><i class="fas fa-pen"></i></button>
                            <?php endif; ?>
                            <?php if ($isUserAdmin): ?>
                                <form method="POST" class="d-inline" title="Valider / retirer le partage">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($own || $isUserAdmin): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette trame ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal formulaire -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formRdv" onsubmit="return prepareForm()">
                <div class="modal-header">
                    <h5 class="modal-title" id="formTitle"><i class="fas fa-clipboard-list"></i> Nouvelle trame</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="fId" value="0">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nom de la trame *</label>
                            <input type="text" class="form-control" name="nom_modele" id="fNom" required maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="categorie" id="fCategorie">
                                <option value="">—</option>
                                <?php foreach ($rdvCategories as $cat): ?><option value="<?= e($cat) ?>"><?= e($cat) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Durée (min)</label>
                            <input type="number" min="0" class="form-control" name="duree_min" id="fDuree" value="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Objectif de l'entretien</label>
                            <input type="text" class="form-control" name="objectif" id="fObjectif" maxlength="500">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Trame d'entretien *</label>
                            <div id="toolbar"></div>
                            <div class="wysiwyg-editor rdv-trame" id="fEditor" contenteditable="true"></div>
                            <textarea name="trame" id="fTrame" style="display:none;"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Points de contrôle (checklist)</label>
                            <div id="checklistRows"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addChecklistRow('')"><i class="fas fa-plus"></i> Ajouter un point</button>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="usage_public" id="fPublic" value="1">
                                <label class="form-check-label" for="fPublic">Proposer le partage avec les autres conseillers (validation par un administrateur)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal préparation -->
<div class="modal fade" id="prepModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prepTitle"><i class="fas fa-play"></i> Préparation du RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="prepMeta" class="mb-3"></div>
                <div class="rdv-trame" id="prepTrame"></div>
                <div id="prepChecklistWrap" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-ce" onclick="printPrep()"><i class="fas fa-print"></i> Imprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
const MODELES = <?= json_encode($jsModels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const TOOLBAR_HTML = '<div class="wysiwyg-toolbar">'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'bold\')" title="Gras"><i class="fas fa-bold"></i></button>'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'italic\')" title="Italique"><i class="fas fa-italic"></i></button>'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'underline\')" title="Souligné"><i class="fas fa-underline"></i></button>'
    + '<span class="toolbar-separator"></span>'
    + '<select class="form-select form-select-sm" style="width:auto" onchange="execWysVal(\'formatBlock\', this.value); this.selectedIndex=0;">'
    + '<option value="">Style</option><option value="h4">Titre</option><option value="h5">Sous-titre</option><option value="p">Normal</option></select>'
    + '<span class="toolbar-separator"></span>'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'insertUnorderedList\')" title="Puces"><i class="fas fa-list-ul"></i></button>'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'insertOrderedList\')" title="Numéroté"><i class="fas fa-list-ol"></i></button>'
    + '<button type="button" onmousedown="event.preventDefault()" onclick="execWys(\'removeFormat\')" title="Effacer la mise en forme"><i class="fas fa-eraser"></i></button>'
    + '</div>';

document.getElementById('toolbar').innerHTML = TOOLBAR_HTML;
const formModal = new bootstrap.Modal(document.getElementById('formModal'));
const prepModal = new bootstrap.Modal(document.getElementById('prepModal'));

function execWys(cmd) { document.execCommand(cmd, false, null); }
function execWysVal(cmd, val) { document.execCommand(cmd, false, val); }

function addChecklistRow(val) {
    const wrap = document.getElementById('checklistRows');
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    const inp = document.createElement('input');
    inp.type = 'text'; inp.className = 'form-control'; inp.name = 'checklist[]'; inp.value = val || '';
    inp.placeholder = 'Point à vérifier pendant le RDV';
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'btn btn-outline-danger';
    btn.innerHTML = '<i class="fas fa-times"></i>';
    btn.onclick = () => div.remove();
    div.appendChild(inp); div.appendChild(btn);
    wrap.appendChild(div);
}

function openForm(id) {
    const m = MODELES[id] || null;
    document.getElementById('fId').value = m ? m.id : 0;
    document.getElementById('fNom').value = m ? m.nom : '';
    document.getElementById('fCategorie').value = m ? m.categorie : '';
    document.getElementById('fObjectif').value = m ? m.objectif : '';
    document.getElementById('fDuree').value = m ? m.duree : 30;
    document.getElementById('fEditor').innerHTML = m ? (m.trame || '') : '';
    document.getElementById('fPublic').checked = m ? true : false;
    const wrap = document.getElementById('checklistRows');
    wrap.innerHTML = '';
    if (m && m.checklist && m.checklist.length) m.checklist.forEach(v => addChecklistRow(v));
    else addChecklistRow('');
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-clipboard-list"></i> ' + (m ? 'Modifier la trame' : 'Nouvelle trame');
    formModal.show();
}

function prepareForm() {
    const editor = document.getElementById('fEditor');
    const content = editor.innerHTML.trim().replace(/^<br\s*\/?>$/i, '');
    if (!content) { alert('La trame ne peut pas être vide.'); return false; }
    document.getElementById('fTrame').value = content;
    return true;
}

function openPrep(id) {
    const m = MODELES[id];
    if (!m) return;
    document.getElementById('prepTitle').innerHTML = '<i class="fas fa-play"></i> ' + escapeHtml(m.nom);
    let meta = '';
    if (m.categorie) meta += '<span class="badge bg-secondary me-1">' + escapeHtml(m.categorie) + '</span>';
    meta += '<span class="badge bg-light text-dark border me-1"><i class="fas fa-clock"></i> ' + m.duree + ' min</span>';
    if (m.objectif) meta += '<div class="mt-2"><strong>Objectif :</strong> ' + escapeHtml(m.objectif) + '</div>';
    document.getElementById('prepMeta').innerHTML = meta;
    document.getElementById('prepTrame').innerHTML = m.trame || '';
    const wrap = document.getElementById('prepChecklistWrap');
    if (m.checklist && m.checklist.length) {
        let h = '<h6><i class="fas fa-list-check"></i> Points de contrôle</h6><div class="list-group">';
        m.checklist.forEach((c, i) => {
            h += '<label class="list-group-item"><input class="form-check-input me-2" type="checkbox" id="prepchk' + i + '"> ' + escapeHtml(c) + '</label>';
        });
        h += '</div>';
        wrap.innerHTML = h;
    } else wrap.innerHTML = '';
    prepModal.show();
}

function printPrep() {
    const title = document.getElementById('prepTitle').textContent;
    const safeTitle = escapeHtml(title);
    const body = document.getElementById('prepModal').querySelector('.modal-body').innerHTML;
    const w = window.open('', '_blank');
    w.document.write('<html><head><title>' + safeTitle + '</title>'
        + '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">'
        + '<style>body{padding:24px}.rdv-trame{line-height:1.7}</style></head><body>'
        + '<h3>' + safeTitle + '</h3>' + body + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 400);
}

function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
