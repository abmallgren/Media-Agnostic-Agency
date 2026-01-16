<?php

declare(strict_types=1);

require_once __DIR__ . '/../session.php';

function handle_projects(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            handle_projects_get($pdo);
            break;

        case 'POST':
            handle_projects_post($pdo);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handle_project_upvote(PDO $pdo, string $method, int $projectId): void
{
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    ensure_session_started();

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }

    $userId = (int)$_SESSION['user_id'];

    // Ensure project exists and owner id
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

    // Owner cannot upvote own project
    if ((int)$ownerId === $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot upvote your own project']);
        return;
    }

    // Insert vote if not exists
    $insert = $pdo->prepare("
        INSERT IGNORE INTO project_votes (project_id, user_id)
        VALUES (:project_id, :user_id)
    ");
    $insert->execute([
        ':project_id' => $projectId,
        ':user_id'    => $userId,
    ]);

    // Return updated project DTO
    $project = fetch_project_with_flags($pdo, $projectId, $userId);
    if ($project === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
        return;
    }

    http_response_code(200);
    echo json_encode($project);
}

function handle_projects_get(PDO $pdo): void
{
    ensure_session_started();
    $currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $stmt = $pdo->query("
        SELECT
            id,
            user_id,
            name,
            description,
            involvement_sought AS involvementSought,
            created_at
        FROM projects
        ORDER BY created_at DESC, id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projects = [];
    foreach ($rows as $row) {
        $projects[] = project_row_to_dto($pdo, $row, $currentUserId);
    }

    echo json_encode($projects);
}

function handle_projects_post(PDO $pdo): void
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
        empty($body['name']) ||
        empty($body['description']) ||
        empty($body['involvementSought'])
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        return;
    }

    $name              = trim((string)$body['name']);
    $description       = trim((string)$body['description']);
    $involvementSought = trim((string)$body['involvementSought']);
    $userId            = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO projects (user_id, name, description, involvement_sought)
        VALUES (:user_id, :name, :description, :involvement_sought)
    ");

    $stmt->execute([
        ':user_id'            => $userId,
        ':name'               => $name,
        ':description'        => $description,
        ':involvement_sought' => $involvementSought,
    ]);

    $id = (int)$pdo->lastInsertId();

    // Load row back and map to DTO so flags are consistent
    $rowStmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            name,
            description,
            involvement_sought AS involvementSought,
            created_at
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");
    $rowStmt->execute([':id' => $id]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

    $project = $row ? project_row_to_dto($pdo, $row, $userId) : null;

    http_response_code(201);
    echo json_encode($project);
}

/**
 * Map a single DB row to the Project DTO with canEdit / canUpvote / upvotesLast7Days.
 *
 * @param array<string,mixed> $row
 */
function project_row_to_dto(PDO $pdo, array $row, ?int $currentUserId): array
{
    $projectId = (int)$row['id'];
    $ownerId   = (int)$row['user_id'];

    // Upvotes in last 7 days
    $votesStmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM project_votes
        WHERE project_id = :pid
          AND created_at >= (NOW() - INTERVAL 7 DAY)
    ");
    $votesStmt->execute([':pid' => $projectId]);
    $upvotesLast7Days = (int)($votesStmt->fetchColumn() ?: 0);

    $canEdit   = $currentUserId !== null && $currentUserId === $ownerId;
    $canUpvote = $currentUserId !== null && $currentUserId !== $ownerId;

    return [
        'id'                => $projectId,
        'ownerId'           => $ownerId,
        'name'              => $row['name'],
        'description'       => $row['description'],
        'involvementSought' => $row['involvementSought'],
        'isActive'          => true,
        'upvotesLast7Days'  => $upvotesLast7Days,
        'canEdit'           => $canEdit,
        'canUpvote'         => $canUpvote,
    ];
}

/**
 * Fetch a single project row and convert to DTO.
 */
function fetch_project_with_flags(PDO $pdo, int $projectId, int $currentUserId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            name,
            description,
            involvement_sought AS involvementSought,
            created_at
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    return project_row_to_dto($pdo, $row, $currentUserId);
}