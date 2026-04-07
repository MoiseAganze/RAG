<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$user = jsonRequireAuth();
$pdo  = getPDO();

$body     = json_decode(file_get_contents('php://input'), true);
$convId   = (int)($body['conv_id'] ?? 0);
$question = trim($body['question'] ?? '');

if (!$convId || $question === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

// Verify conversation ownership
$check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
$check->execute([$convId, $user['id']]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Conversation introuvable']);
    exit;
}

// Count existing messages to detect first message
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE conversation_id = ?");
$countStmt->execute([$convId]);
$isFirst = ((int)$countStmt->fetchColumn() === 0);

// Save user message
$ins = $pdo->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)");
$ins->execute([$convId, 'user', $question]);

// Call n8n webhook via cURL
$answer = null;
$ch = curl_init(WEBHOOK_QA_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'question'        => $question,
        'conversation_id' => $convId,
    ]),
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$raw      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode < 200 || $httpCode >= 300) {
    $errMsg = $curlErr ?: "Erreur webhook HTTP $httpCode";
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $answer = (is_string($data) ? $data : null)
           ?? $data['answer']   ?? $data['response'] ?? $data['output']
           ?? $data['text']     ?? $data['message']  ?? null;
    if ($answer === null) $answer = $raw;
} else {
    $answer = $raw;
}

// Save assistant message
$ins->execute([$convId, 'assistant', $answer]);

// Auto-title on first message
$newTitle = null;
if ($isFirst) {
    $newTitle = mb_substr($question, 0, 72);
    if (mb_strlen($question) > 72) $newTitle .= '…';
    $upd = $pdo->prepare("UPDATE conversations SET title = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$newTitle, $convId]);
} else {
    // Keep updated_at fresh
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);
}

echo json_encode([
    'success'   => true,
    'answer'    => $answer,
    'new_title' => $newTitle,
]);
