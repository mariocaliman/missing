<?php
session_start();
// check if the admin is logged, if not redirect it to login page
if(!isset($_SESSION['rss_script_admin'])) {
header("location:login.php");
exit;
}
error_reporting(E_ERROR); // hide notices and warnings and show only the real errors
// include database connection files and other neccessary classes and functions.
include("../include/config.php");
include("../include/connect.php");
include("include/functions.php");
include("../include/donation_tracking.php");
include("include/setting.php");
include("include/general.class.php");
include("include/upload.class.php");
include("include/pagination.php");
include("include/nocsrf.php");
// define the general class
$general = new General;
$general->set_connection($mysqli);
// fetch the current url to get the page name
$parts = Explode('/', $_SERVER["PHP_SELF"]);
$currenttab = $parts[count($parts) - 1];

$pending_tips_count = 0;
$pending_donations_count = 0;
$pending_tickets_count = 0;
if (($mysqli instanceof mysqli) && !$mysqli->connect_errno) {
	ensure_news_tips_table();
	ensure_support_tickets_table();
	donation_tracking_ensure_table($mysqli);

	$tips_result = $mysqli->query("SELECT COUNT(*) AS total FROM news_tips WHERE status='pending'");
	if ($tips_result && ($tips_row = $tips_result->fetch_assoc())) {
		$pending_tips_count = intval($tips_row['total']);
	}

	$donations_result = $mysqli->query("SELECT COUNT(*) AS total FROM donation_tracking WHERE status='pending'");
	if ($donations_result && ($donations_row = $donations_result->fetch_assoc())) {
		$pending_donations_count = intval($donations_row['total']);
	}

	$tickets_result = $mysqli->query("SELECT COUNT(*) AS total FROM support_tickets WHERE status='open'");
	if ($tickets_result && ($tickets_row = $tickets_result->fetch_assoc())) {
		$pending_tickets_count = intval($tickets_row['total']);
	}
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">	
    <title>RSS News | Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link href="https://fonts.googleapis.com/css?family=Titillium+Web:700" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="assets/css/jasny-bootstrap.min.css">
	<link href="assets/js/plugins/morris/morris.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/jquery-ui.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/jasny-bootstrap.min.js"></script>
	<script src="assets/js/jquery_checkall.js"></script>
	<script src="assets/js/plugins/morris/raphael.min.js"></script>
	<script src="assets/js/plugins/morris/morris.min.js"></script>
	<script src="assets/js/plugins/tinymce/tinymce.min.js"></script>
	<script src="assets/js/plugins/tinymce/tinymce-function.js"></script>
	<script src="assets/js/functions.js"></script>
	<style>
	.admin-alert-badge {
		display: inline-block;
		min-width: 18px;
		height: 18px;
		line-height: 18px;
		padding: 0 6px;
		margin-left: 6px;
		border-radius: 999px;
		background: #e53935;
		color: #fff;
		font-size: 11px;
		font-weight: 700;
		text-align: center;
		vertical-align: middle;
	}
	</style>
</head>
<body class="admin-shell">
<nav class="navbar navbar-inverse navbar-fixed-top admin-navbar" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="./"><span class="fa fa-rss"></span> <span class="mini-brand">RSS</span> News</a>
            </div>

            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav admin-main-nav">
                    <li <?php if ($currenttab == 'index.php') { ?>class="active"<?php } ?>><a href="index.php"><span class="fa fa-dashboard"></span> Dashboard</a></li>
                    <li <?php if ($currenttab == 'categories.php') { ?>class="active"<?php } ?>><a href="categories.php"><span class="fa fa-reorder"></span> Categories</a></li>
                    <li <?php if ($currenttab == 'sources.php') { ?>class="active"<?php } ?>><a href="sources.php"><span class="fa fa-rss"></span> Sources</a></li>
					<li <?php if ($currenttab == 'news.php') { ?>class="active"<?php } ?>><a href="news.php"><span class="fa fa-newspaper-o"></span> News</a></li>
					<li <?php if ($currenttab == 'tips.php') { ?>class="active"<?php } ?>><a href="tips.php"><span class="fa fa-shield"></span> Tips<?php if ($pending_tips_count > 0) { ?><span class="admin-alert-badge"><?php echo $pending_tips_count; ?></span><?php } ?></a></li>
					<li <?php if ($currenttab == 'donations.php') { ?>class="active"<?php } ?>><a href="donations.php"><span class="fa fa-heart"></span> Donations<?php if ($pending_donations_count > 0) { ?><span class="admin-alert-badge"><?php echo $pending_donations_count; ?></span><?php } ?></a></li>
					<li <?php if ($currenttab == 'tickets.php') { ?>class="active"<?php } ?>><a href="tickets.php"><span class="fa fa-life-ring"></span> Tickets<?php if ($pending_tickets_count > 0) { ?><span class="admin-alert-badge"><?php echo $pending_tickets_count; ?></span><?php } ?></a></li>
					<li <?php if ($currenttab == 'links.php') { ?>class="active"<?php } ?>><a href="links.php"><span class="fa fa-link"></span> Links</a></li>
					<li <?php if ($currenttab == 'pages.php') { ?>class="active"<?php } ?>><a href="pages.php"><span class="fa fa-file"></span> Pages</a></li>
					<li class="dropdown">
					  <a href="javascript:void();" class="dropdown-toggle" data-toggle="dropdown"><span class="fa fa-cogs"></span> Settings <b class="caret"></b></a>
					  <ul class="dropdown-menu">
						<li><a href="setting.php">General Settings</a></li>
						<li><a href="setting.php?case=theme">Theme Settings</a></li>
						<li><a href="setting.php?case=apis">APIs</a></li>
						<li class="divider"></li>
						<li><a href="setting.php?case=clear_cache">Clear Cache</a></li>
						<li><a href="setting.php?case=optimize_database">Optimize Database</a></li>
						<li class="divider"></li>
						<li><a href="setting.php?case=remove_old_news">Remove Old News</a></li>
					  </ul>
					</li>
                </ul>
				<ul class="nav navbar-nav navbar-right admin-user-nav">
					<li <?php if ($currenttab == 'change_password.php') { ?>class="active"<?php } ?>><a href="change_password.php"><span class="fa fa-lock"></span> Password</a></li>
					<li><a href="javascript:ConfirmLogOut();"><span class="fa fa-sign-out"></span> Logout</a></li>
                </ul>
            </div><!--.nav-collapse -->
        </div>
    </nav>
<div class="container admin-container">
