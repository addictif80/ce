<?php
/**
 * Données de zonage A/B/C pour le simulateur PTZ (JSON). Sans paramètre : disponibilité et liste des départements ;
 * avec ?dep=31 : communes du département avec leur zone. Aucune donnée n'est enregistrée.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/zonage.php';
requirePublicTool('ptz', true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=600');
try {
    $st = zonageStats();
    if ($st['count'] === 0) { echo json_encode(['available' => false]); exit; }
    // ?insee=31555 : zone d'une commune
    if (isset($_GET['insee'])) {
        $insee = strtoupper(trim($_GET['insee']));
        if (!preg_match('/^(\d{5}|2[AB]\d{3})$/', $insee)) { http_response_code(400); echo json_encode(['error' => 'Code commune invalide.']); exit; }
        $c = zonageCommune($insee);
        echo json_encode($c ? ['available' => true, 'code' => $insee, 'commune' => $c[0], 'zone' => $c[1]] : ['available' => true, 'zone' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $dep = strtoupper(trim($_GET['dep'] ?? ''));
    if ($dep === '') { echo json_encode(['available' => true, 'count' => $st['count'], 'date' => $st['date'], 'libelle' => $st['libelle'], 'departements' => zonageDepartements()], JSON_UNESCAPED_UNICODE); exit; }
    if (!preg_match('/^(\d{2,3}|2[AB])$/', $dep)) { http_response_code(400); echo json_encode(['error' => 'Département invalide.']); exit; }
    echo json_encode(['available' => true, 'communes' => zonageCommunes($dep)], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['available' => false]);
}
