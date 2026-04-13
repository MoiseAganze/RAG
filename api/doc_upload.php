<?php 
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

 function parseIniSize(string $value): int {
     $value = trim($value);
     if ($value === '') {
         return 0;
     }

     $unit = strtolower(substr($value, -1));
     $number = (float) $value;

     switch ($unit) {
         case 'g':
             $number *= 1024;
         case 'm':
             $number *= 1024;
         case 'k':
             $number *= 1024;
     }

     return (int) round($number);
 }

 function detectMimeType(string $path): string {
     if (function_exists('mime_content_type')) {
         $mime = @mime_content_type($path);
         if (is_string($mime) && $mime !== '') {
             return $mime;
         }
     }

     if (function_exists('finfo_open')) {
         $finfo = @finfo_open(FILEINFO_MIME_TYPE);
         if ($finfo) {
             $mime = @finfo_file($finfo, $path);
             finfo_close($finfo);
             if (is_string($mime) && $mime !== '') {
                 return $mime;
             }
         }
     }

     return 'application/octet-stream';
 }

 function getUploadErrorMessage(int $code): string {
     switch ($code) {
         case UPLOAD_ERR_INI_SIZE:
             return 'Le fichier dépasse la limite upload_max_filesize du serveur.';
         case UPLOAD_ERR_FORM_SIZE:
             return 'Le fichier dépasse la taille maximale autorisée par le formulaire.';
         case UPLOAD_ERR_PARTIAL:
             return 'Le fichier a été seulement partiellement téléversé.';
         case UPLOAD_ERR_NO_FILE:
             return 'Aucun fichier n’a été envoyé.';
         case UPLOAD_ERR_NO_TMP_DIR:
             return 'Le dossier temporaire PHP est manquant.';
         case UPLOAD_ERR_CANT_WRITE:
             return 'PHP n’a pas pu écrire le fichier sur le disque.';
         case UPLOAD_ERR_EXTENSION:
             return 'Une extension PHP a interrompu l’upload.';
         default:
             return 'Erreur d’upload inconnue.';
     }
 }

try {
    $user = jsonRequireRole('admin_full');
    $pdo  = getPDO();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }

    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    $postMaxSize = parseIniSize((string) ini_get('post_max_size'));
    if ($contentLength > 0 && $postMaxSize > 0 && $contentLength > $postMaxSize && empty($_FILES)) {
        http_response_code(413);
        echo json_encode([
            'success' => false,
            'error' => 'La requête dépasse la limite post_max_size du serveur (' . ini_get('post_max_size') . ').',
        ]);
        exit;
    }

    if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu par PHP. Vérifiez post_max_size et la configuration d’upload.']);
        exit;
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errCode = (int) ($_FILES['file']['error'] ?? -1);
        $message = getUploadErrorMessage($errCode);
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $message .= ' Limites serveur: upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . '.';
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message . " (code {$errCode})"]);
        exit;
    }

    $file         = $_FILES['file'];
    $originalName = basename($file['name']);
    $fileSize     = (int)$file['size'];

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Le fichier temporaire PHP est introuvable ou invalide. Vérifiez upload_tmp_dir et les permissions.']);
        exit;
    }

    $mimeType     = detectMimeType($file['tmp_name']);
    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $storedName   = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
    $uploadDir    = __DIR__ . '/../uploads/';
    $destPath     = $uploadDir . $storedName;

    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier d\'upload. Vérifiez les permissions.']);
            exit;
        }
    }

    if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'error' => 'Impossible de sauvegarder le fichier sur le serveur. Vérifiez les permissions du dossier uploads et de upload_tmp_dir.']);
        exit;
    }

    if (!is_readable($destPath)) {
        throw new RuntimeException('Le fichier sauvegardé est illisible par PHP. Vérifiez les permissions du dossier uploads.');
    }

    // ─── Forward to n8n webhook ───────────────────────────────────────────────
    $webhookOk  = false;
    $webhookErr = '';

    if (!function_exists('curl_init')) {
        throw new RuntimeException('L’extension PHP cURL est indisponible sur ce serveur.');
    }

    if (!class_exists('CURLFile')) {
        throw new RuntimeException('La classe CURLFile est indisponible sur ce serveur.');
    }

    $ch = curl_init(WEBHOOK_INDEXATION_URL);
    if (!$ch) {
        throw new Exception('Impossible d\'initialiser cURL');
    }
    
    $cf = new CURLFile($destPath, $mimeType, $originalName);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cf, 'filename' => $originalName],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 120, // Increased timeout for larger files
        CURLOPT_SSL_VERIFYPEER => false, // Prevent SSL issues if n8n has self-signed cert
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $response = '';
    }

    if ($curlErr) {
        $webhookErr = "cURL Error: " . $curlErr;
    } elseif ($httpCode < 200 || $httpCode >= 300) {
        $webhookErr = "HTTP {$httpCode}: " . substr($response, 0, 100);
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

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
