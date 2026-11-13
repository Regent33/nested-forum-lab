-- Nested forum lab: schema + fictional seed data.
-- Run with: mysql -u root -p < database/schema.sql
-- All timestamps are stored in UTC (the app sets the session time zone to +00:00).

SET time_zone = '+00:00'; -- so the seed times below are read as UTC

DROP DATABASE IF EXISTS nested_forum;
CREATE DATABASE nested_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(30)  NOT NULL UNIQUE,
    email         VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,          -- NULL = seed user, can never log in
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discussions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE comments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id     INT UNSIGNED NULL,          -- NULL = top-level comment
    user_id       INT UNSIGNED NOT NULL,
    body          TEXT         NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- target for the composite FK below
    UNIQUE KEY uq_comments_discussion_id (discussion_id, id),
    KEY ix_comments_parent (discussion_id, parent_id),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions (id),
    CONSTRAINT fk_comments_user       FOREIGN KEY (user_id)       REFERENCES users (id),
    -- a parent must live in the same discussion (skipped by MySQL when parent_id IS NULL)
    CONSTRAINT fk_comments_parent FOREIGN KEY (discussion_id, parent_id)
        REFERENCES comments (discussion_id, id)
) ENGINE=InnoDB;

-- Fictional seed users. NULL password hashes mean nobody can log in as them.
INSERT INTO users (id, username, email, password_hash) VALUES
    (1, 'alice', 'alice@example.test', NULL),
    (2, 'ben',   'ben@example.test',   NULL),
    (3, 'cara',  'cara@example.test',  NULL),
    (4, 'dee',   'dee@example.test',   NULL),
    (5, 'eli',   'eli@example.test',   NULL);

INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
    (1, 1, 'How do I learn PHP?', 'Share your learning tips.', '2026-11-01 08:00:00'),
    (2, 2, 'Favourite text editor?', 'What do you write PHP in?', '2026-11-02 08:00:00');

-- Discussion 1 matches the lab handout; comment 7 sits at displayed depth 4.
INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
    (1, 1, NULL, 1, 'Start with the PHP manual.',    '2026-11-01 09:00:00'),
    (2, 1, 1,    2, 'Try the examples as you read.', '2026-11-01 09:10:00'),
    (3, 1, 2,    3, 'Build a small project too.',    '2026-11-01 09:20:00'),
    (4, 1, 1,    4, 'Which manual section first?',   '2026-11-01 09:30:00'),
    (5, 1, NULL, 5, 'Practice SQL as well.',         '2026-11-01 09:40:00'),
    (6, 1, 3,    2, 'Start with a forum.',           '2026-11-01 09:50:00'),
    (7, 1, 6,    5, 'Add replies next.',             '2026-11-01 10:00:00'),
    -- discussion 2 exists so the cross-discussion parent check can be tested
    (8, 2, NULL, 3, 'VS Code with the PHP extension.', '2026-11-02 09:00:00');
