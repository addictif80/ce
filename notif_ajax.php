<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$userId = getCurrentUserId();

$db->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT,
    lien VARCHAR(500),
    lu TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_lu (user_id, lu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'count') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0");
    $stmt->execute([$userId]);
    echo json_encode(['count' => (int)$stmt->fetchColumn()]);
    exit;
}

if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$userId]);
    echo json_encode(['notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
