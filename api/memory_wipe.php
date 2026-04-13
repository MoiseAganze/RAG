<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

function sendRemoteRequest(string $url, string $method, array $headers, ?string $body = null): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('L’extension PHP cURL est indisponible sur ce serveur.');
    }

    $ch = curl_init($url);
    if (!$ch) {
        throw new RuntimeException('Impossible d’initialiser cURL.');
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    return [
        'body' => $response === false ? '' : $response,
        'http_code' => $httpCode,
        'error' => $curlErr,
    ];
}

function deleteUploadContents(string $directory, array &$failedPaths): int {
    if (!is_dir($directory)) {
        return 0;
    }

    $deletedCount = 0;
    $entries = scandir($directory);
    if ($entries === false) {
        throw new RuntimeException('Impossible de lire le dossier uploads.');
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || strpos($entry, '.') === 0) {
            continue;
        }

        $fullPath = $directory . '/' . $entry;

        if (is_dir($fullPath)) {
            $deletedCount += deleteUploadContents($fullPath, $failedPaths);
            if (!@rmdir($fullPath)) {
                $failedPaths[] = $fullPath;
            }
            continue;
        }

        if (is_file($fullPath)) {
            if (@unlink($fullPath)) {
                $deletedCount++;
            } else {
                $failedPaths[] = $fullPath;
            }
        }
    }

    return $deletedCount;
}

try {
    jsonRequireRole('admin_full');
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }

    $collectionUrl = 'http://148.230.120.123:32768/collections/test_rag_v2';
    $bearerToken = 'iGlWfzD7ykHU5VkURuoXyBuh7sNt4FZ9';
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json',
    ];

    $remoteWarnings = [];

    $deleteResponse = sendRemoteRequest($collectionUrl, 'DELETE', $headers);
    if ($deleteResponse['error'] !== '') {
        $remoteWarnings[] = 'Suppression distante impossible: ' . $deleteResponse['error'];
    }

    if (!in_array($deleteResponse['http_code'], [200, 202, 204, 404], true)) {
        $remoteWarnings[] = 'Suppression distante échouée: HTTP ' . $deleteResponse['http_code'];
    }

    $failedPaths = [];
    $deletedFiles = deleteUploadContents(__DIR__ . '/../uploads', $failedPaths);

    $deletedDocuments = 0;
    $pdo->beginTransaction();
    try {
        $deletedDocuments = (int) $pdo->exec('DELETE FROM documents');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $payload = json_encode([
        'vectors' => [
            'size' => 3072,
            'distance' => 'Cosine',
        ],
    ]);

    if ($payload === false) {
        throw new RuntimeException('Impossible de construire la requête de réinitialisation distante.');
    }

    $putResponse = sendRemoteRequest($collectionUrl, 'PUT', $headers, $payload);
    if ($putResponse['error'] !== '') {
        $remoteWarnings[] = 'Recréation distante impossible: ' . $putResponse['error'];
    }

    if ($putResponse['http_code'] < 200 || $putResponse['http_code'] >= 300) {
        $remoteWarnings[] = 'Recréation distante échouée: HTTP ' . $putResponse['http_code'];
    }

    if (!empty($failedPaths)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Certains fichiers n’ont pas pu être supprimés du dossier uploads.',
            'deleted_documents' => $deletedDocuments,
            'deleted_files' => $deletedFiles,
            'warning' => empty($remoteWarnings) ? null : implode(' | ', array_unique($remoteWarnings)),
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'deleted_documents' => $deletedDocuments,
        'deleted_files' => $deletedFiles,
        'warning' => empty($remoteWarnings) ? null : implode(' | ', array_unique($remoteWarnings)),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
