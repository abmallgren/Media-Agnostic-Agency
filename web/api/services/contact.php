<?php

declare(strict_types=1);

require_once __DIR__ . '/../session.php';

function handle_contact(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'POST':
            handle_contact_post($pdo);
            break;

        case 'GET':
            handle_contact_get($pdo);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handle_contact_post(PDO $pdo): void
{
    ensure_session_started();

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (
        !is_array($body) ||
        empty($body['body']) ||
        empty($body['projectId'])
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        return;
    }

    $messageBody = trim((string)$body['body']);
    $projectId   = (int)$body['projectId'];
    $senderId    = (int)$_SESSION['user_id'];

    // Look up project owner (recipient)
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $projectId]);
    $ownerId = $stmt->fetchColumn();

    if ($ownerId === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
        return;
    }

    $recipientId = (int)$ownerId;

    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (sender_user_id, recipient_user_id, project_id, body)
        VALUES (:sender_user_id, :recipient_user_id, :project_id, :body)
    ");

    $stmt->execute([
        ':sender_user_id'    => $senderId,
        ':recipient_user_id' => $recipientId,
        ':project_id'        => $projectId,
        ':body'              => $messageBody,
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'ok']);
}

/**
 * GET /api/contact
 * Returns messages where the current user is the recipient.
 */
function handle_contact_get(PDO $pdo): void
{
    ensure_session_started();

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            cm.id,
            cm.body,
            cm.created_at AS sentAt,
            s.display_name AS senderName,
            r.display_name AS recipientName,
            p.name AS projectName
        FROM contact_messages cm
        JOIN users s ON s.id = cm.sender_user_id
        JOIN users r ON r.id = cm.recipient_user_id
        JOIN projects p ON p.id = cm.project_id
        WHERE cm.recipient_user_id = :uid
        ORDER BY cm.created_at DESC, cm.id DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $messages = $stmt->fetchAll();

    echo json_encode($messages);
}