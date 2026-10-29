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
    <meta name="description" content="Create and manage StudentHub campus events.">
    <title>Event Management | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
    <script src="js/event-management.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Administrator</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <div class="nav-actions">
                <a class="btn btn--ghost btn--sm" href="admin-dashboard.php">Admin dashboard</a>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Administration</span><h1 class="t-h1">Event management</h1><p class="t-lead">Create and maintain campus events and their posters.</p></div></div></header>
        <section class="section section--tight"><div class="container event-management" data-event-manager data-api="api/events.php" data-csrf-token="<?= form_escape(auth_csrf_token()) ?>">
            <p class="alert" data-event-status role="status" aria-live="polite" hidden></p>

            <section class="card" aria-labelledby="event-form-heading">
                <span class="t-eyebrow" data-form-eyebrow>New event</span>
                <h2 class="t-h2" id="event-form-heading" data-form-heading>Add an event</h2>
                <form data-event-form class="form" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="event-form-grid">
                        <div class="field"><label class="field__label" for="event-title">Title</label><input class="input" id="event-title" name="title" maxlength="150" required></div>
                        <div class="field"><label class="field__label" for="event-category">Category</label><input class="input" id="event-category" name="category" maxlength="50" required></div>
                        <div class="field"><label class="field__label" for="event-date">Date</label><input class="input" id="event-date" name="date" type="date" required></div>
                        <div class="field"><label class="field__label" for="event-time">Time</label><input class="input" id="event-time" name="time" maxlength="80" required></div>
                        <div class="field"><label class="field__label" for="event-location">Location</label><input class="input" id="event-location" name="location" maxlength="150" required></div>
                        <div class="field"><label class="field__label" for="event-note">Note</label><input class="input" id="event-note" name="note" maxlength="255"></div>
                        <div class="field event-form-grid__wide"><label class="field__label" for="event-description">Description</label><textarea class="textarea" id="event-description" name="description" maxlength="2000" required></textarea></div>
                        <div class="field"><label class="field__label" for="event-link">Optional link</label><input class="input" id="event-link" name="link" maxlength="2048" placeholder="contact.php or https://example.com"></div>
                        <div class="field"><label class="field__label" for="event-link-text">Link text</label><input class="input" id="event-link-text" name="linkText" maxlength="100"></div>
                        <div class="field event-form-grid__wide">
                            <label class="field__label" for="event-poster">Poster image</label>
                            <input class="input" id="event-poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp">
                            <span class="field__hint">JPEG, PNG, or WebP; maximum 5 MB. Leave blank to keep the current poster or skip uploading one.</span>
                            <img class="event-admin-poster" data-current-poster alt="Current event poster" hidden>
                        </div>
                    </div>
                    <div class="form__actions">
                        <button class="btn btn--primary" type="submit" data-submit-event>Add event</button>
                        <button class="btn btn--ghost" type="button" data-cancel-edit hidden>Cancel edit</button>
                    </div>
                </form>
            </section>

            <section aria-labelledby="event-list-heading">
                <div class="cluster cluster--between event-management__heading"><div><span class="t-eyebrow">Current events</span><h2 class="t-h2" id="event-list-heading">Manage events</h2></div><a class="btn btn--ghost" href="events.html">View public events</a></div>
                <p class="data-status" data-event-list-status role="status" aria-live="polite">Loading events…</p>
                <div class="grid grid--2" data-event-list></div>
            </section>
        </div></section>
    </main>
</body>
</html>
