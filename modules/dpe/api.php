<?php
/**
 * API publique (sans connexion) de recherche de DPE, proxy vers l'open data de l'ADEME.
 *   ?mode=address&q=<adresse>[&sources=new,old]  -> DPE d'une adresse
 *   ?mode=map&bbox=lonMin,latMin,lonMax,latMax[&sources=new,old] -> DPE dans une zone (carte)
 * Sources : new = logements depuis juillet 2021 (dpe03existant), old = avant juillet 2021 (dpe-france).
 */
header('Content-Type: application/json; charset=utf-8');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

// Limitation simple par IP (60 requêtes / minute) : l'endpoint étant public
$rlFile = sys_get_temp_dir() . '/dpe_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$now = time();
$hits = is_file($rlFile) ? array_filter(array_map('intval', file($rlFile, FILE_IGNORE_NEW_LINES)), fn($t) => $t > $now - 60) : [];
if (count($hits) >= 60) fail('Trop de requêtes, réessayez dans une minute.', 429);
$hits[] = $now;
@file_put_contents($rlFile, implode("\n", $hits));

function dpeNormalize($s) {
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','œ'=>'oe','æ'=>'ae','ÿ'=>'y']);
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
}

function ademe($dataset, array $params) {
    $url = "https://data.ademe.fr/data-fair/api/v1/datasets/$dataset/lines?" . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = $body ? json_decode($body, true) : null;
    if ($code !== 200 || !is_array($data) || !isset($data['results'])) return null;
    return $data;
}

// Description des deux jeux de données (champs ADEME -> format commun)
const NEW_FIELDS = 'numero_dpe,adresse_ban,complement_adresse_logement,complement_adresse_batiment,etiquette_dpe,etiquette_ges,date_etablissement_dpe,date_fin_validite_dpe,type_batiment,surface_habitable_logement,annee_construction,periode_construction,conso_5_usages_par_m2_ep,emission_ges_5_usages_par_m2,cout_total_5_usages,type_energie_principale_chauffage,numero_etage_appartement,_geopoint';
const OLD_FIELDS = 'numero_dpe,geo_adresse,date_etablissement_dpe,classe_consommation_energie,classe_estimation_ges,consommation_energie,estimation_ges,surface_thermique_lot,annee_construction,tr002_type_batiment_description,_geopoint';

function mapNew($r) {
    return [
        'source' => 'new', 'numero_dpe' => $r['numero_dpe'] ?? '', 'adresse' => $r['adresse_ban'] ?? '',
        'complement' => implode(' – ', array_filter([$r['complement_adresse_logement'] ?? '', $r['complement_adresse_batiment'] ?? ''])),
        'date' => $r['date_etablissement_dpe'] ?? null, 'validite' => $r['date_fin_validite_dpe'] ?? null,
        'etiquette_dpe' => $r['etiquette_dpe'] ?? null, 'etiquette_ges' => $r['etiquette_ges'] ?? null,
        'type' => $r['type_batiment'] ?? '', 'etage' => $r['numero_etage_appartement'] ?? null,
        'surface' => $r['surface_habitable_logement'] ?? null,
        'conso' => $r['conso_5_usages_par_m2_ep'] ?? null, 'ges' => $r['emission_ges_5_usages_par_m2'] ?? null,
        'cout' => $r['cout_total_5_usages'] ?? null, 'energie' => $r['type_energie_principale_chauffage'] ?? '',
        'construction' => $r['annee_construction'] ?? ($r['periode_construction'] ?? ''),
        'geo' => $r['_geopoint'] ?? null,
    ];
}
function mapOld($r) {
    $cls = fn($v) => ($v && preg_match('/^[A-G]$/', $v)) ? $v : null; // "N" = non classé
    return [
        'source' => 'old', 'numero_dpe' => $r['numero_dpe'] ?? '', 'adresse' => $r['geo_adresse'] ?? '',
        'complement' => '', 'date' => $r['date_etablissement_dpe'] ?? null, 'validite' => null,
        'etiquette_dpe' => $cls($r['classe_consommation_energie'] ?? null), 'etiquette_ges' => $cls($r['classe_estimation_ges'] ?? null),
        'type' => $r['tr002_type_batiment_description'] ?? '', 'etage' => null,
        'surface' => $r['surface_thermique_lot'] ?? null,
        'conso' => !empty($r['consommation_energie']) ? $r['consommation_energie'] : null,
        'ges' => !empty($r['estimation_ges']) ? $r['estimation_ges'] : null,
        'cout' => null, 'energie' => '', 'construction' => $r['annee_construction'] ?? '',
        'geo' => $r['_geopoint'] ?? null,
    ];
}

$sources = array_intersect(explode(',', $_GET['sources'] ?? 'new,old'), ['new', 'old']);
if (!$sources) fail('Source inconnue.');
$mode = $_GET['mode'] ?? 'address';
$all = [];
$errors = 0;

if ($mode === 'address') {
    $q = trim($_GET['q'] ?? '');
    if (mb_strlen($q) < 5 || mb_strlen($q) > 200) fail('Adresse invalide.');
    foreach ($sources as $s) {
        $new = $s === 'new';
        $data = ademe($new ? 'dpe03existant' : 'dpe-france', [
            'q' => $q, 'q_fields' => $new ? 'adresse_ban' : 'geo_adresse', 'q_mode' => 'complete',
            'size' => 100, 'sort' => '-date_etablissement_dpe', 'select' => $new ? NEW_FIELDS : OLD_FIELDS,
        ]);
        if ($data === null) { $errors++; continue; }
        foreach ($data['results'] as $r) $all[] = $new ? mapNew($r) : mapOld($r);
    }
    if ($errors === count($sources)) fail("L'API de l'ADEME est momentanément indisponible.", 502);

    $target = dpeNormalize($q);
    $exact = $approx = [];
    foreach ($all as $r) {
        if (dpeNormalize($r['adresse']) === $target) $exact[] = $r; else $approx[] = $r;
    }
    $byDate = fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? '');
    usort($exact, $byDate);
    usort($approx, $byDate);
    echo json_encode(['exact' => $exact, 'approx' => $approx, 'partial' => $errors > 0]);
    exit;
}

if ($mode === 'map') {
    if (!preg_match('/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/', $_GET['bbox'] ?? '')) fail('Zone invalide.');
    [$w, $s_, $e, $n] = array_map('floatval', explode(',', $_GET['bbox']));
    if (abs($e - $w) > 0.05 || abs($n - $s_) > 0.05) fail('Zone trop étendue : zoomez sur la carte.');
    $bbox = "$w,$s_,$e,$n";
    foreach ($sources as $s) {
        $new = $s === 'new';
        $data = ademe($new ? 'dpe03existant' : 'dpe-france', [
            'bbox' => $bbox, 'size' => 500, 'sort' => '-date_etablissement_dpe', 'select' => $new ? NEW_FIELDS : OLD_FIELDS,
        ]);
        if ($data === null) { $errors++; continue; }
        foreach ($data['results'] as $r) $all[] = $new ? mapNew($r) : mapOld($r);
    }
    if ($errors === count($sources)) fail("L'API de l'ADEME est momentanément indisponible.", 502);

    // Un marqueur par point géographique (un immeuble regroupe souvent plusieurs DPE)
    $groups = [];
    foreach ($all as $r) {
        if (!$r['geo']) continue;
        $groups[$r['geo']][] = $r;
    }
    $out = [];
    foreach ($groups as $geo => $rows) {
        usort($rows, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        [$lat, $lon] = array_map('floatval', explode(',', $geo));
        $out[] = ['lat' => $lat, 'lon' => $lon, 'adresse' => $rows[0]['adresse'], 'count' => count($rows), 'dpe' => array_slice($rows, 0, 15)];
    }
    echo json_encode(['points' => $out, 'truncated' => count($all) >= 500, 'partial' => $errors > 0]);
    exit;
}

fail('Mode inconnu.');
