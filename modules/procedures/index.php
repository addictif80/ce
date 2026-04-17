<?php
$pageTitle = 'Procédures';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$isUserAdmin = isAdmin();

// Auto-add approval columns
try { $db->exec("ALTER TABLE procedures ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE procedures ADD COLUMN approved_by INT DEFAULT NULL"); } catch (Exception $e) {}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $miseEnAvant = isset($_POST['mise_en_avant']) ? 1 : 0;
    $lienPartage = generateShareLink();
    $stmt = $db->prepare("INSERT INTO procedures (user_id, nom, texte, mise_en_avant, lien_partage, approved, approved_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $userId, $_POST['nom'], $_POST['texte'], $miseEnAvant, $lienPartage,
        $isUserAdmin ? 1 : 0,
        $isUserAdmin ? $userId : null
    ]);
    if ($isUserAdmin) {
        $nom = trim($_POST['nom']);
        $stmtAll = $db->prepare("SELECT id FROM users WHERE id != ?");
        $stmtAll->execute([$userId]);
        foreach ($stmtAll->fetchAll() as $u) {
            createNotification($u['id'], 'procedure', 'Nouvelle procédure : ' . $nom, '', APP_URL . '/modules/procedures/index.php');
        }
    }
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $miseEnAvant = isset($_POST['mise_en_avant']) ? 1 : 0;
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("UPDATE procedures SET nom = ?, texte = ?, mise_en_avant = ? WHERE id = ?");
        $stmt->execute([$_POST['nom'], $_POST['texte'], $miseEnAvant, $id]);
    } else {
        $stmt = $db->prepare("UPDATE procedures SET nom = ?, texte = ?, mise_en_avant = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['nom'], $_POST['texte'], $miseEnAvant, $id, $userId]);
    }
    header('Location: index.php?open=' . $id);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM procedures WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM procedures WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste : ses propres procédures + toutes les procédures approuvées
$stmt = $db->prepare("SELECT p.*, u.nom AS author_nom, u.prenom AS author_prenom
    FROM procedures p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? OR p.approved = 1
    ORDER BY p.mise_en_avant DESC, p.created_at DESC");
$stmt->execute([$userId]);
$procedures = $stmt->fetchAll();

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/view.php';
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($procedures) ?></div>
            <div class="stat-label">Procédures disponibles</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle procédure</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les procédures</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchProcedures" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableProcedures">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Mise en avant</th>
                <th>Auteur</th>
                <th>Statut</th>
                <th>Lien de partage</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($procedures as $proc):
            $isOwn = ($proc['user_id'] == $userId);
        ?>
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
                    <?php if ($isOwn): ?>
                        <span class="badge bg-primary">Moi</span>
                    <?php else: ?>
                        <?= e($proc['author_prenom'] . ' ' . $proc['author_nom']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($proc['approved']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Approuvé</span>
                    <?php elseif ($isOwn): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="input-group input-group-sm" style="max-width:320px;">
                        <input type="text" class="form-control form-control-sm" value="<?= e($baseUrl . '?token=' . $proc['lien_partage']) ?>" readonly id="link_<?= $proc['id'] ?>">
                        <button class="btn btn-ce-outline btn-sm" onclick="copyLink(<?= $proc['id'] ?>)" title="Copier"><i class="fas fa-copy"></i></button>
                    </div>
                </td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $proc['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <a href="share_mail.php?id=<?= $proc['id'] ?>" class="btn btn-sm btn-ce-outline" title="Partager par mail"><i class="fas fa-envelope"></i></a>
                    <?php if ($isOwn || $isUserAdmin): ?>
                    <button class="btn btn-sm btn-ce-outline" onclick="editProcedure(<?= $proc['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette procédure ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $proc['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle procédure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" onsubmit="return prepareWysiwyg('addEditor', 'addTexte')">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la procédure</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mise_en_avant" id="addMiseEnAvant" value="1">
                                <label class="form-check-label" for="addMiseEnAvant">Mise en avant</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Texte de la procédure</label>
                            <div id="addToolbarContainer"></div>
                            <div class="wysiwyg-editor" id="addEditor" contenteditable="true"></div>
                            <textarea name="texte" id="addTexte" style="display:none;"></textarea>
                        </div>
                        <?php if (!$isUserAdmin): ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Cette procédure sera soumise à validation par un administrateur avant d'être visible par tous.</div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la procédure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la procédure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<!-- Dialog insertion média (overlay fixe, passe au-dessus des modales Bootstrap) -->
<div id="mediaInsertDialog" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;padding:24px;max-width:520px;width:calc(100% - 40px);max-height:82vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h5 id="mediaModalTitle" style="margin:0;color:#333;"></h5>
            <button type="button" onclick="closeMediaDialog()" style="background:none;border:none;font-size:24px;color:#666;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" id="tabUploadBtn" onclick="switchMediaTab('upload')"><i class="fas fa-upload me-1"></i> Depuis mon PC</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tabUrlBtn" onclick="switchMediaTab('url')"><i class="fas fa-link me-1"></i> URL</button>
            </li>
        </ul>
        <div id="mediaTabUpload">
            <label class="form-label">Choisir un fichier</label>
            <input type="file" class="form-control" id="mediaFileInput" accept="image/*" onchange="handleMediaUpload(this)">
            <div class="text-muted small mt-1">Images : JPG, PNG, GIF, WEBP — PDF (max 10 Mo)</div>
            <div id="uploadProgress" class="mt-2 text-primary" style="display:none;"><i class="fas fa-spinner fa-spin me-1"></i> Téléchargement en cours…</div>
        </div>
        <div id="mediaTabUrl" style="display:none;">
            <label class="form-label">URL directe du fichier</label>
            <input type="url" class="form-control" id="mediaUrlInput" placeholder="https://exemple.com/image.jpg">
            <div class="text-muted small mt-1">Copiez l'URL directe de l'image ou du PDF.</div>
        </div>
        <div id="mediaPreview" class="mt-3"></div>
        <input type="hidden" id="uploadedMediaUrl" value="">
        <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeMediaDialog()">Annuler</button>
            <button type="button" class="btn btn-ce" onclick="confirmInsertMedia()"><i class="fas fa-check me-1"></i> Insérer</button>
        </div>
    </div>
</div>

<style>
.wysiwyg-toolbar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 6px 6px 0 0;
    padding: 6px 10px;
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
}
.wysiwyg-toolbar button {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #333;
    font-size: 13px;
    transition: all 0.15s;
    flex-shrink: 0;
}
.wysiwyg-toolbar button:hover { background: #e9ecef; border-color: #adb5bd; }
.wysiwyg-select {
    height: 30px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 12px;
    padding: 0 4px;
    background: #fff;
    cursor: pointer;
}
.wysiwyg-color {
    width: 30px;
    height: 30px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1px;
    cursor: pointer;
}
.toolbar-separator { width: 1px; height: 24px; background: #dee2e6; margin: 0 2px; flex-shrink: 0; }
.wysiwyg-editor {
    border: 1px solid #dee2e6;
    border-radius: 0 0 6px 6px;
    min-height: 300px;
    max-height: 520px;
    overflow-y: auto;
    padding: 15px;
    background: #fff;
    font-size: 14px;
    line-height: 1.6;
}
.wysiwyg-editor:focus {
    outline: none;
    border-color: var(--ce-red);
    box-shadow: 0 0 0 0.2rem rgba(228,0,43,0.15);
}
.wysiwyg-editor img { max-width: 100%; height: auto; border-radius: 4px; margin: 4px 0; }
.wysiwyg-content { line-height: 1.8; font-size: 15px; }
.wysiwyg-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0; display: block; }
.wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 { color: var(--ce-red); margin-top: 16px; margin-bottom: 8px; }
.wysiwyg-content ul, .wysiwyg-content ol { padding-left: 24px; }
.wysiwyg-content object, .wysiwyg-content embed { max-width: 100%; display: block; margin: 8px 0; }
</style>

<script>
filterTable('searchProcedures', 'tableProcedures');

const proceduresData = <?= json_encode($procedures) ?>;
const baseShareUrl = <?= json_encode($baseUrl) ?>;

let mediaInsertPrefix = 'add';
let mediaInsertType = 'image';
let savedSelection = null;

function escapeHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Rend le contenu : HTML si contient des balises, sinon texte brut avec pre-wrap
function renderContent(html) {
    if (!html) return '<em class="text-muted">Aucun contenu</em>';
    if (/<[a-z][\s\S]*>/i.test(html)) {
        return '<div class="wysiwyg-content">' + html + '</div>';
    }
    const d = document.createElement('div');
    d.textContent = html;
    return '<div style="white-space:pre-wrap;line-height:1.8;">' + d.innerHTML + '</div>';
}

// WYSIWYG commands
function execWys(cmd) { document.execCommand(cmd, false, null); }
function execWysVal(cmd, val) { document.execCommand(cmd, false, val); }

function getWysiwygToolbar(prefix) {
    return `<div class="wysiwyg-toolbar">
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('bold')" title="Gras"><i class="fas fa-bold"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('italic')" title="Italique"><i class="fas fa-italic"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('strikeThrough')" title="Barré"><i class="fas fa-strikethrough"></i></button>
        <span class="toolbar-separator"></span>
        <select class="wysiwyg-select" onchange="execWysVal('formatBlock', this.value); this.selectedIndex=0;">
            <option value="">Style</option>
            <option value="h1">Titre 1</option>
            <option value="h2">Titre 2</option>
            <option value="h3">Titre 3</option>
            <option value="p">Normal</option>
        </select>
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('justifyLeft')" title="Gauche"><i class="fas fa-align-left"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('justifyCenter')" title="Centrer"><i class="fas fa-align-center"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('justifyRight')" title="Droite"><i class="fas fa-align-right"></i></button>
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('insertUnorderedList')" title="Puces"><i class="fas fa-list-ul"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('insertOrderedList')" title="Numéroté"><i class="fas fa-list-ol"></i></button>
        <span class="toolbar-separator"></span>
        <input type="color" value="#000000" onchange="execWysVal('foreColor', this.value)" title="Couleur du texte" class="wysiwyg-color">
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="openMediaModal('image', '${prefix}')" title="Insérer une image"><i class="fas fa-image"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="openMediaModal('pdf', '${prefix}')" title="Insérer un PDF"><i class="fas fa-file-pdf"></i></button>
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('removeFormat')" title="Effacer la mise en forme"><i class="fas fa-eraser"></i></button>
    </div>`;
}

function prepareWysiwyg(editorId, textareaId) {
    const editor = document.getElementById(editorId);
    const textarea = document.getElementById(textareaId);
    if (!editor || !textarea) return true;
    const content = editor.innerHTML.trim().replace(/^<br\s*\/?>$/i, '');
    if (!content) {
        alert('Le contenu ne peut pas être vide.');
        editor.focus();
        return false;
    }
    textarea.value = content;
    return true;
}

// --- Média ---
function openMediaModal(type, prefix) {
    mediaInsertType = type;
    mediaInsertPrefix = prefix;
    const sel = window.getSelection();
    savedSelection = (sel && sel.rangeCount > 0) ? sel.getRangeAt(0).cloneRange() : null;
    document.getElementById('mediaModalTitle').innerHTML =
        type === 'image'
            ? '<i class="fas fa-image me-2" style="color:var(--ce-red);"></i>Insérer une image'
            : '<i class="fas fa-file-pdf me-2" style="color:var(--ce-red);"></i>Insérer un PDF';
    document.getElementById('mediaFileInput').accept =
        type === 'image' ? 'image/jpeg,image/png,image/gif,image/webp' : '.pdf,application/pdf';
    document.getElementById('mediaFileInput').value = '';
    document.getElementById('mediaUrlInput').value = '';
    document.getElementById('mediaPreview').innerHTML = '';
    document.getElementById('uploadedMediaUrl').value = '';
    document.getElementById('uploadProgress').style.display = 'none';
    switchMediaTab('upload');
    document.getElementById('mediaInsertDialog').style.display = 'flex';
}

function closeMediaDialog() {
    document.getElementById('mediaInsertDialog').style.display = 'none';
}

function switchMediaTab(tab) {
    document.getElementById('mediaTabUpload').style.display = tab === 'upload' ? '' : 'none';
    document.getElementById('mediaTabUrl').style.display   = tab === 'url'    ? '' : 'none';
    document.getElementById('tabUploadBtn').classList.toggle('active', tab === 'upload');
    document.getElementById('tabUrlBtn').classList.toggle('active',    tab !== 'upload');
}

async function handleMediaUpload(input) {
    const file = input.files[0];
    if (!file) return;
    const progress = document.getElementById('uploadProgress');
    progress.style.display = '';
    document.getElementById('uploadedMediaUrl').value = '';
    document.getElementById('mediaPreview').innerHTML = '';
    const formData = new FormData();
    formData.append('file', file);
    try {
        const resp = await fetch('../../upload.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.error) { alert('Erreur : ' + data.error); return; }
        document.getElementById('uploadedMediaUrl').value = data.url;
        const preview = document.getElementById('mediaPreview');
        if (mediaInsertType === 'image') {
            preview.innerHTML = '<img src="' + escapeHtml(data.url) + '" style="max-width:100%;max-height:200px;border-radius:4px;" alt="Aperçu">';
        } else {
            preview.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check-circle me-2"></i><strong>' + escapeHtml(data.name) + '</strong> chargé avec succès</div>';
        }
    } catch(e) {
        alert('Erreur lors du téléchargement. Vérifiez votre connexion.');
    } finally {
        progress.style.display = 'none';
    }
}

function confirmInsertMedia() {
    const uploadedUrl = document.getElementById('uploadedMediaUrl').value;
    const urlInput    = document.getElementById('mediaUrlInput').value.trim();
    const url = uploadedUrl || urlInput;
    if (!url) { alert('Veuillez sélectionner un fichier ou saisir une URL.'); return; }

    closeMediaDialog();

    const editor = document.getElementById(mediaInsertPrefix + 'Editor');
    if (!editor) return;
    editor.focus();
    if (savedSelection) {
        const sel = window.getSelection();
        if (sel) { sel.removeAllRanges(); sel.addRange(savedSelection); }
    }
    if (mediaInsertType === 'image') {
        document.execCommand('insertHTML', false,
            '<img src="' + url + '" style="max-width:100%;height:auto;border-radius:4px;" alt="Image">');
    } else {
        const safeUrl = url.replace(/[<>"']/g, c => ({'<':'%3C','>':'%3E','"':'%22',"'":'%27'}[c]));
        document.execCommand('insertHTML', false,
            '<div style="margin:10px 0;">' +
            '<object data="' + safeUrl + '#toolbar=1" type="application/pdf" width="100%" height="500" style="border:1px solid #dee2e6;border-radius:4px;"></object>' +
            '<p style="margin:4px 0;font-size:12px;"><i class="fas fa-file-pdf" style="color:#e4002b;"></i> ' +
            '<a href="' + safeUrl + '" target="_blank" rel="noopener">Ouvrir le PDF</a></p></div>');
    }
    savedSelection = null;
}

// --- Copier lien de partage ---
function copyLink(id) {
    const input = document.getElementById('link_' + id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    });
}

// --- Voir le détail ---
function showDetail(id) {
    const proc = proceduresData.find(p => p.id == id);
    if (!proc) return;
    const shareUrl = baseShareUrl + '?token=' + proc.lien_partage;
    const detailEl = document.getElementById('detailContent');
    detailEl.innerHTML = `
        <div class="row">
            <div class="col-12 mb-3">
                <h4>${escapeHtml(proc.nom)}</h4>
                <small class="text-muted">Créée le ${formatLocalDateTime(proc.created_at)}</small>
                ${proc.mise_en_avant == 1 ? ' <span class="badge-fait ms-2"><i class="fas fa-star"></i> Mise en avant</span>' : ''}
                ${proc.approved == 1 ? ' <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Approuvé</span>' : ' <span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock"></i> En attente</span>'}
            </div>
            <div class="col-12 mb-3">
                <div class="p-3 bg-light rounded" id="procDetailContent"></div>
            </div>
            <div class="col-12">
                <label class="form-label"><strong>Lien de partage :</strong></label>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" value="${escapeHtml(shareUrl)}" readonly id="detail_link_${id}">
                    <button class="btn btn-ce-outline" onclick="navigator.clipboard.writeText(document.getElementById('detail_link_${id}').value)"><i class="fas fa-copy"></i> Copier</button>
                    <a href="${escapeHtml(shareUrl)}" target="_blank" class="btn btn-ce-outline"><i class="fas fa-external-link-alt"></i> Ouvrir</a>
                </div>
                <a href="share_mail.php?id=${proc.id}" class="btn btn-ce-outline">
                    <i class="fas fa-envelope"></i> Partager par mail (Outlook)
                </a>
            </div>
        </div>`;
    // Injecter le contenu séparément (évite les problèmes avec backticks / template literals)
    document.getElementById('procDetailContent').innerHTML = renderContent(proc.texte);
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// --- Modifier ---
function editProcedure(id) {
    const proc = proceduresData.find(p => p.id == id);
    if (!proc) return;
    document.getElementById('editContent').innerHTML = `
        <form method="POST" onsubmit="return prepareWysiwyg('editEditor', 'editTexte')">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de la procédure</label>
                    <input type="text" name="nom" class="form-control" value="${escapeHtml(proc.nom)}" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mise_en_avant" id="editMiseEnAvant" value="1" ${proc.mise_en_avant == 1 ? 'checked' : ''}>
                        <label class="form-check-label" for="editMiseEnAvant">Mise en avant</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Texte de la procédure</label>
                    <div id="editToolbarContainer"></div>
                    <div class="wysiwyg-editor" id="editEditor" contenteditable="true"></div>
                    <textarea name="texte" id="editTexte" style="display:none;"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    document.getElementById('editToolbarContainer').innerHTML = getWysiwygToolbar('edit');
    document.getElementById('editEditor').innerHTML = proc.texte || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Initialiser la toolbar du formulaire d'ajout
document.getElementById('addToolbarContainer').innerHTML = getWysiwygToolbar('add');

// Auto-ouverture après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
