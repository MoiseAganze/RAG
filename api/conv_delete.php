<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = jsonRequireAuth();
$pdo  = getPDO();

$body   = json_decode(file_get_contents('php://input'), true);
$convId = (int)($body['conv_id'] ?? 0);

if (!$convId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'conv_id manquant']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM conversations WHERE id = ? AND user_id = ?");
$stmt->execute([$convId, $user['id']]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Conversation introuvable']);
    exit;
}

echo json_encode(['success' => true]);
