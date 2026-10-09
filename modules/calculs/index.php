<?php
$pageTitle = 'Boîte à calculs';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-calculator"></i> Boîte à calculs</h4>
    <p class="text-muted mb-0">Pourcentages, TVA, règle de trois, durées, prorata, intérêts simples et taux équivalents. Ce sont des calculs instantanés : rien n'est enregistré.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
