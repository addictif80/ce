<?php
/** Point d'entrée JSON de l'aide à la rédaction (voir includes/assist.php). Accessible aux visiteurs /tools autorisés et aux utilisateurs connectés. */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/assist.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée.']); exit; }
requirePublicTool('courrier', true);
$in = json_decode(file_get_contents('php://input'), true);
echo json_encode(is_array($in) ? assistHandle($in) : ['error' => 'Demande invalide.'], JSON_UNESCAPED_UNICODE);
