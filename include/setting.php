<?php
// fetch the options row
$options = array();

if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
	try {
		$squery = $mysqli->query("SELECT * FROM options ORDER BY id ASC");
		if ($squery && $squery->num_rows > 0) {
			while ($row = $squery->fetch_assoc()) {
				$options[$row["option_name"]] = $row["option_value"];
			}
		}
	} catch (mysqli_sql_exception $e) {
		$options = array();
	}
	} else {
	$options = array(
		'installed' => 1,
		'site_theme' => 'default'
	);
}
