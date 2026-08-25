<?php
header("Content-type: text/html; charset=utf8");
set_time_limit(60000*60);
error_reporting(E_ERROR);
ini_set('default_socket_timeout', '20');

$cron_debug_mode = (isset($_GET['debug']) && $_GET['debug'] === '1');
if (PHP_SAPI !== 'cli') {
	ignore_user_abort(true);
	if (!$cron_debug_mode) {
		header('Content-Type: text/plain; charset=utf-8');
		echo "Cron accepted\n";
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} else {
			@ob_flush();
			@flush();
		}
	}
}

include('include/config.php');
include('include/connect.php');
include('include/functions.php');
include('include/setting.php');

$cron_lock_file = __DIR__ . '/cache/cron.lock';
$cron_lock_handle = @fopen($cron_lock_file, 'c');
if (!$cron_lock_handle) {
	if ($cron_debug_mode || PHP_SAPI === 'cli') {
		echo 'Cron aborted: unable to create lock file.';
	}
	exit;
}

if (!@flock($cron_lock_handle, LOCK_EX | LOCK_NB)) {
	if ($cron_debug_mode || PHP_SAPI === 'cli') {
		echo 'Cron skipped: another run is already in progress.';
	}
	@fclose($cron_lock_handle);
	exit;
}

register_shutdown_function(function () use ($cron_lock_handle) {
	@flock($cron_lock_handle, LOCK_UN);
	@fclose($cron_lock_handle);
});

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
	if ($cron_debug_mode || PHP_SAPI === 'cli') {
		echo 'Cron aborted: database connection failed.';
	}
	exit;
}

function upsert_runtime_option($name, $value, $set = 'General')
{
	global $mysqli;
	if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
		return false;
	}

	$name = $mysqli->real_escape_string((string) $name);
	$value = $mysqli->real_escape_string((string) $value);
	$set = $mysqli->real_escape_string((string) $set);

	$check = $mysqli->query("SELECT id FROM options WHERE option_name='" . $name . "' LIMIT 1");
	if ($check && $check->num_rows > 0) {
		return (bool) $mysqli->query("UPDATE options SET option_value='" . $value . "' WHERE option_name='" . $name . "' LIMIT 1");
	}

	return (bool) $mysqli->query("INSERT INTO options (option_name, option_value, option_default, option_set) VALUES ('" . $name . "', '" . $value . "', '" . $value . "', '" . $set . "')");
}

function get_runtime_option($name, $default = '')
{
	global $mysqli;
	if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
		return $default;
	}

	$name = $mysqli->real_escape_string((string) $name);
	$query = $mysqli->query("SELECT option_value FROM options WHERE option_name='" . $name . "' LIMIT 1");
	if (!$query || $query->num_rows === 0) {
		return $default;
	}

	$row = $query->fetch_assoc();
	if (!isset($row['option_value'])) {
		return $default;
	}

	return (string) $row['option_value'];
}

function get_ai_runtime_option($name, $default = '')
{
	global $options;
	$env_name = strtoupper((string) $name);
	$env_value = getenv($env_name);
	if ($env_value !== false && trim((string) $env_value) !== '') {
		return trim((string) $env_value);
	}

	if (isset($options[$name]) && trim((string) $options[$name]) !== '') {
		return trim((string) $options[$name]);
	}

	return (string) $default;
}

function strip_markdown_fences($text)
{
	$text = trim((string) $text);
	if (strpos($text, '```') === 0) {
		$text = preg_replace('/^```(?:html|markdown|md|text)?\s*/i', '', $text);
		$text = preg_replace('/\s*```\s*$/', '', $text);
	}
	return trim((string) $text);
}

function generate_timeline_with_ai_for_cron($title, $details, $language, &$error_message = '')
{
	$title = trim((string) $title);
	$details_plain = trim(strip_tags(html_entity_decode((string) $details, ENT_QUOTES, 'UTF-8')));
	$details_plain = preg_replace('/\s+/u', ' ', $details_plain);
	if ($title === '' || $details_plain === '') {
		$error_message = 'Missing title or details for timeline generation.';
		return false;
	}

	if (mb_strlen($details_plain, 'UTF-8') > 4000) {
		$details_plain = mb_substr($details_plain, 0, 4000, 'UTF-8');
	}

	$api_key = get_ai_runtime_option('openai_api_key', '');
	if ($api_key === '') {
		if (isset($GLOBALS['ai_config']['api_key']) && trim((string) $GLOBALS['ai_config']['api_key']) !== '') {
			$api_key = trim((string) $GLOBALS['ai_config']['api_key']);
		}
	}
	if ($api_key === '') {
		$error_message = 'OpenAI API key is not configured.';
		return false;
	}

	$model = get_ai_runtime_option('openai_model', '');
	if ($model === '' && isset($GLOBALS['ai_config']['model']) && trim((string) $GLOBALS['ai_config']['model']) !== '') {
		$model = trim((string) $GLOBALS['ai_config']['model']);
	}
	if ($model === '') {
		$model = 'gpt-4o-mini';
	}

	$endpoint = get_ai_runtime_option('openai_base_url', '');
	if ($endpoint === '' && isset($GLOBALS['ai_config']['base_url']) && trim((string) $GLOBALS['ai_config']['base_url']) !== '') {
		$endpoint = trim((string) $GLOBALS['ai_config']['base_url']);
	}
	if ($endpoint === '') {
		$endpoint = 'https://api.openai.com/v1/chat/completions';
	}

	$messages = array(
		array(
			'role' => 'system',
			'content' => 'You are an investigative newsroom assistant. Return valid HTML only. No markdown fences.'
		),
		array(
			'role' => 'user',
			'content' => "Create a case timeline update for this missing person report in {$language}. Title: {$title}. Source details: {$details_plain}. Include: (1) h2 heading 'Case Timeline', (2) 4-8 bullet items in chronological order, (3) h2 heading 'Potential New Leads to Verify', and (4) a short paragraph with verification guidance. Use cautious language when details are uncertain and avoid inventing specific names or addresses."
		)
	);

	$payload = array(
		'model' => $model,
		'messages' => $messages,
		'temperature' => 0.4
	);

	$ch = curl_init($endpoint);
	if ($ch === false) {
		$error_message = 'Could not initialize cURL.';
		return false;
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $api_key
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($ch, CURLOPT_TIMEOUT, 90);

	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	curl_close($ch);

	if ($response === false) {
		$error_message = 'AI request failed: ' . $curl_error;
		return false;
	}

	$data = json_decode($response, true);
	if (!is_array($data)) {
		$error_message = 'AI returned an invalid response.';
		return false;
	}

	if ($http_code >= 400) {
		$api_error = '';
		if (isset($data['error']['message'])) {
			$api_error = (string) $data['error']['message'];
		}
		$error_message = 'AI API error (' . $http_code . '): ' . $api_error;
		return false;
	}

	$content = '';
	if (isset($data['choices'][0]['message']['content'])) {
		$content = (string) $data['choices'][0]['message']['content'];
	}
	$content = strip_markdown_fences($content);
	if ($content === '') {
		$error_message = 'AI returned empty timeline content.';
		return false;
	}

	return $content;
}

function append_timeline_to_article_details($existing_details_encoded, $timeline_html)
{
	$existing_decoded = htmlspecialchars_decode((string) $existing_details_encoded, ENT_QUOTES);
	$existing_decoded = trim((string) $existing_decoded);
	$timeline_html = trim((string) $timeline_html);
	if ($timeline_html === '') {
		return (string) $existing_details_encoded;
	}

	$stamp = date('F j, Y');
	$timeline_block = '<hr />' . "\n" . '<p><strong>Timeline Updated: ' . $stamp . '</strong></p>' . "\n" . $timeline_html;
	if ($existing_decoded !== '') {
		$merged = $existing_decoded . "\n\n" . $timeline_block;
	} else {
		$merged = $timeline_block;
	}

	return htmlspecialchars($merged, ENT_QUOTES);
}

function run_daily_timeline_updates($daily_limit = 2)
{
	global $mysqli;

	$daily_limit = max(1, min(5, intval($daily_limit)));
	$force_daily = (isset($_GET['timeline_force']) && $_GET['timeline_force'] === '1');
	$today_key = date('Y-m-d');
	$last_daily = get_runtime_option('cron_timeline_daily_last_date', '');
	if (!$force_daily && $last_daily === $today_key) {
		return array('status' => 'skipped', 'updated' => 0, 'message' => 'Already executed today.');
	}

	$updated = 0;
	$attempted = 0;
	$errors = array();
	$processed_ids = array();

	$candidates_sql = "SELECT id,title,details FROM news WHERE published='1' AND details<>'' ORDER BY RAND() LIMIT 40";
	$candidates_q = $mysqli->query($candidates_sql);
	if (!$candidates_q || $candidates_q->num_rows === 0) {
		upsert_runtime_option('cron_timeline_daily_last_date', $today_key);
		upsert_runtime_option('cron_timeline_daily_last_count', '0');
		upsert_runtime_option('cron_timeline_daily_last_status', 'no_candidates');
		return array('status' => 'no_candidates', 'updated' => 0, 'message' => 'No published candidates found.');
	}

	while (($row = $candidates_q->fetch_assoc()) && $updated < $daily_limit) {
		$article_id = intval($row['id']);
		if ($article_id <= 0 || isset($processed_ids[$article_id])) {
			continue;
		}
		$processed_ids[$article_id] = 1;

		$title = isset($row['title']) ? (string) $row['title'] : '';
		$details_encoded = isset($row['details']) ? (string) $row['details'] : '';
		$plain = trim(strip_tags(html_entity_decode($details_encoded, ENT_QUOTES, 'UTF-8')));
		if ($plain === '' || mb_strlen($plain, 'UTF-8') < 120) {
			continue;
		}

		$attempted++;
		$timeline_error = '';
		$timeline_html = generate_timeline_with_ai_for_cron($title, $details_encoded, 'English', $timeline_error);
		if ($timeline_html === false) {
			$errors[] = 'article #' . $article_id . ': ' . $timeline_error;
			continue;
		}

		$updated_details = append_timeline_to_article_details($details_encoded, $timeline_html);
		$updated_details_sql = $mysqli->real_escape_string($updated_details);
		$article_id_sql = intval($article_id);
		$updated_at = time();
		$updated_day = date('j', $updated_at);
		$updated_month = date('n', $updated_at);
		$updated_year = date('Y', $updated_at);
		$update_sql = "UPDATE news SET details='" . $updated_details_sql . "', day='" . $updated_day . "', month='" . $updated_month . "', year='" . $updated_year . "' WHERE id='" . $article_id_sql . "' LIMIT 1";
		if ($mysqli->query($update_sql)) {
			$updated++;
		} else {
			$errors[] = 'article #' . $article_id . ': database update failed';
		}
	}

	$status = 'success';
	if ($updated === 0 && $attempted > 0) {
		$status = 'failed';
	} elseif ($updated > 0 && !empty($errors)) {
		$status = 'partial';
	}

	upsert_runtime_option('cron_timeline_daily_last_date', $today_key);
	upsert_runtime_option('cron_timeline_daily_last_count', (string) $updated);
	upsert_runtime_option('cron_timeline_daily_last_status', $status);
	upsert_runtime_option('cron_timeline_daily_last_errors', implode(' | ', array_slice($errors, 0, 5)));

	return array(
		'status' => $status,
		'updated' => $updated,
		'attempted' => $attempted,
		'message' => implode(' | ', array_slice($errors, 0, 3))
	);
}

function fetch_remote_binary($url, $timeout = 12)
{
	$url = trim((string) $url);
	$timeout = max(3, intval($timeout));
	if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
		return false;
	}

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		curl_close($ch);
		if ($data !== false && $data !== '') {
			return $data;
		}
	}

	$context = stream_context_create(array(
		'http' => array(
			'method' => 'GET',
			'timeout' => $timeout,
			'ignore_errors' => true,
			'header' => "User-Agent: Mozilla/5.0\r\n"
		),
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false
		)
	));

	return @file_get_contents($url, false, $context);
}
// get first image url from sting using HTML dom
function get_first_image($html){
	if (!empty($html)) {
    require_once('include/simple_html_dom.php');
    $post_dom = str_get_html($html);
    $first_img = $post_dom->find('img', 0);
    if($first_img !== null) {
	$image = $first_img->src;
	if (strtok($image, '?') != '') {
	$image = strtok($image, '?');
	} else {
	$image = $image;
	}
        return $image;
    }

    return null;
	} else {
	return null;	
	}
}


// check if the article exists before
function check_item_url($permalink,$source_id) {
	global $mysqli;
	$sql = "SELECT permalink,source_id FROM news WHERE permalink='$permalink' AND source_id='$source_id' LIMIT 1";
	$query = $mysqli->query($sql);
	return $query->num_rows;
}

@require_once('include/autoload.php');
	$run_started_at = time();
	$total_sources = 0;
	$total_inserted = 0;
	$total_failed = 0;
	$default_batch_size = 10;
	$batch_size = intval(get_runtime_option('cron_sources_batch_size', (string) $default_batch_size));
	if ($batch_size <= 0) {
		$batch_size = $default_batch_size;
	}
	if (isset($_GET['batch'])) {
		$batch_size = intval($_GET['batch']);
	}
	$batch_size = max(1, min(50, $batch_size));
	$batch_offset = intval(get_runtime_option('cron_sources_offset', '0'));
	if ($batch_offset < 0) {
		$batch_offset = 0;
	}
	$processed_in_batch = 0;
	$total_auto_sources = 0;
	$count_query = $mysqli->query("SELECT COUNT(*) AS total FROM sources WHERE auto_update='1'");
	if ($count_query && ($count_row = $count_query->fetch_assoc())) {
		$total_auto_sources = intval($count_row['total']);
	}
	if ($total_auto_sources > 0 && $batch_offset >= $total_auto_sources) {
		$batch_offset = 0;
	}
	log_observability_event('cron_start', 'info', 'Automated cron import started.', array('script' => 'cron.php', 'started_at' => date('c', $run_started_at)));
	upsert_runtime_option('cron_last_run_started_at', date('c', $run_started_at));
	upsert_runtime_option('cron_last_run_status', 'running');
	upsert_runtime_option('cron_last_run_inserted', '0');
	upsert_runtime_option('cron_last_run_failed', '0');
	$sql = "SELECT * FROM sources WHERE auto_update='1' ORDER BY id ASC LIMIT " . $batch_offset . "," . $batch_size;
	$query = $mysqli->query($sql);
	while ($query && ($row = $query->fetch_assoc())) {
	$processed_in_batch++;
	$total_sources++;
	$source_inserted = 0;
	$source_failed = 0;
	$use_remote_images = is_remote_image_import_enabled();
	$category_id = $row['category_id'];
	$source_id = $row['id'];
	$rss_link = $row['rss_link'];
	$news_number = intval($row['news_number']);
	$grab_limit = $news_number > 0 ? $news_number : 0;
	$day = date('j');
	$month = date('n');
	$year = date('Y');
		$feed_items = array();
		if (class_exists('SimplePie\\SimplePie')) {
			$feed = new \SimplePie\SimplePie();
			$feed->set_useragent();
			$feed->set_timeout(20);
			$feed->set_feed_url($rss_link);
			$feed->strip_htmltags(false);
			$feed->force_feed(true);
			if ($feed->init()) {
				$feed->handle_content_type();
				if ($grab_limit > 0) {
					$array = array_reverse($feed->get_items(0,$grab_limit));
				} else {
					$array = array_reverse($feed->get_items());
				}
				foreach ($array AS $item) {
					$link = $item->get_permalink();
					if (strpos($link,'feedproxy') !== false) {
						$orig = $item->get_item_tags('http://rssnamespace.org/feedburner/ext/1.0','origLink');
						$permalink = !empty($orig[0]['data']) ? $orig[0]['data'] : $link;
					} else {
						$permalink = $link;
					}
					$enclosure_link = '';
					if ($enclosure = $item->get_enclosure()) {
						$enclosure_link = $enclosure->get_link();
					}
					$feed_items[] = array(
						'permalink' => $permalink,
						'title' => $item->get_title(),
						'content' => $item->get_content(),
						'description' => $item->get_description(),
						'enclosure' => $enclosure_link
					);
				}
			}
		}

		if (empty($feed_items)) {
			$feed_items = fetch_feed_items($rss_link, $grab_limit);
		}

		foreach ($feed_items AS $item) {
			$permalink = trim($item['permalink']);
			if (empty($permalink)) {
				continue;
			}
			$normalized_title = normalize_import_title($item['title']);
			$title = $mysqli->real_escape_string(htmlspecialchars($normalized_title, ENT_QUOTES));
			$content = isset($item['content']) ? $item['content'] : '';
			$description = isset($item['description']) ? $item['description'] : '';
			$fallback_details = (mb_strlen(strip_tags($content),'UTF-8') > mb_strlen(strip_tags($description),'UTF-8')) ? $content : $description;
			$article_data = get_article_data_from_url($permalink, $fallback_details);
			$rewritten_details = rewrite_feed_article_details($normalized_title, $article_data['details']);
			$details = $mysqli->real_escape_string(htmlspecialchars($rewritten_details, ENT_QUOTES));
			$image = $article_data['image'];
			$datetime = time();
			if (check_item_url($permalink,$source_id) == 0) {
				if (empty($image) && !empty($item['enclosure'])) {
					$image = $item['enclosure'];
					if (!empty($image)) {
						$image = strtok($image, '?');
					}
				}

				if (empty($image)) {
					$image = get_first_image($fallback_details);
				}

				if (!empty($image) && is_disallowed_feed_image_url($image)) {
					$image = '';
				}

				if (!empty($image) && $use_remote_images && filter_var($image, FILTER_VALIDATE_URL)) {
					$filename = $image;
				} elseif (!empty($image)) {
					$filetype = strtolower(pathinfo($image, PATHINFO_EXTENSION));
					if (!in_array($filetype,array('jpg','jpeg','png','gif','webp'))) {
						$filename = '';
					} else {
						$filename = 'image_'.time().'_'.rand(0000000,99999999).'.'.$filetype;
						$image_data = fetch_remote_binary($image, 12);
						if ($image_data !== false) {
							$path = 'upload/news/'.$filename;
							$up = @file_put_contents($path, $image_data);
							if ($up !== false && file_exists($path)) {
								$size = filesize($path);
								list($width) = getimagesize($path);
								if (intval($size) >= 1024 AND $width >= 40) {
									$filename = $filename;
								} else {
									@unlink($path);
									$filename = '';
								}
							} else {
								$filename = '';
							}
						} else {
							$filename = '';
						}
					}
				} else {
					$filename = '';
				}

				$permalink_safe = $mysqli->real_escape_string($permalink);
				$filename_safe = $mysqli->real_escape_string($filename);
				$insert = $mysqli->query("INSERT INTO news (title,permalink,category_id,source_id,details,datetime,published,thumbnail,day,month,year,hits) VALUES ('$title','$permalink_safe','$category_id','$source_id','$details','$datetime','1','$filename_safe','$day','$month','$year','0')");
				if ($insert) {
					$source_inserted++;
					$total_inserted++;
				} else {
					$source_failed++;
					$total_failed++;
				}
			}
		}
	$now = time();
	$mysqli->query("UPDATE sources SET latest_activity='$now' WHERE id='$source_id'");
	log_observability_event(
		'import_source_finished',
		$source_failed > 0 ? 'warning' : 'info',
		'Cron import finished for source #' . $source_id,
		array(
			'rss_link' => $rss_link,
			'use_remote_images' => $use_remote_images ? 1 : 0,
			'inserted' => $source_inserted,
			'failed' => $source_failed
		),
		$source_id,
		$source_inserted
	);
	}

	if ($total_auto_sources > 0) {
		$next_offset = $batch_offset + $processed_in_batch;
		if ($next_offset >= $total_auto_sources || $processed_in_batch === 0) {
			$next_offset = 0;
		}
		upsert_runtime_option('cron_sources_offset', (string) $next_offset);
		upsert_runtime_option('cron_sources_batch_size', (string) $batch_size);
		upsert_runtime_option('cron_sources_total', (string) $total_auto_sources);
		upsert_runtime_option('cron_sources_last_batch_processed', (string) $processed_in_batch);
	}

	$run_finished_at = time();
	$status = ($total_failed > 0) ? 'partial' : 'success';
	upsert_runtime_option('cron_last_run_finished_at', date('c', $run_finished_at));
	upsert_runtime_option('cron_last_run_status', $status);
	upsert_runtime_option('cron_last_run_inserted', (string) $total_inserted);
	upsert_runtime_option('cron_last_run_failed', (string) $total_failed);

	log_observability_event(
		'cron_finished',
		($total_failed > 0 ? 'warning' : 'info'),
		'Automated cron import finished.',
		array(
			'sources_processed' => $total_sources,
			'sources_batch_size' => $batch_size,
			'sources_batch_offset' => $batch_offset,
			'sources_total' => $total_auto_sources,
			'inserted' => $total_inserted,
			'failed' => $total_failed,
			'duration_seconds' => ($run_finished_at - $run_started_at)
		),
		0,
		$total_inserted
	);

	if ($cron_debug_mode || PHP_SAPI === 'cli') {
		echo 'Cron finished. Batch processed: ' . $total_sources . '/' . $total_auto_sources . ' (offset ' . $batch_offset . ', size ' . $batch_size . ').';
	}

	$timeline_daily_result = run_daily_timeline_updates(2);
	log_observability_event(
		'cron_timeline_daily',
		($timeline_daily_result['status'] === 'success' ? 'info' : (($timeline_daily_result['status'] === 'skipped' || $timeline_daily_result['status'] === 'no_candidates') ? 'info' : 'warning')),
		'Daily timeline update routine finished.',
		array(
			'status' => isset($timeline_daily_result['status']) ? $timeline_daily_result['status'] : 'unknown',
			'updated' => isset($timeline_daily_result['updated']) ? intval($timeline_daily_result['updated']) : 0,
			'attempted' => isset($timeline_daily_result['attempted']) ? intval($timeline_daily_result['attempted']) : 0,
			'details' => isset($timeline_daily_result['message']) ? $timeline_daily_result['message'] : ''
		)
	);
	if (($cron_debug_mode || PHP_SAPI === 'cli') && isset($timeline_daily_result['status'])) {
		echo ' Timeline daily: ' . $timeline_daily_result['status'] . ' (' . intval($timeline_daily_result['updated']) . ' updated).';
	}

	if (!empty($options['uptimerobot_heartbeat_url'])) {
		$heartbeat_ok = ping_uptimerobot_heartbeat($options['uptimerobot_heartbeat_url']);
		log_observability_event(
			'uptimerobot_ping',
			$heartbeat_ok ? 'info' : 'warning',
			$heartbeat_ok ? 'UptimeRobot heartbeat ping sent.' : 'UptimeRobot heartbeat ping failed.',
			array('url' => $options['uptimerobot_heartbeat_url'])
		);
	}