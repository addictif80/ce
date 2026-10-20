<?php
/** Recherche dans les fiches « particuliers » de service-public.fr (voir includes/servicepublic.php). Accessible aux visiteurs /tools autorisés et aux utilisateurs connectés. */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/servicepublic.php';
require_once __DIR__ . '/../includes/assist.php';
require_once __DIR__ . '/../includes/audit.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée.']); exit; }
requirePublicTool('servicepublic', true);
$in = json_decode(file_get_contents('php://input'), true);
$q = trim(preg_replace('/\s+/u', ' ', (string)($in['q'] ?? '')));
if (mb_strlen($q) < 3) { echo json_encode(['error' => 'Saisissez une question ou quelques mots (3 caractères au moins).']); exit; }
$q = mb_substr($q, 0, 300);
// Limite par session : 60 recherches par heure
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
$now = time(); $h = array_values(array_filter($_SESSION['sp_hits'] ?? [], fn($t) => $t > $now - 3600));
if (count($h) >= 60) { echo json_encode(['error' => 'Trop de recherches en peu de temps. Réessayez dans quelques minutes.']); exit; }
$h[] = $now; $_SESSION['sp_hits'] = $h;

$status = spStatus();
if ($status['n'] === 0) { echo json_encode(['error' => 'La base service-public.fr n\'est pas encore chargée : un administrateur doit l\'importer (Administration, onglet Rédaction).']); exit; }
auditServer('servicepublic', 'recherche', 'service-public.fr', $q);

$terms = spTerms($q);
$fiches = spSearch($q, 5);
$results = [];
foreach ($fiches as $f) {
    $ex = trim($f['description']) !== '' ? $f['description'] : spExcerpt($f['texte'], $terms, 300, $f['titre']);
    $results[] = ['titre' => $f['titre'], 'theme' => $f['theme'], 'url' => $f['url'], 'date' => $f['date_maj'] ? date('d/m/Y', strtotime($f['date_maj'])) : '', 'extrait' => mb_substr($ex, 0, 320)];
}
$answer = null; $why = ''; $detail = '';
if ($fiches) {
    if (!assistEnabled()) { $why = 'desactive'; $detail = getToolsSetting('assist_on', '0') !== '1' ? 'aide à la rédaction désactivée dans l\'administration' : 'clé API absente'; }
    elseif (!assistRateOk()) { $why = 'limite'; $detail = 'limite de demandes par heure atteinte'; }
    else [$answer, $why, $detail] = spAnswer($q, $fiches, $terms);
}
echo json_encode(['ok' => true, 'results' => $results, 'answer' => $answer, 'why' => $answer ? '' : $why,
    'detail' => (!$answer && toolsVisitorIsLoggedIn() && function_exists('isAdmin') && isAdmin()) ? mb_substr((string)$detail, 0, 400) : '',
    'maj' => $status['derniere_maj'] ? date('d/m/Y', strtotime($status['derniere_maj'])) : '', 'sources' => count($fiches)], JSON_UNESCAPED_UNICODE);
