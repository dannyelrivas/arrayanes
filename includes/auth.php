<?php
require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    if (!isset($_SESSION['user_id'])) return false;
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        logout();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /arrayanes/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['error' => 'Acceso no autorizado']));
    }
}

function isAdmin(): bool {
    return ($_SESSION['rol'] ?? '') === 'admin';
}

function login(string $username, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password_hash, nombre, rol FROM usuarios_sistema WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
    }
    
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['nombre']        = $user['nombre'];
    $_SESSION['rol']           = $user['rol'];
    $_SESSION['last_activity'] = time();
    
    $db->prepare("UPDATE usuarios_sistema SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    
    return ['success' => true, 'rol' => $user['rol']];
}

function logout(): void {
    startSession();
    session_destroy();
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nombre'   => $_SESSION['nombre'] ?? '',
        'rol'      => $_SESSION['rol'] ?? '',
    ];
}
