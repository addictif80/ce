<?php
/**
 * Contenu des présentations « Comment ça marche ? » de /tools : une entrée par outil (clé du catalogue).
 * Chaque présentation : une accroche, trois étapes d'utilisation, des points à savoir et, selon l'outil, une diapositive finale commune
 * (partage, impression, mode client). Textes volontairement courts : ils se modifient ici.
 */
function tourDef($icon, $titre, $accroche, array $etapes, $savoir, $closing = true) {
    return ['icon' => $icon, 'titre' => $titre, 'accroche' => $accroche, 'closing' => $closing, 'slides' => [
        ['icon' => 'fa-list-ol', 'titre' => 'En trois étapes', 'liste' => array_map(fn($e) => [$e[0], $e[1], $e[2]], $etapes)],
        ['icon' => 'fa-lightbulb', 'titre' => 'Bon à savoir', 'texte' => $savoir[0], 'detail' => $savoir[1] ?? ''],
    ]];
}

function toolTours() {
    static $t = null; if ($t !== null) return $t;
    $t = [
        'calculateur' => tourDef('fa-calculator', 'Calculateur de budget', 'Estimez le reste à vivre d\'un foyer à partir de ses revenus et de ses charges.', [
            ['fa-pen', 'Saisissez les revenus et les charges', 'Salaires, loyers, crédits, charges courantes : chaque ligne s\'ajoute au budget.'],
            ['fa-gauge', 'Lisez le reste à vivre', 'Il se met à jour à chaque modification, avec le détail par catégorie.'],
            ['fa-print', 'Remettez le budget', 'Un document imprimable résume la situation pour le client.']],
            ['Les chiffres restent dans votre navigateur.', 'Rien n\'est envoyé ni conservé : fermer la page efface la saisie.']),
        'capacite' => tourDef('fa-hand-holding-dollar', 'Capacité d\'emprunt', 'Estimez le capital empruntable et le budget d\'achat possible.', [
            ['fa-wallet', 'Renseignez revenus et charges', 'Revenus nets du foyer, charges de crédit en cours, loyer conservé.'],
            ['fa-sliders', 'Choisissez les conditions du prêt', 'Taux, durée, assurance, frais et apport : le résultat suit.'],
            ['fa-chart-pie', 'Lisez la capacité', 'Mensualité maximale, capital empruntable, budget d\'achat et reste à vivre.']],
            ['Le taux d\'endettement maximal vient du barème HCSF.', 'Vérifiez la date de contrôle du barème affichée au-dessus des résultats.']),
        'notaire' => tourDef('fa-scale-balanced', 'Frais de notaire', 'Estimez les frais d\'acquisition d\'un bien ancien ou neuf.', [
            ['fa-house', 'Indiquez le prix et le type de bien', 'Ancien ou neuf, département, mobilier, primo-accédant si le département le prévoit.'],
            ['fa-coins', 'Ajoutez les débours estimés', 'Formalités et frais divers selon votre connaissance du dossier.'],
            ['fa-receipt', 'Lisez le détail', 'Droits, émoluments, TVA, contribution de sécurité immobilière : total et pourcentage du prix.']],
            ['Les taux départementaux changent.', 'Contrôlez le barème (date affichée) et rappelez au client que le montant définitif est fixé par le notaire.']),
        'ptz' => tourDef('fa-percent', 'Simulateur PTZ', 'Vérifiez l\'éligibilité au prêt à taux zéro et estimez son montant.', [
            ['fa-users', 'Décrivez le foyer', 'Nombre de personnes, revenu fiscal de référence N-2, primo-accession, résidence principale.'],
            ['fa-map-location-dot', 'Situez l\'opération', 'Département et commune pour déduire la zone, type de bien et coût de l\'opération.'],
            ['fa-circle-check', 'Lisez le résultat', 'Éligibilité, montant, durée, différé et mensualité après le différé.']],
            ['Avec un PTZ, c\'est le revenu fiscal de référence N-2 qui compte.', 'Ancien avec travaux : les travaux doivent atteindre 25 % du coût de l\'opération.']),
        'relais' => tourDef('fa-house-circle-check', 'Prêt relais', 'Estimez un prêt relais, son coût et ce qu\'il reste après la vente.', [
            ['fa-house', 'Indiquez le bien à vendre', 'Valeur estimée, crédit restant dû, durée du relais.'],
            ['fa-percent', 'Saisissez les conditions', 'Taux, frais et quotité retenue sur la valeur du bien.'],
            ['fa-sack-dollar', 'Lisez le montant et le coût', 'Capital du relais, intérêts, et solde après la vente.']],
            ['Le prêt relais est un financement à court terme.', 'Il se rembourse avec le prix de vente : vérifiez les délais de vente avec le client.']),
        'rachat' => tourDef('fa-arrows-rotate', 'Rachat de crédits', 'Regroupez des crédits et mesurez le gain de mensualité et le coût total.', [
            ['fa-list', 'Ajoutez chaque crédit', 'Capital restant dû, taux, mensualité et durée restante.'],
            ['fa-sliders', 'Choisissez le nouveau prêt', 'Taux, durée, frais, indemnités de remboursement anticipé.'],
            ['fa-scale-balanced', 'Comparez', 'Mensualité avant et après, coût total et durée.']],
            ['Une mensualité plus basse peut coûter plus cher au total.', 'Montrez toujours les deux chiffres au client.']),
        'pieces' => tourDef('fa-list-check', 'Pièces justificatives', 'Composez la liste des documents à demander au client.', [
            ['fa-clipboard-list', 'Choisissez le dossier', 'Type de projet et de situation : la liste se construit.'],
            ['fa-square-check', 'Cochez ou retirez', 'Retirez les lignes qui ne concernent pas le client pour le document imprimé.'],
            ['fa-print', 'Imprimez ou partagez', 'Document client prêt à remettre ou lien à envoyer.']],
            ['La liste reste dans votre navigateur.', 'Le nom du client et les cases cochées ne sont ni envoyés ni conservés.']),
        'pdf' => tourDef('fa-file-pdf', 'Boîte à outils PDF', 'Fusionnez, découpez, pivotez ou créez des PDF sans envoyer vos fichiers.', [
            ['fa-file-import', 'Ajoutez vos fichiers', 'Glissez-déposez des PDF ou des images.'],
            ['fa-arrows-up-down-left-right', 'Choisissez l\'action', 'Fusionner, extraire des pages, faire pivoter, convertir des images.'],
            ['fa-download', 'Téléchargez le résultat', 'Le fichier est produit dans votre navigateur.']],
            ['Aucun fichier n\'est envoyé.', 'Le traitement se fait sur votre poste : vos documents restent confidentiels.'], false),
        'courrier' => tourDef('fa-envelope-open-text', 'Générateur de courrier', 'Rédigez un courrier mis en forme avec des variables.', [
            ['fa-file-lines', 'Choisissez un modèle', 'Ou partez d\'une page vierge.'],
            ['fa-pen-nib', 'Complétez les variables', 'Nom, adresse, dates : elles remplacent les champs du modèle.'],
            ['fa-print', 'Imprimez ou enregistrez en PDF', 'Le courrier reste dans votre navigateur.']],
            ['Relisez toujours le courrier avant de l\'envoyer.', 'Les modèles proposés sont indicatifs.'], false),
        'dpe' => tourDef('fa-leaf', 'Recherche DPE', 'Retrouvez les diagnostics de performance énergétique d\'une adresse.', [
            ['fa-location-dot', 'Saisissez l\'adresse', 'L\'adresse est recherchée dans la Base Adresse Nationale.'],
            ['fa-list', 'Parcourez les diagnostics', 'Liste ou carte, avec l\'étiquette énergie et climat.'],
            ['fa-file-circle-check', 'Ouvrez le détail', 'Date, numéro et consommation du diagnostic.']],
            ['Les données viennent de l\'ADEME.', 'L\'adresse saisie est transmise aux API publiques, sans être conservée par ce portail.'], false),
        'rge' => tourDef('fa-certificate', 'Vérification RGE', 'Vérifiez la certification RGE d\'une entreprise de travaux.', [
            ['fa-magnifying-glass', 'Recherchez l\'entreprise', 'Par nom, SIREN ou SIRET.'],
            ['fa-certificate', 'Lisez les qualifications', 'Domaines de travaux et dates de validité.'],
            ['fa-circle-check', 'Contrôlez la validité', 'Une qualification expirée ne donne pas droit aux aides.']],
            ['La recherche interroge l\'API publique de l\'ADEME.', 'Le nom ou le numéro saisi n\'est pas conservé par ce portail.'], false),
        'bureau_dom' => tourDef('fa-building', 'Bureau domiciliaire', 'Remplissez le formulaire de modification de bureau domiciliaire.', [
            ['fa-pen', 'Complétez les champs', 'Identité du client, ancien et nouveau bureau.'],
            ['fa-eye', 'Vérifiez l\'aperçu', 'Le formulaire se met en page à mesure de la saisie.'],
            ['fa-print', 'Imprimez', 'Le document est prêt à signer.']],
            ['Le formulaire reste dans votre navigateur.', 'Rien n\'est envoyé ni conservé.'], false),
        'procedures' => tourDef('fa-book', 'Procédures', 'Consultez les procédures publiées et leur contenu.', [
            ['fa-folder-open', 'Choisissez une catégorie', 'Les procédures sont classées par thème.'],
            ['fa-magnifying-glass', 'Cherchez un mot-clé', 'Le filtre réduit la liste au fur et à mesure.'],
            ['fa-pen-to-square', 'Proposez une amélioration', 'Une proposition d\'ajout ou de modification est soumise à validation.']],
            ['Consultation sans enregistrement.', 'Seules vos propositions, que vous choisissez d\'envoyer, sont conservées pour validation.'], false),
        'codes' => tourDef('fa-key', 'Codes utiles', 'Retrouvez la liste des codes validés et leur fonction.', [
            ['fa-magnifying-glass', 'Cherchez un code', 'Par numéro ou par mot de sa fonction.'],
            ['fa-list', 'Lisez la fonction', 'Chaque code est expliqué.'],
            ['fa-pen-to-square', 'Proposez un ajout', 'Une proposition est validée par un administrateur avant publication.']],
            ['Consultation sans enregistrement.', 'Seules vos propositions sont conservées pour validation.'], false),
        'contacts' => tourDef('fa-address-book', 'Contacts utiles', 'Trouvez le bon service, son téléphone et son e-mail.', [
            ['fa-magnifying-glass', 'Cherchez un service ou un motif', 'Le filtre porte sur le service et le motif de contact.'],
            ['fa-phone', 'Contactez', 'Téléphone et e-mail affichés pour chaque service.'],
            ['fa-pen-to-square', 'Proposez une mise à jour', 'Une proposition est validée par un administrateur.']],
            ['Consultation sans enregistrement.', 'Seules vos propositions sont conservées pour validation.'], false),
        'dates' => tourDef('fa-calendar-days', 'Calculateur de dates', 'Ajoutez des jours ouvrés, ouvrables ou calendaires et mesurez un écart.', [
            ['fa-calendar-plus', 'Choisissez la date et la durée', 'Jours calendaires, ouvrés (lun-ven), ouvrables (lun-sam), mois ou années.'],
            ['fa-flag', 'Repérez les fériés', 'Les jours fériés légaux sont pris en compte automatiquement.'],
            ['fa-stopwatch', 'Utilisez les délais usuels', 'Offre de prêt, rétractation, contestation : saisissez la date de départ.']],
            ['Les jours fériés sont ceux de la France métropolitaine.', 'Ils ne tiennent pas compte des jours de fermeture propres à un établissement.']),
        'validateurs' => tourDef('fa-shield-halved', 'Validateurs de numéros', 'Contrôlez la forme d\'un IBAN, RIB, SIREN / SIRET, carte, numéro de sécurité sociale ou BIC.', [
            ['fa-hand-pointer', 'Choisissez le type de numéro', 'Un onglet par type.'],
            ['fa-keyboard', 'Saisissez le numéro', 'Espaces et tirets sont acceptés.'],
            ['fa-circle-check', 'Lisez le contrôle', 'Longueur, clé de contrôle, découpage et informations déduites.']],
            ['Le contrôle porte sur la forme, pas sur l\'existence.', 'Les numéros restent dans votre navigateur : ils ne sont ni enregistrés, ni repris dans un lien ou une impression.'], false),
        'calculs' => tourDef('fa-calculator', 'Boîte à calculs', 'Pourcentages, TVA, règle de trois, durées, prorata, intérêts et taux équivalents.', [
            ['fa-grip', 'Choisissez le bloc', 'Chaque calcul a sa carte : les résultats sont instantanés.'],
            ['fa-keyboard', 'Modifiez les valeurs', 'Le résultat se met à jour à chaque frappe.'],
            ['fa-link', 'Partagez le lien si besoin', 'Le lien reprend toutes les valeurs saisies.']],
            ['Ce sont des calculs rapides.', 'Pour un calcul réglementaire (crédit, TAEG), utilisez l\'outil dédié.'], false),
        'saisie' => tourDef('fa-gavel', 'Quotité saisissable', 'Calculez la part saisissable d\'une rémunération et le solde bancaire insaisissable.', [
            ['fa-user', 'Saisissez la rémunération nette', 'Mensuelle, primes comprises.'],
            ['fa-people-roof', 'Ajoutez les personnes à charge', 'Elles relèvent les seuils du barème.'],
            ['fa-table', 'Lisez le détail par tranche', 'Quotité de chaque tranche, part saisissable et reste disponible.']],
            ['Le débiteur conserve au moins le RSA personne seule.', 'Le barème est revalorisé chaque année : vérifiez sa date de contrôle.']),
        'memo_plafonds' => tourDef('fa-gauge-high', 'Mémo : plafonds et seuils', 'Plafonds des livrets, espèces, garantie des dépôts et abattements.', [
            ['fa-magnifying-glass', 'Filtrez le mémo', 'Tapez un mot : seules les lignes concernées restent.'],
            ['fa-eye', 'Lisez la valeur et la précision', 'Chaque ligne indique le montant et la condition.'],
            ['fa-print', 'Imprimez la fiche', 'Document daté avec la mention de vérification.']],
            ['Les plafonds évoluent par décret ou loi de finances.', 'La date de contrôle affichée vous dit si le mémo est à jour.'], false),
        'memo_delais' => tourDef('fa-hourglass-half', 'Mémo : délais légaux', 'Délais de réflexion, de rétractation, de contestation et de réponse.', [
            ['fa-magnifying-glass', 'Filtrez le mémo', 'Tapez un mot : seules les lignes concernées restent.'],
            ['fa-clock', 'Lisez le délai et sa précision', 'Point de départ et conditions éventuelles.'],
            ['fa-calendar-days', 'Calculez la date', 'Le calculateur de dates donne l\'échéance exacte.']],
            ['Ces délais sont des repères.', 'Vérifiez le texte applicable au contrat du client en cas de doute.'], false),
        'epargne' => tourDef('fa-piggy-bank', 'Simulateur d\'épargne', 'Estimez le capital constitué, le versement ou la durée pour atteindre un objectif.', [
            ['fa-pen', 'Renseignez l\'épargne', 'Capital de départ, versement mensuel, durée, rendement et frais.'],
            ['fa-landmark', 'Choisissez la fiscalité', 'Livrets, prélèvement forfaitaire ou assurance-vie.'],
            ['fa-bullseye', 'Fixez un objectif', 'Le simulateur donne le versement ou la durée nécessaires.']],
            ['Les gains sont imposés à la sortie dans ce simulateur.', 'C\'est une estimation : le rendement futur n\'est pas garanti.']),
        'assurancevie' => tourDef('fa-shield-heart', 'Assurance-vie', 'Projection d\'un contrat, fiscalité d\'un rachat et capital décès.', [
            ['fa-chart-line', 'Projetez le contrat', 'Versements, rendement, frais de gestion et sur versements.'],
            ['fa-hand-holding-dollar', 'Simulez un rachat', 'Ancienneté, part de gains, abattement, impôt et prélèvements sociaux.'],
            ['fa-people-roof', 'Calculez le capital décès', 'Bénéficiaires, abattement et prélèvement.']],
            ['Les taux et abattements viennent du barème de fiscalité.', 'Vérifiez sa date de contrôle : les règles fiscales évoluent chaque année.']),
        'per' => tourDef('fa-umbrella-beach', 'Simulateur PER', 'Estimez l\'économie d\'impôt d\'un versement sur un plan d\'épargne retraite.', [
            ['fa-users', 'Décrivez le foyer fiscal', 'Revenu net imposable et nombre de parts.'],
            ['fa-piggy-bank', 'Indiquez le versement', 'Le plafond de déduction se calcule sur les revenus professionnels.'],
            ['fa-percent', 'Lisez l\'économie', 'Impôt avant et après, taux marginal et coût réel.']],
            ['L\'avantage est surtout un report d\'imposition.', 'À la sortie, les sommes déduites sont imposées : estimation sans décote ni plafonnement du quotient familial.']),
        'epargnecredit' => tourDef('fa-code-compare', 'Épargne ou crédit ?', 'Comparez le coût d\'un financement par votre épargne, un crédit, ou un mélange.', [
            ['fa-bullseye', 'Décrivez le besoin', 'Montant, épargne disponible, épargne de précaution à conserver.'],
            ['fa-file-contract', 'Saisissez le crédit', 'Taux, durée, assurance et frais.'],
            ['fa-scale-balanced', 'Comparez les trois scénarios', 'Coût actualisé et écart à l\'échéance.']],
            ['Le seuil d\'indifférence est le taux effectif du crédit.', 'Si votre épargne rapporte plus (net d\'impôt), l\'emprunt devient avantageux.']),
        'creditsspeciaux' => tourDef('fa-gift', 'Crédits spéciaux', 'Éligibilité et montant du PTZ, Doublissimo, Primo Jeune, Primoz et Grandioz.', [
            ['fa-house', 'Décrivez le projet et les emprunteurs', 'Type d\'opération, zone, revenus, dates de naissance.'],
            ['fa-sliders', 'Réglez les hypothèses', 'Taux et durée du prêt principal, taux des prêts spéciaux.'],
            ['fa-circle-check', 'Lisez chaque fiche', 'Conditions réunies, à vérifier ou non éligible, avec le montant.']],
            ['Le moteur est celui du module crédit immobilier.', 'Les règles viennent des barèmes : leur date de contrôle est affichée.']),
        'plan' => tourDef('fa-diagram-project', 'Plan de financement complet', 'Montez le financement d\'un projet avec les prêts aidés et spéciaux.', [
            ['fa-coins', 'Chiffrez le projet', 'Acquisition, travaux, frais, garantie, apport.'],
            ['fa-layer-group', 'Composez les prêts', 'PTZ, Doublissimo, Primo Jeune, Primoz, Grandioz, prêt principal.'],
            ['fa-gauge', 'Contrôlez le résultat', 'Mensualité, endettement, reste à vivre, TAEG et alertes d\'éligibilité.']],
            ['Le prêt principal couvre le solde à financer.', 'Les résultats suivent les règles du logiciel de dossiers : mêmes mensualités, mêmes TAEG.']),
        'scenarios' => tourDef('fa-code-compare', 'Comparateur de scénarios', 'Comparez jusqu\'à trois montages de financement côte à côte.', [
            ['fa-house', 'Décrivez le projet', 'Il est commun aux trois montages.'],
            ['fa-sliders', 'Réglez chaque montage', 'Apport, taux, durée et prêts aidés ou spéciaux.'],
            ['fa-table', 'Comparez', 'Les meilleures valeurs sont soulignées en vert.']],
            ['Un montage plus long baisse la mensualité mais augmente le coût.', 'Regardez à la fois la mensualité, le reste à vivre et le coût total.']),
        'signataires' => tourDef('fa-signature', 'Qui peut signer quoi ?', 'Selon la situation du client, qui peut effectuer chaque opération.', [
            ['fa-user-tag', 'Choisissez la situation', 'Mineur, majeur protégé, couple, société, indivision, mandataire…'],
            ['fa-file-signature', 'Choisissez l\'opération', 'Ouverture, emprunt, garantie, procuration, clôture.'],
            ['fa-pen-nib', 'Lisez qui signe et les pièces', 'Avec les points d\'attention.']],
            ['C\'est une aide à l\'orientation, pas un avis juridique.', 'En cas de doute, consultez la conformité ou le service juridique.'], true),
        'evenements' => tourDef('fa-route', 'Parcours événements de vie', 'Démarches, solutions à proposer et pièces à demander selon l\'événement.', [
            ['fa-flag', 'Choisissez l\'événement', 'Naissance, mariage, décès, retraite, achat, perte d\'emploi…'],
            ['fa-square-check', 'Cochez au fil de l\'entretien', 'Démarches, propositions et pièces.'],
            ['fa-print', 'Imprimez la liste', 'Le client repart avec ce qui reste à faire.']],
            ['Les listes se modifient dans l\'administration.', 'Adaptez-les aux offres et aux procédures de la Caisse d\'Épargne.']),
        'usure' => tourDef('fa-ban', 'Taux d\'usure', 'Les seuils de l\'usure en vigueur et le contrôle d\'un TAEG.', [
            ['fa-list', 'Choisissez la catégorie de prêt', 'Immobilier à taux fixe, variable, prêt relais…'],
            ['fa-percent', 'Saisissez le TAEG de l\'offre', 'Assurance, frais et garantie compris.'],
            ['fa-circle-check', 'Lisez le verdict', 'Marge sous le seuil, ou dépassement à corriger.']],
            ['Les seuils changent chaque trimestre.', 'Vérifiez la période d\'application affichée avant toute décision.'], true),
    ];
    return $t;
}
