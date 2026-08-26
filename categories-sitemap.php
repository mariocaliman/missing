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
$siteurl = $general_setting['siteurl'];
$today = date('Y-m-d');	
$sitemap .= '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="include/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
			    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
                            		
$query = $mysqli->query("SELECT * FROM categories ORDER BY id ASC");
while($row = $query->fetch_assoc())
{
	$category_name = trim((string) $row['category']);
	if (preg_match('/^[A-Z]{2}\s*-\s*(.+)$/', $category_name, $matches)) {
		$state_name = trim($matches[1]);
		$url = $siteurl."/missing-persons/".slugit($state_name)."/";
	} else {
		$url = $siteurl."/category/".$row['id']."/".slugit($row['category']);
	}
$slugged = str_replace(':/','://',str_replace('//','/',($url)));
$sitemap .= "<url>";
$sitemap .= "<loc>$slugged</loc>";
$sitemap .= "<lastmod>$today</lastmod>
<changefreq>daily</changefreq>
<priority>0.8</priority>
</url>";
}

$submit_missing_person_url = str_replace(':/','://',str_replace('//','/',($siteurl.'/submit-missing-person')));
$sitemap .= "<url>";
$sitemap .= "<loc>$submit_missing_person_url</loc>";
$sitemap .= "<lastmod>$today</lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>";
$sitemap .= "</urlset>";
echo $sitemap;

?>