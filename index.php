<?php
include(__DIR__ . '/include/autoloader.php');
include(__DIR__ . '/include/donation_tracking.php');

if (!function_exists('rss_is_state_category_name')) {
	function rss_is_state_category_name($name)
	{
		$name = trim((string) $name);
		return (bool) preg_match('/^[A-Z]{2}\s*-\s+/', $name);
	}
}

if (!function_exists('rss_compare_categories_by_news_count')) {
	function rss_compare_categories_by_news_count($a, $b)
	{
		$a_count = isset($a['published_count']) ? intval($a['published_count']) : 0;
		$b_count = isset($b['published_count']) ? intval($b['published_count']) : 0;

		if ($a_count === $b_count) {
			$a_order = isset($a['category_order']) ? intval($a['category_order']) : 0;
			$b_order = isset($b['category_order']) ? intval($b['category_order']) : 0;
			if ($a_order === $b_order) {
				$a_id = isset($a['id']) ? intval($a['id']) : 0;
				$b_id = isset($b['id']) ? intval($b['id']) : 0;
				return ($a_id < $b_id) ? -1 : 1;
			}
			return ($a_order < $b_order) ? -1 : 1;
		}

		return ($a_count > $b_count) ? -1 : 1;
	}
}

if (!function_exists('rss_normalize_https_image_url')) {
	function rss_normalize_https_image_url($url)
	{
		$url = trim((string) $url);
		if ($url === '') {
			return '';
		}
		$parts = @parse_url($url);
		if ($parts && isset($parts['scheme']) && strtolower($parts['scheme']) === 'http') {
			$url = 'https://' . ltrim(substr($url, 7), '/');
		}
		return $url;
	}
}

if (!function_exists('rss_article_thumb_url')) {
	function rss_article_thumb_url($thumbnail, $siteurl)
	{
		$thumbnail = trim((string) $thumbnail);
		if ($thumbnail === '') {
			return '';
		}

		if (filter_var($thumbnail, FILTER_VALIDATE_URL)) {
			return rss_normalize_https_image_url($thumbnail);
		}

		$path = __DIR__ . '/upload/news/' . $thumbnail;
		if (!is_file($path)) {
			return '';
		}

		$siteurl = rtrim((string) $siteurl, '/');
		if ($siteurl === '') {
			return 'upload/news/' . $thumbnail;
		}

		return $siteurl . '/upload/news/' . $thumbnail;
	}
}

if (($mysqli instanceof mysqli) && !$mysqli->connect_errno) {
	$donation_status = isset($_GET['donation_status']) ? trim($_GET['donation_status']) : '';
	$donation_ref = isset($_GET['donation_ref']) ? trim($_GET['donation_ref']) : '';
	if ($donation_status === 'cancel' && $donation_ref !== '') {
		donation_tracking_mark_status($mysqli, $donation_ref, 'cancel');
	}
}

$smarty->assign('is_home',1); // to use with menu (home select)
$latest_home = array();
if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
	$latest_query = $mysqli->query("SELECT * FROM news WHERE published='1' ORDER BY id DESC LIMIT 20");
	if ($latest_query && $latest_query->num_rows > 0) {
		while ($latest_row = $latest_query->fetch_assoc()) {
			$latest_home[] = $latest_row;
		}
	} else {
		$latest_home = 0;
	}
	$db_available = 1;
	$smarty->assign('db_connection_error','');
	$smarty->assign('db_available',1);
	} else {
	$latest_home = 0;
	$db_available = 0;
	$smarty->assign('db_connection_error',isset($connection_error) ? $connection_error : 'Database unavailable');
	$smarty->assign('db_available',0);
}
$smarty->assign('latest_home',$latest_home);

$weekly_featured_case = 0;
$seen_people_cases = array();
if (($mysqli instanceof mysqli) && !$mysqli->connect_errno) {
	$siteurl = !empty($general_setting['siteurl']) ? $general_setting['siteurl'] : '';
	$featured_since = time() - (7 * 86400);
	$featured_sql = "SELECT id,title,thumbnail,source_id,category_id,datetime,hits FROM news WHERE published='1' AND datetime >= " . intval($featured_since) . " ORDER BY hits DESC, id DESC LIMIT 1";
	$featured_query = $mysqli->query($featured_sql);
	if ($featured_query && $featured_query->num_rows > 0) {
		$weekly_featured_case = $featured_query->fetch_assoc();
	} else {
		$fallback_featured_query = $mysqli->query("SELECT id,title,thumbnail,source_id,category_id,datetime,hits FROM news WHERE published='1' ORDER BY hits DESC, id DESC LIMIT 1");
		if ($fallback_featured_query && $fallback_featured_query->num_rows > 0) {
			$weekly_featured_case = $fallback_featured_query->fetch_assoc();
		}
	}
	if (is_array($weekly_featured_case) && !empty($weekly_featured_case)) {
		$weekly_featured_case['thumb_url'] = rss_article_thumb_url(isset($weekly_featured_case['thumbnail']) ? $weekly_featured_case['thumbnail'] : '', $siteurl);
	}

	$seen_sql = "SELECT id,title,thumbnail,source_id,category_id,datetime,hits FROM news WHERE published='1' AND thumbnail<>'' ORDER BY hits DESC, id DESC LIMIT 12";
	$seen_query = $mysqli->query($seen_sql);
	if ($seen_query && $seen_query->num_rows > 0) {
		while ($row = $seen_query->fetch_assoc()) {
			$row['thumb_url'] = rss_article_thumb_url(isset($row['thumbnail']) ? $row['thumbnail'] : '', $siteurl);
			if ($row['thumb_url'] !== '') {
				$seen_people_cases[] = $row;
			}
			if (count($seen_people_cases) >= 1) {
				break;
			}
		}
	}
}

$smarty->assign('weekly_featured_case', (is_array($weekly_featured_case) && !empty($weekly_featured_case)) ? $weekly_featured_case : 0);
$smarty->assign('seen_people_cases', !empty($seen_people_cases) ? $seen_people_cases : 0);

$home_categories = array();
if (($mysqli instanceof mysqli) && !$mysqli->connect_errno && isset($categories) && is_array($categories)) {
	$selected_categories = array();
	foreach ($categories as $category_item) {
		if (intval($category_item['index_view']) === 1) {
			$selected_categories[] = $category_item;
		}
	}

	$pool_categories = !empty($selected_categories) ? $selected_categories : $categories;
	$published_counts = array();
	$count_query = $mysqli->query("SELECT category_id, COUNT(*) AS total FROM news WHERE published='1' GROUP BY category_id");
	if ($count_query && $count_query->num_rows > 0) {
		while ($count_row = $count_query->fetch_assoc()) {
			$published_counts[intval($count_row['category_id'])] = intval($count_row['total']);
		}
	}

	$state_categories = array();
	$other_categories = array();
	foreach ($pool_categories as $category_item) {
		$category_id = intval($category_item['id']);
		$category_item['published_count'] = isset($published_counts[$category_id]) ? intval($published_counts[$category_id]) : 0;

		if (rss_is_state_category_name($category_item['category'])) {
			if ($category_item['published_count'] > 0) {
				$state_categories[] = $category_item;
			}
		} else {
			$other_categories[] = $category_item;
		}
	}

	if (!empty($state_categories)) {
		usort($state_categories, 'rss_compare_categories_by_news_count');
		$state_categories = array_slice($state_categories, 0, 7);
	}

	$home_categories = array_merge($other_categories, $state_categories);
}

$smarty->assign('home_categories', !empty($home_categories) ? $home_categories : 0);

if (isset($db_available) && !$db_available) {
	header('Content-Type: text/html; charset=UTF-8');
	$site_title = !empty($general_setting['seo_title']) ? $general_setting['seo_title'] : 'RSS News';
	$message = !empty($connection_error) ? $connection_error : 'Database unavailable';
	echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8') . '</title><style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#1f2937;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.card{max-width:640px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.08);padding:32px}h1{margin:0 0 12px;font-size:28px}p{margin:0;line-height:1.6;color:#4b5563}.hint{margin-top:16px;font-size:14px;color:#6b7280}</style></head><body><main class="card"><h1>' . htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8') . '</h1><p>Home page temporarily unavailable.</p><p class="hint">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
	exit;
}

// assign the SEO variables (title,keywords,description).	
$smarty->assign('seo_title',$general_setting['seo_title']);	
$smarty->assign('seo_keywords',$general_setting['seo_keywords']);
$smarty->assign('seo_description',$general_setting['seo_description']);
// display the index HTML 
$smarty->display('index.html');
?>