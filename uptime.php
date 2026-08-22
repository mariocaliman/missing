<?php
header('Content-Type: application/json; charset=utf-8');

include('include/config.php');
include('include/connect.php');
include('include/setting.php');

$now = time();
$max_age_seconds = isset($_GET['max_age']) ? intval($_GET['max_age']) : 10800;
if ($max_age_seconds < 300) {
    $max_age_seconds = 300;
}

$last_started_raw = isset($options['cron_last_run_started_at']) ? (string) $options['cron_last_run_started_at'] : '';
$last_finished_raw = isset($options['cron_last_run_finished_at']) ? (string) $options['cron_last_run_finished_at'] : '';
$last_status = isset($options['cron_last_run_status']) ? (string) $options['cron_last_run_status'] : 'unknown';
$last_inserted = isset($options['cron_last_run_inserted']) ? intval($options['cron_last_run_inserted']) : 0;
$last_failed = isset($options['cron_last_run_failed']) ? intval($options['cron_last_run_failed']) : 0;

$last_finished_ts = $last_finished_raw !== '' ? strtotime($last_finished_raw) : 0;
$cron_recent = ($last_finished_ts > 0) ? (($now - $last_finished_ts) <= $max_age_seconds) : false;
$cron_ok = $cron_recent && ($last_status === 'success' || $last_status === 'partial');

$payload = array(
    'service' => 'missing-usa',
    'status' => $cron_ok ? 'ok' : 'degraded',
    'checked_at' => date('c', $now),
    'cron' => array(
        'last_started_at' => $last_started_raw,
        'last_finished_at' => $last_finished_raw,
        'last_status' => $last_status,
        'last_inserted' => $last_inserted,
        'last_failed' => $last_failed,
        'recent' => $cron_recent,
        'max_age_seconds' => $max_age_seconds
    )
);

$strict = isset($_GET['strict']) ? intval($_GET['strict']) : 0;
if ($strict === 1 && !$cron_ok) {
    http_response_code(503);
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
