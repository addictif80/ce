<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$userId = getCurrentUserId();

$map = [
    'instances' => ['table' => 'instances', 'field' => 'statut', 'doneVal' => 'fait', 'todoVal' => 'a_faire'],
    'rappels' => ['table' => 'demandes_rappel', 'field' => 'traitee', 'doneVal' => 1, 'todoVal' => 0],
    'demandes_clients' => ['table' => 'demandes_clients', 'field' => 'traitee', 'doneVal' => 1, 'todoVal' => 0],
];

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$done = ($_POST['done'] ?? '') === '1';

if (!isset($map[$type]) || $id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$conf = $map[$type];
$value = $done ? $conf['doneVal'] : $conf['todoVal'];
$stmt = $db->prepare("UPDATE `{$conf['table']}` SET `{$conf['field']}` = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$value, $id, $userId]);

echo json_encode(['success' => $stmt->rowCount() > 0]);
