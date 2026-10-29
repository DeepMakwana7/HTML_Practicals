<?php
function form_post_value(string $field): ?string
{
    $value = $_POST[$field] ?? null;

    return is_string($value) ? trim($value) : null;
}

function form_post_raw_value(string $field): ?string
{
    $value = $_POST[$field] ?? null;

    return is_string($value) ? $value : null;
}

function form_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function append_json_record(string $path, array $record): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the storage directory.');
    }

    $file = fopen($path, 'c+');
    if ($file === false) {
        throw new RuntimeException('Unable to open the storage file.');
    }

    $locked = false;
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Unable to lock the storage file.');
        }
        $locked = true;

        rewind($file);
        $contents = stream_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the storage file.');
        }

        if ($contents === '') {
            $records = [];
        } else {
            $records = json_decode($contents, true);
            if (!is_array($records) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('The storage file contains invalid JSON.');
            }
        }

        $records[] = $record;
        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode the record as JSON.');
        }

        rewind($file);
        if (!ftruncate($file, 0)) {
            throw new RuntimeException('Unable to prepare the storage file for writing.');
        }

        $written = 0;
        $length = strlen($json . PHP_EOL);
        $output = $json . PHP_EOL;
        while ($written < $length) {
            $bytes = fwrite($file, substr($output, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('Unable to write the record.');
            }
            $written += $bytes;
        }

        if (!fflush($file)) {
            throw new RuntimeException('Unable to flush the storage file.');
        }
    } finally {
        if ($locked) {
            flock($file, LOCK_UN);
        }
        fclose($file);
    }
}
