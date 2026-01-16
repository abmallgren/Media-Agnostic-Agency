<?php

declare(strict_types=1);

require_once __DIR__ . '/../session.php';

function handle_auth(PDO $pdo, string $method, string $relativePath): void
{
    if ($relativePath === '/api/auth/user') {
        handle_auth_user($pdo, $method);
        return;
    }

    if ($relativePath === '/api/auth/login/google') {
        handle_auth_login_google($pdo, $method);
        return;
    }

    if ($relativePath === '/api/auth/callback/google') {
        handle_auth_callback_google($pdo, $method);
        return;
    }

    if ($relativePath === '/api/auth/logout') {
        handle_auth_logout($pdo, $method);
        return;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}

/**
 * GET /api/auth/user
 */
function handle_auth_user(PDO $pdo, string $method): void
{
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    ensure_session_started();

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT id, email, display_name
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }

    echo json_encode([
        'id'    => (int)$user['id'],
        'name'  => $user['display_name'],
        'email' => $user['email'],
    ]);
}

/**
 * GET /api/auth/login/google
 */
function handle_auth_login_google(PDO $pdo, string $method): void
{
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    ensure_session_started();

    $clientId    = GOOGLE_CLIENT_ID;
    $redirectUri = GOOGLE_REDIRECT_URI;

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth2_state'] = $state;

    $params = [
        'response_type' => 'code',
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'offline',
        'prompt'        => 'select_account',
    ];

    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

    header('Location: ' . $authUrl);
    exit;
}

/**
 * GET /api/auth/callback/google
 */
function handle_auth_callback_google(PDO $pdo, string $method): void
{
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    ensure_session_started();

    if (!isset($_GET['state'], $_SESSION['oauth2_state']) || $_GET['state'] !== $_SESSION['oauth2_state']) {
        unset($_SESSION['oauth2_state']);
        http_response_code(400);
        echo 'Invalid OAuth state.';
        return;
    }
    unset($_SESSION['oauth2_state']);

    if (!isset($_GET['code'])) {
        http_response_code(400);
        echo 'Missing authorization code.';
        return;
    }

    $code         = $_GET['code'];
    $clientId     = GOOGLE_CLIENT_ID;
    $clientSecret = GOOGLE_CLIENT_SECRET;
    $redirectUri  = GOOGLE_REDIRECT_URI;

    // 1) Exchange code for tokens
    $tokenBody = http_build_query([
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $tokenBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $rawToken = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($rawToken === false || $httpCode >= 400) {
        error_log('Token exchange failed: HTTP ' . $httpCode . ' body=' . $rawToken);
        http_response_code(500);
        echo 'Failed to exchange token.';
        return;
    }

    $tokenData = json_decode($rawToken, true);
    if (!is_array($tokenData) || empty($tokenData['access_token'])) {
        error_log('Invalid token response: ' . $rawToken);
        http_response_code(500);
        echo 'Invalid token response.';
        return;
    }

    $accessToken = $tokenData['access_token'];

    // 2) Fetch user info
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);
    $rawUserInfo = curl_exec($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($rawUserInfo === false || $httpCode >= 400) {
        error_log('Userinfo fetch failed: HTTP ' . $httpCode . ' body=' . $rawUserInfo);
        http_response_code(500);
        echo 'Failed to fetch user info.';
        return;
    }

    $userInfo = json_decode($rawUserInfo, true);
    if (!is_array($userInfo) || empty($userInfo['email'])) {
        error_log('Invalid user info: ' . $rawUserInfo);
        http_response_code(500);
        echo 'Invalid user info.';
        return;
    }

    $email = $userInfo['email'];
    $name  = $userInfo['name'] ?? ($userInfo['given_name'] ?? 'Google User');

    // 3) Upsert into users table
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        $userId = (int)$existingId;
        $update = $pdo->prepare("
            UPDATE users
            SET display_name = :display_name
            WHERE id = :id
        ");
        $update->execute([
            ':display_name' => $name,
            ':id'           => $userId,
        ]);
    } else {
        $insert = $pdo->prepare("
            INSERT INTO users (email, display_name)
            VALUES (:email, :display_name)
        ");
        $insert->execute([
            ':email'        => $email,
            ':display_name' => $name
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    // 4) Set session and redirect to SPA root
    $_SESSION['user_id'] = $userId;

    header('Location: /');
    exit;
}

/**
 * GET /api/auth/logout
 */
function handle_auth_logout(PDO $pdo, string $method): void
{
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    ensure_session_started();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();

    header('Location: /');
    exit;
}