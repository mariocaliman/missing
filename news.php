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
if ($article_category != 0 && isset($article_category['category'])) {
$article_category_name = trim($article_category['category']);
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
	$smarty->assign('article_middle_image',rss_find_middle_image_for_article($article['id']));
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
if (!empty($article['thumbnail'])) {
$thumbnail = $general_setting['siteurl'].'/upload/news/'.$article['thumbnail'];
$thumbnail_url = str_replace(':/','://',str_replace('//','/',($thumbnail)));
$smarty->assign('thumbnail_url',$thumbnail_url);
}
// assign the SEO variables (title,keywords,description).	
$smarty->assign('seo_title',htmlspecialchars_decode($article['title'],ENT_QUOTES));	
$smarty->assign('seo_keywords',title_to_keywords(htmlspecialchars_decode($article['title'],ENT_QUOTES)));
$smarty->assign('seo_description',mb_substr(make_safe(htmlspecialchars_decode($article['details'],ENT_QUOTES)),0,255,'UTF-8'));
// display the article HTML 
$smarty->display('article.html');
?>