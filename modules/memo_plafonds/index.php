<?php
$pageTitle = 'Mémo : plafonds et seuils';
require_once __DIR__ . '/../../templates/header.php';
$memoKey = 'memo_plafonds';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-gauge-high"></i> Mémo : plafonds et seuils</h4>
    <p class="text-muted mb-0">Plafonds de dépôt, d'espèces, de garantie des dépôts et abattements usuels. Mémo de consultation : rien n'est enregistré.</p>
</div>
<?php require __DIR__ . '/../memos/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
