<?php
include(__DIR__ . '/include/autoloader.php');

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    header('Location:' . $general_setting['siteurl'] . '/not-found');
    exit;
}

ensure_news_tips_table();

$news_id = isset($_GET['news_id']) ? intval($_GET['news_id']) : 0;
$case_title = '';
if ($news_id > 0) {
    $case_stmt = $mysqli->prepare("SELECT title FROM news WHERE published='1' AND id=? LIMIT 1");
    if ($case_stmt) {
        $case_stmt->bind_param('i', $news_id);
        $case_stmt->execute();
        $case_result = $case_stmt->get_result();
        if ($case_result && $case_result->num_rows > 0) {
            $case_row = $case_result->fetch_assoc();
            $case_title = html_entity_decode((string) $case_row['title'], ENT_QUOTES, 'UTF-8');
        }
        $case_stmt->close();
    }
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}
$size = 12;

$where = "status='approved'";
if ($news_id > 0) {
    $where .= " AND news_id='" . $news_id . "'";
}

$count_query = $mysqli->query("SELECT id FROM news_tips WHERE " . $where);
$total_records = $count_query ? (int) $count_query->num_rows : 0;

$tips = array();
$pagi = '';

if ($total_records > 0) {
    $pagination = new Pagination();
    if ($news_id > 0) {
        $pagination->setLink('./approved-tips/' . $news_id . '?page=%s');
    } else {
        $pagination->setLink('./approved-tips?page=%s');
    }
    $pagination->setPage($page);
    $pagination->setSize($size);
    $pagination->setTotalRecords($total_records);

    $sql = "SELECT t.id, t.news_id, t.missing_name, t.tip_location, t.tip_message, t.created_at, n.title AS news_title
            FROM news_tips t
            LEFT JOIN news n ON n.id=t.news_id
            WHERE t." . $where . "
            ORDER BY t.id DESC " . $pagination->getLimitSql();
    $query = $mysqli->query($sql);
    if ($query && $query->num_rows > 0) {
        while ($row = $query->fetch_assoc()) {
            $row['case_slug'] = slugit((string) $row['news_title']);
            $tips[] = $row;
        }
    }

    $pagi = $pagination->create_links();
}

$approved_tips_latest = array();
$latest_query = $mysqli->query("SELECT id, source_id, title, thumbnail, datetime FROM news WHERE published='1' ORDER BY id DESC LIMIT 8");
if ($latest_query && $latest_query->num_rows > 0) {
    while ($latest_row = $latest_query->fetch_assoc()) {
        $approved_tips_latest[] = $latest_row;
    }
}

$smarty->assign('approved_tips', $tips);
$smarty->assign('approved_tips_count', $total_records);
$smarty->assign('approved_tips_pagi', $pagi);
$smarty->assign('approved_tips_news_id', $news_id);
$smarty->assign('approved_tips_case_title', $case_title);
$smarty->assign('approved_tips_latest', $approved_tips_latest);

$seo_title = ($news_id > 0 && $case_title !== '') ? ('Approved Tips - ' . $case_title) : 'Approved Tips';
$smarty->assign('seo_title', $seo_title);
$smarty->assign('seo_keywords', 'approved tips, missing persons, case tips');
$smarty->assign('seo_description', 'Anonymous tips that were approved by administrators.');

$smarty->display('approved-tips.html');
?>