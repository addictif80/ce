<?php
/** Compteur anonyme d'une action sur /tools (voir includes/stats.php). Réponse vide, aucune donnée de visiteur n'est lue ni conservée. */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stats.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k = (string)($_POST['k'] ?? ''); $e = (string)($_POST['e'] ?? '');
    if (isset(getPublicToolsCatalog()[$k]) && isset(statsEvents()[$e]) && $e !== 'view') statsHit($k, $e);
}
http_response_code(204);
