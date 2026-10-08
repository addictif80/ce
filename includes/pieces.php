<?php
/**
 * Modèles de listes de pièces justificatives (gérés par l'administrateur, consultés dans /tools et dans le portail).
 * Une ligne par pièce, au format « Groupe ; Libellé ; Détail ; Profil », profil ∈ tous | salarie | independant | retraite.
 */
function piecesProfils() {
    return ['tous' => 'Tous', 'salarie' => 'Salarié', 'independant' => 'Indépendant / professionnel', 'retraite' => 'Retraité'];
}

function piecesEnsureSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS pieces_modeles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(150) NOT NULL,
        ordre INT NOT NULL DEFAULT 0,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        lignes MEDIUMTEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ((int)$db->query("SELECT COUNT(*) FROM pieces_modeles")->fetchColumn() === 0) {
        $ins = $db->prepare("INSERT INTO pieces_modeles (nom, ordre, lignes) VALUES (?, ?, ?)");
        foreach (piecesModelesParDefaut() as $i => [$nom, $lignes]) $ins->execute([$nom, $i + 1, trim($lignes)]);
    }
}

/** Modèles fournis au premier usage : à adapter par l'administrateur. */
function piecesModelesParDefaut() {
    return [
        ['Crédit immobilier', <<<TXT
Identité;Pièce d'identité en cours de validité;Recto-verso, pour chaque emprunteur;tous
Identité;Livret de famille ou acte de naissance;Si enfants à charge;tous
Identité;Justificatif de domicile de moins de 3 mois;Facture ou quittance;tous
Revenus;3 derniers bulletins de salaire;;salarie
Revenus;Contrat de travail ou attestation de l'employeur;Mentionnant la date d'embauche et la fin de période d'essai;salarie
Revenus;3 derniers bilans et liasses fiscales;;independant
Revenus;Attestation de régularité sociale et fiscale;;independant
Revenus;Derniers titres de pension ou notifications de retraite;;retraite
Revenus;2 derniers avis d'imposition;Tous les foyers fiscaux concernés;tous
Comptes et patrimoine;3 derniers relevés des comptes bancaires détenus ailleurs;;tous
Comptes et patrimoine;Relevés d'épargne et de placements;Justification de l'apport;tous
Comptes et patrimoine;Tableaux d'amortissement des crédits en cours;;tous
Projet;Compromis ou promesse de vente signé;;tous
Projet;Diagnostics immobiliers dont le DPE;;tous
Projet;Dernière taxe foncière et charges de copropriété;Bien ancien;tous
Projet;Devis des travaux;Si travaux financés;tous
Projet;Permis de construire, plans et contrat de construction;Construction;tous
Assurance;Questionnaire de santé / de risque;Pour chaque emprunteur;tous
TXT],
        ['Crédit à la consommation', <<<TXT
Identité;Pièce d'identité en cours de validité;Recto-verso;tous
Identité;Justificatif de domicile de moins de 3 mois;;tous
Revenus;3 derniers bulletins de salaire;;salarie
Revenus;Dernier bilan ou avis d'imposition;;independant
Revenus;Dernier titre de pension;;retraite
Revenus;Dernier avis d'imposition;;tous
Comptes;3 derniers relevés de comptes bancaires;Si compte dans une autre banque;tous
Charges;Justificatif de loyer ou de crédit immobilier en cours;;tous
Projet;Devis, bon de commande ou facture pro forma;;tous
TXT],
        ['Ouverture de compte', <<<TXT
Identité;Pièce d'identité en cours de validité;Recto-verso;tous
Identité;Justificatif de domicile de moins de 3 mois;;tous
Activité;Dernier bulletin de salaire;;salarie
Activité;Extrait Kbis ou avis de situation SIRENE;;independant
Activité;Dernier titre de pension;;retraite
Mineur;Livret de famille;Pour un compte de mineur;tous
Mineur;Pièce d'identité du représentant légal;Pour un compte de mineur;tous
TXT],
        ['Succession', <<<TXT
Défunt;Acte de décès;;tous
Défunt;Livret de famille;;tous
Défunt;Testament, donation ou contrat de mariage;Si existant;tous
Héritiers;Pièce d'identité de chaque héritier;;tous
Héritiers;Attestation de dévolution successorale ou acte de notoriété;Délivré par le notaire;tous
Patrimoine;Liste des comptes et produits détenus;;tous
Patrimoine;Derniers relevés de comptes à la date du décès;;tous
TXT],
    ];
}

/** Lignes texte -> liste d'items [groupe, libelle, detail, profil] (lignes vides ou mal formées ignorées) */
function piecesParse($lignes) {
    $profils = piecesProfils();
    $items = [];
    foreach (preg_split('/\R/', (string)$lignes) as $l) {
        $p = array_map('trim', explode(';', $l));
        if (count($p) < 2 || $p[1] === '') continue;
        $prof = $p[3] ?? 'tous';
        $items[] = ['groupe' => $p[0] !== '' ? $p[0] : 'Divers', 'libelle' => $p[1], 'detail' => $p[2] ?? '', 'profil' => isset($profils[$prof]) ? $prof : 'tous'];
    }
    return $items;
}

function piecesModelesActifs() {
    piecesEnsureSchema();
    $out = [];
    foreach (getDB()->query("SELECT id, nom, lignes FROM pieces_modeles WHERE actif = 1 ORDER BY ordre, nom")->fetchAll() as $m) {
        $out[] = ['id' => (int)$m['id'], 'nom' => $m['nom'], 'items' => piecesParse($m['lignes'])];
    }
    return $out;
}
