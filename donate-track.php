<?php
header('Content-Type: application/json; charset=utf-8');

include(__DIR__ . '/include/config.php');
include(__DIR__ . '/include/connect.php');
include(__DIR__ . '/include/setting.php');
include(__DIR__ . '/include/donation_tracking.php');

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    http_response_code(503);
    echo json_encode(array('ok' => false, 'message' => 'Database unavailable'));
    exit;
}

$siteurl = !empty($options['siteurl']) ? $options['siteurl'] : '';
$page = isset($_POST['page']) ? $_POST['page'] : '/';

$ref = donation_tracking_register_click($mysqli, $page);
if (!$ref) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'message' => 'Could not register donation click'));
    exit;
}

$paypal_url = donation_tracking_build_paypal_url($siteurl, $ref);

echo json_encode(array(
    'ok' => true,
    'ref' => $ref,
    'paypal_url' => $paypal_url
));
