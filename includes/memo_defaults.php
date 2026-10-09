<?php
/**
 * Contenu par défaut des fiches « Qui peut signer quoi ? » et « Parcours événements de vie ».
 * Rédigé à partir de règles générales du droit français : toutes les lignes sont à faire contrôler par la conformité (statut « à contrôler »)
 * et se modifient librement dans l'administration (onglet Barèmes).
 * Format commun des fiches de mémo : groupes [ titre, lignes [ libelle, valeur, note ] ].
 */
function memoGroup($titre, array $lignes) {
    return ['titre' => $titre, 'lignes' => array_map(fn($l) => ['libelle' => $l[0], 'valeur' => $l[1], 'note' => $l[2] ?? ''], $lignes)];
}

/** Opérations (libellé de ligne) communes à toutes les situations de « Qui peut signer quoi ? ». */
function signataireOperations() {
    return ['Ouvrir un compte ou un produit', 'Opérations courantes (virement, retrait)', 'Emprunter', 'Donner une garantie (hypothèque, caution)', 'Donner une procuration', 'Clôturer un compte'];
}

function memoDefaultSignataires() {
    $op = signataireOperations();
    $g = fn($titre, array $v) => memoGroup($titre, array_map(null, $op, array_column($v, 0), array_column($v, 1)));
    return ['groupes' => [
        $g('Majeur capable, personne seule', [
            ['Le client lui-même', 'Pièce d\'identité en cours de validité, justificatif de domicile ; justificatif de revenus selon le produit'],
            ['Le client', 'Authentification habituelle ; un mandataire peut agir dans la limite de sa procuration'],
            ['Le client', 'Vérifier revenus, charges et situation ; offre acceptée par le client lui-même'],
            ['Le client pour ses propres biens ; une caution s\'engage seule', 'Caution : mention manuscrite obligatoire. Hypothèque : acte authentique chez le notaire'],
            ['Le client', 'Pièces d\'identité du client et du mandataire ; étendue et durée de la procuration à préciser'],
            ['Le client', 'Moyens de paiement restitués, opérations en cours dénouées'],
        ]),
        $g('Couple marié', [
            ['Chaque époux pour son compte personnel ; les deux pour un compte joint', 'Pièces de chaque titulaire ; livret de famille ou contrat de mariage si le régime matrimonial est utile'],
            ['Compte personnel : son titulaire. Compte joint : chaque cotitulaire, sauf convention contraire', 'Vérifier la convention de compte joint (solidarité ou signature conjointe)'],
            ['L\'époux emprunteur ; les deux époux pour un crédit commun', 'Les dettes ménagères (vie courante, éducation des enfants) engagent solidairement les époux ; pas les emprunts importants contractés par un seul sans l\'accord de l\'autre'],
            ['Caution : consentement exprès de l\'autre époux pour engager les biens communs. Hypothèque ou vente du logement de la famille : accord des deux époux', 'Régime matrimonial à vérifier (communauté, séparation de biens, participation aux acquêts)'],
            ['Le titulaire du compte (les deux pour un compte joint)', ''],
            ['Compte personnel : son titulaire. Compte joint : chaque cotitulaire peut le clôturer', 'Informer l\'autre cotitulaire ; vérifier la convention'],
        ]),
        $g('Partenaires de PACS et concubins', [
            ['Chaque partenaire pour son compte ; les deux pour un compte joint', 'Convention de PACS ou justificatif de vie commune si utile'],
            ['Compte personnel : son titulaire. Compte joint : selon la convention', ''],
            ['Chacun s\'engage pour lui-même ; les deux signent un crédit commun', 'PACS : solidarité pour les dépenses de la vie courante. Concubins : aucune solidarité légale'],
            ['Chacun pour ses propres biens', 'Pas de consentement légal du partenaire pour une caution comme entre époux (à confirmer selon le dossier)'],
            ['Le titulaire du compte', ''],
            ['Compte personnel : son titulaire. Compte joint : selon la convention', ''],
        ]),
        $g('Mineur de moins de 16 ans', [
            ['Les représentants légaux (les deux parents, sauf autorité parentale exclusive)', 'Livret de famille, pièces d\'identité des parents, pièce d\'identité ou extrait d\'acte de naissance de l\'enfant'],
            ['Les représentants légaux', 'Le mineur peut disposer de petites sommes d\'argent de poche avec l\'accord des parents'],
            ['Impossible au nom du mineur sans autorisation du juge des tutelles', ''],
            ['Acte de disposition : autorisation du juge des tutelles', ''],
            ['Non : le mineur ne peut pas donner procuration', ''],
            ['Les représentants légaux', ''],
        ]),
        $g('Mineur de 16 à 18 ans', [
            ['Le mineur avec l\'autorisation de ses représentants légaux (compte de dépôt) ; livrets possibles', 'Autorisation écrite d\'un représentant légal, pièces d\'identité du mineur et du parent'],
            ['Le mineur dans la limite de l\'autorisation reçue', 'Plafonds adaptés (carte, retrait) selon l\'offre jeune'],
            ['Impossible sans autorisation du juge des tutelles', ''],
            ['Autorisation du juge des tutelles', ''],
            ['Non', ''],
            ['Le mineur avec l\'accord des représentants légaux, ou les représentants légaux', ''],
        ]),
        $g('Mineur émancipé', [
            ['Le mineur émancipé', 'Jugement ou décision d\'émancipation, pièce d\'identité'],
            ['Le mineur émancipé', 'Capacité d\'un majeur pour les actes de la vie courante'],
            ['Le mineur émancipé pour les actes de gestion ; certains actes importants nécessitent l\'autorisation du conseil de famille', 'Vérifier la décision d\'émancipation et les limites éventuelles'],
            ['Le mineur émancipé, avec les mêmes limites', ''],
            ['Le mineur émancipé', ''],
            ['Le mineur émancipé', ''],
        ]),
        $g('Majeur sous tutelle', [
            ['Le tuteur (représentation)', 'Jugement de tutelle, pièce d\'identité du tuteur et du majeur protégé'],
            ['Le tuteur ; le majeur protégé seulement dans la limite fixée par le juge', 'Un compte de gestion peut être ouvert pour le tuteur'],
            ['Le tuteur avec autorisation du juge ou du conseil de famille pour un emprunt', 'Joindre l\'autorisation à l\'offre'],
            ['Autorisation du juge ou du conseil de famille', ''],
            ['En principe non : le majeur protégé ne peut pas donner de procuration', ''],
            ['Le tuteur', 'Peut nécessiter l\'autorisation du juge'],
        ]),
        $g('Majeur sous curatelle', [
            ['Le majeur assisté de son curateur selon le jugement', 'Jugement de curatelle, pièces d\'identité'],
            ['Le majeur seul pour les actes d\'administration, avec le curateur pour les actes importants', 'Selon la mesure : curatelle simple ou renforcée (renforcée : le curateur perçoit les revenus et paie les dépenses)'],
            ['Le majeur assisté de son curateur', 'Co-signature du curateur sur l\'offre'],
            ['Le majeur assisté de son curateur ; autorisation du juge pour certains actes', ''],
            ['À examiner selon le jugement', ''],
            ['Le majeur assisté de son curateur', ''],
        ]),
        $g('Sauvegarde de justice, habilitation familiale, mandat de protection future', [
            ['Selon la décision ou le mandat : le majeur ou la personne habilitée / le mandataire', 'Lire le jugement, l\'habilitation ou le mandat : ils fixent les pouvoirs'],
            ['Selon l\'étendue des pouvoirs', ''],
            ['Selon l\'étendue des pouvoirs ; autorisation du juge pour un acte grave', ''],
            ['Selon l\'étendue des pouvoirs ; autorisation du juge pour un acte grave', ''],
            ['Selon la décision ou le mandat', ''],
            ['Selon la décision ou le mandat', ''],
        ]),
        $g('Société (SARL, SAS, SA, EURL)', [
            ['Le représentant légal (gérant, président…)', 'Kbis de moins de 3 mois, statuts, pièce d\'identité du représentant, bénéficiaires effectifs'],
            ['Le représentant légal ou une personne habilitée', 'Liste des personnes habilitées et pouvoirs à jour'],
            ['Le représentant légal dans la limite des statuts', 'Décision de l\'organe compétent (assemblée, associés) si les statuts l\'exigent'],
            ['Le représentant légal ; la caution du dirigeant est une garantie personnelle distincte', 'Respecter l\'objet social ; caution du dirigeant : mention manuscrite et régime matrimonial à vérifier'],
            ['Le représentant légal', 'Procuration écrite précisant les opérations autorisées'],
            ['Le représentant légal', 'Décision sociale si les statuts l\'exigent'],
        ]),
        $g('Société civile immobilière (SCI)', [
            ['Le gérant', 'Statuts, Kbis, pièce d\'identité du gérant, liste des associés'],
            ['Le gérant ou une personne habilitée', ''],
            ['Le gérant, avec l\'accord des associés si les statuts le prévoient', 'Procès-verbal de l\'assemblée autorisant l\'emprunt et la garantie'],
            ['Le gérant dans la limite des statuts ; caution des associés : engagement personnel de chacun', 'Hypothèque de l\'immeuble : acte authentique ; chaque associé qui se porte caution doit donner sa mention manuscrite'],
            ['Le gérant', ''],
            ['Le gérant', 'Décision des associés si les statuts l\'exigent'],
        ]),
        $g('Association', [
            ['Le représentant légal désigné par les statuts (président, mandataire)', 'Statuts, récépissé de déclaration, publication au Journal officiel, composition du bureau, pièce d\'identité'],
            ['Les personnes habilitées par la décision de l\'organe dirigeant', ''],
            ['Le représentant légal après délibération de l\'organe compétent', 'Procès-verbal du conseil d\'administration ou de l\'assemblée'],
            ['Le représentant légal après délibération', ''],
            ['Le représentant légal', ''],
            ['Le représentant légal', ''],
        ]),
        $g('Indivision', [
            ['Les indivisaires (compte indivis) ; convention d\'indivision utile', 'Pièces de chaque indivisaire, acte de propriété ou de succession'],
            ['Selon la convention d\'indivision (gérant désigné ou signature conjointe)', ''],
            ['Tous les indivisaires pour un acte de disposition', 'Un acte d\'administration peut être décidé à la majorité des deux tiers'],
            ['Unanimité des indivisaires', ''],
            ['Selon la convention d\'indivision', ''],
            ['Tous les indivisaires', ''],
        ]),
        $g('Entrepreneur individuel', [
            ['L\'entrepreneur lui-même', 'Pièce d\'identité, justificatif d\'activité (Kbis, avis de situation Sirene), compte professionnel dédié'],
            ['L\'entrepreneur', 'Compte professionnel distinct du compte personnel'],
            ['L\'entrepreneur', 'Préciser si l\'emprunt engage le patrimoine professionnel, personnel ou les deux'],
            ['L\'entrepreneur pour ses biens ; consentement du conjoint pour une caution selon le régime', ''],
            ['L\'entrepreneur', ''],
            ['L\'entrepreneur', ''],
        ]),
        $g('Mandataire agissant par procuration', [
            ['Non, sauf mandat spécial', 'La procuration bancaire ne couvre pas l\'ouverture d\'un compte au nom du mandant sauf pouvoir exprès'],
            ['Le mandataire, dans la limite de la procuration', 'Contrôler l\'étendue des pouvoirs, la durée, l\'identité du mandataire'],
            ['Non, sauf mandat spécial et exprès', 'Un emprunt par mandataire exige un mandat précis (souvent notarié)'],
            ['Non, sauf mandat spécial ; hypothèque : mandat authentique', 'Un mandat sous seing privé ne suffit pas pour une hypothèque'],
            ['Non, sauf pouvoir exprès de substitution', ''],
            ['Seulement si la procuration le permet explicitement', ''],
        ]),
    ]];
}

function memoDefaultEvenements() {
    return ['groupes' => [
        memoGroup('Naissance ou adoption', [
            ['Ajouter l\'enfant dans le dossier, informer le client des aides disponibles', 'Démarche', 'Livret de famille ou acte de naissance'],
            ['Ouvrir un livret ou un compte pour l\'enfant', 'Proposition', 'Livret A ou livret jeune : voir les plafonds dans le mémo'],
            ['Proposer une épargne de projet pour l\'enfant (assurance-vie, plan d\'épargne)', 'Proposition', 'Selon l\'horizon et la capacité d\'épargne'],
            ['Réviser le budget et vérifier la protection de la famille (prévoyance, assurance habitation)', 'Proposition', ''],
            ['Livret de famille ou acte de naissance', 'Pièce à fournir', ''],
        ]),
        memoGroup('Mariage ou PACS', [
            ['Mettre à jour l\'état civil et le nom d\'usage', 'Démarche', 'Livret de famille ou convention de PACS'],
            ['Ouvrir ou faire évoluer un compte joint, mettre à jour les procurations', 'Démarche', 'Vérifier le régime et la convention de compte joint'],
            ['Proposer un bilan patrimonial et une protection du conjoint (assurance-vie, bénéficiaire)', 'Proposition', ''],
            ['Étudier un projet immobilier commun et le financement', 'Proposition', 'Voir le plan de financement et les crédits spéciaux'],
            ['Livret de famille ou convention de PACS, pièces d\'identité', 'Pièce à fournir', ''],
        ]),
        memoGroup('Divorce ou séparation', [
            ['Faire le point sur les comptes joints (clôture, transformation) et les procurations', 'Démarche', 'Informer chaque cotitulaire ; ne pas clôturer sans en avoir le droit'],
            ['Mettre à jour les moyens de paiement, prélèvements et bénéficiaires d\'assurances', 'Démarche', ''],
            ['Étudier le sort des crédits communs (rachat de soulte, reprise par un seul, rachat de crédit)', 'Proposition', 'Accord du prêteur nécessaire'],
            ['Refaire un budget et vérifier la capacité de remboursement', 'Proposition', ''],
            ['Jugement ou convention de divorce, justificatifs de revenus', 'Pièce à fournir', ''],
        ]),
        memoGroup('Décès d\'un proche', [
            ['Informer l\'agence, bloquer les comptes personnels du défunt', 'Démarche', 'Le compte joint continue de fonctionner'],
            ['Prélever les frais d\'obsèques sur les comptes du défunt dans la limite prévue', 'Démarche', 'Voir le mémo de plafonds et seuils, sur présentation de la facture'],
            ['Orienter vers le notaire pour la succession ; suivre la remise des capitaux', 'Démarche', 'Attestation de dévolution successorale ou acte de notoriété'],
            ['Proposer un accompagnement des capitaux reçus (assurance-vie, épargne, remboursement de crédit)', 'Proposition', 'Le client doit avoir le temps de décider'],
            ['Acte de décès, livret de famille, pièces d\'identité des héritiers, coordonnées du notaire', 'Pièce à fournir', ''],
        ]),
        memoGroup('Premier emploi ou premier logement', [
            ['Domicilier le salaire, ouvrir le compte et la carte adaptés', 'Démarche', ''],
            ['Proposer une épargne de précaution et un versement automatique', 'Proposition', ''],
            ['Proposer l\'assurance habitation et l\'assurance des moyens de paiement', 'Proposition', ''],
            ['Étudier un financement jeune (prêts jeunes, projet immobilier futur)', 'Proposition', ''],
            ['Contrat de travail, bulletins de salaire, justificatif de domicile', 'Pièce à fournir', ''],
        ]),
        memoGroup('Achat de la résidence principale', [
            ['Étudier la capacité d\'emprunt et le plan de financement', 'Démarche', 'Voir le plan de financement complet'],
            ['Vérifier l\'éligibilité au PTZ et aux crédits spéciaux', 'Démarche', 'Voir le simulateur de crédits spéciaux'],
            ['Proposer l\'assurance emprunteur et l\'assurance habitation', 'Proposition', ''],
            ['Proposer l\'épargne de précaution après l\'achat', 'Proposition', ''],
            ['Compromis de vente, pièces d\'identité, justificatifs de revenus et d\'apport, avis d\'imposition', 'Pièce à fournir', ''],
        ]),
        memoGroup('Départ à la retraite', [
            ['Domicilier les pensions, vérifier les prélèvements et les dates d\'encaissement', 'Démarche', ''],
            ['Faire le point sur l\'épargne retraite (PER, assurance-vie) et le mode de sortie', 'Proposition', 'Rente ou capital : voir le simulateur PER'],
            ['Réviser la prévoyance, l\'assurance emprunteur et la transmission', 'Proposition', ''],
            ['Bilan patrimonial', 'Proposition', ''],
            ['Notification de retraite, avis d\'imposition', 'Pièce à fournir', ''],
        ]),
        memoGroup('Perte d\'emploi ou baisse de revenus', [
            ['Examiner les crédits en cours : modulation, report d\'échéances, assurance emprunteur (garantie perte d\'emploi)', 'Démarche', 'Selon les conditions du contrat'],
            ['Refaire le budget et prioriser les charges', 'Démarche', 'Voir la boîte à calculs et le calculateur de budget'],
            ['Proposer un regroupement ou un rachat si la situation le permet', 'Proposition', 'À étudier avec prudence'],
            ['Orienter vers un accompagnement budgétaire si besoin', 'Proposition', ''],
            ['Justificatifs de la nouvelle situation (courrier de l\'employeur, notification d\'allocation)', 'Pièce à fournir', ''],
        ]),
        memoGroup('Déménagement ou changement d\'adresse', [
            ['Mettre à jour l\'adresse dans le dossier client', 'Démarche', 'Justificatif de domicile récent'],
            ['Mettre à jour l\'assurance habitation et les contrats liés', 'Démarche', ''],
            ['Revoir les prélèvements et les virements récurrents', 'Démarche', ''],
        ]),
        memoGroup('Héritage ou donation reçue', [
            ['Identifier la nature des capitaux reçus (liquidités, titres, assurance-vie, immobilier)', 'Démarche', ''],
            ['Proposer un placement ou un remboursement anticipé selon les objectifs', 'Proposition', 'Voir le comparateur épargne ou crédit'],
            ['Faire le point sur la fiscalité et la transmission à son tour', 'Proposition', 'Abattements : voir le mémo de plafonds et seuils'],
            ['Acte de notoriété ou acte de donation, relevé de la succession', 'Pièce à fournir', ''],
        ]),
        memoGroup('Création ou reprise d\'entreprise', [
            ['Ouvrir un compte professionnel distinct du compte personnel', 'Démarche', 'Statuts ou justificatif d\'immatriculation'],
            ['Étudier le financement du projet et les garanties', 'Proposition', ''],
            ['Proposer les assurances de l\'activité et la protection du dirigeant', 'Proposition', ''],
            ['Statuts, Kbis ou avis de situation, pièce d\'identité, plan de financement', 'Pièce à fournir', ''],
        ]),
    ]];
}
