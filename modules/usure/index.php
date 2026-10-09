<?php
$pageTitle = 'Taux d\'usure';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-ban"></i> Taux d\'usure</h4>
    <p class="text-muted mb-0">Seuils de l\'usure en vigueur par catégorie de prêt, et contrôle du TAEG d\'une offre. Les seuils se mettent à jour dans l'administration (onglet Barèmes).</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
