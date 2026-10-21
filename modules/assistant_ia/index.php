<?php
$pageTitle = 'Assistant IA';
require_once __DIR__ . '/../../templates/header.php';
$iaEndpoint = '../../tools/ia_chat.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-robot"></i> Assistant IA</h4>
    <p class="text-muted mb-0">Un assistant de conversation pour rédiger, expliquer, calculer ou préparer un entretien.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
