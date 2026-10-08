<?php
// Barre de boutons communs aux outils (portail et /tools). Option : $actionsOpts = ['link' => bool, 'copy' => bool, 'print' => bool, 'mail' => bool]
$o = array_merge(['link' => true, 'copy' => true, 'print' => true, 'mail' => true], $actionsOpts ?? []);
$actionsOpts = null;
?>
<div class="d-flex flex-wrap gap-2 mb-3 no-print">
    <?php if ($o['link']): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-tool-action="link" title="Copie un lien qui reprend les valeurs saisies (rien n'est enregistré sur le serveur)"><i class="fas fa-link me-1"></i>Copier le lien</button><?php endif; ?>
    <?php if ($o['copy']): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-tool-action="copy"><i class="fas fa-copy me-1"></i>Copier le résultat</button><?php endif; ?>
    <?php if ($o['print']): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-tool-action="print"><i class="fas fa-print me-1"></i>Imprimer</button><?php endif; ?>
    <?php if ($o['mail']): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-tool-action="mail"><i class="fas fa-envelope me-1"></i>Envoyer par e-mail</button><?php endif; ?>
</div>
