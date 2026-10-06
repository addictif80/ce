<?php
// Champs du calculateur de budget, partagés par le module du portail et la page publique /tools.
$revenus_fields = [
    'salaire' => 'Salaire net mensuel', 'salaire_conjoint' => 'Salaire net conjoint', 'autres_revenus' => 'Autres revenus',
    'autres_revenus_conjoint' => 'Autres revenus conjoint', 'allocations' => 'Allocations (CAF, etc.)', 'pensions' => 'Pensions',
    'pensions_conjoint' => 'Pensions conjoint', 'revenus_fonciers' => 'Revenus fonciers', 'revenus_fonciers_conjoint' => 'Revenus fonciers conjoint',
];
$conjoint_fields = ['salaire_conjoint', 'autres_revenus_conjoint', 'pensions_conjoint', 'revenus_fonciers_conjoint'];
$charges_fixes_fields = [
    'loyer_charges' => 'Loyer et charges', 'credit_immo' => 'Crédit immobilier', 'credits_conso' => 'Crédits consommation',
    'assurance_habitation' => 'Assurance habitation', 'assurance_auto' => 'Assurance auto', 'assurance_sante' => 'Assurance santé / mutuelle',
    'impots' => 'Impôts sur le revenu', 'taxe_fonciere' => 'Taxe foncière', 'taxe_habitation' => 'Taxe d\'habitation',
];
$charges_courantes_fields = [
    'electricite_gaz' => 'Électricité / Gaz', 'eau' => 'Eau', 'telephone_internet' => 'Téléphone / Internet', 'transport' => 'Transport',
    'alimentation' => 'Alimentation', 'habillement' => 'Habillement', 'sante' => 'Santé', 'loisirs' => 'Loisirs',
    'epargne_mensuelle' => 'Épargne mensuelle', 'divers' => 'Divers',
];
$sections = [
    ['revenus', 'fa-arrow-circle-down', 'Revenus mensuels', $revenus_fields],
    ['fixes', 'fa-file-invoice-dollar', 'Charges fixes mensuelles', $charges_fixes_fields],
    ['courantes', 'fa-shopping-cart', 'Charges courantes mensuelles', $charges_courantes_fields],
];
