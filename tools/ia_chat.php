<?php
/** Conversation avec l'assistant généraliste (voir includes/assist.php). Utilisateurs connectés ; visiteurs /tools seulement si l'administrateur a activé l'outil « Assistant IA ». */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/assist.php';
require_once __DIR__ . '/../includes/audit.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée.']); exit; }
requirePublicTool('assistant_ia', true);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) { echo json_encode(['error' => 'Demande invalide.']); exit; }
$res = assistChatHandle($in);
if (!empty($res['ok'])) {
    // Journal : seulement la taille de l'échange (jamais le contenu des conversations)
    $last = end($in['messages']);
    auditServer('assistant_ia', 'ia', 'message à l\'assistant', mb_strlen((string)($last['content'] ?? '')) . ' car. envoyés, ' . mb_strlen($res['text']) . ' car. reçus');
}
if (!empty($res['detail']) && !(toolsVisitorIsLoggedIn() && function_exists('isAdmin') && isAdmin())) unset($res['detail']);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
