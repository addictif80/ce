<?php
/**
 * Barème du Prêt à Taux Zéro (plafonds de ressources et d'opération, quotités, durées).
 * Lu par le module du portail et par la page publique /tools (lecture seule) ; seul un administrateur peut le modifier.
 * Les valeurs fournies par défaut sont PROVISOIRES : elles doivent être contrôlées sur l'arrêté en vigueur puis validées (champ « valide »).
 */
function ptzDefaultBareme() {
    return [
        'millesime' => '2025 (provisoire)',
        'valide' => false,
        'coeff' => ['1' => 1, '2' => 1.5, '3' => 1.8, '4' => 2.1, '5' => 2.4, '6' => 2.7, '7' => 3.0, '8' => 3.3],
        // Plafonds de ressources (RFR) pour 1 personne : bornes hautes des tranches 1 à 4
        'plafonds_revenus' => [
            'A' => [25000, 31500, 37900, 49000], 'B1' => [21500, 27000, 32500, 34500],
            'B2' => [19500, 24500, 29500, 31500], 'C' => [18000, 22500, 27000, 28500],
        ],
        // Plafonds de prix de l'opération pour 1, 2, 3, 4, 5 personnes et plus
        'plafonds_operation' => [
            'A' => [150000, 210000, 255000, 300000, 345000], 'B1' => [135000, 189000, 230000, 270000, 310000],
            'B2' => [110000, 154000, 187000, 220000, 253000], 'C' => [100000, 140000, 170000, 200000, 230000],
        ],
        // Quotité (% du prix plafonné) par tranche, zones où le type de bien est éligible
        'types' => [
            'collectif_neuf' => ['label' => 'Logement neuf collectif', 'zones' => ['A', 'B1', 'B2', 'C'], 'quotites' => [50, 40, 30, 20]],
            'maison_neuve' => ['label' => 'Maison individuelle neuve', 'zones' => ['A', 'B1', 'B2', 'C'], 'quotites' => [30, 20, 10, 10]],
            'ancien_travaux' => ['label' => 'Ancien avec travaux (≥ 25 % du coût)', 'zones' => ['B2', 'C'], 'quotites' => [40, 40, 40, 40]],
        ],
        // Durée totale et différé (en années) par tranche
        'durees' => [['total' => 25, 'differe' => 15], ['total' => 22, 'differe' => 10], ['total' => 20, 'differe' => 5], ['total' => 20, 'differe' => 0]],
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

/** Valide la structure d'un barème saisi par l'administrateur ; renvoie un message d'erreur ou null. */
function ptzCheckBareme($b) {
    if (!is_array($b)) return 'JSON invalide.';
    foreach (['coeff', 'plafonds_revenus', 'plafonds_operation', 'types', 'durees'] as $k) if (!isset($b[$k]) || !is_array($b[$k])) return "Clé manquante : $k";
    foreach (['A', 'B1', 'B2', 'C'] as $z) {
        if (count($b['plafonds_revenus'][$z] ?? []) !== 4) return "Plafonds de ressources : 4 tranches attendues pour la zone $z.";
        if (count($b['plafonds_operation'][$z] ?? []) !== 5) return "Plafonds d'opération : 5 valeurs attendues pour la zone $z.";
    }
    if (count($b['durees']) !== 4) return 'Durées : 4 tranches attendues.';
    foreach ($b['types'] as $t) if (count($t['quotites'] ?? []) !== 4 || !is_array($t['zones'] ?? null)) return 'Chaque type de bien doit avoir 4 quotités et une liste de zones.';
    return null;
}
