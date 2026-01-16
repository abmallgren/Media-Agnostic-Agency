<?php

declare(strict_types=1);

/**
 * @return callable(PDO): void
 */
return function (PDO $pdo): void {
    // Users / profiles
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            display_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Projects (for Expo / ProjectForm)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            involvement_sought TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN,
            CONSTRAINT fk_projects_users
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Journal entries
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS journal_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_journal_entries_users
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Contact form submissions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_user_id INT,
            recipient_user_id INT,
            project_id INT,
            body TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN,
            CONSTRAINT fk_contact_messages_sender_users
                FOREIGN KEY (sender_user_id) REFERENCES users(id)
                ON DELETE SET NULL,
            CONSTRAINT fk_contact_messages_receipient_users
                FOREIGN KEY (recipient_user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Project votes (for upvotes)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_project_votes_project
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            CONSTRAINT fk_project_votes_user
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT uq_project_votes_project_user
                UNIQUE (project_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
};