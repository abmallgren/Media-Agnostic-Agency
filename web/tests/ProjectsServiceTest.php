<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FakePdo.php';
require_once __DIR__ . '/Support/MockPhpStream.php';
require_once __DIR__ . '/../api/services/projects.php';

final class ProjectsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function test_handle_projects_get_returns_list_with_flags(): void
    {
        $pdo = new FakePdo();

        $sql = "
        SELECT
            id,
            user_id,
            name,
            description,
            involvement_sought AS involvementSought,
            created_at
        FROM projects
        ORDER BY created_at DESC, id DESC
    ";

        // One project owned by user 10
        $pdo->setResultForQuery($sql, [
            [
                'id'                => 1,
                'user_id'           => 10,
                'name'              => 'Project 1',
                'description'       => 'Desc 1',
                'involvementSought' => 'Help needed',
                'created_at'        => '2025-01-01 00:00:00',
            ],
        ]);

        // Votes count query for that project
        $votesSql = "
        SELECT COUNT(*) AS cnt
        FROM project_votes
        WHERE project_id = :pid
          AND created_at >= (NOW() - INTERVAL 7 DAY)
    ";
        $pdo->setResultForQuery($votesSql, 3);

        // Logged-in user is the owner
        $_SESSION['user_id'] = 10;

        $level = ob_get_level();
        ob_start();
        handle_projects_get($pdo);
        $output = ob_get_clean();
        $this->assertSame($level, ob_get_level(), 'Output buffer level mismatch');

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertCount(1, $data);
        $project = $data[0];

        $this->assertSame(1, $project['id']);
        $this->assertSame(10, $project['ownerId']);
        $this->assertSame('Project 1', $project['name']);
        $this->assertSame('Desc 1', $project['description']);
        $this->assertSame('Help needed', $project['involvementSought']);
        $this->assertSame(3, $project['upvotesLast7Days']);
        $this->assertTrue($project['canEdit']);
        $this->assertFalse($project['canUpvote']);
    }

    public function test_handle_projects_post_valid_payload_returns_project_dto(): void
    {
        $pdo = new FakePdo();
        $pdo->setLastInsertId(42);

        // New project SELECT after insert
        $rowSql = "
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
    ";
        $pdo->setResultForQuery($rowSql, [
            'id'                => 42,
            'user_id'           => 5,
            'name'              => 'New Project',
            'description'       => 'Some description',
            'involvementSought' => 'Help with testing',
            'created_at'        => '2025-01-01 00:00:00',
        ]);

        // Votes count for new project
        $votesSql = "
        SELECT COUNT(*) AS cnt
        FROM project_votes
        WHERE project_id = :pid
          AND created_at >= (NOW() - INTERVAL 7 DAY)
    ";
        $pdo->setResultForQuery($votesSql, 0);

        $_SESSION['user_id'] = 5;

        $payload = [
            'name'              => 'New Project',
            'description'       => 'Some description',
            'involvementSought' => 'Help with testing',
        ];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $level = ob_get_level();

        $this->withInputStream($json, function () use ($pdo, $level) {
            ob_start();
            handle_projects_post($pdo);
            $output = ob_get_clean();
            $this->assertSame($level, ob_get_level(), 'Output buffer level mismatch');

            $this->assertJson($output);
            $data = json_decode($output, true);

            $this->assertSame(42, $data['id']);
            $this->assertSame(5, $data['ownerId']);
            $this->assertSame('New Project', $data['name']);
            $this->assertSame('Some description', $data['description']);
            $this->assertSame('Help with testing', $data['involvementSought']);
            $this->assertSame(0, $data['upvotesLast7Days']);
            $this->assertTrue($data['canEdit']);
            $this->assertFalse($data['canUpvote']);
        });
    }

    private function withInputStream(string $content, callable $fn): void
    {
        MockPhpStream::$content = $content;

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', MockPhpStream::class);

        try {
            $fn();
        } finally {
            stream_wrapper_restore('php');
        }
    }
}