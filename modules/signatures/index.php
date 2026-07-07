<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$userId = getCurrentUserId();

// --- Auto-création des tables ---
$db->exec("CREATE TABLE IF NOT EXISTS `dossiers_signature` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `numero_personne` VARCHAR(100) NOT NULL,
    `nom_client` VARCHAR(255) NOT NULL,
    `email_client` VARCHAR(255) NOT NULL,
    `date_envoi` DATE NOT NULL,
    `statut` ENUM('en_cours','traite') DEFAULT 'en_cours',
    `date_traitement` DATE DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS `signature_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dossier_id` INT NOT NULL,
    `nom_document` VARCHAR(255) NOT NULL,
    `recu` TINYINT(1) DEFAULT 0,
    `date_reception` DATE DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_signature`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- Génération EML (avant tout output HTML) ---
if (isset($_GET['action']) && in_array($_GET['action'], ['gen_eml_initial', 'gen_eml_rappel'])) {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM dossiers_signature WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $dossier = $stmt->fetch();

    if (!$dossier) {
        header('Location: index.php');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM signature_documents WHERE dossier_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $docs = $stmt->fetchAll();

    $nomClientEsc = htmlspecialchars($dossier['nom_client'], ENT_QUOTES);

    if ($_GET['action'] === 'gen_eml_initial') {
        $subject    = 'Documents à retourner signés';
        $bandeau    = 'Documents à retourner signés';
        $docsSource = $docs;
        $intro      = 'Veuillez trouver ci-joints les documents suivants &agrave; nous retourner sign&eacute;s&nbsp;:';
        $introTxt   = "Veuillez trouver ci-joints les documents suivants à nous retourner signés :";
        $type       = 'envoi';
    } else {
        $docsSource = array_values(array_filter($docs, fn($d) => !$d['recu']));
        if (empty($docsSource)) {
            header('Location: index.php?open=' . $id);
            exit;
        }
        $dateEnvoi = formatDate($dossier['date_envoi']);
        $subject   = 'RAPPEL : Documents en attente de signature depuis le ' . $dateEnvoi;
        $bandeau   = 'Rappel &mdash; Documents en attente de signature';
        $intro     = 'Sauf erreur de notre part, nous n&rsquo;avons pas encore re&ccedil;u les documents suivants,
                      en attente de signature depuis le <strong>' . htmlspecialchars($dateEnvoi, ENT_QUOTES) . '</strong>&nbsp;:';
        $introTxt  = "Sauf erreur de notre part, nous n'avons pas encore reçu les documents suivants,\nen attente de signature depuis le $dateEnvoi :";
        $type      = 'rappel';
    }

    // Listes de documents
    $listeHtml = '<ul style="margin:8px 0 0 0;padding-left:20px;">'
        . implode('', array_map(fn($d) => '<li>' . htmlspecialchars($d['nom_document'], ENT_QUOTES) . '</li>', $docsSource))
        . '</ul>';
    $listeTxt = implode("\r\n", array_map(fn($d) => '  - ' . $d['nom_document'], $docsSource));

    // Corps texte brut
    $textBody  = "Madame, Monsieur,\r\n\r\n";
    $textBody .= $introTxt . "\r\n\r\n";
    $textBody .= $listeTxt . "\r\n\r\n";
    $textBody .= "Nous restons disponibles pour tout renseignement complémentaire.\r\n\r\n";
    $textBody .= "Cordialement";

    // Corps HTML (même design que mobiliz_mail.php)
    $logoUrl  = 'https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2';

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
                {$intro}
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
                      Documents
                    </p>
                    {$listeHtml}
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 28px 0;font-size:14px;color:#333333;line-height:1.7;">
                Nous restons disponibles pour tout renseignement compl&eacute;mentaire.
              </p>

              <p style="margin:0;font-size:14px;color:#333333;line-height:1.7;">
                Cordialement
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
    $boundary      = 'bound_' . md5(uniqid('emlsig_', true));
    $subjectHeader = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $safeName      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $dossier['numero_personne']);
    $filename      = $type . '_' . $safeName . '_' . date('Ymd') . '.eml';
    $toHeader      = $nomClientEsc . ' <' . $dossier['email_client'] . '>';

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

// Ajout dossier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $db->prepare("INSERT INTO dossiers_signature (user_id, numero_personne, nom_client, email_client, date_envoi) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        trim($_POST['numero_personne']),
        trim($_POST['nom_client']),
        trim($_POST['email_client']),
        $_POST['date_envoi'],
    ]);
    $dossierId = (int)$db->lastInsertId();

    if (!empty($_POST['documents'])) {
        $stmtDoc = $db->prepare("INSERT INTO signature_documents (dossier_id, nom_document) VALUES (?, ?)");
        foreach ($_POST['documents'] as $doc) {
            $doc = trim($doc);
            if ($doc !== '') {
                $stmtDoc->execute([$dossierId, $doc]);
            }
        }
    }

    header('Location: index.php?open=' . $dossierId . '&eml_initial=1');
    exit;
}

// Édition dossier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("UPDATE dossiers_signature SET numero_personne = ?, nom_client = ?, email_client = ?, date_envoi = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([
        trim($_POST['numero_personne']),
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
                $stmt = $db->prepare("UPDATE signature_documents SET nom_document = ? WHERE id = ? AND dossier_id = ?");
                $stmt->execute([$docName, $docId, $id]);
            }
        }
    }

    foreach ($toDelete as $docId) {
        $stmt = $db->prepare("DELETE FROM signature_documents WHERE id = ? AND dossier_id = ?");
        $stmt->execute([$docId, $id]);
    }

    if (!empty($_POST['new_docs'])) {
        $stmtDoc = $db->prepare("INSERT INTO signature_documents (dossier_id, nom_document) VALUES (?, ?)");
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

// Suppression dossier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $db->prepare("DELETE FROM dossiers_signature WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    header('Location: index.php');
    exit;
}

// Toggle document reçu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_recu'])) {
    $docId     = (int)$_POST['doc_id'];
    $dossierId = (int)$_POST['dossier_id'];

    $stmt = $db->prepare("SELECT sd.id, sd.recu FROM signature_documents sd
        JOIN dossiers_signature ds ON sd.dossier_id = ds.id
        WHERE sd.id = ? AND ds.id = ? AND ds.user_id = ?");
    $stmt->execute([$docId, $dossierId, $userId]);
    $doc = $stmt->fetch();

    if ($doc) {
        $newRecu = $doc['recu'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE signature_documents SET recu = ?, date_reception = ? WHERE id = ?");
        $stmt->execute([$newRecu, $newRecu ? date('Y-m-d') : null, $docId]);

        $stmt = $db->prepare("SELECT COUNT(*) FROM signature_documents WHERE dossier_id = ? AND recu = 0");
        $stmt->execute([$dossierId]);
        $nbNonRecu = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM signature_documents WHERE dossier_id = ?");
        $stmt->execute([$dossierId]);
        $nbTotal = (int)$stmt->fetchColumn();

        if ($nbNonRecu === 0 && $nbTotal > 0) {
            $stmt = $db->prepare("UPDATE dossiers_signature SET statut = 'traite', date_traitement = CURDATE() WHERE id = ? AND user_id = ?");
            $stmt->execute([$dossierId, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE dossiers_signature SET statut = 'en_cours', date_traitement = NULL WHERE id = ? AND user_id = ?");
            $stmt->execute([$dossierId, $userId]);
        }
    }

    header('Location: index.php?open=' . $dossierId);
    exit;
}

// Ajout note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_note') {
    addNote('dossiers_signature', (int)$_POST['record_id'], $_POST['message'], $userId);
    header('Location: index.php?open=' . (int)$_POST['record_id']);
    exit;
}

// --- Stats ---
$stmt = $db->prepare("SELECT COUNT(*) FROM dossiers_signature WHERE user_id = ? AND statut = 'en_cours'");
$stmt->execute([$userId]);
$nbEnCours = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM dossiers_signature WHERE user_id = ? AND statut = 'traite'");
$stmt->execute([$userId]);
$nbTraites = (int)$stmt->fetchColumn();

// --- Liste ---
$stmt = $db->prepare("
    SELECT ds.*,
        (SELECT COUNT(*) FROM signature_documents WHERE dossier_id = ds.id) AS nb_docs,
        (SELECT COUNT(*) FROM signature_documents WHERE dossier_id = ds.id AND recu = 1) AS nb_recus
    FROM dossiers_signature ds
    WHERE ds.user_id = ?
    ORDER BY CASE WHEN ds.statut = 'en_cours' THEN 0 ELSE 1 END, ds.date_envoi DESC
");
$stmt->execute([$userId]);
$dossiers = $stmt->fetchAll();

// --- Documents de tous les dossiers (pour les modals JS) ---
$allDocs = [];
foreach ($dossiers as $d) {
    $stmt = $db->prepare("SELECT * FROM signature_documents WHERE dossier_id = ? ORDER BY id ASC");
    $stmt->execute([$d['id']]);
    $allDocs[$d['id']] = $stmt->fetchAll();
}

$pageTitle = 'Suivi des signatures';
require_once __DIR__ . '/../../templates/header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-warning">
            <div class="stat-number"><?= $nbEnCours ?></div>
            <div class="stat-label">Dossiers en cours</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-success">
            <div class="stat-number"><?= $nbTraites ?></div>
            <div class="stat-label">Dossiers traités</div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Nouveau dossier
        </button>
    </div>
</div>

<!-- Tableau -->
<div class="data-table-container">
    <div class="data-table-header">
        <h3>Dossiers de signature</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchSignatures" placeholder="Rechercher...">
        </div>
    </div>
    <table class="data-table" id="tableSignatures">
        <thead>
            <tr>
                <th>N° Personne / Nom</th>
                <th>Nom client</th>
                <th>Email</th>
                <th>Date envoi</th>
                <th>Documents</th>
                <th>Statut</th>
                <th>Traité le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dossiers as $d): ?>
            <tr>
                <td><strong><?= e($d['numero_personne']) ?></strong></td>
                <td><?= e($d['nom_client']) ?></td>
                <td><a href="mailto:<?= e($d['email_client']) ?>"><?= e($d['email_client']) ?></a></td>
                <td><?= formatDate($d['date_envoi']) ?></td>
                <td>
                    <?php $pct = $d['nb_docs'] > 0 ? round($d['nb_recus'] / $d['nb_docs'] * 100) : 0; ?>
                    <span class="<?= $d['nb_recus'] == $d['nb_docs'] && $d['nb_docs'] > 0 ? 'text-success' : '' ?>">
                        <?= (int)$d['nb_recus'] ?> / <?= (int)$d['nb_docs'] ?>
                    </span>
                </td>
                <td>
                    <?php if ($d['statut'] === 'traite'): ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Traité</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En cours</span>
                    <?php endif; ?>
                </td>
                <td><?= formatDate($d['date_traitement']) ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-ce-outline" onclick="showDetail(<?= $d['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ce-outline" onclick="editDossier(<?= $d['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce dossier et tous ses documents ?')">
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

<!-- Modal Ajout -->
<div class="modal fade modal-fullscreen-custom" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature"></i> Nouveau dossier de signature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° Personne</label>
                            <input type="text" name="numero_personne" class="form-control" required placeholder="Ex : 12345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom client</label>
                            <input type="text" name="nom_client" class="form-control" required placeholder="Prénom NOM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email client</label>
                            <input type="email" name="email_client" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'envoi</label>
                            <input type="date" name="date_envoi" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Documents à signer</label>
                            <div id="add-docs-list">
                                <div class="d-flex gap-2 mb-2 align-items-center">
                                    <input type="text" name="documents[]" class="form-control" placeholder="Nom du document" required>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDocRow(this)" title="Supprimer">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-ce-outline btn-sm mt-1" onclick="addDocRow('add-docs-list', 'documents')">
                                <i class="fas fa-plus"></i> Ajouter un document
                            </button>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ce">
                                <i class="fas fa-save"></i> Enregistrer et générer l'e-mail
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
                <h5 class="modal-title"><i class="fas fa-file-signature"></i> Dossier de signature</h5>
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
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le dossier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent"></div>
        </div>
    </div>
</div>

<script>
filterTable('searchSignatures', 'tableSignatures');

const dossiersData = <?= json_encode($dossiers) ?>;
const allDocs      = <?= json_encode($allDocs) ?>;
const allNotes     = {};
<?php foreach ($dossiers as $d): ?>
allNotes[<?= $d['id'] ?>] = <?= json_encode(getNotes('dossiers_signature', $d['id'])) ?>;
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
        <input type="text" name="${fieldName}[]" class="form-control" placeholder="Nom du document" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDocRow(this)" title="Supprimer">
            <i class="fas fa-minus"></i>
        </button>`;
    container.appendChild(div);
}

function removeDocRow(btn) {
    const row = btn.closest('div.d-flex');
    // Ne pas supprimer si c'est la seule ligne dans le formulaire d'ajout
    const container = row.parentElement;
    if (container.id === 'add-docs-list' && container.querySelectorAll('.d-flex').length <= 1) return;
    row.remove();
}

function showDetail(id) {
    const dossier = dossiersData.find(d => d.id == id);
    if (!dossier) return;
    const docs  = allDocs[id]  || [];
    const notes = allNotes[id] || [];

    const nbRecus  = docs.filter(d => d.recu == 1).length;
    const nbTotal  = docs.length;
    const allDone  = nbTotal > 0 && nbRecus === nbTotal;

    let docsHtml = '';
    docs.forEach(doc => {
        const recu = doc.recu == 1;
        docsHtml += `
        <div class="d-flex align-items-center gap-2 mb-2">
            <form method="POST" class="d-inline m-0">
                <input type="hidden" name="toggle_recu" value="1">
                <input type="hidden" name="doc_id" value="${doc.id}">
                <input type="hidden" name="dossier_id" value="${id}">
                <button type="submit" class="btn btn-sm ${recu ? 'btn-success' : 'btn-outline-secondary'}"
                    title="${recu ? 'Marquer comme non reçu' : 'Marquer comme reçu'}">
                    <i class="fas fa-${recu ? 'check-circle' : 'circle'}"></i>
                </button>
            </form>
            <span class="${recu ? 'text-decoration-line-through text-muted' : 'fw-semibold'}">${doc.nom_document}</span>
            ${recu && doc.date_reception ? `<small class="text-muted">(reçu le ${formatDate(doc.date_reception)})</small>` : ''}
        </div>`;
    });

    const hasNonRecus = docs.some(d => d.recu == 0);
    const initialBtn = `<a href="index.php?action=gen_eml_initial&id=${id}" class="btn btn-ce-outline">
           <i class="fas fa-envelope-open-text"></i> Mail initial (.eml)
       </a>`;

    const rappelBtn = (dossier.statut === 'en_cours' && hasNonRecus)
        ? `<a href="index.php?action=gen_eml_rappel&id=${id}" class="btn btn-ce-outline">
               <i class="fas fa-envelope"></i> Rappel (.eml)
           </a>`
        : '';

    const traiteInfo = dossier.statut === 'traite'
        ? `<div class="alert alert-success py-2 mb-3">
               <i class="fas fa-check-circle"></i> Dossier <strong>traité</strong> le ${formatDate(dossier.date_traitement)}
           </div>`
        : '';

    let notesHtml = notes.map(n =>
        `<div class="note-item"><div class="note-meta"><strong>${n.prenom} ${n.nom}</strong> — ${formatLocalDateTime(n.created_at)}</div><div class="note-content">${n.message}</div></div>`
    ).join('');

    document.getElementById('detailContent').innerHTML = `
        ${traiteInfo}
        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>N° Personne :</strong> ${dossier.numero_personne}</p>
                <p><strong>Nom client :</strong> ${dossier.nom_client}</p>
                <p><strong>Email :</strong> <a href="mailto:${dossier.email_client}">${dossier.email_client}</a></p>
                <p><strong>Date d'envoi :</strong> ${formatDate(dossier.date_envoi)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Documents reçus :</strong>
                    <span class="${allDone ? 'text-success fw-bold' : ''}">${nbRecus} / ${nbTotal}</span>
                </p>
                <p><strong>Statut :</strong>
                    ${dossier.statut === 'traite'
                        ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Traité</span>'
                        : '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En cours</span>'}
                </p>
            </div>
        </div>
        <div class="mb-4">
            <h6 class="mb-3"><i class="fas fa-file-alt"></i> Documents</h6>
            ${docsHtml || '<p class="text-muted">Aucun document</p>'}
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4">${initialBtn}${rappelBtn}</div>
        <div class="notes-section">
            <h5><i class="fas fa-sticky-note"></i> Notes</h5>
            ${notesHtml || '<p class="text-muted">Aucune note</p>'}
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="record_id" value="${id}">
                <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Ajouter une note..." required>
                    <button class="btn btn-ce" type="submit"><i class="fas fa-plus"></i></button>
                </div>
            </form>
        </div>`;

    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function editDossier(id) {
    const dossier = dossiersData.find(d => d.id == id);
    if (!dossier) return;
    const docs = allDocs[id] || [];

    let existingDocsHtml = docs.map(doc => `
        <div class="d-flex gap-2 mb-2 align-items-center">
            <input type="text" name="existing_docs[${doc.id}]" class="form-control"
                value="${doc.nom_document.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}" required>
            <div class="form-check m-0 ps-0" style="min-width:32px">
                <input class="form-check-input" type="checkbox" name="delete_docs[]"
                    value="${doc.id}" id="del_${doc.id}" style="width:1.2em;height:1.2em;cursor:pointer">
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
                <div class="col-md-6">
                    <label class="form-label">N° Personne</label>
                    <input type="text" name="numero_personne" class="form-control"
                        value="${dossier.numero_personne.replace(/"/g,'&quot;')}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom client</label>
                    <input type="text" name="nom_client" class="form-control"
                        value="${dossier.nom_client.replace(/"/g,'&quot;')}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email client</label>
                    <input type="email" name="email_client" class="form-control"
                        value="${dossier.email_client}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date d'envoi</label>
                    <input type="date" name="date_envoi" class="form-control"
                        value="${dossier.date_envoi}" required>
                </div>
                ${docs.length > 0 ? `
                <div class="col-12">
                    <label class="form-label">Documents existants
                        <small class="text-muted">(cocher <i class="fas fa-trash text-danger"></i> pour supprimer)</small>
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
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>`;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-ouverture après enregistrement + téléchargement EML initial
const urlParams  = new URLSearchParams(window.location.search);
const openId     = urlParams.get('open');
const emlInitial = urlParams.get('eml_initial');

if (openId) {
    showDetail(parseInt(openId));
    history.replaceState(null, '', 'index.php' + (window.location.search.indexOf('embedded=1') !== -1 ? '?embedded=1' : ''));
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
