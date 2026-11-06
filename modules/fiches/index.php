<?php
$pageTitle = 'Fiches à imprimer';
require_once __DIR__ . '/../../templates/header.php';
$ficheTypes = ['instances', 'production', 'phoning'];
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-print"></i> Fiches à imprimer</h4>
    <p class="text-muted mb-0">Maquettes A4 vierges (instances, production, phoning) à imprimer pour travailler sur papier. Les instances peuvent aussi être transmises par e-mail à un collaborateur avant un congé.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
