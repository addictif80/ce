<?php
/** Zone de configuration placée au début du fichier généré : toutes les listes et réglages modifiables à la main, au même endroit. */
function sa_config_block(array $tools, array $groups, array $agences, $feedbackTo) {
    $cfgDir = __DIR__ . '/config/';
    $read = fn($f) => rtrim((string)@file_get_contents($cfgDir . $f)) . "\n";
    $blocks = function ($id, $titre, $aide, $contenu) {
        $com = '';
        foreach (explode("\n", trim($aide)) as $l) $com .= '# ' . trim($l) . "\n";
        return "\n<!-- ==================== $titre ==================== -->\n<script type=\"text/plain\" id=\"$id\">\n$com\n" . rtrim($contenu) . "\n</script>\n";
    };
    // outils
    $o = '';
    foreach ($groups as $g => $gk) {
        $o .= "\n# $g\n";
        foreach ($gk as $k) if (isset($tools[$k])) $o .= str_pad($k . ':true', 26) . '# ' . $tools[$k]['title'] . "\n";
    }
    // agences
    $a = '';
    foreach ($agences as [$nom, $num, $adr]) {
        $l1 = $adr; $l2 = '';
        if (preg_match('~^(.*),\s*(\d{5}\s+.+)$~u', (string)$adr, $m)) { $l1 = $m[1]; $l2 = $m[2]; }
        $a .= implode(' ; ', [$nom, $num, $l1, $l2, '']) . "\n";
    }
    $head = <<<TXT
<!--
  ==========================================================================================
   ZONE DE CONFIGURATION  -  tout ce qui se modifie à la main se trouve ICI, au début du fichier
  ==========================================================================================
   Règles communes :
   - une ligne par élément ; les champs sont séparés par un point-virgule « ; »
   - une ligne qui commence par # est un commentaire (ignorée)
   - aucune apostrophe ni guillemet à protéger : écrivez le texte tel quel
   - ne modifiez jamais les balises <script type="text/plain" ...> et </script>
   - une ligne mal écrite est simplement ignorée : la page ne casse jamais
   - enregistrez le fichier, puis actualisez la page (F5) pour voir le résultat
   Ce qui n'est pas ici (barèmes réglementaires, mémos, pièces justificatives…) se met à jour
   dans l'administration du portail, puis le fichier est régénéré.
  ==========================================================================================
-->
TXT;
    $out = $head . "\n";
    $out .= $blocks('cfg-general', 'RÉGLAGES GÉNÉRAUX', "Un réglage par ligne, au format  nom:valeur\nadresse_retours : destinataire préinscrit dans le fichier e-mail du formulaire « Un retour ? » (laisser vide = aucun)\nseuil_endettement : repère du taux d'endettement affiché dans le calculateur de budget, en %",
        'adresse_retours:' . $feedbackTo . "\nseuil_endettement:35");
    $out .= $blocks('cfg-outils', 'OUTILS AFFICHÉS', "Un outil par ligne, au format  nom:true  (affiché)  ou  nom:false  (masqué).\nUn outil qui n'est pas listé ici reste affiché. Le texte après le # n'est qu'un rappel du nom de l'outil.\n(calculateur = calculateur de budget ; courrier = générateur de courrier)", $o);
    $out .= $blocks('cfg-agences', 'AGENCES', "Format : Nom ; Numéro ; Adresse (rue) ; Code postal et ville ; Téléphone\nAlimente la liste des agences du générateur de courrier (adresse et téléphone repris dans la lettre)\net celle du bureau domiciliaire (affichée « NOM (numéro) »). Le téléphone est facultatif.", $a);
    $out .= $blocks('cfg-conseillers', 'SIGNATAIRES (CONSEILLERS DU COURRIER)', "Format : Prénom ; NOM ; Intitulé du poste ; Téléphone ; E-mail\nListe « Conseiller » du générateur de courrier (signature de la lettre).", $read('default_conseillers.txt'));
    $out .= $blocks('cfg-politesses', 'FORMULES DE POLITESSE (COURRIER)', "Format : Libellé affiché dans la liste ; Formule complète\n[IDENT] est remplacé par « M. DUPONT », « Mme MARTIN » ou « Madame, Monsieur ».", $read('default_politesses.txt'));
    $out .= $blocks('cfg-ponderation', 'PONDÉRATION DES REVENUS ET CHARGES (BUDGET)', "Format : Groupe ; Poste ; Pondération en % ; Commentaire ; credit (facultatif)\nGroupe = Revenus ou Charges fixes. Écrire credit en dernière colonne pour qu'un poste soit compté comme crédit\n(il entre alors dans le taux d'endettement).", $read('default_ponderation.txt'));
    $out .= $blocks('cfg-lignes', 'POSTES AFFICHÉS D\'OFFICE (BUDGET)', "Format : Groupe ; Poste   (le poste doit exister dans la pondération ci-dessus, même orthographe)\nPostes présents à l'ouverture du calculateur de budget.", $read('default_lignes.txt'));
    return $out . "\n";
}
