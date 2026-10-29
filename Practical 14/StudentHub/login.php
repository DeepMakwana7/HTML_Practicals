<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';

auth_start_session();
$formError = null;

try {
    require_once __DIR__ . '/includes/db.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $identifier = form_post_value('username') ?? '';
        $password = form_post_raw_value('password');
        $rememberMe = form_post_value('remember_me') === 'on';

        if (!auth_valid_csrf_token(form_post_value('csrf_token'))) {
            $formError = 'Your sign-in request expired. Please try again.';
        } elseif ($identifier === '' || strlen($identifier) > 254 || $password === null || $password === '') {
            $formError = 'Enter a valid username or email address and password.';
        } else {
            $statement = $conn->prepare(
                'SELECT id, full_name, username, email, password_hash, role
                 FROM students
                 WHERE username = ? OR email = ?
                 LIMIT 1'
            );
            $statement->bind_param('ss', $identifier, $identifier);
            $statement->execute();
            $statement->bind_result($userId, $fullName, $username, $email, $passwordHash, $role);
            $found = $statement->fetch();
            $statement->close();

            if (!$found || !password_verify($password, (string) $passwordHash)) {
                $formError = 'The username/email or password is incorrect.';
            } elseif (!in_array($role, ['student', 'admin'], true)) {
                error_log('StudentHub login rejected an account with an unsupported role.');
                $formError = 'We could not sign you in. Please contact the administrator.';
            } else {
                if ($rememberMe) {
                    auth_revoke_remember_cookie($conn);
                    auth_issue_remember_token($conn, (int) $userId);
                } else {
                    auth_revoke_remember_cookie($conn);
                }

                auth_set_user_session([
                    'id' => $userId,
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                ]);
                auth_redirect_for_role((string) $role);
            }
        }
    } elseif (
        isset($_SESSION['user_id'], $_SESSION['role'])
        && in_array($_SESSION['role'], ['student', 'admin'], true)
    ) {
        auth_redirect_for_role((string) $_SESSION['role']);
    } elseif (isset($_SESSION['user_id'])) {
        $_SESSION = [];
        session_regenerate_id(true);
        $formError = 'We could not verify your account. Please sign in again.';
    } elseif (auth_restore_remembered_user($conn)) {
        auth_redirect_for_role((string) $_SESSION['role']);
    }

    $conn->close();
} catch (mysqli_sql_exception | RuntimeException $exception) {
    error_log('StudentHub login error: ' . $exception->getMessage());
    $formError = 'Sign-in is temporarily unavailable. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to your StudentHub student account.">
    <title>Sign In | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <ul class="nav-list"><li><a href="index.html">Home</a></li><li><a href="about.html">About</a></li><li><a href="registration.php">Registration</a></li><li><a href="dashboard.php">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li><li><a href="attendance.html">Attendance</a></li><li><a href="resources.html">Resources</a></li><li><a href="notice.html">Notices</a></li><li><a href="contact.php">Contact</a></li></ul>
            <div class="nav-actions"><a href="login.php" class="btn btn--ghost btn--sm" aria-current="page">Sign In</a><a href="registration.php" class="btn btn--primary btn--sm">Register</a></div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Welcome back</span><h1 class="t-h1">Sign in to StudentHub</h1><p class="t-lead">Access your student dashboard and academic tools.</p></div></div></header>
        <section class="section"><div class="container auth"><div class="auth__card">
            <div><h2 class="t-h2">Student sign in</h2><p class="t-muted">Use your registered account details.</p></div>
            <form action="login.php" method="post" class="form">
                <?php if (isset($_GET['logged_out'])): ?>
                    <div class="alert alert--success" role="status">You have been signed out.</div>
                <?php endif; ?>
                <?php if ($formError !== null): ?>
                    <div class="alert alert--danger" role="alert"><?= form_escape($formError) ?></div>
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                <label class="field"><span class="field__label">Username or email</span><input class="input" type="text" id="username" name="username" autocomplete="username" required></label>
                <label class="field"><span class="field__label">Password</span><input class="input" type="password" id="password" name="password" autocomplete="current-password" required></label>
                <label class="check"><input type="checkbox" name="remember_me"><span>Keep me signed in on this device for 30 days</span></label>
                <div class="form__actions"><button class="btn btn--primary btn--lg btn--block" type="submit">Sign in</button></div>
            </form>
            <p class="t-small t-muted t-center">New to StudentHub? <a href="registration.php">Create an account</a></p>
        </div></div></section>
    </main>
    <footer class="site-footer"><div class="container footer-grid"><div class="footer-brand"><a href="index.html" class="brand"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a><p>One place for your academic life at CSPIT.</p></div><nav class="footer-col" aria-label="Academic links"><h4>Academic</h4><ul><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li></ul></nav><nav class="footer-col" aria-label="Student links"><h4>StudentHub</h4><ul><li><a href="dashboard.php">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="contact.php">Contact</a></li></ul></nav><div class="footer-bottom"><p>&copy; 2026 StudentHub Portal.</p></div></div></footer>
</body>
</html>
