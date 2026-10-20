<?php
/**
 * Moteur de suggestions de prochaine action / produit.
 *
 * Fonctionne d'abord par RÈGLES (déterministes, à partir des données du dossier),
 * puis — si l'aide IA est activée — l'IA reformule et complète ces suggestions.
 * Ainsi le module reste utile même sans IA.
 */
require_once __DIR__ . '/functions.php';

/** Catalogue produits par domaine, utilisé pour rattacher un besoin à une offre. */
function getProduitsCatalogue() {
    return [
        'Banca' => [
            'Offre groupée compte à composer (CDD + carte)',
            'Izicarte / carte haut de gamme',
            'Assurance des moyens de paiement',
            'Service de banque à distance',
        ],
        'Epargne' => [
            'Livret A / LDDS',
            'Plan épargne logement (PEL)',
            'Compte sur livret',
            'Épargne programmée',
        ],
        'Placement' => [
            'Assurance-vie / assurance-vie multisupport',
            'Plan épargne retraite (PER)',
            'Parts sociales',
            'PEA',
        ],
        'Credit' => [
            'Prêt personnel',
            'Crédit immobilier',
            'Rachat / regroupement de crédits',
            'Prêt travaux',
        ],
        'Assurance' => [
            'Assurance emprunteur',
            'Prévoyance / garantie des accidents de la vie',
            'Assurance habitation',
            'Complémentaire santé',
        ],
    ];
}

/** Renvoie la liste de produits associée à une catégorie (tolérante aux accents). */
function getProduitsPourCategorie($categorie) {
    $catalogue = getProduitsCatalogue();
    $normalize = function ($s) {
        $s = mb_strtolower((string)$s, 'UTF-8');
        return strtr($s, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ç' => 'c']);
    };
    $cible = $normalize($categorie);
    foreach ($catalogue as $cat => $produits) {
        if ($normalize($cat) === $cible) return $produits;
    }
    return [];
}

/** Fabrique une suggestion normalisée. */
function suggestionItem($titre, $detail, $produit = '', $priorite = 'normale', $source = 'regle') {
    return [
        'titre' => $titre,
        'detail' => $detail,
        'produit' => $produit,
        'priorite' => in_array($priorite, ['haute', 'normale', 'basse'], true) ? $priorite : 'normale',
        'source' => $source,
    ];
}

/**
 * Applique les règles métier au dossier et retourne la liste des suggestions.
 */
function getSuggestionsRegles($fiche) {
    $out = [];
    $now = time();
    $s = $fiche['stats'];

    // 1. Instances en retard
    foreach ($fiche['instances'] as $i) {
        if (($i['statut'] ?? '') === 'a_faire' && !empty($i['date_echeance']) && strtotime($i['date_echeance']) < $now) {
            $jours = (int)floor(($now - strtotime($i['date_echeance'])) / 86400);
            $out[] = suggestionItem(
                'Traiter une instance en retard',
                'Instance « ' . excerpt((string)($i['details'] ?? ''), 90) . ' » échue depuis ' . $jours . ' jour(s).',
                '', 'haute'
            );
        }
    }

    // 2. Demandes clients ouvertes
    foreach ($fiche['demandes'] as $d) {
        if ((int)($d['traitee'] ?? 0) === 0) {
            $age = !empty($d['date_ajout']) ? (int)floor(($now - strtotime($d['date_ajout'])) / 86400) : 0;
            $out[] = suggestionItem(
                $age >= 7 ? 'Relancer une demande client en souffrance' : 'Suivre la demande client en cours',
                'Demande « ' . excerpt((string)($d['details_demande'] ?? ''), 90) . ' » du ' . ($d['date_ajout'] ?? '?') .
                ' (' . $age . ' j), service : ' . (($d['service'] ?? '') !== '' ? $d['service'] : 'non précisé') . '.',
                '', $age >= 7 ? 'haute' : 'normale'
            );
        }
    }

    // 3. Rappels en attente
    foreach ($fiche['rappels'] as $r) {
        if ((int)($r['traitee'] ?? 0) === 0) {
            $age = !empty($r['date_ajout']) ? (int)floor(($now - strtotime($r['date_ajout'])) / 86400) : 0;
            $out[] = suggestionItem(
                'Effectuer le rappel demandé',
                'Motif : ' . excerpt((string)($r['motif'] ?? ''), 90) . ' (demandé le ' . ($r['date_ajout'] ?? '?') . ', ' . $age . ' j).',
                '', $age >= 3 ? 'haute' : 'normale'
            );
        }
    }

    // 4. Signatures en cours
    foreach ($fiche['signatures'] as $sg) {
        if (($sg['statut'] ?? '') === 'en_cours') {
            $age = !empty($sg['date_envoi']) ? (int)floor(($now - strtotime($sg['date_envoi'])) / 86400) : 0;
            $out[] = suggestionItem(
                'Relancer la signature',
                'Dossier envoyé le ' . ($sg['date_envoi'] ?? '?') . ' — en attente depuis ' . $age . ' jour(s).',
                '', $age >= 7 ? 'haute' : 'normale'
            );
        }
    }

    // 5. Mobilités en cours
    foreach ($fiche['mobilites'] as $m) {
        $etape = $m['etape'] ?? '';
        if ($etape !== '' && $etape !== 'termine') {
            $out[] = suggestionItem(
                'Faire avancer la mobilité bancaire',
                'Étape en cours : ' . $etape . '. Banque de départ : ' . (($m['banque_depart'] ?? '') !== '' ? $m['banque_depart'] : '—') . '.',
                'Offre groupée compte à composer (CDD + carte)', 'normale'
            );
        }
    }

    // 6. Crédit immobilier : offre non signée
    foreach ($fiche['credit_immo'] as $c) {
        if ((int)($c['suivi_offre_signee'] ?? 0) === 0) {
            $out[] = suggestionItem(
                'Suivre le dossier de crédit immobilier',
                (($c['type_credit'] ?? '') !== '' ? $c['type_credit'] : 'Crédit immobilier') .
                ' — offre ' . ((int)($c['suivi_edition_offres'] ?? 0) ? 'éditée' : 'non éditée') .
                ', signature ' . ((int)($c['suivi_envoi_signature'] ?? 0) ? 'envoyée' : 'à envoyer') . '.',
                'Assurance emprunteur', 'haute'
            );
        }
    }

    // 7. Intérêts exprimés : proposer le produit correspondant
    foreach ($fiche['interets'] as $it) {
        $cats = getProduitsPourCategorie($it['categorie'] ?? '');
        $produit = $cats ? $cats[0] : '';
        $out[] = suggestionItem(
            'Répondre à un intérêt exprimé',
            'Le client s\'est montré intéressé par « ' . excerpt((string)($it['interet'] ?? ''), 90) . ' » (' . ($it['categorie'] ?? '') . ').',
            $produit, 'haute'
        );
    }

    // 8. Aucun contact récent : proposer un rendez-vous
    $jours = null;
    if (!empty($s['dernier_contact'])) $jours = (int)floor(($now - strtotime($s['dernier_contact'])) / 86400);
    if ($jours === null) {
        $out[] = suggestionItem(
            'Programmer un premier contact',
            'Aucun contact tracé pour ce client. Proposer un rendez-vous de découverte.',
            'Offre groupée compte à composer (CDD + carte)', 'haute'
        );
    } elseif ($jours >= 60) {
        $out[] = suggestionItem(
            'Reprendre contact',
            'Dernier contact il y a ' . $jours . ' jours. Une prise de contact régulière renforce la relation.',
            '', 'normale'
        );
    }

    // 9. Absence d'épargne/placement connue alors que des ventes Banca existent
    if (empty($fiche['interets']) && empty($fiche['envois'])) {
        $out[] = suggestionItem(
            'Proposer un bilan d\'équipement',
            'Peu de produits d\'épargne ou de placement identifiés : un bilan patrimonial peut révéler des opportunités.',
            'Assurance-vie / assurance-vie multisupport', 'basse'
        );
    }

    // Déduplication (titre + détail) et tri par priorité
    $seen = [];
    $unique = [];
    foreach ($out as $sg) {
        $k = mb_strtolower($sg['titre'] . '|' . $sg['detail']);
        if (isset($seen[$k])) continue;
        $seen[$k] = true;
        $unique[] = $sg;
    }
    $ordre = ['haute' => 0, 'normale' => 1, 'basse' => 2];
    usort($unique, function ($a, $b) use ($ordre) {
        return ($ordre[$a['priorite']] ?? 1) <=> ($ordre[$b['priorite']] ?? 1);
    });
    return array_slice($unique, 0, 12);
}
