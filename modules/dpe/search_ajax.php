<?php
/**
 * Recherche de DPE par adresse via l'API open data de l'ADEME (base DPE logements existants, depuis juillet 2021).
 * Entrée : ?q=<adresse complète, ex. libellé BAN>
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 5) {
    echo json_encode(['error' => 'Adresse trop courte.']);
    exit;
}

function dpeNormalize($s) {
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','œ'=>'oe','æ'=>'ae','ÿ'=>'y']);
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
}

$fields = 'numero_dpe,adresse_ban,code_postal_ban,nom_commune_ban,complement_adresse_logement,complement_adresse_batiment,'
    . 'etiquette_dpe,etiquette_ges,date_etablissement_dpe,date_fin_validite_dpe,type_batiment,surface_habitable_logement,'
    . 'annee_construction,periode_construction,conso_5_usages_par_m2_ep,emission_ges_5_usages_par_m2,cout_total_5_usages,'
    . 'type_energie_principale_chauffage,numero_etage_appartement,_geopoint';

$url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?' . http_build_query([
    'q'        => $q,
    'q_fields' => 'adresse_ban',
    'q_mode'   => 'complete',
    'size'     => 100,
    'sort'     => '-date_etablissement_dpe',
    'select'   => $fields,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = $body ? json_decode($body, true) : null;
if ($code !== 200 || !is_array($data) || !isset($data['results'])) {
    http_response_code(502);
    echo json_encode(['error' => "L'API de l'ADEME est momentanément indisponible."]);
    exit;
}

// Séparer les correspondances exactes (même adresse BAN) des adresses approchantes
$target = dpeNormalize($q);
$exact = [];
$approx = [];
foreach ($data['results'] as $r) {
    unset($r['_score'], $r['_id'], $r['_i'], $r['_rand']);
    if (dpeNormalize($r['adresse_ban'] ?? '') === $target) $exact[] = $r;
    else $approx[] = $r;
}

echo json_encode(['exact' => $exact, 'approx' => $approx, 'total' => $data['total'] ?? count($data['results'])]);
