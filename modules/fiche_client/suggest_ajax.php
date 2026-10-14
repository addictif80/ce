<?php
/**
 * Suggestions enrichies par l'IA pour un dossier client (appel AJAX).
 * Les règles métier sont calculées localement ; l'IA reformule et complète.
 * Les données transmises au service externe sont anonymisées.
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/fiche_client.php';
require_once __DIR__ . '/../../includes/suggestions.php';
require_once __DIR__ . '/../../includes/assist_dossier.php';

header('Content-Type: application/json; charset=utf-8');

$userId = getCurrentUserId();
$numero = trim((string)($_POST['numero'] ?? ''));
$nom    = trim((string)($_POST['nom'] ?? ''));

if ($numero === '' && $nom === '') {
    echo json_encode(['ok' => false, 'error' => 'Client non identifié.']);
    exit;
}

if (!assistDossierEnabled()) {
    echo json_encode(['ok' => false, 'error' => 'L\'aide IA n\'est pas activée (administration → Rédaction).']);
    exit;
}

$db = getDB();
$fiche = getFicheClient($db, $numero, $nom, $userId);
$regles = getSuggestionsRegles($fiche);

[$out, $err] = assistDossierSuggestions($fiche, $regles);
if ($out === null) {
    echo json_encode(['ok' => false, 'error' => $err]);
    exit;
}

echo json_encode(['ok' => true, 'suggestions' => $out]);
