<?php

declare(strict_types=1);

// Reuse the same config as migrations
require_once __DIR__ . '/../php/config.php';

$host = DB_HOST;
$port = DB_PORT;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASSWORD;

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}