<?php
$options_row = array();
try {
	$setting_query = "SELECT * FROM options";
	$setting_result = $mysqli->query($setting_query);
	if ($setting_result && $setting_result->num_rows > 0) {
		$options_row = $setting_result->fetch_assoc();
	}
} catch (mysqli_sql_exception $e) {
	$options_row = array();
}
