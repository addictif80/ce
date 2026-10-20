<?php
/** Réception des événements du journal d'audit de /tools (voir includes/audit.php). Accessible aux visiteurs autorisés et aux utilisateurs connectés. */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
// Même origine uniquement (le navigateur indique l'origine de la requête)
$sfs = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
if ($sfs !== '' && !in_array($sfs, ['same-origin', 'same-site'], true)) { http_response_code(403); exit; }
requireToolsAccess(true);
$in = json_decode(file_get_contents('php://input'), true);
$list = is_array($in['events'] ?? null) ? array_slice($in['events'], 0, 100) : [];
$rows = [];
foreach ($list as $e) {
    if (!is_array($e)) continue;
    $rows[] = [ // l'horodatage est toujours celui du serveur
        'sid' => (string)($e['sid'] ?? ''), 'tool_key' => (string)($e['tool'] ?? ''), 'page' => (string)($e['page'] ?? ''),
        'event' => (string)($e['ev'] ?? ''), 'target' => (string)($e['target'] ?? ''), 'value' => isset($e['value']) ? (string)$e['value'] : null];
}
auditAppend($rows);
http_response_code(204);
