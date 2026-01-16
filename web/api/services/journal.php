<?php

declare(strict_types=1);

require_once __DIR__ . '/../session.php';

function handle_journal(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            handle_journal_get($pdo);
            break;

        case 'POST':
            handle_journal_post($pdo);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handle_journal_get(PDO $pdo): void
{
    // Paging params from query string
    $skip = isset($_GET['skip']) ? max(0, (int)$_GET['skip']) : 0;
    $take = isset($_GET['take']) ? max(1, min(100, (int)$_GET['take'])) : 10;

    // Total count of entries
    $countSql  = 'SELECT COUNT(*) FROM journal_entries';
    $countStmt = $pdo->query($countSql);
    $totalCount = (int)($countStmt->fetchColumn() ?: 0);

    if ($totalCount === 0) {
        echo json_encode([
            'posts'      => [],
            'totalCount' => 0,
        ]);
        return;
    }

    // Page of posts with author info
    $pageSql = 'SELECT je.id, je.title, je.body, je.created_at, u.display_name AS author_name
                FROM journal_entries je
                LEFT JOIN users u ON u.id = je.user_id
                ORDER BY je.created_at DESC, je.id DESC
                LIMIT :take OFFSET :skip';

    $stmt = $pdo->prepare($pageSql);
    $stmt->bindValue(':take', $take, PDO::PARAM_INT);
    $stmt->bindValue(':skip', $skip, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map DB rows to JournalPost shape expected by the UI
    $posts = array_map(
        static function (array $row): array {
            return [
                'Id'         => (int)$row['id'],
                'Title'      => $row['title'],
                'Body'       => $row['body'],
                'CreatedAt'  => $row['created_at'],
                'AuthorName' => $row['author_name'] ?? 'Unknown',
            ];
        },
        $rows
    );

    echo json_encode([
        'posts'      => $posts,
        'totalCount' => $totalCount,
    ]);
}

function handle_journal_post(PDO $pdo): void
{
    ensure_session_started();

    $body = json_decode(file_get_contents('php://input'), true);

    if (!is_array($body) || empty($body['title']) || empty($body['body'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        return;
    }

    $title  = trim((string)$body['title']);
    $text   = trim((string)$body['body']);
    $userId = $_SESSION['user_id'] ?? null;

    $stmt = $pdo->prepare(
        'INSERT INTO journal_entries (user_id, title, body)
         VALUES (:user_id, :title, :body)'
    );

    $stmt->execute([
        ':user_id' => $userId ?: null,
        ':title'   => $title,
        ':body'    => $text,
    ]);

    $id = (int)$pdo->lastInsertId();

    // Return created post in the same shape JournalPost expects
    echo json_encode([
        'Id'         => $id,
        'Title'      => $title,
        'Body'       => $text,
        'CreatedAt'  => date('c'),
        'AuthorName' => '',
    ], JSON_THROW_ON_ERROR);
}