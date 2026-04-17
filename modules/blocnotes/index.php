<?php
$pageTitle = 'Bloc-notes';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Migrations pour le partage
try { $db->exec("ALTER TABLE blocnotes ADD COLUMN lien_partage VARCHAR(64) DEFAULT NULL"); } catch (Exception $e) {}
$db->exec("CREATE TABLE IF NOT EXISTS blocnotes_partages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    shared_by INT NOT NULL,
    shared_with INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_share (note_id, shared_with),
    KEY idx_shared_with (shared_with)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $db->prepare("INSERT INTO blocnotes (user_id, nom_note, contenu, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$userId, $_POST['nom_note'], $_POST['contenu']]);
    header('Location: index.php');
    exit;
}

// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $db->prepare("UPDATE blocnotes SET nom_note = ?, contenu = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['nom_note'], $_POST['contenu'], (int)$_POST['id'], $userId]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM blocnotes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    // Nettoyage des partages associés
    $db->prepare("DELETE FROM blocnotes_partages WHERE note_id = ?")->execute([$id]);
    header('Location: index.php');
    exit;
}

// Liste : mes notes + notes partagées avec moi
$stmt = $db->prepare("
    SELECT bn.*, u.nom AS owner_nom, u.prenom AS owner_prenom,
           bp.id AS bp_id, sb.nom AS shared_by_nom, sb.prenom AS shared_by_prenom
    FROM blocnotes bn
    JOIN users u ON bn.user_id = u.id
    LEFT JOIN blocnotes_partages bp ON bn.id = bp.note_id AND bp.shared_with = ?
    LEFT JOIN users sb ON bp.shared_by = sb.id
    WHERE bn.user_id = ? OR bp.id IS NOT NULL
    ORDER BY (bn.user_id = ?) DESC, bn.updated_at DESC
");
$stmt->execute([$userId, $userId, $userId]);
$notes = $stmt->fetchAll();

// Tous les utilisateurs (pour la modale de partage)
$stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE id != ? ORDER BY nom ASC, prenom ASC");
$stmt->execute([$userId]);
$allUsers = $stmt->fetchAll();

// URL de base pour les liens publics
$viewBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/view.php';
?>

<!-- Barre d'actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($notes) ?></div>
            <div class="stat-label">Notes disponibles</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouvelle note</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Toutes les notes</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchNotes" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableNotes">
        <thead>
            <tr>
                <th>Nom de la note</th>
                <th>Contenu</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($notes as $note):
            $isShared = !empty($note['bp_id']); // note partagée avec moi
        ?>
            <tr>
                <td>
                    <strong><?= e($note['nom_note']) ?></strong>
                    <?php if ($isShared): ?>
                        <br><span class="badge-shared"><i class="fas fa-share-alt"></i> Partagé par <?= e($note['shared_by_prenom'] . ' ' . $note['shared_by_nom']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e(excerpt($note['contenu'], 100)) ?></td>
                <td><?= formatDate($note['updated_at']) ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $note['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <?php if (!$isShared): ?>
                        <button class="btn btn-sm btn-ce-outline" onclick="editNote(<?= $note['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-ce-outline" onclick="shareNote(<?= $note['id'] ?>)" title="Partager"><i class="fas fa-share-alt"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette note ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $note['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouvelle note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" onsubmit="return prepareWysiwyg('addEditor', 'addContenu')">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la note</label>
                            <input type="text" name="nom_note" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contenu</label>
                            <div id="addToolbarContainer"></div>
                            <div class="wysiwyg-editor" id="addEditor" contenteditable="true"></div>
                            <textarea name="contenu" id="addContenu" style="display:none;"></textarea>
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

<!-- Modal Detail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Détails de la note</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<!-- Modal Partage -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:580px;">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--ce-red);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-share-alt me-2"></i> Partager la note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="shareModalContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Dialog insertion média -->
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
/* WYSIWYG */
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
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #333; font-size: 13px;
    transition: all 0.15s; flex-shrink: 0;
}
.wysiwyg-toolbar button:hover { background: #e9ecef; border-color: #adb5bd; }
.wysiwyg-select { height: 30px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; padding: 0 4px; background: #fff; cursor: pointer; }
.wysiwyg-color { width: 30px; height: 30px; border: 1px solid #dee2e6; border-radius: 4px; padding: 1px; cursor: pointer; }
.toolbar-separator { width: 1px; height: 24px; background: #dee2e6; margin: 0 2px; flex-shrink: 0; }
.wysiwyg-editor {
    border: 1px solid #dee2e6;
    border-radius: 0 0 6px 6px;
    min-height: 300px; max-height: 520px;
    overflow-y: auto; padding: 15px;
    background: #fff; font-size: 14px; line-height: 1.6;
}
.wysiwyg-editor:focus { outline: none; border-color: var(--ce-red); box-shadow: 0 0 0 0.2rem rgba(228,0,43,0.15); }
.wysiwyg-editor img { max-width: 100%; height: auto; border-radius: 4px; margin: 4px 0; }
.wysiwyg-content { line-height: 1.8; font-size: 15px; }
.wysiwyg-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0; display: block; }
.wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 { color: var(--ce-red); margin-top: 16px; margin-bottom: 8px; }
.wysiwyg-content ul, .wysiwyg-content ol { padding-left: 24px; }
.wysiwyg-content object, .wysiwyg-content embed { max-width: 100%; display: block; margin: 8px 0; }
/* Badge partagé */
.badge-shared {
    display: inline-block;
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #90caf9;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    margin-top: 3px;
}
/* Partage modal */
.share-user-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 6px;
    border: 1px solid #e9ecef;
}
.share-user-row .user-name { font-size: 14px; }
</style>

<script>
filterTable('searchNotes', 'tableNotes');

const notesData  = <?= json_encode($notes) ?>;
const allUsers   = <?= json_encode($allUsers) ?>;
const viewBaseUrl = <?= json_encode($viewBaseUrl) ?>;

let mediaInsertPrefix = 'add';
let mediaInsertType   = 'image';
let savedSelection    = null;
let currentShareNoteId = null;

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

// ─── WYSIWYG ────────────────────────────────────────────────────────────────
function execWys(cmd)       { document.execCommand(cmd, false, null); }
function execWysVal(cmd, v) { document.execCommand(cmd, false, v); }

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
        <input type="color" value="#000000" onchange="execWysVal('foreColor', this.value)" title="Couleur" class="wysiwyg-color">
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="openMediaModal('image', '${prefix}')" title="Insérer une image"><i class="fas fa-image"></i></button>
        <button type="button" onmousedown="event.preventDefault()" onclick="openMediaModal('pdf', '${prefix}')" title="Insérer un PDF"><i class="fas fa-file-pdf"></i></button>
        <span class="toolbar-separator"></span>
        <button type="button" onmousedown="event.preventDefault()" onclick="execWys('removeFormat')" title="Effacer la mise en forme"><i class="fas fa-eraser"></i></button>
    </div>`;
}

function prepareWysiwyg(editorId, textareaId) {
    const editor   = document.getElementById(editorId);
    const textarea = document.getElementById(textareaId);
    if (!editor || !textarea) return true;
    const content = editor.innerHTML.trim().replace(/^<br\s*\/?>$/i, '');
    if (!content) { alert('Le contenu ne peut pas être vide.'); editor.focus(); return false; }
    textarea.value = content;
    return true;
}

// ─── MÉDIA ──────────────────────────────────────────────────────────────────
function openMediaModal(type, prefix) {
    mediaInsertType   = type;
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
    document.getElementById('mediaUrlInput').value  = '';
    document.getElementById('mediaPreview').innerHTML = '';
    document.getElementById('uploadedMediaUrl').value = '';
    document.getElementById('uploadProgress').style.display = 'none';
    switchMediaTab('upload');
    document.getElementById('mediaInsertDialog').style.display = 'flex';
}

function closeMediaDialog() { document.getElementById('mediaInsertDialog').style.display = 'none'; }

function switchMediaTab(tab) {
    document.getElementById('mediaTabUpload').style.display = tab === 'upload' ? '' : 'none';
    document.getElementById('mediaTabUrl').style.display    = tab === 'url'    ? '' : 'none';
    document.getElementById('tabUploadBtn').classList.toggle('active', tab === 'upload');
    document.getElementById('tabUrlBtn').classList.toggle('active',    tab !== 'upload');
}

async function handleMediaUpload(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('uploadProgress').style.display = '';
    document.getElementById('uploadedMediaUrl').value = '';
    document.getElementById('mediaPreview').innerHTML = '';
    const fd = new FormData();
    fd.append('file', file);
    try {
        const resp = await fetch('../../upload.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.error) { alert('Erreur : ' + data.error); return; }
        document.getElementById('uploadedMediaUrl').value = data.url;
        document.getElementById('mediaPreview').innerHTML = mediaInsertType === 'image'
            ? '<img src="' + escapeHtml(data.url) + '" style="max-width:100%;max-height:200px;border-radius:4px;" alt="Aperçu">'
            : '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check-circle me-2"></i><strong>' + escapeHtml(data.name) + '</strong> chargé</div>';
    } catch(e) { alert('Erreur lors du téléchargement.'); }
    finally { document.getElementById('uploadProgress').style.display = 'none'; }
}

function confirmInsertMedia() {
    const url = document.getElementById('uploadedMediaUrl').value || document.getElementById('mediaUrlInput').value.trim();
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
        const s = url.replace(/[<>"']/g, c => ({'<':'%3C','>':'%3E','"':'%22',"'":'%27'}[c]));
        document.execCommand('insertHTML', false,
            '<div style="margin:10px 0;"><object data="' + s + '#toolbar=1" type="application/pdf" width="100%" height="500" style="border:1px solid #dee2e6;border-radius:4px;"></object>' +
            '<p style="margin:4px 0;font-size:12px;"><i class="fas fa-file-pdf" style="color:#e4002b;"></i> <a href="' + s + '" target="_blank" rel="noopener">Ouvrir le PDF</a></p></div>');
    }
    savedSelection = null;
}

// ─── VOIR ────────────────────────────────────────────────────────────────────
function showDetail(id) {
    const note = notesData.find(n => n.id == id);
    if (!note) return;
    const isShared = note.bp_id !== null;
    const el = document.getElementById('detailContent');
    el.innerHTML = `
        <div class="row">
            <div class="col-12 mb-3">
                <h4>${escapeHtml(note.nom_note)}</h4>
                ${isShared
                    ? '<span class="badge-shared me-2"><i class="fas fa-share-alt"></i> Partagé par ' + escapeHtml(note.shared_by_prenom + ' ' + note.shared_by_nom) + '</span>'
                    : ''}
                <small class="text-muted d-block mt-1">
                    Créée le ${formatLocalDateTime(note.created_at)} — Modifiée le ${formatLocalDateTime(note.updated_at)}
                </small>
            </div>
            <div class="col-12">
                <div class="p-3 bg-light rounded" id="noteDetailContent"></div>
            </div>
        </div>`;
    document.getElementById('noteDetailContent').innerHTML = renderContent(note.contenu);
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// ─── MODIFIER ────────────────────────────────────────────────────────────────
function editNote(id) {
    const note = notesData.find(n => n.id == id);
    if (!note || note.bp_id !== null) return; // pas de modification sur une note partagée
    document.getElementById('editContent').innerHTML = `
        <form method="POST" onsubmit="return prepareWysiwyg('editEditor', 'editContenu')">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de la note</label>
                    <input type="text" name="nom_note" class="form-control" value="${escapeHtml(note.nom_note)}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Contenu</label>
                    <div id="editToolbarContainer"></div>
                    <div class="wysiwyg-editor" id="editEditor" contenteditable="true"></div>
                    <textarea name="contenu" id="editContenu" style="display:none;"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;
    document.getElementById('editToolbarContainer').innerHTML = getWysiwygToolbar('edit');
    document.getElementById('editEditor').innerHTML = note.contenu || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ─── PARTAGE ─────────────────────────────────────────────────────────────────
async function shareNote(id) {
    currentShareNoteId = id;
    const note = notesData.find(n => n.id == id);
    if (!note) return;
    document.getElementById('shareModalContent').innerHTML =
        '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';
    new bootstrap.Modal(document.getElementById('shareModal')).show();
    await refreshShareModal(id);
}

async function refreshShareModal(noteId) {
    try {
        const resp = await fetch('share_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'get_shares', note_id: noteId })
        });
        const data = await resp.json();
        if (data.error) { document.getElementById('shareModalContent').innerHTML = '<div class="alert alert-danger">' + escapeHtml(data.error) + '</div>'; return; }
        const note = notesData.find(n => n.id == noteId);
        renderShareModal(note, data);
    } catch(e) {
        document.getElementById('shareModalContent').innerHTML = '<div class="alert alert-danger">Erreur de connexion.</div>';
    }
}

function renderShareModal(note, data) {
    const shares      = data.shares || [];
    const token       = data.lien_partage;
    const sharesIds   = new Set(shares.map(s => parseInt(s.shared_with)));
    const available   = allUsers.filter(u => !sharesIds.has(parseInt(u.id)));

    const sharesHtml = shares.length === 0
        ? '<p class="text-muted small mb-0">Aucun partage utilisateur actif.</p>'
        : shares.map(s => `
            <div class="share-user-row">
                <span class="user-name"><i class="fas fa-user me-2 text-muted"></i>${escapeHtml(s.prenom + ' ' + s.nom)}</span>
                <button class="btn btn-sm btn-outline-danger" onclick="doUnshareUser(${currentShareNoteId}, ${s.shared_with})">
                    <i class="fas fa-times me-1"></i> Révoquer
                </button>
            </div>`).join('');

    const userSelectOptions = available.length === 0
        ? '<option value="">— Tous les utilisateurs ont déjà accès —</option>'
        : '<option value="">— Choisir un utilisateur —</option>' + available.map(u =>
            `<option value="${u.id}">${escapeHtml(u.prenom + ' ' + u.nom)}</option>`).join('');

    const publicHtml = token
        ? `<div class="input-group mb-2">
               <input type="text" class="form-control form-control-sm" value="${escapeHtml(viewBaseUrl + '?token=' + token)}" readonly id="publicLinkInput">
               <button class="btn btn-ce-outline btn-sm" onclick="copyPublicLink()" title="Copier"><i class="fas fa-copy"></i></button>
               <a href="${escapeHtml(viewBaseUrl + '?token=' + token)}" target="_blank" class="btn btn-ce-outline btn-sm" title="Ouvrir"><i class="fas fa-external-link-alt"></i></a>
           </div>
           <button class="btn btn-sm btn-outline-danger" onclick="doRevokeLink(${currentShareNoteId})">
               <i class="fas fa-ban me-1"></i> Révoquer le lien
           </button>`
        : `<p class="text-muted small mb-2">Aucun lien public actif.</p>
           <button class="btn btn-sm btn-ce" onclick="doGenerateLink(${currentShareNoteId})">
               <i class="fas fa-link me-1"></i> Générer un lien public
           </button>`;

    document.getElementById('shareModalContent').innerHTML = `
        <h6 class="mb-4"><i class="fas fa-sticky-note me-2" style="color:var(--ce-red);"></i>${escapeHtml(note.nom_note)}</h6>

        <div class="mb-4">
            <label class="form-label fw-semibold"><i class="fas fa-users me-1"></i> Partager avec un utilisateur</label>
            <div class="input-group mb-3">
                <select class="form-select form-select-sm" id="shareUserSelect">${userSelectOptions}</select>
                <button class="btn btn-ce btn-sm" onclick="doShareUser(${currentShareNoteId})" ${available.length === 0 ? 'disabled' : ''}>
                    <i class="fas fa-plus"></i> Partager
                </button>
            </div>
            <div id="currentSharesList">${sharesHtml}</div>
        </div>

        <hr>

        <div>
            <label class="form-label fw-semibold"><i class="fas fa-link me-1"></i> Lien public (sans connexion)</label>
            <div id="publicLinkSection">${publicHtml}</div>
        </div>`;
}

async function doShareUser(noteId) {
    const select = document.getElementById('shareUserSelect');
    const uid = select ? select.value : '';
    if (!uid) { alert('Veuillez choisir un utilisateur.'); return; }
    const resp = await fetch('share_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'share_user', note_id: noteId, share_with: uid })
    });
    const data = await resp.json();
    if (data.error) { alert(data.error); return; }
    await refreshShareModal(noteId);
}

async function doUnshareUser(noteId, shareWithId) {
    const resp = await fetch('share_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'unshare_user', note_id: noteId, share_with: shareWithId })
    });
    const data = await resp.json();
    if (data.error) { alert(data.error); return; }
    await refreshShareModal(noteId);
}

async function doGenerateLink(noteId) {
    const resp = await fetch('share_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'generate_link', note_id: noteId })
    });
    const data = await resp.json();
    if (data.error) { alert(data.error); return; }
    // Mettre à jour localement
    const note = notesData.find(n => n.id == noteId);
    if (note) note.lien_partage = data.token;
    await refreshShareModal(noteId);
}

async function doRevokeLink(noteId) {
    if (!confirm('Révoquer le lien public ? Les personnes disposant de ce lien ne pourront plus accéder à la note.')) return;
    const resp = await fetch('share_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'revoke_link', note_id: noteId })
    });
    const data = await resp.json();
    if (data.error) { alert(data.error); return; }
    const note = notesData.find(n => n.id == noteId);
    if (note) note.lien_partage = null;
    await refreshShareModal(noteId);
}

function copyPublicLink() {
    const input = document.getElementById('publicLinkInput');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    });
}

// ─── INIT ────────────────────────────────────────────────────────────────────
document.getElementById('addToolbarContainer').innerHTML = getWysiwygToolbar('add');

const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
