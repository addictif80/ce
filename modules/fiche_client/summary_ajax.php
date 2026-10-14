<?php
/**
 * Résumé automatique d'un dossier client (appel AJAX depuis la fiche 360°).
 * Les données transmises au service externe sont anonymisées.
 * Si l'aide IA n'est pas disponible, on retourne un résumé local (sans IA).
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/fiche_client.php';
require_once __DIR__ . '/../../includes/assist_dossier.php';

header('Content-Type: application/json; charset=utf-8');

$userId = getCurrentUserId();
$numero = trim((string)($_POST['numero'] ?? ''));
$nom    = trim((string)($_POST['nom'] ?? ''));

if ($numero === '' && $nom === '') {
    echo json_encode(['ok' => false, 'error' => 'Client non identifié.']);
    exit;
}

$db = getDB();
$fiche = getFicheClient($db, $numero, $nom, $userId);

// Résumé local d'abord (toujours disponible)
$local = localDossierSummary($fiche);

if (!assistDossierEnabled()) {
    echo json_encode(['ok' => true, 'source' => 'local', 'text' => $local]);
    exit;
}

[$out, $err] = assistDossierSummary($fiche);
if ($out === null) {
    // Repli sur le résumé local plutôt qu'une erreur sèche
    echo json_encode(['ok' => true, 'source' => 'local', 'text' => $local, 'note' => $err]);
    exit;
}

echo json_encode(['ok' => true, 'source' => 'ia', 'text' => $out]);
