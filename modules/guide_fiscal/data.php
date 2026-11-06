<?php
// Guide de la fiscalité bancaire et patrimoniale (barèmes 2026). Données vérifiées le 06/11/2026 par recoupement de sources officielles
// (service-public.gouv.fr, impots.gouv.fr, BOFiP, Légifrance). Une ligne portant le drapeau 'w' est à confirmer avant diffusion.
// Format d'une ligne : [libellé, valeur, note facultative, 'w' si à confirmer]
$SP = 'https://www.service-public.gouv.fr/particuliers/vosdroits/';
return [
'verifie' => '06/11/2026',
'sections' => [

['id' => 'bases', 'titre' => 'Taux et principes de base', 'icon' => 'fa-percent',
 'intro' => 'Les taux de référence de l\'année : prélèvements sociaux, flat tax, barème de l\'impôt sur le revenu.',
 'cards' => [
  ['titre' => 'Prélèvements sociaux sur les revenus du patrimoine (2026)', 'statut' => 'ok', 'rows' => [
    ['Cas général (dividendes, intérêts, plus-values mobilières…)', '18,6 %', 'CSG 10,6 % + CRDS 0,5 % + prélèvement de solidarité 7,5 %. La CSG passe de 9,2 % à 10,6 % par la loi de financement de la sécurité sociale pour 2026 (loi n° 2025-1403 du 30/12/2025, art. 12).'],
    ['Taux maintenu à 17,2 % (CSG à 9,2 %)', 'Assurance-vie, PEL, CEL, PEP, revenus fonciers, plus-values immobilières', 'Liste publiée par service-public.gouv.fr (page vérifiée le 30/06/2026) et par la brochure fiscale 2026.'],
    ['Exonérés', 'Livret A, livret jeune, LEP, LDDS, gains du plan d\'épargne avenir climat', ''],
    ['Non-résident affilié à la sécurité sociale d\'un pays de l\'EEE ou de la Suisse', '7,5 % (prélèvement de solidarité seul)', 'Sur attestation sur l\'honneur. Les revenus fonciers et plus-values immobilières restent à 17,2 %, la location meublée non professionnelle à 18,6 %.'],
    ['CSG déductible du revenu imposable (option barème)', '6,8 %', 'Maintenue à 6,8 % d\'après la brochure fiscale 2026.'],
  ], 'sources' => [['Prélèvements sociaux (service-public)', $SP . 'F2329'], ['LFSS 2026, article 12 (Légifrance)', 'https://www.legifrance.gouv.fr/jorf/article_jo/JORFARTI000053226452']]],
  ['titre' => 'Prélèvement forfaitaire unique (flat tax)', 'statut' => 'ok', 'rows' => [
    ['Taux global (cas général)', '31,4 %', '12,8 % d\'impôt sur le revenu + 18,6 % de prélèvements sociaux. Il était de 30 % jusqu\'en 2025.'],
    ['Taux global assurance-vie (hors régimes spécifiques)', '30 %', '12,8 % + 17,2 % de prélèvements sociaux. Certains contrats restent à 7,5 % d\'impôt au-delà de 8 ans (voir assurance-vie).'],
    ['Option pour le barème progressif', 'Case 2OP', 'Elle porte sur l\'ensemble des revenus de capitaux mobiliers et plus-values de cession de valeurs mobilières. À compter des revenus 2026, elle n\'est plus irrévocable : on peut y renoncer.'],
    ['Abattement de 40 % sur les dividendes', 'Barème uniquement', 'Il n\'existe pas avec le PFU. Le barème est en général plus favorable à un foyer non imposable ou imposé à 11 %, le PFU à partir de 30 %.'],
    ['Dispense d\'acompte sur dividendes et plus-values', 'RFR N-2 < 50 000 € (seul) ou 75 000 € (couple)', 'Demande à l\'établissement au plus tard le 30/11/2026 pour les revenus 2026 ; c\'est le RFR 2024 qui compte.'],
    ['Dispense d\'acompte sur intérêts', 'RFR N-2 < 25 000 € (seul) ou 50 000 € (couple)', 'Même formalité. Seuils cités pour les intérêts du PER ; à confirmer pour les autres produits de taux.', 'w'],
  ], 'sources' => [['Revenus d\'épargne et de placement 2026', $SP . 'F34913/1_7'], ['Les revenus mobiliers (impots.gouv)', 'https://www.impots.gouv.fr/particulier/les-revenus-mobiliers']]],
  ['titre' => 'Barème de l\'impôt sur le revenu 2026 (revenus 2025)', 'statut' => 'ok', 'rows' => [
    ['Jusqu\'à 11 600 €', '0 %', ''],
    ['De 11 601 € à 29 579 €', '11 %', ''],
    ['De 29 580 € à 84 577 €', '30 %', ''],
    ['De 84 578 € à 181 917 €', '41 %', ''],
    ['Au-delà de 181 917 €', '45 %', 'Barème par part de quotient familial. Revalorisation de 0,9 % par la loi de finances pour 2026.'],
    ['Plafonnement de l\'avantage par demi-part', '1 807 €', 'Soit 3 614 € pour deux enfants. Parent isolé : 4 262 € avec un enfant, 6 069 € avec deux. Réduit de moitié en résidence alternée.'],
  ], 'sources' => [['Barème de l\'impôt sur le revenu (service-public)', $SP . 'F1419']]],
 ]],

['id' => 'epargne', 'titre' => 'Épargne de précaution et de taux', 'icon' => 'fa-piggy-bank',
 'intro' => 'Livrets réglementés et épargne logement. Les taux d\'intérêt réglementés changent deux fois par an : consultez la Banque de France ou service-public avant de les citer.',
 'cards' => [
  ['titre' => 'Livrets réglementés', 'statut' => 'ok', 'rows' => [
    ['Livret A, livret jeune, LEP, LDDS', 'Intérêts exonérés d\'impôt sur le revenu et de prélèvements sociaux', ''],
    ['Taux du Livret A', 'Non figé dans ce guide', 'Fixé par arrêté pour des périodes semestrielles (la fiche service-public indique 1,7 % du 01/08/2026 au 31/01/2027). Des sources secondaires donnent des valeurs différentes : se référer à l\'arrêté.'],
    ['Plafonds de dépôt', 'Voir « Mémo : plafonds et seuils »', 'Non revérifiés ici.'],
  ], 'sources' => [['Livret A (service-public)', $SP . 'F2365']]],
  ['titre' => 'Plan d\'épargne logement (PEL) et compte épargne logement (CEL)', 'statut' => 'warn', 'rows' => [
    ['Taux d\'un PEL ouvert à partir du 01/01/2026', '2 %', 'Hors prime d\'État. Plafond de versements 61 200 €.'],
    ['Intérêts d\'un PEL ouvert depuis 2018', 'PFU : 12,8 % d\'impôt + prélèvements sociaux', 'Taux de prélèvements sociaux : 17,2 % selon service-public (tableau 2026), 18,6 % selon la brochure fiscale. Divergence non tranchée.', 'w'],
    ['Intérêts d\'un PEL ouvert jusqu\'en 2017', 'Exonérés d\'impôt sur le revenu les 12 premières années ; prélèvements sociaux dus', 'Le taux exact des prélèvements sociaux n\'a pas pu être confirmé.', 'w'],
  ], 'sources' => [['Prélèvements sociaux (service-public)', $SP . 'F2329'], ['Hausse du taux du PEL en 2026 (service-public)', 'https://www.service-public.gouv.fr/particuliers/actualites/A16217']]],
 ]],

['id' => 'av', 'titre' => 'Assurance-vie et contrats de capitalisation', 'icon' => 'fa-shield-heart',
 'intro' => 'Fiscalité des rachats (de son vivant) et fiscalité au décès.',
 'cards' => [
  ['titre' => 'Rachats : primes versées depuis le 27/09/2017', 'statut' => 'ok', 'rows' => [
    ['À chaque retrait', 'Prélèvement forfaitaire non libératoire', '12,8 % si le contrat a moins de 8 ans, 7,5 % à partir de 8 ans. Il s\'impute sur l\'impôt de l\'année suivante ; l\'excédent est restitué.'],
    ['Contrat de moins de 8 ans', 'PFU 12,8 %', ''],
    ['Contrat de 8 ans et plus', 'PFU 7,5 % si l\'encours (primes nettes) ≤ 150 000 €', 'Au-delà, la fraction excédentaire est imposée à 12,8 %.'],
    ['Abattement annuel des gains (contrat ≥ 8 ans)', '4 600 € (seul) · 9 200 € (couple)', 'Appliqué à l\'ensemble des contrats du foyer, uniquement sur l\'impôt (pas sur les prélèvements sociaux).'],
    ['Prélèvements sociaux', '17,2 %', 'Prélevés par l\'assureur sur la part de gains de chaque retrait. L\'assurance-vie est exclue de la hausse de la CSG de 2026.'],
    ['Option pour le barème progressif', 'Case 2OP', 'Elle concerne l\'ensemble des revenus mobiliers du foyer.'],
  ], 'sources' => [['Fiscalité des retraits (impots.gouv)', 'https://www.impots.gouv.fr/particulier/questions/jai-effectue-des-retraits-sur-mon-contrat-dassurance-vie-quelles-sont-les'], ['Prélèvements sociaux (service-public)', $SP . 'F2329']]],
  ['titre' => 'Rachats : primes versées jusqu\'au 26/09/2017', 'statut' => 'ok', 'rows' => [
    ['Régime par défaut', 'Barème progressif de l\'impôt sur le revenu', ''],
    ['Option pour le prélèvement forfaitaire libératoire', '35 % (< 4 ans) · 15 % (4 à < 8 ans) · 7,5 % (≥ 8 ans)', 'Après 8 ans, l\'abattement de 4 600 € / 9 200 € s\'applique.'],
  ], 'sources' => [['Fiscalité des retraits (impots.gouv)', 'https://www.impots.gouv.fr/particulier/questions/jai-effectue-des-retraits-sur-mon-contrat-dassurance-vie-quelles-sont-les']]],
  ['titre' => 'Décès : transmission aux bénéficiaires', 'statut' => 'ok', 'rows' => [
    ['Primes versées avant les 70 ans de l\'assuré (art. 990 I du CGI)', 'Abattement de 152 500 € par bénéficiaire, tous contrats confondus', 'Au-delà : 20 % jusqu\'à 700 000 € de part taxable, 31,25 % au-delà.'],
    ['Primes versées après les 70 ans de l\'assuré (art. 757 B du CGI)', 'Abattement global de 30 500 €', 'Au-delà, les primes (hors produits) sont soumises aux droits de succession selon le lien de parenté. Les gains ne sont pas taxés.'],
    ['Conjoint ou partenaire de Pacs bénéficiaire', 'Exonéré', 'Les frères et sœurs remplissant les conditions de l\'article 796-0 ter peuvent l\'être aussi.'],
  ], 'sources' => [['Prélèvement sur l\'assurance décès (BOFiP TCAS-AUT-60)', 'https://bofip.impots.gouv.fr/bofip/1335-PGP.html/identifiant=BOI-TCAS-AUT-60-20230330'], ['Bénéficiaire d\'une assurance-vie (impots.gouv)', 'https://www.impots.gouv.fr/particulier/questions/je-suis-beneficiaire-dune-assurance-vie-comment-la-declarer']]],
 ]],

['id' => 'titres', 'titre' => 'Titres financiers : PEA et compte-titres', 'icon' => 'fa-chart-line',
 'intro' => 'Gains sur actions, OPC, obligations, dividendes.',
 'cards' => [
  ['titre' => 'PEA', 'statut' => 'warn', 'rows' => [
    ['Retrait après 5 ans', 'Exonération d\'impôt sur le revenu ; prélèvements sociaux 18,6 % sur les gains', 'Un retrait partiel après 5 ans ne clôt pas le plan. Le taux appliqué est celui en vigueur au moment du retrait, y compris pour les gains accumulés avant 2026.'],
    ['Retrait avant 5 ans', 'Clôture du plan et PFU de 31,4 % sur les gains', 'Exceptions : création ou reprise d\'entreprise, licenciement, invalidité, retraite anticipée.'],
    ['Plafond de versements du PEA', '150 000 €', 'Une seule source secondaire. À confirmer sur service-public ou le BOFiP avant diffusion.', 'w'],
    ['Plafond de versements du PEA-PME', 'Non vérifié', 'Aucune source consultée ne le donne : à confirmer.', 'w'],
  ], 'sources' => [['L\'assurance-vie et le PEA (impots.gouv)', 'https://www.impots.gouv.fr/particulier/lassurance-vie-et-le-pea-0'], ['Prélèvements sociaux (service-public)', $SP . 'F2329']]],
  ['titre' => 'Compte-titres ordinaire', 'statut' => 'warn', 'rows' => [
    ['Dividendes, intérêts, plus-values de cession', 'PFU 31,4 % ou barème progressif sur option', 'Voir « Taux et principes de base ».'],
    ['Abattement de 40 % sur les dividendes', 'Barème uniquement', 'Les frais ne sont pas déductibles au PFU.'],
    ['Report des moins-values', '10 ans', 'Règle générale connue ; non retrouvée dans les sources consultées pour cette mise à jour.', 'w'],
  ], 'sources' => [['Revenus d\'épargne et de placement 2026', $SP . 'F34913/1_7'], ['Aide à la déclaration des capitaux mobiliers (impots.gouv)', 'https://simulateur-ir-ifi.impots.gouv.fr/calcul_impot/2026/aides/capitaux_mobiliers.htm']]],
 ]],

['id' => 'per', 'titre' => 'Épargne retraite (PER)', 'icon' => 'fa-umbrella-beach',
 'intro' => 'Déductibilité à l\'entrée, fiscalité à la sortie.',
 'cards' => [
  ['titre' => 'Versements : plafond de déduction 2026', 'statut' => 'ok', 'rows' => [
    ['Salarié', '10 % des revenus d\'activité nets 2025, maximum 37 680 €', 'Plancher de 4 710 €.'],
    ['Sans revenu professionnel', '4 710 €', ''],
    ['Report des plafonds non utilisés', 'Utilisables pendant 5 ans', 'Part 2024 utilisable jusqu\'en 2027, part 2025 jusqu\'en 2028, part 2026 et suivantes pendant 5 ans (le plus ancien d\'abord).'],
    ['Versements après 70 ans', 'Plus déductibles depuis le 01/01/2026', ''],
    ['Plafond personnalisé', 'Mentionné sur l\'avis d\'impôt 2026', 'Il inclut les reports. Indépendants : règles particulières.'],
  ], 'sources' => [['PER individuel (service-public)', $SP . 'F36526/0']]],
  ['titre' => 'Sorties', 'statut' => 'ok', 'rows' => [
    ['Capital, versements déduits', 'Barème de l\'IR, sans abattement de 10 % ni prélèvements sociaux', ''],
    ['Capital, part de gains', 'PFU 31,4 %', '12,8 % + 18,6 %. 30 % pour les gains perçus jusqu\'au 31/12/2025.'],
    ['Rente', 'Régime des pensions (abattement de 10 %, plafonné par foyer)', 'Prélèvements sociaux 18,6 % depuis 2026 sur une fraction de la rente : 70 % (< 50 ans), 50 % (50-59), 40 % (60-69), 30 % (> 69 ans).'],
    ['Déblocage pour accident de la vie', 'Versements exonérés d\'impôt et de prélèvements sociaux', 'Les gains supportent les prélèvements sociaux.'],
    ['Déblocage pour la résidence principale', 'Versements déduits : IR sans abattement, sans prélèvements sociaux ; gains au PFU', 'Les droits issus de versements obligatoires restent bloqués.'],
    ['Transfert vers un autre PER', 'Gratuit après 5 ans', 'Avant : frais limités à 1 % de l\'épargne.'],
  ], 'sources' => [['PER individuel (service-public)', $SP . 'F36526/0']]],
  ['titre' => 'Point d\'attention : taux de prélèvements sociaux du PER', 'statut' => 'ok', 'rows' => [
    ['Gains du PER, quelle que soit l\'enveloppe (bancaire ou assurantielle)', '18,6 % depuis 2026', 'La page service-public du PER (vérifiée le 12/10/2026) retient 18,6 %. Des sources secondaires hésitaient pour le PER assurantiel : la source officielle prévaut.'],
  ], 'sources' => [['PER individuel (service-public)', $SP . 'F36526/0']]],
 ]],

['id' => 'immo', 'titre' => 'Immobilier : plus-values et IFI', 'icon' => 'fa-house',
 'intro' => 'Cession d\'un bien immobilier et impôt sur la fortune immobilière.',
 'cards' => [
  ['titre' => 'Plus-value immobilière (hors résidence principale)', 'statut' => 'ok', 'rows' => [
    ['Taux', '19 % d\'impôt + 17,2 % de prélèvements sociaux', 'Prélèvements sociaux maintenus à 17,2 %.'],
    ['Abattement impôt (durée de détention)', '6 % par an de la 6e à la 21e année, 4 % la 22e', 'Exonération d\'impôt après 22 ans.'],
    ['Abattement prélèvements sociaux', '1,65 % par an de la 6e à la 21e année, 1,6 % la 22e, 9 % par an de la 23e à la 30e', 'Exonération des prélèvements sociaux après 30 ans.'],
    ['Taxe sur les plus-values élevées', '2 % à 6 % au-delà de 50 000 € de plus-value imposable', 'Barème détaillé dans le formulaire 2048-IMM-SD.'],
    ['Abattement exceptionnel', '70 % ou 60 % selon la zone, jusqu\'à 85 % avec logement social', 'Promesses de vente signées du 01/01/2024 au 31/12/2027.'],
    ['Amortissements de la location meublée non professionnelle', 'Réintégrés dans le calcul de la plus-value', 'Cessions à partir du 15/02/2025. Information issue d\'une source secondaire.', 'w'],
  ], 'sources' => [['Plus-value immobilière (service-public)', $SP . 'F10864'], ['Plus-values imposées (impots.gouv)', 'https://www.impots.gouv.fr/particulier/plus-values-imposees']]],
  ['titre' => 'Impôt sur la fortune immobilière (IFI) 2026', 'statut' => 'ok', 'rows' => [
    ['Seuil d\'imposition', '1 300 000 € de patrimoine immobilier net taxable au 01/01', 'L\'impôt se calcule à partir de 800 000 €.'],
    ['0 % jusqu\'à 800 000 €', '', ''],
    ['De 800 001 € à 1 300 000 €', '0,50 %', ''],
    ['De 1 300 001 € à 2 570 000 €', '0,70 %', ''],
    ['De 2 570 001 € à 5 000 000 €', '1 %', ''],
    ['De 5 000 001 € à 10 000 000 €', '1,25 %', ''],
    ['Au-delà de 10 000 000 €', '1,50 %', ''],
    ['Décote entre 1,3 M€ et 1,4 M€', '17 500 € − 1,25 % × patrimoine net taxable', ''],
    ['Plafonnement', 'Impôts totaux limités à 75 % des revenus mondiaux', 'Réservé aux résidents fiscaux français.'],
    ['Abattement de 30 % sur la résidence principale', 'Non retrouvé dans les sources consultées', 'À confirmer (article 973 du CGI, notice 2042-IFI).', 'w'],
  ], 'sources' => [['Calcul de l\'IFI (service-public)', $SP . 'F138'], ['Calcul de l\'IFI (impots.gouv)', 'https://www.impots.gouv.fr/particulier/calcul-de-lifi']]],
 ]],

['id' => 'transmission', 'titre' => 'Succession et donation', 'icon' => 'fa-people-roof',
 'intro' => 'Droits de mutation à titre gratuit : barèmes et abattements.',
 'cards' => [
  ['titre' => 'Barèmes des droits de succession', 'statut' => 'ok', 'rows' => [
    ['Ligne directe : jusqu\'à 8 072 €', '5 %', 'Part taxable après abattement.'],
    ['De 8 073 € à 12 109 €', '10 %', ''],
    ['De 12 110 € à 15 932 €', '15 %', ''],
    ['De 15 933 € à 552 324 €', '20 %', ''],
    ['De 552 325 € à 902 838 €', '30 %', ''],
    ['De 902 839 € à 1 805 677 €', '40 %', ''],
    ['Au-delà de 1 805 677 €', '45 %', ''],
    ['Frères et sœurs', '35 % jusqu\'à 24 430 €, 45 % au-delà', 'Exonération possible sous 3 conditions : célibataire, veuf, divorcé ou séparé ; plus de 50 ans ou infirme ; domicilié avec le défunt 5 ans.'],
    ['Parents jusqu\'au 4e degré (neveux et nièces…)', '55 %', ''],
    ['Au-delà du 4e degré ou sans lien de parenté', '60 %', 'Neveux et nièces par alliance : 60 %.'],
    ['Conjoint survivant et partenaire de Pacs', 'Exonérés', ''],
  ], 'sources' => [['Droits de succession (service-public)', $SP . 'F14198']]],
  ['titre' => 'Abattements en succession', 'statut' => 'ok', 'rows' => [
    ['Enfant, ascendant', '100 000 €', 'Par parent et par enfant.'],
    ['Petit-enfant', '1 594 € (100 000 € en représentation, partagé)', ''],
    ['Frère ou sœur', '15 932 €', ''],
    ['Neveu ou nièce', '7 967 € (15 932 € en représentation, partagé)', ''],
    ['Autre lien ou aucun lien', '1 594 €', ''],
    ['Personne handicapée', '159 325 €', 'Cumulable avec les autres abattements.'],
  ], 'sources' => [['Droits de succession (service-public)', $SP . 'F14198'], ['Abattements (BOFiP ENR-DMTG-10-50-20)', 'https://bofip.impots.gouv.fr/bofip/3369-PGP.html/identifiant=BOI-ENR-DMTG-10-50-20-20230524']]],
  ['titre' => 'Donations', 'statut' => 'warn', 'rows' => [
    ['Donation à un enfant', '100 000 € par parent', 'Renouvelable tous les 15 ans.'],
    ['Donation à un petit-enfant', '31 865 €', 'Mineur ou majeur.'],
    ['Don familial de somme d\'argent (art. 790 G du CGI)', '31 865 € tous les 15 ans', 'Donateur de moins de 80 ans, bénéficiaire majeur. Déclaration en ligne dans le mois suivant le don pour conserver l\'exonération. Cumulable avec les abattements de lien de parenté.'],
    ['Rappel fiscal des donations antérieures', '15 ans', 'Les donations de moins de 15 ans s\'ajoutent à la nouvelle opération pour le calcul des droits.'],
    ['Abattement entre époux ou partenaires', 'Non confirmé dans les sources consultées', 'La valeur de 80 724 € est couramment citée mais non retrouvée : à confirmer.', 'w'],
    ['Autres liens (frère, sœur, neveu, nièce, arrière-petit-enfant)', 'Non revérifié', 'Se reporter à la fiche service-public sur les droits de donation.', 'w'],
  ], 'sources' => [['Donner à ses enfants et petits-enfants (impots.gouv)', 'https://www.impots.gouv.fr/particulier/questions/que-puis-je-donner-mes-enfants-petits-enfants-sans-avoir-payer-de-droits'], ['Don d\'une somme d\'argent (service-public)', $SP . 'F36656'], ['Droits de donation (service-public)', $SP . 'F14203']]],
 ]],

['id' => 'conseil', 'titre' => 'Aide au conseil', 'icon' => 'fa-comments',
 'intro' => 'Repères pour orienter un entretien patrimonial. Ces éléments ne remplacent pas l\'analyse du dossier.',
 'cards' => [
  ['titre' => 'Quelle enveloppe pour quel objectif ?', 'statut' => 'ok', 'rows' => [
    ['Épargne de précaution', 'Livrets réglementés', 'Intérêts exonérés. Plafonds à vérifier dans le mémo.'],
    ['Projet à moyen terme, transmission', 'Assurance-vie', 'Avantage fiscal à partir de 8 ans et cadre successoral propre (art. 990 I).'],
    ['Actions sur le long terme', 'PEA', 'Exonération d\'impôt sur le revenu après 5 ans, prélèvements sociaux dus.'],
    ['Réduire l\'impôt avant la retraite', 'PER', 'Déduction à l\'entrée ; intérêt si le taux marginal d\'imposition d\'aujourd\'hui dépasse celui prévu à la sortie.'],
    ['Aider ses enfants', 'Donation / don familial', 'Utiliser les abattements de 15 ans en anticipant.'],
  ], 'sources' => []],
  ['titre' => 'Points de vigilance à l\'écrit comme à l\'oral', 'statut' => 'ok', 'rows' => [
    ['Taux 2026', 'Ne pas citer 30 % de PFU hors assurance-vie', 'Le taux global est 31,4 % pour la plupart des placements depuis le 01/01/2026.'],
    ['Devoir de conseil', 'Ces repères sont généraux', 'Ils ne tiennent pas compte de la situation personnelle du client.'],
    ['Source', 'Citer la source et sa date', 'Les barèmes évoluent chaque année par la loi de finances.'],
  ], 'sources' => []],
 ]],

],
// Divergences relevées entre sources lors du recoupement : transparence sur ce qui a été arbitré.
'divergences' => [
  ['Barème de l\'impôt sur le revenu', 'Certaines pages affichent encore 11 497 € / 29 315 € / 83 823 € / 180 294 € (revenus 2024). Retenu : 11 600 € / 29 579 € / 84 577 € / 181 917 € (service-public, revenus 2025). Contrôle par le calcul : 11 497 € × 1,009 ≈ 11 600 €, cohérent avec l\'indexation de 0,9 %.'],
  ['PFU', 'Des pages ne mentionnent que 30 %. Retenu : 31,4 % (18,6 % de prélèvements sociaux) sauf assurance-vie, PEL, CEL (17,2 %).'],
  ['PER et CSG', 'Des sites secondaires hésitaient pour le PER assurantiel. Retenu : 18,6 %, selon la fiche service-public du PER (12/10/2026).'],
  ['Assurance-vie et CSG', 'Un amendement du gouvernement a prévu l\'exclusion, confirmée par la brochure fiscale 2026 et service-public (17,2 %). Une page tierce affichait 18,6 % dans un tableau : écartée.'],
  ['PEL ouvert depuis 2018', 'Service-public : 17,2 %. Brochure fiscale : 18,6 %. Non tranché, ligne signalée à confirmer.'],
  ['Taux du Livret A', 'Plusieurs sources secondaires divergent. Le guide ne fige pas ce taux.'],
  ['Plafond PER', 'Des pages affichent 37 094 € / 4 637 € (2025). Retenu : 37 680 € / 4 710 € (versements 2026, service-public).'],
  ['Abattement du conjoint en donation (80 724 €) et abattement IFI de 30 % sur la résidence principale', 'Non retrouvés dans les sources consultées : non affirmés, signalés à confirmer.'],
],
];
