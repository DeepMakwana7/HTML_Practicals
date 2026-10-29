<?php
const EVENT_POSTER_MAX_BYTES = 5242880;

function events_file_path(): string
{
    return dirname(__DIR__) . '/data/events.json';
}

function events_read_all(): array
{
    $file = fopen(events_file_path(), 'rb');
    if ($file === false) {
        throw new RuntimeException('Unable to open the events file.');
    }

    try {
        if (!flock($file, LOCK_SH)) {
            throw new RuntimeException('Unable to lock the events file.');
        }

        $contents = stream_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the events file.');
        }

        $events = json_decode($contents, true);
        if (!is_array($events) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('The events file contains invalid JSON.');
        }

        return $events;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

function events_write_all(array $events): void
{
    $file = fopen(events_file_path(), 'c+');
    if ($file === false) {
        throw new RuntimeException('Unable to open the events file for writing.');
    }

    $locked = false;
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Unable to lock the events file.');
        }
        $locked = true;

        $json = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode the events.');
        }

        rewind($file);
        $output = $json . PHP_EOL;
        if (!ftruncate($file, 0)) {
            throw new RuntimeException('Unable to save the events file.');
        }
        $written = 0;
        while ($written < strlen($output)) {
            $bytes = fwrite($file, substr($output, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('Unable to save the events file.');
            }
            $written += $bytes;
        }
        if (!fflush($file)) {
            throw new RuntimeException('Unable to save the events file.');
        }
    } finally {
        if ($locked) {
            flock($file, LOCK_UN);
        }
        fclose($file);
    }
}

function events_store_poster(array $upload): ?string
{
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('The poster must be no larger than 5 MB.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The poster upload failed. Please choose another image and try again.');
    }

    $temporaryPath = $upload['tmp_name'] ?? null;
    $fileSize = $upload['size'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath) || !is_int($fileSize)) {
        throw new RuntimeException('The uploaded poster is invalid.');
    }
    if ($fileSize < 1 || $fileSize > EVENT_POSTER_MAX_BYTES) {
        throw new RuntimeException('The poster must be no larger than 5 MB.');
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
        throw new RuntimeException('Poster files must be JPEG, PNG, or WebP images.');
    }

    $directory = dirname(__DIR__) . '/uploads/event-posters';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the poster upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('Unable to save the uploaded poster.');
    }

    return 'uploads/event-posters/' . $filename;
}

function events_remove_poster(?string $poster): void
{
    if (!is_string($poster) || !preg_match('~^uploads/event-posters/[a-f0-9]{32}\.(jpg|png|webp)$~D', $poster)) {
        return;
    }

    $path = dirname(__DIR__) . '/' . $poster;
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('Unable to remove the event poster.');
    }
}
