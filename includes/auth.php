<?php
require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function getSessionUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function loginUser(array $row): void {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'        => (int) $row['id'],
        'matricule' => $row['matricule'],
        'nom'       => $row['nom'],
        'prenom'    => $row['prenom'],
        'role'      => $row['role'],
    ];
}

function logoutUser(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function requireAuth(string $redirectTo = 'login.php'): array {
    $user = getSessionUser();
    if (!$user) {
        header('Location: ' . $redirectTo);
        exit;
    }
    return $user;
}

function requireRole(string $role, string $redirectTo = 'chat.php'): array {
    $user = requireAuth();
    if ($user['role'] !== $role) {
        header('Location: ' . $redirectTo);
        exit;
    }
    return $user;
}

function jsonRequireAuth(): array {
    $user = getSessionUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }
    return $user;
}

function jsonRequireRole(string $role): array {
    $user = jsonRequireAuth();
    if ($user['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        exit;
    }
    return $user;
}
