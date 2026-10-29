<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

auth_require_user($conn, ['admin']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StudentHub administrator landing page.">
    <title>Administrator | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Administrator</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <div class="nav-actions">
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Administrator</span><h1 class="t-h1">Welcome, <?= form_escape((string) $_SESSION['full_name']) ?></h1><p class="t-lead">You are signed in with administrator access.</p></div></div></header>
        <section class="section section--tight">
            <div class="container">
                <div class="card">
                    <span class="t-eyebrow">Administration</span>
                    <h2 class="t-h2">Student records</h2>
                    <p class="t-muted">Create and manage student accounts, search the directory, and filter records by course or year.</p>
                    <div class="btn-group">
                        <a class="btn btn--primary btn--lg" href="student-management.php">Open student management</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
