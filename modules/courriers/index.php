<?php
$pageTitle = 'Générateur de courriers';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$user = getCurrentUser();
$isUserAdmin = isAdmin();

// Création des tables si nécessaire
$db->exec("CREATE TABLE IF NOT EXISTS courriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_prenom_dest VARCHAR(255) NOT NULL,
    complement_dest VARCHAR(255) DEFAULT '',
    adresse_dest VARCHAR(255) NOT NULL,
    complement_adresse_dest VARCHAR(255) DEFAULT '',
    cp_ville_dest VARCHAR(255) NOT NULL,
    lieu VARCHAR(255) DEFAULT 'Capdenac-Gare',
    date_courrier DATETIME DEFAULT CURRENT_TIMESTAMP,
    objet VARCHAR(500) NOT NULL,
    corps LONGTEXT,
    INDEX(user_id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS modeles_courriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_modele VARCHAR(255) NOT NULL,
    objet VARCHAR(500) DEFAULT '',
    corps LONGTEXT,
    variables JSON DEFAULT NULL,
    INDEX(user_id)
)");

// Migrations
try { $db->exec("ALTER TABLE modeles_courriers ADD COLUMN variables JSON DEFAULT NULL"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE courriers ADD COLUMN civilite_dest VARCHAR(20) DEFAULT '' AFTER user_id"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE courriers ADD COLUMN nom_dest VARCHAR(255) DEFAULT '' AFTER civilite_dest"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE courriers ADD COLUMN prenom_dest VARCHAR(255) DEFAULT '' AFTER nom_dest"); } catch (PDOException $e) {}

// Approval columns for modeles_courriers
try { $db->exec("ALTER TABLE modeles_courriers ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE modeles_courriers ADD COLUMN approved_by INT DEFAULT NULL"); } catch (PDOException $e) {}

// Ajout courrier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $civilite = $_POST['civilite_dest'] ?? '';
    $nom = $_POST['nom_dest'] ?? '';
    $prenom = $_POST['prenom_dest'] ?? '';
    $nomPrenom = trim($civilite . ' ' . $prenom . ' ' . $nom);
    $stmt = $db->prepare("INSERT INTO courriers (user_id, civilite_dest, nom_dest, prenom_dest, nom_prenom_dest, complement_dest, adresse_dest, complement_adresse_dest, cp_ville_dest, lieu, objet, corps) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $civilite,
        $nom,
        $prenom,
        $nomPrenom,
        $_POST['complement_dest'] ?? '',
        $_POST['adresse_dest'],
        $_POST['complement_adresse_dest'] ?? '',
        $_POST['cp_ville_dest'],
        $_POST['lieu'] ?: 'Capdenac-Gare',
        $_POST['objet'],
        $_POST['corps']
    ]);
    header('Location: index.php');
    exit;
}

// Edition courrier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $civilite = $_POST['civilite_dest'] ?? '';
    $nom = $_POST['nom_dest'] ?? '';
    $prenom = $_POST['prenom_dest'] ?? '';
    $nomPrenom = trim($civilite . ' ' . $prenom . ' ' . $nom);
    $stmt = $db->prepare("UPDATE courriers SET civilite_dest = ?, nom_dest = ?, prenom_dest = ?, nom_prenom_dest = ?, complement_dest = ?, adresse_dest = ?, complement_adresse_dest = ?, cp_ville_dest = ?, lieu = ?, objet = ?, corps = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([
        $civilite,
        $nom,
        $prenom,
        $nomPrenom,
        $_POST['complement_dest'] ?? '',
        $_POST['adresse_dest'],
        $_POST['complement_adresse_dest'] ?? '',
        $_POST['cp_ville_dest'],
        $_POST['lieu'] ?: 'Capdenac-Gare',
        $_POST['objet'],
        $_POST['corps'],
        (int)$_POST['id'],
        $userId
    ]);
    header('Location: index.php?open=' . (int)$_POST['id']);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare("DELETE FROM courriers WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $userId]);
    header('Location: index.php');
    exit;
}

// Sauvegarde modèle (auto-approuvé si admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_template') {
    $variables = isset($_POST['tpl_variables']) ? $_POST['tpl_variables'] : null;
    $stmt = $db->prepare("INSERT INTO modeles_courriers (user_id, nom_modele, objet, corps, variables, approved, approved_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $_POST['nom_modele'], $_POST['tpl_objet'] ?? '', $_POST['tpl_corps'] ?? '', $variables,
        $isUserAdmin ? 1 : 0,
        $isUserAdmin ? $userId : null
    ]);
    header('Location: index.php');
    exit;
}

// Suppression modèle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_template') {
    $id = (int)$_POST['tpl_id'];
    if ($isUserAdmin) {
        $stmt = $db->prepare("DELETE FROM modeles_courriers WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("DELETE FROM modeles_courriers WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
    header('Location: index.php');
    exit;
}

// Liste courriers
$stmt = $db->prepare("SELECT * FROM courriers WHERE user_id = ? ORDER BY date_courrier DESC");
$stmt->execute([$userId]);
$courriers = $stmt->fetchAll();

// Liste modèles : ses propres modèles + tous les modèles approuvés
$stmt = $db->prepare("SELECT m.*, u.nom AS author_nom, u.prenom AS author_prenom
    FROM modeles_courriers m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.user_id = ? OR m.approved = 1
    ORDER BY m.nom_modele ASC");
$stmt->execute([$userId]);
$modeles = $stmt->fetchAll();
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($courriers) ?></div>
            <div class="stat-label">Courriers générés</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><?= count($modeles) ?></div>
            <div class="stat-label">Modèles enregistrés</div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Nouveau courrier</button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Tous les courriers</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCourriers" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableCourriers">
        <thead>
            <tr>
                <th>Date</th>
                <th>Destinataire</th>
                <th>Objet</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($courriers as $c): ?>
            <tr>
                <td><?= formatDate($c['date_courrier']) ?></td>
                <td><strong><?= e($c['nom_prenom_dest']) ?></strong></td>
                <td><?= e(excerpt($c['objet'])) ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $c['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editCourrier(<?= $c['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="printCourrier(<?= $c['id'] ?>)" title="Imprimer"><i class="fas fa-print"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce courrier ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nouveau courrier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm" onsubmit="return prepareSubmit('add')">
                    <input type="hidden" name="action" value="add">

                    <!-- Modèles -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Charger un modèle</label>
                            <div class="input-group">
                                <select id="addTemplateSelect" class="form-select">
                                    <option value="">-- Choisir un modèle --</option>
                                    <?php foreach ($modeles as $m):
                                        $tplLabel = e($m['nom_modele']);
                                        if ($m['user_id'] != $userId) $tplLabel .= ' (' . e($m['author_prenom'] . ' ' . $m['author_nom']) . ')';
                                        if (!$m['approved'] && $m['user_id'] == $userId) $tplLabel .= ' [En attente]';
                                    ?>
                                        <option value="<?= $m['id'] ?>"><?= $tplLabel ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-ce-outline" onclick="loadTemplate('add')"><i class="fas fa-download"></i> Charger</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sauvegarder comme modèle</label>
                            <div class="input-group">
                                <input type="text" id="addTemplateName" class="form-control" placeholder="Nom du modèle...">
                                <button type="button" class="btn btn-ce-outline" onclick="saveTemplate('add')"><i class="fas fa-save"></i> Sauver</button>
                            </div>
                            <?php if (!$isUserAdmin): ?>
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Le modèle sera soumis à validation admin.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <!-- Destinataire -->
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Civilité <span class="text-danger">*</span></label>
                            <select name="civilite_dest" id="addCivilite" class="form-select" required>
                                <option value="">--</option>
                                <option value="Madame">Madame</option>
                                <option value="Monsieur">Monsieur</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom_dest" id="addNomDest" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom_dest" id="addPrenomDest" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Complément (titre, service...)</label>
                            <input type="text" name="complement_dest" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse <span class="text-danger">*</span></label>
                            <input type="text" name="adresse_dest" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Complément d'adresse</label>
                            <input type="text" name="complement_adresse_dest" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code postal et ville <span class="text-danger">*</span></label>
                            <input type="text" name="cp_ville_dest" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lieu</label>
                            <input type="text" name="lieu" class="form-control" value="Capdenac-Gare">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" disabled>
                        </div>

                        <!-- Objet -->
                        <div class="col-12">
                            <label class="form-label">Objet <span class="text-danger">*</span></label>
                            <input type="text" name="objet" id="addObjet" class="form-control" required>
                        </div>

                        <!-- Variables dynamiques -->
                        <div class="col-12">
                            <div class="variables-section">
                                <div class="variables-header">
                                    <label class="form-label mb-0"><i class="fas fa-code"></i> Variables dynamiques</label>
                                    <button type="button" class="btn btn-sm btn-ce-outline" onclick="addVariableRow('add')"><i class="fas fa-plus"></i> Ajouter une variable</button>
                                </div>
                                <div class="variables-help">
                                    Cliquez sur une variable pour l'insérer dans le corps du courrier. Les variables fixes sont remplies automatiquement depuis les champs du destinataire.
                                </div>
                                <div class="builtin-variables">
                                    <span class="builtin-var-label">Variables fixes :</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('add', 'civilite')"><code>{{civilite}}</code></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('add', 'nom_dest')"><code>{{nom_dest}}</code></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('add', 'prenom_dest')"><code>{{prenom_dest}}</code></button>
                                </div>
                                <div id="addVariablesContainer"></div>
                            </div>
                        </div>

                        <!-- WYSIWYG -->
                        <div class="col-12">
                            <label class="form-label">Corps du courrier <span class="text-danger">*</span></label>
                            <div class="wysiwyg-toolbar" id="addToolbar">
                                <button type="button" onclick="execCmd('bold')" title="Gras"><i class="fas fa-bold"></i></button>
                                <button type="button" onclick="execCmd('italic')" title="Italique"><i class="fas fa-italic"></i></button>
                                <button type="button" onclick="execCmd('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
                                <span class="toolbar-separator"></span>
                                <button type="button" onclick="execCmd('insertUnorderedList')" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
                                <button type="button" onclick="execCmd('insertOrderedList')" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
                                <span class="toolbar-separator"></span>
                                <input type="color" id="addColorPicker" value="#000000" onchange="execCmdVal('foreColor', this.value)" title="Couleur du texte" style="width:30px;height:28px;border:none;padding:0;cursor:pointer;">
                            </div>
                            <div class="wysiwyg-editor" id="addEditor" contenteditable="true"></div>
                            <textarea name="corps" id="addCorps" style="display:none;"></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer le courrier</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détail -->
<div class="modal fade modal-fullscreen-custom" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope-open-text"></i> Détail du courrier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le courrier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
            </div>
        </div>
    </div>
</div>

<!-- Zone impression (cachée) -->
<div id="printArea" class="print-courrier" style="display:none;"></div>

<style>
/* WYSIWYG Editor */
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
}
.wysiwyg-toolbar button:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}
.toolbar-separator {
    width: 1px;
    height: 24px;
    background: #dee2e6;
    margin: 0 4px;
}
.wysiwyg-editor {
    border: 1px solid #dee2e6;
    border-radius: 0 0 6px 6px;
    min-height: 250px;
    max-height: 500px;
    overflow-y: auto;
    padding: 15px;
    background: #fff;
    font-size: 14px;
    line-height: 1.6;
}
.wysiwyg-editor:focus {
    outline: none;
    border-color: #CF0A2C;
    box-shadow: 0 0 0 0.2rem rgba(207, 10, 44, 0.15);
}

/* Print styles */
@media print {
    body * {
        visibility: hidden !important;
    }
    #printArea, #printArea * {
        visibility: visible !important;
    }
    #printArea {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    @page {
        size: A4;
        margin: 8mm 10mm 8mm 10mm;
    }
}

.print-courrier {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11pt;
    line-height: 1.4;
    color: #000;
}

/* Detail letter preview */
.letter-preview {
    max-width: 800px;
    margin: 0 auto;
    padding: 25px 30px;
    font-family: Arial, Helvetica, sans-serif;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.letter-preview .lp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0;
}
.letter-preview .lp-logo img {
    max-width: 160px;
}
.letter-preview .lp-sender {
    font-size: 12px;
    color: #555;
    margin-top: 6px;
    line-height: 1.3;
}
.letter-preview .lp-dest {
    text-align: left;
    margin-left: auto;
    margin-right: 0;
    margin-top: 20px;
    width: 280px;
    line-height: 1.4;
    padding: 10px 15px;
    border: 1px dashed #ccc;
    border-radius: 4px;
    background: #fafafa;
}
.letter-preview .lp-lieu-date {
    text-align: right;
    margin: 15px 0 10px 0;
    color: #555;
}
.letter-preview .lp-objet {
    font-weight: bold;
    margin: 10px 0;
    font-size: 14px;
}
.letter-preview .lp-corps {
    margin: 10px 0;
    line-height: 1.6;
    text-align: justify;
}
.letter-preview .lp-footer {
    text-align: right;
    margin-top: 30px;
    font-weight: 500;
}

/* Variables dynamiques */
.variables-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px 15px;
}
.variables-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.variables-help {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 8px;
}
.variables-help code {
    background: #e9ecef;
    padding: 1px 5px;
    border-radius: 3px;
    color: #CF0A2C;
}
.variable-row {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 6px;
}
.variable-row input {
    font-size: 13px;
}
.variable-row .var-name {
    flex: 0 0 180px;
}
.variable-row .var-value {
    flex: 1;
}
.variable-row .btn-insert-var {
    flex-shrink: 0;
    font-size: 12px;
    white-space: nowrap;
}
.variable-row .btn-remove-var {
    flex-shrink: 0;
}
.builtin-variables {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}
.builtin-var-label {
    font-size: 12px;
    color: #555;
    font-weight: 500;
}
.builtin-variables .btn {
    font-size: 12px;
    padding: 2px 8px;
}
.builtin-variables code {
    color: #CF0A2C;
    font-size: 12px;
}
</style>

<script>
filterTable('searchCourriers', 'tableCourriers');

const courriersData = <?= json_encode($courriers) ?>;
const modelesData = <?= json_encode($modeles) ?>;
const userData = <?= json_encode(['nom' => $user['nom'], 'prenom' => $user['prenom'], 'email_pro' => $user['email_pro'] ?? '', 'tel_pro' => $user['tel_pro'] ?? '']) ?>;

// WYSIWYG commands
function execCmd(command) {
    document.execCommand(command, false, null);
}
function execCmdVal(command, value) {
    document.execCommand(command, false, value);
}

// --- Variables dynamiques ---

// Insérer une variable fixe (civilite, nom_dest, prenom_dest) dans l'éditeur
function insertBuiltinVar(prefix, varName) {
    const editor = document.getElementById(prefix + 'Editor');
    editor.focus();
    document.execCommand('insertText', false, '{{' + varName + '}}');
}

// Récupérer les valeurs des variables fixes depuis les champs du formulaire
function getBuiltinVariables(prefix) {
    const civiliteEl = document.getElementById(prefix + 'Civilite');
    const nomEl = document.getElementById(prefix + 'NomDest');
    const prenomEl = document.getElementById(prefix + 'PrenomDest');
    return {
        'civilite': civiliteEl ? civiliteEl.value : '',
        'nom_dest': nomEl ? nomEl.value : '',
        'prenom_dest': prenomEl ? prenomEl.value : ''
    };
}

// Ajouter une ligne de variable
function addVariableRow(prefix, name = '', value = '') {
    const container = document.getElementById(prefix + 'VariablesContainer');
    const row = document.createElement('div');
    row.className = 'variable-row';
    row.innerHTML = `
        <input type="text" class="form-control var-name" placeholder="Nom (ex: next-rdv)" value="${name.replace(/"/g, '&quot;')}">
        <input type="text" class="form-control var-value" placeholder="Valeur (ex: 25/03/2026 à 14h30)" value="${value.replace(/"/g, '&quot;')}">
        <button type="button" class="btn btn-sm btn-ce-outline btn-insert-var" onclick="insertVariable(this, '${prefix}')" title="Insérer dans le corps"><i class="fas fa-arrow-down"></i> Insérer</button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-var" onclick="this.closest('.variable-row').remove()" title="Supprimer"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}

// Insérer le tag {{nom}} dans l'éditeur à la position du curseur
function insertVariable(btn, prefix) {
    const row = btn.closest('.variable-row');
    const name = row.querySelector('.var-name').value.trim();
    if (!name) {
        alert('Veuillez saisir un nom de variable.');
        row.querySelector('.var-name').focus();
        return;
    }
    const editor = document.getElementById(prefix + 'Editor');
    editor.focus();
    document.execCommand('insertText', false, '{{' + name + '}}');
}

// Récupérer toutes les variables d'un formulaire
function getVariables(prefix) {
    const vars = {};
    const rows = document.querySelectorAll('#' + prefix + 'VariablesContainer .variable-row');
    rows.forEach(row => {
        const name = row.querySelector('.var-name').value.trim();
        const value = row.querySelector('.var-value').value;
        if (name) vars[name] = value;
    });
    return vars;
}

// Récupérer les noms de variables (pour sauvegarde dans les modèles)
function getVariableNames(prefix) {
    const names = [];
    const rows = document.querySelectorAll('#' + prefix + 'VariablesContainer .variable-row');
    rows.forEach(row => {
        const name = row.querySelector('.var-name').value.trim();
        if (name) names.push(name);
    });
    return names;
}

// Remplacer les {{variables}} dans un texte
function replaceVariables(text, vars) {
    for (const [name, value] of Object.entries(vars)) {
        const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        text = text.replace(new RegExp('\\{\\{' + escaped + '\\}\\}', 'g'), value);
    }
    return text;
}

// Auto-détecter les variables {{...}} dans le contenu d'un modèle (exclut les variables fixes)
const BUILTIN_VARS = ['civilite', 'nom_dest', 'prenom_dest'];
function detectVariables(text) {
    const matches = text.match(/\{\{([^}]+)\}\}/g);
    if (!matches) return [];
    const names = [...new Set(matches.map(m => m.replace(/\{\{|\}\}/g, '')))];
    return names.filter(n => !BUILTIN_VARS.includes(n));
}

// Préparer le formulaire avant soumission (copier innerHTML dans textarea + remplacer variables)
function prepareSubmit(prefix) {
    const editor = document.getElementById(prefix + 'Editor');
    const textarea = document.getElementById(prefix + 'Corps');
    const builtinVars = getBuiltinVariables(prefix);
    const customVars = getVariables(prefix);
    const allVars = {...builtinVars, ...customVars};
    let content = editor.innerHTML.trim();
    content = replaceVariables(content, allVars);
    textarea.value = content;
    if (!textarea.value || textarea.value === '<br>') {
        alert('Veuillez rédiger le corps du courrier.');
        return false;
    }
    return true;
}

// Charger un modèle
function loadTemplate(prefix) {
    const select = document.getElementById(prefix + 'TemplateSelect');
    const id = select.value;
    if (!id) return;
    const modele = modelesData.find(m => m.id == id);
    if (!modele) return;
    document.getElementById(prefix + 'Objet').value = modele.objet || '';
    document.getElementById(prefix + 'Editor').innerHTML = modele.corps || '';

    // Charger les variables du modèle ou les auto-détecter
    const container = document.getElementById(prefix + 'VariablesContainer');
    container.innerHTML = '';
    let varNames = [];
    if (modele.variables) {
        try {
            varNames = JSON.parse(modele.variables);
        } catch(e) {}
    }
    if (varNames.length === 0) {
        varNames = detectVariables(modele.corps || '');
    }
    varNames.forEach(name => addVariableRow(prefix, name, ''));
}

// Sauvegarder comme modèle
function saveTemplate(prefix) {
    const nameInput = document.getElementById(prefix + 'TemplateName');
    const nom = nameInput.value.trim();
    if (!nom) {
        alert('Veuillez saisir un nom pour le modèle.');
        nameInput.focus();
        return;
    }
    const objet = document.getElementById(prefix + 'Objet').value;
    const corps = document.getElementById(prefix + 'Editor').innerHTML.trim();

    const varNames = getVariableNames(prefix);
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = `
        <input name="action" value="save_template">
        <input name="nom_modele" value="${nom.replace(/"/g, '&quot;')}">
        <input name="tpl_objet" value="${objet.replace(/"/g, '&quot;')}">
        <textarea name="tpl_corps">${corps}</textarea>
        <input name="tpl_variables" value="${JSON.stringify(varNames).replace(/"/g, '&quot;')}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Formater la date
function fmtDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}

// Afficher le détail
function showDetail(id) {
    const c = courriersData.find(x => x.id == id);
    if (!c) return;

    const destName = [c.civilite_dest, c.prenom_dest, c.nom_dest].filter(Boolean).join(' ') || c.nom_prenom_dest;
    let destLines = destName;
    if (c.complement_dest) destLines += '<br>' + c.complement_dest;
    destLines += '<br>' + c.adresse_dest;
    if (c.complement_adresse_dest) destLines += '<br>' + c.complement_adresse_dest;
    destLines += '<br>' + c.cp_ville_dest;

    document.getElementById('detailContent').innerHTML = `
        <div class="letter-preview">
            <div class="lp-header">
                <div>
                    <div class="lp-logo">
                        <img src="https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2" alt="Caisse d'Épargne">
                    </div>
                    <div class="lp-sender">
                        ${userData.prenom} ${userData.nom}<br>
                        5 Avenue Charles de Gaulle<br>
                        12700 Capdenac-Gare<br>
                        ${userData.tel_pro ? userData.tel_pro + '<br>' : ''}
                        ${userData.email_pro ? userData.email_pro : ''}
                    </div>
                </div>
            </div>
            <br><br>
            <div class="lp-dest">
                ${destLines}
            </div>
            <div class="lp-lieu-date">
                ${c.lieu || 'Capdenac-Gare'}, le ${fmtDate(c.date_courrier)}
            </div>
            <br><br>
            <div class="lp-objet">Objet : ${c.objet}</div>
            <br><br>
            <div class="lp-corps">${c.corps}</div>
            <div class="lp-footer">${userData.prenom} ${userData.nom}</div>
        </div>
        <div class="text-center mt-3">
            <button class="btn btn-ce" onclick="printCourrier(${c.id})"><i class="fas fa-print"></i> Imprimer</button>
        </div>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Modifier un courrier
function editCourrier(id) {
    const c = courriersData.find(x => x.id == id);
    if (!c) return;

    let tplOptions = '<option value="">-- Choisir un modèle --</option>';
    modelesData.forEach(m => {
        tplOptions += `<option value="${m.id}">${m.nom_modele}</option>`;
    });

    document.getElementById('editContent').innerHTML = `
        <form method="POST" id="editForm" onsubmit="return prepareSubmit('edit')">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">

            <!-- Modèles -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Charger un modèle</label>
                    <div class="input-group">
                        <select id="editTemplateSelect" class="form-select">${tplOptions}</select>
                        <button type="button" class="btn btn-ce-outline" onclick="loadTemplate('edit')"><i class="fas fa-download"></i> Charger</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sauvegarder comme modèle</label>
                    <div class="input-group">
                        <input type="text" id="editTemplateName" class="form-control" placeholder="Nom du modèle...">
                        <button type="button" class="btn btn-ce-outline" onclick="saveTemplate('edit')"><i class="fas fa-save"></i> Sauver</button>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Civilité <span class="text-danger">*</span></label>
                    <select name="civilite_dest" id="editCivilite" class="form-select" required>
                        <option value="">--</option>
                        <option value="Madame" ${c.civilite_dest === 'Madame' ? 'selected' : ''}>Madame</option>
                        <option value="Monsieur" ${c.civilite_dest === 'Monsieur' ? 'selected' : ''}>Monsieur</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom_dest" id="editNomDest" class="form-control" value="${c.nom_dest || ''}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="prenom_dest" id="editPrenomDest" class="form-control" value="${c.prenom_dest || ''}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Complément (titre, service...)</label>
                    <input type="text" name="complement_dest" class="form-control" value="${c.complement_dest || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Adresse <span class="text-danger">*</span></label>
                    <input type="text" name="adresse_dest" class="form-control" value="${c.adresse_dest}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Complément d'adresse</label>
                    <input type="text" name="complement_adresse_dest" class="form-control" value="${c.complement_adresse_dest || ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code postal et ville <span class="text-danger">*</span></label>
                    <input type="text" name="cp_ville_dest" class="form-control" value="${c.cp_ville_dest}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lieu</label>
                    <input type="text" name="lieu" class="form-control" value="${c.lieu || 'Capdenac-Gare'}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="text" class="form-control" value="${fmtDate(c.date_courrier)}" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Objet <span class="text-danger">*</span></label>
                    <input type="text" name="objet" id="editObjet" class="form-control" value="${c.objet}" required>
                </div>
                <div class="col-12">
                    <div class="variables-section">
                        <div class="variables-header">
                            <label class="form-label mb-0"><i class="fas fa-code"></i> Variables dynamiques</label>
                            <button type="button" class="btn btn-sm btn-ce-outline" onclick="addVariableRow('edit')"><i class="fas fa-plus"></i> Ajouter une variable</button>
                        </div>
                        <div class="variables-help">
                            Cliquez sur une variable pour l'insérer dans le corps du courrier. Les variables fixes sont remplies automatiquement depuis les champs du destinataire.
                        </div>
                        <div class="builtin-variables">
                            <span class="builtin-var-label">Variables fixes :</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('edit', 'civilite')"><code>{{civilite}}</code></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('edit', 'nom_dest')"><code>{{nom_dest}}</code></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertBuiltinVar('edit', 'prenom_dest')"><code>{{prenom_dest}}</code></button>
                        </div>
                        <div id="editVariablesContainer"></div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Corps du courrier <span class="text-danger">*</span></label>
                    <div class="wysiwyg-toolbar" id="editToolbar">
                        <button type="button" onclick="execCmd('bold')" title="Gras"><i class="fas fa-bold"></i></button>
                        <button type="button" onclick="execCmd('italic')" title="Italique"><i class="fas fa-italic"></i></button>
                        <button type="button" onclick="execCmd('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
                        <span class="toolbar-separator"></span>
                        <button type="button" onclick="execCmd('insertUnorderedList')" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="execCmd('insertOrderedList')" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
                        <span class="toolbar-separator"></span>
                        <input type="color" id="editColorPicker" value="#000000" onchange="execCmdVal('foreColor', this.value)" title="Couleur du texte" style="width:30px;height:28px;border:none;padding:0;cursor:pointer;">
                    </div>
                    <div class="wysiwyg-editor" id="editEditor" contenteditable="true">${c.corps || ''}</div>
                    <textarea name="corps" id="editCorps" style="display:none;"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                </div>
            </div>
        </form>`;
    new bootstrap.Modal(document.getElementById('editModal')).show();

    // Auto-détecter les variables dans le corps existant
    const editVarNames = detectVariables(c.corps || '');
    editVarNames.forEach(name => addVariableRow('edit', name, ''));
}

// Imprimer un courrier
function printCourrier(id) {
    const c = courriersData.find(x => x.id == id);
    if (!c) return;

    const destName = [c.civilite_dest, c.prenom_dest, c.nom_dest].filter(Boolean).join(' ') || c.nom_prenom_dest;
    let destLines = destName;
    if (c.complement_dest) destLines += '<br>' + c.complement_dest;
    destLines += '<br>' + c.adresse_dest;
    if (c.complement_adresse_dest) destLines += '<br>' + c.complement_adresse_dest;
    destLines += '<br>' + c.cp_ville_dest;

    const printArea = document.getElementById('printArea');
    printArea.innerHTML = `
        <div style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;line-height:1.4;color:#000;position:relative;">
            <div>
                <img src="https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2" alt="Caisse d'Épargne" style="max-width:180px;height:auto;">
                <div style="margin-top:4px;font-size:9pt;line-height:1.3;">
                    ${userData.prenom} ${userData.nom}<br>
                    5 Avenue Charles de Gaulle<br>
                    12700 Capdenac-Gare<br>
                    ${userData.tel_pro ? userData.tel_pro + '<br>' : ''}
                    ${userData.email_pro ? userData.email_pro : ''}
                </div>
            </div>
            <div style="position:absolute;top:47mm;left:100mm;width:85mm;font-size:11pt;line-height:1.5;">
                ${destLines}
            </div>
            <div style="margin-top:40mm;"></div>
            <div style="text-align:right;">
                ${c.lieu || 'Capdenac-Gare'}, le ${fmtDate(c.date_courrier)}
            </div>
            <br><br>
            <div style="font-weight:bold;">Objet : ${c.objet}</div>
            <br><br>
            <div style="text-align:justify;">${c.corps}</div>
            <div style="text-align:right;margin-top:40px;">${userData.prenom} ${userData.nom}</div>
        </div>
    `;
    printArea.style.display = 'block';

    // Fermer les modales si ouvertes
    document.querySelectorAll('.modal.show').forEach(m => {
        bootstrap.Modal.getInstance(m)?.hide();
    });

    setTimeout(() => {
        window.print();
        printArea.style.display = 'none';
    }, 300);
}

// Auto-ouverture du dossier après enregistrement
const urlParams = new URLSearchParams(window.location.search);
const openId = urlParams.get('open');
if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
