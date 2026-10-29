<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function events_api_respond(int $status, array $response): void
{
    http_response_code($status);
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function events_api_validate(array $input): array
{
    $fields = ['title', 'category', 'date', 'description', 'location', 'time', 'note', 'link', 'linkText'];
    $event = [];
    foreach ($fields as $field) {
        $event[$field] = isset($input[$field]) && is_string($input[$field]) ? trim($input[$field]) : '';
    }

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
    $errors = [];
    foreach ($limits as $field => $limit) {
        if (strlen($event[$field]) > $limit) {
            $errors[] = ucfirst($field) . ' must be ' . $limit . ' characters or fewer.';
        }
    }

    foreach (['title', 'category', 'date', 'description', 'location', 'time'] as $requiredField) {
        if ($event[$requiredField] === '') {
            $errors[] = ucfirst($requiredField) . ' is required.';
        }
    }

    $dateParts = explode('-', $event['date']);
    if (
        count($dateParts) !== 3
        || !checkdate((int) ($dateParts[1] ?? 0), (int) ($dateParts[2] ?? 0), (int) ($dateParts[0] ?? 0))
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $event['date'])
    ) {
        $errors[] = 'Enter a valid event date.';
    }

    if (($event['link'] === '') !== ($event['linkText'] === '')) {
        $errors[] = 'Provide both a link and link text, or leave both blank.';
    }
    if ($event['link'] !== '') {
        $scheme = parse_url($event['link'], PHP_URL_SCHEME);
        $isHttpUrl = in_array(strtolower((string) $scheme), ['http', 'https'], true)
            && filter_var($event['link'], FILTER_VALIDATE_URL);
        $isRelativeLink = $scheme === null
            && preg_match('~^(?!/)(?!.*://)[a-zA-Z0-9_./#?=&%-]+$~D', $event['link']);
        if (!$isHttpUrl && !$isRelativeLink) {
            $errors[] = 'Enter a valid HTTP(S) URL or a relative site link.';
        }
    }

    if ($errors !== []) {
        events_api_respond(422, ['success' => false, 'errors' => $errors]);
    }

    return $event;
}

try {
    require_once dirname(__DIR__) . '/includes/auth.php';
    require_once dirname(__DIR__) . '/includes/db.php';
    require_once dirname(__DIR__) . '/includes/events.php';

    auth_start_session();
    if (!isset($_SESSION['user_id'])) {
        auth_restore_remembered_user($conn);
    }
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        events_api_respond(401, ['success' => false, 'message' => 'Sign in as an administrator to continue.']);
    }
    if ($_SESSION['role'] !== 'admin') {
        events_api_respond(403, ['success' => false, 'message' => 'Administrator access is required.']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        events_api_respond(200, ['success' => true, 'events' => events_read_all()]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        events_api_respond(405, ['success' => false, 'message' => 'Use GET to list events or POST to manage them.']);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    if (!auth_valid_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
        events_api_respond(403, ['success' => false, 'message' => 'Your session token is invalid. Refresh the page and try again.']);
    }

    $input = $_POST;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === 0) {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($decoded)) {
            events_api_respond(400, ['success' => false, 'message' => 'The request body must contain a JSON object.']);
        }
        $input = $decoded;
    }

    $action = $input['action'] ?? null;
    if (!is_string($action) || !in_array($action, ['add', 'update', 'delete'], true)) {
        events_api_respond(400, ['success' => false, 'message' => 'Choose add, update, or delete.']);
    }

    $events = events_read_all();
    $id = isset($input['id']) && is_string($input['id']) ? $input['id'] : '';
    $eventIndex = null;
    if ($action !== 'add') {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
            events_api_respond(404, ['success' => false, 'message' => 'The selected event could not be found.']);
        }
        foreach ($events as $index => $event) {
            if (isset($event['id']) && hash_equals((string) $event['id'], $id)) {
                $eventIndex = $index;
                break;
            }
        }
        if ($eventIndex === null) {
            events_api_respond(404, ['success' => false, 'message' => 'The selected event could not be found.']);
        }
    }

    if ($action === 'delete') {
        $oldPoster = $events[$eventIndex]['poster'] ?? null;
        array_splice($events, $eventIndex, 1);
        events_write_all($events);
        events_remove_poster(is_string($oldPoster) ? $oldPoster : null);
        events_api_respond(200, ['success' => true, 'message' => 'Event deleted.', 'events' => $events]);
    }

    $eventData = events_api_validate($input);
    $uploadedPoster = events_store_poster(is_array($_FILES['poster'] ?? null) ? $_FILES['poster'] : []);
    $oldPoster = $action === 'update' ? ($events[$eventIndex]['poster'] ?? null) : null;
    $eventData['id'] = $action === 'add' ? bin2hex(random_bytes(16)) : $id;
    if ($uploadedPoster !== null) {
        $eventData['poster'] = $uploadedPoster;
    } elseif ($action === 'update' && isset($events[$eventIndex]['poster'])) {
        $eventData['poster'] = $events[$eventIndex]['poster'];
    }

    if ($action === 'add') {
        $events[] = $eventData;
    } else {
        $events[$eventIndex] = $eventData;
    }

    try {
        events_write_all($events);
    } catch (RuntimeException $exception) {
        if ($uploadedPoster !== null) {
            events_remove_poster($uploadedPoster);
        }
        throw $exception;
    }

    if ($uploadedPoster !== null && is_string($oldPoster)) {
        events_remove_poster($oldPoster);
    }

    events_api_respond(200, [
        'success' => true,
        'message' => $action === 'add' ? 'Event created.' : 'Event updated.',
        'events' => $events,
    ]);
} catch (Throwable $exception) {
    error_log('StudentHub events API failed: ' . $exception->getMessage());
    events_api_respond(500, ['success' => false, 'message' => 'Unable to process events right now. Please try again.']);
}
