<?php
header("Content-Type: application/xml; charset=utf-8");
error_reporting(E_ERROR);
include('include/config.php');
include('include/connect.php');
include('include/functions.php');
include('include/setting.php');
include('include/general.class.php');

$general = new General;
$general->set_connection($mysqli);
$general_setting = $general->get_options('General');
$siteurl = rss_normalize_site_url(isset($general_setting['siteurl']) ? $general_setting['siteurl'] : '');
$today = date('Y-m-d');
$sitemap_items = isset($general_setting['sitemap_items']) && intval($general_setting['sitemap_items']) > 0 ? intval($general_setting['sitemap_items']) : 1000;

$sitemap .= '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="include/sitemap.xsl"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
				    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

if ($siteurl !== '' && ($mysqli instanceof mysqli) && !$mysqli->connect_errno) {
	$published_query = $mysqli->query("SELECT COUNT(*) AS total FROM news WHERE published='1'");
	$published_total = 0;
	if ($published_query && ($published_row = $published_query->fetch_assoc())) {
		$published_total = intval($published_row['total']);
	}
	$news_pages = max(1, (int) ceil(max(0, $published_total) / max(1, $sitemap_items)));

	$sitemap_urls = array(
		$siteurl . '/categories-sitemap.xml'
	);
	for ($page = 1; $page <= $news_pages; $page++) {
		$sitemap_urls[] = $siteurl . '/sitemap-' . $page . '.xml';
	}

	foreach ($sitemap_urls as $sitemap_url) {
		$sitemap .= "<sitemap>";
		$sitemap .= "<loc>" . htmlspecialchars($sitemap_url, ENT_QUOTES, 'UTF-8') . "</loc>";
		$sitemap .= "<lastmod>" . $today . "</lastmod>";
		$sitemap .= "</sitemap>";
	}
}

$sitemap .= "</sitemapindex>";
echo $sitemap;
