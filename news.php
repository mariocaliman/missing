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
// recieve the article id and slug variables
$id = intval(make_safe(xss_clean($_GET['id'])));
$slug = make_safe(xss_clean($_GET['slug']));
$smarty->assign('is_article',1);
$article = $general->article($id);
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
$smarty->assign('article_category_name',$article_category_name);
$smarty->assign('article_category_display_name',$article_category_name_normalized);
$editorial_categories = array('explained', 'case & stories', 'case & sotories', 'cases & stories', 'cases & sotories');
$is_editorial_category = in_array($article_category_lower,$editorial_categories) ? 1 : 0;
$smarty->assign('is_explained_category',$is_editorial_category);
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
$url = $general_setting['siteurl']."/news/".$article['id']."/".slugit($article['title']);
$article_url = str_replace(':/','://',str_replace('//','/',($url)));
$smarty->assign('article_url',$article_url);
$smarty->assign('canonical_url',$article_url);
if (!empty($article['thumbnail'])) {
$thumbnail = $general_setting['siteurl'].'/upload/news/'.$article['thumbnail'];
$thumbnail_url = str_replace(':/','://',str_replace('//','/',($thumbnail)));
$smarty->assign('thumbnail_url',$thumbnail_url);
	$thumbnail_dimensions = rss_image_dimensions(__DIR__ . '/upload/news/' . $article['thumbnail']);
	$smarty->assign('thumbnail_width',$thumbnail_dimensions['width']);
	$smarty->assign('thumbnail_height',$thumbnail_dimensions['height']);
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
// display the article HTML 
$smarty->display('article.html');
?>