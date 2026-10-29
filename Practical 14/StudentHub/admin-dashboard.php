<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/events.php';
require_once __DIR__ . '/includes/navigation.php';

auth_require_user($conn, ['admin']);

$statsStatement = $conn->prepare(
    "SELECT COUNT(*),
            COALESCE(SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END), 0),
            COALESCE(SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END), 0)
     FROM students"
);
$statsStatement->execute();
$statsStatement->bind_result($userCount, $studentCount, $adminCount);
$statsStatement->fetch();
$statsStatement->close();

$registrationsStatement = $conn->prepare(
    "SELECT full_name, username, email, DATE_FORMAT(created_at, '%b %e, %Y')
     FROM students
     WHERE role = 'student'
     ORDER BY created_at DESC, id DESC
     LIMIT 5"
);
$registrationsStatement->execute();
$registrationsStatement->bind_result($registrationName, $registrationUsername, $registrationEmail, $registrationDate);
$latestRegistrations = [];
while ($registrationsStatement->fetch()) {
    $latestRegistrations[] = [
        'name' => $registrationName,
        'username' => $registrationUsername,
        'email' => $registrationEmail,
        'date' => $registrationDate,
    ];
}
$registrationsStatement->close();

$events = events_read_all();
$eventCount = count($events);
$menuItems = navigation_items_for_role((string) $_SESSION['role']);
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
            <ul class="nav-list">
                <?php foreach ($menuItems as $item): ?>
                    <li><a href="<?= form_escape($item['href']) ?>"<?= $item['href'] === 'admin-dashboard.php' ? ' aria-current="page"' : '' ?>><?= form_escape($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="nav-actions">
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Administrator overview</span><h1 class="t-h1">Welcome, <?= form_escape((string) $_SESSION['full_name']) ?></h1><p class="t-lead">Manage StudentHub accounts and events from one place.</p></div></div></header>
        <section class="section">
            <div class="container dash-layout">
                <aside class="dash-side" aria-label="Administrator navigation">
                    <h2 class="dash-side__title">Administration</h2>
                    <ul>
                        <?php foreach (array_slice($menuItems, 1) as $item): ?>
                            <li><a href="<?= form_escape($item['href']) ?>"<?= $item['href'] === 'admin-dashboard.php' ? ' aria-current="page"' : '' ?>><?= form_escape($item['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
                <div class="stack stack--lg">
                    <section class="grid grid--4" aria-label="User and event statistics">
                        <article class="stat"><span class="stat__value"><?= number_format((int) $userCount) ?></span><span class="stat__label">Total users</span></article>
                        <article class="stat"><span class="stat__value"><?= number_format((int) $studentCount) ?></span><span class="stat__label">Students</span></article>
                        <article class="stat"><span class="stat__value"><?= number_format((int) $adminCount) ?></span><span class="stat__label">Administrators</span></article>
                        <article class="stat"><span class="stat__value"><?= number_format($eventCount) ?></span><span class="stat__label">Events</span></article>
                    </section>
                    <section class="stack stack--sm" aria-labelledby="latest-registrations-heading">
                        <div class="cluster cluster--between">
                            <div>
                                <span class="t-eyebrow">New accounts</span>
                                <h2 class="t-h2" id="latest-registrations-heading">Latest registrations</h2>
                            </div>
                            <a class="btn btn--ghost btn--sm" href="student-management.php">View students</a>
                        </div>
                        <div class="table-wrap">
                            <table class="table">
                                <caption>Most recently registered student accounts</caption>
                                <thead><tr><th scope="col">Student</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Registered</th></tr></thead>
                                <tbody>
                                    <?php if ($latestRegistrations === []): ?>
                                        <tr><td colspan="4">No student registrations yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($latestRegistrations as $registration): ?>
                                            <tr>
                                                <th scope="row"><?= form_escape((string) $registration['name']) ?></th>
                                                <td><?= form_escape((string) $registration['username']) ?></td>
                                                <td><?= form_escape((string) $registration['email']) ?></td>
                                                <td><?= form_escape((string) $registration['date']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <section class="card">
                        <span class="t-eyebrow">Administration</span>
                        <h2 class="t-h2">Manage StudentHub</h2>
                        <p class="t-muted">Create and manage student accounts, or update events that appear on the public events page.</p>
                        <div class="btn-group">
                            <a class="btn btn--primary" href="student-management.php">Student management</a>
                            <a class="btn btn--ghost" href="event-management.php">Event management</a>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
