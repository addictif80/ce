<?php
/**
 * Barèmes réglementaires utilisés par les simulateurs (PTZ, frais de notaire, plafonds HCSF).
 * Gérés par l'administrateur (onglet « Barèmes ») ; chaque barème porte une date de référence et un indicateur « contrôlé ».
 * Les valeurs par défaut sont indicatives : tant qu'un barème n'est pas contrôlé, les simulateurs affichent un avertissement.
 */
function baremeCatalog() {
    require_once __DIR__ . '/../modules/ptz/bareme.php';
    return [
        'ptz' => [
            'label' => 'Prêt à taux zéro (PTZ)', 'icon' => 'fa-percent',
            'help' => 'Conditions de ressources, plafonds de coût, part financée et durées du prêt à taux zéro.',
            'sources' => [
                ['Service-public.fr – Prêt à taux zéro (PTZ)', 'https://www.service-public.fr/particuliers/vosdroits/F10871', 'Tableaux « Montant auquel votre revenu doit être inférieur… », « Déterminer la tranche de revenus », « Coût maximum de l\'opération » et « Part maximum du PTZ » (choisir « Offre de prêt émise à partir d\'avril 2025 »).'],
                ['ANIL – Simulateur « Votre prêt à taux zéro »', 'https://www.anil.org/outils/outils-de-calcul/votre-pret-a-taux-zero/', 'Saisissez un cas concret (commune, personnes, revenu fiscal, coût de l\'opération) et comparez montant maximum, durée, différé et mensualités avec notre simulateur.'],
            ],
            'verifie' => ['date' => '08/10/2026', 'source' => 'Service-public.fr et le simulateur de l\'ANIL',
                'ok' => 'coefficients familiaux, revenus maximaux, limites de tranches, coûts maximaux, parts financées, durées et différés par tranche, revenu retenu (coût ÷ 9) — offres émises à partir d\'avril 2025',
                'ko' => ''],
            'default' => ptzDefaultBareme(),
        ],
        'notaire' => [
            'label' => 'Frais de notaire', 'icon' => 'fa-scale-balanced',
            'help' => 'Émoluments du notaire (tranches), droits de mutation et taxes, taux par département.',
            'sources' => [
                ['impots.gouv.fr – Taux des droits de mutation par département (tableau PDF)', 'https://www.impots.gouv.fr/sites/default/files/media/1_metier/3_partenaire/notaires/dmto/dmto_2026-02.pdf', 'Tableau « Droits d\'enregistrement et taxe de publicité foncière : taux… » (version du 1er février 2026) : colonne « Taux voté » (5 % ou 4,50 %…) et colonne « article 1594 D » (taux de droit commun) pour chaque département. Une version plus récente peut exister : page « Achat dans l\'ancien » ci-dessous.'],
                ['impots.gouv.fr – Achat dans l\'ancien', 'https://www.impots.gouv.fr/particulier/achat-dans-lancien', 'Explique les droits dus à l\'achat d\'un logement ancien (taux départemental, taxe communale 1,20 %) ; sert aussi à retrouver le dernier tableau des taux par département.'],
                ['economie.gouv.fr – Quels frais de notaire devez-vous payer ?', 'https://www.economie.gouv.fr/particuliers/gerer-mon-argent/investir-dans-limmobilier/achat-dun-bien-immobilier-quels-frais-de-notaire-devez-vous-payer', 'Composition des frais : droits et taxes, émoluments du notaire (barème par tranches), débours.'],
                ['Notaires de Paris – Augmentation des droits de mutation, département par département', 'https://paris.notaires.fr/fr/actualites/point-sur-laugmentation-des-droits-de-mutation-departement-par-departement', 'Point d\'étape sur les départements ayant voté la hausse à 5 %.'],
            ],
            'verifie' => ['date' => '08/10/2026', 'source' => 'impots.gouv.fr et sites des notaires',
                'ok' => 'taux départementaux (98 lignes, tableau impots.gouv.fr au 1er février 2026), taxe communale 1,20 %, frais d\'assiette 2,37 %, contribution de sécurité immobilière 0,10 %, TVA 20 %, barème des émoluments (3,870 / 1,596 / 1,064 / 0,799 %, en vigueur depuis le 1er janvier 2021, recoupé avec un calcul de simulateur)',
                'ko' => 'taux « neuf » (0,715 % ici ; les sources indiquent 0,71 %), le Cantal (tableau : 5 % « jusqu\'au 31/05/2026 », date dépassée : mis à 4,50 %), la Guyane, et toute évolution des taux départementaux depuis février 2026 — à confirmer sur la page « Achat dans l\'ancien »'],
            'default' => [
                'emoluments' => [[6500, 3.870], [17000, 1.596], [60000, 1.064], [null, 0.799]], // [borne haute, taux %] (null = au-delà)
                'droits_neuf' => 0.715, 'taxe_communale' => 1.2, 'frais_assiette' => 2.37, 'tva' => 20, 'csi' => 0.1,
                'taux_departemental_defaut' => 4.5,
                // Droits de mutation par département : taux voté (hors primo-accédant) et taux pour un primo-accédant — tableau impots.gouv.fr au 1er février 2026
                'departements' => [
                '01' => ['nom' => 'Ain', 'taux' => 5.0, 'taux_primo' => 4.5],
                '02' => ['nom' => 'Aisne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '03' => ['nom' => 'Allier', 'taux' => 5.0, 'taux_primo' => 4.5],
                '04' => ['nom' => 'Alpes-de-Haute-Provence', 'taux' => 5.0, 'taux_primo' => 4.5],
                '05' => ['nom' => 'Hautes-Alpes', 'taux' => 4.5, 'taux_primo' => 4.5],
                '06' => ['nom' => 'Alpes-Maritimes', 'taux' => 4.5, 'taux_primo' => 4.5],
                '07' => ['nom' => 'Ardèche', 'taux' => 4.5, 'taux_primo' => 4.5],
                '08' => ['nom' => 'Ardennes', 'taux' => 5.0, 'taux_primo' => 4.5],
                '09' => ['nom' => 'Ariège', 'taux' => 5.0, 'taux_primo' => 4.5],
                '10' => ['nom' => 'Aube', 'taux' => 5.0, 'taux_primo' => 4.5],
                '11' => ['nom' => 'Aude', 'taux' => 5.0, 'taux_primo' => 4.5],
                '12' => ['nom' => 'Aveyron', 'taux' => 5.0, 'taux_primo' => 4.5],
                '13' => ['nom' => 'Bouches-du-Rhône', 'taux' => 5.0, 'taux_primo' => 4.5],
                '14' => ['nom' => 'Calvados', 'taux' => 5.0, 'taux_primo' => 4.5],
                '15' => ['nom' => 'Cantal', 'taux' => 4.5, 'taux_primo' => 4.5],
                '16' => ['nom' => 'Charente', 'taux' => 4.5, 'taux_primo' => 4.5],
                '17' => ['nom' => 'Charente-Maritime', 'taux' => 5.0, 'taux_primo' => 4.5],
                '18' => ['nom' => 'Cher', 'taux' => 5.0, 'taux_primo' => 4.5],
                '19' => ['nom' => 'Corrèze', 'taux' => 5.0, 'taux_primo' => 4.5],
                '20' => ['nom' => 'Corse', 'taux' => 5.0, 'taux_primo' => 4.5],
                '21' => ['nom' => 'Côte-d\'Or', 'taux' => 5.0, 'taux_primo' => 4.5],
                '22' => ['nom' => 'Côtes-d\'Armor', 'taux' => 5.0, 'taux_primo' => 4.5],
                '23' => ['nom' => 'Creuse', 'taux' => 5.0, 'taux_primo' => 4.5],
                '24' => ['nom' => 'Dordogne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '25' => ['nom' => 'Doubs', 'taux' => 5.0, 'taux_primo' => 4.5],
                '26' => ['nom' => 'Drôme', 'taux' => 4.5, 'taux_primo' => 4.5],
                '27' => ['nom' => 'Eure', 'taux' => 4.5, 'taux_primo' => 4.5],
                '28' => ['nom' => 'Eure-et-Loir', 'taux' => 5.0, 'taux_primo' => 4.5],
                '29' => ['nom' => 'Finistère', 'taux' => 5.0, 'taux_primo' => 4.5],
                '30' => ['nom' => 'Gard', 'taux' => 5.0, 'taux_primo' => 4.5],
                '31' => ['nom' => 'Haute-Garonne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '32' => ['nom' => 'Gers', 'taux' => 5.0, 'taux_primo' => 4.5],
                '33' => ['nom' => 'Gironde', 'taux' => 5.0, 'taux_primo' => 4.5],
                '34' => ['nom' => 'Hérault', 'taux' => 5.0, 'taux_primo' => 4.5],
                '35' => ['nom' => 'Ille-et-Vilaine', 'taux' => 5.0, 'taux_primo' => 4.5],
                '36' => ['nom' => 'Indre', 'taux' => 3.8, 'taux_primo' => 3.8],
                '37' => ['nom' => 'Indre-et-Loire', 'taux' => 5.0, 'taux_primo' => 4.5],
                '38' => ['nom' => 'Isère', 'taux' => 5.0, 'taux_primo' => 4.5],
                '39' => ['nom' => 'Jura', 'taux' => 5.0, 'taux_primo' => 4.5],
                '40' => ['nom' => 'Landes', 'taux' => 5.0, 'taux_primo' => 4.5],
                '41' => ['nom' => 'Loir-et-Cher', 'taux' => 5.0, 'taux_primo' => 4.5],
                '42' => ['nom' => 'Loire', 'taux' => 5.0, 'taux_primo' => 4.5],
                '43' => ['nom' => 'Haute-Loire', 'taux' => 5.0, 'taux_primo' => 4.5],
                '44' => ['nom' => 'Loire-Atlantique', 'taux' => 5.0, 'taux_primo' => 4.5],
                '45' => ['nom' => 'Loiret', 'taux' => 5.0, 'taux_primo' => 4.5],
                '46' => ['nom' => 'Lot', 'taux' => 5.0, 'taux_primo' => 4.5],
                '47' => ['nom' => 'Lot-et-Garonne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '48' => ['nom' => 'Lozère', 'taux' => 4.5, 'taux_primo' => 4.5],
                '49' => ['nom' => 'Maine-et-Loire', 'taux' => 5.0, 'taux_primo' => 4.5],
                '50' => ['nom' => 'Manche', 'taux' => 5.0, 'taux_primo' => 4.5],
                '51' => ['nom' => 'Marne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '52' => ['nom' => 'Haute-Marne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '53' => ['nom' => 'Mayenne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '54' => ['nom' => 'Meurthe-et-Moselle', 'taux' => 5.0, 'taux_primo' => 4.5],
                '55' => ['nom' => 'Meuse', 'taux' => 5.0, 'taux_primo' => 4.5],
                '56' => ['nom' => 'Morbihan', 'taux' => 5.0, 'taux_primo' => 4.5],
                '57' => ['nom' => 'Moselle', 'taux' => 5.0, 'taux_primo' => 4.5],
                '58' => ['nom' => 'Nièvre', 'taux' => 5.0, 'taux_primo' => 4.5],
                '59' => ['nom' => 'Nord', 'taux' => 5.0, 'taux_primo' => 4.5],
                '60' => ['nom' => 'Oise', 'taux' => 4.5, 'taux_primo' => 4.5],
                '61' => ['nom' => 'Orne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '62' => ['nom' => 'Pas-de-Calais', 'taux' => 5.0, 'taux_primo' => 4.5],
                '63' => ['nom' => 'Puy-de-Dôme', 'taux' => 5.0, 'taux_primo' => 4.5],
                '64' => ['nom' => 'Pyrénées-Atlantiques', 'taux' => 5.0, 'taux_primo' => 4.5],
                '65' => ['nom' => 'Hautes-Pyrénées', 'taux' => 4.5, 'taux_primo' => 3.8],
                '66' => ['nom' => 'Pyrénées-Orientales', 'taux' => 5.0, 'taux_primo' => 4.5],
                '69' => ['nom' => 'Rhône et Métropole de Lyon', 'taux' => 5.0, 'taux_primo' => 4.5],
                '70' => ['nom' => 'Haute-Saône', 'taux' => 5.0, 'taux_primo' => 4.5],
                '71' => ['nom' => 'Saône-et-Loire', 'taux' => 4.5, 'taux_primo' => 4.5],
                '72' => ['nom' => 'Sarthe', 'taux' => 5.0, 'taux_primo' => 4.5],
                '73' => ['nom' => 'Savoie', 'taux' => 5.0, 'taux_primo' => 4.5],
                '74' => ['nom' => 'Haute-Savoie', 'taux' => 5.0, 'taux_primo' => 4.5],
                '75' => ['nom' => 'Paris', 'taux' => 5.0, 'taux_primo' => 4.5],
                '76' => ['nom' => 'Seine-Maritime', 'taux' => 5.0, 'taux_primo' => 4.5],
                '77' => ['nom' => 'Seine-et-Marne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '78' => ['nom' => 'Yvelines', 'taux' => 5.0, 'taux_primo' => 4.5],
                '79' => ['nom' => 'Deux-Sèvres', 'taux' => 5.0, 'taux_primo' => 4.5],
                '80' => ['nom' => 'Somme', 'taux' => 5.0, 'taux_primo' => 4.5],
                '81' => ['nom' => 'Tarn', 'taux' => 5.0, 'taux_primo' => 4.5],
                '82' => ['nom' => 'Tarn-et-Garonne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '83' => ['nom' => 'Var', 'taux' => 5.0, 'taux_primo' => 4.5],
                '84' => ['nom' => 'Vaucluse', 'taux' => 5.0, 'taux_primo' => 4.5],
                '85' => ['nom' => 'Vendée', 'taux' => 5.0, 'taux_primo' => 4.5],
                '86' => ['nom' => 'Vienne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '87' => ['nom' => 'Haute-Vienne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '88' => ['nom' => 'Vosges', 'taux' => 5.0, 'taux_primo' => 4.5],
                '89' => ['nom' => 'Yonne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '90' => ['nom' => 'Territoire-de-Belfort', 'taux' => 5.0, 'taux_primo' => 4.5],
                '91' => ['nom' => 'Essonne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '92' => ['nom' => 'Hauts-de-Seine', 'taux' => 5.0, 'taux_primo' => 4.5],
                '93' => ['nom' => 'Seine-Saint-Denis', 'taux' => 5.0, 'taux_primo' => 4.5],
                '94' => ['nom' => 'Val-de-Marne', 'taux' => 5.0, 'taux_primo' => 4.5],
                '95' => ['nom' => 'Val-d\'Oise', 'taux' => 5.0, 'taux_primo' => 4.5],
                '971' => ['nom' => 'Guadeloupe', 'taux' => 4.5, 'taux_primo' => 4.5],
                '972' => ['nom' => 'Martinique', 'taux' => 5.0, 'taux_primo' => 4.5],
                '973' => ['nom' => 'Guyane', 'taux' => 5.0, 'taux_primo' => 4.5],
                '974' => ['nom' => 'La Réunion', 'taux' => 5.0, 'taux_primo' => 4.5],
                '976' => ['nom' => 'Mayotte', 'taux' => 3.8, 'taux_primo' => 3.8],
                ],
            ],
        ],
        'doublissimo' => [
            'label' => 'Doublissimo (prêt complémentaire des primo-accédants)', 'icon' => 'fa-clone',
            'help' => 'Règles de la fiche produit : 20 % du financement total, plafond, durées et offre exceptionnelle (plafond doublé et taux préférentiel pendant la campagne).',
            'sources' => [
                ['Fiche produit Doublissimo (intranet / Easydoc) – mise à jour avril 2026', '', 'Fiche interne : montant (20 % du financement total CEMP, plafond 22 500 € hors campagne ; offre exceptionnelle du 1er avril au 30 juin : plafond 45 000 € pour le canal agence, 22 500 € pour la prescription immobilière, taux fixe 1,99 %), durée de 3 mois jusqu\'à la durée du prêt principal (300 mois maximum).'],
            ],
            'verifie' => ['date' => '08/10/2026', 'source' => 'la fiche produit interne (avril 2026)',
                'ok' => 'pourcentage, plafonds, durées, dates et taux de la campagne',
                'ko' => 'la fiche indique une campagne du 1er avril au 30 juin 2026, mais le logiciel interne applique encore le plafond de 45 000 € : la date de fin est donc laissée vide (campagne ouverte) ; renseignez-la quand la campagne s\'arrête'],
            'default' => ['pourcentage' => 20, 'plafond' => 22500, 'duree_min_mois' => 3, 'duree_max_mois' => 300,
                'campagne' => ['debut' => '2026-04-01', 'fin' => '', 'plafond_agence' => 45000, 'plafond_prescription' => 22500, 'taux' => 1.99]],
        ],
        'primo_jeune' => [
            'label' => 'Primo Jeune 0 % (prêt complémentaire au PTZ)', 'icon' => 'fa-child',
            'help' => 'Règles de la fiche produit : prêt sans intérêt ni frais, plafonné en montant et à un pourcentage du financement total, réservé aux emprunteurs de 35 ans ou moins éligibles au PTZ.',
            'sources' => [
                ['Fiche produit Primo Jeune 0 % et présentation « Primo Jeunes et Grandioz » (intranet / Easydoc)', '', 'Fiches internes : montant maximum 20 000 € et 10 % du montant global des financements (PTZ compris), durée maximale 20 ans par multiples de 12 mois, taux 0 %, sans frais de dossier ni IRA, un emprunteur de 35 ans maximum, primo-accédant éligible au PTZ (PTZ obligatoire), résidence principale.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'la fiche produit interne',
                'ok' => 'montant, pourcentage, durée et âge maximum',
                'ko' => 'la fiche produit porte une date de version incohérente (2031) : vérifier auprès de la hiérarchie que le dispositif est toujours commercialisé'],
            'default' => ['pourcentage' => 10, 'plafond' => 20000, 'duree_max_mois' => 240, 'age_max' => 35],
        ],
        'primoz' => [
            'label' => 'Primoz (prêt amorti avec différé de longue durée)', 'icon' => 'fa-hourglass-half',
            'help' => 'Règles de la fiche produit : 10 à 20 % du financement, 10 000 à 120 000 €, durée de 20 à 25 ans dont 10 à 15 ans de différé d\'amortissement (intérêts seuls), primo-accédants de moins de 36 ans en CDI.',
            'sources' => [
                ['Fiche produit Primoz (intranet / Easydoc) – version du 05/11/2024', '', 'Fiche interne : montant entre 10 % et 20 % du montant financé (10 000 € minimum, 120 000 € maximum), durée de 20 à 25 ans par multiples de 12 mois, différé d\'amortissement en capital de 120 à 180 mois (échéances d\'intérêts seuls), taux selon le barème de la CE, couplé obligatoirement à un prêt principal amortissable, incompatible avec PC-PAS, Primolis et Grandioz.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'la fiche produit interne',
                'ok' => 'pourcentages, montants, durées, différé et âge maximum', 'ko' => ''],
            'default' => ['pct_min' => 10, 'pct_max' => 20, 'montant_min' => 10000, 'montant_max' => 120000, 'duree_min_mois' => 240, 'duree_max_mois' => 300, 'differe_min_mois' => 120, 'differe_max_mois' => 180, 'age_max' => 35],
        ],
        'grandioz' => [
            'label' => 'Grandioz (prêt à échéances progressives)', 'icon' => 'fa-arrow-trend-up',
            'help' => 'Règles de la présentation « Primo Jeunes et Grandioz » : échéances progressives de 1 % par an, financement minimum, durée de 7 à 25 ans, primo-accédants de 35 ans maximum ; ne se combine pas avec le PTZ.',
            'sources' => [
                ['Présentation « Primo Jeunes et Grandioz » (intranet / Easydoc)', '', 'Fiche interne : prêt à taux fixe à échéances progressives (1 % l\'an), financement minimum 50 000 €, durée de 7 à 25 ans, primo-accédant de 35 ans maximum en CDI / titulaire, exclusion des bénéficiaires du PTZ, taux d\'effort de 35 % calculé sur la première échéance.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'la présentation interne',
                'ok' => 'progression, montant minimum, durées et âge', 'ko' => ''],
            'default' => ['progression' => 1, 'montant_min' => 50000, 'duree_min_mois' => 84, 'duree_max_mois' => 300, 'age_max' => 35],
        ],
        'hcsf' => [
            'label' => 'Plafonds HCSF (endettement et durée)', 'icon' => 'fa-gauge-high',
            'help' => 'Taux d\'endettement maximal et durées maximales recommandées pour les crédits immobiliers.',
            'sources' => [
                ['economie.gouv.fr – Décision HCSF sur les crédits immobiliers', 'https://www.economie.gouv.fr/node/3230893', 'Décision D-HCSF-2021-7 du 29 septembre 2021 (applicable depuis le 1er janvier 2022) : taux d\'effort maximal de 35 % (assurance comprise) et durée maximale de 25 ans, portée à 27 ans en cas de différé d\'amortissement (achat sur plan, construction, travaux).'],
                ['Assemblée nationale – réponse ministérielle sur les critères HCSF', 'https://questions.assemblee-nationale.fr/dyn/16/questions/QANR5L16QE13282.pdf', 'Rappel des deux critères (35 % et 25 ans) et de la marge de flexibilité de 20 % laissée aux banques.'],
            ],
            'verifie' => ['date' => '08/10/2026', 'source' => 'economie.gouv.fr et Assemblée nationale',
                'ok' => 'taux d\'effort maximal de 35 %, durée maximale de 25 ans, 27 ans en cas de différé d\'amortissement',
                'ko' => ''],
            'default' => ['taux_endettement_max' => 35, 'duree_max_annees' => 25, 'duree_max_annees_neuf' => 27],
        ],
    ];
}

function baremeEnsureSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS baremes (
        cle VARCHAR(30) PRIMARY KEY,
        data MEDIUMTEXT NOT NULL,
        valide TINYINT(1) NOT NULL DEFAULT 0,
        date_reference DATE DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Contrôle de structure d'un barème saisi ; renvoie un message d'erreur ou null. */
function baremeCheck($cle, $d) {
    if (!is_array($d)) return 'JSON invalide.';
    if ($cle === 'ptz') return ptzCheckBareme($d);
    if ($cle === 'notaire') {
        if (!isset($d['emoluments']) || !is_array($d['emoluments']) || !$d['emoluments']) return 'Émoluments : au moins une tranche attendue.';
        foreach ($d['emoluments'] as $t) if (!is_array($t) || count($t) !== 2 || !is_numeric($t[1])) return 'Émoluments : chaque tranche est [borne haute ou null, taux].';
        foreach (['droits_neuf', 'taxe_communale', 'frais_assiette', 'tva', 'csi', 'taux_departemental_defaut'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k])) return "Valeur numérique manquante : $k";
        foreach ((array)($d['departements'] ?? []) as $dep) if (!is_array($dep) || !isset($dep['taux']) || !is_numeric($dep['taux']) || (isset($dep['taux_primo']) && !is_numeric($dep['taux_primo']))) return 'Départements : chaque ligne doit avoir un code et des taux numériques.';
        return null;
    }
    if ($cle === 'doublissimo') {
        foreach (['pourcentage', 'plafond', 'duree_min_mois', 'duree_max_mois'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur positive manquante : $k";
        $c = $d['campagne'] ?? null;
        if (!is_array($c)) return 'Campagne : données manquantes.';
        foreach (['plafond_agence', 'plafond_prescription', 'taux'] as $k) if (!isset($c[$k]) || !is_numeric($c[$k]) || $c[$k] < 0) return "Campagne : valeur manquante ($k).";
        foreach (['debut', 'fin'] as $k) if (($c[$k] ?? '') !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$c[$k])) return "Campagne : date invalide ($k).";
        if ($c['debut'] !== '' && $c['fin'] !== '' && $c['fin'] < $c['debut']) return 'Campagne : la date de fin précède la date de début.';
        if ($d['duree_min_mois'] > $d['duree_max_mois']) return 'Durée minimale supérieure à la durée maximale.';
        return null;
    }
    if ($cle === 'primo_jeune') {
        foreach (['pourcentage', 'plafond', 'duree_max_mois', 'age_max'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur positive manquante : $k";
        return null;
    }
    if ($cle === 'primoz') {
        foreach (['pct_min', 'pct_max', 'montant_min', 'montant_max', 'duree_min_mois', 'duree_max_mois', 'differe_min_mois', 'differe_max_mois', 'age_max'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur positive manquante : $k";
        if ($d['pct_min'] > $d['pct_max'] || $d['montant_min'] > $d['montant_max'] || $d['duree_min_mois'] > $d['duree_max_mois'] || $d['differe_min_mois'] > $d['differe_max_mois']) return 'Un minimum dépasse son maximum.';
        return null;
    }
    if ($cle === 'grandioz') {
        foreach (['progression', 'montant_min', 'duree_min_mois', 'duree_max_mois', 'age_max'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur positive manquante : $k";
        if ($d['duree_min_mois'] > $d['duree_max_mois']) return 'Durée minimale supérieure à la durée maximale.';
        return null;
    }
    if ($cle === 'hcsf') {
        foreach (['taux_endettement_max', 'duree_max_annees', 'duree_max_annees_neuf'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur numérique positive manquante : $k";
        return null;
    }
    return 'Barème inconnu.';
}

/** Barème : ['data' => tableau, 'valide' => bool, 'date' => 'Y-m-d'|null, 'defaut' => bool (jamais enregistré)] */
function baremeGet($cle) {
    $cat = baremeCatalog();
    $out = ['data' => json_decode(json_encode($cat[$cle]['default'] ?? []), true), 'valide' => false, 'date' => null, 'defaut' => true];
    try {
        baremeEnsureSchema();
        $st = getDB()->prepare("SELECT data, valide, date_reference FROM baremes WHERE cle = ?");
        $st->execute([$cle]);
        $row = $st->fetch();
        if (!$row && $cle === 'ptz') { // reprise de l'ancien emplacement du barème PTZ
            try {
                $old = getDB()->query("SELECT data FROM ptz_bareme WHERE id = 1")->fetchColumn();
                $b = $old ? json_decode($old, true) : null;
                if (is_array($b) && !baremeCheck('ptz', $b)) return ['data' => $b, 'valide' => !empty($b['valide']), 'date' => null, 'defaut' => false];
            } catch (Exception $e) {}
        }
        if ($row) {
            $d = json_decode($row['data'], true);
            if (is_array($d) && !baremeCheck($cle, $d)) $out = ['data' => $d, 'valide' => (bool)$row['valide'], 'date' => $row['date_reference'], 'defaut' => false];
        }
    } catch (Exception $e) {}
    return $out;
}

function baremeSave($cle, array $data, $valide, $date, $userId) {
    baremeEnsureSchema();
    getDB()->prepare("REPLACE INTO baremes (cle, data, valide, date_reference, updated_by) VALUES (?, ?, ?, ?, ?)")
        ->execute([$cle, json_encode($data, JSON_UNESCAPED_UNICODE), $valide ? 1 : 0, $date ?: null, $userId]);
}

/** 'ok' | 'provisoire' (non contrôlé) | 'ancien' (date de référence de plus de 12 mois ou absente) */
function baremeStatut(array $b) {
    if (!$b['valide']) return 'provisoire';
    if (!$b['date'] || strtotime($b['date']) < strtotime('-12 months')) return 'ancien';
    return 'ok';
}
function baremesAReviser() {
    $n = 0;
    foreach (array_keys(baremeCatalog()) as $k) if (baremeStatut(baremeGet($k)) !== 'ok') $n++;
    return $n;
}
