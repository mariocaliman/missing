<?php
include('header.php');
include('../include/donation_tracking.php');

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
if ($days < 1) {
    $days = 30;
}

$table_ready = donation_tracking_ensure_table($mysqli);

$clicked = 0;
$positive = 0;
$cancelled = 0;
$pending = 0;
$conversion_rate = 0;
$abandon_rate = 0;
$rows = array();

if ($table_ready) {
    $where = '';
    if ($days > 0) {
        $since = time() - ($days * 86400);
        $where = ' WHERE clicked_at >= ' . intval($since) . ' ';
    }

    $sql = "SELECT
        COUNT(*) AS total_clicked,
        SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS total_success,
        SUM(CASE WHEN status='cancel' THEN 1 ELSE 0 END) AS total_cancel,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS total_pending
        FROM donation_tracking" . $where;

    $query = $mysqli->query($sql);
    if ($query) {
        $stats = $query->fetch_assoc();
        $clicked = isset($stats['total_clicked']) ? intval($stats['total_clicked']) : 0;
        $positive = isset($stats['total_success']) ? intval($stats['total_success']) : 0;
        $cancelled = isset($stats['total_cancel']) ? intval($stats['total_cancel']) : 0;
        $pending = isset($stats['total_pending']) ? intval($stats['total_pending']) : 0;
    }

    if ($clicked > 0) {
        $conversion_rate = round(($positive / $clicked) * 100, 2);
        $abandon_rate = round(($cancelled / $clicked) * 100, 2);
    }

    $list_sql = "SELECT donation_ref, page_path, status, clicked_at, status_updated_at FROM donation_tracking" . $where . " ORDER BY id DESC LIMIT 100";
    $list_query = $mysqli->query($list_sql);
    if ($list_query && $list_query->num_rows > 0) {
        while ($row = $list_query->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}
?>

<div class="page-header page-heading">
	<div class="row">
		<div class="col-md-8">
			<h1><i class="fa fa-heart"></i> Donations Analytics</h1>
		</div>
		<div class="col-md-4">
			<form method="GET" class="form-inline text-right" style="margin-top:22px;">
				<label for="days" style="margin-right:8px;">Period</label>
				<select id="days" name="days" class="form-control" onchange="this.form.submit()">
					<option value="7" <?php if ($days === 7) { echo 'selected'; } ?>>Last 7 days</option>
					<option value="30" <?php if ($days === 30) { echo 'selected'; } ?>>Last 30 days</option>
					<option value="90" <?php if ($days === 90) { echo 'selected'; } ?>>Last 90 days</option>
					<option value="365" <?php if ($days === 365) { echo 'selected'; } ?>>Last 365 days</option>
					<option value="99999" <?php if ($days === 99999) { echo 'selected'; } ?>>All time</option>
				</select>
			</form>
		</div>
	</div>
</div>

<?php if (!$table_ready) { ?>
<div class="alert alert-danger">Could not initialize donation analytics table.</div>
<?php } ?>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Clicked Donate</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#1f2937;"><?php echo $clicked; ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Returned Positive</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#16a34a;"><?php echo $positive; ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">Gave Up (Cancel)</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#dc2626;"><?php echo $cancelled; ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-body text-center">
				<div class="text-uppercase text-muted" style="letter-spacing:.06em; font-size:12px;">No Return Yet</div>
				<div style="font-size:34px; font-weight:700; line-height:1.1; color:#6b7280;"><?php echo $pending; ?></div>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><strong>Conversion Rate</strong></div>
			<div class="panel-body">
				<h3 style="margin:0;"><?php echo number_format($conversion_rate, 2); ?>%</h3>
				<p class="text-muted" style="margin-top:8px;">Returned Positive / Clicked Donate</p>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><strong>Cancel Rate</strong></div>
			<div class="panel-body">
				<h3 style="margin:0;"><?php echo number_format($abandon_rate, 2); ?>%</h3>
				<p class="text-muted" style="margin-top:8px;">Gave Up (Cancel) / Clicked Donate</p>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list"></i> Latest Donation Journeys</h3></div>
	<div class="table-responsive">
		<table class="table table-striped" style="margin-bottom:0;">
			<thead>
				<tr>
					<th>Reference</th>
					<th>Page</th>
					<th>Status</th>
					<th>Clicked At</th>
					<th>Status Updated</th>
				</tr>
			</thead>
			<tbody>
			<?php if (!empty($rows)) { ?>
				<?php foreach ($rows as $row) { ?>
				<tr>
					<td><?php echo htmlspecialchars(substr($row['donation_ref'], 0, 12), ENT_QUOTES, 'UTF-8'); ?>...</td>
					<td><?php echo htmlspecialchars($row['page_path'], ENT_QUOTES, 'UTF-8'); ?></td>
					<td>
						<?php
						$status = $row['status'];
						$label = 'label-default';
						if ($status === 'success') {
							$label = 'label-success';
						} elseif ($status === 'cancel') {
							$label = 'label-danger';
						} elseif ($status === 'pending') {
							$label = 'label-warning';
						}
						?>
						<span class="label <?php echo $label; ?>"><?php echo htmlspecialchars(strtoupper($status), ENT_QUOTES, 'UTF-8'); ?></span>
					</td>
					<td><?php echo date('Y-m-d H:i:s', intval($row['clicked_at'])); ?></td>
					<td><?php echo !empty($row['status_updated_at']) ? date('Y-m-d H:i:s', intval($row['status_updated_at'])) : '-'; ?></td>
				</tr>
				<?php } ?>
			<?php } else { ?>
				<tr><td colspan="5" class="text-center text-muted" style="padding:18px;">No donation records found for the selected period.</td></tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
</div>

<?php include('footer.php'); ?>
