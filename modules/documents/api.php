<?php
// Point d'entrée JSON des documents (tableurs et présentations). Réservé aux utilisateurs connectés ; l'en-tête X-Doc-Api écarte les envois de formulaires venus d'autres sites.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/documents.php';
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Session expirée : reconnectez-vous.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_DOC_API'] ?? '') !== '1') { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Requête invalide.']); exit; }
@set_time_limit(60);
docsApi();
