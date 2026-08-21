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

$sql = "CREATE TABLE IF NOT EXISTS news_tips (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($sql)) {
    echo "Migration complete: news_tips table is ready.\n";
} else {
    echo "Migration failed: " . $mysqli->error . "\n";
}

$mysqli->close();
?>