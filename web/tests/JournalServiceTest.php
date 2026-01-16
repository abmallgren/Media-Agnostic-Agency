<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FakePdo.php';
require_once __DIR__ . '/Support/MockPhpStream.php';
require_once __DIR__ . '/../api/services/journal.php';

final class JournalServiceTest extends TestCase
{
    public function test_handle_journal_get_returns_empty_when_no_entries(): void
    {
        $pdo = new FakePdo();

        // Stub total count to 0 – handle_journal_get will short-circuit
        $countSql = 'SELECT COUNT(*) FROM journal_entries';
        $pdo->setResultForQuery($countSql, [0]);

        $_GET['skip'] = '0';
        $_GET['take'] = '10';

        $level = ob_get_level();
        ob_start();
        handle_journal_get($pdo);
        $output = ob_get_clean();
        $this->assertSame($level, ob_get_level(), 'Output buffer level mismatch in journal_get');

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('totalCount', $data);
        $this->assertSame(0, $data['totalCount']);
        $this->assertIsArray($data['posts']);
        $this->assertCount(0, $data['posts']);
    }

    public function test_handle_journal_post_requires_title_and_body(): void
    {
        $pdo = new FakePdo();

        $this->withInputStream(json_encode(['title' => ''], JSON_THROW_ON_ERROR), function () use ($pdo) {
            ob_start();
            handle_journal_post($pdo);
            $output = ob_get_clean();

            $this->assertStringContainsString('Invalid payload', $output);
            $this->assertSame(400, http_response_code());
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