<?php
/**
 * Passerelle « simulation -> dossier crédit » : transforme une simulation enregistrée (capacité d'emprunt, frais de notaire, PTZ)
 * en données de formulaire pour ciCollect(). Renvoie null si la simulation n'existe pas ou n'appartient pas à l'utilisateur.
 */
function ciFormFromSimulation($db, $userId, $simId) {
    $st = $db->prepare("SELECT * FROM simulations WHERE id = ? AND user_id = ? AND type IN ('capacite', 'notaire', 'ptz')");
    $st->execute([$simId, $userId]);
    $sim = $st->fetch(PDO::FETCH_ASSOC);
    if (!$sim) return null;
    $pa = json_decode($sim['params'] ?? '{}', true) ?: [];
    $re = json_decode($sim['resultat'] ?? '{}', true) ?: [];
    $f = fn($k) => (float)($pa[$k] ?? 0);
    $nom = mb_substr($sim['nom'], 0, 100);

    $p = ['numero_personne' => $nom, 'type_client' => 'Particulier', 'workflow_status' => 'etude'];
    $emp = ['nom' => $nom, 'num_personne' => '', 'bdf' => '', 'drc' => '', 'topcc' => '', 'rfr' => 0, 'revenus' => [], 'charges' => [], 'epargne' => []];

    if ($sim['type'] === 'capacite') {
        if ($f('rev') > 0) $emp['revenus'][] = ['intitule' => 'Revenus du foyer', 'montant' => $f('rev'), 'periodicite' => 'mensuelle'];
        if ($f('chg') > 0) $emp['charges'][] = ['intitule' => 'Crédits en cours', 'montant' => $f('chg')];
        if ($f('loyer') > 0) $emp['charges'][] = ['intitule' => 'Loyer conservé', 'montant' => $f('loyer')];
        $capital = (float)($re['capital'] ?? 0);
        $prix = (float)($re['budget'] ?? 0);
        $p['nb_personnes_foyer'] = (int)$f('pers');
        $p['montant_acquisition'] = round($prix);
        $p['frais_notaire'] = round($prix * $f('not') / 100);
        $p['apport'] = $f('apport');
        if ($f('gar') > 0) $p['garantie_montant'] = round($capital * $f('gar') / 100, 2);
        $p['lignes_credit_json'] = json_encode([['libelle' => 'Prêt principal', 'montant' => $capital, 'duree' => (int)round($f('duree') * 12), 'taux' => $f('taux'), 'frais_dossier' => $f('fd'), 'doublissimo' => false]]);
        if ($f('ass') > 0) $p['ade_json'] = json_encode([['emp' => 0, 'ligne' => 0, 'taux' => $f('ass'), 'base' => 'CI', 'quotite' => 100, 'couverture' => []]]);
    } elseif ($sim['type'] === 'notaire') {
        $p['montant_acquisition'] = $f('prix');
        $p['dont_mobilier_financable'] = $f('mob');
        $p['frais_notaire'] = round((float)($re['frais'] ?? 0));
        if (($pa['type'] ?? '') === 'neuf') $p['type_projet'] = 'NEUF_VEFA';
    } else { // ptz
        $emp['rfr'] = $f('rfr');
        $p['nb_personnes_foyer'] = (int)$f('pers');
        $p['montant_acquisition'] = $f('cout');
        if (!empty($pa['primo'])) $p['primo_accedant_statut'] = 'OUI';
        $p['type_projet'] = ['collectif_neuf' => 'NEUF_VEFA', 'ancien_travaux' => 'ANCIEN_AVEC_TRAVAUX'][$pa['type'] ?? ''] ?? '';
        if (!empty($re['eligible']) && (float)($re['ptz'] ?? 0) > 0) {
            $p['ptz_actif'] = 1; $p['ptz_montant'] = (float)$re['ptz']; $p['ptz_duree'] = (int)($re['duree'] ?? 0);
        }
    }
    $p['emprunteurs_json'] = json_encode([$emp], JSON_UNESCAPED_UNICODE);
    return $p;
}
