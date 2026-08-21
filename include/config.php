<?php
// change the values to fit yours
$db_config = array(
	'host' => 'localhost',
	'user' => 'missingu_mcc',
	'pass' => '2512Kati####',
	'name' => 'missingu_usa'
);

// AI writer configuration (used by admin/news.php -> Generate Draft)
// Fill api_key to enable AI generation from the admin form.
$ai_config = array(
	'api_key' => '',
	'model' => 'gpt-4o-mini',
	'base_url' => 'https://api.openai.com/v1/chat/completions'
);