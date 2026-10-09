<?php
$pageTitle = 'Mémo : délais légaux';
require_once __DIR__ . '/../../templates/header.php';
$memoKey = 'memo_delais';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-hourglass-half"></i> Mémo : délais légaux</h4>
    <p class="text-muted mb-0">Délais de réflexion, de rétractation, de contestation et de réponse les plus courants. Mémo de consultation : rien n'est enregistré.</p>
</div>
<?php require __DIR__ . '/../memos/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
