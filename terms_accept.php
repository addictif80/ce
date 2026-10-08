<?php
// Enregistre l'acceptation des conditions d'utilisation par l'utilisateur connecté.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/terms.php';
requireLogin();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
termsAccept(getCurrentUserId());
echo json_encode(['ok' => true]);
