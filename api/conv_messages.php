<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$user   = jsonRequireAuth();
$pdo    = getPDO();
$convId = (int)($_GET['conv_id'] ?? 0);

if (!$convId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'conv_id manquant']);
    exit;
}

// Verify ownership
$check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
$check->execute([$convId, $user['id']]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Conversation introuvable']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, role, content, created_at
     FROM messages
     WHERE conversation_id = ?
     ORDER BY created_at ASC"
);
$stmt->execute([$convId]);
$messages = $stmt->fetchAll();

echo json_encode(['success' => true, 'messages' => $messages]);
