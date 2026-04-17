<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$db = getDB();
$userId = getCurrentUserId();
$action = $_POST['action'] ?? '';

if ($action === 'get_shares') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    $stmt = $db->prepare("SELECT lien_partage FROM blocnotes WHERE id = ? AND user_id = ?");
    $stmt->execute([$noteId, $userId]);
    $note = $stmt->fetch();
    if (!$note) { echo json_encode(['error' => 'Non autorisé']); exit; }

    $stmt = $db->prepare("
        SELECT bp.shared_with, u.nom, u.prenom
        FROM blocnotes_partages bp
        JOIN users u ON bp.shared_with = u.id
        WHERE bp.note_id = ?
        ORDER BY u.nom ASC, u.prenom ASC
    ");
    $stmt->execute([$noteId]);
    $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'shares' => $shares, 'lien_partage' => $note['lien_partage']]);
    exit;
}

if ($action === 'share_user') {
    $noteId    = (int)($_POST['note_id']    ?? 0);
    $shareWith = (int)($_POST['share_with'] ?? 0);

    $stmt = $db->prepare("SELECT id FROM blocnotes WHERE id = ? AND user_id = ?");
    $stmt->execute([$noteId, $userId]);
    if (!$stmt->fetch()) { echo json_encode(['error' => 'Non autorisé']); exit; }
    if ($shareWith === $userId) { echo json_encode(['error' => 'Impossible de partager avec vous-même']); exit; }
    if (!$shareWith) { echo json_encode(['error' => 'Utilisateur invalide']); exit; }

    $stmt = $db->prepare("INSERT IGNORE INTO blocnotes_partages (note_id, shared_by, shared_with) VALUES (?, ?, ?)");
    $stmt->execute([$noteId, $userId, $shareWith]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'unshare_user') {
    $noteId    = (int)($_POST['note_id']    ?? 0);
    $shareWith = (int)($_POST['share_with'] ?? 0);

    $stmt = $db->prepare("DELETE FROM blocnotes_partages WHERE note_id = ? AND shared_by = ? AND shared_with = ?");
    $stmt->execute([$noteId, $userId, $shareWith]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'generate_link') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    $stmt = $db->prepare("SELECT id FROM blocnotes WHERE id = ? AND user_id = ?");
    $stmt->execute([$noteId, $userId]);
    if (!$stmt->fetch()) { echo json_encode(['error' => 'Non autorisé']); exit; }

    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE blocnotes SET lien_partage = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$token, $noteId, $userId]);
    echo json_encode(['success' => true, 'token' => $token]);
    exit;
}

if ($action === 'revoke_link') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    $stmt = $db->prepare("UPDATE blocnotes SET lien_partage = NULL WHERE id = ? AND user_id = ?");
    $stmt->execute([$noteId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
