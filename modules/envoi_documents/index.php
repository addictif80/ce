<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';

$db     = getDB();
$userId = getCurrentUserId();

// --- Auto-création des tables ---
$db->exec("CREATE TABLE IF NOT EXISTS `envois_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `numero_personne` VARCHAR(100) NOT NULL,
    `prenom_client` VARCHAR(255) NOT NULL,
    `nom_client` VARCHAR(255) NOT NULL,
    `email_client` VARCHAR(255) NOT NULL,
    `date_envoi` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `envoi_documents_liste` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `envoi_id` INT NOT NULL,
    `nom_document` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`envoi_id`) REFERENCES `envois_documents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- Génération EML (avant tout output HTML) ---
if (isset($_GET['action']) && $_GET['action'] === 'gen_eml') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM envois_documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $envoi = $stmt->fetch();

    if (!$envoi) {
        header('Location: index.php');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM envoi_documents_liste WHERE envoi_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $docs = $stmt->fetchAll();

    $prenomNom    = $envoi['prenom_client'] . ' ' . strtoupper($envoi['nom_client']);
    $nomClientEsc = htmlspecialchars($prenomNom, ENT_QUOTES);
    $nbDocs       = count($docs);

    $subject = '[CEMP] Nouveau document disponible';
    $bandeau = $nbDocs > 1 ? 'Nouveaux documents disponibles' : 'Nouveau document disponible';
    $introHtml = $nbDocs > 1
        ? 'Nous avons le plaisir de vous informer que les documents suivants sont d&eacute;sormais disponibles&nbsp;:'
        : 'Nous avons le plaisir de vous informer que le document suivant est d&eacute;sormais disponible&nbsp;:';
    $introTxt = $nbDocs > 1
        ? "Nous avons le plaisir de vous informer que les documents suivants sont désormais disponibles :"
        : "Nous avons le plaisir de vous informer que le document suivant est désormais disponible :";

    // Variable pré-calculée pour le heredoc
    $cesDocuments = $nbDocs > 1 ? 'ces documents' : 'ce document';

    // Listes de documents
    $listeHtml = '<ul style="margin:8px 0 0 0;padding-left:20px;">'
        . implode('', array_map(
            fn($d) => '<li>' . htmlspecialchars($d['nom_document'], ENT_QUOTES) . '</li>',
            $docs
        ))
        . '</ul>';
    $listeTxt = implode("\r\n", array_map(fn($d) => '  - ' . $d['nom_document'], $docs));

    // Corps texte brut
    $textBody  = "Madame, Monsieur,\r\n\r\n";
    $textBody .= $introTxt . "\r\n\r\n";
    $textBody .= $listeTxt . "\r\n\r\n";
    $textBody .= "Vous trouverez " . ($nbDocs > 1 ? 'ces documents' : 'ce document') . " en pi\xc3\xa8ce jointe de ce message.\r\n\r\n";
    $textBody .= "Nous restons disponibles pour tout renseignement compl\xc3\xa9mentaire.\r\n\r\n";
    $textBody .= "Cordialement\r\n\r\n";
    $textBody .= "---\r\n";
    $textBody .= "Ce message est g\xc3\xa9n\xc3\xa9r\xc3\xa9 automatiquement. Merci de ne pas y r\xc3\xa9pondre directement.";

    // Corps HTML
    $logoUrl = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#f5f5f5;padding:30px 0;">
    <tr>
      <td align="center">

        <!-- Carte centrale -->
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;
                      border-radius:8px;overflow:hidden;
                      box-shadow:0 2px 12px rgba(0,0,0,0.08);">

          <!-- En-tête rouge -->
          <tr>
            <td style="background-color:#CF0A2C;padding:22px 32px;">
              <img src="{$logoUrl}"
                   alt="Caisse d'Épargne" height="38"
                   style="display:block;border:0;height:38px;">
            </td>
          </tr>

          <!-- Bandeau sous-titre -->
          <tr>
            <td style="background-color:#a50823;padding:8px 32px;">
              <p style="margin:0;font-size:11px;color:#f9c9c9;
                        text-transform:uppercase;letter-spacing:0.08em;">
                {$bandeau}
              </p>
            </td>
          </tr>

          <!-- Contenu principal -->
          <tr>
            <td style="padding:36px 32px 28px 32px;">

              <p style="margin:0 0 20px 0;font-size:15px;color:#333333;line-height:1.7;">
                Madame, Monsieur,
              </p>

              <p style="margin:0 0 20px 0;font-size:15px;color:#333333;line-height:1.7;">
                {$introHtml}
              </p>

              <!-- Liste des documents -->
              <table cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:24px;background:#f8f8f8;
                            border-left:3px solid #CF0A2C;
                            border-radius:0 4px 4px 0;">
                <tr>
                  <td style="padding:14px 16px;">
                    <p style="margin:0 0 8px 0;font-size:12px;color:#888888;
                               text-transform:uppercase;letter-spacing:0.06em;">
                      Document(s)
                    </p>
                    {$listeHtml}
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 16px 0;font-size:14px;color:#333333;line-height:1.7;">
                Vous trouverez {$cesDocuments} en pi&egrave;ce jointe de ce message.
              </p>

              <p style="margin:0 0 28px 0;font-size:14px;color:#333333;line-height:1.7;">
                Nous restons disponibles pour tout renseignement compl&eacute;mentaire.
              </p>

              <p style="margin:0 0 32px 0;font-size:14px;color:#333333;line-height:1.7;">
                Cordialement
              </p>

            </td>
          </tr>

          <!-- Pied de page automatique -->
          <tr>
            <td style="background-color:#f0f0f0;padding:14px 32px;
                       border-top:1px solid #e0e0e0;">
              <p style="margin:0;font-size:11px;color:#999999;line-height:1.6;
                        font-style:italic;">
                Ce message est g&eacute;n&eacute;r&eacute; automatiquement.
                Merci de ne pas y r&eacute;pondre directement.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    // Construction du .eml multipart/alternative
    $boundary      = 'bound_' . md5(uniqid('emldoc_', true));
    $subjectHeader = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $safeName      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $envoi['numero_personne']);
    $filename      = 'envoi_doc_' . $safeName . '_' . date('Ymd') . '.eml';
    $toHeader      = $nomClientEsc . ' <' . $envoi['email_client'] . '>';

    header('Content-Type: message/rfc822');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');

    $eml  = "MIME-Version: 1.0\r\n";
    $eml .= "Date: " . date('r') . "\r\n";
    $eml .= "Subject: {$subjectHeader}\r\n";
    $eml .= "To: {$toHeader}\r\n";
    $eml .= "X-Unsent: 1\r\n";
    $eml .= "Content-Type: multipart/alternative;\r\n\tboundary=\"{$boundary}\"\r\n";
    $eml .= "\r\n";

    $eml .= "--{$boundary}\r\n";
    $eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
    $eml .= "\r\n";
    $eml .= quoted_printable_encode($textBody) . "\r\n";

    $eml .= "--{$boundary}\r\n";
    $eml .= "Content-Type: text/html; charset=UTF-8\r\n";
    $eml .= "Content-Transfer-Encoding: quoted-printable\r\n";
    $eml .= "\r\n";
    $eml .= quoted_printable_encode($htmlBody) . "\r\n";

    $eml .= "--{$boundary}--\r\n";

    echo $eml;
    exit;
}

// --- Handlers POST ---

// Ajout envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $db->prepare("INSERT INTO envois_documents
        (user_id, numero_personne, prenom_client, nom_client, email_client, date_envoi)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        trim($_POST['numero_personne']),
        trim($_POST['prenom_client']),
        trim($_POST['nom_client']),
        trim($_POST['email_client']),
        $_POST['date_envoi'],
    ]);
    $envoisId = (int)$db->lastInsertId();

    if (!empty($_POST['documents'])) {
        $stmtDoc = $db->prepare("INSERT INTO envoi_documents_liste (envoi_id, nom_document) VALUES (?, ?)");
        foreach ($_POST['documents'] as $doc) {
            $doc = trim($doc);
            if ($doc !== '') {
                $stmtDoc->execute([$envoisId, $doc]);
            }
        }
    }

    header('Location: index.php?gen=' . $envoisId);
    exit;
}

// Suppression envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id   = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM envois_documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php');
    exit;
}

// Édition envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id   = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE envois_documents SET
        numero_personne = ?, prenom_client = ?, nom_client = ?,
        email_client = ?, date_envoi = ?
        WHERE id = ? AND user_id = ?");
    $stmt->execute([
        trim($_POST['numero_personne']),
        trim($_POST['prenom_client']),
        trim($_POST['nom_client']),
        trim($_POST['email_client']),
        $_POST['date_envoi'],
        $id,
        $userId,
    ]);

    $toDelete = isset($_POST['delete_docs']) ? array_map('intval', $_POST['delete_docs']) : [];

    if (!empty($_POST['existing_docs'])) {
        foreach ($_POST['existing_docs'] as $docId => $docName) {
            $docId   = (int)$docId;
            $docName = trim($docName);
            if (!in_array($docId, $toDelete) && $docName !== '') {
                $stmt = $db->prepare("UPDATE envoi_documents_liste SET nom_document = ? WHERE id = ? AND envoi_id = ?");
                $stmt->execute([$docName, $docId, $id]);
            }
        }
    }

    foreach ($toDelete as $docId) {
        $stmt = $db->prepare("DELETE FROM envoi_documents_liste WHERE id = ? AND envoi_id = ?");
        $stmt->execute([$docId, $id]);
    }

    if (!empty($_POST['new_docs'])) {
        $stmtDoc = $db->prepare("INSERT INTO envoi_documents_liste (envoi_id, nom_document) VALUES (?, ?)");
        foreach ($_POST['new_docs'] as $doc) {
            $doc = trim($doc);
            if ($doc !== '') {
                $stmtDoc->execute([$id, $doc]);
            }
        }
    }

    header('Location: index.php?open=' . $id);
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_note') {
    addNote('envois_documents', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// --- Données ---
$stmt = $db->prepare("
    SELECT e.*,
        COUNT(edl.id) AS nb_docs
    FROM envois_documents e
    LEFT JOIN envoi_documents_liste edl ON edl.envoi_id = e.id
    WHERE e.user_id = ?
    GROUP BY e.id
    ORDER BY e.date_envoi DESC, e.created_at DESC
");
$stmt->execute([$userId]);
$envois = $stmt->fetchAll();

// Documents de tous les envois (pour les modals JS)
$allDocs = [];
foreach ($envois as $envoi) {
    $stmt = $db->prepare("SELECT * FROM envoi_documents_liste WHERE envoi_id = ? ORDER BY id ASC");
    $stmt->execute([$envoi['id']]);
    $allDocs[$envoi['id']] = $stmt->fetchAll();
}

$pageTitle = 'Envoi de documents';
require_once __DIR__ . '/../../templates/header.php';
?>

<!-- En-tête stats + bouton -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-info">
            <div class="stat-number"><?= count($envois) ?></div>
            <div class="stat-label">Envois générés</div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Nouvel envoi
        </button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Historique des envois</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchEnvois" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableEnvois">
        <thead>
            <tr>
                <th>N° Personne</th>
                <th>Prénom Nom</th>
                <th>Email</th>
                <th>Date d'envoi</th>
                <th>Documents</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($envois as $e): ?>
            <tr>
                <td><strong><?= e($e['numero_personne']) ?></strong></td>
                <td><?= e($e['prenom_client']) ?> <?= e($e['nom_client']) ?></td>
                <td><a href="mailto:<?= e($e['email_client']) ?>"><?= e($e['email_client']) ?></a></td>
                <td><?= formatDate($e['date_envoi']) ?></td>
                <td><?= (int)$e['nb_docs'] ?> document<?= $e['nb_docs'] > 1 ? 's' : '' ?></td>
                <td class="actions">
                    <a href="index.php?action=gen_eml&id=<?= $e['id'] ?>"
                       class="btn btn-sm btn-ce" title="Générer le .eml">
                        <i class="fas fa-envelope-open-text"></i> .eml
                    </a>
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $e['id'] ?>)" title="Voir">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editEnvoi(<?= $e['id'] ?>)" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Supprimer cet envoi ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
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
                <h5 class="modal-title">
                    <i class="fas fa-file-export"></i> Nouvel envoi de documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">N° Personne</label>
                            <input type="text" name="numero_personne" class="form-control"
                                   required placeholder="Ex : 12345678">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom_client" class="form-control"
                                   required placeholder="Prénom">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom_client" class="form-control"
                                   required placeholder="NOM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email client</label>
                            <input type="email" name="email_client" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'envoi</label>
                            <input type="date" name="date_envoi" class="form-control"
                                   required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Documents envoyés</label>
                            <div id="add-docs-list">
                                <div class="d-flex gap-2 mb-2 align-items-center">
                                    <input type="text" name="documents[]" class="form-control"
                                           placeholder="Nom du document" required>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="removeDocRow(this)" title="Supprimer">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-ce-outline btn-sm mt-1"
                                    onclick="addDocRow('add-docs-list', 'documents')">
                                <i class="fas fa-plus"></i> Ajouter un document
                            </button>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce">
                                <i class="fas fa-download"></i> Enregistrer et générer le .eml
                            </button>
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
                <h5 class="modal-title">
                    <i class="fas fa-file-export"></i> Détail de l'envoi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<!-- Modal Édition -->
<div class="modal fade modal-fullscreen-custom" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Modifier l'envoi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchEnvois', 'tableEnvois');

const envoisData = <?= json_encode($envois) ?>;
const allDocs    = <?= json_encode($allDocs) ?>;
const allNotes   = {};
<?php foreach ($envois as $envoi): ?>
allNotes[<?= $envoi['id'] ?>] = <?= json_encode(getNotes('envois_documents', $envoi['id'])) ?>;
<?php endforeach; ?>

function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '/' + parts[1] + '/' + parts[0];
}

function addDocRow(containerId, fieldName) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'd-flex gap-2 mb-2 align-items-center';
    div.innerHTML = `
        <input type="text" name="${fieldName}[]" class="form-control"
               placeholder="Nom du document" required>
        <button type="button" class="btn btn-outline-danger btn-sm"
                onclick="removeDocRow(this)" title="Supprimer">
            <i class="fas fa-minus"></i>
        </button>`;
    container.appendChild(div);
}

function removeDocRow(btn) {
    const row = btn.closest('div.d-flex');
    const container = row.parentElement;
    if (container.id === 'add-docs-list' && container.querySelectorAll('.d-flex').length <= 1) return;
    row.remove();
}

function showDetail(id) {
    const envoi = envoisData.find(e => e.id == id);
    if (!envoi) return;
    const docs  = allDocs[id]  || [];
    const notes = allNotes[id] || [];

    let docsHtml = docs.map(doc =>
        `<li class="mb-1">${doc.nom_document.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</li>`
    ).join('');

    let notesHtml = notes.map(n =>
        `<div class="note-item">
            <div class="note-meta">
                <strong>${n.prenom} ${n.nom}</strong> — ${formatLocalDateTime(n.created_at)}
            </div>
            <div class="note-content">${n.message}</div>
        </div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>N° Personne :</strong> ${envoi.numero_personne}</p>
                <p><strong>Prénom :</strong> ${envoi.prenom_client}</p>
                <p><strong>Nom :</strong> ${envoi.nom_client}</p>
                <p><strong>Email :</strong>
                    <a href="mailto:${envoi.email_client}">${envoi.email_client}</a>
                </p>
                <p><strong>Date d'envoi :</strong> ${formatDate(envoi.date_envoi)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Documents :</strong> ${docs.length}</p>
            </div>
        </div>
        <div class="mb-4">
            <h6 class="mb-2"><i class="fas fa-file-alt"></i> Documents envoyés</h6>
            ${docsHtml
                ? `<ul class="mb-0">${docsHtml}</ul>`
                : '<p class="text-muted">Aucun document</p>'}
        </div>
        <div class="mb-4">
            <a href="index.php?action=gen_eml&id=${id}" class="btn btn-ce">
                <i class="fas fa-envelope-open-text"></i> Régénérer le .eml
            </a>
        </div>
        <div class="notes-section">
            <h5><i class="fas fa-sticky-note"></i> Notes</h5>
            ${notesHtml || '<p class="text-muted">Aucune note</p>'}
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="record_id" value="${id}">
                <div class="input-group">
                    <input type="text" name="message" class="form-control"
                           placeholder="Ajouter une note..." required>
                    <button class="btn btn-ce" type="submit">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </form>
        </div>`;

    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editEnvoi(id) {
    const envoi = envoisData.find(e => e.id == id);
    if (!envoi) return;
    const docs = allDocs[id] || [];

    const esc = s => s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');

    let existingDocsHtml = docs.map(doc => `
        <div class="d-flex gap-2 mb-2 align-items-center">
            <input type="text" name="existing_docs[${doc.id}]" class="form-control"
                   value="${esc(doc.nom_document)}" required>
            <div class="form-check m-0 ps-0" style="min-width:32px">
                <input class="form-check-input" type="checkbox" name="delete_docs[]"
                       value="${doc.id}" id="del_${doc.id}"
                       style="width:1.2em;height:1.2em;cursor:pointer">
                <label class="form-check-label text-danger small" for="del_${doc.id}"
                       title="Cocher pour supprimer"><i class="fas fa-trash"></i></label>
            </div>
        </div>
    `).join('');

    document.getElementById('editContent').innerHTML = `
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">N° Personne</label>
                    <input type="text" name="numero_personne" class="form-control"
                           value="${esc(envoi.numero_personne)}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom_client" class="form-control"
                           value="${esc(envoi.prenom_client)}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom_client" class="form-control"
                           value="${esc(envoi.nom_client)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email client</label>
                    <input type="email" name="email_client" class="form-control"
                           value="${esc(envoi.email_client)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date d'envoi</label>
                    <input type="date" name="date_envoi" class="form-control"
                           value="${envoi.date_envoi}" required>
                </div>
                ${docs.length > 0 ? `
                <div class="col-12">
                    <label class="form-label">Documents existants
                        <small class="text-muted">
                            (cocher <i class="fas fa-trash text-danger"></i> pour supprimer)
                        </small>
                    </label>
                    <div id="existing-docs-list">${existingDocsHtml}</div>
                </div>` : ''}
                <div class="col-12">
                    <label class="form-label">Ajouter des documents</label>
                    <div id="new-docs-list-edit"></div>
                    <button type="button" class="btn btn-ce-outline btn-sm mt-1"
                            onclick="addDocRow('new-docs-list-edit', 'new_docs')">
                        <i class="fas fa-plus"></i> Ajouter un document
                    </button>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-ce">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>`;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-ouverture après ajout + téléchargement EML
const urlParams = new URLSearchParams(window.location.search);
const genId     = urlParams.get('gen');
const openId    = urlParams.get('open');

if (genId) {
    // Déclencher le téléchargement du .eml automatiquement
    const link = document.createElement('a');
    link.href  = 'index.php?action=gen_eml&id=' + genId;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    // Ouvrir le détail
    showDetail(parseInt(genId));
    history.replaceState(null, '', 'index.php');
}

if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
