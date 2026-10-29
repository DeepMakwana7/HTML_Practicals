<?php
const AUTH_SESSION_TIMEOUT = 1800;
const REMEMBER_COOKIE_NAME = 'studenthub_remember';
const REMEMBER_TOKEN_LIFETIME = 2592000;

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['user_id'])) {
        $lastActivity = $_SESSION['last_activity'] ?? null;
        if (!is_int($lastActivity) || time() - $lastActivity > AUTH_SESSION_TIMEOUT) {
            $_SESSION = [];
            session_destroy();
            session_start();
            session_regenerate_id(true);
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
}

function auth_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function auth_valid_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function auth_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_clear_remember_cookie(): void
{
    setcookie(REMEMBER_COOKIE_NAME, '', auth_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE_NAME]);
}

function auth_revoke_remember_cookie(mysqli $conn): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;
    try {
        if (is_string($cookie) && preg_match('/^([a-f0-9]{32}):[a-f0-9]{64}$/D', $cookie, $parts)) {
            $statement = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $statement->bind_param('s', $parts[1]);
            $statement->execute();
            $statement->close();
        }
    } finally {
        auth_clear_remember_cookie();
    }
}

function auth_role_home(string $role): string
{
    if ($role === 'admin') {
        return 'admin-dashboard.php';
    }

    return $role === 'student' ? 'dashboard.php' : 'login.php';
}

function auth_redirect_for_role(string $role): void
{
    header('Location: ' . auth_role_home($role), true, 303);
    exit;
}

function auth_set_user_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'user_id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'username' => (string) $user['username'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'last_activity' => time(),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
}

function auth_issue_remember_token(mysqli $conn, int $userId): void
{
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + REMEMBER_TOKEN_LIFETIME);

    $statement = $conn->prepare(
        'INSERT INTO remember_tokens (student_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    $statement->bind_param('isss', $userId, $selector, $tokenHash, $expiresAt);
    $statement->execute();
    $statement->close();

    setcookie(
        REMEMBER_COOKIE_NAME,
        $selector . ':' . $validator,
        auth_cookie_options(time() + REMEMBER_TOKEN_LIFETIME)
    );
}

function auth_restore_remembered_user(mysqli $conn): bool
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/D', $cookie, $parts)) {
        if ($cookie !== null) {
            auth_clear_remember_cookie();
        }

        return false;
    }

    $selector = $parts[1];
    $validator = $parts[2];
    $statement = $conn->prepare(
        'SELECT rt.id, rt.token_hash, s.id, s.full_name, s.username, s.email, s.role
         FROM remember_tokens rt
         INNER JOIN students s ON s.id = rt.student_id
         WHERE rt.selector = ? AND rt.expires_at > UTC_TIMESTAMP()
         LIMIT 1'
    );
    $statement->bind_param('s', $selector);
    $statement->execute();
    $statement->bind_result($tokenId, $storedHash, $userId, $fullName, $username, $email, $role);
    $found = $statement->fetch();
    $statement->close();

    if (!$found || !hash_equals((string) $storedHash, hash('sha256', $validator))) {
        $delete = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $delete->bind_param('s', $selector);
        $delete->execute();
        $delete->close();
        auth_clear_remember_cookie();

        return false;
    }

    $delete = $conn->prepare('DELETE FROM remember_tokens WHERE id = ?');
    $delete->bind_param('i', $tokenId);
    $delete->execute();
    $delete->close();

    auth_clear_remember_cookie();
    auth_issue_remember_token($conn, (int) $userId);
    auth_set_user_session([
        'id' => $userId,
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'role' => $role,
    ]);

    return true;
}

function auth_logout(mysqli $conn): void
{
    try {
        auth_revoke_remember_cookie($conn);
    } finally {
        auth_destroy_session();
    }
}

function auth_destroy_session(): void
{
    auth_clear_remember_cookie();

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', auth_cookie_options(time() - 3600));
        }

        session_destroy();
    }
}

function auth_require_user(mysqli $conn, array $allowedRoles): void
{
    auth_start_session();

    if (!isset($_SESSION['user_id'])) {
        auth_restore_remembered_user($conn);
    }

    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header('Location: login.php', true, 303);
        exit;
    }

    $role = (string) $_SESSION['role'];
    if (!in_array($role, $allowedRoles, true)) {
        auth_redirect_for_role($role);
    }
}
