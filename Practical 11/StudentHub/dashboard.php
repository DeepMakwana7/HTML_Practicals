<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

auth_require_user($conn, ['student']);
$conn->close();
$fullName = (string) $_SESSION['full_name'];
$firstName = explode(' ', trim($fullName))[0];
$initials = strtoupper(substr($firstName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your secure StudentHub student dashboard.">
    <title>Dashboard | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <ul class="nav-list"><li><a href="index.html">Home</a></li><li><a href="about.html">About</a></li><li><a href="registration.php">Registration</a></li><li><a href="dashboard.php" aria-current="page">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li><li><a href="attendance.html">Attendance</a></li><li><a href="resources.html">Resources</a></li><li><a href="notice.html">Notices</a></li><li><a href="contact.php">Contact</a></li></ul>
            <div class="nav-actions">
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Student overview</span><h1 class="t-h1">Dashboard</h1><p class="t-lead">Your StudentHub account and academic tools.</p></div></div></header>
        <section class="section"><div class="container dash-layout">
            <aside class="dash-side" aria-label="Academic dashboard navigation">
                <h2 class="dash-side__title">Academic tools</h2>
                <ul><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li><li><a href="attendance.html">Attendance</a></li><li><a href="resources.html">Study resources</a></li><li><a href="notice.html">Notices</a></li></ul>
            </aside>
            <div class="stack stack--lg">
                <section class="profile">
                    <span class="avatar avatar--lg" aria-hidden="true"><?= form_escape($initials) ?></span>
                    <div class="stack stack--sm">
                        <h2 class="t-h2">Welcome, <?= form_escape($firstName) ?></h2>
                        <p><?= form_escape((string) $_SESSION['email']) ?></p>
                        <span class="badge">Student account</span>
                    </div>
                </section>
                <section class="card">
                    <h2 class="t-h2">Account details</h2>
                    <dl class="detail-list">
                        <div><dt>Name</dt><dd><?= form_escape($fullName) ?></dd></div>
                        <div><dt>Username</dt><dd><?= form_escape((string) $_SESSION['username']) ?></dd></div>
                        <div><dt>Email</dt><dd><?= form_escape((string) $_SESSION['email']) ?></dd></div>
                    </dl>
                </section>
            </div>
        </div></section>
    </main>
</body>
</html>
