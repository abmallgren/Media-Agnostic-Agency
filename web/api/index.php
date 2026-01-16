<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Basic CORS (adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$base     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // /api
$relative = '/' . ltrim(str_replace($base, '', $path), '/'); // e.g. /projects/2/upvote

switch (true) {
    // NEW: upvote route MUST come before the generic /projects route
    case preg_match('#^/projects/(\d+)/upvote/?$#', $relative, $m):
        require __DIR__ . '/services/projects.php';
        $projectId = (int)$m[1];
        handle_project_upvote($pdo, $method, $projectId);
        break;

    case preg_match('#^/projects/?$#', $relative):
        require __DIR__ . '/services/projects.php';
        handle_projects($pdo, $method);
        break;

    case preg_match('#^/my/projects/?$#', $relative):
        require __DIR__ . '/services/projects.php';
        handle_my_projects($pdo, $method);
        break;

    case preg_match('#^/intelligence/?$#', $relative):
        require __DIR__ . '/services/intelligence.php';
        handle_intelligence($pdo, $method);
        break;

    case preg_match('#^/journal/?#', $relative):
        require __DIR__ . '/services/journal.php';
        handle_journal($pdo, $method);
        break;

    case preg_match('#^/contact/?#', $relative):
        require __DIR__ . '/services/contact.php';
        handle_contact($pdo, $method);
        break;

    case preg_match('#^/auth/#', $relative):
        require __DIR__ . '/services/auth.php';
        handle_auth($pdo, $method, '/api' . $relative);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found', 'path' => $relative]);
        break;
}