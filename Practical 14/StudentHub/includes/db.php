<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('STUDENTHUB_DB_HOST') ?: 'localhost';
$username = getenv('STUDENTHUB_DB_USER') ?: 'root';
$password = getenv('STUDENTHUB_DB_PASSWORD') ?: '';
$database = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
$port = (int) (getenv('STUDENTHUB_DB_PORT') ?: 3306);

try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub database connection failed: ' . $exception->getMessage());
    throw new RuntimeException('Unable to connect to the StudentHub database.', 0, $exception);
}
