<?php
// Menu déroulant d'accès rapide aux fiches vierges à imprimer (portail). Les liens ouvrent la page « Fiches à imprimer » sur la fiche choisie.
?>
<div class="dropdown d-inline-block">
    <button class="btn btn-ce-outline dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-print"></i> Fiches vierges</button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="../fiches/index.php?type=instances"><i class="fas fa-hourglass-half me-2"></i>Fiche d'instances</a></li>
        <li><a class="dropdown-item" href="../fiches/index.php?type=production"><i class="fas fa-chart-line me-2"></i>Fiche de production</a></li>
        <li><a class="dropdown-item" href="../fiches/index.php?type=phoning"><i class="fas fa-phone-volume me-2"></i>Fiche de phoning</a></li>
    </ul>
</div>
