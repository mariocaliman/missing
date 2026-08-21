<?php
include(__DIR__ . '/include/autoloader.php');
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