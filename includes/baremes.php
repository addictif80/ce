<?php
require_once __DIR__ . '/memo_defaults.php';
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
        'signataires' => [
            'label' => 'Fiche : qui peut signer quoi ?', 'icon' => 'fa-signature', 'type' => 'memo',
            'champs' => ['Opération', 'Qui signe', 'Pièces et points d\'attention'],
            'help' => 'Pour chaque situation (majeur protégé, mineur, société, indivision…), qui peut effectuer chaque opération. Chaque groupe est une situation ; chaque ligne une opération. Les libellés d\'opération doivent être identiques d\'un groupe à l\'autre.',
            'sources' => [
                ['Service-public.fr – Protection juridique, capacité, régimes matrimoniaux', 'https://www.service-public.fr/', 'Règles générales sur la capacité des personnes et la représentation.'],
                ['Légifrance – Code civil', 'https://www.legifrance.gouv.fr/', 'Textes de référence (capacité, régimes matrimoniaux, majeurs protégés).'],
                ['Procédures internes de la banque', '', 'À compléter par la conformité : pièces exigées et conditions propres à l\'établissement.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'règles générales du droit français rédigées en résumé',
                'ok' => '', 'ko' => 'tout le contenu est à faire valider par la conformité avant de cocher « J\'ai contrôlé » : c\'est une aide à l\'orientation, pas un avis juridique'],
            'default' => memoDefaultSignataires(),
        ],
        'evenements' => [
            'label' => 'Fiche : parcours événements de vie', 'icon' => 'fa-route', 'type' => 'memo',
            'champs' => ['Démarche ou proposition', 'Catégorie (Démarche, Proposition, Pièce à fournir)', 'Précision'],
            'help' => 'Pour chaque événement de vie, la liste des démarches bancaires, des solutions à proposer et des pièces à demander. Chaque groupe est un événement. Catégories reconnues : Démarche, Proposition, Pièce à fournir.',
            'sources' => [
                ['Procédures internes de la banque', '', 'Démarches et pièces propres à l\'établissement.'],
                ['Service-public.fr – Démarches par événement de vie', 'https://www.service-public.fr/', 'Rubriques « Famille », « Retraite », « Décès » pour les démarches administratives.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'pratiques courantes en agence',
                'ok' => '', 'ko' => 'à adapter aux offres et aux procédures de votre établissement avant de cocher « J\'ai contrôlé »'],
            'default' => memoDefaultEvenements(),
        ],
        'saisie' => [
            'label' => 'Saisie des rémunérations et solde bancaire insaisissable', 'icon' => 'fa-gavel',
            'help' => 'Barème annuel des quotités saisissables (tranches de rémunération nette), majoration par personne à charge et montant du solde bancaire insaisissable.',
            'sources' => [
                ['Service-public.fr – Saisie sur salaire', 'https://www.service-public.fr/', 'Barème en vigueur au 1er janvier, par tranches annuelles.'],
                ['Légifrance – décret n° 2025-1299 du 24 décembre 2025', 'https://www.legifrance.gouv.fr/', 'Revalorisation 2026 des seuils de saisie des rémunérations.'],
                ['Service-public.fr – RSA (montant forfaitaire)', 'https://www.service-public.fr/', 'Le solde bancaire insaisissable est égal au RSA pour une personne seule.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'barème 2026 relevé dans les sources publiques',
                'ok' => 'les six seuils annuels et la majoration par personne à charge (145 € / mois)',
                'ko' => 'le montant du RSA (651,69 € ou 646,52 € selon les sources) est à vérifier, il fixe le solde bancaire insaisissable'],
            'default' => ['seuils' => [4480, 8730, 13000, 17230, 21470, 25810], 'quotites' => [5, 10, 20, 25, 33.33, 66.67, 100], 'charge_annuelle' => 1740, 'rsa_mensuel' => 651.69],
        ],
        'memo_plafonds' => [
            'label' => 'Mémo : plafonds et seuils', 'icon' => 'fa-gauge-high', 'type' => 'memo',
            'help' => 'Plafonds de dépôt, d\'espèces, de garantie des dépôts et abattements usuels, affichés dans le mémo de /tools. Chaque ligne se modifie librement.',
            'sources' => [
                ['Service-public.fr – Argent / Épargne / Fiscalité', 'https://www.service-public.fr/', 'Plafonds des livrets, abattements sur donations et successions, plafond de paiement en espèces.'],
                ['Fonds de garantie des dépôts et de résolution (FGDR)', 'https://www.garantiedesdepots.fr/', 'Garantie des dépôts (100 000 €), des titres (70 000 €) et des cautionnements.'],
                ['Légifrance – Code monétaire et financier, Code général des impôts', 'https://www.legifrance.gouv.fr/', 'Textes de référence pour chaque plafond.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'connaissance générale des plafonds en vigueur',
                'ok' => '', 'ko' => 'toutes les valeurs sont à contrôler sur les sources officielles avant de cocher « J\'ai contrôlé » (elles changent par décret ou en loi de finances)'],
            'default' => ['groupes' => [
                ['titre' => 'Épargne réglementée : plafonds de dépôt', 'lignes' => [
                    ['libelle' => 'Livret A', 'valeur' => '22 950 €', 'note' => 'Hors capitalisation des intérêts'],
                    ['libelle' => 'Livret de développement durable et solidaire (LDDS)', 'valeur' => '12 000 €', 'note' => ''],
                    ['libelle' => 'Livret d\'épargne populaire (LEP)', 'valeur' => '10 000 €', 'note' => 'Sous conditions de revenus'],
                    ['libelle' => 'Livret Jeune', 'valeur' => '1 600 €', 'note' => '12 à 25 ans'],
                    ['libelle' => 'Plan d\'épargne logement (PEL)', 'valeur' => '61 200 €', 'note' => ''],
                    ['libelle' => 'Compte épargne logement (CEL)', 'valeur' => '15 300 €', 'note' => ''],
                    ['libelle' => 'PEA', 'valeur' => '150 000 €', 'note' => 'Versements'],
                    ['libelle' => 'PEA-PME', 'valeur' => '225 000 €', 'note' => 'Cumul PEA + PEA-PME limité à 225 000 €'],
                ]],
                ['titre' => 'Espèces et paiements', 'lignes' => [
                    ['libelle' => 'Paiement en espèces entre particuliers et professionnels', 'valeur' => '1 000 €', 'note' => 'Résident fiscal français ; 15 000 € pour un non-résident'],
                    ['libelle' => 'Dépôts ou retraits en espèces cumulés sur un mois', 'valeur' => '10 000 €', 'note' => 'Au-delà, déclaration systématique à Tracfin par la banque'],
                    ['libelle' => 'Virement SEPA instantané', 'valeur' => '100 000 €', 'note' => 'Maximum du schéma ; la banque peut fixer un plafond inférieur'],
                ]],
                ['titre' => 'Garantie des dépôts (FGDR)', 'lignes' => [
                    ['libelle' => 'Dépôts (comptes, livrets)', 'valeur' => '100 000 €', 'note' => 'Par déposant et par établissement'],
                    ['libelle' => 'Titres (comptes-titres, PEA)', 'valeur' => '70 000 €', 'note' => 'Par investisseur et par établissement'],
                    ['libelle' => 'Cautionnements', 'valeur' => '90 000 €', 'note' => ''],
                    ['libelle' => 'Assurance-vie (FGAP)', 'valeur' => '70 000 €', 'note' => 'Par assuré et par entreprise d\'assurance'],
                ]],
                ['titre' => 'Donations et successions : abattements', 'lignes' => [
                    ['libelle' => 'Parent / enfant', 'valeur' => '100 000 €', 'note' => 'Renouvelable tous les 15 ans'],
                    ['libelle' => 'Grand-parent / petit-enfant', 'valeur' => '31 865 €', 'note' => ''],
                    ['libelle' => 'Arrière-grand-parent / arrière-petit-enfant', 'valeur' => '5 310 €', 'note' => ''],
                    ['libelle' => 'Frère / sœur', 'valeur' => '15 932 €', 'note' => ''],
                    ['libelle' => 'Neveu / nièce', 'valeur' => '7 967 €', 'note' => ''],
                    ['libelle' => 'Personne handicapée (cumulable)', 'valeur' => '159 325 €', 'note' => ''],
                    ['libelle' => 'Don familial de sommes d\'argent', 'valeur' => '31 865 €', 'note' => 'Donateur de moins de 80 ans, bénéficiaire majeur'],
                ]],
                ['titre' => 'Assurance-vie', 'lignes' => [
                    ['libelle' => 'Capital décès : abattement par bénéficiaire (primes avant 70 ans)', 'valeur' => '152 500 €', 'note' => 'Article 990 I du CGI'],
                    ['libelle' => 'Rachat après 8 ans : abattement annuel sur les gains', 'valeur' => '4 600 € / 9 200 €', 'note' => 'Personne seule / couple soumis à imposition commune'],
                ]],
                ['titre' => 'Crédit', 'lignes' => [
                    ['libelle' => 'Crédit à la consommation : champ d\'application', 'valeur' => '200 € à 75 000 €', 'note' => ''],
                ]],
            ]],
        ],
        'memo_delais' => [
            'label' => 'Mémo : délais légaux', 'icon' => 'fa-hourglass-half', 'type' => 'memo',
            'help' => 'Délais de réflexion, de rétractation, de contestation et de réponse les plus courants en agence, affichés dans le mémo de /tools.',
            'sources' => [
                ['Service-public.fr – Argent / Banque et crédit', 'https://www.service-public.fr/', 'Fiches pratiques sur les délais applicables aux particuliers.'],
                ['Légifrance – Code de la consommation, Code monétaire et financier', 'https://www.legifrance.gouv.fr/', 'Textes de référence pour chaque délai.'],
                ['Médiateur de l\'AFB / ACPR – Réclamations et médiation', 'https://acpr.banque-france.fr/', 'Délais de traitement des réclamations et saisine du médiateur.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'connaissance générale de la réglementation',
                'ok' => '', 'ko' => 'tous les délais sont à contrôler sur les textes avant de cocher « J\'ai contrôlé » ; à faire valider par la conformité'],
            'default' => ['groupes' => [
                ['titre' => 'Crédit immobilier', 'lignes' => [
                    ['libelle' => 'Délai de réflexion sur l\'offre', 'valeur' => '10 jours', 'note' => 'Acceptation possible à partir du 11e jour suivant la réception de l\'offre'],
                    ['libelle' => 'Validité minimale de l\'offre', 'valeur' => '30 jours', 'note' => ''],
                    ['libelle' => 'Remboursement anticipé : indemnité maximale', 'valeur' => '6 mois d\'intérêts ou 3 % du capital restant dû', 'note' => 'Le montant le moins élevé est retenu'],
                    ['libelle' => 'Assurance emprunteur : résiliation / substitution', 'valeur' => 'À tout moment', 'note' => 'La banque répond sous 10 jours ouvrés à une demande de substitution (loi Lemoine)'],
                    ['libelle' => 'Condition suspensive d\'obtention du prêt', 'valeur' => 'Environ 45 jours', 'note' => 'Délai usuel, fixé dans le compromis de vente'],
                ]],
                ['titre' => 'Crédit à la consommation', 'lignes' => [
                    ['libelle' => 'Délai de rétractation', 'valeur' => '14 jours calendaires', 'note' => 'À compter de l\'acceptation de l\'offre'],
                    ['libelle' => 'Offre préalable : durée de maintien', 'valeur' => '15 jours minimum', 'note' => ''],
                ]],
                ['titre' => 'Comptes et moyens de paiement', 'lignes' => [
                    ['libelle' => 'Contestation d\'un prélèvement SEPA autorisé', 'valeur' => '8 semaines', 'note' => 'À compter du débit'],
                    ['libelle' => 'Contestation d\'une opération non autorisée (carte, virement, prélèvement)', 'valeur' => '13 mois', 'note' => 'La banque rembourse au plus tard le premier jour ouvrable suivant, sauf soupçon de fraude'],
                    ['libelle' => 'Validité d\'un chèque', 'valeur' => '1 an et 8 jours', 'note' => 'À compter de la date d\'émission'],
                    ['libelle' => 'Virement SEPA', 'valeur' => 'J+1 ouvrable', 'note' => 'Crédit du compte du bénéficiaire au plus tard le jour ouvrable suivant'],
                    ['libelle' => 'Clôture d\'un compte de dépôt par la banque', 'valeur' => 'Préavis de 60 jours', 'note' => ''],
                    ['libelle' => 'Comptes inactifs', 'valeur' => '10 ans', 'note' => 'Transfert à la Caisse des dépôts au terme de l\'inactivité'],
                ]],
                ['titre' => 'Réclamations et médiation', 'lignes' => [
                    ['libelle' => 'Réclamation : accusé de réception', 'valeur' => '10 jours ouvrables', 'note' => ''],
                    ['libelle' => 'Réclamation sur un service de paiement : réponse', 'valeur' => '15 jours ouvrables', 'note' => 'Jusqu\'à 35 jours en cas de circonstances exceptionnelles'],
                    ['libelle' => 'Autre réclamation : réponse', 'valeur' => '2 mois maximum', 'note' => ''],
                    ['libelle' => 'Saisine du médiateur', 'valeur' => 'Après 2 mois sans réponse ou en cas de refus', 'note' => ''],
                ]],
                ['titre' => 'Assurance-vie et succession', 'lignes' => [
                    ['libelle' => 'Assurance-vie : renonciation au contrat', 'valeur' => '30 jours', 'note' => ''],
                    ['libelle' => 'Assurance-vie : versement du capital au bénéficiaire', 'valeur' => '1 mois', 'note' => 'À compter de la réception des pièces'],
                    ['libelle' => 'Déclaration de succession', 'valeur' => '6 mois', 'note' => '12 mois si le décès a lieu hors de France'],
                    ['libelle' => 'Frais d\'obsèques prélevés sur les comptes du défunt', 'valeur' => 'Jusqu\'à 5 910 €', 'note' => ''],
                ]],
                ['titre' => 'Prescription', 'lignes' => [
                    ['libelle' => 'Action d\'un professionnel contre un consommateur (crédit)', 'valeur' => '2 ans', 'note' => ''],
                    ['libelle' => 'Prescription de droit commun', 'valeur' => '5 ans', 'note' => ''],
                ]],
            ]],
        ],
        'fiscalite' => [
            'label' => 'Fiscalité de l\'épargne et impôt sur le revenu (estimations)', 'icon' => 'fa-landmark',
            'help' => 'Taux et seuils utilisés par les simulateurs d\'épargne, d\'assurance-vie, de PER et le comparateur épargne / crédit. Ce sont des paramètres d\'estimation : à contrôler chaque année (loi de finances et loi de financement de la Sécurité sociale).',
            'sources' => [
                ['Service-public.fr – Impôt sur le revenu : barème et quotient familial', 'https://www.service-public.fr/', 'Tranches du barème applicable aux revenus de l\'année précédente.'],
                ['Impots.gouv.fr – Prélèvement forfaitaire unique et prélèvements sociaux', 'https://www.impots.gouv.fr/', 'Taux du PFU (12,8 % d\'impôt) et des prélèvements sociaux selon le placement.'],
                ['Urssaf / Service-public.fr – Plafond annuel de la Sécurité sociale (PASS)', 'https://www.urssaf.fr/', 'Le plafond de déduction du PER se calcule sur le PASS de l\'année précédente.'],
            ],
            'verifie' => ['date' => '09/10/2026', 'source' => 'articles de presse spécialisée et connaissance générale (non vérifié sur les textes officiels)',
                'ok' => '',
                'ko' => 'prélèvements sociaux de 18,6 % sur les produits financiers depuis la LFSS 2026 (assurance-vie et épargne logement restent à 17,2 %), barème de l\'impôt sur le revenu et PASS : tout est à contrôler avant de cocher « J\'ai contrôlé »'],
            'default' => ['pfu_ir' => 12.8, 'ps_standard' => 18.6, 'ps_assurance_vie' => 17.2,
                'av_taux_8ans' => 7.5, 'av_abattement_seul' => 4600, 'av_abattement_couple' => 9200, 'av_seuil_primes' => 150000,
                'av_990i_abattement' => 152500, 'av_990i_taux1' => 20, 'av_990i_seuil' => 700000, 'av_990i_taux2' => 31.25,
                'ir_seuils' => [11600, 29579, 84577, 181917], 'ir_taux' => [0, 11, 30, 41, 45],
                'pass_n1' => 47100, 'per_pct' => 10, 'per_plafond_pass' => 8],
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
    if ($cle === 'saisie') {
        $sx = $d['seuils'] ?? null; $qx = $d['quotites'] ?? null;
        if (!is_array($sx) || count($sx) !== 6 || !is_array($qx) || count($qx) !== 7) return 'Six seuils et sept quotités attendus.';
        $prev = 0; foreach ($sx as $v) { if (!is_numeric($v) || $v <= $prev) return 'Les seuils doivent être croissants.'; $prev = $v; }
        foreach ($qx as $v) if (!is_numeric($v) || $v < 0 || $v > 100) return 'Chaque quotité doit être comprise entre 0 et 100 %.';
        foreach (['charge_annuelle', 'rsa_mensuel'] as $k) if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] <= 0) return "Valeur positive manquante : $k";
        return null;
    }
    if (baremeIsMemo($cle)) {
        if (empty($d['groupes']) || !is_array($d['groupes'])) return 'Au moins un groupe de lignes est attendu.';
        foreach ($d['groupes'] as $g) { if (!is_array($g) || trim((string)($g['titre'] ?? '')) === '' || empty($g['lignes'])) return 'Chaque groupe doit avoir un titre et au moins une ligne.'; }
        return null;
    }
    if ($cle === 'fiscalite') {
        foreach (['pfu_ir', 'ps_standard', 'ps_assurance_vie', 'av_taux_8ans', 'av_abattement_seul', 'av_abattement_couple', 'av_seuil_primes', 'av_990i_abattement', 'av_990i_taux1', 'av_990i_seuil', 'av_990i_taux2', 'pass_n1', 'per_pct', 'per_plafond_pass'] as $k)
            if (!isset($d[$k]) || !is_numeric($d[$k]) || $d[$k] < 0) return "Valeur numérique manquante : $k";
        $sx = $d['ir_seuils'] ?? null; $tx = $d['ir_taux'] ?? null;
        if (!is_array($sx) || count($sx) !== 4 || !is_array($tx) || count($tx) !== 5) return 'Quatre seuils et cinq taux attendus pour le barème de l\'impôt sur le revenu.';
        $prev = 0; foreach ($sx as $v) { if (!is_numeric($v) || $v <= $prev) return 'Les seuils du barème doivent être croissants.'; $prev = $v; }
        foreach ($tx as $v) if (!is_numeric($v) || $v < 0 || $v > 100) return 'Chaque taux du barème doit être compris entre 0 et 100 %.';
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


/**
 * Bandeau discret « barème utilisé » affiché sur les simulateurs : état du contrôle et date.
 * Le texte est repris dans le pied du document imprimé (attribut data-bareme-note).
 * @param string[] $cles clés de barèmes (catalogue)
 */
function baremeNotice(array $cles) {
    $cat = baremeCatalog(); $parts = []; $worst = 'ok'; $order = ['ok' => 0, 'ancien' => 1, 'provisoire' => 2];
    foreach ($cles as $k) {
        if (!isset($cat[$k])) continue;
        $b = baremeGet($k); $st = baremeStatut($b);
        $name = preg_replace('/\s*\(.*$/u', '', $cat[$k]['label']);
        if ($st === 'ok') $parts[] = $name . ' : contrôlé le ' . date('d/m/Y', strtotime($b['date']));
        elseif ($st === 'ancien') $parts[] = $name . ' : dernier contrôle le ' . ($b['date'] ? date('d/m/Y', strtotime($b['date'])) : '—') . ' (à revoir)';
        else $parts[] = $name . ' : à confirmer';
        if ($order[$st] > $order[$worst]) $worst = $st;
    }
    if (!$parts) return '';
    $cls = ['ok' => 'text-success', 'ancien' => 'text-danger', 'provisoire' => 'text-warning'][$worst];
    $icon = $worst === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation';
    $txt = 'Barème utilisé — ' . implode(' · ', $parts);
    return '<div class="small ' . $cls . ' mb-2 no-client" data-bareme-note="' . htmlspecialchars($txt, ENT_QUOTES) . '"><i class="fas ' . $icon . ' me-1"></i>' . htmlspecialchars($txt) . '</div>';
}


/** Fiches de mémo (groupes de lignes libellé / valeur / note) : mémos, fiche des signataires, parcours événements de vie. */
function baremeIsMemo($cle) { return (baremeCatalog()[$cle]['type'] ?? '') === 'memo'; }
