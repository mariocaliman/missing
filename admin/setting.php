<?php include('header.php'); ?>
<div class="row">
<div class="col-md-3">
<div class="list-group">
	<a href="setting.php" class="list-group-item <?php if (!isset($_GET['case'])) {echo 'active';} ?>"><span class="fa fa-cog"></span> General Setting</a>
	<a href="setting.php?case=theme" class="list-group-item <?php if (isset($_GET['case']) AND $_GET['case'] == 'theme') {echo 'active';} ?>"><span class="fa fa-paint-brush"></span> Theme Setting</a>
	<a href="setting.php?case=apis" class="list-group-item <?php if (isset($_GET['case']) AND $_GET['case'] == 'apis') {echo 'active';} ?>"><span class="fa fa-key"></span> APIS</a>
	<a href="setting.php?case=clear_cache" class="list-group-item <?php if (isset($_GET['case']) AND $_GET['case'] == 'clear_cache') {echo 'active';} ?>"><span class="fa fa-eraser"></span> Clear Cache</a>
	<a href="setting.php?case=optimize_database" class="list-group-item <?php if (isset($_GET['case']) AND $_GET['case'] == 'optimize_database') {echo 'active';} ?>"><span class="fa fa-database"></span> Optimize Database</a>
	<a href="setting.php?case=remove_old_news" class="list-group-item <?php if (isset($_GET['case']) AND $_GET['case'] == 'remove_old_news') {echo 'active';} ?>"><span class="fa fa-trash"></span> Remove Old News</a>
</div>
</div>
<div class="col-md-9">
<?php 
if (!empty($_GET['case'])) {
$case = make_safe($_GET['case']);	
} else {
$case = '';	
}
switch ($case) {
case 'apis':
if (isset($_POST['save'])) {
$message = $general->set_options($_POST,'AI');
}
$options = $general->get_options('AI');
?>
        <div class="page-header page-heading">
            <h1><i class="fa fa-key"></i> APIs</h1>
        </div>
		<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="">
		  <div class="alert alert-info">
			Use these settings for AI draft generation in Add Article.
		  </div>
		  <div class="form-group">
			<label for="openai_api_key">OpenAI API Key</label>
			<input type="password" class="form-control" name="openai_api_key" id="openai_api_key" value="<?php if (isset($options['openai_api_key'])) {echo htmlspecialchars_decode($options['openai_api_key'],ENT_QUOTES);} ?>" autocomplete="off" />
		  </div>
		  <div class="form-group">
			<label for="openai_model">Model</label>
			<input type="text" class="form-control" name="openai_model" id="openai_model" value="<?php if (isset($options['openai_model']) AND !empty($options['openai_model'])) {echo htmlspecialchars_decode($options['openai_model'],ENT_QUOTES);} else {echo 'gpt-4o-mini';} ?>" />
		  </div>
		  <div class="form-group">
			<label for="openai_base_url">Endpoint</label>
			<input type="text" class="form-control" name="openai_base_url" id="openai_base_url" value="<?php if (isset($options['openai_base_url']) AND !empty($options['openai_base_url'])) {echo htmlspecialchars_decode($options['openai_base_url'],ENT_QUOTES);} else {echo 'https://api.openai.com/v1/chat/completions';} ?>" />
		  </div>
		  <div class="form-group">
			<label for="openai_image_model">Image Model</label>
			<input type="text" class="form-control" name="openai_image_model" id="openai_image_model" value="<?php if (isset($options['openai_image_model']) AND !empty($options['openai_image_model'])) {echo htmlspecialchars_decode($options['openai_image_model'],ENT_QUOTES);} else {echo 'gpt-image-1';} ?>" />
		  </div>
		  <div class="form-group">
			<label for="openai_image_endpoint">Image Endpoint</label>
			<input type="text" class="form-control" name="openai_image_endpoint" id="openai_image_endpoint" value="<?php if (isset($options['openai_image_endpoint']) AND !empty($options['openai_image_endpoint'])) {echo htmlspecialchars_decode($options['openai_image_endpoint'],ENT_QUOTES);} else {echo 'https://api.openai.com/v1/images/generations';} ?>" />
		  </div>
		  <button type="submit" name="save" class="btn btn-primary">Save</button>
		</form>

<?php
break;
case 'optimize_database':
if (isset($_POST['save'])) {
	$message = notification('success','Database Optimized Successfuly.');
}   
?>

                <div class="page-header page-heading">
                    <h1><i class="fa fa-database"></i> Optimize Database</h1>
                </div>
	<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="">
		  <div class="form-group">
			<label for="facebook_account">Are you sure that you want to Optimize the Database ?</label>
		  </div>
		  <button type="submit" name="save" class="btn btn-warning">Optimize Database</button>
		</form>
	
<?php 
break;
case 'clear_cache':
if (isset($_POST['save'])) {
	$folder = '../cache';
	$delete = empty_templates_cache($folder);
	if ($delete) {
	$message = notification('success','All Cache Files Are Cleared.');
	} else {
	$message = notification('danger','Error Happened.');
	}
}   
?>

                <div class="page-header page-heading">
                    <h1><i class="fa fa-eraser"></i> Clear Cache</h1>
                </div>
	<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="">
		  <div class="form-group">
			<label for="facebook_account">Are you sure that you want to clear all cached files ?</label>
		  </div>
		  <button type="submit" name="save" class="btn btn-danger">Clear Cache</button>
		</form>
	
<?php 
break;
case 'remove_old_news':
if (isset($_POST['save'])) {
	$period = intval($_POST['period']);
	$hits = intval($_POST['hits']);
	$category = intval($_POST['category']);
	if (isset($_POST['source'])) {
	$source = intval($_POST['source']);
	} else {
	$source = '';	
	}
	if (isset($_POST['no_image'])) {
	$no_image = intval($_POST['no_image']);
	} else {
	$no_image = 0;	
	}
	$now = time();
	$delete_time = $now-$period;
	$where = "datetime < $delete_time";
	if ($hits > 0) {
	$where .= " AND hits < $hits";	
	}
	if ($no_image == 1) {
	$where .= " AND thumbnail=''";	
	}
	if ($category > 1) {
	$where .= " AND category_id='$category'";	
	}
	if (!empty($source)) {
	$where .= " AND source_id='$source'";	
	}
	$sql = "SELECT * FROM news WHERE $where";
	$query = $mysqli->query($sql);
	$isthere = $query->num_rows;
	if ($isthere > 0) {
	while ($row = $query->fetch_assoc()) {
		if (!empty($row['thumbnail']) AND file_exists('../upload/news/'.$row['thumbnail'])) {
			@unlink('../upload/news/'.$row['thumbnail']);
		}
	}	
	}
	$delete = $mysqli->query("DELETE FROM news WHERE $where");
	$affected = $mysqli->affected_rows;
	if ($delete) {
	if ($affected == 0) {
	$message = notification('warning','There Are no News to Delete.');	
	} else {	
	$message = notification('success','All Selected News Are Cleared.');
	}
	} else {
	$message = notification('danger','Error Happened.');
	}
}   
?>

        <div class="page-header page-heading">
            <h1><i class="fa fa-trash"></i> Remove Old News</h1>
        </div>
	<?php if (isset($message)) {echo $message;} else {echo notification('warning','Be careful, This Action can\'t be undo.');} ?>
		<form role="form" method="POST" action="">
		  <div class="form-group">
		  <div class="row">
		  <div class="col-md-6">
			<label for="period">Select The Period</label>
			<select class="form-control" name="period" id="period">
				<option value="31104000">Older Than a Year</option>
				<option value="15552000">Older Than 6 months</option>
				<option value="7776000">Older Than 3 months</option>
				<option value="2592000">Older Than a month</option>
				<option value="1209600">Older Than 2 Weeks</option>
				<option value="604800">Older Than a Week</option>
				<option value="259200">Older Than 3 Days</option>				
			</select>
		  </div>
		  <div class="col-md-6">
			<label for="hits">Visits Number</label>
			<select class="form-control" name="hits" id="hits">
				<option value="0">Doesn't Matter</option>
				<option value="2">Less Than 2</option>
				<option value="5">Less Than 5</option>
				<option value="10">Less Than 10</option>
				<option value="25">Less Than 25</option>
			</select>
		  </div>
		  <div class="clear"></div>
		  </div>
		  </div>
		  <div class="form-group">
		  <div class="row">
		  <div class="col-md-6">
			<label for="category">Select Category</label>
			<select class="form-control" name="category" id="category">
				<option value="0">All Categories</option>
				<?php 
				$categories = $general->categories('category_order ASC');
				foreach ($categories AS $category) {
				?>
				<option value="<?php echo $category['id']; ?>"><?php echo $category['category']; ?></option>
				<?php	
				}
				?>
			</select>
		  </div>
		  <div class="col-md-6">
			<label for="source">Select Source</label>
			<select class="form-control" name="source" id="source">
				<option value="">All Sources</option>
				<option value="0">Private</option>
				<?php 
				$sources = $general->sources();
				foreach ($sources AS $source) {
				?>
				<option value="<?php echo $source['id']; ?>"><?php echo $source['title']; ?></option>
				<?php	
				}
				?>
			</select>
		  </div>
		  <div class="clear"></div>
		  </div>
		  </div>
		  <div class="form-group">
			<input type="checkbox" name="no_image" id="no_image" value="1" /> <span class="checkbox-label">Only News That Has No Image ?</span>
		  </div>
		  <button type="submit" name="save" class="btn btn-danger">Remove</button>
		</form>
	
<?php 
break;
case 'theme':
$theme = $general->get_options('General');
if (isset($_POST['save'])) {
$message = $general->set_options($_POST,'Theme');
}
$options = $general->get_options('Theme'); 
include('../themes/'.$theme['site_theme'].'/theme-options.php');
break;
default:
if (isset($_POST['save'])) {
$_POST['rewrite_imported_news'] = isset($_POST['rewrite_imported_news']) ? 1 : 0;
$message = $general->set_options($_POST,'General');
}
$options = $general->get_options('General'); 
?>
        <div class="page-header page-heading">
            <h1><i class="fa fa-cog"></i> General Setting</h1>
        </div>
		<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="">
		  <div class="form-group">
			<label for="siteurl">Site Url</label>
			<input type="text" class="form-control" name="siteurl" id="siteurl" placeholder="http://www.domain.com" value="<?php echo $options['siteurl']; ?>" />
		  </div>
		  <div class="form-group">
			<label for="seo_title">Site Name</label>
			<input type="text" class="form-control" name="seo_title" id="seo_title" placeholder="your site title" value="<?php echo $options['seo_title']; ?>" />
		  </div>
		  
		  <div class="form-group">
			<label for="seo_keywords">SEO Keywords</label>
			<input type="text" class="form-control" name="seo_keywords" id="seo_keywords" placeholder="news,rss,feeds" value="<?php echo $options['seo_keywords']; ?>" />
		  </div>
		  <div class="form-group">
			<label for="seo_description">SEO Description</label>
			<textarea class="form-control" name="seo_description" id="seo_description" rows="3" placeholder="some words about the site .. don't exceed 255 character."><?php echo $options['seo_description']; ?></textarea>
		  </div>
		  <div class="form-group">
			<label for="top_news_period">Top News Period (Days)</label>
			<select name="top_news_period" id="top_news_period" class="form-control">
				<option value="86400" <?php if ($options['top_news_period'] == 86400) {echo 'SELECTED';} ?>>Today</option>
				<option value="259200" <?php if ($options['top_news_period'] == 259200) {echo 'SELECTED';} ?>>Last Three Days</option>
				<option value="604800" <?php if ($options['top_news_period'] == 604800) {echo 'SELECTED';} ?>>This Week</option>
				<option value="2592000" <?php if ($options['top_news_period'] == 2592000) {echo 'SELECTED';} ?>>This Month</option>
			</select>
			</div>
		   <div class="form-group">
			<label for="site_theme">Site Theme</label>
			<select name="site_theme" id="site_theme" class="form-control">
				<?php
				$path = '../themes/';
				$results = glob($path . "*");
					foreach ($results as $result) {
						if ($result === '.' or $result === '..') continue;
						if(is_dir($result)) {
						
						echo "
						<option value='".str_replace($path,'',$result)."'";
						if ($options['site_theme'] == str_replace($path,'',$result)) {
						echo 'SELECTED';
						}
						echo ">".str_replace($path,'',$result)."</option>";		
						}
						}
						?>						
			</select>
		   </div>
		   <div class="form-group">
			<input type="checkbox" name="display_rss_link" id="display_rss_link" value="1" <?php if (isset($options['display_rss_link']) AND $options['display_rss_link'] == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Display RSS Link ?</span>
		  </div>
		   <div class="form-group">
			<label for="rss_news_number">Number of News in RSS file (default 10)</label>
			<select name="rss_news_number" id="rss_news_number" class="form-control">
			<?php for($r=5;$r<51;$r=$r+5) { ?>
				<option value="<?php echo $r; ?>" <?php if (isset($options['rss_news_number']) AND $options['rss_news_number'] == $r) {echo 'SELECTED';} ?>><?php echo $r; ?></option>
			<?php } ?>
			</select>
			</div>
		   <div class="form-group">
			<label for="sitemap_items">Number of News in each Sitemap</label>
			<select name="sitemap_items" id="sitemap_items" class="form-control">
			<?php for($s=1000;$s<11000;$s=$s+1000) { ?>
				<option value="<?php echo $s; ?>" <?php if (isset($options['sitemap_items']) AND $options['sitemap_items'] == $s) {echo 'SELECTED';} ?>><?php echo $s; ?></option>
			<?php } ?>
			</select>
			</div>
			<div class="form-group">
			<input type="checkbox" name="display_disqus_comments" id="display_disqus_comments" value="1" <?php if (isset($options['display_disqus_comments']) AND $options['display_disqus_comments'] == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Display Disqus Comments ?</span>
		  </div>
		  <div class="form-group">
			<input type="checkbox" name="rewrite_imported_news" id="rewrite_imported_news" value="1" <?php if (!isset($options['rewrite_imported_news']) OR $options['rewrite_imported_news'] == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Rewrite imported news with emotional multi-paragraph text</span>
		  </div>
		  <div class="form-group">
			<label for="disqus_shortname">Disqus Shortname</label>
			<input type="text" class="form-control" name="disqus_shortname" id="disqus_shortname" value="<?php if (isset($options['disqus_shortname'])) { echo $options['disqus_shortname']; }?>" />
		  </div>
		   <input type="hidden" name="installed" value="1" />
		  <button type="submit" name="save" class="btn btn-primary">Save</button>
		</form>
<?php } ?>
</div>
</div>
<?php include('footer.php'); ?>
 