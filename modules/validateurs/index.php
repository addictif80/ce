<?php
$pageTitle = 'Validateurs de numéros';
require_once __DIR__ . '/../../templates/header.php';
?>
<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-shield-halved"></i> Validateurs de numéros</h4>
    <p class="text-muted mb-0">Contrôlez la forme d'un IBAN, d'un RIB, d'un SIREN / SIRET, d'une carte, d'un numéro de sécurité sociale ou d'un BIC. Par prudence, aucun numéro n'est enregistré, même ici.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
