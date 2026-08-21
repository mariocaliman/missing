<?php
include('include/autoloader.php');
// recieve the category id and slug variables
$id = isset($_GET['id']) ? intval(make_safe(xss_clean($_GET['id']))) : 0;
$slug = isset($_GET['slug']) ? make_safe(xss_clean($_GET['slug'])) : '';
$state_slug = isset($_GET['state']) ? make_safe(xss_clean($_GET['state'])) : '';
$is_state_page = 0;
$state_name = '';

if ($id <= 0 && $state_slug !== '') {
	$result = $mysqli->query("SELECT id,category FROM categories WHERE category REGEXP '^[A-Z]{2} - '");
	if ($result) {
		while ($row = $result->fetch_assoc()) {
			$category_name = trim((string) $row['category']);
			if (preg_match('/^[A-Z]{2}\s*-\s*(.+)$/', $category_name, $m)) {
				$candidate_state = trim($m[1]);
				if (slugit($candidate_state) === $state_slug) {
					$id = intval($row['id']);
					$slug = slugit($category_name);
					$is_state_page = 1;
					$state_name = $candidate_state;
					break;
				}
			}
		}
	}
}

$smarty->assign('is_category',1); // to use with menu (selected category)
$category = $general->category($id); // the category method found in include/general.class.php
// check if the category exists, if not redirect to error page 
if ($category == 0) {
header('Location:'.$general_setting['siteurl'].'/not-found');	
}

if ($is_state_page == 0 && preg_match('/^[A-Z]{2}\s*-\s*(.+)$/', trim((string) $category['category']), $m2)) {
	$is_state_page = 1;
	$state_name = trim($m2[1]);
}
// fetching the result
foreach ($category AS $key=>$value) {
$smarty->assign('category_'.$key,$value);
}

$canonical_url = $general_setting['siteurl'] . '/category/' . intval($category['id']) . '/' . slugit($category['category']);
if ($is_state_page == 1 && $state_name !== '') {
	$canonical_url = $general_setting['siteurl'] . '/missing-persons/' . slugit($state_name) . '/';
	$smarty->assign('category_heading', 'Missing Persons in ' . $state_name);
	$smarty->assign('category_intro', 'Find information about missing children and adults reported missing in ' . $state_name . '.');
}
$canonical_url = str_replace(':/','://',str_replace('//','/',$canonical_url));
$smarty->assign('canonical_url', $canonical_url);
// fetch the url to get the page id
$ur = explode('?',curPageURL());
if (count($ur) != 0) {
if (isset($ur[1])) {
parse_str($ur[1],$query);	
}
}
	$page = 1; // first page number
	$size = $theme_setting['category_news_number']; // number of news per category page you can change it from theme setting
	if (intval($size) < 1) {
		$size = 12;
	}
	$category_name_lower = strtolower(trim($category['category']));
	$editorial_categories = array('explained', 'case & stories', 'case & sotories', 'cases & stories', 'cases & sotories');
	if (in_array($category_name_lower, $editorial_categories)) {
		$size = max(intval($size), 50);
	}
	if (isset($query['page'])){ $page = (int) $query['page']; }
	// count news number that related to this category
	$sqls = "SELECT * FROM news WHERE published='1' AND category_id='$category[id]'";
	$qu = $mysqli->query($sqls);
	$total_records = $qu->num_rows;
	$smarty->assign('total_records',$total_records);
	if ($total_records > 0) {
	// define the pagination class. found at : include/pagination.php 	
	$pagination = new Pagination();
	$pagination->setLink("./category/$id/$slug?page=%s"); // the link of each page (%s) represent the page number variable
	$pagination->setPage($page);
	$pagination->setSize($size);
	$pagination->setTotalRecords($total_records);
	$get = "SELECT * FROM news WHERE published='1' AND category_id='$category[id]' ORDER BY id DESC ".$pagination->getLimitSql();
	$q = $mysqli->query($get);
	while ($row = $q->fetch_assoc()) {
	$news[] = $row;
	}
	$smarty->assign('news',$news);
	$pagi = $pagination->create_links();
	$smarty->assign('pagi',$pagi);
	}

// assign the SEO variables (title,keywords,description).	
$seo_title = $category['category'];
$seo_description = $category['seo_description'];
if ($is_state_page == 1 && $state_name !== '') {
	$seo_title = 'Missing Persons in ' . $state_name;
	$seo_description = 'Find information about missing children and adults reported missing in ' . $state_name . '.';
}
$smarty->assign('seo_title',$seo_title);	
$smarty->assign('seo_keywords',$category['seo_keywords']);
$smarty->assign('seo_description',$seo_description);
// display the category HTML 
$smarty->display('category.html');
?>