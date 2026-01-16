<?php

declare(strict_types=1);

function handle_intelligence(PDO $pdo, string $method): void
{
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    // Active projects: created in last 7 days
    $activeStmt = $pdo->query("
        SELECT COUNT(*) AS cnt
        FROM projects
        WHERE created_at >= (NOW() - INTERVAL 7 DAY)
    ");
    $activeProjects = (int)($activeStmt->fetchColumn() ?: 0);

    // Recent votes: votes in last 7 days
    $votesStmt = $pdo->query("
        SELECT COUNT(*) AS cnt
        FROM project_votes
        WHERE created_at >= (NOW() - INTERVAL 7 DAY)
    ");
    $recentVotes = (int)($votesStmt->fetchColumn() ?: 0);

    echo json_encode([
        'activeProjects' => $activeProjects,
        'recentVotes'    => $recentVotes,
    ]);
}