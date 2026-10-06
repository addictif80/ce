<?php
/**
 * Vérification de certification RGE d'une entreprise, par nom, SIREN ou SIRET.
 * Source : open data ADEME « Liste des entreprises RGE » (une ligne par entreprise et par qualification).
 * Accessible aux utilisateurs connectés et via /tools (outil « rge »).
 *   ?q=<nom | SIREN (9 chiffres) | SIRET (14 chiffres)>
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
requirePublicTool('rge', true);

function rgeFail($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

if (!toolsVisitorIsLoggedIn() && !toolsRateLimitHit('rge', 60, 60)) rgeFail('Trop de requêtes, réessayez dans une minute.', 429);

$q = trim($_GET['q'] ?? '');
$digits = preg_replace('/[\s.\-]/', '', $q);
$params = ['size' => 300, 'sort' => 'nom_entreprise'];
if ($digits !== '' && ctype_digit($digits)) {
    if (strlen($digits) === 14) $params['qs'] = 'siret:"' . $digits . '"';
    elseif (strlen($digits) === 9) $params['qs'] = 'siret:' . $digits . '*';
    else rgeFail('Un SIREN compte 9 chiffres et un SIRET 14 chiffres.');
} else {
    if (mb_strlen($q) < 3 || mb_strlen($q) > 100) rgeFail('Saisissez au moins 3 caractères du nom de l\'entreprise.');
    $params = ['q' => $q, 'q_fields' => 'nom_entreprise', 'q_mode' => 'complete', 'size' => 300];
}
$params['select'] = 'siret,nom_entreprise,adresse,code_postal,commune,telephone,email,site_internet,code_qualification,nom_qualification,url_qualification,nom_certificat,domaine,meta_domaine,organisme,particulier,lien_date_debut,lien_date_fin';

function rgeFetch($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = $body ? json_decode($body, true) : null;
    return ($code === 200 && is_array($data)) ? $data : null;
}

const RGE_DATASET = 'liste-des-entreprises-rge-2';
$data = rgeFetch('https://data.ademe.fr/data-fair/api/v1/datasets/' . RGE_DATASET . '/lines?' . http_build_query($params));
if ($data === null || !isset($data['results'])) rgeFail("L'API de l'ADEME est momentanément indisponible.", 502);

// Date de mise à jour du jeu de données (mise en cache 6 h)
$cache = sys_get_temp_dir() . '/rge_meta.json';
$meta = (is_file($cache) && filemtime($cache) > time() - 21600) ? json_decode(file_get_contents($cache), true) : null;
if (!$meta) {
    $info = rgeFetch('https://data.ademe.fr/data-fair/api/v1/datasets/' . RGE_DATASET);
    $meta = ['updated' => $info['dataUpdatedAt'] ?? null];
    if ($meta['updated']) @file_put_contents($cache, json_encode($meta));
}

// Regroupement par établissement (SIRET)
$today = date('Y-m-d');
$companies = [];
foreach ($data['results'] as $r) {
    $siret = $r['siret'] ?? '';
    if ($siret === '') continue;
    if (!isset($companies[$siret])) {
        $companies[$siret] = [
            'siret' => $siret, 'siren' => substr($siret, 0, 9), 'nom' => $r['nom_entreprise'] ?? '',
            'adresse' => trim(($r['adresse'] ?? '') . ' ' . ($r['code_postal'] ?? '') . ' ' . ($r['commune'] ?? '')),
            'telephone' => $r['telephone'] ?? '', 'email' => $r['email'] ?? '', 'site' => $r['site_internet'] ?? '',
            'qualifications' => [], 'valide' => false,
        ];
    }
    $debut = $r['lien_date_debut'] ?? null; $fin = $r['lien_date_fin'] ?? null;
    $valide = (!$debut || $debut <= $today) && (!$fin || $fin >= $today);
    if ($valide) $companies[$siret]['valide'] = true;
    $companies[$siret]['qualifications'][] = [
        'domaine' => $r['domaine'] ?? '', 'meta_domaine' => $r['meta_domaine'] ?? '', 'qualification' => $r['nom_qualification'] ?? '',
        'code' => $r['code_qualification'] ?? '', 'organisme' => $r['organisme'] ?? '', 'certificat' => $r['nom_certificat'] ?? '',
        'debut' => $debut, 'fin' => $fin, 'valide' => $valide, 'url' => $r['url_qualification'] ?? '', 'particulier' => $r['particulier'] ?? null,
    ];
}
foreach ($companies as &$c) {
    usort($c['qualifications'], fn($a, $b) => [$b['valide'], $a['domaine']] <=> [$a['valide'], $b['domaine']]);
}
unset($c);

echo json_encode([
    'companies' => array_slice(array_values($companies), 0, 30),
    'nb_companies' => count($companies),
    'truncated' => count($data['results']) >= 300 || count($companies) > 30,
    'updated' => $meta['updated'] ?? null,
], JSON_UNESCAPED_UNICODE);
