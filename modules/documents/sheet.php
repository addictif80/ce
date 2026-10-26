<?php
$pageTitle = 'Tableur';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/documents.php';
$d = docsOwn((int)($_GET['id'] ?? 0));
if (!$d || $d['type'] !== 'sheet') { header('Location: index.php'); exit; }
$docMode = 'portal'; $docApi = 'api.php';
$docInit = ['id' => (int)$d['id'], 'nom' => $d['nom'], 'data' => json_decode($d['contenu'], true), 'links' => docsLinksOf($d['id']), 'updated_at' => $d['updated_at']];
?>
<h4 class="mb-3"><i class="fas fa-table text-success"></i> Tableur</h4>
<?php require __DIR__ . '/sheet_ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
