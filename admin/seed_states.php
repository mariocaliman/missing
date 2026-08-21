<?php
include('header.php');

function seed_get_column_length($mysqli, $column)
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

function seed_clip_text($value, $limit)
{
    if ($limit <= 0) {
        return $value;
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit, 'UTF-8');
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return substr($value, 0, $limit);
}

function seed_build_keywords($stateName, $stateCode)
{
    $keywords = array(
        $stateName . ' missing children',
        $stateName . ' missing persons',
        $stateCode . ' amber alert',
        $stateName . ' child safety',
        $stateName . ' runaway youth',
        $stateName . ' missing teens',
        $stateName . ' recovery tips',
        $stateName . ' family support',
        $stateName . ' law enforcement',
        $stateName . ' community alerts'
    );

    return implode(', ', $keywords);
}

function seed_build_description($stateName)
{
    return 'Missing USA coverage for ' . $stateName . ': child and missing person alerts, case updates, and community tips to help families and law enforcement.';
}

function run_us_states_seed($mysqli)
{
    $states = array(
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
        'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
        'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
        'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
    );

    $existing = array();
    $stateAliasMap = array();
    $query = $mysqli->query("SELECT id, category FROM categories");
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $normalized = strtoupper(trim((string) $row['category']));
            $existing[$normalized] = (int) $row['id'];

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

    $categoryLen = seed_get_column_length($mysqli, 'category');
    $keywordsLen = seed_get_column_length($mysqli, 'seo_keywords');
    $descriptionLen = seed_get_column_length($mysqli, 'seo_description');

    if ($categoryLen <= 0) { $categoryLen = 120; }
    if ($keywordsLen <= 0) { $keywordsLen = 255; }
    if ($descriptionLen <= 0) { $descriptionLen = 255; }

    $maxOrder = 0;
    $orderQuery = $mysqli->query("SELECT MAX(category_order) AS max_order FROM categories");
    if ($orderQuery) {
        $row = $orderQuery->fetch_assoc();
        $maxOrder = isset($row['max_order']) ? (int) $row['max_order'] : 0;
    }

    $insert = $mysqli->prepare("INSERT INTO categories (category, index_view, menu_view, seo_keywords, seo_description, category_order) VALUES (?, 1, 0, ?, ?, ?)");
    $update = $mysqli->prepare("UPDATE categories SET category=?, index_view=1, seo_keywords=?, seo_description=? WHERE id=?");

    if (!$insert || !$update) {
        return array('error' => 'Failed to prepare SQL statements.');
    }

    $created = 0;
    $updated = 0;
    $failed = 0;
    $failedStates = array();

    foreach ($states as $code => $name) {
        $category = seed_clip_text($code . ' - ' . $name, $categoryLen);
        $keywords = seed_clip_text(seed_build_keywords($name, $code), $keywordsLen);
        $description = seed_clip_text(seed_build_description($name), $descriptionLen);

        $stateId = isset($stateAliasMap[$code]) ? (int) $stateAliasMap[$code] : 0;

        if ($stateId > 0) {
            $update->bind_param('sssi', $category, $keywords, $description, $stateId);
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

    $missingStates = array();
    foreach ($states as $code => $name) {
        if (!isset($presentCodes[$code])) {
            $missingStates[] = $code . ' - ' . $name;
        }
    }

    return array(
        'created' => $created,
        'updated' => $updated,
        'failed' => $failed,
        'failed_states' => $failedStates,
        'present' => count($states) - count($missingStates),
        'total' => count($states),
        'missing_states' => $missingStates
    );
}

$report = null;
if (isset($_POST['seed_states'])) {
    try {
        NoCSRF::check('seed_states_token', $_POST, true, 60*10, false);
        $report = run_us_states_seed($mysqli);
    } catch (Exception $e) {
        $report = array('error' => 'Security token error. Please try again.');
    }
}

$seedToken = NoCSRF::generate('seed_states_token');
?>
<div class="page-header page-heading">
    <h1>Seed US State Categories
    <a href="categories.php" class="btn btn-default pull-right"><span class="fa fa-arrow-right"></span></a>
    </h1>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <p>This tool inserts or updates all 50 US state categories in format: <strong>CODE - State Name</strong>.</p>
        <form method="POST" action="">
            <input type="hidden" name="seed_states_token" value="<?php echo $seedToken; ?>" />
            <button type="submit" name="seed_states" class="btn btn-primary"><span class="fa fa-database"></span> Run Seeder</button>
        </form>
    </div>
</div>

<?php if (is_array($report)) { ?>
    <?php if (!empty($report['error'])) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($report['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } else { ?>
        <div class="alert alert-info">
            <strong>Seeder completed.</strong><br>
            Created: <?php echo (int) $report['created']; ?><br>
            Updated: <?php echo (int) $report['updated']; ?><br>
            Failed: <?php echo (int) $report['failed']; ?><br>
            States present as CODE - Name: <?php echo (int) $report['present']; ?>/<?php echo (int) $report['total']; ?>
        </div>

        <?php if (!empty($report['failed_states'])) { ?>
            <div class="panel panel-danger">
                <div class="panel-heading"><strong>Failure Details</strong></div>
                <div class="panel-body">
                    <ul>
                        <?php foreach ($report['failed_states'] as $failure) { ?>
                            <li><?php echo htmlspecialchars($failure, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($report['missing_states'])) { ?>
            <div class="panel panel-warning">
                <div class="panel-heading"><strong>Missing States After Run</strong></div>
                <div class="panel-body">
                    <ul>
                        <?php foreach ($report['missing_states'] as $missing) { ?>
                            <li><?php echo htmlspecialchars($missing, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
<?php } ?>

<?php
include('footer.php');
?>