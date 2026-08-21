<?php
error_reporting(E_ERROR);
include ('include/config.php');
include ('include/connect.php');
include ('include/functions.php');
include ('include/rss.php');
include ('include/setting.php');
include('include/general.class.php');

$general = new General();
$general->set_connection($mysqli);
$general_setting = $general->get_options('General') ?? [];

$siteurl = $general_setting['siteurl'] ?? '';
$site_title = $general_setting['seo_title'] ?? 'RSS News';
$site_description = $general_setting['seo_description'] ?? 'Latest news';

if (isset($general_setting['rss_news_number']) && (int) $general_setting['rss_news_number'] > 0) {
    $news_number = (int) $general_setting['rss_news_number'];
} else {
    $news_number = 10;
}

$feed = new RSS();
$feed->title = $site_title;
$feed->link = $siteurl;
$feed->description = $site_description;

$result = $mysqli->query("SELECT * FROM news WHERE published='1' ORDER BY id DESC LIMIT $news_number");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shortdes = mb_substr(strip_tags(htmlspecialchars_decode($row['details'], ENT_QUOTES)), 0, 255, 'UTF-8');
        $shortdes = preg_replace('/\s+/', ' ', trim($shortdes));

        $item = new RSSItem();
        $item->title = html_entity_decode($row['title'], ENT_QUOTES, 'UTF-8');
        $item->link = $siteurl . '/news/' . (int) $row['id'] . '/' . url_slug($row['title']);
        $item->description = $shortdes;
        $item->setPubDate($row['datetime']);
        $feed->addItem($item);
    }
}

echo $feed->serve();
