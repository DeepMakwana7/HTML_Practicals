<?php
require_once __DIR__ . '/includes/auth.php';

auth_start_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php', true, 303);
    exit;
}

if (!auth_valid_csrf_token(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
    http_response_code(400);
    exit('Invalid sign-out request.');
}

try {
    require_once __DIR__ . '/includes/db.php';
    auth_logout($conn);
    $conn->close();
} catch (mysqli_sql_exception $exception) {
    auth_destroy_session();
    error_log('StudentHub logout token cleanup failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Your session was signed out, but remember-me token cleanup failed. Please contact the administrator.');
} catch (RuntimeException $exception) {
    auth_destroy_session();
    error_log('StudentHub logout database error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Your session was signed out, but remember-me token cleanup failed. Please contact the administrator.');
}

header('Location: login.php?logged_out=1', true, 303);
exit;
