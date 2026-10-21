<?php
// Rubriques du calculateur de budget, partagées par le module du portail et la page publique /tools.
// Quatre sections : revenus, dépenses fixes (repère 50 %), dépenses variables (30 %), épargne (20 %). Chaque ligne peut recevoir plusieurs montants,
// et chaque section peut recevoir des lignes personnalisées.
$budget_sections = [
    'revenus'   => ['icon' => 'fa-arrow-circle-down', 'title' => 'Revenus mensuels', 'cible' => null, 'lignes' => [
        'salaire' => 'Salaire net mensuel', 'salaire_conjoint' => 'Salaire net conjoint', 'autres_revenus' => 'Autres revenus',
        'autres_revenus_conjoint' => 'Autres revenus conjoint', 'allocations' => 'Allocations (CAF, etc.)', 'pensions' => 'Pensions',
        'pensions_conjoint' => 'Pensions conjoint', 'revenus_fonciers' => 'Revenus fonciers', 'revenus_fonciers_conjoint' => 'Revenus fonciers conjoint']],
    'fixes'     => ['icon' => 'fa-file-invoice-dollar', 'title' => 'Dépenses fixes', 'sub' => 'loyer, crédits, assurances, mutuelle, factures, taxes et impôts', 'cible' => 50, 'lignes' => [
        'loyer_charges' => 'Loyer et charges', 'credit_immo' => 'Crédit immobilier', 'credits_conso' => 'Crédits consommation',
        'assurance_habitation' => 'Assurance habitation', 'assurance_auto' => 'Assurance auto', 'assurance_sante' => 'Assurance santé / mutuelle',
        'electricite_gaz' => 'Électricité / Gaz', 'eau' => 'Eau', 'impots' => 'Impôts sur le revenu', 'taxe_fonciere' => 'Taxe foncière', 'taxe_habitation' => 'Taxe d\'habitation']],
    'variables' => ['icon' => 'fa-shopping-cart', 'title' => 'Dépenses variables', 'sub' => 'non essentielles et du quotidien : téléphone, box, abonnements, loisirs…', 'cible' => 30, 'lignes' => [
        'telephone_internet' => 'Téléphone / Box internet', 'abonnements' => 'Abonnements (Netflix, Spotify…)', 'transport' => 'Transport', 'alimentation' => 'Alimentation',
        'habillement' => 'Habillement', 'sante' => 'Santé', 'loisirs' => 'Loisirs', 'divers' => 'Divers']],
    'epargne'   => ['icon' => 'fa-piggy-bank', 'title' => 'Épargne', 'sub' => 'mise de côté chaque mois', 'cible' => 20, 'lignes' => [
        'epargne_mensuelle' => 'Épargne mensuelle']],
];
$conjoint_fields = ['salaire_conjoint', 'autres_revenus_conjoint', 'pensions_conjoint', 'revenus_fonciers_conjoint'];

/** Identifiants des colonnes historiques de la table calculateur_budget (une colonne par rubrique par défaut). */
function budgetLegacyIds() {
    global $budget_sections;
    $ids = [];
    foreach ($budget_sections as $s) foreach ($s['lignes'] as $id => $l) $ids[] = $id;
    return $ids;
}

/** État initial : lignes par défaut, avec les montants du budget enregistré (JSON, sinon colonnes historiques). */
function budgetBuildState($budget, $avecConjoint = 0) {
    global $budget_sections, $conjoint_fields;
    $saved = ($budget && !empty($budget['lignes_json'])) ? json_decode($budget['lignes_json'], true) : null;
    $state = ['conjoint' => (int)$avecConjoint, 'sections' => []];
    foreach ($budget_sections as $key => $sec) {
        $lines = [];
        $savedLines = is_array($saved['sections'][$key] ?? null) ? $saved['sections'][$key] : null;
        if ($savedLines !== null) {
            foreach ($savedLines as $l) {
                $id = (string)($l['id'] ?? '');
                $a = array_values(array_map('floatval', array_slice((array)($l['a'] ?? []), 0, 12)));
                $custom = !isset($sec['lignes'][$id]);
                $lines[] = ['id' => $custom ? '' : $id, 'label' => $custom ? mb_substr(trim((string)($l['label'] ?? '')), 0, 60) : $sec['lignes'][$id], 'custom' => $custom, 'conj' => in_array($id, $conjoint_fields, true), 'a' => $a ?: [0]];
            }
            // rubriques par défaut absentes de l'enregistrement : ajoutées vides
            $have = array_column($lines, 'id');
            foreach ($sec['lignes'] as $id => $label) if (!in_array($id, $have, true)) $lines[] = ['id' => $id, 'label' => $label, 'custom' => false, 'conj' => in_array($id, $conjoint_fields, true), 'a' => [0]];
        } else {
            foreach ($sec['lignes'] as $id => $label) {
                $v = ($budget && isset($budget[$id])) ? (float)$budget[$id] : 0;
                $lines[] = ['id' => $id, 'label' => $label, 'custom' => false, 'conj' => in_array($id, $conjoint_fields, true), 'a' => [$v]];
            }
        }
        $state['sections'][$key] = $lines;
    }
    return $state;
}

/** Valide un état reçu du navigateur (taille bornée) et le renvoie nettoyé. */
function budgetSanitizeState($raw) {
    global $budget_sections;
    $in = is_string($raw) ? json_decode($raw, true) : $raw;
    $out = ['conjoint' => !empty($in['conjoint']) ? 1 : 0, 'sections' => []];
    foreach ($budget_sections as $key => $sec) {
        $out['sections'][$key] = [];
        foreach (array_slice((array)($in['sections'][$key] ?? []), 0, 40) as $l) {
            $id = (string)($l['id'] ?? '');
            $isDefault = isset($sec['lignes'][$id]);
            $label = $isDefault ? '' : mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($l['label'] ?? ''))), 0, 60);
            if (!$isDefault && $label === '') continue;
            $a = []; foreach (array_slice((array)($l['a'] ?? []), 0, 12) as $v) { $v = (float)$v; $a[] = round(max(0, min(9999999, $v)), 2); }
            $out['sections'][$key][] = ['id' => $isDefault ? $id : '', 'label' => $label, 'a' => $a ?: [0]];
        }
    }
    return $out;
}

/** Totaux par rubrique par défaut (colonnes historiques de la table). */
function budgetLegacyValues(array $state) {
    $v = [];
    foreach ($state['sections'] as $lines) foreach ($lines as $l) if ($l['id'] !== '') $v[$l['id']] = round(array_sum($l['a']), 2);
    return $v;
}
