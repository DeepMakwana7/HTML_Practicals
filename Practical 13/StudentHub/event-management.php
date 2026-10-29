<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/events.php';

auth_require_user($conn, ['admin']);
$conn->close();

$errors = [];
$notice = null;
$editId = isset($_GET['edit']) && is_string($_GET['edit']) ? $_GET['edit'] : null;
$formValues = [
    'title' => '',
    'category' => '',
    'date' => '',
    'description' => '',
    'location' => '',
    'time' => '',
    'note' => '',
    'link' => '',
    'linkText' => '',
];

try {
    $events = events_read_all();
} catch (RuntimeException $exception) {
    $events = [];
    $errors[] = $exception->getMessage();
}

if ($editId !== null) {
    $foundEditEvent = false;
    foreach ($events as $event) {
        if (isset($event['id']) && hash_equals((string) $event['id'], $editId)) {
            $formValues = array_merge($formValues, $event);
            $foundEditEvent = true;
            break;
        }
    }
    if (!$foundEditEvent) {
        $errors[] = 'The selected event could not be found.';
        $editId = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = form_post_value('action');
    if (!auth_valid_csrf_token(form_post_value('csrf_token'))) {
        $errors[] = 'Your session token is invalid. Refresh the page and try again.';
    } elseif (!in_array($action, ['create', 'update', 'delete'], true)) {
        $errors[] = 'The requested action is not supported.';
    } else {
        try {
            $events = events_read_all();
            $id = form_post_value('id');

            if ($action === 'delete') {
                if ($id === null || !preg_match('/^[a-f0-9]{32}$/D', $id)) {
                    throw new RuntimeException('The selected event could not be found.');
                }
                $index = null;
                foreach ($events as $eventIndex => $event) {
                    if (isset($event['id']) && hash_equals((string) $event['id'], $id)) {
                        $index = $eventIndex;
                        break;
                    }
                }
                if ($index === null) {
                    throw new RuntimeException('The selected event could not be found.');
                }
                $oldPoster = $events[$index]['poster'] ?? null;
                array_splice($events, $index, 1);
                events_write_all($events);
                events_remove_poster(is_string($oldPoster) ? $oldPoster : null);
                $notice = 'Event deleted.';
                $editId = null;
            } else {
                $formValues = [
                    'title' => form_post_value('title') ?? '',
                    'category' => form_post_value('category') ?? '',
                    'date' => form_post_value('date') ?? '',
                    'description' => form_post_value('description') ?? '',
                    'location' => form_post_value('location') ?? '',
                    'time' => form_post_value('time') ?? '',
                    'note' => form_post_value('note') ?? '',
                    'link' => form_post_value('link') ?? '',
                    'linkText' => form_post_value('linkText') ?? '',
                ];

                $limits = [
                    'title' => 150,
                    'category' => 50,
                    'description' => 2000,
                    'location' => 150,
                    'time' => 80,
                    'note' => 255,
                    'link' => 2048,
                    'linkText' => 100,
                ];
                foreach ($limits as $field => $limit) {
                    if (strlen($formValues[$field]) > $limit) {
                        $errors[] = ucfirst($field) . ' must be ' . $limit . ' characters or fewer.';
                    }
                }
                foreach (['title', 'category', 'date', 'description', 'location', 'time'] as $requiredField) {
                    if ($formValues[$requiredField] === '') {
                        $errors[] = ucfirst($requiredField) . ' is required.';
                    }
                }

                $dateParts = explode('-', $formValues['date']);
                if (
                    count($dateParts) !== 3
                    || !checkdate((int) ($dateParts[1] ?? 0), (int) ($dateParts[2] ?? 0), (int) ($dateParts[0] ?? 0))
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $formValues['date'])
                ) {
                    $errors[] = 'Enter a valid event date.';
                }
                if (($formValues['link'] === '') !== ($formValues['linkText'] === '')) {
                    $errors[] = 'Provide both a link and link text, or leave both blank.';
                }
                if ($formValues['link'] !== '') {
                    $linkScheme = parse_url($formValues['link'], PHP_URL_SCHEME);
                    $isHttpUrl = in_array(strtolower((string) $linkScheme), ['http', 'https'], true)
                        && filter_var($formValues['link'], FILTER_VALIDATE_URL);
                    $isRelativeLink = $linkScheme === null
                        && preg_match('~^(?!/)(?!.*://)[a-zA-Z0-9_./#?=&%-]+$~D', $formValues['link']);
                    if (!$isHttpUrl && !$isRelativeLink) {
                        $errors[] = 'Enter a valid HTTP(S) URL or a relative site link.';
                    }
                }

                $editIndex = null;
                if ($action === 'update') {
                    if ($id === null || !preg_match('/^[a-f0-9]{32}$/D', $id)) {
                        $errors[] = 'The selected event could not be found.';
                    } else {
                        foreach ($events as $eventIndex => $event) {
                            if (isset($event['id']) && hash_equals((string) $event['id'], $id)) {
                                $editIndex = $eventIndex;
                                break;
                            }
                        }
                        if ($editIndex === null) {
                            $errors[] = 'The selected event could not be found.';
                        }
                    }
                }

                if ($errors === []) {
                    $poster = events_store_poster(is_array($_FILES['poster'] ?? null) ? $_FILES['poster'] : []);
                    $eventData = $formValues;
                    $eventData['id'] = $action === 'create' ? bin2hex(random_bytes(16)) : $id;
                    $oldPoster = $action === 'update' ? ($events[$editIndex]['poster'] ?? null) : null;
                    if ($poster !== null) {
                        $eventData['poster'] = $poster;
                    } elseif ($action === 'update' && isset($events[$editIndex]['poster'])) {
                        $eventData['poster'] = $events[$editIndex]['poster'];
                    }

                    if ($action === 'create') {
                        $events[] = $eventData;
                    } else {
                        $events[$editIndex] = $eventData;
                    }

                    try {
                        events_write_all($events);
                    } catch (RuntimeException $exception) {
                        if ($poster !== null) {
                            events_remove_poster($poster);
                        }
                        throw $exception;
                    }

                    if ($poster !== null && is_string($oldPoster)) {
                        events_remove_poster($oldPoster);
                    }
                    $notice = $action === 'create' ? 'Event created.' : 'Event updated.';
                    $formValues = [
                        'title' => '',
                        'category' => '',
                        'date' => '',
                        'description' => '',
                        'location' => '',
                        'time' => '',
                        'note' => '',
                        'link' => '',
                        'linkText' => '',
                    ];
                    $editId = null;
                }
            }
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

if ($errors === [] && $notice === null) {
    try {
        $events = events_read_all();
    } catch (RuntimeException $exception) {
        $errors[] = $exception->getMessage();
    }
}
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
        <section class="section section--tight"><div class="container event-management">
            <?php if ($errors !== []): ?>
                <div class="alert alert--danger" role="alert"><div><strong>Unable to complete the request:</strong><ul><?php foreach ($errors as $error): ?><li><?= form_escape($error) ?></li><?php endforeach; ?></ul></div></div>
            <?php elseif ($notice !== null): ?>
                <div class="alert alert--success" role="status"><?= form_escape($notice) ?></div>
            <?php endif; ?>

            <section class="card" aria-labelledby="event-form-heading">
                <span class="t-eyebrow"><?= $editId === null ? 'New event' : 'Edit event' ?></span>
                <h2 class="t-h2" id="event-form-heading"><?= $editId === null ? 'Add an event' : 'Update event' ?></h2>
                <form method="post" action="event-management.php<?= $editId !== null ? '?edit=' . rawurlencode($editId) : '' ?>" class="form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $editId === null ? 'create' : 'update' ?>">
                    <?php if ($editId !== null): ?><input type="hidden" name="id" value="<?= form_escape($editId) ?>"><?php endif; ?>
                    <div class="event-form-grid">
                        <div class="field"><label class="field__label" for="event-title">Title</label><input class="input" id="event-title" name="title" maxlength="150" required value="<?= form_escape((string) $formValues['title']) ?>"></div>
                        <div class="field"><label class="field__label" for="event-category">Category</label><input class="input" id="event-category" name="category" maxlength="50" required value="<?= form_escape((string) $formValues['category']) ?>"></div>
                        <div class="field"><label class="field__label" for="event-date">Date</label><input class="input" id="event-date" name="date" type="date" required value="<?= form_escape((string) $formValues['date']) ?>"></div>
                        <div class="field"><label class="field__label" for="event-time">Time</label><input class="input" id="event-time" name="time" maxlength="80" required value="<?= form_escape((string) $formValues['time']) ?>"></div>
                        <div class="field"><label class="field__label" for="event-location">Location</label><input class="input" id="event-location" name="location" maxlength="150" required value="<?= form_escape((string) $formValues['location']) ?>"></div>
                        <div class="field"><label class="field__label" for="event-note">Note</label><input class="input" id="event-note" name="note" maxlength="255" value="<?= form_escape((string) $formValues['note']) ?>"></div>
                        <div class="field event-form-grid__wide"><label class="field__label" for="event-description">Description</label><textarea class="textarea" id="event-description" name="description" maxlength="2000" required><?= form_escape((string) $formValues['description']) ?></textarea></div>
                        <div class="field"><label class="field__label" for="event-link">Optional link</label><input class="input" id="event-link" name="link" maxlength="2048" value="<?= form_escape((string) $formValues['link']) ?>" placeholder="contact.php or https://example.com"></div>
                        <div class="field"><label class="field__label" for="event-link-text">Link text</label><input class="input" id="event-link-text" name="linkText" maxlength="100" value="<?= form_escape((string) $formValues['linkText']) ?>"></div>
                        <div class="field event-form-grid__wide">
                            <label class="field__label" for="event-poster">Poster image</label>
                            <input class="input" id="event-poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp">
                            <span class="field__hint">JPEG, PNG, or WebP; maximum 5 MB. Leave blank to keep the current poster<?= !empty($formValues['poster']) ? '.' : ' or skip uploading one.' ?></span>
                            <?php if (!empty($formValues['poster'])): ?><img class="event-admin-poster" src="<?= form_escape((string) $formValues['poster']) ?>" alt="Current event poster"><?php endif; ?>
                        </div>
                    </div>
                    <div class="form__actions">
                        <button class="btn btn--primary" type="submit"><?= $editId === null ? 'Add event' : 'Save changes' ?></button>
                        <?php if ($editId !== null): ?><a class="btn btn--ghost" href="event-management.php">Cancel edit</a><?php endif; ?>
                    </div>
                </form>
            </section>

            <section aria-labelledby="event-list-heading">
                <div class="cluster cluster--between event-management__heading"><div><span class="t-eyebrow">Current events</span><h2 class="t-h2" id="event-list-heading">Manage events</h2></div><a class="btn btn--ghost" href="events.html">View public events</a></div>
                <?php if ($events === []): ?>
                    <p class="alert alert--info">No events have been added yet.</p>
                <?php else: ?>
                    <div class="grid grid--2">
                        <?php foreach ($events as $event): ?>
                            <?php if (!is_array($event) || !isset($event['id'])) { continue; } ?>
                            <article class="card event-admin-card">
                                <?php if (!empty($event['poster'])): ?><img class="event-admin-poster" src="<?= form_escape((string) $event['poster']) ?>" alt="Poster for <?= form_escape((string) ($event['title'] ?? 'event')) ?>"><?php endif; ?>
                                <span class="badge badge--brand"><?= form_escape((string) ($event['category'] ?? '')) ?></span>
                                <h3 class="card__title"><?= form_escape((string) ($event['title'] ?? '')) ?></h3>
                                <p class="card__meta"><?= form_escape((string) ($event['date'] ?? '')) ?> · <?= form_escape((string) ($event['location'] ?? '')) ?></p>
                                <p><?= form_escape((string) ($event['description'] ?? '')) ?></p>
                                <div class="form__actions">
                                    <a class="btn btn--soft btn--sm" href="event-management.php?edit=<?= rawurlencode((string) $event['id']) ?>">Edit</a>
                                    <form method="post" action="event-management.php" onsubmit="return confirm('Delete this event?');">
                                        <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= form_escape((string) $event['id']) ?>">
                                        <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div></section>
    </main>
</body>
</html>
