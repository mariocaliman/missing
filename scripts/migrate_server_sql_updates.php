<?php
if (php_sapi_name() !== 'cli') {
    exit("This script must be run from CLI.\n");
}

$root = dirname(__DIR__);
require_once $root . '/include/config.php';
require_once $root . '/include/connect.php';

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    $error = isset($connection_error) ? $connection_error : 'Unknown database connection error';
    exit("Database connection failed: {$error}\n");
}

$results = array();

function run_sql($mysqli, $label, $sql)
{
    if ($mysqli->query($sql)) {
        return "[OK] {$label}";
    }

    return "[FAIL] {$label} => " . $mysqli->error;
}

$results[] = run_sql(
    $mysqli,
    'Create table news_tips',
    "CREATE TABLE IF NOT EXISTS news_tips (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        news_id INT UNSIGNED NOT NULL,
        missing_name VARCHAR(255) NOT NULL,
        tip_name VARCHAR(255) NOT NULL,
        tip_email VARCHAR(255) NOT NULL,
        tip_phone VARCHAR(80) NOT NULL,
        tip_location VARCHAR(255) NOT NULL,
        tip_message TEXT NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        admin_note TEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        reviewed_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_news_id (news_id),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$results[] = run_sql(
    $mysqli,
    'Create table support_tickets',
    "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        ticket_code VARCHAR(24) NOT NULL,
        visitor_name VARCHAR(255) NOT NULL,
        visitor_email VARCHAR(255) NOT NULL,
        ticket_subject VARCHAR(255) NOT NULL,
        ticket_message TEXT NOT NULL,
        status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
        admin_reply TEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        replied_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_ticket_code (ticket_code),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$results[] = run_sql(
    $mysqli,
    'Ensure option rewrite_imported_news',
    "INSERT INTO options (option_name, option_value, option_default, option_set)
     SELECT 'rewrite_imported_news', '1', '1', 'General'
     FROM DUAL
     WHERE NOT EXISTS (
        SELECT 1 FROM options WHERE option_name='rewrite_imported_news'
     )"
);

$results[] = run_sql(
     $mysqli,
     'Ensure option use_source_image_url',
     "INSERT INTO options (option_name, option_value, option_default, option_set)
      SELECT 'use_source_image_url', '0', '0', 'General'
      FROM DUAL
      WHERE NOT EXISTS (
          SELECT 1 FROM options WHERE option_name='use_source_image_url'
      )"
);

$results[] = run_sql(
    $mysqli,
    'Ensure option openai_image_model',
    "INSERT INTO options (option_name, option_value, option_default, option_set)
     SELECT 'openai_image_model', 'gpt-image-1', 'gpt-image-1', 'AI'
     FROM DUAL
     WHERE NOT EXISTS (
        SELECT 1 FROM options WHERE option_name='openai_image_model'
     )"
);

$results[] = run_sql(
    $mysqli,
    'Ensure option openai_image_url',
    "INSERT INTO options (option_name, option_value, option_default, option_set)
     SELECT 'openai_image_url', 'https://api.openai.com/v1/images/generations', 'https://api.openai.com/v1/images/generations', 'AI'
     FROM DUAL
     WHERE NOT EXISTS (
        SELECT 1 FROM options WHERE option_name='openai_image_url'
     )"
);

foreach ($results as $line) {
    echo $line . "\n";
}

$mysqli->close();
