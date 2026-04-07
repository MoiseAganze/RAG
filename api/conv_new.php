<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = jsonRequireAuth();
$pdo  = getPDO();

$stmt = $pdo->prepare(
    "INSERT INTO conversations (user_id, title) VALUES (?, 'Nouvelle conversation')"
);
$stmt->execute([$user['id']]);
$id = (int) $pdo->lastInsertId();

echo json_encode(['success' => true, 'id' => $id, 'title' => 'Nouvelle conversation']);
