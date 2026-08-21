<?php
declare(strict_types=1);

const SEEDER_VERSION = 'v2.1';

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connect.php';

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$states = array(
    'AL' => 'Alabama',
    'AK' => 'Alaska',
    'AZ' => 'Arizona',
    'AR' => 'Arkansas',
    'CA' => 'California',
    'CO' => 'Colorado',
    'CT' => 'Connecticut',
    'DE' => 'Delaware',
    'FL' => 'Florida',
    'GA' => 'Georgia',
    'HI' => 'Hawaii',
    'ID' => 'Idaho',
    'IL' => 'Illinois',
    'IN' => 'Indiana',
    'IA' => 'Iowa',
    'KS' => 'Kansas',
    'KY' => 'Kentucky',
    'LA' => 'Louisiana',
    'ME' => 'Maine',
    'MD' => 'Maryland',
    'MA' => 'Massachusetts',
    'MI' => 'Michigan',
    'MN' => 'Minnesota',
    'MS' => 'Mississippi',
    'MO' => 'Missouri',
    'MT' => 'Montana',
    'NE' => 'Nebraska',
    'NV' => 'Nevada',
    'NH' => 'New Hampshire',
    'NJ' => 'New Jersey',
    'NM' => 'New Mexico',
    'NY' => 'New York',
    'NC' => 'North Carolina',
    'ND' => 'North Dakota',
    'OH' => 'Ohio',
    'OK' => 'Oklahoma',
    'OR' => 'Oregon',
    'PA' => 'Pennsylvania',
    'RI' => 'Rhode Island',
    'SC' => 'South Carolina',
    'SD' => 'South Dakota',
    'TN' => 'Tennessee',
    'TX' => 'Texas',
    'UT' => 'Utah',
    'VT' => 'Vermont',
    'VA' => 'Virginia',
    'WA' => 'Washington',
    'WV' => 'West Virginia',
    'WI' => 'Wisconsin',
    'WY' => 'Wyoming'
);

function build_keywords(string $stateName, string $stateCode): string
{
    $keywords = array(
        "{$stateName} missing children",
        "{$stateName} missing persons",
        "{$stateCode} amber alert",
        "{$stateName} child safety",
        "{$stateName} runaway youth",
        "{$stateName} missing teens",
        "{$stateName} recovery tips",
        "{$stateName} family support",
        "{$stateName} law enforcement",
        "{$stateName} community alerts"
    );

    return implode(', ', $keywords);
}

function build_description(string $stateName): string
{
    return "Missing USA coverage for {$stateName}: child and missing person alerts, case updates, and community tips to help families and law enforcement.";
}

function get_column_length(mysqli $mysqli, string $column): int
{
    $columnEscaped = $mysqli->real_escape_string($column);
    $result = $mysqli->query("SHOW COLUMNS FROM categories LIKE '{$columnEscaped}'");
    if (!$result || $result->num_rows === 0) {
        return 0;
    }

    $row = $result->fetch_assoc();
    if (!isset($row['Type'])) {
        return 0;
    }

    if (preg_match('/\((\d+)\)/', (string) $row['Type'], $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

function clip_text(string $value, int $limit): string
{
    if ($limit <= 0) {
        return $value;
    }
    if (mb_strlen($value, 'UTF-8') <= $limit) {
        return $value;
    }
    return mb_substr($value, 0, $limit, 'UTF-8');
}

$existing = array();
$stateAliasMap = array();
$query = $mysqli->query("SELECT id, category, category_order, menu_view FROM categories");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $normalized = strtoupper(trim((string) $row['category']));
        $existing[$normalized] = $row;

        if (preg_match('/^([A-Z]{2})\s*\-\s*/', $normalized, $m)) {
            $stateAliasMap[$m[1]] = (int) $row['id'];
        } elseif (preg_match('/^[A-Z]{2}$/', $normalized)) {
            $stateAliasMap[$normalized] = (int) $row['id'];
        } else {
            foreach ($states as $code => $stateName) {
                if ($normalized === strtoupper($stateName)) {
                    $stateAliasMap[$code] = (int) $row['id'];
                    break;
                }
            }
        }
    }
}

$categoryLen = get_column_length($mysqli, 'category');
$keywordsLen = get_column_length($mysqli, 'seo_keywords');
$descriptionLen = get_column_length($mysqli, 'seo_description');

if ($categoryLen <= 0) {
    $categoryLen = 120;
}
if ($keywordsLen <= 0) {
    $keywordsLen = 255;
}
if ($descriptionLen <= 0) {
    $descriptionLen = 255;
}

$maxOrder = 0;
$orderQuery = $mysqli->query("SELECT MAX(category_order) AS max_order FROM categories");
if ($orderQuery) {
    $row = $orderQuery->fetch_assoc();
    $maxOrder = isset($row['max_order']) ? (int) $row['max_order'] : 0;
}

$insert = $mysqli->prepare("INSERT INTO categories (category, index_view, menu_view, seo_keywords, seo_description, category_order) VALUES (?, 1, 0, ?, ?, ?)");
$update = $mysqli->prepare("UPDATE categories SET category=?, index_view=1, seo_keywords=?, seo_description=? WHERE id=?");

if (!$insert || !$update) {
    fwrite(STDERR, "Failed to prepare SQL statements.\n");
    exit(1);
}

$created = 0;
$updated = 0;
$failed = 0;
$failedStates = array();

foreach ($states as $code => $name) {
    $category = strtoupper($code) . ' - ' . $name;
    $legacyCode = strtoupper($code);
    $legacyName = strtoupper($name);

    $category = clip_text($category, $categoryLen);
    $keywords = clip_text(build_keywords($name, $code), $keywordsLen);
    $description = clip_text(build_description($name), $descriptionLen);

    if (isset($stateAliasMap[$code])) {
        $id = (int) $stateAliasMap[$code];
        $update->bind_param('sssi', $category, $keywords, $description, $id);
        if ($update->execute()) {
            $updated++;
        } else {
            $failed++;
            $failedStates[] = $code . ': ' . $update->error;
        }
        continue;
    }

    if (isset($existing[strtoupper($category)])) {
        $id = (int) $existing[strtoupper($category)]['id'];
        $update->bind_param('sssi', $category, $keywords, $description, $id);
        if ($update->execute()) {
            $updated++;
        } else {
            $failed++;
            $failedStates[] = $code . ': ' . $update->error;
        }
        continue;
    }

    if (isset($existing[$legacyCode])) {
        $id = (int) $existing[$legacyCode]['id'];
        $update->bind_param('sssi', $category, $keywords, $description, $id);
        if ($update->execute()) {
            $updated++;
        } else {
            $failed++;
            $failedStates[] = $code . ': ' . $update->error;
        }
        continue;
    }

    if (isset($existing[$legacyName])) {
        $id = (int) $existing[$legacyName]['id'];
        $update->bind_param('sssi', $category, $keywords, $description, $id);
        if ($update->execute()) {
            $updated++;
        } else {
            $failed++;
            $failedStates[] = $code . ': ' . $update->error;
        }
        continue;
    }

    $maxOrder++;
    $insert->bind_param('sssi', $category, $keywords, $description, $maxOrder);
    if ($insert->execute()) {
        $created++;
    } else {
        $failed++;
        $failedStates[] = $code . ': ' . $insert->error;
    }
}

$insert->close();
$update->close();

$presentCodes = array();
$checkQuery = $mysqli->query("SELECT category FROM categories");
if ($checkQuery) {
    while ($row = $checkQuery->fetch_assoc()) {
        $value = strtoupper(trim((string) $row['category']));
        if (preg_match('/^([A-Z]{2})\s*\-\s*/', $value, $m)) {
            $presentCodes[$m[1]] = true;
        }
    }
}

$missingCodes = array();
foreach ($states as $code => $name) {
    if (!isset($presentCodes[$code])) {
        $missingCodes[] = $code . ' - ' . $name;
    }
}

echo "US state categories seeded successfully (" . SEEDER_VERSION . ").\n";
echo "Created: {$created}\n";
echo "Updated: {$updated}\n";
echo "Failed: {$failed}\n";
echo "Total states handled: " . count($states) . "\n";
echo "States present as 'CODE - Name': " . (count($states) - count($missingCodes)) . "\n";
if ($failed > 0) {
    echo "Failure details:\n";
    foreach ($failedStates as $failure) {
        echo "- {$failure}\n";
    }
}
if (!empty($missingCodes)) {
    echo "Missing states after run:\n";
    foreach ($missingCodes as $missingState) {
        echo "- {$missingState}\n";
    }
}
