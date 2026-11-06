<?php
/**
 * Génère le fichier HTML autonome « Outils conseiller » : tous les outils de /tools dans UN seul fichier, CSS, scripts et polices
 * embarqués, sans serveur ni base de données (données réglementaires figées au moment de la génération).
 *
 *   php scripts/build_standalone.php [--out=standalone/outils-conseiller.html] [--feedback-to=adresse@exemple.fr]
 *
 * Lancé sur le serveur du portail, il embarque les barèmes validés en administration, la liste des agences et, s'il y en a,
 * les procédures, codes et contacts publiés. Sans base de données, il utilise les valeurs par défaut (barèmes « à confirmer »).
 * Les outils qui exigent un serveur (assistant IA, recherche service-public.fr) ne sont pas inclus.
 */
chdir(dirname(__DIR__));
define('TOOLS_STANDALONE', 1);

// ---------- mode enfant : rend une page d'outil et l'écrit sur la sortie standard (un processus par outil : aucun état partagé) ----------
if (($argv[1] ?? '') === '--zonage') { // export du zonage A/B/C (JSON) si la base en contient
    ini_set('display_errors', '0'); error_reporting(0);
    function getDB() {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = getenv('CE_DB_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, getenv('CE_DB_USER') !== false ? getenv('CE_DB_USER') : DB_USER, getenv('CE_DB_PASS') !== false ? getenv('CE_DB_PASS') : DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 3]);
        }
        return $pdo;
    }
    try {
        require_once 'includes/config.php'; require_once 'includes/functions.php'; require_once 'includes/zonage.php';
        $st = zonageStats(); if ($st['count'] < 1) exit(0);
        $deps = zonageDepartements(); $com = [];
        foreach ($deps as [$d]) $com[$d] = zonageCommunes($d);
        echo json_encode(['count' => $st['count'], 'date' => $st['date'], 'libelle' => $st['libelle'], 'departements' => $deps, 'communes' => $com], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {}
    exit(0);
}
if (($argv[1] ?? '') === '--render') {
    $file = $argv[2] ?? '';
    $_SERVER += ['SCRIPT_NAME' => '/tools/' . basename($file), 'REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/tools/' . basename($file)];
    ini_set('display_errors', '0'); error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
    chdir(dirname($file));
    register_shutdown_function(function () { $e = error_get_last(); if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) fwrite(STDERR, "ERREUR PHP : {$e['message']} ({$e['file']}:{$e['line']})\n"); });
    // base de données : utilisée si elle répond (barèmes validés, agences, procédures…), sinon exception interceptée par les outils (valeurs par défaut)
    function getDB() {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $dsn = getenv('CE_DB_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, getenv('CE_DB_USER') !== false ? getenv('CE_DB_USER') : DB_USER, getenv('CE_DB_PASS') !== false ? getenv('CE_DB_PASS') : DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 3]);
                try { $pdo->exec("SET time_zone = 'Europe/Paris'"); } catch (PDOException $e) {} // fuseaux non chargés sur certains serveurs : sans incidence ici
            } catch (PDOException $e) { throw new RuntimeException('Base de données indisponible : ' . $e->getMessage()); }
        }
        return $pdo;
    }
    include $file;
    exit;
}

function opt($name, $default = null) { global $argv; foreach ($argv as $a) if (strpos($a, "--$name=") === 0) return substr($a, strlen($name) + 3); return $default; }
$out = opt('out', 'standalone/outils-conseiller.html');
$feedbackTo = opt('feedback-to', '');
$only = opt('only') ? explode(',', opt('only')) : null;
$cache = __DIR__ . '/../standalone/.cache'; if (!is_dir($cache)) @mkdir($cache, 0775, true);
if (!is_dir($cache) || !is_writable($cache)) { $cache = sys_get_temp_dir() . '/outils_standalone_cache'; if (!is_dir($cache)) mkdir($cache, 0775, true); } // dossier du portail non inscriptible : cache temporaire

function say($m) { fwrite(STDERR, $m . "\n"); }
function fetchCached($url, $cache) {
    $f = $cache . '/' . basename(parse_url($url, PHP_URL_PATH));
    if (!is_file($f) || filesize($f) < 100) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 90, CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; build)']);
        $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($b === false || $c !== 200) { say("Téléchargement impossible : $url (HTTP $c)"); exit(1); }
        file_put_contents($f, $b);
    }
    return file_get_contents($f);
}

// ---------- ressources partagées (CSS, JS, polices) ----------
$bsCss = file_get_contents(__DIR__ . '/../standalone/assets/abhd-bootstrap.min.css'); // Bootstrap compilé avec la charte ABhD
$bsCss = preg_replace('~/\*# sourceMappingURL=.*?\*/~', '', $bsCss);
$bsJs = fetchCached('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', $cache);
$bsJs = preg_replace('~//# sourceMappingURL=\S+~', '', $bsJs);
$faCss = fetchCached('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', $cache);
$fonts = ['fa-solid-900' => base64_encode(fetchCached('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2', $cache)),
          'fa-regular-400' => base64_encode(fetchCached('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2', $cache))];
// polices : seules les formats woff2 solid et regular sont gardés ; les icônes de marques ne sont pas utilisées
$faCss = preg_replace_callback('~@font-face\{([^}]*)\}~', function ($m) use ($fonts) {
    if (!preg_match('~webfonts/(fa-[a-z]+-\d+)\.woff2~', $m[1], $f) || !isset($fonts[$f[1]])) return '';
    $fam = preg_match('~font-family:([^;]+);~', $m[1], $x) ? $x[1] : '"Font Awesome 6 Free"';
    $w = preg_match('~font-weight:(\d+)~', $m[1], $x) ? $x[1] : '900';
    return '@font-face{font-family:' . $fam . ';font-style:normal;font-weight:' . $w . ';font-display:block;src:url(data:font/woff2;base64,' . $fonts[$f[1]] . ') format("woff2")}';
}, $faCss);
$leafletJs = fetchCached('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', $cache);
$leafletCss = fetchCached('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', $cache);
$leafletCss = preg_replace('~url\(images/[^)]*\)~', 'none', $leafletCss); // images du contrôle de couches : non utilisées (marqueurs dessinés en CSS)
$pdfLib = fetchCached('https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js', $cache);
$portalCss = file_get_contents('assets/css/style.css');
$common = trim(file_get_contents('assets/js/tools-common.js'));

// ---------- catalogue des outils ----------
// le générateur lui-même n'a pas besoin de base ; chaque rendu d'outil (processus enfant) tente de s'y connecter
if (!function_exists('getDB')) { function getDB() { throw new RuntimeException('Base de données non utilisée par le générateur'); } }
require_once 'includes/config.php';
require_once 'includes/functions.php';
$catalog = getPublicToolsCatalog();
$groups = [
    'Financer un projet' => ['calculateur', 'capacite', 'notaire', 'ptz', 'creditsspeciaux', 'plan', 'scenarios', 'relais', 'rachat', 'usure'],
    'Épargne et patrimoine' => ['epargne', 'assurancevie', 'per', 'epargnecredit', 'succession'],
    'Conseil et conformité' => ['signataires', 'evenements', 'pieces', 'saisie', 'memo_plafonds', 'memo_delais'],
    'Boîte à outils' => ['dates', 'validateurs', 'calculs', 'pdf', 'courrier', 'bureau_dom', 'disc', 'tableur', 'presentation'],
    'Références' => ['dpe', 'rge', 'procedures', 'codes', 'contacts'],
];
// Outils qui exigent un serveur : non inclus dans le fichier autonome
$exclus = ['assistant_ia' => 'assistant IA (service en ligne)', 'servicepublic' => 'recherche service-public.fr (index et IA hébergés sur le serveur)'];
// Outils déjà présents en version native dans le gabarit de base
$natifs = ['calculateur' => 'budget', 'courrier' => 'courrier'];

function renderTool($key, $file) {
    $p = proc_open([PHP_BINARY, __FILE__, '--render', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($p)) return [null, 'processus impossible'];
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
    return [$out, trim($err)];
}
function wrapperFile($key, $catalog) {
    // contenus issus de la base (procédures, codes, contacts) : pages dédiées du fichier autonome, sans formulaire de proposition
    if (in_array($key, ['procedures', 'codes', 'contacts'], true)) return realpath(__DIR__ . '/../standalone/pages/' . $key . '.php');
    $u = $catalog[$key]['url'];
    $f = realpath(__DIR__ . '/../tools/' . $u) ?: null;
    return $f;
}

$tools = []; $report = [];
foreach ($groups as $g => $keys) foreach ($keys as $key) {
    if ($only && !in_array($key, $only, true)) continue;
    $c = $catalog[$key] ?? null;
    if (!$c) { $report[$key] = 'absent du catalogue'; continue; }
    if (isset($natifs[$key])) { $tools[$key] = ['key' => $key, 'group' => $g, 'title' => $c['label'], 'icon' => $c['icon'], 'desc' => $c['description'] ?? '', 'native' => $natifs[$key]]; continue; }
    $file = wrapperFile($key, $catalog);
    if (!$file || !is_file($file)) { $report[$key] = 'page introuvable (' . $c['url'] . ')'; continue; }
    say("Rendu : $key");
    [$html, $err] = renderTool($key, $file);
    if (strpos($err, 'BASE_INDISPONIBLE') !== false) { $report[$key] = 'non inclus : base de données indisponible (lancez le générateur sur le serveur pour embarquer ce contenu)'; continue; }
    if (strpos($err, 'AUCUN_ELEMENT') !== false) { $report[$key] = 'non inclus : aucun élément publié dans la base'; continue; }
    if (!is_string($html) || stripos($html, '</html>') === false || strpos($err, 'ERREUR PHP') !== false || strpos($html, 'Fatal error') !== false) { $report[$key] = 'rendu impossible : ' . substr($err !== '' ? $err : trim((string)$html), -300); continue; }
    $tools[$key] = ['key' => $key, 'group' => $g, 'title' => $c['label'], 'icon' => $c['icon'], 'desc' => $c['description'] ?? '', 'html' => $html];
}
foreach ($exclus as $k => $why) $report[$k] = 'non inclus : ' . $why;

// zonage A/B/C des communes (PTZ, plan de financement, scénarios, crédits spéciaux) : embarqué s'il est dans la base
$zon = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --zonage 2>/dev/null');
$zonage = (is_string($zon) && strlen($zon) > 50 && json_decode($zon) !== null) ? $zon : null;
say($zonage ? 'Zonage embarqué : ' . round(strlen($zonage) / 1024) . ' Ko' : 'Zonage : non disponible (choix manuel de la zone dans les simulateurs)');
require __DIR__ . '/../standalone/assemble.php';
