<?php
/**
 * Recherche en langage naturel dans les procédures (appel AJAX).
 *
 * La question est convertie en mots-clés : découpage local, complété par l'IA
 * (si activée) pour proposer des synonymes / termes métier. Les procédures sont
 * ensuite classées selon le nombre de mots-clés trouvés (nom et texte).
 */
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/assist_dossier.php';

header('Content-Type: application/json; charset=utf-8');

$userId = getCurrentUserId();
$q = trim((string)($_POST['q'] ?? ''));
if ($q === '') { echo json_encode(['ok' => false, 'error' => 'Saisissez une question.']); exit; }
if (mb_strlen($q) > 500) $q = mb_substr($q, 0, 500);

$db = getDB();

// Mots-clés : découpage local d'abord
$mots = searchQueryKeywords($q);
$iaUtilisee = false;
if (assistSearchEnabled()) {
    [$aiMots, $err] = assistSearchKeywords($q);
    if (is_array($aiMots)) {
        $mots = array_values(array_unique(array_merge($mots, $aiMots)));
        $iaUtilisee = true;
    }
}
$mots = array_slice($mots, 0, 12);

if (!$mots) {
    echo json_encode(['ok' => true, 'keywords' => [], 'ia' => $iaUtilisee, 'results' => []]);
    exit;
}

// Récupérer des candidats (au moins un mot-clé), dans le périmètre habituel
$conds = [];
$params = [$userId];
foreach ($mots as $m) {
    $conds[] = 'p.nom LIKE ?';
    $params[] = '%' . $m . '%';
    $conds[] = 'p.texte LIKE ?';
    $params[] = '%' . $m . '%';
}
$sql = "SELECT p.id, p.nom, p.texte, p.mise_en_avant, p.categorie_id, c.nom AS categorie
        FROM procedures p LEFT JOIN categories_procedures c ON p.categorie_id = c.id
        WHERE (p.user_id = ? OR p.approved = 1) AND (" . implode(' OR ', $conds) . ")
        LIMIT 200";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    // categories_procedures peut ne pas exister : repli sans la jointure
    $sql = "SELECT p.id, p.nom, p.texte, p.mise_en_avant FROM procedures p WHERE (p.user_id = ? OR p.approved = 1) AND (" . implode(' OR ', $conds) . ") LIMIT 200";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

// Classement : nom > texte, puis mise en avant
$scored = [];
foreach ($rows as $r) {
    $nom = mb_strtolower((string)$r['nom'], 'UTF-8');
    $texte = mb_strtolower(strip_tags((string)$r['texte']), 'UTF-8');
    $score = (int)($r['mise_en_avant'] ?? 0) * 0.5;
    foreach ($mots as $m) {
        $m = mb_strtolower($m, 'UTF-8');
        if (mb_strpos($nom, $m) !== false) $score += 3;
        if (mb_strpos($texte, $m) !== false) $score += 1;
    }
    $r['_score'] = $score;
    $scored[] = $r;
}
usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
$scored = array_slice($scored, 0, 15);

$results = [];
foreach ($scored as $r) {
    $results[] = [
        'id' => (int)$r['id'],
        'nom' => $r['nom'],
        'categorie' => $r['categorie'] ?? '',
        'extrait' => excerpt(strip_tags((string)($r['texte'] ?? '')), 180),
        'mise_en_avant' => (int)($r['mise_en_avant'] ?? 0),
    ];
}

echo json_encode(['ok' => true, 'keywords' => $mots, 'ia' => $iaUtilisee, 'results' => $results]);
