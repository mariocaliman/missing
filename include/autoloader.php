<?php
// hide the warnings and notices and display only the real errors.
error_reporting(E_ERROR);

$include_dir = __DIR__;
$project_dir = dirname($include_dir);

// include required files
include($include_dir . '/config.php');
include($include_dir . '/connect.php');
include($include_dir . '/functions.php');
include($include_dir . '/setting.php');
include($include_dir . '/pagination.php');
include($include_dir . '/general.class.php');
// check if the script installed
if (!isset($options['installed']) OR $options['installed'] == 0) {
die('You Should Install the Script. <a href="install.php">Go to Installation</a>');	
}
// check if the install.php still exists
if (isset($options['installed']) AND $options['installed'] == 1 AND file_exists('install.php')) {
die('Please Delete the install.php file or rename it for security reasons.');		
}
// define general class and setting
$general = null;
if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $general = new General;
    $general->set_connection($mysqli);
}
// define smarty templates class and setting
require_once($include_dir . '/smarty/Smarty.class.php');
$smarty = new Smarty;
$smarty->compile_dir = $project_dir . '/cache/';
$smarty->template_dir = $project_dir . '/themes/' . (!empty($options['site_theme']) ? $options['site_theme'] : 'default') . '/';
$smarty->plugins_dir = array(
                       $include_dir . '/smarty/plugins'
                       );
$smarty->force_compile = true;

// setting to fetch and assign to smarty
if ($general) {
    $general_setting = $general->get_options('General');
    $theme_setting = $general->get_options('Theme');
} else {
    $general_setting = array(
        'seo_title' => '',
        'seo_keywords' => '',
        'seo_description' => '',
        'top_news_period' => 0,
    );
    $theme_setting = array(
        'top_news_number' => 0,
        'home_category_news_number' => 0,
        'allow_lazyload' => 0,
    );
}
foreach ($general_setting AS $key=>$value) {
$smarty->assign('general_'.$key,$value);
}
foreach ($theme_setting AS $key=>$value) {
$smarty->assign('theme_'.$key,$value);
}

$organization_schema = array(
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => !empty($general_setting['seo_title']) ? $general_setting['seo_title'] : 'Missing USA',
    'url' => !empty($general_setting['siteurl']) ? $general_setting['siteurl'] : '',
    'logo' => (!empty($general_setting['siteurl']) ? $general_setting['siteurl'] : '') . '/themes/default/images/logo.png'
);
$smarty->assign('organization_schema_json', json_encode($organization_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
// queries that run in all pages
    if ($general) {
// categories query
        $categories = $general->categories('category_order ASC');
$smarty->assign('categories',$categories);
// links query
        $links = $general->links('link_order ASC');
$smarty->assign('links',$links);
// pages query
        $pages = $general->pages('page_order ASC');
$smarty->assign('pages',$pages);
// top news query
        $top = $general->news($general_setting['top_news_period'],'hits DESC',$theme_setting['top_news_number']);
$smarty->assign('top',$top);
    } else {
        $smarty->assign('categories',array());
        $smarty->assign('links',array());
        $smarty->assign('pages',array());
        $smarty->assign('top',array());
    }
// ads blocks
$header_ad = file_get_contents($project_dir . '/ads/header.txt');
$smarty->assign('header_ad',$header_ad);
$widget_ad = file_get_contents($project_dir . '/ads/widget.txt');
$smarty->assign('widget_ad',$widget_ad);
$content_ad = file_get_contents($project_dir . '/ads/content.txt');
$smarty->assign('content_ad',$content_ad);

$donate_thank_you_url = !empty($general_setting['siteurl']) ? rtrim($general_setting['siteurl'], '/') . '/thank-you-for-donating' : './thank-you-for-donating';
$donate_paypal_url = 'https://www.paypal.com/donate/?hosted_button_id=R7F6DQKU83LJQ&return=' . urlencode($donate_thank_you_url) . '&cancel_return=' . urlencode(!empty($general_setting['siteurl']) ? rtrim($general_setting['siteurl'], '/') . '/' : './');
$smarty->assign('donate_thank_you_url', $donate_thank_you_url);
$smarty->assign('donate_paypal_url', $donate_paypal_url);
?>
