<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = jsonRequireAuth();
$pdo  = getPDO();

$body   = json_decode(file_get_contents('php://input'), true);
$convId = (int)($body['conv_id'] ?? 0);
$title  = trim($body['title'] ?? '');

if (!$convId || $title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$title = mb_substr($title, 0, 255);

$stmt = $pdo->prepare(
    "UPDATE conversations SET title = ? WHERE id = ? AND user_id = ?"
);
$stmt->execute([$title, $convId, $user['id']]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Conversation introuvable']);
    exit;
}

echo json_encode(['success' => true, 'title' => $title]);
