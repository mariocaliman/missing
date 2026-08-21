<?php
session_start();
header("Content-type: text/html; charset=utf8");
set_time_limit(60000*60);
error_reporting(E_ERROR);
if(!isset($_SESSION['rss_script_admin'])) {
header("location:login.php");
exit;
}
include('../include/config.php');
include('../include/connect.php');
include('include/functions.php');
include('include/setting.php');
include('include/general.class.php');
$general = new General;
$general->set_connection($mysqli);
$case = make_safe(xss_clean($_GET['case']));
$action = make_safe(xss_clean($_POST['action'])); 
// sort categories 
if ($action == "sort_category"){
	$records = $_POST['records'];
	$counter = 1;
	foreach ($records as $record) {
		$sql = "UPDATE categories SET category_order='$counter' WHERE id='$record'";
		$query = $mysqli->query($sql);
		$counter = $counter + 1;	
	}
}
// sort links
if ($action == "sort_links"){
	$records = $_POST['records'];
	$counter = 1;
	foreach ($records as $record) {
		$sql = "UPDATE links SET link_order='$counter' WHERE id='$record'";
		$query = $mysqli->query($sql);
		$counter = $counter + 1;	
	}
}
// sort pages
if ($action == "sort_pages"){
	$records = $_POST['records'];
	$counter = 1;
	foreach ($records as $record) {
		$sql = "UPDATE pages SET page_order='$counter' WHERE id='$record'";
		$query = $mysqli->query($sql);
		$counter = $counter + 1;	
	}
}
// remove article image
if ($action == "remove_image") {
	$id = abs(intval($_POST['id']));
	if (empty($id)) {
	header("location:login.php");
	}
	$sql = "SELECT thumbnail FROM news WHERE id='$id'";
	$query = $mysqli->query($sql);
	$row = $query->fetch_assoc();
	if (file_exists('../upload/news/'.$row['thumbnail'])) {
		@unlink('../upload/news/'.$row['thumbnail']);
	}
	$mysqli->query("UPDATE news SET thumbnail='' WHERE id='$id'");
}
// import news from source
if ($action == "news_grab") {
	@require_once('include/autoload.php');
	$id = abs(intval($_POST['id']));
	if (empty($id)) {
	header("location:login.php");
	}
	$sql = "SELECT * FROM sources WHERE id='$id' LIMIT 1";
	$query = $mysqli->query($sql);
	$row = $query->fetch_assoc();
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

			$title = $mysqli->real_escape_string(htmlspecialchars($item['title'], ENT_QUOTES));
			$content = isset($item['content']) ? $item['content'] : '';
			$description = isset($item['description']) ? $item['description'] : '';
			$fallback_details = (mb_strlen(strip_tags($content),'UTF-8') > mb_strlen(strip_tags($description),'UTF-8')) ? $content : $description;
			$article_data = get_article_data_from_url($permalink, $fallback_details);
				$rewritten_details = rewrite_feed_article_details($item['title'], $article_data['details']);
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

				if (!empty($image)) {
					$filetype = strtolower(pathinfo($image, PATHINFO_EXTENSION));
					if (!in_array($filetype,array('jpg','jpeg','png','gif','webp'))) {
						$filename = ''; 	
					} else {
						$filename = 'image_'.time().'_'.rand(0000000,99999999).'.'.$filetype;
						$up = @file_put_contents('../upload/news/'.$filename,@file_get_contents($image));
						if ($up !== false && file_exists('../upload/news/'.$filename)) {
							$size = filesize('../upload/news/'.$filename);
							list($width) = getimagesize('../upload/news/'.$filename);
							if(intval($size) >= 1024 AND $width >= 40) {
								$filename = $filename;
							} else {
								@unlink('../upload/news/'.$filename);
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
				}
			}
	$now = time();
	$mysqli->query("UPDATE sources SET latest_activity='$now' WHERE id='$id'");
}