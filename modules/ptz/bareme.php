<?php
/**
 * Barème du Prêt à Taux Zéro (plafonds de ressources et d'opération, quotités, durées).
 * Lu par le module du portail et par la page publique /tools (lecture seule) ; seul un administrateur peut le modifier.
 * Les valeurs fournies par défaut sont PROVISOIRES : elles doivent être contrôlées sur l'arrêté en vigueur puis validées (champ « valide »).
 */
function ptzDefaultBareme() {
    return [
        'millesime' => 'offres émises à partir d\'avril 2025',
        'valide' => false,
        // Coefficient familial (1, 2, 3, 4 puis 5 personnes et plus)
        'coeff_familial' => [1, 1.5, 1.8, 2.1, 2.4],
        // Revenu maximal pour obtenir le PTZ, selon la zone et le nombre de personnes (1 à 7, puis 8 et plus)
        'plafonds_ressources' => [
            'A' => [49000, 73500, 88200, 102900, 117600, 132300, 147000, 161700],
            'B1' => [34500, 51750, 62100, 72450, 82800, 93150, 103500, 113850],
            'B2' => [31500, 47250, 56700, 66150, 75600, 85050, 94500, 103950],
            'C' => [28500, 42750, 51300, 59850, 68400, 76950, 85500, 94050],
        ],
        // Limites hautes des tranches 1 à 4, applicables au revenu divisé par le coefficient familial
        'tranches' => [
            'A' => [25000, 31000, 37000, 49000], 'B1' => [21500, 26000, 30000, 34500],
            'B2' => [18000, 22500, 27000, 31500], 'C' => [15000, 19500, 24000, 28500],
        ],
        // Coût maximal de l'opération pris en compte, selon la zone et le nombre de personnes (1, 2, 3, 4, puis 5 et plus)
        'plafonds_operation' => [
            'A' => [150000, 225000, 270000, 315000, 360000], 'B1' => [135000, 202500, 243000, 283500, 324000],
            'B2' => [110000, 165000, 198000, 231000, 264000], 'C' => [100000, 150000, 180000, 210000, 240000],
        ],
        // Part du coût financée par le PTZ (%) par tranche, et zones où le type de logement est éligible
        'types' => [
            'collectif_neuf' => ['label' => 'Logement neuf en bâtiment collectif', 'zones' => ['A', 'B1', 'B2', 'C'], 'quotites' => [50, 40, 40, 20]],
            'maison_neuve' => ['label' => 'Maison individuelle neuve', 'zones' => ['A', 'B1', 'B2', 'C'], 'quotites' => [30, 20, 20, 10]],
            'ancien_travaux' => ['label' => 'Ancien avec travaux (au moins 25 % du coût)', 'zones' => ['B2', 'C'], 'quotites' => [50, 40, 40, 20]],
        ],
        // Durée totale et différé de remboursement (en années) par tranche
        'durees' => [['total' => 25, 'differe' => 10], ['total' => 20, 'differe' => 8], ['total' => 15, 'differe' => 2], ['total' => 10, 'differe' => 0]],
        // Revenu retenu = le plus élevé du revenu fiscal de référence et du coût de l'opération divisé par ce nombre
        'diviseur_cout' => 9,
    ];
}

/** Barème PTZ en vigueur (géré dans l'administration, onglet « Barèmes ») ; « valide » = barème contrôlé. */
function ptzGetBareme() {
    require_once __DIR__ . '/../../includes/baremes.php';
    $m = baremeGet('ptz');
    $b = $m['data'];
    $b['valide'] = $m['valide'];
    if ($m['date']) $b['millesime'] = 'au ' . date('d/m/Y', strtotime($m['date']));
    return $b;
}

/** Valide la structure d'un barème PTZ ; renvoie un message d'erreur ou null. */
function ptzCheckBareme($b) {
    if (!is_array($b)) return 'Données invalides.';
    foreach (['coeff_familial', 'plafonds_ressources', 'tranches', 'plafonds_operation', 'types', 'durees', 'diviseur_cout'] as $k) if (!isset($b[$k])) return "Donnée manquante : $k";
    if (count($b['coeff_familial']) !== 5) return 'Coefficients familiaux : 5 valeurs attendues.';
    foreach (['A', 'B1', 'B2', 'C'] as $z) {
        if (count($b['plafonds_ressources'][$z] ?? []) !== 8) return "Plafonds de ressources : 8 valeurs attendues pour la zone $z.";
        if (count($b['tranches'][$z] ?? []) !== 4) return "Tranches : 4 limites attendues pour la zone $z.";
        if (count($b['plafonds_operation'][$z] ?? []) !== 5) return "Coûts plafonds : 5 valeurs attendues pour la zone $z.";
        $t = $b['tranches'][$z];
        if (!($t[0] < $t[1] && $t[1] < $t[2] && $t[2] < $t[3])) return "Tranches de la zone $z : les limites doivent être croissantes.";
    }
    if (count($b['durees']) !== 4) return 'Durées : 4 tranches attendues.';
    foreach ($b['durees'] as $d) if (!isset($d['total'], $d['differe']) || $d['differe'] >= $d['total']) return 'Durées : le différé doit être inférieur à la durée totale.';
    foreach ($b['types'] as $t) if (count($t['quotites'] ?? []) !== 4 || !is_array($t['zones'] ?? null)) return 'Chaque type de logement doit avoir 4 quotités et une liste de zones.';
    if ((float)$b['diviseur_cout'] <= 0) return 'Le diviseur du coût doit être positif.';
    return null;
}
