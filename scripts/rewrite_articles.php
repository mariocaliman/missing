<?php
/**
 * Rewrite article details into richer, more emotional multi-paragraph copy.
 *
 * Usage examples:
 *   php scripts/rewrite_articles.php
 *   php scripts/rewrite_articles.php --apply
 *   php scripts/rewrite_articles.php --apply --limit=50 --published=1 --source=all
 *   php scripts/rewrite_articles.php --apply --id=29
 */

if (php_sapi_name() !== 'cli') {
    exit("This script must be run from CLI.\n");
}

$options = getopt('', array(
    'apply',
    'limit::',
    'published::',
    'source::',
    'id::',
    'backup-file::'
));

$apply = isset($options['apply']);
$limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;
$published = isset($options['published']) ? (int) $options['published'] : null;
$sourceMode = isset($options['source']) ? strtolower(trim((string) $options['source'])) : 'all';
$singleId = isset($options['id']) ? (int) $options['id'] : 0;
$backupFile = isset($options['backup-file']) ? trim((string) $options['backup-file']) : '';

if (!in_array($sourceMode, array('all', 'private', 'feed'), true)) {
    exit("Invalid --source value. Use: all, private, feed\n");
}

$root = dirname(__DIR__);
require_once $root . '/include/config.php';
require_once $root . '/include/connect.php';

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    $error = isset($connection_error) ? $connection_error : 'Unknown database connection error';
    exit("Database connection failed: {$error}\n");
}

$where = array("details IS NOT NULL", "details <> ''");
if ($published !== null) {
    $where[] = "published='" . (int) $published . "'";
}
if ($sourceMode === 'private') {
    $where[] = "source_id='0'";
} elseif ($sourceMode === 'feed') {
    $where[] = "source_id<>'0'";
}
if ($singleId > 0) {
    $where[] = "id='" . $singleId . "'";
}

$sql = "SELECT id, title, details, source_id, permalink, datetime FROM news WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";
if ($limit > 0) {
    $sql .= " LIMIT " . $limit;
}

$result = $mysqli->query($sql);
if (!$result) {
    exit("Query failed: " . $mysqli->error . "\n");
}

$rows = array();
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (count($rows) === 0) {
    exit("No articles matched filters.\n");
}

if ($apply && $backupFile === '') {
    $backupFile = $root . '/cache/rewrite-backup-' . date('Ymd-His') . '.jsonl';
}

$updated = 0;
$skipped = 0;
$preview = 0;

$updateStmt = $mysqli->prepare("UPDATE news SET details=? WHERE id=?");
if (!$updateStmt) {
    exit("Prepare failed: " . $mysqli->error . "\n");
}

if ($apply) {
    $backupHandle = fopen($backupFile, 'ab');
    if ($backupHandle === false) {
        exit("Unable to open backup file: {$backupFile}\n");
    }
    echo "Backup file: {$backupFile}\n";
}

foreach ($rows as $row) {
    $articleId = (int) $row['id'];
    $title = html_entity_decode((string) $row['title'], ENT_QUOTES, 'UTF-8');
    $originalDetails = html_entity_decode((string) $row['details'], ENT_QUOTES, 'UTF-8');

    $rewritten = rewrite_article_text($title, $originalDetails);
    if ($rewritten === null) {
        $skipped++;
        continue;
    }

    $encoded = htmlspecialchars($rewritten, ENT_QUOTES, 'UTF-8');

    if ($apply) {
        $backupRecord = array(
            'id' => $articleId,
            'title' => $title,
            'old_details' => $row['details'],
            'rewritten_at' => date('c')
        );
        fwrite($backupHandle, json_encode($backupRecord, JSON_UNESCAPED_UNICODE) . "\n");

        $updateStmt->bind_param('si', $encoded, $articleId);
        if ($updateStmt->execute()) {
            $updated++;
        } else {
            echo "Failed updating ID {$articleId}: " . $updateStmt->error . "\n";
        }
    } else {
        $preview++;
        echo "---- Preview ID {$articleId}: {$title} ----\n";
        echo mb_substr($rewritten, 0, 700, 'UTF-8') . "\n\n";
    }
}

if ($apply) {
    fclose($backupHandle);
    echo "Done. Updated: {$updated}, Skipped: {$skipped}\n";
} else {
    echo "Dry-run complete. Previewed: {$preview}, Skipped: {$skipped}\n";
    echo "Use --apply to persist changes.\n";
}

$updateStmt->close();
$mysqli->close();

function rewrite_article_text($title, $details)
{
    $plain = trim(strip_tags($details));
    $plain = preg_replace('/\s+/u', ' ', $plain);

    if ($plain === '' || mb_strlen($plain, 'UTF-8') < 80) {
        return null;
    }

    $sentences = preg_split('/(?<=[.!?])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
    if (!$sentences || count($sentences) < 2) {
        $sentences = array($plain);
    }

    $headline = trim($title) !== '' ? trim($title) : 'This case';
    $s1 = sanitize_sentence($sentences[0]);
    $s2 = sanitize_sentence(isset($sentences[1]) ? $sentences[1] : '');
    $s3 = sanitize_sentence(isset($sentences[2]) ? $sentences[2] : '');

    $paragraph1 = "{$headline} is a story that deserves care, attention, and urgency.";

    $paragraph2Parts = array();
    if ($s1 !== '') {
        $paragraph2Parts[] = $s1;
    }
    if ($s2 !== '') {
        $paragraph2Parts[] = $s2;
    }
    if ($s3 !== '') {
        $paragraph2Parts[] = $s3;
    }
    $paragraph2 = implode(' ', $paragraph2Parts);

    $paragraph3 = "Every detail can matter, and even one shared lead may help bring answers faster. If you recognize anything related to this case, please contact the responsible authorities immediately.";

    $paragraph4 = "Families and communities carry deep concern in situations like this. Keeping this case visible and sharing verified information can make a real difference.";

    $paragraphs = array_filter(array(
        trim($paragraph1),
        trim($paragraph2),
        trim($paragraph3),
        trim($paragraph4)
    ));

    $rewritten = implode("\n\n", $paragraphs);

    if (mb_strlen($rewritten, 'UTF-8') <= mb_strlen($plain, 'UTF-8')) {
        $rewritten .= "\n\nPlease continue sharing verified information so this case remains visible and receives timely attention.";
    }

    return trim($rewritten);
}

function sanitize_sentence($text)
{
    $text = trim($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($text === '') {
        return '';
    }
    if (!preg_match('/[.!?]$/u', $text)) {
        $text .= '.';
    }
    return $text;
}
