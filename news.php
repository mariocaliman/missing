<?php
include('include/autoloader.php');

if (!function_exists('rss_find_middle_image_for_article')) {
function rss_find_middle_image_for_article($article_id) {
	$article_id = intval($article_id);
	if ($article_id <= 0) {
		return '';
	}
	$dir = __DIR__.'/upload/news/middle/';
	if (!is_dir($dir)) {
		return '';
	}
	$matches = glob($dir.$article_id.'_*');
	if (!$matches || count($matches) === 0) {
		return '';
	}
	return basename($matches[0]);
}
}

if (!function_exists('rss_image_dimensions')) {
function rss_image_dimensions($path) {
	$dimensions = array('width' => 0, 'height' => 0);
	$path = trim((string) $path);
	if ($path === '' || !is_file($path)) {
		return $dimensions;
	}
	$info = @getimagesize($path);
	if ($info !== false) {
		$dimensions['width'] = isset($info[0]) ? intval($info[0]) : 0;
		$dimensions['height'] = isset($info[1]) ? intval($info[1]) : 0;
	}
	return $dimensions;
}
}

if (!function_exists('rss_article_seo_description')) {
function rss_article_seo_description($title, $details, $limit = 160) {
	$title = trim((string) html_entity_decode($title, ENT_QUOTES, 'UTF-8'));
	$details = trim((string) html_entity_decode($details, ENT_QUOTES, 'UTF-8'));
	$details = preg_replace('/\s+/u', ' ', strip_tags($details));
	$description = $title;
	if ($details !== '') {
		$description .= ' - ' . $details;
	}
	$description = trim($description);
	if (mb_strlen($description, 'UTF-8') > $limit) {
		$description = mb_substr($description, 0, $limit - 1, 'UTF-8');
		$description = rtrim($description, " ,;:-");
	}
	return $description;
}
}

if (!function_exists('rss_has_updated_case_timeline')) {
function rss_has_updated_case_timeline($details)
{
	$details = trim((string) html_entity_decode((string) $details, ENT_QUOTES, 'UTF-8'));
	if ($details === '') {
		return 0;
	}

	$plain = strtolower(preg_replace('/\s+/u', ' ', strip_tags($details)));
	if ($plain === '') {
		return 0;
	}

	return (strpos($plain, 'case timeline') !== false) ? 1 : 0;
}
}

if (!function_exists('rss_emphasize_case_timeline_and_dates')) {
function rss_emphasize_case_timeline_and_dates($text)
{
	$text = (string) $text;
	if ($text === '') {
		return '';
	}

	$text = preg_replace('/\b(Case\s+Timeline)\b/i', '<strong>$1</strong>', $text);
	$text = preg_replace('/\b(Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:t(?:ember)?)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{1,2},\s+\d{4}\b/i', '<strong>$0</strong>', $text);
	$text = preg_replace('/\b\d{1,2}\/\d{1,2}\/(?:\d{2}|\d{4})\b/', '<strong>$0</strong>', $text);

	return $text;
}
}

if (!function_exists('rss_prepare_article_details_for_render')) {
function rss_prepare_article_details_for_render($details, $source_id)
{
	$decoded = htmlspecialchars_decode((string) $details, ENT_QUOTES);
	if (intval($source_id) !== 0) {
		$decoded = strip_tags($decoded);
	}

	$decoded = rss_emphasize_case_timeline_and_dates($decoded);
	return nl2br($decoded);
}
}

if (!function_exists('rss_article_schema_json')) {
function rss_article_schema_json($article, $article_url, $thumbnail_url, $category_name) {
	$title = html_entity_decode((string) $article['title'], ENT_QUOTES, 'UTF-8');
	$details = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $article['details'], ENT_QUOTES, 'UTF-8'))));
	$published_iso = date('c', intval($article['datetime']));
	$image = array();
	if (!empty($thumbnail_url)) {
		$image[] = $thumbnail_url;
	}

	$article_schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'Article',
		'headline' => $title,
		'description' => rss_article_seo_description($title, $details, 160),
		'datePublished' => $published_iso,
		'dateModified' => $published_iso,
		'mainEntityOfPage' => $article_url,
		'url' => $article_url,
		'publisher' => array(
			'@type' => 'Organization',
			'name' => $GLOBALS['general_setting']['seo_title'] ?? 'Missing USA'
		),
		'articleSection' => $category_name
	);
	if (!empty($image)) {
		$article_schema['image'] = $image;
	}

	$breadcrumb_schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'BreadcrumbList',
		'itemListElement' => array(
			array(
				'@type' => 'ListItem',
				'position' => 1,
				'name' => 'Home',
				'item' => $GLOBALS['general_setting']['siteurl'] ?? '/'
			),
			array(
				'@type' => 'ListItem',
				'position' => 2,
				'name' => $category_name !== '' ? $category_name : 'Article',
				'item' => $article_url
			),
			array(
				'@type' => 'ListItem',
				'position' => 3,
				'name' => $title,
				'item' => $article_url
			)
		)
	);

	return array(
		'article' => json_encode($article_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
		'breadcrumb' => json_encode($breadcrumb_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
	);
}
}

if (!function_exists('rss_is_remote_image_url')) {
function rss_is_remote_image_url($value) {
	$value = trim((string) $value);
	if ($value === '') {
		return false;
	}
	return filter_var($value, FILTER_VALIDATE_URL) !== false;
}
}

if (!function_exists('rss_build_absolute_image_url')) {
function rss_build_absolute_image_url($src, $siteurl)
{
	$src = trim((string) $src);
	$siteurl = rtrim((string) $siteurl, '/');
	if ($src === '') {
		return '';
	}

	if (preg_match('~^https?://~i', $src)) {
		return $src;
	}

	if (strpos($src, '//') === 0) {
		return 'https:' . $src;
	}

	if ($siteurl === '') {
		return '';
	}

	if ($src[0] !== '/') {
		$src = '/' . $src;
	}

	return $siteurl . $src;
}
}

if (!function_exists('rss_extract_first_share_image_from_details')) {
function rss_extract_first_share_image_from_details($details_html, $siteurl)
{
	$details_html = (string) $details_html;
	if ($details_html === '') {
		return '';
	}

	if (!preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $details_html, $matches)) {
		return '';
	}

	$blocked_parts = array(
		'/themes/default/images/logo',
		'/themes/default-rtl/images/logo',
		'/upload/noimage',
		'logo.'
	);

	foreach ($matches[1] as $src) {
		$url = rss_build_absolute_image_url($src, $siteurl);
		if ($url === '') {
			continue;
		}

		$lower = strtolower($url);
		$blocked = false;
		foreach ($blocked_parts as $part) {
			if (strpos($lower, $part) !== false) {
				$blocked = true;
				break;
			}
		}
		if ($blocked) {
			continue;
		}

		return $url;
	}

	return '';
}
}

if (!function_exists('rss_normalize_share_image_url')) {
function rss_normalize_share_image_url($url)
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

if (!function_exists('rss_detect_image_extension_from_content_type')) {
function rss_detect_image_extension_from_content_type($content_type)
{
	$content_type = strtolower(trim((string) $content_type));
	if ($content_type === 'image/jpeg' || $content_type === 'image/jpg') {
		return 'jpg';
	}
	if ($content_type === 'image/png') {
		return 'png';
	}
	if ($content_type === 'image/webp') {
		return 'webp';
	}
	if ($content_type === 'image/gif') {
		return 'gif';
	}
	return '';
}
}

if (!function_exists('rss_cache_remote_share_image')) {
function rss_cache_remote_share_image($remote_url, $article_id)
{
	$remote_url = trim((string) $remote_url);
	$article_id = intval($article_id);
	if ($remote_url === '' || $article_id <= 0 || !filter_var($remote_url, FILTER_VALIDATE_URL)) {
		return '';
	}

	$cache_dir = __DIR__ . '/upload/news/share/';
	if (!is_dir($cache_dir) && !@mkdir($cache_dir, 0755, true)) {
		return '';
	}

	$existing = glob($cache_dir . 'article_' . $article_id . '.*');
	if ($existing && isset($existing[0]) && is_file($existing[0])) {
		$age = time() - filemtime($existing[0]);
		if ($age < 86400 * 7) {
			return basename($existing[0]);
		}
	}

	$body = false;
	$content_type = '';
	if (function_exists('curl_init')) {
		$ch = curl_init($remote_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)');
		$body = curl_exec($ch);
		$content_type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		if ($http_code < 200 || $http_code >= 400) {
			$body = false;
		}
	}

	if ($body === false) {
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'timeout' => 15,
				'ignore_errors' => true,
				'header' => "User-Agent: facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)\r\n"
			)
		));
		$body = @file_get_contents($remote_url, false, $context);
	}

	if ($body === false || $body === '') {
		return '';
	}

	if (strlen($body) > 8 * 1024 * 1024) {
		return '';
	}

	$ext = '';
	$path_ext = strtolower(pathinfo(parse_url($remote_url, PHP_URL_PATH), PATHINFO_EXTENSION));
	if (in_array($path_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
		$ext = ($path_ext === 'jpeg') ? 'jpg' : $path_ext;
	}
	if ($ext === '') {
		$ext = rss_detect_image_extension_from_content_type($content_type);
	}
	if ($ext === '') {
		$img_info = @getimagesizefromstring($body);
		if ($img_info && isset($img_info['mime'])) {
			$ext = rss_detect_image_extension_from_content_type($img_info['mime']);
		}
	}
	if ($ext === '') {
		$ext = 'jpg';
	}

	foreach (glob($cache_dir . 'article_' . $article_id . '.*') as $old_file) {
		@unlink($old_file);
	}

	$filename = 'article_' . $article_id . '.' . $ext;
	$target = $cache_dir . $filename;
	$written = @file_put_contents($target, $body);
	if ($written === false || !is_file($target)) {
		return '';
	}

	$img_info = @getimagesize($target);
	if ($img_info === false || intval($img_info[0]) < 40 || intval($img_info[1]) < 40) {
		@unlink($target);
		return '';
	}

	return $filename;
}
}

if (!function_exists('rss_is_famous_missing_category')) {
function rss_is_famous_missing_category($category_name)
{
	$normalized = strtolower(trim((string) $category_name));
	$aliases = array(
		'famous missing persons cases',
		'famous missing person cases',
		'famous missing persons',
		'famous missing person'
	);
	return in_array($normalized, $aliases, true);
}
}

if (!function_exists('rss_find_famous_article_by_slug')) {
function rss_find_famous_article_by_slug($slug)
{
	global $mysqli;
	$slug = trim((string) $slug);
	if ($slug === '' || !($mysqli instanceof mysqli) || $mysqli->connect_errno) {
		return 0;
	}

	$category_ids = array();
	$categories_q = $mysqli->query("SELECT id, category FROM categories");
	if ($categories_q) {
		while ($cat = $categories_q->fetch_assoc()) {
			if (rss_is_famous_missing_category($cat['category'])) {
				$category_ids[] = intval($cat['id']);
			}
		}
	}

	if (empty($category_ids)) {
		return 0;
	}

	$like = $mysqli->real_escape_string(str_replace('-', '%', $slug));
	$where_ids = implode(',', $category_ids);
	$sql = "SELECT * FROM news WHERE published='1' AND category_id IN (" . $where_ids . ") AND title LIKE '%" . $like . "%' ORDER BY id DESC LIMIT 500";
	$query = $mysqli->query($sql);
	if (!$query) {
		return 0;
	}

	while ($row = $query->fetch_assoc()) {
		if (slugit($row['title']) === $slug) {
			return $row;
		}
	}

	return 0;
}
}
// recieve the article id and slug variables
$id = isset($_GET['id']) ? intval(make_safe(xss_clean($_GET['id']))) : 0;
$slug = isset($_GET['slug']) ? make_safe(xss_clean($_GET['slug'])) : '';
$famous_slug = isset($_GET['famous_slug']) ? make_safe(xss_clean($_GET['famous_slug'])) : '';
$smarty->assign('is_article',1);
$article = 0;
if ($id > 0) {
	$article = $general->article($id);
} elseif ($famous_slug !== '') {
	$article = rss_find_famous_article_by_slug($famous_slug);
}
// check if the article exists, if not redirect to error page 
if ($article == 0) {
header('Location:'.$general_setting['siteurl'].'/not-found');	
}
// fetching the result
foreach ($article AS $key=>$value) {
$smarty->assign('article_'.$key,$value);	
}
$article_category = $general->category($article['category_id']);
$article_category_name = '';
$article_category_url = '';
if ($article_category != 0 && isset($article_category['category'])) {
$article_category_name = trim($article_category['category']);
$article_category_url = $general_setting['siteurl'] . '/category/' . intval($article['category_id']) . '/' . slugit($article_category_name);
}
$article_category_lower = strtolower($article_category_name);
$stories_aliases = array('case & stories', 'case & sotories', 'cases & stories', 'cases & sotories');
$article_category_name_normalized = in_array($article_category_lower,$stories_aliases) ? 'Cases & Stories' : $article_category_name;
$is_famous_category = rss_is_famous_missing_category($article_category_name) ? 1 : 0;
$smarty->assign('article_category_name',$article_category_name);
$smarty->assign('article_category_display_name',$article_category_name_normalized);
$editorial_categories = array('explained', 'case & stories', 'case & sotories', 'cases & stories', 'cases & sotories');
$is_editorial_category = (in_array($article_category_lower,$editorial_categories) || $is_famous_category == 1) ? 1 : 0;
$smarty->assign('is_explained_category',$is_editorial_category);
$middle_image_name = '';
if ($is_editorial_category == 1) {
	$middle_image_name = rss_find_middle_image_for_article($article['id']);
	$smarty->assign('article_middle_image',$middle_image_name);
	if (!empty($middle_image_name)) {
		$middle_dimensions = rss_image_dimensions(__DIR__ . '/upload/news/middle/' . $middle_image_name);
		$smarty->assign('middle_image_width',$middle_dimensions['width']);
		$smarty->assign('middle_image_height',$middle_dimensions['height']);
	}
} else {
	$smarty->assign('article_middle_image','');
}
// related news method found in include/general.class.php
$related = $general->related($article['id'],$article['category_id'],$article['title'],$theme_setting['related_news_number']);
// if there related news then assign them.
if ($related != 0) {
$smarty->assign('related',$related);
}
$famous_url = $general_setting['siteurl']."/famous-missing-persons/".slugit($article['title'])."/";
$url = $is_famous_category ? $famous_url : ($general_setting['siteurl']."/news/".$article['id']."/".slugit($article['title']));
$article_url = str_replace(':/','://',str_replace('//','/',($url)));

if ($is_famous_category == 1 && $famous_slug === '') {
	header('Location:'.$article_url, true, 301);
	exit;
}

$smarty->assign('article_url',$article_url);
$smarty->assign('canonical_url',$article_url);
$smarty->assign('article_thumbnail_src','');
$thumbnail_url = '';
if (!empty($article['thumbnail'])) {
	if (rss_is_remote_image_url($article['thumbnail'])) {
		$thumbnail_url = rss_normalize_share_image_url($article['thumbnail']);
		$cached_share_image = rss_cache_remote_share_image($thumbnail_url, $article['id']);
		if ($cached_share_image !== '' && !empty($general_setting['siteurl'])) {
			$thumbnail_url = rtrim($general_setting['siteurl'], '/') . '/upload/news/share/' . $cached_share_image;
		}
		$smarty->assign('thumbnail_url',$thumbnail_url);
		$smarty->assign('article_thumbnail_src',$thumbnail_url);
	} else {
		$thumbnail = $general_setting['siteurl'].'/upload/news/'.$article['thumbnail'];
		$thumbnail_url = str_replace(':/','://',str_replace('//','/',($thumbnail)));
		$smarty->assign('thumbnail_url',$thumbnail_url);
		$smarty->assign('article_thumbnail_src','./upload/news/'.$article['thumbnail']);
		$thumbnail_dimensions = rss_image_dimensions(__DIR__ . '/upload/news/' . $article['thumbnail']);
		$smarty->assign('thumbnail_width',$thumbnail_dimensions['width']);
		$smarty->assign('thumbnail_height',$thumbnail_dimensions['height']);
	}
}

if (empty($thumbnail_url) && !empty($article['details'])) {
	$details_image = rss_extract_first_share_image_from_details($article['details'], $general_setting['siteurl']);
	if (!empty($details_image)) {
		$thumbnail_url = rss_normalize_share_image_url($details_image);
		if (rss_is_remote_image_url($thumbnail_url)) {
			$cached_share_image = rss_cache_remote_share_image($thumbnail_url, $article['id']);
			if ($cached_share_image !== '' && !empty($general_setting['siteurl'])) {
				$thumbnail_url = rtrim($general_setting['siteurl'], '/') . '/upload/news/share/' . $cached_share_image;
			}
		}
		$smarty->assign('thumbnail_url', $thumbnail_url);
		$smarty->assign('article_thumbnail_src', $thumbnail_url);
	}
}

if (empty($thumbnail_url) && !empty($middle_image_name)) {
	$middle_image = $general_setting['siteurl'].'/upload/news/middle/'.$middle_image_name;
	$thumbnail_url = str_replace(':/','://',str_replace('//','/',($middle_image)));
	$thumbnail_url = rss_normalize_share_image_url($thumbnail_url);
	$smarty->assign('thumbnail_url', $thumbnail_url);
	$smarty->assign('article_thumbnail_src', './upload/news/middle/'.$middle_image_name);
}

if (!empty($thumbnail_url)) {
	$smarty->assign('thumbnail_secure_url', rss_normalize_share_image_url($thumbnail_url));
}
// assign the SEO variables (title,keywords,description).	
$site_title = !empty($general_setting['seo_title']) ? $general_setting['seo_title'] : 'Missing USA';
$article_title = htmlspecialchars_decode($article['title'],ENT_QUOTES);
$smarty->assign('seo_title',$article_title . ' - ' . $site_title);	
$smarty->assign('seo_keywords',title_to_keywords(htmlspecialchars_decode($article['title'],ENT_QUOTES)));
$smarty->assign('seo_description',rss_article_seo_description($article['title'],$article['details'],160));
$schema_data = rss_article_schema_json($article,$article_url,!empty($thumbnail_url) ? $thumbnail_url : '',$article_category_name_normalized);
$breadcrumb_schema = json_decode($schema_data['breadcrumb'], true);
if (is_array($breadcrumb_schema) && isset($breadcrumb_schema['itemListElement'][1])) {
	$breadcrumb_schema['itemListElement'][1]['item'] = $article_category_url !== '' ? $article_category_url : $article_url;
	$schema_data['breadcrumb'] = json_encode($breadcrumb_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
$smarty->assign('article_schema_json',$schema_data['article']);
$smarty->assign('breadcrumb_schema_json',$schema_data['breadcrumb']);
$smarty->assign('has_updated_timeline', rss_has_updated_case_timeline($article['details']));
$smarty->assign('article_details_rendered', rss_prepare_article_details_for_render($article['details'], $article['source_id']));
// display the article HTML 
$smarty->display('article.html');
?>