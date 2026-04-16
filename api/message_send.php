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

if ($question === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$createdConversation = false;
$isFirst = false;

if ($convId > 0) {
    $check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
    $check->execute([$convId, $user['id']]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conversation introuvable']);
        exit;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE conversation_id = ?");
    $countStmt->execute([$convId]);
    $isFirst = ((int)$countStmt->fetchColumn() === 0);
} else {
    $stmt = $pdo->prepare("INSERT INTO conversations (user_id, title) VALUES (?, 'Nouvelle conversation')");
    $stmt->execute([$user['id']]);
    $convId = (int) $pdo->lastInsertId();
    $createdConversation = true;
    $isFirst = true;
}

// Save user message
$ins = $pdo->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)");
$ins->execute([$convId, 'user', $question]);

$newTitle = null;
if ($isFirst) {
    $newTitle = substr($question, 0, 72);
    if (strlen($question) > 72) $newTitle .= '…';
    $upd = $pdo->prepare("UPDATE conversations SET title = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$newTitle, $convId]);
} else {
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);
}

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
    echo json_encode([
        'success' => false,
        'error' => $errMsg,
        'conv_id' => $createdConversation ? $convId : null,
        'new_title' => $newTitle,
    ]);
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

echo json_encode([
    'success'   => true,
    'conv_id'   => $convId,
    'answer'    => $answer,
    'new_title' => $newTitle,
]);
