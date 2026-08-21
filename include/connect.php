<?php
// mysqli connect method
mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = null;
$connection_error = '';
$connection_hosts = array($db_config['host']);

if ($db_config['host'] === 'localhost') {
    $connection_hosts[] = '127.0.0.1';
}

foreach ($connection_hosts as $connection_host) {
    try {
        $mysqli = new mysqli($connection_host, $db_config['user'], $db_config['pass'], $db_config['name']);
        if (!$mysqli->connect_errno) {
            break;
        }
        $connection_error = $mysqli->connect_error;
    } catch (mysqli_sql_exception $exception) {
        $connection_error = $exception->getMessage();
        $mysqli = null;
    }
}

if (!$mysqli || $mysqli->connect_errno) {
    if (empty($connection_error) && $mysqli) {
        $connection_error = $mysqli->connect_error;
    }
}
// set charset to UTF-8
if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->set_charset("utf8");
}