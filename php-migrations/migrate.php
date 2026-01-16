<?php

declare(strict_types=1);

require_once __DIR__ . '/../web/php/config.php';

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
    fwrite(STDERR, "Connection failed: " . $ex->getMessage() . PHP_EOL);
    exit(1);
}

// Ensure migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Get already applied migrations
$applied = $pdo->query("SELECT name FROM migrations ORDER BY name")
               ->fetchAll(PDO::FETCH_COLUMN);

$appliedSet = array_flip($applied);

// Load migration files
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.php');
sort($files);

if (!$files) {
    echo "No migration files found in {$migrationsDir}" . PHP_EOL;
    exit(0);
}

try {
    foreach ($files as $file) {
        $name = basename($file);

        if (isset($appliedSet[$name])) {
            // Already applied
            continue;
        }

        echo "Applying migration: {$name}" . PHP_EOL;

        /** @var callable(PDO): void $migration */
        $migration = require $file;

        if (!is_callable($migration)) {
            throw new RuntimeException("Migration file {$name} must return a callable.");
        }

        // Wrap this migration in its own transaction
        $pdo->beginTransaction();

        try {
            $migration($pdo);

            $stmt = $pdo->prepare("INSERT INTO migrations (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    echo "All pending migrations applied successfully." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}