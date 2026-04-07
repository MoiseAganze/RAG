<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = jsonRequireRole('admin_full');
$pdo  = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    echo json_encode(['success' => false, 'error' => "Erreur d'upload (code {$errCode})"]);
    exit;
}

$file         = $_FILES['file'];
$originalName = basename($file['name']);
$fileSize     = (int)$file['size'];
$mimeType     = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
$ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$storedName   = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
$uploadDir    = __DIR__ . '/../uploads/';
$destPath     = $uploadDir . $storedName;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Impossible de sauvegarder le fichier']);
    exit;
}

// ─── Forward to n8n webhook ───────────────────────────────────────────────
$webhookOk  = false;
$webhookErr = '';

$ch = curl_init(WEBHOOK_INDEXATION_URL);
$cf = new CURLFile($destPath, $mimeType, $originalName);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['file' => $cf, 'filename' => $originalName],
    CURLOPT_TIMEOUT        => 60,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    $webhookErr = $curlErr;
} elseif ($httpCode < 200 || $httpCode >= 300) {
    $webhookErr = "HTTP {$httpCode}";
} else {
    $webhookOk = true;
}

// ─── Save record to DB ────────────────────────────────────────────────────
$status = $webhookOk ? 'success' : 'error';
$pdo->prepare(
    "INSERT INTO documents (user_id, original_name, stored_name, file_size, mime_type, status, error_msg)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
)->execute([
    $user['id'],
    $originalName,
    $storedName,
    $fileSize,
    $mimeType,
    $status,
    $webhookOk ? null : $webhookErr,
]);

if ($webhookOk) {
    echo json_encode(['success' => true, 'filename' => $originalName]);
} else {
    echo json_encode(['success' => false, 'error' => "Webhook : {$webhookErr}", 'filename' => $originalName]);
}
