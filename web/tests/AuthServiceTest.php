<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FakePdo.php';

final class AuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function test_handle_auth_user_returns_401_when_not_authenticated(): void
    {
        $pdo = new FakePdo();

        ob_start();
        handle_auth_user($pdo, 'GET');
        $output = ob_get_clean();

        $this->assertStringContainsString('Not authenticated', $output);
        $this->assertSame(401, http_response_code());
    }

    public function test_handle_auth_user_returns_user_when_authenticated(): void
    {
        $pdo = new FakePdo();

        // Simulate logged in user id in session
        $_SESSION['user_id'] = 123;

        // Configure fake query result
        $sql = "
        SELECT id, email, display_name
        FROM users
        WHERE id = :id
        LIMIT 1
    ";
        $pdo->setResultForQuery($sql, [
            'id'           => 123,
            'email'        => 'user@example.com',
            'display_name' => 'Test User',
        ]);

        ob_start();
        handle_auth_user($pdo, 'GET');
        $output = ob_get_clean();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertSame(123, $data['id']);
        $this->assertSame('Test User', $data['name']);
        $this->assertSame('user@example.com', $data['email']);
    }

    public function test_handle_auth_dispatches_to_user_route(): void
    {
        $pdo = new FakePdo();

        $_SESSION['user_id'] = 1;

        $sql = "
        SELECT id, email, display_name
        FROM users
        WHERE id = :id
        LIMIT 1
    ";
        $pdo->setResultForQuery($sql, [
            'id'           => 1,
            'email'        => 'test@example.com',
            'display_name' => 'Name',
        ]);

        ob_start();
        handle_auth($pdo, 'GET', '/api/auth/user');
        $output = ob_get_clean();

        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertSame(1, $data['id']);
    }
}