<?php
include('header.php');
$start = $general->start_period();
$options = $general->get_options('General');
$published_result = $mysqli->query("SELECT published FROM news WHERE published='1'");
$published_news = $published_result ? $published_result->num_rows : 0;
$categories_result = $mysqli->query("SELECT id FROM categories");
$categories_total = $categories_result ? $categories_result->num_rows : 0;
$sitemap_items = isset($options['sitemap_items']) && intval($options['sitemap_items']) > 0 ? intval($options['sitemap_items']) : 1000;
$sitemap_total = $published_news > 0 ? ceil($published_news / $sitemap_items) : 0;

if ($start != 0) {
if (!isset($_GET['month']) OR empty($_GET['month']) OR $_GET['month'] > 12) {$current_month = date('n');} else {$current_month = intval($_GET['month']);}
if (!isset($_GET['year']) OR empty($_GET['year']) OR $_GET['year'] > date('Y')) {$current_year = date('Y');} else {$current_year = intval($_GET['year']);}
?>
<div class="row">
	<div class="col-md-4">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Published News</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#1f2937;"><?php echo $published_news; ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Categories</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#1f2937;"><?php echo $categories_total; ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Sitemaps</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#1f2937;"><?php echo $sitemap_total; ?></div>
			</div>
		</div>
	</div>
</div>

<div class="page-header page-heading">
	<div class="row">
		<div class="col-md-9"><h1><i class="fa fa-bar-chart"></i> News Statistics For <span class="text-info"><?php echo month_name($current_month).', '.$current_year; ?></span></h1></div>
		<div class="col-md-3">
			<form method="GET" name="menu">
				<select name="selectedPage" onChange="changePage(this.form.selectedPage)" class="form-control">
					<option>Choose a Month</option>
					<?php
					echo generate_statics_select($start['year'],$start['month']);
					?>
				</select>
			</form>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-body">
<?php
$thetime = mktime(0, 0, 0, $current_month, 3, $current_year);
$days = date('t',$thetime);
?>
<script>
					$(function() {
					Morris.Bar({
						element: 'morris-area-chart',
						data: [
						<?php for($i=1;$i<$days+1;$i++) {
						?>
						<?php echo "{"; ?>
						periods: '<?php echo $current_year.'-'.$current_month.'-'.$i; ?>',
						news: <?php echo $general->statistics_news($i,$current_month,$current_year); ?>
						<?php echo "}, "; ?>
						<?php } ?>
						],
						xkey: 'periods',
						ykeys: ['news'],
						labels: ['News'],
						barColors: ['#61A9DC'],
						pointSize: 4,
						hideHover: 'auto',
						resize: true
					});
					});
					</script>
					<div id="morris-area-chart" style="min-height:320px;"></div>
				</div>
</div>
<?php
}

if ($published_news > 0) {
?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title"><i class="fa fa-sitemap"></i> Sitemaps</h3>
	</div>
	<table class="table">
		<thead>
			<tr>
				<th>Sitemap</th>
			</tr>
		</thead>
		<tbody>
<?php 
if ($categories_total > 0) {
$categories_sitemap_link = $options['siteurl'].'/categories-sitemap.xml';
$categories_sitemap = str_replace(':/','://',str_replace('//','/',($categories_sitemap_link)));
?>
			<tr><td><a href="<?php echo $categories_sitemap; ?>" target="_BLANK"><?php echo $categories_sitemap; ?></a></td></tr>
<?php	
}

for($c=0;$c<$sitemap_total;$c++) {
	$n = $c+1;
	$sitemap_link = $options['siteurl'].'/sitemap-'.$n.'.xml';
	$sitemap = str_replace(':/','://',str_replace('//','/',($sitemap_link)));
?>
			<tr><td><a href="<?php echo $sitemap; ?>" target="_BLANK"><?php echo $sitemap; ?></a></td></tr>
<?php
}
?>
		</tbody>
	</table>
</div>
<?php
}
include('footer.php');
?>