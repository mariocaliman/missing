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

function cleanup_name_prefix($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/^\s*#\s*:\s*/u', '', $text);
    $text = preg_replace('/^\s*[:\-]+\s*/u', '', $text);
    return trim((string) $text);
}

function cleanup_details_prefixes($details)
{
    $details = (string) $details;
    if ($details === '') {
        return $details;
    }

    $details = preg_replace('/(^|\n)(\s*Name\s*:\s*)#\s*:\s*/iu', '$1$2', $details);
    $details = preg_replace('/(^|\n)\s*#\s*:\s*/u', "$1", $details);
    return $details;
}

$sql = "SELECT id, title, details FROM news";
$query = $mysqli->query($sql);
if (!$query) {
    exit("Query failed: " . $mysqli->error . "\n");
}

$updated = 0;
$checked = 0;

$update_stmt = $mysqli->prepare("UPDATE news SET title=?, details=? WHERE id=? LIMIT 1");
if (!$update_stmt) {
    exit("Prepare failed: " . $mysqli->error . "\n");
}

while ($row = $query->fetch_assoc()) {
    $checked++;
    $old_title = (string) $row['title'];
    $old_details = (string) $row['details'];

    $new_title = cleanup_name_prefix(htmlspecialchars_decode($old_title, ENT_QUOTES));
    $new_title = htmlspecialchars($new_title, ENT_QUOTES);

    $decoded_details = htmlspecialchars_decode($old_details, ENT_QUOTES);
    $cleaned_details = cleanup_details_prefixes($decoded_details);
    $new_details = htmlspecialchars($cleaned_details, ENT_QUOTES);

    if ($new_title !== $old_title || $new_details !== $old_details) {
        $id = intval($row['id']);
        $update_stmt->bind_param('ssi', $new_title, $new_details, $id);
        if ($update_stmt->execute()) {
            $updated++;
        }
    }
}

$update_stmt->close();
$mysqli->close();

echo "Checked: {$checked}\n";
echo "Updated: {$updated}\n";
